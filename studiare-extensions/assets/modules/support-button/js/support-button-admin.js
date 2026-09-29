/**
 * Studiare Extensions — support button settings panel.
 *
 * Fields bind to the store through admin.js (`data-stx-bind`); this adds:
 *   - channel rows: summary chips, the resolved link, open/close, reorder
 *   - the live phone preview and the design cards, rendered with the real
 *     front-end stylesheet
 *
 * Contracts: renderWidget() mirrors views/button.php and channelUrl()
 * mirrors Channels::url(). Change them together.
 */
( function ( $ ) {
	'use strict';

	const STX = window.STX;
	const root = document.querySelector( '[data-stx-module="support_button"]' );
	if ( ! STX || ! STX.store || ! root ) {
		return;
	}

	const store = STX.store;
	const esc = STX.escapeHtml;
	const moduleData = STX.data.moduleData;
	const channels = moduleData.channels;
	const i18n = Object.assign( {}, STX.i18n, moduleData.i18n || {} );

	/* =====================================================================
	 * Channel links (mirror of Channels::url())
	 * =================================================================== */

	const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
	const ARABIC_DIGITS = '٠١٢٣٤٥٦٧٨٩';

	const latinDigits = ( value ) => value.replace( /[۰-۹٠-٩]/g, ( digit ) => String( Math.max( PERSIAN_DIGITS.indexOf( digit ), ARABIC_DIGITS.indexOf( digit ) ) ) );
	const isUrl = ( value ) => /^(https?:\/\/|[a-z0-9-]+(\.[a-z0-9-]+)+\/)/i.test( value );
	const withScheme = ( value ) => ( /^([a-z][a-z0-9+.-]*:|\/)/i.test( value ) ? value : 'https://' + value );
	const isPhone = ( value ) => /^\+?[\d\s()-]{8,}$/.test( value );

	function international( value ) {
		const digits = value.replace( /\D/g, '' );
		if ( value.charAt( 0 ) !== '+' && digits.indexOf( '00' ) === 0 ) {
			return digits.slice( 2 );
		}
		if ( value.charAt( 0 ) !== '+' && /^09\d{9}$/.test( digits ) ) {
			return '98' + digits.slice( 1 );
		}
		return digits;
	}

	function handleUrl( value, base ) {
		if ( isUrl( value ) ) {
			return withScheme( value );
		}
		const handle = value.replace( /^@+/, '' );
		return /^[A-Za-z0-9_.]{2,64}$/.test( handle ) ? base + handle : '';
	}

	function whatsappUrl( value, message ) {
		if ( isUrl( value ) ) {
			return withScheme( value );
		}
		const number = international( value );
		if ( number.length < 8 ) {
			return '';
		}
		return 'https://wa.me/' + number + ( message.trim() ? '?text=' + encodeURIComponent( message.trim() ) : '' );
	}

	function channelUrl( id, channel ) {
		const value = latinDigits( String( channel.value || '' ).trim() );
		if ( ! value ) {
			return '';
		}

		switch ( id ) {
			case 'telegram':
				return isPhone( value ) ? 'https://t.me/+' + international( value ) : handleUrl( value, 'https://t.me/' );
			case 'whatsapp':
				return whatsappUrl( value, String( channel.message || '' ) );
			case 'bale':
				return handleUrl( value, 'https://ble.ir/' );
			case 'eitaa':
				return handleUrl( value, 'https://eitaa.com/' );
			case 'instagram':
				return handleUrl( value, 'https://ig.me/m/' );
			case 'phone': {
				const number = ( value.charAt( 0 ) === '+' ? '+' : '' ) + value.replace( /\D/g, '' );
				return number.length >= 3 ? 'tel:' + number : '';
			}
			case 'email':
				return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value ) ? 'mailto:' + value : '';
			case 'link':
				return isUrl( value ) || value.charAt( 0 ) === '/' ? withScheme( value ) : '';
		}

		return '';
	}

	function channelGlyph( id, channel ) {
		return id === 'link' ? moduleData.icons[ channel.icon ] || moduleData.icons.chat : channels[ id ].glyph;
	}

	const channelLabel = ( id, channel ) => channel.label || channels[ id ].label;

	/** Switched-on channels that resolve to a link, in order (mirror of Frontend::channels()). */
	function readyChannels( state ) {
		return state.order.filter( ( id ) => channels[ id ] && state.channels[ id ] && state.channels[ id ].enabled ).map( ( id ) => {
			const channel = state.channels[ id ];
			return {
				id,
				label: channelLabel( id, channel ),
				note: channel.note || '',
				url: channelUrl( id, channel ),
				glyph: channelGlyph( id, channel ),
				brand: state.colors.brand ? channels[ id ].color : '',
			};
		} ).filter( ( channel ) => channel.url );
	}

	/* =====================================================================
	 * Widget markup (mirror of views/button.php)
	 * =================================================================== */

	const brandStyle = ( brand ) => ( brand ? ' style="--stx-sb-brand:' + esc( brand ) + '"' : '' );

	function styleVars( state ) {
		let css = '--stx-sb-size:' + state.button.size + 'px;--stx-sb-x:' + state.position.offset_x + 'px;--stx-sb-y:' + state.position.offset_y + 'px;--stx-sb-z:5;';
		if ( state.colors.button_bg ) {
			css += '--stx-sb-button:' + state.colors.button_bg + ';';
		}
		if ( state.colors.button_icon ) {
			css += '--stx-sb-button-icon:' + state.colors.button_icon + ';';
		}
		return css;
	}

	/**
	 * @param {Object}  state
	 * @param {Object}  options
	 * @param {string}  options.design Design to draw (defaults to the saved one).
	 * @param {boolean} options.open   Draw the menu open.
	 * @param {number}  options.limit  Maximum channels (small design cards).
	 * @return {string} HTML, or '' when no channel is ready.
	 */
	function renderWidget( state, options ) {
		const list = readyChannels( state ).slice( 0, options.limit || undefined );
		if ( ! list.length ) {
			return '';
		}

		const design = options.design || state.design;
		const single = list.length === 1;
		const button = state.button;
		// The preview is a phone, where "computers only" labels are hidden.
		const labelMode = ! button.label || button.label_mode === 'desktop' ? 'never' : button.label_mode;
		const classes = [ 'stx-sb', 'stx-sb--' + design, 'stx-sb--' + state.position.side, 'stx-sb--label-' + labelMode ];
		const label = single ? list[ 0 ].label : button.label || i18n.support;
		const icon = single ? list[ 0 ].glyph : moduleData.icons[ button.icon ];
		const showGreeting = state.greeting.enabled && state.greeting.text && ! options.open && ! options.limit;

		let html = '<div class="' + classes.join( ' ' ) + '" style="' + esc( styleVars( state ) ) + '">';

		if ( single ) {
			const brand = state.colors.button_bg ? '' : list[ 0 ].brand;
			html += '<a class="stx-sb__toggle" href="#"' + brandStyle( brand ) + '>' +
				'<span class="stx-sb__icons" aria-hidden="true"><span class="stx-sb__icon">' + icon + '</span></span>' +
				'<span class="stx-sb__label">' + esc( label ) + '</span></a>';
		} else {
			const header = design === 'card' ? state.header : { title: '', subtitle: '' };

			html += '<details class="stx-sb__menu"' + ( options.open ? ' open' : '' ) + '>' +
				'<summary class="stx-sb__toggle">' +
					'<span class="stx-sb__icons" aria-hidden="true">' +
						'<span class="stx-sb__icon">' + icon + '</span>' +
						'<span class="stx-sb__icon stx-sb__icon--close">' + moduleData.closeIcon + '</span>' +
					'</span>' +
					'<span class="stx-sb__label">' + esc( label ) + '</span>' +
				'</summary>' +
				'<div class="stx-sb__panel">';

			if ( header.title || header.subtitle ) {
				html += '<div class="stx-sb__head">' +
					( header.title ? '<p class="stx-sb__title">' + esc( header.title ) + '</p>' : '' ) +
					( header.subtitle ? '<p class="stx-sb__subtitle">' + esc( header.subtitle ) + '</p>' : '' ) +
					'</div>';
			}

			html += '<ul class="stx-sb__list" style="--stx-sb-count:' + list.length + '">' + list.map( ( channel, index ) => (
				'<li class="stx-sb__item" style="--stx-sb-i:' + index + '">' +
					'<a class="stx-sb__channel" href="#">' +
						'<span class="stx-sb__glyph" aria-hidden="true"' + brandStyle( channel.brand ) + '>' + channel.glyph + '</span>' +
						'<span class="stx-sb__text">' +
							'<span class="stx-sb__name">' + esc( channel.label ) + '</span>' +
							( channel.note ? '<span class="stx-sb__note">' + esc( channel.note ) + '</span>' : '' ) +
						'</span>' +
					'</a>' +
				'</li>'
			) ).join( '' ) + '</ul></div></details>';
		}

		if ( showGreeting ) {
			html += '<div class="stx-sb__greeting">' +
				'<button type="button" class="stx-sb__greeting-text">' + esc( state.greeting.text ) + '</button>' +
				'<button type="button" class="stx-sb__greeting-close" aria-label="' + esc( i18n.close ) + '">' + moduleData.closeIcon + '</button>' +
				'</div>';
		}

		return html + '</div>';
	}

	/* =====================================================================
	 * Preview
	 * =================================================================== */

	const preview = {
		screen: root.querySelector( '[data-stx-preview-screen]' ),
		host: root.querySelector( '[data-stx-preview-host]' ),
		empty: root.querySelector( '[data-stx-preview-empty]' ),
		hiddenNote: root.querySelector( '[data-stx-preview-hidden]' ),
		open: true,
	};
	const designStages = Array.from( root.querySelectorAll( '[data-stx-design-stage]' ) );

	/** Design cards draw a phone-width widget, scaled down to the card. */
	const STAGE_PHONE_WIDTH = 360;
	const STAGE_MAX_SCALE = 0.6;
	let renderQueued = false;

	function scaleStage( stage ) {
		stage.style.setProperty( '--stx-stage-scale', String( Math.min( STAGE_MAX_SCALE, stage.clientWidth / STAGE_PHONE_WIDTH ) ) );
	}

	function renderPreviews() {
		renderQueued = false;
		const state = store.get();
		const html = renderWidget( state, { open: preview.open } );
		const phoneHidden = state.display.devices === 'desktop';

		preview.host.innerHTML = phoneHidden ? '' : html;
		preview.empty.hidden = Boolean( html );
		preview.hiddenNote.hidden = ! ( html && phoneHidden );

		designStages.forEach( ( stage ) => {
			stage.innerHTML = '<span class="stx-stage__viewport">' + renderWidget( state, { design: stage.dataset.stxDesignStage, open: true, limit: 3 } ) + '</span>';
			scaleStage( stage );
		} );
	}

	function queueRender() {
		if ( ! renderQueued ) {
			renderQueued = true;
			window.requestAnimationFrame( renderPreviews );
		}
	}

	function setPreviewOpen( open ) {
		preview.open = open;
		root.querySelectorAll( '[data-stx-preview-state]' ).forEach( ( input ) => {
			input.checked = input.value === ( open ? 'open' : 'closed' );
		} );
		queueRender();
	}

	function initPreview() {
		// Links never navigate in the preview; the main button opens and closes it.
		preview.host.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( 'a, button' ) ) {
				event.preventDefault();
			}
			if ( event.target.closest( 'summary' ) ) {
				event.preventDefault();
				setPreviewOpen( ! preview.open );
			}
		} );

		// The design card's radio still receives the click.
		designStages.forEach( ( stage ) => stage.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( 'a, summary' ) ) {
				event.preventDefault();
				stage.closest( 'label' ).querySelector( 'input' ).click();
			}
		} ) );

		if ( window.ResizeObserver ) {
			const observer = new ResizeObserver( ( entries ) => entries.forEach( ( entry ) => scaleStage( entry.target ) ) );
			designStages.forEach( ( stage ) => observer.observe( stage ) );
		}

		root.querySelectorAll( '[data-stx-preview-scheme]' ).forEach( ( input ) => {
			input.addEventListener( 'change', () => preview.screen.classList.toggle( 'scdarkcolors', input.value === 'dark' ) );
		} );

		root.querySelectorAll( '[data-stx-preview-state]' ).forEach( ( input ) => {
			input.addEventListener( 'change', () => setPreviewOpen( input.value === 'open' ) );
		} );
	}

	/* =====================================================================
	 * Channel rows
	 * =================================================================== */

	const list = root.querySelector( '[data-stx-channels]' );
	const noneReady = root.querySelector( '[data-stx-none-ready]' );
	const rows = () => Array.from( list.querySelectorAll( '.stx-item[data-channel]' ) );

	/** Shortens long links for the chip; the full value stays in the field. */
	const shorten = ( text ) => ( text.length > 28 ? text.slice( 0, 27 ) + '…' : text );

	function refreshRow( row, state ) {
		const id = row.dataset.channel;
		const channel = state.channels[ id ];
		const url = channelUrl( id, channel );
		const value = String( channel.value || '' ).trim();

		row.classList.toggle( 'is-off', ! channel.enabled );
		row.querySelector( '[data-channel-icon]' ).innerHTML = channelGlyph( id, channel );
		row.querySelector( '[data-channel-label]' ).textContent = channelLabel( id, channel );

		const chip = row.querySelector( '[data-channel-value]' );
		chip.hidden = ! url;
		chip.textContent = shorten( value );

		let problem = '';
		if ( ! channel.enabled ) {
			problem = value ? '' : i18n.off;
		} else if ( ! value ) {
			problem = i18n.notSet;
		} else if ( ! url ) {
			problem = i18n.invalid;
		}
		const warning = row.querySelector( '[data-channel-warning]' );
		warning.hidden = ! problem;
		warning.textContent = problem;
		warning.classList.toggle( 'stx-chip--warn', Boolean( channel.enabled ) );

		const link = row.querySelector( '[data-channel-url]' );
		link.hidden = ! url;
		link.innerHTML = url ? esc( i18n.opens ) + ' <a href="' + esc( url ) + '" target="_blank" rel="noopener" dir="ltr">' + esc( url ) + '</a>' : '';
	}

	/** Puts the rows in the stored order (after reordering, reset or save). */
	function syncOrder( state ) {
		const current = rows().map( ( row ) => row.dataset.channel );
		if ( current.join() === state.order.join() ) {
			return;
		}
		const byId = {};
		rows().forEach( ( row ) => {
			byId[ row.dataset.channel ] = row;
		} );
		state.order.forEach( ( id ) => byId[ id ] && list.appendChild( byId[ id ] ) );
	}

	function refreshChannels() {
		const state = store.get();
		syncOrder( state );
		rows().forEach( ( row ) => refreshRow( row, state ) );
		noneReady.hidden = readyChannels( state ).length > 0;
	}

	function setOpen( row, open ) {
		row.classList.toggle( 'is-open', open );
		row.querySelector( '.stx-item__editor' ).hidden = ! open;
		row.querySelector( '.stx-item__summary' ).setAttribute( 'aria-expanded', String( open ) );
	}

	function move( id, step ) {
		const order = store.get( 'order' ).slice();
		const index = order.indexOf( id );
		const target = index + step;
		if ( index < 0 || target < 0 || target >= order.length ) {
			return;
		}
		order.splice( target, 0, order.splice( index, 1 )[ 0 ] );
		store.set( 'order', order );
	}

	function initChannels() {
		list.addEventListener( 'click', ( event ) => {
			const row = event.target.closest( '.stx-item' );
			if ( ! row ) {
				return;
			}

			if ( event.target.closest( '[data-item-toggle]' ) ) {
				setOpen( row, ! row.classList.contains( 'is-open' ) );
				return;
			}

			const mover = event.target.closest( '[data-channel-move]' );
			if ( mover ) {
				move( row.dataset.channel, mover.dataset.channelMove === 'up' ? -1 : 1 );
				mover.focus();
			}
		} );

		if ( $ && $.fn.sortable ) {
			$( list ).sortable( {
				handle: '.stx-item__handle',
				items: '> .stx-item',
				axis: 'y',
				tolerance: 'pointer',
				placeholder: 'stx-item-placeholder',
				update() {
					store.set( 'order', rows().map( ( row ) => row.dataset.channel ) );
				},
			} );
		}
	}

	/* =====================================================================
	 * Boot
	 * =================================================================== */

	initPreview();
	initChannels();

	store.subscribe( () => {
		refreshChannels();
		queueRender();
	} );
	refreshChannels();
	renderPreviews();
}( window.jQuery ) );
