/**
 * Studiare Extensions — page templates admin.
 *
 * Renders the template pickers (with live preview), the category rules
 * editor, the template library and the home pages tab (designs → new
 * pages) on top of the shared admin store (window.STX from admin.js).
 */
( function () {
	'use strict';

	const STX = window.STX;
	if ( ! STX || ! STX.store || STX.data.module !== 'builder' ) {
		return;
	}

	const { store, escapeHtml, toast, post } = STX;
	const data = STX.data.moduleData || {};
	const t = data.i18n || {};
	const app = document.getElementById( 'stx-admin' );
	let templates = data.templates || [];
	let pages = data.pages || [];

	const ICONS = {
		eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
		edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
		copy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
		restore: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>',
		rename: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>',
		trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>',
		up: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>',
		down: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>',
		check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
		close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
		external: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>',
		desktop: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
		tablet: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M11 18h2"/></svg>',
		mobile: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/></svg>',
		plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
		home: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>',
	};

	const call = ( payload ) => post( 'stx_builder_library', payload );

	/* ---------------------------------------------------------------------
	 * Template helpers
	 * ------------------------------------------------------------------- */

	const ofType = ( type ) => templates.filter( ( tpl ) => tpl.type === type );
	// Template kinds in the order the settings list them (Module::type_labels()).
	const kinds = Object.keys( data.typeLabels || {} );
	const findTemplate = ( id ) => templates.find( ( tpl ) => String( tpl.id ) === String( id ) );

	/** Every stored reference to a template, to show "In use" badges. */
	function usedIds() {
		const used = new Set();
		const add = ( value ) => used.add( String( value ) );

		[ 'course', 'product' ].forEach( ( type ) => {
			add( store.get( type + '.default' ) );
			( store.get( type + '.rules' ) || [] ).forEach( ( rule ) => add( rule.template ) );
		} );
		[ 'header', 'footer' ].forEach( ( area ) => {
			add( store.get( area + '.desktop' ) );
			add( store.get( area + '.mobile' ) );
		} );
		[ 'archive', 'post' ].forEach( ( kind ) => add( store.get( 'blog.' + kind ) ) );

		return used;
	}

	function keywordOption( keyword, type ) {
		const titles = {
			theme: type === 'header' ? t.themeHeader : ( type === 'footer' ? t.themeFooter : t.theme ),
			none: t.none,
			same: t.same,
		};
		const descs = { theme: t.themeDesc, none: t.noneDesc, same: t.sameDesc };

		return {
			value: keyword,
			title: titles[ keyword ],
			desc: descs[ keyword ],
			thumb: ( data.keywordThumbs || {} )[ keyword ] || '',
			previewUrl: keyword === 'theme' ? ( data.themePreview || {} )[ type ] : '',
			editUrl: '',
		};
	}

	/* ---------------------------------------------------------------------
	 * Preview dialog
	 * ------------------------------------------------------------------- */

	const DEVICES = { desktop: 1280, tablet: 820, mobile: 390 };

	function openPreview( url, title ) {
		if ( ! url ) {
			toast( t.noSample, 'error' );
			return;
		}

		const dialog = document.createElement( 'dialog' );
		dialog.className = 'stx-dialog stx-preview-dialog';
		dialog.innerHTML =
			'<div class="stx-preview-dialog__head">' +
				'<h2>' + escapeHtml( t.previewOf + ': ' + title ) + '</h2>' +
				'<div class="stx-segmented stx-segmented--sm" role="radiogroup" aria-label="' + escapeHtml( t.previewOf ) + '">' +
					Object.keys( DEVICES ).map( ( device, i ) =>
						'<label class="stx-segmented__option"><input type="radio" name="stx-preview-device" value="' + device + '"' + ( i === 0 ? ' checked' : '' ) + '><span>' + ICONS[ device ] + escapeHtml( t[ device ] ) + '</span></label>'
					).join( '' ) +
				'</div>' +
				'<a class="stx-icon-btn" href="' + escapeHtml( url ) + '" target="_blank" rel="noopener" title="' + escapeHtml( t.openTab ) + '" aria-label="' + escapeHtml( t.openTab ) + '">' + ICONS.external + '</a>' +
				'<button type="button" class="stx-icon-btn" data-close aria-label="' + escapeHtml( t.close ) + '">' + ICONS.close + '</button>' +
			'</div>' +
			'<p class="stx-preview-dialog__note">' + escapeHtml( t.unsavedPreview ) + '</p>' +
			'<div class="stx-preview-dialog__stage"><div class="stx-preview-dialog__frame"><iframe title="' + escapeHtml( title ) + '" src="' + escapeHtml( url ) + '"></iframe></div></div>';

		app.appendChild( dialog );

		const stage = dialog.querySelector( '.stx-preview-dialog__stage' );
		const frame = dialog.querySelector( '.stx-preview-dialog__frame' );
		let device = 'desktop';

		function fit() {
			const width = DEVICES[ device ];
			const scale = Math.min( 1, ( stage.clientWidth - 24 ) / width );
			frame.style.width = width + 'px';
			frame.style.height = ( ( stage.clientHeight - 24 ) / scale ) + 'px';
			frame.style.transform = 'scale(' + scale + ')';
			frame.classList.toggle( 'is-device', device !== 'desktop' );
		}

		dialog.addEventListener( 'change', ( event ) => {
			if ( event.target.name === 'stx-preview-device' ) {
				device = event.target.value;
				fit();
			}
		} );

		const close = () => {
			observer.disconnect();
			dialog.close();
			dialog.remove();
		};

		dialog.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( '[data-close]' ) || event.target === dialog ) {
				close();
			}
		} );
		dialog.addEventListener( 'cancel', ( event ) => {
			event.preventDefault();
			close();
		} );

		const observer = new ResizeObserver( fit );
		observer.observe( stage );
		dialog.showModal();
		fit();
	}

	/* ---------------------------------------------------------------------
	 * Pickers (default layout, header/footer per device)
	 * ------------------------------------------------------------------- */

	function cardHtml( option, name, used ) {
		const chips = [];
		if ( option.preset ) {
			chips.push( '<span class="stx-chip stx-chip--accent">' + escapeHtml( t.preset ) + '</span>' );
		}
		if ( option.modified ) {
			chips.push( '<span class="stx-chip">' + escapeHtml( t.modified ) + '</span>' );
		}

		const actions = [];
		if ( option.previewUrl ) {
			actions.push( '<button type="button" class="stx-icon-btn" data-preview="' + escapeHtml( option.previewUrl ) + '" data-title="' + escapeHtml( option.title ) + '" title="' + escapeHtml( t.preview ) + '" aria-label="' + escapeHtml( t.preview ) + '">' + ICONS.eye + '</button>' );
		}
		if ( option.editUrl ) {
			actions.push( '<a class="stx-icon-btn" href="' + escapeHtml( option.editUrl ) + '" target="_blank" rel="noopener" title="' + escapeHtml( t.edit ) + '" aria-label="' + escapeHtml( t.edit ) + '">' + ICONS.edit + '</a>' );
		}

		return '<label class="stx-tpl-card' + ( used ? ' is-used' : '' ) + '">' +
			'<input type="radio" name="' + escapeHtml( name ) + '" value="' + escapeHtml( option.value ) + '">' +
			'<span class="stx-tpl-card__thumb">' + option.thumb + '<i class="stx-tpl-card__check">' + ICONS.check + '</i></span>' +
			'<span class="stx-tpl-card__body">' +
				'<span class="stx-tpl-card__title">' + escapeHtml( option.title ) + '</span>' +
				( option.desc ? '<span class="stx-tpl-card__desc">' + escapeHtml( option.desc ) + '</span>' : '' ) +
				( chips.length ? '<span class="stx-tpl-card__chips">' + chips.join( '' ) + '</span>' : '' ) +
			'</span>' +
			( actions.length ? '<span class="stx-tpl-card__actions">' + actions.join( '' ) + '</span>' : '' ) +
		'</label>';
	}

	function renderPicker( host ) {
		const type = host.dataset.stxPicker;
		const path = host.dataset.path;
		const keywords = ( host.dataset.keywords || '' ).split( ',' ).filter( Boolean );
		const name = 'stx-pick-' + path.replace( /\W/g, '-' );
		const presets = {};
		( data.presets || [] ).forEach( ( preset ) => {
			presets[ preset.key ] = preset;
		} );

		const options = keywords.map( ( keyword ) => keywordOption( keyword, type ) ).concat(
			ofType( type ).map( ( tpl ) => ( {
				value: String( tpl.id ),
				title: tpl.title,
				desc: tpl.preset && presets[ tpl.preset ] ? presets[ tpl.preset ].description : t.custom,
				thumb: tpl.thumb,
				preset: Boolean( tpl.preset ),
				modified: tpl.modified,
				previewUrl: tpl.previewUrl,
				editUrl: tpl.editUrl,
			} ) )
		);

		host.innerHTML = options.map( ( option ) => cardHtml( option, name, false ) ).join( '' );
		syncPicker( host );
	}

	function syncPicker( host ) {
		const value = String( store.get( host.dataset.path ) );
		host.querySelectorAll( 'input[type="radio"]' ).forEach( ( input ) => {
			input.checked = input.value === value;
		} );
	}

	app.addEventListener( 'change', ( event ) => {
		const input = event.target;
		const picker = input.closest && input.closest( '[data-stx-picker]' );
		if ( picker && input.type === 'radio' && input.checked ) {
			store.set( picker.dataset.path, input.value );
		}
	} );

	app.addEventListener( 'click', ( event ) => {
		const preview = event.target.closest( '[data-preview]' );
		if ( preview ) {
			event.preventDefault();
			openPreview( preview.dataset.preview, preview.dataset.title || '' );
		}
	} );

	/* ---------------------------------------------------------------------
	 * Category rules
	 * ------------------------------------------------------------------- */

	const terms = data.terms || [];
	const termById = new Map( terms.map( ( term ) => [ String( term.id ), term ] ) );

	/** Terms in tree order with their depth, for the picker list. */
	function termTree() {
		const children = new Map();
		terms.forEach( ( term ) => {
			const key = String( term.parent );
			if ( ! children.has( key ) ) {
				children.set( key, [] );
			}
			children.get( key ).push( term );
		} );

		const out = [];
		const walk = ( parent, depth ) => {
			( children.get( String( parent ) ) || [] ).forEach( ( term ) => {
				out.push( { term, depth } );
				walk( term.id, depth + 1 );
			} );
		};
		walk( 0, 0 );

		// Orphans (parent not in the list) at the end.
		terms.forEach( ( term ) => {
			if ( ! out.some( ( row ) => row.term.id === term.id ) ) {
				out.push( { term, depth: 0 } );
			}
		} );

		return out;
	}

	const parseIds = ( value ) => String( value || '' ).split( ',' ).map( ( id ) => id.trim() ).filter( Boolean );

	function templateSelect( type, current ) {
		const labels = data.typeLabels || {};
		const option = ( value, label ) => '<option value="' + escapeHtml( value ) + '"' + ( String( current ) === String( value ) ? ' selected' : '' ) + '>' + escapeHtml( label ) + '</option>';
		const group = ( groupType ) => {
			const list = ofType( groupType );
			return list.length ? '<optgroup label="' + escapeHtml( labels[ groupType ] || groupType ) + '">' + list.map( ( tpl ) => option( tpl.id, tpl.title ) ).join( '' ) + '</optgroup>' : '';
		};
		const other = type === 'course' ? 'product' : 'course';

		return '<select class="stx-rule__template" aria-label="' + escapeHtml( t.useTemplate ) + '">' + option( 'theme', t.theme ) + group( type ) + group( other ) + '</select>';
	}

	function renderRules( host ) {
		const type = host.dataset.stxRules;
		const rules = store.get( type + '.rules' ) || [];

		if ( ! rules.length ) {
			host.innerHTML = '<p class="stx-rules__empty">' + escapeHtml( t.noRules ) + '</p>';
			return;
		}

		host.innerHTML = rules.map( ( rule, index ) => {
			const ids = parseIds( rule.terms );
			const chips = ids.map( ( id ) => termById.get( id ) ).filter( Boolean ).map( ( term ) => '<span class="stx-chip">' + escapeHtml( term.name ) + '</span>' ).join( '' );

			return '<div class="stx-rule" data-index="' + index + '">' +
				'<span class="stx-rule__num">' + ( index + 1 ) + '</span>' +
				'<div class="stx-rule__cats">' +
					'<button type="button" class="stx-rule__pick' + ( ids.length ? '' : ' is-empty' ) + '" data-pick-terms aria-haspopup="true">' +
						( chips || '<span>' + escapeHtml( t.pickCategories ) + '</span>' ) +
					'</button>' +
					( ids.length ? '' : '<span class="stx-rule__warn">' + escapeHtml( t.ruleIncomplete ) + '</span>' ) +
				'</div>' +
				'<label class="stx-rule__children"><span class="stx-switch stx-switch--sm"><input type="checkbox" data-children' + ( rule.children ? ' checked' : '' ) + '><span class="stx-switch__track" aria-hidden="true"></span></span><span>' + escapeHtml( t.includeChildren ) + '</span></label>' +
				'<div class="stx-select stx-rule__select">' + templateSelect( type, rule.template ) + '</div>' +
				'<div class="stx-rule__tools">' +
					'<button type="button" class="stx-icon-btn" data-move="-1" aria-label="' + escapeHtml( t.moveUp ) + '"' + ( index === 0 ? ' disabled' : '' ) + '>' + ICONS.up + '</button>' +
					'<button type="button" class="stx-icon-btn" data-move="1" aria-label="' + escapeHtml( t.moveDown ) + '"' + ( index === rules.length - 1 ? ' disabled' : '' ) + '>' + ICONS.down + '</button>' +
					'<button type="button" class="stx-icon-btn stx-icon-btn--danger" data-remove aria-label="' + escapeHtml( t.removeRule ) + '">' + ICONS.trash + '</button>' +
				'</div>' +
			'</div>';
		} ).join( '' );
	}

	function updateRules( type, mutate ) {
		const rules = STX.clone( store.get( type + '.rules' ) || [] );
		mutate( rules );
		store.set( type + '.rules', rules );
	}

	app.querySelectorAll( '[data-stx-add-rule]' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const type = button.dataset.stxAddRule;
			const rules = store.get( type + '.rules' ) || [];
			if ( rules.length >= ( data.maxRules || 30 ) ) {
				toast( t.maxRules, 'error' );
				return;
			}
			updateRules( type, ( list ) => list.push( { id: '', terms: '', children: true, template: 'theme' } ) );
			const host = app.querySelector( '[data-stx-rules="' + type + '"]' );
			const last = host.querySelector( '.stx-rule:last-child [data-pick-terms]' );
			if ( last ) {
				// After this click finishes, or the outside-click handler would close it at once.
				window.setTimeout( () => openTermPicker( last ), 0 );
			}
		} );
	} );

	app.addEventListener( 'change', ( event ) => {
		const row = event.target.closest( '.stx-rule' );
		if ( ! row ) {
			return;
		}
		const type = row.closest( '[data-stx-rules]' ).dataset.stxRules;
		const index = Number( row.dataset.index );

		if ( event.target.matches( '[data-children]' ) ) {
			updateRules( type, ( rules ) => {
				rules[ index ].children = event.target.checked;
			} );
		} else if ( event.target.matches( '.stx-rule__template' ) ) {
			updateRules( type, ( rules ) => {
				rules[ index ].template = event.target.value;
			} );
		}
	} );

	app.addEventListener( 'click', ( event ) => {
		const row = event.target.closest( '.stx-rule' );
		if ( ! row ) {
			return;
		}
		const type = row.closest( '[data-stx-rules]' ).dataset.stxRules;
		const index = Number( row.dataset.index );

		const move = event.target.closest( '[data-move]' );
		if ( move ) {
			updateRules( type, ( rules ) => {
				const to = index + Number( move.dataset.move );
				if ( to >= 0 && to < rules.length ) {
					rules.splice( to, 0, rules.splice( index, 1 )[ 0 ] );
				}
			} );
			return;
		}

		if ( event.target.closest( '[data-remove]' ) ) {
			updateRules( type, ( rules ) => rules.splice( index, 1 ) );
			return;
		}

		const pick = event.target.closest( '[data-pick-terms]' );
		if ( pick ) {
			openTermPicker( pick );
		}
	} );

	/* Category popover */

	let popover = null;

	function closeTermPicker() {
		if ( popover ) {
			popover.remove();
			popover = null;
		}
	}

	function openTermPicker( button ) {
		closeTermPicker();

		const row = button.closest( '.stx-rule' );
		const type = row.closest( '[data-stx-rules]' ).dataset.stxRules;
		const index = Number( row.dataset.index );
		const selected = new Set( parseIds( ( store.get( type + '.rules' ) || [] )[ index ].terms ) );

		popover = document.createElement( 'div' );
		popover.className = 'stx-popover stx-term-picker';
		popover.innerHTML =
			'<input type="search" class="stx-input" placeholder="' + escapeHtml( t.searchCats ) + '">' +
			'<div class="stx-term-picker__list">' +
				( terms.length ? termTree().map( ( { term, depth } ) =>
					'<label class="stx-check" style="--depth:' + depth + '" data-name="' + escapeHtml( term.name.toLowerCase() ) + '"><input type="checkbox" value="' + term.id + '"' + ( selected.has( String( term.id ) ) ? ' checked' : '' ) + '><span>' + escapeHtml( term.name ) + '</span><em>' + term.count + '</em></label>'
				).join( '' ) : '<p class="stx-term-picker__empty">' + escapeHtml( t.noCats ) + '</p>' ) +
			'</div>';

		button.parentElement.appendChild( popover );

		const search = popover.querySelector( 'input[type="search"]' );
		search.focus();
		search.addEventListener( 'input', () => {
			const query = search.value.trim().toLowerCase();
			popover.querySelectorAll( '.stx-check' ).forEach( ( label ) => {
				label.hidden = query !== '' && ! label.dataset.name.includes( query );
			} );
		} );

		popover.addEventListener( 'change', ( event ) => {
			if ( event.target.type !== 'checkbox' ) {
				return;
			}
			const ids = Array.from( popover.querySelectorAll( 'input[type="checkbox"]:checked' ) ).map( ( input ) => input.value );
			const keepOpen = popover;
			updateRules( type, ( rules ) => {
				rules[ index ].terms = ids.join( ',' );
			} );
			// The rules re-render; re-attach the open popover to the new row.
			const newButton = app.querySelector( '[data-stx-rules="' + type + '"] .stx-rule[data-index="' + index + '"] [data-pick-terms]' );
			if ( newButton ) {
				newButton.parentElement.appendChild( keepOpen );
				popover = keepOpen;
			}
		} );
	}

	document.addEventListener( 'click', ( event ) => {
		if ( popover && ! popover.contains( event.target ) && ! event.target.closest( '[data-pick-terms]' ) ) {
			closeTermPicker();
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && popover ) {
			closeTermPicker();
		}
	} );

	/* ---------------------------------------------------------------------
	 * Library
	 * ------------------------------------------------------------------- */

	function renderLibrary() {
		const host = app.querySelector( '[data-stx-library]' );
		if ( ! host ) {
			return;
		}

		if ( ! ( data.elementor || {} ).active ) {
			host.innerHTML = '<div class="stx-empty"><p>' + escapeHtml( t.needsElementor ) + '</p></div>';
			return;
		}

		const labels = data.typeLabels || {};
		const used = usedIds();

		host.innerHTML = kinds.map( ( type ) => {
			const list = ofType( type );
			const cards = list.length ? list.map( ( tpl ) =>
				'<article class="stx-lib-card" data-id="' + tpl.id + '">' +
					'<button type="button" class="stx-lib-card__thumb" data-preview="' + escapeHtml( tpl.previewUrl ) + '" data-title="' + escapeHtml( tpl.title ) + '" aria-label="' + escapeHtml( t.preview + ': ' + tpl.title ) + '">' + tpl.thumb + '</button>' +
					'<div class="stx-lib-card__body">' +
						'<h4>' + escapeHtml( tpl.title ) + '</h4>' +
						'<div class="stx-tpl-card__chips">' +
							( used.has( String( tpl.id ) ) ? '<span class="stx-chip stx-chip--accent">' + escapeHtml( t.inUse ) + '</span>' : '' ) +
							( tpl.preset ? '<span class="stx-chip">' + escapeHtml( t.preset ) + '</span>' : '' ) +
							( tpl.modified ? '<span class="stx-chip">' + escapeHtml( t.modified ) + '</span>' : '' ) +
						'</div>' +
					'</div>' +
					'<div class="stx-lib-card__actions">' +
						'<a class="stx-btn stx-btn--soft stx-btn--sm" href="' + escapeHtml( tpl.editUrl ) + '" target="_blank" rel="noopener">' + ICONS.edit + '<span>' + escapeHtml( t.edit ) + '</span></a>' +
						'<button type="button" class="stx-icon-btn" data-preview="' + escapeHtml( tpl.previewUrl ) + '" data-title="' + escapeHtml( tpl.title ) + '" title="' + escapeHtml( t.preview ) + '" aria-label="' + escapeHtml( t.preview ) + '">' + ICONS.eye + '</button>' +
						'<button type="button" class="stx-icon-btn" data-lib="duplicate" title="' + escapeHtml( t.duplicate ) + '" aria-label="' + escapeHtml( t.duplicate ) + '">' + ICONS.copy + '</button>' +
						'<button type="button" class="stx-icon-btn" data-lib="rename" title="' + escapeHtml( t.rename ) + '" aria-label="' + escapeHtml( t.rename ) + '">' + ICONS.rename + '</button>' +
						( tpl.preset ? '<button type="button" class="stx-icon-btn" data-lib="restore" title="' + escapeHtml( t.restore ) + '" aria-label="' + escapeHtml( t.restore ) + '">' + ICONS.restore + '</button>' : '' ) +
						'<button type="button" class="stx-icon-btn stx-icon-btn--danger" data-lib="delete" title="' + escapeHtml( t.delete ) + '" aria-label="' + escapeHtml( t.delete ) + '">' + ICONS.trash + '</button>' +
					'</div>' +
				'</article>'
			).join( '' ) : '<p class="stx-rules__empty">' + escapeHtml( t.empty ) + '</p>';

			return '<section class="stx-lib-group"><h3>' + escapeHtml( labels[ type ] || type ) + ' <span class="stx-counter">' + list.length + '</span></h3><div class="stx-lib-grid">' + cards + '</div></section>';
		} ).join( '' );
	}

	function applyResult( result, message ) {
		if ( result.templates ) {
			templates = result.templates;
		}
		if ( result.pages ) {
			pages = result.pages;
		}
		if ( result.elementor ) {
			data.elementor = result.elementor;
		}
		renderAll();
		if ( message ) {
			toast( message );
		}
	}

	/** Small dialog with one text field. Resolves to the value or null. */
	function promptDialog( title, label, value ) {
		return new Promise( ( resolve ) => {
			const dialog = document.createElement( 'dialog' );
			dialog.className = 'stx-dialog stx-dialog--confirm';
			dialog.innerHTML =
				'<form method="dialog">' +
					'<div class="stx-dialog__head"><h2>' + escapeHtml( title ) + '</h2></div>' +
					'<div class="stx-dialog__body"><label class="stx-field"><span class="stx-field__label">' + escapeHtml( label ) + '</span><input type="text" class="stx-input" value="' + escapeHtml( value ) + '" required></label></div>' +
					'<div class="stx-dialog__actions"><button type="button" class="stx-btn stx-btn--ghost" value="cancel">' + escapeHtml( t.cancel ) + '</button><button type="submit" class="stx-btn stx-btn--primary" value="ok">' + escapeHtml( t.save ) + '</button></div>' +
				'</form>';
			app.appendChild( dialog );

			const input = dialog.querySelector( 'input' );
			const finish = ( result ) => {
				dialog.close();
				dialog.remove();
				resolve( result );
			};

			dialog.querySelector( 'form' ).addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				finish( input.value.trim() || null );
			} );
			dialog.querySelector( 'button[value="cancel"]' ).addEventListener( 'click', () => finish( null ) );
			dialog.addEventListener( 'cancel', ( event ) => {
				event.preventDefault();
				finish( null );
			} );

			dialog.showModal();
			input.select();
		} );
	}

	app.addEventListener( 'click', async ( event ) => {
		const button = event.target.closest( '[data-lib]' );
		if ( ! button ) {
			return;
		}

		const card = button.closest( '[data-id]' );
		const tpl = findTemplate( card.dataset.id );
		const op = button.dataset.lib;
		if ( ! tpl ) {
			return;
		}

		try {
			if ( op === 'duplicate' ) {
				applyResult( await call( { op, id: tpl.id } ), t.duplicated );
			} else if ( op === 'rename' ) {
				const title = await promptDialog( t.rename, t.renamePrompt, tpl.title );
				if ( title ) {
					applyResult( await call( { op, id: tpl.id, title } ), t.renamed );
				}
			} else if ( op === 'restore' ) {
				const ok = await STX.confirm( { title: t.restoreTitle, message: t.restoreMessage, confirmLabel: t.restoreConfirm } );
				if ( ok ) {
					applyResult( await call( { op, id: tpl.id } ), t.restored );
				}
			} else if ( op === 'delete' ) {
				const ok = await STX.confirm( { title: t.deleteTitle, message: t.deleteMessage.replace( '%s', tpl.title ), confirmLabel: t.delete, danger: true } );
				if ( ok ) {
					applyResult( await call( { op, id: tpl.id } ), t.deleted );
				}
			}
		} catch ( error ) {
			toast( error.message, 'error' );
		}
	} );

	/* New template */

	function newTemplateDialog() {
		const labels = data.typeLabels || {};
		const dialog = document.createElement( 'dialog' );
		dialog.className = 'stx-dialog stx-dialog--confirm stx-new-dialog';

		const presetOptions = ( type ) => '<option value="">' + escapeHtml( t.blank ) + '</option>' +
			( data.presets || [] ).filter( ( preset ) => preset.type === type ).map( ( preset ) => '<option value="' + escapeHtml( preset.key ) + '">' + escapeHtml( preset.label ) + '</option>' ).join( '' );

		dialog.innerHTML =
			'<form method="dialog">' +
				'<div class="stx-dialog__head"><h2>' + escapeHtml( t.newTitle ) + '</h2></div>' +
				'<div class="stx-dialog__body stx-fields">' +
					'<label class="stx-field"><span class="stx-field__label">' + escapeHtml( t.newName ) + '</span><input type="text" class="stx-input" name="title" required></label>' +
					'<label class="stx-field"><span class="stx-field__label">' + escapeHtml( t.newType ) + '</span><span class="stx-select"><select name="type">' +
						kinds.map( ( type ) => '<option value="' + type + '">' + escapeHtml( labels[ type ] ) + '</option>' ).join( '' ) +
					'</select></span></label>' +
					'<label class="stx-field"><span class="stx-field__label">' + escapeHtml( t.newStart ) + '</span><span class="stx-select"><select name="from">' + presetOptions( 'course' ) + '</select></span></label>' +
				'</div>' +
				'<div class="stx-dialog__actions">' +
					'<button type="button" class="stx-btn stx-btn--ghost" value="cancel">' + escapeHtml( t.cancel ) + '</button>' +
					'<button type="submit" class="stx-btn stx-btn--soft" value="create">' + escapeHtml( t.createOnly ) + '</button>' +
					'<button type="submit" class="stx-btn stx-btn--primary" value="edit">' + escapeHtml( t.create ) + '</button>' +
				'</div>' +
			'</form>';
		app.appendChild( dialog );

		const form = dialog.querySelector( 'form' );
		const close = () => {
			dialog.close();
			dialog.remove();
		};

		form.elements.type.addEventListener( 'change', () => {
			form.elements.from.innerHTML = presetOptions( form.elements.type.value );
		} );
		dialog.querySelector( 'button[value="cancel"]' ).addEventListener( 'click', close );
		dialog.addEventListener( 'cancel', ( event ) => {
			event.preventDefault();
			close();
		} );

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			const open = event.submitter && event.submitter.value === 'edit';
			const buttons = form.querySelectorAll( 'button' );
			buttons.forEach( ( button ) => {
				button.disabled = true;
			} );

			try {
				const result = await call( {
					op: 'create',
					type: form.elements.type.value,
					from: form.elements.from.value,
					title: form.elements.title.value.trim(),
				} );
				close();
				applyResult( result, t.created );
				if ( open && result.editUrl ) {
					window.open( result.editUrl, '_blank', 'noopener' );
				}
			} catch ( error ) {
				toast( error.message, 'error' );
				buttons.forEach( ( button ) => {
					button.disabled = false;
				} );
			}
		} );

		dialog.showModal();
		form.elements.title.focus();
	}

	const newButton = app.querySelector( '[data-stx-new-template]' );
	if ( newButton ) {
		newButton.addEventListener( 'click', newTemplateDialog );
	}

	const installButton = app.querySelector( '[data-stx-install-presets]' );
	if ( installButton ) {
		installButton.addEventListener( 'click', async () => {
			installButton.disabled = true;
			try {
				applyResult( await call( { op: 'install' } ), t.installed );
			} catch ( error ) {
				toast( error.message, 'error' );
			} finally {
				installButton.disabled = false;
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Pages: create pages from the page designs (home, about us, contact us)
	 * ------------------------------------------------------------------- */

	const pageKinds = data.pageKinds || [];

	/** Page kind settings (group title, suggested page name) by kind. */
	function pageKind( kind ) {
		return pageKinds.find( ( item ) => item.kind === kind ) || { kind, title: '', name: '' };
	}

	function renderHomeDesigns() {
		const host = app.querySelector( '[data-stx-home-designs]' );
		if ( ! host ) {
			return;
		}

		if ( ! ( data.elementor || {} ).active ) {
			host.innerHTML = '<div class="stx-empty"><p>' + escapeHtml( t.needsElementor ) + '</p></div>';
			return;
		}

		const presets = {};
		( data.presets || [] ).forEach( ( preset ) => {
			presets[ preset.key ] = preset;
		} );

		if ( ! pageKinds.some( ( item ) => ofType( item.kind ).length ) ) {
			host.innerHTML = '<p class="stx-rules__empty">' + escapeHtml( t.noDesigns ) + '</p>';
			return;
		}

		host.innerHTML = pageKinds.map( ( item ) => {
			const list = ofType( item.kind );

			return list.length ? '<section class="stx-lib-group"><h3>' + escapeHtml( item.title ) + ' <span class="stx-counter">' + list.length + '</span></h3>' +
				'<div class="stx-home-designs">' + list.map( ( tpl ) => designCard( tpl, presets ) ).join( '' ) + '</div></section>' : '';
		} ).join( '' );
	}

	function designCard( tpl, presets ) {
		const desc = tpl.preset && presets[ tpl.preset ] ? presets[ tpl.preset ].description : t.custom;

		return '<article class="stx-home-card" data-id="' + tpl.id + '">' +
			'<button type="button" class="stx-home-card__thumb" data-preview="' + escapeHtml( tpl.previewUrl ) + '" data-title="' + escapeHtml( tpl.title ) + '" aria-label="' + escapeHtml( t.preview + ': ' + tpl.title ) + '">' + tpl.thumb + '</button>' +
			'<div class="stx-home-card__body">' +
				'<h3>' + escapeHtml( tpl.title ) + '</h3>' +
				'<p>' + escapeHtml( desc ) + '</p>' +
				( tpl.modified ? '<span class="stx-tpl-card__chips"><span class="stx-chip">' + escapeHtml( t.modified ) + '</span></span>' : '' ) +
			'</div>' +
			'<div class="stx-home-card__actions">' +
				'<button type="button" class="stx-btn stx-btn--primary stx-btn--sm" data-home-create>' + ICONS.plus + '<span>' + escapeHtml( t.createPage ) + '</span></button>' +
				'<button type="button" class="stx-icon-btn" data-preview="' + escapeHtml( tpl.previewUrl ) + '" data-title="' + escapeHtml( tpl.title ) + '" title="' + escapeHtml( t.preview ) + '" aria-label="' + escapeHtml( t.preview ) + '">' + ICONS.eye + '</button>' +
				'<a class="stx-icon-btn" href="' + escapeHtml( tpl.editUrl ) + '" target="_blank" rel="noopener" title="' + escapeHtml( t.editDesign ) + '" aria-label="' + escapeHtml( t.editDesign ) + '">' + ICONS.edit + '</a>' +
			'</div>' +
		'</article>';
	}

	function renderHomePages() {
		const host = app.querySelector( '[data-stx-home-pages]' );
		if ( ! host ) {
			return;
		}

		if ( ! pages.length ) {
			host.innerHTML = '<p class="stx-rules__empty">' + escapeHtml( t.noPages ) + '</p>';
			return;
		}

		host.innerHTML = '<ul class="stx-page-list">' + pages.map( ( page ) => {
			const live = page.status === 'publish';
			// A page whose design was deleted has no kind; it may still be the home page.
			const homeKind = page.kind === 'home' || page.kind === '';

			return '<li class="stx-page-row" data-page="' + page.id + '">' +
				'<div class="stx-page-row__main">' +
					'<a class="stx-page-row__title" href="' + escapeHtml( page.viewUrl ) + '" target="_blank" rel="noopener">' + escapeHtml( page.title ) + '</a>' +
					'<span class="stx-tpl-card__chips">' +
						( page.isFront ? '<span class="stx-chip stx-chip--accent">' + escapeHtml( t.frontPage ) + '</span>' : '' ) +
						( homeKind ? '' : '<span class="stx-chip">' + escapeHtml( ( data.typeLabels || {} )[ page.kind ] || '' ) + '</span>' ) +
						'<span class="stx-chip">' + escapeHtml( live ? t.published : t.draft ) + '</span>' +
					'</span>' +
					( page.design ? '<span class="stx-page-row__from">' + escapeHtml( t.madeFrom.replace( '%s', page.design ) ) + '</span>' : '' ) +
				'</div>' +
				'<div class="stx-page-row__actions">' +
					'<a class="stx-btn stx-btn--soft stx-btn--sm" href="' + escapeHtml( page.editUrl ) + '" target="_blank" rel="noopener">' + ICONS.edit + '<span>' + escapeHtml( t.edit ) + '</span></a>' +
					'<a class="stx-icon-btn" href="' + escapeHtml( page.viewUrl ) + '" target="_blank" rel="noopener" title="' + escapeHtml( t.view ) + '" aria-label="' + escapeHtml( t.view ) + '">' + ICONS.external + '</a>' +
					( live && homeKind && ! page.isFront ? '<button type="button" class="stx-icon-btn" data-home-front title="' + escapeHtml( t.setFront ) + '" aria-label="' + escapeHtml( t.setFront ) + '">' + ICONS.home + '</button>' : '' ) +
				'</div>' +
			'</li>';
		} ).join( '' ) + '</ul>';
	}

	/** Switch row for the create-page dialog (same markup as Admin\Fields::toggle). */
	function switchRow( name, label, help, checked ) {
		return '<div class="stx-field stx-field--toggle"><label class="stx-switch-row">' +
			'<span class="stx-switch-row__text"><span class="stx-field__label">' + escapeHtml( label ) + '</span><p class="stx-field__help">' + escapeHtml( help ) + '</p></span>' +
			'<span class="stx-switch"><input type="checkbox" name="' + name + '"' + ( checked ? ' checked' : '' ) + '><span class="stx-switch__track" aria-hidden="true"></span></span>' +
		'</label></div>';
	}

	function createPageDialog( tpl ) {
		// Only a home design can become the site's front page.
		const home = tpl.type === 'home';
		const dialog = document.createElement( 'dialog' );
		dialog.className = 'stx-dialog stx-dialog--confirm stx-new-dialog';
		dialog.innerHTML =
			'<form method="dialog">' +
				'<div class="stx-dialog__head"><h2>' + escapeHtml( t.createPageTitle.replace( '%s', tpl.title ) ) + '</h2></div>' +
				'<div class="stx-dialog__body stx-fields">' +
					'<label class="stx-field"><span class="stx-field__label">' + escapeHtml( t.pageName ) + '</span><input type="text" class="stx-input" name="title" required value="' + escapeHtml( pageKind( tpl.type ).name ) + '"></label>' +
					switchRow( 'publish', t.publishNow, t.publishHelp, true ) +
					( home ? switchRow( 'front', t.makeFront, t.makeFrontHelp, false ) : '' ) +
				'</div>' +
				'<div class="stx-dialog__actions">' +
					'<button type="button" class="stx-btn stx-btn--ghost" value="cancel">' + escapeHtml( t.cancel ) + '</button>' +
					'<button type="submit" class="stx-btn stx-btn--soft" value="create">' + escapeHtml( t.createOnly ) + '</button>' +
					'<button type="submit" class="stx-btn stx-btn--primary" value="edit">' + escapeHtml( t.create ) + '</button>' +
				'</div>' +
			'</form>';
		app.appendChild( dialog );

		const form = dialog.querySelector( 'form' );
		const close = () => {
			dialog.close();
			dialog.remove();
		};

		// The home page must be published, so "publish" follows the home page switch.
		if ( home ) {
			form.elements.front.addEventListener( 'change', () => {
				if ( form.elements.front.checked ) {
					form.elements.publish.checked = true;
				}
				form.elements.publish.disabled = form.elements.front.checked;
			} );
		}

		dialog.querySelector( 'button[value="cancel"]' ).addEventListener( 'click', close );
		dialog.addEventListener( 'cancel', ( event ) => {
			event.preventDefault();
			close();
		} );

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			const open = event.submitter && event.submitter.value === 'edit';
			const buttons = form.querySelectorAll( 'button' );
			buttons.forEach( ( button ) => {
				button.disabled = true;
			} );

			try {
				const result = await call( {
					op: 'create_page',
					id: tpl.id,
					title: form.elements.title.value.trim(),
					publish: form.elements.publish.checked ? '1' : '',
					front: home && form.elements.front.checked ? '1' : '',
				} );
				close();
				applyResult( result, t.pageCreated );
				if ( open && result.editUrl ) {
					window.open( result.editUrl, '_blank', 'noopener' );
				}
			} catch ( error ) {
				toast( error.message, 'error' );
				buttons.forEach( ( button ) => {
					button.disabled = false;
				} );
			}
		} );

		dialog.showModal();
		form.elements.title.select();
	}

	app.addEventListener( 'click', async ( event ) => {
		const create = event.target.closest( '[data-home-create]' );
		if ( create ) {
			const tpl = findTemplate( create.closest( '[data-id]' ).dataset.id );
			if ( tpl ) {
				createPageDialog( tpl );
			}
			return;
		}

		const front = event.target.closest( '[data-home-front]' );
		if ( front ) {
			front.disabled = true;
			try {
				applyResult( await call( { op: 'set_front', id: front.closest( '[data-page]' ).dataset.page } ), t.frontSet );
			} catch ( error ) {
				toast( error.message, 'error' );
				front.disabled = false;
			}
		}
	} );

	/* ---------------------------------------------------------------------
	 * Brand & Elementor housekeeping
	 * ------------------------------------------------------------------- */

	/** Mixes two #rrggbb colours (amount 0–1 of the second). */
	function mix( a, b, amount ) {
		const parse = ( hex ) => {
			const clean = hex.replace( '#', '' );
			const full = clean.length === 3 ? clean.split( '' ).map( ( c ) => c + c ).join( '' ) : clean.slice( 0, 6 );
			return [ 0, 2, 4 ].map( ( i ) => parseInt( full.slice( i, i + 2 ), 16 ) || 0 );
		};
		const [ ca, cb ] = [ parse( a ), parse( b ) ];
		return '#' + ca.map( ( value, i ) => Math.round( value + ( cb[ i ] - value ) * amount ).toString( 16 ).padStart( 2, '0' ) ).join( '' );
	}

	const themeColors = app.querySelector( '[data-stx-theme-colors]' );
	if ( themeColors ) {
		themeColors.addEventListener( 'click', () => {
			const primary = ( data.palette || {} ).primary || '#26a69a';
			store.set( 'brand.accent', primary );
			store.set( 'brand.accent_strong', mix( primary, '#000000', 0.18 ) );
			store.set( 'brand.accent_soft', mix( primary, '#ffffff', 0.88 ) );
			toast( t.colorsApplied );
		} );
	}

	const syncColors = app.querySelector( '[data-stx-sync-colors]' );
	if ( syncColors ) {
		syncColors.addEventListener( 'click', async () => {
			syncColors.disabled = true;
			try {
				await call( { op: 'sync_colors', colors: JSON.stringify( store.get( 'brand' ) ) } );
				toast( t.synced );
			} catch ( error ) {
				toast( error.message, 'error' );
			} finally {
				syncColors.disabled = false;
			}
		} );
	}

	const containerButton = app.querySelector( '[data-stx-activate-container]' );
	if ( containerButton ) {
		containerButton.addEventListener( 'click', async () => {
			containerButton.disabled = true;
			try {
				await call( { op: 'activate_container' } );
				toast( t.containerOn );
				const notice = app.querySelector( '[data-stx-container-notice]' );
				if ( notice ) {
					notice.remove();
				}
			} catch ( error ) {
				toast( error.message, 'error' );
				containerButton.disabled = false;
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------- */

	function renderAll() {
		app.querySelectorAll( '[data-stx-picker]' ).forEach( renderPicker );
		app.querySelectorAll( '[data-stx-rules]' ).forEach( renderRules );
		renderLibrary();
		renderHomeDesigns();
		renderHomePages();
	}

	store.subscribe( ( path ) => {
		if ( path === '*' ) {
			renderAll();
			return;
		}

		app.querySelectorAll( '[data-stx-picker]' ).forEach( ( host ) => {
			if ( host.dataset.path === path ) {
				syncPicker( host );
			}
		} );

		const rulesMatch = /^(course|product)\.rules$/.exec( path );
		if ( rulesMatch ) {
			renderRules( app.querySelector( '[data-stx-rules="' + rulesMatch[ 1 ] + '"]' ) );
		}

		if ( /\.(default|desktop|mobile|rules)$/.test( path ) ) {
			renderLibrary();
		}
	} );

	renderAll();

	// Deep link from Elementor's "exit" button: ?stx_tab=library.
	const wanted = new URLSearchParams( window.location.search ).get( 'stx_tab' );
	if ( wanted ) {
		const tab = app.querySelector( '[data-stx-tab="' + wanted + '"]' );
		if ( tab ) {
			tab.click();
		}
	}
}() );
