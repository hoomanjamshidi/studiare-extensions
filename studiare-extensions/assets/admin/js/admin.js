/**
 * Studiare Extensions — admin shell.
 *
 * Provides the pieces every module panel shares and exposes them as
 * `window.STX` for module scripts:
 *   - store:    module settings with get/set by dot-path, dirty tracking and subscriptions
 *   - binding:  `[data-stx-bind]` controls ⇄ store, `[data-stx-show-if]` conditions
 *   - widgets:  tabs, colour fields, range read-outs
 *   - actions:  save (Ctrl/⌘+S), reset, dashboard module switches
 *   - feedback: toasts and a promise-based confirm dialog
 */
( function () {
	'use strict';

	const app = document.getElementById( 'stx-admin' );
	if ( ! app ) {
		return;
	}

	const data = window.stxAdmin || {};
	const i18n = data.i18n || {};

	/* ---------------------------------------------------------------------
	 * Utilities
	 * ------------------------------------------------------------------- */

	const clone = ( value ) => JSON.parse( JSON.stringify( value ) );

	function getPath( object, path ) {
		return path.split( '.' ).reduce( ( node, key ) => ( node == null ? undefined : node[ key ] ), object );
	}

	function setPath( object, path, value ) {
		const keys = path.split( '.' );
		const last = keys.pop();
		const target = keys.reduce( ( node, key ) => {
			if ( node[ key ] == null || typeof node[ key ] !== 'object' ) {
				node[ key ] = {};
			}
			return node[ key ];
		}, object );

		target[ last ] = value;
	}

	function escapeHtml( value ) {
		return String( value == null ? '' : value ).replace( /[&<>"']/g, ( char ) => (
			{ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ char ]
		) );
	}

	/**
	 * Evaluates a condition such as `style=floating|pill;!layout.glass`.
	 * Parts separated by `;` must all pass.
	 *
	 * @param {string}   expression Condition.
	 * @param {Function} lookup     Resolves a path to its current value.
	 */
	function evaluate( expression, lookup ) {
		return expression.split( ';' ).every( ( rawPart ) => {
			const part = rawPart.trim();
			if ( ! part ) {
				return true;
			}
			if ( part.charAt( 0 ) === '!' ) {
				return ! lookup( part.slice( 1 ) );
			}

			const eq = part.indexOf( '=' );
			if ( eq === -1 ) {
				return Boolean( lookup( part ) );
			}

			return part.slice( eq + 1 ).split( '|' ).includes( String( lookup( part.slice( 0, eq ) ) ) );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Store
	 * ------------------------------------------------------------------- */

	function createStore( initial ) {
		let state = clone( initial || {} );
		let savedSnapshot = JSON.stringify( state );
		const listeners = new Set();

		const emit = ( path ) => listeners.forEach( ( listener ) => listener( path, state ) );

		return {
			get: ( path ) => ( path ? getPath( state, path ) : state ),

			set( path, value ) {
				setPath( state, path, value );
				emit( path );
			},

			/** Replaces everything (after save/reset). `clean` marks it as saved. */
			replace( next, options = {} ) {
				state = clone( next );
				if ( options.clean ) {
					savedSnapshot = JSON.stringify( state );
				}
				emit( '*' );
			},

			isDirty: () => JSON.stringify( state ) !== savedSnapshot,

			subscribe( listener ) {
				listeners.add( listener );
				return () => listeners.delete( listener );
			},
		};
	}

	const store = data.settings ? createStore( data.settings ) : null;

	/* ---------------------------------------------------------------------
	 * Feedback: toasts and confirm dialog
	 * ------------------------------------------------------------------- */

	const toastHost = app.querySelector( '.stx-toasts' );

	function toast( message, type = 'success' ) {
		const node = document.createElement( 'div' );
		node.className = 'stx-toast stx-toast--' + type;
		node.setAttribute( 'role', type === 'error' ? 'alert' : 'status' );
		node.textContent = message;
		toastHost.appendChild( node );

		window.setTimeout( () => {
			node.classList.add( 'is-leaving' );
			window.setTimeout( () => node.remove(), 300 );
		}, type === 'error' ? 5000 : 3000 );
	}

	/**
	 * @param {Object}  options
	 * @param {string}  options.title
	 * @param {string}  options.message
	 * @param {string}  options.confirmLabel
	 * @param {boolean} [options.danger]
	 * @return {Promise<boolean>}
	 */
	function confirmDialog( options ) {
		return new Promise( ( resolve ) => {
			const dialog = document.createElement( 'dialog' );
			dialog.className = 'stx-dialog stx-dialog--confirm';
			dialog.innerHTML =
				'<div class="stx-dialog__head"><h2>' + escapeHtml( options.title ) + '</h2></div>' +
				'<div class="stx-dialog__body"><p>' + escapeHtml( options.message ) + '</p></div>' +
				'<div class="stx-dialog__actions">' +
					'<button type="button" class="stx-btn stx-btn--ghost" value="cancel">' + escapeHtml( i18n.cancel ) + '</button>' +
					'<button type="button" class="stx-btn ' + ( options.danger ? 'stx-btn--danger' : 'stx-btn--primary' ) + '" value="ok">' + escapeHtml( options.confirmLabel ) + '</button>' +
				'</div>';

			app.appendChild( dialog );

			const finish = ( result ) => {
				dialog.close();
				dialog.remove();
				resolve( result );
			};

			dialog.addEventListener( 'click', ( event ) => {
				const button = event.target.closest( 'button[value]' );
				if ( button ) {
					finish( button.value === 'ok' );
				} else if ( event.target === dialog ) {
					finish( false ); // Backdrop click.
				}
			} );
			dialog.addEventListener( 'cancel', ( event ) => {
				event.preventDefault();
				finish( false );
			} );

			dialog.showModal();
			dialog.querySelector( 'button[value="cancel"]' ).focus();
		} );
	}

	/* ---------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------- */

	async function post( action, payload = {} ) {
		const body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', data.nonce );
		Object.keys( payload ).forEach( ( key ) => body.append( key, payload[ key ] ) );

		let json = null;
		try {
			const response = await fetch( data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } );
			json = await response.json();
		} catch ( error ) {
			throw new Error( i18n.saveFailed );
		}

		if ( ! json || ! json.success ) {
			throw new Error( ( json && json.data && json.data.message ) || i18n.saveFailed );
		}

		return json.data;
	}

	/* ---------------------------------------------------------------------
	 * Field binding
	 * ------------------------------------------------------------------- */

	function readControl( control ) {
		if ( control.type === 'checkbox' ) {
			return control.checked;
		}

		if ( control.dataset.stxType === 'number' ) {
			const number = parseFloat( control.value );
			return Number.isFinite( number ) ? number : 0;
		}

		return control.value;
	}

	function writeControl( control, value ) {
		if ( control.type === 'checkbox' ) {
			control.checked = Boolean( value );
		} else if ( control.type === 'radio' ) {
			control.checked = String( value ) === control.value;
		} else if ( document.activeElement !== control || control.type === 'range' ) {
			// Never rewrite a text field while the user is typing in it.
			control.value = value == null ? '' : value;
		}

		if ( control.type === 'range' ) {
			paintRange( control );
		}
	}

	/** Colours the filled part of a range track. */
	function paintRange( control ) {
		const min = parseFloat( control.min ) || 0;
		const max = parseFloat( control.max ) || 100;
		const percent = ( ( parseFloat( control.value ) - min ) / ( max - min ) ) * 100;
		control.style.setProperty( '--stx-fill', percent + '%' );
	}

	function formatOutput( value ) {
		return typeof value === 'number' && ! Number.isInteger( value ) ? value.toFixed( 2 ).replace( /0$/, '' ) : String( value );
	}

	function bindControls( scope ) {
		const controls = Array.from( scope.querySelectorAll( '[data-stx-bind]' ) );
		const outputs = Array.from( scope.querySelectorAll( '[data-stx-output]' ) );
		const conditionals = Array.from( scope.querySelectorAll( '[data-stx-show-if]' ) );

		controls.forEach( ( control ) => {
			const isChoice = control.type === 'checkbox' || control.type === 'radio' || control.tagName === 'SELECT';

			control.addEventListener( isChoice ? 'change' : 'input', () => {
				if ( control.type === 'radio' && ! control.checked ) {
					return;
				}
				store.set( control.dataset.stxBind, readControl( control ) );
			} );
		} );

		function sync() {
			controls.forEach( ( control ) => writeControl( control, store.get( control.dataset.stxBind ) ) );
			outputs.forEach( ( output ) => {
				output.textContent = formatOutput( store.get( output.dataset.stxOutput ) );
			} );
			conditionals.forEach( ( node ) => {
				node.hidden = ! evaluate( node.dataset.stxShowIf, store.get );
			} );
		}

		store.subscribe( sync );
		sync();
	}

	/* ---------------------------------------------------------------------
	 * Tabs (remembers the last tab per module)
	 * ------------------------------------------------------------------- */

	function initTabs() {
		app.querySelectorAll( '[data-stx-tabs]' ).forEach( ( list ) => {
			const storageKey = 'stx-tab-' + list.dataset.stxTabs;
			const tabs = Array.from( list.querySelectorAll( '[data-stx-tab]' ) );

			function select( id, focus ) {
				tabs.forEach( ( tab ) => {
					const selected = tab.dataset.stxTab === id;
					const panel = document.getElementById( tab.getAttribute( 'aria-controls' ) );

					tab.setAttribute( 'aria-selected', String( selected ) );
					tab.tabIndex = selected ? 0 : -1;
					if ( panel ) {
						panel.hidden = ! selected;
					}
					if ( selected && focus ) {
						tab.focus();
					}
				} );

				try {
					window.localStorage.setItem( storageKey, id );
				} catch ( error ) {
					// Remembering the tab is optional.
				}
			}

			tabs.forEach( ( tab ) => tab.addEventListener( 'click', () => select( tab.dataset.stxTab ) ) );

			list.addEventListener( 'keydown', ( event ) => {
				if ( event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' ) {
					return;
				}

				const rtl = getComputedStyle( list ).direction === 'rtl';
				const forward = ( event.key === 'ArrowRight' ) !== rtl;
				const index = tabs.findIndex( ( tab ) => tab.getAttribute( 'aria-selected' ) === 'true' );
				const next = tabs[ ( index + ( forward ? 1 : -1 ) + tabs.length ) % tabs.length ];

				event.preventDefault();
				select( next.dataset.stxTab, true );
			} );

			let remembered = null;
			try {
				remembered = window.localStorage.getItem( storageKey );
			} catch ( error ) {
				remembered = null;
			}

			select( tabs.some( ( tab ) => tab.dataset.stxTab === remembered ) ? remembered : tabs[ 0 ].dataset.stxTab );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Colour fields: swatch (native picker) + free text (hex/rgba) + reset
	 * ------------------------------------------------------------------- */

	const RESET_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';

	const colorProbe = document.createElement( 'canvas' ).getContext( '2d' );

	/** Converts any CSS colour to #rrggbb for <input type="color">. */
	function toHex( color ) {
		if ( ! color || ! colorProbe ) {
			return '#000000';
		}

		colorProbe.fillStyle = '#000000';
		colorProbe.fillStyle = color;
		const normalized = colorProbe.fillStyle;

		if ( normalized.charAt( 0 ) === '#' ) {
			return normalized;
		}

		const channels = normalized.match( /\d+(\.\d+)?/g ) || [ 0, 0, 0 ];
		return '#' + channels.slice( 0, 3 ).map( ( channel ) => Number( channel ).toString( 16 ).padStart( 2, '0' ) ).join( '' );
	}

	const isValidColor = ( value ) => window.CSS && CSS.supports( 'color', value );

	function initColorFields( scope ) {
		scope.querySelectorAll( '[data-stx-color]' ).forEach( ( host ) => {
			const path = host.dataset.stxColor;
			const fallback = host.dataset.fallback || '';
			const labelNode = host.parentElement.querySelector( '.stx-field__label' );
			const label = labelNode ? labelNode.textContent.trim() : '';

			host.innerHTML =
				'<label class="stx-color__swatch"><input type="color" tabindex="-1" aria-hidden="true"></label>' +
				'<input type="text" class="stx-color__text" spellcheck="false" autocomplete="off" aria-label="' + escapeHtml( label ) + '" placeholder="' + escapeHtml( i18n.themeDefault ) + '">' +
				'<button type="button" class="stx-icon-btn stx-color__reset" title="' + escapeHtml( i18n.resetColor ) + '" aria-label="' + escapeHtml( i18n.resetColor ) + '">' + RESET_ICON + '</button>';

			const picker = host.querySelector( 'input[type="color"]' );
			const text = host.querySelector( '.stx-color__text' );
			const swatch = host.querySelector( '.stx-color__swatch' );

			function render() {
				const value = store.get( path ) || '';
				host.classList.toggle( 'has-value', value !== '' );
				swatch.style.setProperty( '--stx-swatch', value || fallback );
				picker.value = toHex( value || fallback );
				if ( document.activeElement !== text ) {
					text.value = value;
				}
			}

			picker.addEventListener( 'input', () => store.set( path, picker.value ) );
			text.addEventListener( 'input', () => {
				const value = text.value.trim();
				if ( value === '' || isValidColor( value ) ) {
					store.set( path, value );
				}
			} );
			text.addEventListener( 'blur', render );
			host.querySelector( '.stx-color__reset' ).addEventListener( 'click', () => store.set( path, '' ) );

			store.subscribe( render );
			render();
		} );
	}

	/* ---------------------------------------------------------------------
	 * Save / reset
	 * ------------------------------------------------------------------- */

	function setStatusDot( moduleId, enabled ) {
		const dot = app.querySelector( '[data-stx-status="' + moduleId + '"]' );
		if ( dot ) {
			dot.classList.toggle( 'is-on', Boolean( enabled ) );
		}
	}

	function initSaving() {
		const saveButton = app.querySelector( '[data-stx-save]' );
		const resetButton = app.querySelector( '[data-stx-reset]' );
		const dirtyBadge = app.querySelector( '[data-stx-dirty]' );
		const saveLabel = saveButton.querySelector( '[data-stx-save-label]' );
		let saving = false;

		function refresh() {
			const dirty = store.isDirty();
			saveButton.disabled = saving || ! dirty;
			dirtyBadge.hidden = ! dirty;
		}

		async function save() {
			if ( saving || ! store.isDirty() ) {
				return;
			}

			saving = true;
			saveButton.classList.add( 'is-busy' );
			saveLabel.textContent = i18n.saving;
			refresh();

			try {
				const result = await post( 'stx_save_settings', { module: data.module, settings: JSON.stringify( store.get() ) } );
				store.replace( result.settings, { clean: true } );
				setStatusDot( data.module, result.settings.enabled );
				toast( i18n.saved );
			} catch ( error ) {
				toast( error.message, 'error' );
			} finally {
				saving = false;
				saveButton.classList.remove( 'is-busy' );
				saveLabel.textContent = i18n.save;
				refresh();
			}
		}

		async function reset() {
			const confirmed = await confirmDialog( {
				title: i18n.resetTitle,
				message: i18n.resetMessage,
				confirmLabel: i18n.resetConfirm,
				danger: true,
			} );
			if ( ! confirmed ) {
				return;
			}

			try {
				const result = await post( 'stx_reset_settings', { module: data.module } );
				store.replace( result.settings, { clean: true } );
				setStatusDot( data.module, result.settings.enabled );
				toast( i18n.resetDone );
			} catch ( error ) {
				toast( error.message, 'error' );
			}
		}

		saveButton.addEventListener( 'click', save );
		resetButton.addEventListener( 'click', reset );

		document.addEventListener( 'keydown', ( event ) => {
			if ( ( event.metaKey || event.ctrlKey ) && event.key.toLowerCase() === 's' ) {
				event.preventDefault();
				save();
			}
		} );

		window.addEventListener( 'beforeunload', ( event ) => {
			if ( store.isDirty() ) {
				event.preventDefault();
				event.returnValue = i18n.unsavedLeave;
			}
		} );

		store.subscribe( refresh );
		refresh();
	}

	/* ---------------------------------------------------------------------
	 * Dashboard: enable/disable modules instantly
	 * ------------------------------------------------------------------- */

	function initModuleSwitches() {
		app.querySelectorAll( '[data-stx-module-toggle]' ).forEach( ( input ) => {
			input.addEventListener( 'change', async () => {
				const moduleId = input.dataset.stxModuleToggle;
				const enabled = input.checked;
				input.disabled = true;

				try {
					await post( 'stx_toggle_module', { module: moduleId, enabled: enabled ? '1' : '0' } );
					setStatusDot( moduleId, enabled );
					toast( enabled ? i18n.enabled : i18n.disabled );
				} catch ( error ) {
					input.checked = ! enabled;
					toast( error.message, 'error' );
				} finally {
					input.disabled = false;
				}
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------- */

	initTabs();
	initModuleSwitches();

	if ( store ) {
		bindControls( app );
		initColorFields( app );
		initSaving();
	}

	window.STX = {
		data,
		i18n,
		store,
		clone,
		escapeHtml,
		evaluate,
		getPath,
		toast,
		confirm: confirmDialog,
		post,
	};
}() );
