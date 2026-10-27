/**
 * Studiare Extensions — support button settings panel.
 *
 * Fields bind to the store through admin.js (`data-stx-bind`); this adds:
 *   - channel rows: summary chips, the resolved link, open/close, reorder
 *   - channels the admin makes (`custom`): rows cloned from the template,
 *     their fields (`data-custom-bind`), icon, colour, add and delete
 *   - the live phone preview and the design cards, rendered with the real
 *     front-end stylesheet
 *
 * Contracts: renderWidget() mirrors views/button.php, channelUrl() mirrors
 * Channels::url() and channelEntry() mirrors Channels::ready(). Change them
 * together.
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
	const customPrefix = moduleData.customPrefix;
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

	function linkUrl( value ) {
		if ( ! value ) {
			return '';
		}
		if ( /^(tel|mailto):\S+$/i.test( value ) ) {
			return value;
		}
		return isUrl( value ) || value.charAt( 0 ) === '/' ? withScheme( value ) : '';
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
			case 'rubika':
				return handleUrl( value, 'https://rubika.ir/' );
			case 'instagram':
				return handleUrl( value, 'https://ig.me/m/' );
			case 'phone': {
				const number = ( value.charAt( 0 ) === '+' ? '+' : '' ) + value.replace( /\D/g, '' );
				return number.length >= 3 ? 'tel:' + number : '';
			}
			case 'email':
				return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value ) ? 'mailto:' + value : '';
			case 'link':
				return linkUrl( value );
		}

		return '';
	}

	function channelGlyph( id, channel ) {
		return id === 'link' ? moduleData.icons[ channel.icon ] || moduleData.icons.chat : channels[ id ].glyph;
	}

	const decorate = ( svg ) => svg.replace( /^<svg\b/i, '<svg aria-hidden="true" focusable="false"' );

	/** Glyph of a custom channel (mirror of Channels::custom_glyph()). */
	function customGlyph( channel ) {
		if ( channel.icon_source === 'image' && channel.icon_image ) {
			return '<img src="' + esc( channel.icon_image ) + '" alt="">';
		}
		if ( channel.icon_source === 'svg' ) {
			const svg = STX.sanitizeSvg( channel.icon_svg );
			if ( svg ) {
				return decorate( svg );
			}
		}
		return moduleData.icons[ channel.icon ] || moduleData.icons.share;
	}

	const isCustom = ( id ) => id.indexOf( customPrefix ) === 0;
	const findCustom = ( state, id ) => ( state.custom || [] ).find( ( channel ) => channel.id === id );

	/**
	 * Saved settings, name, link, glyph and colour of any channel, or null
	 * when the id is unknown (mirror of Channels::built_in_entry() / custom_entry()).
	 */
	function channelEntry( state, id ) {
		if ( channels[ id ] ) {
			const channel = state.channels[ id ];
			return channel ? {
				channel,
				label: channel.label || channels[ id ].label,
				url: channelUrl( id, channel ),
				glyph: channelGlyph( id, channel ),
				color: channels[ id ].color,
			} : null;
		}

		const channel = findCustom( state, id );
		return channel ? {
			channel,
			label: String( channel.label || '' ).trim(),
			url: linkUrl( latinDigits( String( channel.value || '' ).trim() ) ),
			glyph: customGlyph( channel ),
			color: channel.color,
		} : null;
	}

	/** Switched-on channels that resolve to a link, in order (mirror of Channels::ready()). */
	function readyChannels( state ) {
		return state.order.map( ( id ) => ( { id, entry: channelEntry( state, id ) } ) )
			.filter( ( { entry } ) => entry && entry.channel.enabled && entry.url && entry.label )
			.map( ( { id, entry } ) => ( {
				id,
				label: entry.label,
				note: entry.channel.note || '',
				url: entry.url,
				glyph: entry.glyph,
				brand: state.colors.brand ? entry.color : '',
			} ) );
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
	const customTemplate = root.querySelector( '[data-stx-custom-template]' );
	const rows = () => Array.from( list.querySelectorAll( '.stx-item[data-channel]' ) );
	const primaryColor = getComputedStyle( root ).getPropertyValue( '--primary_color' ).trim();

	/** Colour fields of the custom rows, by channel id (they redraw on every change). */
	const colorRenders = new Map();

	/** Shortens long links for the chip; the full value stays in the field. */
	const shorten = ( text ) => ( text.length > 28 ? text.slice( 0, 27 ) + '…' : text );

	function refreshRow( row, state ) {
		const id = row.dataset.channel;
		const entry = channelEntry( state, id );
		if ( ! entry ) {
			return;
		}

		const channel = entry.channel;
		const value = String( channel.value || '' ).trim();

		row.classList.toggle( 'is-off', ! channel.enabled );
		row.querySelector( '[data-channel-icon]' ).innerHTML = entry.glyph;
		row.querySelector( '[data-channel-label]' ).textContent = entry.label || i18n.newChannel;

		const chip = row.querySelector( '[data-channel-value]' );
		chip.hidden = ! entry.url;
		chip.textContent = shorten( value );

		let problem = '';
		if ( ! channel.enabled ) {
			problem = value ? '' : i18n.off;
		} else if ( ! entry.label ) {
			problem = i18n.needsName;
		} else if ( ! value ) {
			problem = i18n.notSet;
		} else if ( ! entry.url ) {
			problem = i18n.invalid;
		}
		const warning = row.querySelector( '[data-channel-warning]' );
		warning.hidden = ! problem;
		warning.textContent = problem;
		warning.classList.toggle( 'stx-chip--warn', Boolean( channel.enabled ) );

		const link = row.querySelector( '[data-channel-url]' );
		link.hidden = ! entry.url;
		link.innerHTML = entry.url ? esc( i18n.opens ) + ' <a href="' + esc( entry.url ) + '" target="_blank" rel="noopener" dir="ltr">' + esc( entry.url ) + '</a>' : '';

		if ( isCustom( id ) ) {
			refreshCustomFields( row, channel );
		}
	}

	/** Writes a custom channel's values into its row (never into the field being typed in). */
	function refreshCustomFields( row, channel ) {
		row.querySelector( '[data-channel-icon]' ).style.setProperty( '--stx-sb-brand', channel.color || 'var(--primary_color)' );
		row.querySelector( '[data-custom-icon-preview]' ).innerHTML = moduleData.icons[ channel.icon ] || '';

		row.querySelectorAll( '[data-custom-bind]' ).forEach( ( control ) => {
			const value = channel[ control.dataset.customBind ];
			if ( control.type === 'checkbox' ) {
				control.checked = Boolean( value );
			} else if ( control.type === 'radio' ) {
				control.checked = control.value === value;
			} else if ( document.activeElement !== control ) {
				control.value = value == null ? '' : value;
			}
		} );

		row.querySelectorAll( '[data-custom-show-if]' ).forEach( ( node ) => {
			node.hidden = node.dataset.customShowIf !== channel.icon_source;
		} );

		colorRenders.get( row.dataset.channel )();
	}

	/** Adds rows for new custom channels and removes the rows of deleted ones. */
	function syncCustomRows( state ) {
		const ids = ( state.custom || [] ).map( ( channel ) => channel.id );

		rows().filter( ( row ) => isCustom( row.dataset.channel ) && ! ids.includes( row.dataset.channel ) ).forEach( ( row ) => {
			colorRenders.delete( row.dataset.channel );
			row.remove();
		} );

		ids.filter( ( id ) => ! colorRenders.has( id ) ).forEach( ( id ) => {
			const holder = document.createElement( 'template' );
			holder.innerHTML = customTemplate.innerHTML.replace( /__ID__/g, id );
			const row = holder.content.firstElementChild;

			colorRenders.set( id, STX.colorField( row.querySelector( '[data-custom-color]' ), {
				get: () => ( findCustom( store.get(), id ) || {} ).color,
				set: ( value ) => updateCustom( id, { color: value } ),
				fallback: primaryColor,
			} ) );
			list.appendChild( row );
		} );
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
		syncCustomRows( state );
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

	/* =====================================================================
	 * Custom channels
	 * =================================================================== */

	/**
	 * @param {string} id      Custom channel id.
	 * @param {Object} changes Fields to change.
	 */
	function updateCustom( id, changes ) {
		const custom = STX.clone( store.get( 'custom' ) || [] );
		const channel = custom.find( ( entry ) => entry.id === id );
		if ( channel ) {
			Object.assign( channel, changes );
			store.set( 'custom', custom );
		}
	}

	function addCustom() {
		const custom = STX.clone( store.get( 'custom' ) || [] );
		if ( custom.length >= moduleData.maxCustom ) {
			STX.toast( i18n.maxReached, 'error' );
			return;
		}

		const id = customPrefix + Math.random().toString( 36 ).slice( 2, 10 );
		custom.push( Object.assign( STX.clone( moduleData.customDefaults ), { id } ) );
		store.set( 'custom', custom );
		store.set( 'order', store.get( 'order' ).concat( [ id ] ) );

		const row = list.querySelector( '[data-channel="' + id + '"]' );
		setOpen( row, true );
		row.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		row.querySelector( '[data-custom-bind="label"]' ).focus( { preventScroll: true } );
	}

	async function deleteCustom( id ) {
		const channel = findCustom( store.get(), id );
		const confirmed = await STX.confirm( {
			title: i18n.deleteTitle,
			message: i18n.deleteMessage.replace( '%s', channel.label || i18n.newChannel ),
			confirmLabel: i18n.delete,
			danger: true,
		} );
		if ( ! confirmed ) {
			return;
		}

		store.set( 'custom', store.get( 'custom' ).filter( ( entry ) => entry.id !== id ) );
		store.set( 'order', store.get( 'order' ).filter( ( entry ) => entry !== id ) );
	}

	function customAction( action, id, button ) {
		switch ( action ) {
			case 'delete':
				deleteCustom( id );
				break;

			case 'pick-icon': {
				const channel = findCustom( store.get(), id );
				STX.iconPicker.open( {
					icons: moduleData.catalog,
					glyph: ( key ) => moduleData.icons[ key ] || '',
					selected: channel.icon_source === 'pack' ? channel.icon : '',
					trigger: button,
					onPick: ( key ) => updateCustom( id, { icon: key, icon_source: 'pack' } ),
				} );
				break;
			}

			case 'pick-image':
				STX.pickImage( ( url ) => updateCustom( id, { icon_image: url } ) );
				break;
		}
	}

	/** Field changes in custom rows (built-in rows bind through admin.js). */
	function onCustomInput( event ) {
		const control = event.target.closest( '[data-custom-bind]' );
		if ( ! control || ( control.type === 'radio' && ! control.checked ) ) {
			return;
		}
		updateCustom( control.closest( '.stx-item' ).dataset.channel, {
			[ control.dataset.customBind ]: control.type === 'checkbox' ? control.checked : control.value,
		} );
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
				return;
			}

			const action = event.target.closest( '[data-custom-action]' );
			if ( action ) {
				customAction( action.dataset.customAction, row.dataset.channel, action );
			}
		} );

		list.addEventListener( 'input', onCustomInput );
		list.addEventListener( 'change', onCustomInput );
		root.querySelector( '[data-stx-custom-add]' ).addEventListener( 'click', addCustom );

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
