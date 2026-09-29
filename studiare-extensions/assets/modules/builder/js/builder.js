/**
 * Studiare Extensions — page templates (front end + Elementor preview).
 *
 * Clicks are handled with delegation on the document, so widgets re-rendered
 * by the Elementor editor keep working. Element-level setup (variation
 * buttons, quantity buttons, collapsible text…) runs through initScope(),
 * which is idempotent and is re-run by Elementor's `element_ready` hook.
 */
( function () {
	'use strict';

	const config = window.stxBuilder || {};
	const i18n = config.i18n || {};
	const doc = document;
	const root = doc.documentElement;
	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const isEditor = () => doc.body && doc.body.classList.contains( 'elementor-editor-active' );

	/* ---------------------------------------------------------------------
	 * Layers: drawer, search overlay, lightbox
	 * ------------------------------------------------------------------- */

	let openLayer = null; // { el, trigger }

	function lockScroll( lock ) {
		root.classList.toggle( 'stx-lock', lock );
	}

	function showLayer( el, trigger ) {
		if ( ! el ) {
			return;
		}
		if ( openLayer ) {
			hideLayer();
		}

		// Fixed layers inside a transformed/sticky header would be clipped: move them to <body>.
		if ( ! isEditor() && el.parentElement !== doc.body ) {
			doc.body.appendChild( el );
		}

		el.hidden = false;
		openLayer = { el, trigger };
		lockScroll( true );

		if ( trigger ) {
			trigger.setAttribute( 'aria-expanded', 'true' );
		}

		// Skips unselected tabs (roving tabindex), e.g. in the menu drawer's tabs.
		const focusable = el.querySelector( 'input, a[href], button:not([data-stx-close]):not([tabindex="-1"])' ) || el.querySelector( 'button' );
		if ( focusable ) {
			window.setTimeout( () => focusable.focus( { preventScroll: true } ), 60 );
		}
	}

	function hideLayer() {
		if ( ! openLayer ) {
			return;
		}

		const { el, trigger } = openLayer;
		openLayer = null;

		if ( el.classList.contains( 'stx-lightbox' ) ) {
			el.remove();
		} else {
			el.hidden = true;
		}

		lockScroll( false );

		if ( trigger ) {
			trigger.setAttribute( 'aria-expanded', 'false' );
			trigger.focus( { preventScroll: true } );
		}
	}

	/** Keeps Tab inside the open layer. */
	function trapFocus( event ) {
		if ( ! openLayer || event.key !== 'Tab' ) {
			return;
		}

		const items = Array.from( openLayer.el.querySelectorAll( 'a[href], button, input, iframe, video, [tabindex]' ) )
			.filter( ( node ) => node.getAttribute( 'tabindex' ) !== '-1' && ( node.offsetParent !== null || node === doc.activeElement ) );

		if ( ! items.length ) {
			return;
		}

		const first = items[ 0 ];
		const last = items[ items.length - 1 ];

		if ( event.shiftKey && doc.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && doc.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	/* ---------------------------------------------------------------------
	 * Lightbox (images and videos)
	 * ------------------------------------------------------------------- */

	const CLOSE_ICON = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';

	/** Embeddable player URL for YouTube / Aparat / Vimeo, or null for direct files. */
	function embedUrl( url ) {
		let match = url.match( /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/i );
		if ( match ) {
			return 'https://www.youtube-nocookie.com/embed/' + match[ 1 ] + '?autoplay=1&rel=0';
		}

		match = url.match( /aparat\.com\/(?:v|video\/video\/embed\/videohash)\/([\w-]+)/i );
		if ( match ) {
			return 'https://www.aparat.com/video/video/embed/videohash/' + match[ 1 ] + '/vt/frame?autoplay=true';
		}

		match = url.match( /vimeo\.com\/(?:video\/)?(\d+)/i );
		if ( match ) {
			return 'https://player.vimeo.com/video/' + match[ 1 ] + '?autoplay=1';
		}

		return null;
	}

	function openLightbox( type, src, trigger ) {
		const box = doc.createElement( 'div' );
		box.className = 'stx-lightbox';
		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', 'true' );

		let media;
		if ( 'image' === type ) {
			media = doc.createElement( 'img' );
			media.className = 'stx-lightbox__media';
			media.src = src;
			media.alt = '';
		} else {
			const embed = embedUrl( src );
			if ( embed ) {
				media = doc.createElement( 'iframe' );
				media.className = 'stx-lightbox__media stx-lightbox__media--frame';
				media.src = embed;
				media.allow = 'autoplay; fullscreen; picture-in-picture';
				media.allowFullscreen = true;
			} else {
				media = doc.createElement( 'video' );
				media.className = 'stx-lightbox__media';
				media.src = src;
				media.controls = true;
				media.autoplay = true;
				media.playsInline = true;
			}
		}

		box.innerHTML = '<div class="stx-lightbox__backdrop" data-stx-close></div><div class="stx-lightbox__panel"><button type="button" class="stx-lightbox__close" data-stx-close aria-label="' + ( i18n.close || 'Close' ) + '">' + CLOSE_ICON + '</button></div>';
		box.querySelector( '.stx-lightbox__panel' ).appendChild( media );
		doc.body.appendChild( box );
		showLayer( box, trigger );
	}

	/* ---------------------------------------------------------------------
	 * Tabs, accordions, collapsible text
	 * ------------------------------------------------------------------- */

	function selectTab( tab, focus ) {
		const tabs = tab.closest( '.stx-tabs' );
		if ( ! tabs ) {
			return;
		}

		tabs.querySelectorAll( ':scope > .stx-tabs__nav > .stx-tabs__tab' ).forEach( ( item ) => {
			const selected = item === tab;
			const panel = doc.getElementById( item.getAttribute( 'aria-controls' ) );

			item.classList.toggle( 'is-active', selected );
			item.setAttribute( 'aria-selected', String( selected ) );
			item.tabIndex = selected ? 0 : -1;
			if ( panel ) {
				panel.hidden = ! selected;
			}
		} );

		if ( focus ) {
			tab.focus();
		}
	}

	/** Reveals a hash target hidden in a tab panel or a closed accordion (e.g. #reviews). */
	function revealTarget( id ) {
		if ( ! id ) {
			return;
		}

		let target = null;
		try {
			target = 'stx-buy' === id ? visibleBuyForm() : doc.getElementById( decodeURIComponent( id ) );
		} catch ( error ) {
			return;
		}
		if ( ! target ) {
			return;
		}

		const panel = target.closest( '.stx-tabs__panel[role="tabpanel"]' );
		if ( panel && panel.hidden ) {
			const tab = doc.getElementById( panel.getAttribute( 'aria-labelledby' ) );
			if ( tab ) {
				selectTab( tab );
			}
		}

		const details = target.closest( 'details' );
		if ( details && ! details.open ) {
			details.open = true;
		}

		window.setTimeout( () => target.scrollIntoView( { behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'start' } ), 40 );
	}

	function initCollapsible( scope ) {
		scope.querySelectorAll( '[data-stx-collapse]:not([data-stx-ready])' ).forEach( ( box ) => {
			box.setAttribute( 'data-stx-ready', '' );
			const body = box.querySelector( '.stx-desc__body' );
			const check = () => box.classList.toggle( 'is-short', body.scrollHeight <= body.clientHeight + 24 );
			check();
			window.addEventListener( 'load', check, { once: true } );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Gallery
	 * ------------------------------------------------------------------- */

	function showSlide( gallery, index ) {
		gallery.querySelectorAll( '.stx-gallery__slide' ).forEach( ( slide, i ) => slide.classList.toggle( 'is-active', i === index ) );
		gallery.querySelectorAll( '.stx-gallery__thumb' ).forEach( ( thumb, i ) => thumb.classList.toggle( 'is-active', i === index ) );
	}

	/** Swaps the first image for the chosen variation's image (WooCommerce events). */
	function bindVariationImages() {
		const $ = window.jQuery;
		if ( ! $ || bindVariationImages.done ) {
			return;
		}
		bindVariationImages.done = true;

		const firstImage = () => {
			const gallery = doc.querySelector( '.stx-single [data-stx-gallery]' ) || doc.querySelector( '[data-stx-gallery]' );
			return gallery ? { gallery, img: gallery.querySelector( '.stx-gallery__slide img' ) } : null;
		};

		$( doc.body ).on( 'found_variation', '.variations_form', ( event, variation ) => {
			const found = firstImage();
			if ( ! found || ! found.img || ! variation || ! variation.image || ! variation.image.src ) {
				return;
			}
			if ( ! found.img.dataset.stxSrc ) {
				found.img.dataset.stxSrc = found.img.getAttribute( 'src' );
				found.img.dataset.stxSrcset = found.img.getAttribute( 'srcset' ) || '';
			}
			found.img.setAttribute( 'src', variation.image.src );
			found.img.setAttribute( 'srcset', variation.image.srcset || '' );
			showSlide( found.gallery, 0 );
		} );

		$( doc.body ).on( 'reset_image', '.variations_form', () => {
			const found = firstImage();
			if ( found && found.img && found.img.dataset.stxSrc ) {
				found.img.setAttribute( 'src', found.img.dataset.stxSrc );
				found.img.setAttribute( 'srcset', found.img.dataset.stxSrcset );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Add to cart: quantity buttons and variation buttons
	 * ------------------------------------------------------------------- */

	// Studiare (and some plugins) add their own +/- buttons, often after this
	// runs, so ours are always added and builder.css hides the others.
	function initQuantity( scope ) {
		scope.querySelectorAll( '.stx-atc .quantity:not([data-stx-ready])' ).forEach( ( box ) => {
			box.setAttribute( 'data-stx-ready', '' );
			const input = box.querySelector( 'input.qty' );
			if ( ! input || input.type === 'hidden' ) {
				return;
			}

			const make = ( sign, label ) => {
				const button = doc.createElement( 'button' );
				button.type = 'button';
				button.className = 'stx-qty-btn';
				button.textContent = sign;
				button.setAttribute( 'aria-label', label );
				button.addEventListener( 'click', () => {
					const step = parseFloat( input.step ) || 1;
					const min = input.min !== '' ? parseFloat( input.min ) : 1;
					const max = input.max !== '' ? parseFloat( input.max ) : Infinity;
					const next = ( parseFloat( input.value ) || min ) + ( sign === '+' ? step : -step );
					input.value = String( Math.min( max, Math.max( min, next ) ) );
					input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				} );
				return button;
			};

			// Placed next to the input rather than in the box: a plugin may have
			// wrapped the input, and `insertBefore()` needs a direct child.
			input.insertAdjacentElement( 'beforebegin', make( '+', i18n.increase || '+' ) );
			input.insertAdjacentElement( 'afterend', make( '−', i18n.decrease || '−' ) );
		} );
	}

	function buildChoices( select ) {
		let holder = select.nextElementSibling;
		if ( ! holder || ! holder.classList.contains( 'stx-choices' ) ) {
			holder = doc.createElement( 'div' );
			holder.className = 'stx-choices';
			holder.setAttribute( 'role', 'radiogroup' );
			select.insertAdjacentElement( 'afterend', holder );
		}

		holder.innerHTML = '';
		Array.from( select.options ).forEach( ( option ) => {
			if ( ! option.value ) {
				return;
			}
			const button = doc.createElement( 'button' );
			button.type = 'button';
			button.className = 'stx-choice' + ( option.value === select.value ? ' is-selected' : '' );
			button.textContent = option.textContent;
			button.disabled = option.disabled;
			button.setAttribute( 'role', 'radio' );
			button.setAttribute( 'aria-checked', String( option.value === select.value ) );
			button.addEventListener( 'click', () => {
				select.value = option.value === select.value ? '' : option.value;
				select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				if ( window.jQuery ) {
					window.jQuery( select ).trigger( 'change' );
				}
				buildChoices( select );
			} );
			holder.appendChild( button );
		} );
	}

	function initChoices( scope ) {
		scope.querySelectorAll( '.stx-atc--vars-buttons table.variations select:not(.stx-enhanced)' ).forEach( ( select ) => {
			select.classList.add( 'stx-enhanced' );
			buildChoices( select );
		} );

		const $ = window.jQuery;
		if ( $ && ! initChoices.bound ) {
			initChoices.bound = true;
			// WooCommerce rebuilds the option lists as choices change.
			$( doc.body ).on( 'woocommerce_update_variation_values reset_data', '.variations_form', function () {
				this.querySelectorAll( 'select.stx-enhanced' ).forEach( buildChoices );
			} );
		}
	}

	/* ---------------------------------------------------------------------
	 * Sticky header
	 * ------------------------------------------------------------------- */

	function initStickyHeader() {
		const header = doc.querySelector( '.stx-hf--header' );
		if ( ! header || header.dataset.stxReady ) {
			return;
		}
		header.dataset.stxReady = '1';

		const desktopQuery = window.matchMedia( '(min-width: ' + ( ( config.breakpoint || 1024 ) + 1 ) + 'px)' );
		let mode = 'none';
		let lastY = window.scrollY;

		function applyMode() {
			mode = header.dataset[ desktopQuery.matches ? 'stxStickyDesktop' : 'stxStickyMobile' ] || 'none';
			const sticky = mode !== 'none';
			header.classList.toggle( 'is-sticky', sticky );
			if ( ! sticky ) {
				header.classList.remove( 'is-hidden', 'is-scrolled' );
			}
			updateOffset();
		}

		// Sticky columns and the section jump bar stop below the header while it is on screen.
		function updateOffset() {
			const shown = mode === 'always' || ( mode === 'scroll_up' && ! header.classList.contains( 'is-hidden' ) );
			root.style.setProperty( '--stx-header-offset', shown ? header.offsetHeight + 'px' : '0px' );
		}

		function onScroll() {
			if ( mode === 'none' ) {
				return;
			}
			const y = window.scrollY;
			header.classList.toggle( 'is-scrolled', y > 8 );
			if ( mode === 'scroll_up' ) {
				const goingDown = y > lastY && y > header.offsetHeight + 40;
				const hide = goingDown && ! header.contains( doc.activeElement );
				if ( hide !== header.classList.contains( 'is-hidden' ) ) {
					header.classList.toggle( 'is-hidden', hide );
					updateOffset();
				}
			}
			lastY = y;
		}

		desktopQuery.addEventListener( 'change', applyMode );
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		if ( window.ResizeObserver ) {
			new ResizeObserver( updateOffset ).observe( header );
		}
		applyMode();
	}

	/* ---------------------------------------------------------------------
	 * Mobile buy bar
	 * ------------------------------------------------------------------- */

	/** Templates may hold one add-to-cart per device; use the one on screen. */
	function visibleBuyForm() {
		return Array.from( doc.querySelectorAll( '[data-stx-atc]' ) ).find( ( el ) => el.offsetParent !== null ) || null;
	}

	function initBuyBar() {
		const bar = doc.querySelector( '.stx-buybar:not(.is-editor)' );
		if ( ! bar || bar.dataset.stxReady ) {
			return;
		}
		bar.dataset.stxReady = '1';

		// Out of any transformed/sticky ancestor, so `position: fixed` is the viewport.
		doc.body.appendChild( bar );
		doc.body.classList.add( 'stx-buybar-on', bar.classList.contains( 'stx-buybar--tablet' ) ? 'stx-buybar-on--tablet' : 'stx-buybar-on--mobile' );

		if ( bar.dataset.stxBuybar === 'always' ) {
			bar.classList.add( 'is-visible' );
			return;
		}

		const target = visibleBuyForm();
		if ( ! target || ! window.IntersectionObserver ) {
			const onScroll = () => bar.classList.toggle( 'is-visible', window.scrollY > 320 );
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			onScroll();
			return;
		}

		new IntersectionObserver( ( entries ) => {
			entries.forEach( ( entry ) => {
				bar.classList.toggle( 'is-visible', ! entry.isIntersecting && entry.boundingClientRect.top < 0 );
			} );
		} ).observe( target );
	}

	/* ---------------------------------------------------------------------
	 * Section jump bar (scroll spy)
	 * ------------------------------------------------------------------- */

	function initSpy( scope ) {
		if ( ! window.IntersectionObserver ) {
			return;
		}

		scope.querySelectorAll( '[data-stx-spy]:not([data-stx-ready])' ).forEach( ( box ) => {
			box.setAttribute( 'data-stx-ready', '' );
			const links = Array.from( box.querySelectorAll( '.stx-tabs__nav a' ) );

			const observer = new IntersectionObserver( ( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						links.forEach( ( link ) => link.classList.toggle( 'is-active', link.getAttribute( 'href' ) === '#' + entry.target.id ) );
					}
				} );
			}, { rootMargin: '-35% 0px -60% 0px' } );

			box.querySelectorAll( ':scope > .stx-tabs__panel' ).forEach( ( panel ) => observer.observe( panel ) );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Dark mode (mirrors Studiare's dark-mode.js storage format)
	 * ------------------------------------------------------------------- */

	function toggleDarkMode() {
		const body = doc.body;
		const wasDark = body.classList.contains( 'scdarkcolors' );

		const themeToggle = doc.querySelector( '.dark-mode-toggle:not([data-stx-dark])' );
		if ( themeToggle ) {
			themeToggle.click();
		}

		if ( body.classList.contains( 'scdarkcolors' ) !== wasDark ) {
			return;
		}

		const enable = ! wasDark;
		root.classList.toggle( 'scdarkcolors', enable );
		body.classList.toggle( 'scdarkcolors', enable );
		try {
			window.localStorage.setItem( 'darkMode', enable ? 'enabled' : 'disabled' );
		} catch ( error ) {
			// Storage can be unavailable (private mode); the switch still works for this page.
		}
	}

	/* ---------------------------------------------------------------------
	 * Live search: results under the Search widget's field while typing.
	 * The form still submits to the full results page (Enter / button).
	 * ------------------------------------------------------------------- */

	const LiveSearch = ( function () {
		const DEBOUNCE_MS = 250;
		const SKELETON_ROWS = 3;
		const search = config.search || {};
		const states = new WeakMap();

		function stateOf( form ) {
			if ( ! states.has( form ) ) {
				states.set( form, {
					input: form.querySelector( '.stx-search__input' ),
					panel: form.querySelector( '.stx-live' ),
					results: form.querySelector( '[data-stx-live-results]' ),
					status: form.querySelector( '[data-stx-live-status]' ),
					cache: new Map(),
					timer: 0,
					request: null,
					term: '',
				} );
			}
			return states.get( form );
		}

		function setOpen( form, open ) {
			const state = stateOf( form );
			state.panel.hidden = ! open;
			state.input.setAttribute( 'aria-expanded', String( open ) );
			form.classList.toggle( 'is-live-open', open );
		}

		/** Detailed results bring their own header and footer (see builder.css). */
		function setStyle( form, style ) {
			stateOf( form ).panel.classList.toggle( 'stx-live--detailed', style === 'detailed' );
		}

		function skeleton() {
			const row = '<li class="stx-live__item"><span class="stx-live__link"><span class="stx-live__media"></span>' +
				'<span class="stx-live__text"><span class="stx-live__skel"></span><span class="stx-live__skel stx-live__skel--short"></span></span></span></li>';

			return '<ul class="stx-live__list stx-live__list--skeleton" aria-hidden="true">' + row.repeat( SKELETON_ROWS ) + '</ul>';
		}

		function show( form, html, message, style ) {
			const state = stateOf( form );
			form.classList.remove( 'is-loading' );
			if ( style ) {
				// The server's answer wins over the page config, which a page cache may have kept from before a settings change.
				setStyle( form, style );
			}
			state.results.innerHTML = html;
			state.status.textContent = message;
			setOpen( form, html !== '' );
		}

		function fetchResults( form, term ) {
			const state = stateOf( form );
			const key = term.toLowerCase();

			if ( state.cache.has( key ) ) {
				const cached = state.cache.get( key );
				show( form, cached.html, cached.message, cached.style );
				return;
			}

			const url = new URL( search.url, window.location.href );
			url.searchParams.set( 'term', term );
			url.searchParams.set( 'scope', form.dataset.stxLive );
			url.searchParams.set( 'limit', form.dataset.stxLiveLimit );

			state.request = new AbortController();
			form.classList.add( 'is-loading' );

			// Detailed results show placeholder rows while the first answer loads;
			// later answers replace the visible results in place, without a flash.
			if ( search.style === 'detailed' && state.panel.hidden ) {
				setStyle( form, 'detailed' );
				state.results.innerHTML = skeleton();
				setOpen( form, true );
			}

			fetch( url, { signal: state.request.signal, credentials: 'same-origin' } )
				.then( ( response ) => response.json() )
				.then( ( payload ) => {
					if ( ! payload || ! payload.success ) {
						throw new Error( 'Live search failed' );
					}
					state.cache.set( key, payload.data );
					show( form, payload.data.html, payload.data.message, payload.data.style );
				} )
				.catch( ( error ) => {
					if ( error.name !== 'AbortError' ) {
						const note = doc.createElement( 'p' );
						note.className = 'stx-live__note';
						note.textContent = i18n.searchFailed || '';
						show( form, note.outerHTML, note.textContent );
					}
				} );
		}

		function onInput( form ) {
			const state = stateOf( form );
			const term = state.input.value.trim();
			if ( term === state.term ) {
				return;
			}

			state.term = term;
			window.clearTimeout( state.timer );
			if ( state.request ) {
				state.request.abort(); // A newer term makes the pending answer stale.
				state.request = null;
			}

			if ( term.length < ( search.minChars || 2 ) ) {
				show( form, '', '' );
				return;
			}

			state.timer = window.setTimeout( () => fetchResults( form, term ), DEBOUNCE_MS );
		}

		/** Arrow keys move between the field and the results, like a suggestion list. */
		function onArrow( form, event ) {
			const state = stateOf( form );
			if ( state.panel.hidden ) {
				return;
			}

			// Only visible stops: the detailed style hides the widget's own footer.
			const links = state.panel.querySelectorAll( 'a.stx-live__link, .stx-live__topic, .stx-live__all' );
			const stops = [ state.input ].concat( Array.from( links ).filter( ( node ) => node.getClientRects().length > 0 ) );
			const index = stops.indexOf( doc.activeElement );
			if ( index < 0 ) {
				return;
			}

			event.preventDefault();
			const next = index + ( event.key === 'ArrowDown' ? 1 : -1 );
			stops[ Math.max( 0, Math.min( stops.length - 1, next ) ) ].focus();
		}

		/** Closes every open result list except the one inside `keep`. */
		function closeAll( keep ) {
			doc.querySelectorAll( '.stx-search.is-live-open' ).forEach( ( form ) => {
				if ( ! keep || ! form.contains( keep ) ) {
					setOpen( form, false );
				}
			} );
		}

		function formOf( node ) {
			return search.url && node instanceof Element ? node.closest( '.stx-search[data-stx-live]' ) : null;
		}

		function init() {
			doc.addEventListener( 'input', ( event ) => {
				const form = formOf( event.target );
				if ( form && event.target.classList.contains( 'stx-search__input' ) ) {
					onInput( form );
				}
			} );

			doc.addEventListener( 'keydown', ( event ) => {
				const form = formOf( event.target );
				if ( form && ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) ) {
					onArrow( form, event );
				}
			} );

			// Reopen the last results when the field gets focus from outside the form
			// (not when Escape or the arrow keys move focus back to it).
			doc.addEventListener( 'focusin', ( event ) => {
				const form = formOf( event.target );
				closeAll( form );
				const fromInside = form && event.relatedTarget instanceof Node && form.contains( event.relatedTarget );
				if ( form && ! fromInside && event.target.classList.contains( 'stx-search__input' ) && stateOf( form ).results.innerHTML !== '' ) {
					setOpen( form, true );
				}
			} );
		}

		/** Handles Escape; returns true when a result list was open. */
		function escape() {
			const open = doc.querySelector( '.stx-search.is-live-open' );
			if ( ! open ) {
				return false;
			}
			setOpen( open, false );
			stateOf( open ).input.focus();
			return true;
		}

		return { init, closeAll, escape };
	}() );

	/* ---------------------------------------------------------------------
	 * Delegated clicks
	 * ------------------------------------------------------------------- */

	/** Runs after the current click has finished bubbling, so the theme's
	 * "click outside closes it" handlers do not undo what we open. */
	const afterClick = ( callback ) => window.setTimeout( callback, 0 );

	function closeMenus( except ) {
		doc.querySelectorAll( '.stx-nav--horizontal .stx-menu__item.is-open' ).forEach( ( item ) => {
			if ( ! except || ! item.contains( except ) ) {
				item.classList.remove( 'is-open' );
				const caret = item.querySelector( ':scope > .stx-menu__row > .stx-menu__caret' );
				if ( caret ) {
					caret.setAttribute( 'aria-expanded', 'false' );
				}
			}
		} );

		doc.querySelectorAll( '[data-stx-toggle][aria-expanded="true"]' ).forEach( ( button ) => {
			if ( ! except || ! button.parentElement.contains( except ) ) {
				button.setAttribute( 'aria-expanded', 'false' );
				const target = doc.getElementById( button.dataset.stxToggle );
				if ( target ) {
					target.hidden = true;
				}
			}
		} );
	}

	doc.addEventListener( 'click', ( event ) => {
		const target = event.target instanceof Element ? event.target : null;
		if ( ! target ) {
			return;
		}

		LiveSearch.closeAll( target );

		const open = target.closest( '[data-stx-open]' );
		if ( open ) {
			event.preventDefault();
			showLayer( doc.getElementById( open.dataset.stxOpen ), open );
			return;
		}

		if ( target.closest( '[data-stx-close]' ) ) {
			event.preventDefault();
			hideLayer();
			return;
		}

		const video = target.closest( '[data-stx-video]' );
		if ( video ) {
			const src = video.getAttribute( 'data-stx-video' );
			if ( src && src !== '#' ) {
				event.preventDefault();
				openLightbox( 'video', src, video );
			}
			return;
		}

		const thumb = target.closest( '.stx-gallery__thumb' );
		if ( thumb ) {
			showSlide( thumb.closest( '.stx-gallery' ), parseInt( thumb.dataset.index, 10 ) || 0 );
			return;
		}

		const slide = target.closest( '[data-stx-zoom] .stx-gallery__slide' );
		if ( slide && slide.dataset.full ) {
			openLightbox( 'image', slide.dataset.full, null );
			return;
		}

		const tab = target.closest( '.stx-tabs--tabs > .stx-tabs__nav > .stx-tabs__tab' );
		if ( tab ) {
			selectTab( tab );
			return;
		}

		const expand = target.closest( '[data-stx-expand-all]' );
		if ( expand ) {
			const opening = expand.getAttribute( 'aria-expanded' ) !== 'true';
			expand.closest( '.stx-curr' ).querySelectorAll( 'details' ).forEach( ( details ) => {
				details.open = opening;
			} );
			expand.setAttribute( 'aria-expanded', String( opening ) );
			expand.textContent = opening ? expand.dataset.close : expand.dataset.open;
			return;
		}

		const more = target.closest( '.stx-desc__more' );
		if ( more ) {
			const box = more.closest( '.stx-desc' );
			const opening = ! box.classList.contains( 'is-open' );
			box.classList.toggle( 'is-open', opening );
			more.setAttribute( 'aria-expanded', String( opening ) );
			more.textContent = opening ? ( i18n.collapse || 'Show less' ) : ( i18n.expand || 'Show more' );
			return;
		}

		const caret = target.closest( '.stx-menu__caret' );
		if ( caret ) {
			const item = caret.closest( '.stx-menu__item' );
			const opening = ! item.classList.contains( 'is-open' );
			if ( opening && ! item.closest( '.stx-menu--drawer' ) ) {
				closeMenus( item );
			}
			item.classList.toggle( 'is-open', opening );
			caret.setAttribute( 'aria-expanded', String( opening ) );
			return;
		}

		const toggle = target.closest( '[data-stx-toggle]' );
		if ( toggle ) {
			const menu = doc.getElementById( toggle.dataset.stxToggle );
			if ( menu ) {
				const opening = menu.hidden;
				closeMenus();
				menu.hidden = ! opening;
				toggle.setAttribute( 'aria-expanded', String( opening ) );
			}
			return;
		}

		if ( target.closest( '[data-stx-dark]' ) ) {
			event.preventDefault();
			toggleDarkMode();
			return;
		}

		const cart = target.closest( '[data-stx-cart="auto"]' );
		if ( cart ) {
			const offcanvas = doc.querySelector( '.sc-cart-offcanvas' );
			if ( offcanvas ) {
				event.preventDefault();
				// Studiare closes its mini cart on any click outside it, including this one.
				afterClick( () => offcanvas.classList.add( 'active' ) );
			}
			return;
		}

		const hashLink = target.closest( 'a[href^="#"]' );
		if ( hashLink && hashLink.getAttribute( 'href' ).length > 1 ) {
			const id = hashLink.getAttribute( 'href' ).slice( 1 );
			const destination = doc.getElementById( id );
			if ( destination && ( destination.closest( '.stx-tabs__panel[hidden]' ) || destination.closest( 'details:not([open])' ) || destination.closest( '.stx-tpl' ) ) ) {
				event.preventDefault();
				revealTarget( id );
			}
			return;
		}

		closeMenus();
	} );

	doc.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			if ( LiveSearch.escape() ) {
				return;
			}
			if ( openLayer ) {
				hideLayer();
			} else {
				closeMenus();
			}
			return;
		}

		trapFocus( event );

		const tab = event.target instanceof Element ? event.target.closest( '.stx-tabs--tabs .stx-tabs__tab' ) : null;
		if ( tab && ( event.key === 'ArrowLeft' || event.key === 'ArrowRight' ) ) {
			const tabs = Array.from( tab.parentElement.querySelectorAll( '.stx-tabs__tab' ) );
			const rtl = getComputedStyle( tab ).direction === 'rtl';
			const forward = ( event.key === 'ArrowRight' ) !== rtl;
			const index = tabs.indexOf( tab );
			event.preventDefault();
			selectTab( tabs[ ( index + ( forward ? 1 : -1 ) + tabs.length ) % tabs.length ], true );
		}
	} );

	window.addEventListener( 'hashchange', () => revealTarget( window.location.hash.slice( 1 ) ) );

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------- */

	function initScope( scope ) {
		initCollapsible( scope );
		initQuantity( scope );
		initChoices( scope );
		initSpy( scope );
	}

	function boot() {
		initScope( doc );
		LiveSearch.init();
		bindVariationImages();
		initStickyHeader();
		if ( ! isEditor() ) {
			initBuyBar();
		}
		if ( window.location.hash.length > 1 ) {
			revealTarget( window.location.hash.slice( 1 ) );
		}
	}

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	// Elementor editor: widgets re-render one by one.
	let hooked = false;
	function hookElementor() {
		if ( hooked || ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}
		hooked = true;
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', ( $scope ) => {
			const element = $scope && $scope[ 0 ] ? $scope[ 0 ] : null;
			if ( element ) {
				initScope( element );
			}
		} );
	}

	// Elementor announces itself with a jQuery event (native listeners do not see it).
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', hookElementor );
	}
	window.addEventListener( 'elementor/frontend/init', hookElementor );
	hookElementor();
}() );
