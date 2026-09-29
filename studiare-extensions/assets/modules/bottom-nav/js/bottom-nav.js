/**
 * Studiare Extensions — mobile bottom navigation runtime.
 *
 * Responsibilities:
 *   - item actions (sheets, theme cart / menu / login modal, dark mode, back to top)
 *   - bottom sheets: open/close, focus trap, Esc, drag-to-close, Android back button
 *   - live search results in the search sheet (admin-ajax, see Live_Search.php)
 *   - live cart badge (WooCommerce fragments) with a bump animation
 *   - optional hide-on-scroll
 *
 * Plain links are real <a href> elements, so navigation, long-press and
 * "open in new tab" keep working even if this script fails to load.
 */
( function () {
	'use strict';

	const nav = document.getElementById( 'stx-bottom-nav' );
	if ( ! nav ) {
		return;
	}

	const config = Object.assign( { hideOnScroll: false, haptic: true, search: {}, i18n: {} }, window.stxBottomNav || {} );
	const root = document.documentElement;
	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	/** Sheet close duration; must match `.stx-sheet__panel` transition in CSS. */
	const SHEET_CLOSE_MS = 450;

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	const getItems = () => Array.from( nav.querySelectorAll( '.stx-bn__item' ) );

	/** Runs a callback after the current click finished bubbling, so theme
	 *  "click outside to close" handlers do not immediately undo our action. */
	const afterClick = ( callback ) => window.setTimeout( callback, 0 );

	function vibrate() {
		if ( config.haptic && typeof navigator.vibrate === 'function' ) {
			navigator.vibrate( 8 );
		}
	}

	/** Moves the active state (and the sliding indicator) to an item. */
	function setActive( activeItem ) {
		const items = getItems();

		items.forEach( ( item ) => {
			const isActive = item === activeItem;
			const link = item.querySelector( '.stx-bn__link' );

			item.classList.toggle( 'is-active', isActive );
			if ( isActive ) {
				link.setAttribute( 'aria-current', 'page' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );

		const index = items.indexOf( activeItem );
		nav.style.setProperty( '--stx-bn-active', String( index ) );
		nav.classList.toggle( 'has-active', index >= 0 );
	}

	function isSamePage( link ) {
		const target = new URL( link.href, window.location.href );
		return target.origin === window.location.origin
			&& target.pathname === window.location.pathname
			&& target.search === window.location.search;
	}

	/* ---------------------------------------------------------------------
	 * Dark mode (mirrors Studiare's dark-mode.js storage format)
	 * ------------------------------------------------------------------- */

	function toggleDarkMode() {
		const body = document.body;
		const wasDark = body.classList.contains( 'scdarkcolors' );

		// Prefer the theme's own toggle so its icons and state stay in sync.
		const themeToggle = document.querySelector( '.dark-mode-toggle' );
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
			// Storage can be unavailable (private mode); the toggle still works for this page.
		}
	}

	/* ---------------------------------------------------------------------
	 * Bottom sheets
	 * ------------------------------------------------------------------- */

	const Sheets = ( function () {
		const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

		let current = null; // { el, trigger }
		let historyEntry = false; // True while our pushState entry is on top.
		let pendingNavigation = null; // Callback to run once our history entry is popped.

		function focusables( el ) {
			return Array.from( el.querySelectorAll( FOCUSABLE ) ).filter( ( node ) => node.offsetParent !== null );
		}

		function open( id, trigger ) {
			const el = document.getElementById( id );
			if ( ! el ) {
				return false;
			}

			if ( current ) {
				close( { restoreFocus: false, keepHistory: true } );
			}

			current = { el, trigger };
			el.hidden = false;
			void el.offsetHeight; // Commit `display` before adding the class so the slide-in transition runs.
			el.classList.add( 'is-open' );
			root.classList.add( 'stx-sheet-open' );

			if ( trigger ) {
				trigger.setAttribute( 'aria-expanded', 'true' );
			}

			// Focus synchronously (inside the tap) so iOS shows the keyboard for search.
			const autofocus = el.querySelector( '[data-stx-autofocus]' ) || el.querySelector( '.stx-sheet__panel' );
			autofocus.focus( { preventScroll: true } );

			if ( el.hasAttribute( 'data-stx-history' ) && ! historyEntry ) {
				try {
					window.history.pushState( { stxSheet: id }, '' );
					historyEntry = true;
				} catch ( error ) {
					historyEntry = false;
				}
			}

			document.addEventListener( 'keydown', onKeydown );
			return true;
		}

		/**
		 * @param {Object}  [options]
		 * @param {boolean} [options.restoreFocus=true] Return focus to the trigger.
		 * @param {boolean} [options.fromHistory=false] Closing because the user pressed Back.
		 * @param {boolean} [options.keepHistory=false] Another sheet replaces this one.
		 */
		function close( options = {} ) {
			if ( ! current ) {
				return;
			}

			const { el, trigger } = current;
			current = null;

			el.classList.remove( 'is-open' );
			root.classList.remove( 'stx-sheet-open' );
			document.removeEventListener( 'keydown', onKeydown );

			const panel = el.querySelector( '.stx-sheet__panel' );
			panel.style.transform = '';

			window.setTimeout( () => {
				// The sheet may have been reopened during the closing animation.
				if ( ! el.classList.contains( 'is-open' ) ) {
					el.hidden = true;
				}
			}, reducedMotion.matches ? 0 : SHEET_CLOSE_MS );

			if ( trigger ) {
				trigger.setAttribute( 'aria-expanded', 'false' );
				if ( options.restoreFocus !== false ) {
					trigger.focus( { preventScroll: true } );
				}
			}

			if ( historyEntry && ! options.fromHistory && ! options.keepHistory ) {
				historyEntry = false;
				window.history.back();
			} else if ( options.fromHistory ) {
				historyEntry = false;
			}
		}

		/**
		 * Navigates away from inside a sheet without leaving our extra history
		 * entry behind (which would make the Back button need two presses).
		 */
		function navigate( go ) {
			if ( ! historyEntry ) {
				go();
				return;
			}

			pendingNavigation = go;
			historyEntry = false;
			window.history.back();
		}

		function onPopState() {
			if ( pendingNavigation ) {
				const go = pendingNavigation;
				pendingNavigation = null;
				go();
				return;
			}

			if ( current ) {
				close( { fromHistory: true } );
			}
		}

		function onKeydown( event ) {
			if ( ! current ) {
				return;
			}

			if ( event.key === 'Escape' ) {
				close();
				return;
			}

			if ( event.key !== 'Tab' ) {
				return;
			}

			// Keep keyboard focus inside the open sheet.
			const nodes = focusables( current.el );
			if ( ! nodes.length ) {
				return;
			}

			const first = nodes[ 0 ];
			const last = nodes[ nodes.length - 1 ];

			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		}

		/** Links whose clicks WooCommerce or builders handle themselves. */
		function isInterceptableLink( link, event ) {
			return link.href
				&& ! event.defaultPrevented
				&& event.button === 0
				&& ! ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey )
				&& ! link.target
				&& ! link.hasAttribute( 'download' )
				&& link.getAttribute( 'href' ).charAt( 0 ) !== '#'
				&& ! link.matches( '.remove, .remove_from_cart_button, .ajax_add_to_cart, [role="button"]' );
		}

		function bindSheet( el ) {
			el.addEventListener( 'click', ( event ) => {
				if ( event.target.closest( '[data-stx-close]' ) ) {
					close();
					return;
				}

				if ( ! el.hasAttribute( 'data-stx-history' ) ) {
					return;
				}

				const link = event.target.closest( 'a' );
				if ( link && isInterceptableLink( link, event ) ) {
					event.preventDefault();
					navigate( () => window.location.assign( link.href ) );
				}
			} );

			el.addEventListener( 'submit', ( event ) => {
				const form = event.target;
				if ( historyEntry && ! event.defaultPrevented ) {
					event.preventDefault();
					navigate( () => form.submit() );
				}
			} );

			enableDragToClose( el );
		}

		/** Swipe the header/grip down to dismiss. */
		function enableDragToClose( el ) {
			const panel = el.querySelector( '.stx-sheet__panel' );

			el.querySelectorAll( '[data-stx-drag]' ).forEach( ( handle ) => {
				let startY = 0;
				let startTime = 0;
				let offset = 0;

				function onMove( event ) {
					offset = Math.max( 0, event.clientY - startY );
					panel.style.transform = 'translateY(' + offset + 'px)';
				}

				function onEnd() {
					handle.removeEventListener( 'pointermove', onMove );
					panel.style.transition = '';

					const velocity = offset / Math.max( 1, performance.now() - startTime );
					if ( offset > 90 || velocity > 0.6 ) {
						close();
					} else {
						panel.style.transform = '';
					}
					offset = 0;
				}

				handle.addEventListener( 'pointerdown', ( event ) => {
					if ( event.target.closest( 'button' ) || ( event.pointerType === 'mouse' && event.button !== 0 ) ) {
						return;
					}

					startY = event.clientY;
					startTime = performance.now();
					panel.style.transition = 'none';
					handle.setPointerCapture( event.pointerId );
					handle.addEventListener( 'pointermove', onMove );
					handle.addEventListener( 'pointerup', onEnd, { once: true } );
					handle.addEventListener( 'pointercancel', onEnd, { once: true } );
				} );
			} );
		}

		function init() {
			document.querySelectorAll( '.stx-sheet' ).forEach( bindSheet );
			window.addEventListener( 'popstate', onPopState );
		}

		return { init, open, close, isOpen: () => current !== null };
	}() );

	/* ---------------------------------------------------------------------
	 * Live search: results under the search field while typing. The form
	 * still submits to the full results page (Enter / Search button).
	 * ------------------------------------------------------------------- */

	const LiveSearch = ( function () {
		const DEBOUNCE_MS = 250;

		function bind( form ) {
			const body = form.closest( '.stx-sheet__body' );
			const input = form.querySelector( '.stx-search__input' );
			const results = body.querySelector( '[data-stx-results]' );
			const status = body.querySelector( '[data-stx-results-status]' );
			const skeleton = body.querySelector( '[data-stx-results-skeleton]' );
			const initialHtml = results.innerHTML;
			const cache = new Map();
			let timer = 0;
			let request = null;
			let lastTerm = '';

			function setLoading( loading ) {
				form.classList.toggle( 'is-loading', loading );
				results.classList.toggle( 'is-loading', loading );
			}

			function show( html, message ) {
				setLoading( false );
				results.innerHTML = html;
				status.textContent = message;
			}

			/** Error state, built with textContent: the message is plain text. */
			function showNote( message ) {
				const state = document.createElement( 'div' );
				const text = document.createElement( 'p' );
				state.className = 'stx-results-state';
				text.className = 'stx-results-state__text';
				text.textContent = message;
				state.appendChild( text );
				show( '', message );
				results.appendChild( state );
			}

			function search( term ) {
				if ( cache.has( term ) ) {
					show( cache.get( term ).html, cache.get( term ).message );
					return;
				}

				request = new AbortController();
				setLoading( true );

				// Placeholder rows until the first list arrives; an existing list is dimmed instead.
				if ( skeleton && ! results.querySelector( '.stx-results__link[href]' ) ) {
					results.replaceChildren( skeleton.content.cloneNode( true ) );
				}

				const url = new URL( config.search.url, window.location.href );
				url.searchParams.set( 'item', form.getAttribute( 'data-stx-live-search' ) );
				url.searchParams.set( 'term', term );

				fetch( url, { signal: request.signal, credentials: 'same-origin' } )
					.then( ( response ) => response.json() )
					.then( ( payload ) => {
						if ( ! payload || ! payload.success ) {
							throw new Error( 'Live search failed' );
						}
						cache.set( term, payload.data );
						show( payload.data.html, payload.data.message );
					} )
					.catch( ( error ) => {
						if ( error.name !== 'AbortError' ) {
							showNote( config.i18n.searchFailed || '' );
						}
					} );
			}

			input.addEventListener( 'input', () => {
				const term = input.value.trim();
				if ( term === lastTerm ) {
					return;
				}

				lastTerm = term;
				window.clearTimeout( timer );
				if ( request ) {
					request.abort(); // A newer term makes the pending answer stale.
					request = null;
				}

				if ( term.length < ( config.search.minChars || 2 ) ) {
					show( initialHtml, '' );
					return;
				}

				timer = window.setTimeout( () => search( term ), DEBOUNCE_MS );
			} );

			// Arrow keys move between the field and the results, like a suggestion list.
			body.addEventListener( 'keydown', ( event ) => {
				if ( event.key !== 'ArrowDown' && event.key !== 'ArrowUp' ) {
					return;
				}

				const stops = [ input ].concat( Array.from( results.querySelectorAll( 'a.stx-results__link, .stx-results__all' ) ) );
				const index = stops.indexOf( document.activeElement );
				if ( index < 0 || stops.length < 2 ) {
					return;
				}

				event.preventDefault();
				const next = index + ( event.key === 'ArrowDown' ? 1 : -1 );
				stops[ Math.max( 0, Math.min( stops.length - 1, next ) ) ].focus();
			} );
		}

		function init() {
			if ( config.search.url ) {
				document.querySelectorAll( '.stx-sheet [data-stx-live-search]' ).forEach( bind );
			}
		}

		return { init };
	}() );

	/* ---------------------------------------------------------------------
	 * Item actions. Each returns true when it handled the tap; otherwise the
	 * link's href is followed as a fallback.
	 * ------------------------------------------------------------------- */

	const actions = {
		sheet( link ) {
			return Sheets.open( link.getAttribute( 'aria-controls' ), link );
		},

		/** Studiare's off-canvas mini cart when present, else our cart sheet. */
		cart( link ) {
			const themeCart = document.querySelector( '.sc-cart-offcanvas' );
			if ( themeCart ) {
				afterClick( () => themeCart.classList.add( 'active' ) );
				return true;
			}

			return actions.sheet( link );
		},

		'theme-menu'() {
			if ( ! document.querySelector( '.off-canvas-navigation' ) ) {
				return false;
			}

			afterClick( () => document.body.classList.toggle( 'off-canvas-open' ) );
			return true;
		},

		'login-modal'() {
			const opener = document.querySelector( '.register-modal-opener' );
			if ( ! opener ) {
				return false;
			}

			afterClick( () => opener.click() );
			return true;
		},

		selector( link ) {
			let target = null;
			try {
				target = document.querySelector( link.getAttribute( 'data-stx-selector' ) || '' );
			} catch ( error ) {
				return false; // Invalid selector: fall back to the link URL, if any.
			}

			if ( ! target ) {
				return false;
			}

			afterClick( () => target.click() );
			return true;
		},

		top() {
			window.scrollTo( { top: 0, behavior: reducedMotion.matches ? 'auto' : 'smooth' } );
			return true;
		},

		dark() {
			toggleDarkMode();
			return true;
		},
	};

	function onNavClick( event ) {
		const link = event.target.closest( '.stx-bn__link' );
		if ( ! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
			return;
		}

		vibrate();

		const item = link.closest( '.stx-bn__item' );
		const action = actions[ link.getAttribute( 'data-stx-action' ) ];

		if ( action ) {
			if ( action( link, item ) ) {
				event.preventDefault();
			}
			return;
		}

		// Plain navigation: reflect the destination immediately for instant feedback.
		if ( link.tagName === 'A' && ! link.target && ! isSamePage( link ) ) {
			setActive( item );
		}
	}

	/* ---------------------------------------------------------------------
	 * Live cart badge
	 * ------------------------------------------------------------------- */

	let lastCartCount = null;

	function syncCartBadge() {
		const badge = nav.querySelector( '.stx-bn-cart-count' );
		if ( ! badge ) {
			return;
		}

		const count = parseInt( badge.getAttribute( 'data-count' ), 10 ) || 0;
		const item = badge.closest( '.stx-bn__item' );

		if ( lastCartCount !== null && count > lastCartCount ) {
			item.classList.remove( 'is-bumped' );
			void item.offsetWidth; // Restart the animation.
			item.classList.add( 'is-bumped' );
		}

		lastCartCount = count;
	}

	function watchCart() {
		syncCartBadge();

		// WooCommerce triggers its cart events through jQuery.
		if ( window.jQuery ) {
			window.jQuery( document.body ).on(
				'wc_fragments_loaded wc_fragments_refreshed added_to_cart removed_from_cart wc_cart_emptied',
				() => window.requestAnimationFrame( syncCartBadge )
			);
		}

		nav.addEventListener( 'animationend', ( event ) => {
			const item = event.target.closest( '.stx-bn__item' );
			if ( item ) {
				item.classList.remove( 'is-bumped' );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Hide on scroll
	 * ------------------------------------------------------------------- */

	function watchScroll() {
		let lastY = window.scrollY;
		let ticking = false;

		function update() {
			const y = window.scrollY;
			const delta = y - lastY;
			const atBottom = window.innerHeight + y >= document.documentElement.scrollHeight - 8;

			if ( Math.abs( delta ) > 6 || atBottom ) {
				const hide = delta > 0 && y > 120 && ! atBottom && ! Sheets.isOpen();
				nav.classList.toggle( 'is-hidden', hide );
				document.body.classList.toggle( 'stx-bn-is-hidden', hide );
				lastY = y;
			}

			ticking = false;
		}

		window.addEventListener( 'scroll', () => {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}, { passive: true } );
	}

	/* ---------------------------------------------------------------------
	 * Menus inside sheets: add accessible sub-menu toggles
	 * ------------------------------------------------------------------- */

	function enhanceMenus() {
		document.querySelectorAll( '.stx-sheet .stx-menu .menu-item-has-children' ).forEach( ( li ) => {
			const submenu = li.querySelector( ':scope > .sub-menu' );
			if ( ! submenu ) {
				return;
			}

			const isOpen = li.classList.contains( 'current-menu-ancestor' );
			const toggle = document.createElement( 'button' );
			toggle.type = 'button';
			toggle.className = 'stx-menu__toggle';
			toggle.setAttribute( 'aria-expanded', String( isOpen ) );
			toggle.setAttribute( 'aria-label', config.i18n.toggleSubmenu || '' );
			toggle.innerHTML = '<span aria-hidden="true"></span>';

			li.classList.toggle( 'is-open', isOpen );
			li.insertBefore( toggle, submenu );

			toggle.addEventListener( 'click', () => {
				const open = li.classList.toggle( 'is-open' );
				toggle.setAttribute( 'aria-expanded', String( open ) );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------- */

	const initialActive = nav.querySelector( '.stx-bn__item.is-active' );

	nav.addEventListener( 'click', onNavClick );
	Sheets.init();
	LiveSearch.init();
	enhanceMenus();
	watchCart();

	if ( config.hideOnScroll ) {
		watchScroll();
	}

	// Restoring from the back/forward cache: undo the optimistic active state.
	window.addEventListener( 'pageshow', ( event ) => {
		if ( event.persisted ) {
			Sheets.close( { restoreFocus: false, fromHistory: true } );
			setActive( initialActive );
		}
	} );
}() );
