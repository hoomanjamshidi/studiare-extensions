/**
 * Studiare Extensions — bottom navigation settings panel.
 *
 * Builds on the admin shell (window.STX) and adds:
 *   - a live phone preview and a mini preview on every style card
 *   - the sortable button list (rows cloned from #stx-item-template)
 *   - the icon picker dialog and media-library picker
 *
 * MARKUP CONTRACT: `renderNav()` mirrors views/nav.php and `resolveItems()`
 * mirrors Item_Resolver.php, so the preview matches the site pixel for pixel
 * (both use bottom-nav.css). Update them together.
 */
( function ( $ ) {
	'use strict';

	const STX = window.STX;
	const root = document.querySelector( '[data-stx-module="bottom_nav"]' );
	if ( ! STX || ! STX.store || ! root ) {
		return;
	}

	const store = STX.store;
	const esc = STX.escapeHtml;
	const moduleData = STX.data.moduleData;
	const i18n = Object.assign( {}, STX.i18n, moduleData.i18n || {} );
	const catalog = moduleData.catalog;
	const itemTypes = moduleData.itemTypes;

	/** Styles where the "featured" item becomes a raised centre button. */
	const hasFeaturedButton = ( style ) => Boolean( moduleData.styles[ style ] && moduleData.styles[ style ].featured );

	/* =====================================================================
	 * Icons
	 * =================================================================== */

	const FONT_AWESOME = 'fontawesome';
	const packs = { [ moduleData.iconPack.id ]: moduleData.iconPack.icons };
	const pendingPacks = {};

	/** Loads a pack and its fallback chain; resolves when all are cached. */
	function loadPack( id ) {
		if ( ! id || id === FONT_AWESOME ) {
			return Promise.resolve();
		}

		const fallback = ( catalog.packs[ id ] || {} ).fallback;
		const self = packs[ id ]
			? Promise.resolve()
			: ( pendingPacks[ id ] = pendingPacks[ id ] || fetch( moduleData.iconPackUrl + id + '.json' )
				.then( ( response ) => response.json() )
				.then( ( icons ) => {
					packs[ id ] = icons;
				} )
				.catch( () => {
					packs[ id ] = {};
				} ) );

		return Promise.all( [ self, fallback ? loadPack( fallback ) : Promise.resolve() ] );
	}

	function decorate( svg ) {
		return svg.replace( /^<svg\b/i, '<svg aria-hidden="true" focusable="false"' );
	}

	/** Mirrors Icon_Library::svg(): pack → fallback → tabler. */
	function packSvg( pack, key, active ) {
		const visited = {};
		let current = pack === FONT_AWESOME ? 'tabler' : pack;

		while ( current && ! visited[ current ] ) {
			visited[ current ] = true;
			const icon = ( packs[ current ] || {} )[ key ];
			if ( icon ) {
				return decorate( active && icon.active ? icon.active : icon.svg );
			}
			current = ( catalog.packs[ current ] || {} ).fallback || 'tabler';
		}

		return '';
	}

	function hasActiveVariant( pack, key ) {
		return pack === FONT_AWESOME || Boolean( ( packs[ pack ] || {} )[ key ] && packs[ pack ][ key ].active );
	}

	function faClass( key, weight ) {
		const icon = catalog.icons.find( ( entry ) => entry.key === key );
		if ( ! icon ) {
			return weight + ' fa-circle';
		}
		return icon.fa.indexOf( 'fab ' ) === 0 ? icon.fa : weight + ' ' + icon.fa;
	}

	const faSolid = ( classes ) => classes.replace( /\bfa[lrd]\b/, 'fas' );
	const faIcon = ( classes ) => '<i class="' + esc( classes ) + '" aria-hidden="true"></i>';

	const SVG_TAGS = [ 'svg', 'g', 'defs', 'title', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'clippath', 'lineargradient', 'radialgradient', 'stop' ];

	/**
	 * Client-side counterpart of Icon_Library::sanitize_svg() so unsaved custom
	 * SVG is safe to preview. The server sanitizes again on save.
	 */
	function sanitizeSvg( markup ) {
		const doc = new DOMParser().parseFromString( markup || '', 'image/svg+xml' );
		const svg = doc.documentElement;

		if ( ! svg || svg.nodeName.toLowerCase() !== 'svg' || doc.querySelector( 'parsererror' ) ) {
			return '';
		}

		svg.querySelectorAll( '*' ).forEach( ( node ) => {
			if ( ! SVG_TAGS.includes( node.nodeName.toLowerCase() ) ) {
				node.remove();
			}
		} );

		[ svg, ...svg.querySelectorAll( '*' ) ].forEach( ( node ) => {
			Array.from( node.attributes ).forEach( ( attribute ) => {
				const name = attribute.name.toLowerCase();
				if ( name.indexOf( 'on' ) === 0 || name === 'href' || name === 'xlink:href' || /javascript:/i.test( attribute.value ) ) {
					node.removeAttribute( attribute.name );
				}
			} );
		} );

		return new XMLSerializer().serializeToString( svg );
	}

	/**
	 * Icon markup for an item (mirrors Item_Resolver::icon_html()).
	 * Returns '' for the active variant when there is none.
	 */
	function itemIcon( item, active, state ) {
		const pack = state.icon_pack;
		const wantsVariant = active && state.active_filled;

		switch ( item.icon_source ) {
			case 'image':
				if ( ! active && item.icon_image ) {
					return '<img class="stx-bn__img" src="' + esc( item.icon_image ) + '" alt="">';
				}
				break;

			case 'svg': {
				const svg = active ? '' : sanitizeSvg( item.icon_svg );
				if ( svg ) {
					return decorate( svg );
				}
				break;
			}

			case 'fontawesome':
				if ( item.icon_fa ) {
					if ( active ) {
						return wantsVariant ? faIcon( faSolid( item.icon_fa ) ) : '';
					}
					return faIcon( item.icon_fa );
				}
				break;
		}

		if ( active && ! wantsVariant ) {
			return '';
		}

		if ( pack === FONT_AWESOME ) {
			const classes = faClass( item.icon, state.fa_weight );
			return faIcon( active ? faSolid( classes ) : classes );
		}

		if ( active && ! hasActiveVariant( pack, item.icon ) ) {
			return '';
		}

		return packSvg( pack, item.icon, active );
	}

	/* =====================================================================
	 * Items: validation and resolution (mirrors Item_Resolver.php)
	 * =================================================================== */

	/** Why an item would be hidden on the site, or '' when it works. */
	function itemProblem( item ) {
		const type = itemTypes[ item.type ];

		if ( ! type || ! type.available ) {
			return i18n.needsWoo;
		}

		switch ( item.type ) {
			case 'link':
				return item.url ? '' : i18n.needsUrl;
			case 'menu':
				return item.menu_source === 'wp_menu' && ! item.menu_id ? i18n.needsMenu : '';
			case 'content':
				return ( item.content_source === 'elementor' ? item.content_id : String( item.content_html ).trim() ) ? '' : i18n.needsContent;
			case 'dark_mode':
				return moduleData.theme.darkMode ? '' : i18n.needsDarkMode;
			case 'selector':
				return item.selector || item.url ? '' : i18n.needsSelector;
		}

		return '';
	}

	function isVisibleTo( item, viewer ) {
		return item.visibility === 'all'
			|| ( item.visibility === 'guests' && viewer === 'guest' )
			|| ( item.visibility === 'members' && viewer === 'member' );
	}

	function itemLabel( item, viewer ) {
		if ( item.type === 'account' && viewer === 'guest' ) {
			return item.guest_label || i18n.login;
		}

		return item.label || ( itemTypes[ item.type ] || {} ).default_label || '';
	}

	/**
	 * Items as the site would render them for a visitor, as view models.
	 *
	 * @param {Object} state  Settings.
	 * @param {string} viewer `member` or `guest`.
	 */
	function resolveItems( state, viewer ) {
		const models = state.items
			.filter( ( item ) => item.enabled && isVisibleTo( item, viewer ) && ! itemProblem( item ) )
			.map( ( item ) => {
				let icon = itemIcon( item, false, state );
				let iconActive = itemIcon( item, true, state );

				if ( item.type === 'account' && item.show_avatar && viewer === 'member' ) {
					icon = '<img class="stx-bn__avatar" src="' + esc( moduleData.avatarUrl || '' ) + '" alt="">';
					iconActive = '';
				}

				let iconAlt = '';
				if ( item.type === 'dark_mode' && item.icon_source === 'pack' ) {
					iconAlt = itemIcon( Object.assign( {}, item, { icon: item.icon === 'moon' ? 'sun' : 'moon' } ), false, state );
				}

				let badge = item.badge ? '<span class="stx-bn__badge stx-bn__badge--text">' + esc( item.badge ) + '</span>' : '';
				if ( item.type === 'cart' ) {
					badge = '<span class="stx-bn__badge stx-bn-cart-count" data-count="2">2</span>' + badge;
				}

				return { item, label: itemLabel( item, viewer ), icon, iconActive, iconAlt, badge, featured: false };
			} );

		if ( models.length && hasFeaturedButton( state.style ) ) {
			const flagged = models.findIndex( ( model ) => model.item.featured );
			models[ flagged >= 0 ? flagged : Math.floor( models.length / 2 ) ].featured = true;
		}

		return models;
	}

	/* =====================================================================
	 * Rendering (mirrors Renderer.php + views/nav.php + Style_Vars.php)
	 * =================================================================== */

	function styleVars( state ) {
		const vars = {
			'--stx-bn-h': state.layout.height + 'px',
			'--stx-bn-icon': state.layout.icon_size + 'px',
			'--stx-bn-radius': state.layout.radius + 'px',
			'--stx-bn-offset': state.layout.offset + 'px',
			'--stx-bn-max-w': state.layout.max_width + 'px',
			'--stx-bn-stroke': String( state.icon_stroke ),
			'--stx-bn-font-size': state.typography.font_size + 'px',
			'--stx-bn-font-weight': String( state.typography.font_weight ),
		};

		// In the admin, theme fonts are not loaded; the panel font stands in for them.
		if ( state.typography.font_source === 'custom' && state.typography.font_family ) {
			vars[ '--stx-bn-font' ] = state.typography.font_family + ', Vazirmatn, Tahoma, sans-serif';
		}

		moduleData.colorSlots.forEach( ( slot ) => {
			if ( state.colors[ slot ] ) {
				vars[ '--stx-bn-l-' + slot ] = state.colors[ slot ];
			}
			if ( state.dark.mode === 'auto' && state.dark.colors[ slot ] ) {
				vars[ '--stx-bn-d-' + slot ] = state.dark.colors[ slot ];
			}
		} );

		return Object.keys( vars ).map( ( name ) => name + ':' + vars[ name ] ).join( ';' );
	}

	function navClasses( state, style, activeIndex ) {
		const labelModes = Boolean( moduleData.styles[ style ] && moduleData.styles[ style ].label_modes );
		const classes = [
			'stx-bn',
			'stx-bn--style-' + style,
			'stx-bn--labels-' + ( labelModes ? state.label_mode : 'auto' ),
			'stx-bn--motion-' + state.layout.motion,
			'stx-bn--shadow-' + state.layout.shadow,
		];

		if ( activeIndex >= 0 ) {
			classes.push( 'has-active' );
		}
		if ( state.layout.glass ) {
			classes.push( 'stx-bn--glass' );
		}
		if ( state.dark.mode === 'off' ) {
			classes.push( 'stx-no-dark' );
		}

		return classes.join( ' ' );
	}

	/**
	 * @param {Object} state
	 * @param {Object} options { style, activeIndex, viewer }
	 * @return {string} Navigation markup.
	 */
	function renderNav( state, options ) {
		const style = options.style || state.style;
		const models = resolveItems( Object.assign( {}, state, { style } ), options.viewer || 'member' );
		const activeIndex = models.length ? Math.min( options.activeIndex, models.length - 1 ) : -1;
		const featuredIndex = models.findIndex( ( model ) => model.featured );

		const itemsHtml = models.map( ( model, index ) => {
			const classes = [ 'stx-bn__item', 'stx-bn__item--' + model.item.type ];
			if ( index === activeIndex ) {
				classes.push( 'is-active' );
			}
			if ( model.featured ) {
				classes.push( 'is-featured' );
			}
			if ( model.iconActive ) {
				classes.push( 'has-active-icon' );
			}

			return '<li class="' + classes.join( ' ' ) + '" style="--stx-bn-i:' + index + '">' +
				'<a class="stx-bn__link" href="#" data-preview-index="' + index + '" data-preview-type="' + esc( model.item.type ) + '">' +
					'<span class="stx-bn__icon">' +
						'<span class="stx-bn__glyph stx-bn__glyph--base">' + model.icon + '</span>' +
						( model.iconActive ? '<span class="stx-bn__glyph stx-bn__glyph--active">' + model.iconActive + '</span>' : '' ) +
						( model.iconAlt ? '<span class="stx-bn__glyph stx-bn__glyph--alt">' + model.iconAlt + '</span>' : '' ) +
						model.badge +
					'</span>' +
					'<span class="stx-bn__label">' + esc( model.label ) + '</span>' +
				'</a>' +
			'</li>';
		} ).join( '' );

		const layoutVars = '--stx-bn-count:' + models.length + ';--stx-bn-active:' + activeIndex + ';--stx-bn-featured:' + featuredIndex + ';';

		return '<nav class="' + navClasses( state, style, activeIndex ) + '" style="' + esc( layoutVars + styleVars( state ) ) + '" data-count="' + models.length + '">' +
			'<div class="stx-bn__bar" aria-hidden="true"><span class="stx-bn__indicator"></span></div>' +
			'<ul class="stx-bn__list">' + itemsHtml + '</ul>' +
		'</nav>';
	}

	/* =====================================================================
	 * Previews
	 * =================================================================== */

	const preview = {
		screen: root.querySelector( '[data-stx-preview-screen]' ),
		host: root.querySelector( '[data-stx-preview-nav]' ),
		active: 0,
		scheme: 'light',
		viewer: 'member',
	};

	const styleStages = Array.from( root.querySelectorAll( '[data-stx-style-stage]' ) );

	/** Style cards render a real phone-width bar, then scale it down to the card. */
	const STAGE_PHONE_WIDTH = 360;
	let renderQueued = false;

	function scaleStage( stage ) {
		stage.style.setProperty( '--stx-stage-scale', String( Math.min( 1, stage.clientWidth / STAGE_PHONE_WIDTH ) ) );
	}

	function renderPreviews() {
		renderQueued = false;
		const state = store.get();

		preview.screen.classList.toggle( 'scdarkcolors', preview.scheme === 'dark' );
		preview.host.innerHTML = renderNav( state, { activeIndex: preview.active, viewer: preview.viewer } );

		styleStages.forEach( ( stage ) => {
			stage.innerHTML = '<span class="stx-stage__viewport">' + renderNav( state, { style: stage.dataset.stxStyleStage, activeIndex: 0, viewer: 'member' } ) + '</span>';
			scaleStage( stage );
		} );
	}

	function queueRender() {
		if ( ! renderQueued ) {
			renderQueued = true;
			window.requestAnimationFrame( renderPreviews );
		}
	}

	/** Tapping preview buttons moves the active state, like on the site. */
	function onPreviewClick( event ) {
		const link = event.target.closest( '.stx-bn__link' );
		if ( ! link ) {
			return;
		}
		event.preventDefault();

		const nav = link.closest( '.stx-bn' );
		const item = link.closest( '.stx-bn__item' );
		const type = link.dataset.previewType;

		if ( type === 'dark_mode' ) {
			setScheme( preview.scheme === 'dark' ? 'light' : 'dark' );
			return;
		}

		if ( type === 'cart' ) {
			item.classList.remove( 'is-bumped' );
			void item.offsetWidth;
			item.classList.add( 'is-bumped' );
		}

		const index = Number( link.dataset.previewIndex );
		nav.querySelectorAll( '.stx-bn__item' ).forEach( ( node, i ) => node.classList.toggle( 'is-active', i === index ) );
		nav.style.setProperty( '--stx-bn-active', String( index ) );
		nav.classList.add( 'has-active' );

		if ( nav.parentElement === preview.host ) {
			preview.active = index;
		}
	}

	function setScheme( scheme ) {
		preview.scheme = scheme;
		root.querySelectorAll( '[data-stx-preview-scheme]' ).forEach( ( input ) => {
			input.checked = input.value === scheme;
		} );
		queueRender();
	}

	function initPreview() {
		preview.host.addEventListener( 'click', onPreviewClick );

		if ( window.ResizeObserver ) {
			const observer = new ResizeObserver( ( entries ) => entries.forEach( ( entry ) => scaleStage( entry.target ) ) );
			styleStages.forEach( ( stage ) => observer.observe( stage ) );
		}

		styleStages.forEach( ( stage ) => stage.addEventListener( 'click', ( event ) => {
			// Let the card's radio receive the click, but animate the mini bar too.
			if ( event.target.closest( '.stx-bn__link' ) ) {
				onPreviewClick( event );
				stage.closest( 'label' ).querySelector( 'input' ).click();
			}
		} ) );

		root.querySelectorAll( '[data-stx-preview-scheme]' ).forEach( ( input ) => {
			input.addEventListener( 'change', () => setScheme( input.value ) );
		} );

		root.querySelectorAll( '[data-stx-preview-user]' ).forEach( ( input ) => {
			input.addEventListener( 'change', () => {
				preview.viewer = input.value;
				queueRender();
			} );
		} );
	}

	/* =====================================================================
	 * Button list
	 * =================================================================== */

	const list = root.querySelector( '[data-stx-items]' );
	const template = document.getElementById( 'stx-item-template' );
	const counter = root.querySelector( '[data-stx-item-count]' );
	const emptyState = root.querySelector( '[data-stx-items-empty]' );
	const manyNote = root.querySelector( '[data-stx-items-many]' );
	const addToggle = root.querySelector( '[data-stx-add-toggle]' );
	const addMenu = document.getElementById( 'stx-add-menu' );

	const openItems = new Set();
	let editingField = false; // True while a row edits the store; skips full list rebuilds.

	const getItems = () => store.get( 'items' ) || [];
	const findIndex = ( id ) => getItems().findIndex( ( item ) => item.id === id );

	function newId() {
		return 'i' + Math.random().toString( 36 ).slice( 2, 10 );
	}

	function makeItem( type ) {
		const definition = itemTypes[ type ];
		return Object.assign( STX.clone( moduleData.itemDefaults ), {
			id: newId(),
			type,
			label: definition.default_label,
			icon: definition.icon,
		} );
	}

	/** Writes the item list back to the store. */
	function commitItems( items, options = {} ) {
		editingField = Boolean( options.inPlace );
		store.set( 'items', items );
		editingField = false;
	}

	function updateItem( id, field, value ) {
		const items = STX.clone( getItems() );
		const item = items[ findIndex( id ) ];
		if ( ! item ) {
			return;
		}

		// Switching type: swap label/icon only if they were still the old type's defaults.
		if ( field === 'type' && itemTypes[ value ] ) {
			const previous = itemTypes[ item.type ] || {};
			if ( ! item.label || item.label === previous.default_label ) {
				item.label = itemTypes[ value ].default_label;
			}
			if ( item.icon_source === 'pack' && item.icon === previous.icon ) {
				item.icon = itemTypes[ value ].icon;
			}
		}

		// Only one item can be featured.
		if ( field === 'featured' && value ) {
			items.forEach( ( other ) => {
				other.featured = false;
			} );
		}

		item[ field ] = value;

		const needsRebuild = field === 'type' || field === 'featured';
		commitItems( items, { inPlace: ! needsRebuild } );
	}

	function readItemControl( control ) {
		if ( control.type === 'checkbox' ) {
			return control.checked;
		}
		if ( control.dataset.stxType === 'number' ) {
			return parseInt( control.value, 10 ) || 0;
		}
		return control.value;
	}

	function fillOptions( select ) {
		const source = moduleData[ select.dataset.stxOptions ];
		let options = [];

		if ( Array.isArray( source ) ) {
			options = [ [ '0', i18n.noneOption ] ].concat( source.map( ( entry ) => [ String( entry.id ), entry.name ] ) );
		} else if ( source ) {
			options = Object.keys( source ).map( ( key ) => [ key, source[ key ] ] );
		}

		select.innerHTML = options.map( ( [ value, label ] ) => '<option value="' + esc( value ) + '">' + esc( label ) + '</option>' ).join( '' );
	}

	/** Checkbox chips for a list setting (e.g. post types), from a `{ value: label }` source. */
	function fillChecks( group ) {
		const source = moduleData[ group.dataset.stxOptions ] || {};

		group.innerHTML = Object.keys( source ).map( ( value ) => '<label class="stx-check"><input type="checkbox" value="' + esc( value ) + '"><span>' + esc( source[ value ] ) + '</span></label>' ).join( '' );
	}

	/** Updates everything in a row that depends on the item, without rebuilding it. */
	function refreshRow( row, item ) {
		const state = store.get();
		const type = itemTypes[ item.type ] || {};
		const problem = itemProblem( item );

		row.classList.toggle( 'is-off', ! item.enabled );
		row.querySelector( '[data-item-icon]' ).innerHTML = itemIcon( item, false, state ) || packSvg( 'tabler', type.icon || 'home', false );
		row.querySelector( '[data-item-label]' ).textContent = itemLabel( item, 'member' );
		row.querySelector( '[data-item-type]' ).textContent = type.label || item.type;
		row.querySelector( '[data-item-type-help]' ).textContent = type.description || '';

		const featuredChip = row.querySelector( '[data-item-featured]' );
		featuredChip.hidden = ! ( item.featured && hasFeaturedButton( state.style ) );

		const audience = row.querySelector( '[data-item-audience]' );
		audience.hidden = item.visibility === 'all';
		audience.textContent = item.visibility === 'guests' ? i18n.guestsOnly : i18n.membersOnly;

		const warning = row.querySelector( '[data-item-warning]' );
		warning.hidden = ! problem;
		warning.textContent = problem;
		warning.title = problem ? i18n.hiddenOnSite : '';

		const iconPreview = row.querySelector( '[data-item-icon-preview]' );
		iconPreview.innerHTML = state.icon_pack === FONT_AWESOME ? faIcon( faClass( item.icon, state.fa_weight ) ) : packSvg( state.icon_pack, item.icon, false );

		const sheetTitle = row.querySelector( '[data-item-placeholder="label"]' );
		sheetTitle.placeholder = itemLabel( item, 'member' );

		row.querySelectorAll( '[data-item-show-if]' ).forEach( ( node ) => {
			node.hidden = ! STX.evaluate( node.dataset.itemShowIf, ( key ) => item[ key ] );
		} );

		row.querySelectorAll( '[data-item-bind-list]' ).forEach( ( group ) => {
			const values = item[ group.dataset.itemBindList ] || [];
			group.querySelectorAll( 'input' ).forEach( ( input ) => {
				input.checked = values.includes( input.value );
			} );
		} );

		row.querySelectorAll( '[data-item-bind]' ).forEach( ( control ) => {
			const value = item[ control.dataset.itemBind ];
			if ( control.type === 'checkbox' ) {
				control.checked = Boolean( value );
			} else if ( control.type === 'radio' ) {
				control.checked = String( value ) === control.value;
			} else if ( document.activeElement !== control ) {
				control.value = value == null ? '' : value;
			}
		} );
	}

	function setOpen( row, open ) {
		const id = row.dataset.id;
		row.classList.toggle( 'is-open', open );
		row.querySelector( '.stx-item__editor' ).hidden = ! open;
		row.querySelector( '.stx-item__summary' ).setAttribute( 'aria-expanded', String( open ) );

		if ( open ) {
			openItems.add( id );
		} else {
			openItems.delete( id );
		}
	}

	function buildRow( item ) {
		const row = template.content.firstElementChild.cloneNode( true );
		row.dataset.id = item.id;

		row.querySelectorAll( 'select[data-stx-options]' ).forEach( fillOptions );

		row.querySelectorAll( '[data-item-bind-list]' ).forEach( ( group ) => {
			fillChecks( group );
			group.addEventListener( 'change', () => {
				const values = Array.from( group.querySelectorAll( 'input:checked' ), ( input ) => input.value );
				updateItem( row.dataset.id, group.dataset.itemBindList, values );
			} );
		} );

		row.querySelectorAll( '[data-item-bind]' ).forEach( ( control ) => {
			const field = control.dataset.itemBind;

			// Radio groups need a unique name per row.
			if ( control.type === 'radio' ) {
				control.name = 'stx-' + item.id + '-' + field;
			}

			const isChoice = control.type === 'checkbox' || control.type === 'radio' || control.tagName === 'SELECT';
			control.addEventListener( isChoice ? 'change' : 'input', () => {
				if ( control.type === 'radio' && ! control.checked ) {
					return;
				}
				updateItem( row.dataset.id, field, readItemControl( control ) );
			} );
		} );

		row.addEventListener( 'click', ( event ) => {
			const toggle = event.target.closest( '[data-item-toggle]' );
			if ( toggle ) {
				setOpen( row, ! row.classList.contains( 'is-open' ) );
				return;
			}

			const action = event.target.closest( '[data-item-action]' );
			if ( action ) {
				runItemAction( action.dataset.itemAction, row.dataset.id, action );
			}
		} );

		refreshRow( row, item );
		setOpen( row, openItems.has( item.id ) );

		return row;
	}

	async function runItemAction( action, id, button ) {
		const items = STX.clone( getItems() );
		const index = findIndex( id );
		if ( index < 0 ) {
			return;
		}

		switch ( action ) {
			case 'duplicate': {
				if ( items.length >= moduleData.maxItems ) {
					STX.toast( i18n.maxReached, 'error' );
					return;
				}
				const copy = Object.assign( STX.clone( items[ index ] ), { id: newId(), featured: false } );
				items.splice( index + 1, 0, copy );
				commitItems( items );
				break;
			}

			case 'delete': {
				const confirmed = await STX.confirm( {
					title: i18n.deleteTitle,
					message: i18n.deleteMessage.replace( '%s', itemLabel( items[ index ], 'member' ) ),
					confirmLabel: i18n.delete,
					danger: true,
				} );
				if ( confirmed ) {
					items.splice( index, 1 );
					openItems.delete( id );
					commitItems( items );
				}
				break;
			}

			case 'up':
			case 'down': {
				const target = index + ( action === 'up' ? -1 : 1 );
				if ( target < 0 || target >= items.length ) {
					return;
				}
				items.splice( target, 0, items.splice( index, 1 )[ 0 ] );
				commitItems( items );
				const moved = list.querySelector( '[data-id="' + id + '"] [data-item-action="' + action + '"]' );
				if ( moved ) {
					moved.focus();
				}
				break;
			}

			case 'pick-icon':
				IconPicker.open( id, button );
				break;

			case 'pick-image':
				pickImage( id );
				break;
		}
	}

	function renderList() {
		const items = getItems();

		list.innerHTML = '';
		items.forEach( ( item ) => list.appendChild( buildRow( item ) ) );

		counter.textContent = items.length + ' / ' + moduleData.maxItems;
		emptyState.hidden = items.length > 0;
		manyNote.hidden = items.filter( ( item ) => item.enabled ).length <= 5;
		addToggle.disabled = items.length >= moduleData.maxItems;
		addToggle.title = addToggle.disabled ? i18n.maxReached : '';

		if ( $ && $.fn.sortable ) {
			$( list ).sortable( 'refresh' );
		}
	}

	function refreshRows() {
		const items = getItems();
		list.querySelectorAll( '.stx-item' ).forEach( ( row ) => {
			const item = items.find( ( entry ) => entry.id === row.dataset.id );
			if ( item ) {
				refreshRow( row, item );
			}
		} );
		manyNote.hidden = items.filter( ( item ) => item.enabled ).length <= 5;
	}

	function initSortable() {
		if ( ! $ || ! $.fn.sortable ) {
			return; // The ▲/▼ buttons still allow reordering.
		}

		$( list ).sortable( {
			handle: '.stx-item__handle',
			items: '> .stx-item',
			axis: 'y',
			tolerance: 'pointer',
			placeholder: 'stx-item-placeholder',
			update() {
				const order = Array.from( list.children ).map( ( row ) => row.dataset.id );
				const byId = {};
				getItems().forEach( ( item ) => {
					byId[ item.id ] = item;
				} );
				commitItems( STX.clone( order.map( ( id ) => byId[ id ] ).filter( Boolean ) ) );
			},
		} );
	}

	function initAddMenu() {
		const close = () => {
			addMenu.hidden = true;
			addToggle.setAttribute( 'aria-expanded', 'false' );
		};

		addToggle.addEventListener( 'click', () => {
			const opening = addMenu.hidden;
			addMenu.hidden = ! opening;
			addToggle.setAttribute( 'aria-expanded', String( opening ) );
			if ( opening ) {
				const first = addMenu.querySelector( 'button:not([disabled])' );
				if ( first ) {
					first.focus();
				}
			}
		} );

		addMenu.addEventListener( 'click', ( event ) => {
			const option = event.target.closest( '[data-stx-add-type]' );
			if ( ! option || getItems().length >= moduleData.maxItems ) {
				return;
			}

			const item = makeItem( option.dataset.stxAddType );
			openItems.add( item.id );
			commitItems( getItems().concat( [ item ] ) );
			close();

			const row = list.querySelector( '[data-id="' + item.id + '"]' );
			if ( row ) {
				row.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				row.querySelector( '[data-item-bind="label"]' ).focus( { preventScroll: true } );
			}
		} );

		document.addEventListener( 'click', ( event ) => {
			if ( ! addMenu.hidden && ! event.target.closest( '.stx-add' ) ) {
				close();
			}
		} );

		addMenu.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' ) {
				close();
				addToggle.focus();
			}
		} );
	}

	/* =====================================================================
	 * Media library (custom icon image)
	 * =================================================================== */

	function pickImage( id ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		const frame = window.wp.media( {
			title: i18n.chooseImage,
			button: { text: i18n.useImage },
			library: { type: 'image' },
			multiple: false,
		} );

		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			updateItem( id, 'icon_image', attachment.url );
			refreshRows();
		} );

		frame.open();
	}

	/* =====================================================================
	 * Icon picker dialog
	 * =================================================================== */

	const IconPicker = ( function () {
		const dialog = document.getElementById( 'stx-icon-picker' );
		const grid = dialog.querySelector( '[data-stx-icon-grid]' );
		const search = dialog.querySelector( '[data-stx-icon-search]' );
		let itemId = null;
		let returnFocus = null;

		function render() {
			const state = store.get();
			const item = getItems()[ findIndex( itemId ) ] || {};
			const query = search.value.trim().toLowerCase();

			const matches = catalog.icons.filter( ( icon ) => ! query || ( icon.label + ' ' + icon.keywords + ' ' + icon.key ).toLowerCase().includes( query ) );

			grid.innerHTML = matches.length
				? matches.map( ( icon ) => {
					const glyph = state.icon_pack === FONT_AWESOME ? faIcon( faClass( icon.key, state.fa_weight ) ) : packSvg( state.icon_pack, icon.key, false );
					const selected = item.icon_source === 'pack' && item.icon === icon.key;
					return '<button type="button" class="stx-icon-option" role="option" aria-selected="' + selected + '" data-icon="' + esc( icon.key ) + '">' + glyph + '<span>' + esc( icon.label ) + '</span></button>';
				} ).join( '' )
				: '<p class="stx-field__help">' + esc( i18n.noIcons ) + '</p>';
		}

		function open( id, trigger ) {
			itemId = id;
			returnFocus = trigger;
			search.value = '';
			render();
			dialog.showModal();
			search.focus();
		}

		function close() {
			dialog.close();
		}

		search.addEventListener( 'input', render );

		grid.addEventListener( 'click', ( event ) => {
			const option = event.target.closest( '[data-icon]' );
			if ( ! option ) {
				return;
			}

			const items = STX.clone( getItems() );
			const item = items[ findIndex( itemId ) ];
			if ( item ) {
				item.icon = option.dataset.icon;
				item.icon_source = 'pack';
				commitItems( items, { inPlace: true } );
				refreshRows();
			}
			close();
		} );

		dialog.addEventListener( 'click', ( event ) => {
			if ( event.target === dialog || event.target.closest( '[data-stx-dialog-close]' ) ) {
				close();
			}
		} );

		dialog.addEventListener( 'close', () => {
			if ( returnFocus ) {
				returnFocus.focus();
			}
		} );

		return { open };
	}() );

	/* =====================================================================
	 * Boot
	 * =================================================================== */

	store.subscribe( ( path ) => {
		if ( ( path === '*' || path === 'items' ) && ! editingField ) {
			renderList();
		} else {
			refreshRows();
		}

		if ( path === 'icon_pack' || path === '*' ) {
			const pack = store.get( 'icon_pack' );
			loadPack( pack ).then( () => {
				refreshRows();
				queueRender();
			} );
		}

		queueRender();
	} );

	initPreview();
	initSortable();
	initAddMenu();
	renderList();

	// The fallback pack (Tabler) is needed for brand icons in most packs.
	loadPack( store.get( 'icon_pack' ) ).then( () => {
		refreshRows();
		queueRender();
	} );
	queueRender();
}( window.jQuery ) );
