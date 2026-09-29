/**
 * Studiare Extensions — Slider widget.
 *
 * Enhances markup that already works on its own: slides sit in a scroll-snap
 * track that swipes natively, links are real links and the first picture is
 * in the HTML. This adds arrows, dots and tabs, autoplay, the fade effect,
 * dragging with a mouse and background loading of the next pictures. No dependencies (no jQuery or
 * carousel library), and it reads layout only when a slide changes, so it
 * adds no layout shift and almost no main-thread work.
 *
 * It boots itself (rather than sharing home.js) so a page whose only
 * Studiare+ widget is a slider loads just this file.
 */
( function () {
	'use strict';

	const doc = document;
	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const isEditor = () => doc.body && doc.body.classList.contains( 'elementor-editor-active' );

	/** Horizontal distance (px) that counts as a swipe (fade) or a flick to the next slide (mouse drag). */
	const SWIPE = 40;

	/** Mouse movement (px) before a press becomes a drag, so a slightly shaky click still follows the link. */
	const DRAG = 6;

	/** Longest fade (ms), in case `animationend` never comes (hidden tab). */
	const FADE_MAX = 1200;

	/**
	 * Runs once the page has loaded and the browser is idle, so pictures
	 * fetched ahead of time never compete with the first paint.
	 *
	 * @param {Function} callback Callback.
	 */
	function afterLoad( callback ) {
		const idle = () => ( window.requestIdleCallback ? window.requestIdleCallback( callback, { timeout: 3000 } ) : window.setTimeout( callback, 300 ) );

		if ( doc.readyState === 'complete' ) {
			idle();
		} else {
			window.addEventListener( 'load', idle, { once: true } );
		}
	}

	function init( root ) {
		const main = root.querySelector( '.stx-sl__main' );
		const viewport = root.querySelector( '.stx-sl__viewport' );
		const track = root.querySelector( '.stx-sl__track' );
		const slides = Array.from( track.children );
		const picks = Array.from( root.querySelectorAll( '[data-stx-go]' ) );
		const pauseButton = root.querySelector( '.stx-sl__pause' );
		const interval = parseInt( root.dataset.interval, 10 ) || 0;
		const fade = root.dataset.effect === 'fade';
		const centred = root.classList.contains( 'stx-sl--peek' );
		const rtl = window.getComputedStyle( root ).direction === 'rtl';

		// Reasons autoplay is on hold: hover, focus, touch, scroll, offscreen, hidden, stopped, editor.
		const holds = new Set();
		let current = 0;
		let timer = 0;
		let running = false;
		let deadline = 0;
		let remaining = interval;
		let ready = false;
		let seen = false;
		let engaged = false;
		let ownScroll = false;
		let dragging = false;
		let settleTimer = 0;

		if ( slides.length < 2 ) {
			return;
		}

		/* -- Positions (slide effect) ------------------------------------ */

		// Scroll distance that aligns a slide the way CSS snaps it (start, or centre in "peek").
		function offset( slide ) {
			const box = track.getBoundingClientRect();
			const rect = slide.getBoundingClientRect();

			if ( centred ) {
				return rect.left + ( rect.width / 2 ) - ( box.left + ( box.width / 2 ) );
			}

			return rtl ? rect.right - box.right : rect.left - box.left;
		}

		function nearest() {
			let best = 0;
			let min = Infinity;
			slides.forEach( ( slide, index ) => {
				const distance = Math.abs( offset( slide ) );
				if ( distance < min ) {
					min = distance;
					best = index;
				}
			} );

			return best;
		}

		// In RTL scrollLeft runs from 0 to negative values, hence Math.abs.
		function atEdge( end ) {
			const position = Math.abs( track.scrollLeft );

			return end ? position >= track.scrollWidth - track.clientWidth - 2 : position <= 2;
		}

		/* -- Changing slides --------------------------------------------- */

		function mark( index ) {
			current = index;
			slides.forEach( ( slide, i ) => slide.classList.toggle( 'is-active', i === index ) );
			picks.forEach( ( pick ) => {
				const active = parseInt( pick.dataset.stxGo, 10 ) === index;
				pick.classList.toggle( 'is-active', active );
				if ( active ) {
					pick.setAttribute( 'aria-current', 'true' );
					reveal( pick );
				} else {
					pick.removeAttribute( 'aria-current' );
				}
			} );
			remaining = interval;
			warm();
			schedule();
		}

		// Keeps the current tab in view when the tabs scroll sideways (phones), without moving the page.
		function reveal( pick ) {
			const bar = pick.parentElement;
			if ( bar.scrollWidth <= bar.clientWidth ) {
				return;
			}
			const box = bar.getBoundingClientRect();
			const rect = pick.getBoundingClientRect();
			bar.scrollBy( { left: rect.left + ( rect.width / 2 ) - ( box.left + ( box.width / 2 ) ), behavior: reducedMotion.matches ? 'instant' : 'smooth' } );
		}

		function crossfade( from, to ) {
			if ( from === to ) {
				return;
			}
			slides.forEach( ( slide ) => slide.classList.remove( 'is-leaving' ) );
			from.classList.add( 'is-leaving' );
			to.classList.add( 'is-entering' );

			const finish = () => {
				from.classList.remove( 'is-leaving' );
				to.classList.remove( 'is-entering' );
			};
			if ( reducedMotion.matches ) {
				finish();
				return;
			}
			to.addEventListener( 'animationend', ( event ) => {
				if ( event.target === to ) {
					finish();
				}
			} );
			window.setTimeout( finish, FADE_MAX );
		}

		function go( index ) {
			const target = ( index + slides.length ) % slides.length;

			if ( fade ) {
				crossfade( slides[ current ], slides[ target ] );
			} else {
				// Scrolls the track only, never the page, so autoplay cannot move the visitor.
				const distance = offset( slides[ target ] );
				if ( Math.abs( distance ) > 1 ) {
					ownScroll = true;
					root.classList.add( 'is-jumping' );
					track.scrollBy( { left: distance, behavior: reducedMotion.matches ? 'instant' : 'smooth' } );
				}
			}
			mark( target );
		}

		// At either end of a scrolling track (several cards in view), wrap around.
		const next = () => go( ! fade && atEdge( true ) ? 0 : current + 1 );
		const prev = () => go( ! fade && atEdge( false ) ? slides.length - 1 : current - 1 );

		/* -- Autoplay ---------------------------------------------------- */

		function schedule() {
			window.clearTimeout( timer );
			running = interval > 0 && holds.size === 0;
			root.classList.toggle( 'is-paused', ! running );
			if ( running ) {
				deadline = Date.now() + remaining;
				timer = window.setTimeout( next, remaining );
			}
		}

		// Pausing keeps the time left, so the progress bar and the timer stay in step.
		function hold( reason, on ) {
			if ( on === holds.has( reason ) ) {
				return;
			}
			if ( on ) {
				if ( running ) {
					remaining = Math.max( 0, deadline - Date.now() );
				}
				holds.add( reason );
			} else {
				holds.delete( reason );
			}
			schedule();
		}

		function setStopped( stopped ) {
			hold( 'stopped', stopped );
			root.classList.toggle( 'is-stopped', stopped );
			// Announce slide changes only while they are not automatic.
			track.setAttribute( 'aria-live', stopped || ! interval ? 'polite' : 'off' );
			if ( pauseButton ) {
				pauseButton.setAttribute( 'aria-label', stopped ? pauseButton.dataset.labelPlay : pauseButton.dataset.labelPause );
			}
		}

		/* -- Pictures ahead of time ---------------------------------------- */

		// Pictures of slides that start off screen are not rendered (.is-later in
		// slider.css), so they never compete with the first picture. This shows
		// a slide's pictures and starts loading them.
		function prepare( index ) {
			const slide = slides[ ( index + slides.length ) % slides.length ];
			slide.classList.remove( 'is-later' );
			slide.querySelectorAll( 'img[loading="lazy"]' ).forEach( ( img ) => {
				img.loading = 'eager';
			} );
		}

		// Once the page has loaded (or the visitor reaches for the slider) and the
		// slider is on screen: the slides in view and the next one, plus the
		// previous one after the visitor has used it. One slide ahead, never all.
		function warm() {
			if ( ! ready || ! seen ) {
				return;
			}
			// In "peek" the slide after next already shows at the edge.
			const inView = fade ? 1 : Math.max( 1, Math.round( track.clientWidth / slides[ 0 ].offsetWidth ) ) + ( centred ? 1 : 0 );
			for ( let i = current - ( engaged ? 1 : 0 ); i <= current + inView; i++ ) {
				prepare( i );
			}
		}

		function engage() {
			ready = true;
			seen = true;
			engaged = true;
			warm();
		}

		/* -- Swiping and dragging (slide effect) ---------------------------- */

		// The slide the visitor swiped or dragged to becomes current once scrolling settles.
		function settled() {
			if ( dragging ) {
				// The mouse is still down; releasing it settles.
				return;
			}
			ownScroll = false;
			root.classList.remove( 'is-jumping', 'is-dragging' );
			const index = nearest();
			if ( index !== current ) {
				mark( index );
			}
			hold( 'scroll', false );
		}

		function settleSoon() {
			window.clearTimeout( settleTimer );
			settleTimer = window.setTimeout( settled, 90 );
		}

		// Touch and trackpads scroll the track natively; a mouse cannot, so it
		// drags the track here. Snapping is off from the first move until the
		// glide to the chosen slide ends (.is-dragging), otherwise the browser
		// would re-snap under the pointer and then jump.
		function dragWithMouse() {
			let drag = null;
			let dragged = false;

			track.addEventListener( 'pointerdown', ( event ) => {
				dragged = false;
				drag = null;
				if ( event.pointerType !== 'mouse' || event.button !== 0 || track.scrollWidth <= track.clientWidth + 1 ) {
					return;
				}
				drag = { id: event.pointerId, x: event.clientX, dx: 0, scroll: track.scrollLeft, from: current };
			} );

			track.addEventListener( 'pointermove', ( event ) => {
				if ( ! drag || event.pointerId !== drag.id ) {
					return;
				}
				drag.dx = event.clientX - drag.x;
				if ( ! dragging ) {
					if ( Math.abs( drag.dx ) < DRAG ) {
						return;
					}
					dragging = true;
					// Captured only now: capturing on press would retarget a plain click away from the link.
					track.setPointerCapture( event.pointerId );
					root.classList.add( 'is-dragging' );
					doc.getSelection().removeAllRanges();
				}
				track.scrollLeft = drag.scroll - drag.dx;
			} );

			const release = ( event ) => {
				if ( ! drag || event.pointerId !== drag.id ) {
					return;
				}
				const { dx, from } = drag;
				drag = null;
				if ( ! dragging ) {
					return;
				}
				dragging = false;
				dragged = true;

				// A short flick still moves one slide; a long drag lands on the nearest one. No wrapping.
				let target = nearest();
				if ( target === from && Math.abs( dx ) >= SWIPE ) {
					// Content moves with the pointer: towards the start reveals the next slide.
					target += ( dx < 0 ) !== rtl ? 1 : -1;
				}
				go( Math.min( slides.length - 1, Math.max( 0, target ) ) );
				settleSoon();
			};
			track.addEventListener( 'pointerup', release );
			track.addEventListener( 'pointercancel', release );

			// A drag that ends on a link must not follow it.
			track.addEventListener( 'click', ( event ) => {
				if ( dragged ) {
					event.preventDefault();
					event.stopPropagation();
					dragged = false;
				}
			}, true );
		}

		/* -- Wiring ------------------------------------------------------ */

		if ( fade ) {
			// The slide the visitor may have swiped to before this script ran.
			current = nearest();
			root.classList.add( 'is-fade' );
		}
		root.classList.add( 'is-ready' );
		if ( interval ) {
			root.classList.add( 'is-autoplay' );
		}

		root.querySelectorAll( '[data-stx-step]' ).forEach( ( arrow ) => arrow.addEventListener( 'click', () => {
			if ( arrow.dataset.stxStep === '1' ) {
				next();
			} else {
				prev();
			}
		} ) );

		picks.forEach( ( pick ) => pick.addEventListener( 'click', () => {
			const index = parseInt( pick.dataset.stxGo, 10 );
			if ( index !== current ) {
				go( index );
			}
		} ) );

		if ( pauseButton ) {
			pauseButton.addEventListener( 'click', () => setStopped( ! holds.has( 'stopped' ) ) );
		}

		main.addEventListener( 'keydown', ( event ) => {
			if ( event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' ) {
				return;
			}
			event.preventDefault();
			if ( ( event.key === 'ArrowRight' ) !== rtl ) {
				next();
			} else {
				prev();
			}
		} );

		if ( fade ) {
			let start = null;
			let swiped = false;

			viewport.addEventListener( 'pointerdown', ( event ) => {
				start = event.isPrimary ? { x: event.clientX, y: event.clientY } : null;
				swiped = false;
			} );
			viewport.addEventListener( 'pointerup', ( event ) => {
				if ( ! start ) {
					return;
				}
				const dx = event.clientX - start.x;
				const dy = event.clientY - start.y;
				start = null;
				if ( Math.abs( dx ) < SWIPE || Math.abs( dx ) < Math.abs( dy ) ) {
					return;
				}
				swiped = true;
				// Content moves with the finger: towards the start reveals the next slide.
				if ( ( dx < 0 ) !== rtl ) {
					next();
				} else {
					prev();
				}
			} );
			viewport.addEventListener( 'pointercancel', () => {
				start = null;
			} );
			// A swipe that ends on a link must not follow it.
			viewport.addEventListener( 'click', ( event ) => {
				if ( swiped ) {
					event.preventDefault();
					swiped = false;
				}
			}, true );
		} else {
			track.addEventListener( 'scroll', () => {
				if ( ! ownScroll ) {
					hold( 'scroll', true );
				}
				settleSoon();
			}, { passive: true } );
			dragWithMouse();
		}

		// A mouse swipe or drag over a link would start the browser's own link
		// drag, which cancels the pointer events.
		viewport.addEventListener( 'dragstart', ( event ) => event.preventDefault() );

		// No autoplay while the visitor points at, touches or keyboard-focuses the slider.
		main.addEventListener( 'pointerenter', ( event ) => hold( 'hover', event.pointerType === 'mouse' ) );
		main.addEventListener( 'pointerleave', () => hold( 'hover', false ) );
		track.addEventListener( 'touchstart', () => hold( 'touch', true ), { passive: true } );
		track.addEventListener( 'touchend', () => hold( 'touch', false ), { passive: true } );
		main.addEventListener( 'focusin', ( event ) => {
			if ( event.target !== pauseButton && event.target.matches( ':focus-visible' ) ) {
				hold( 'focus', true );
			}
		} );
		main.addEventListener( 'focusout', ( event ) => {
			if ( ! main.contains( event.relatedTarget ) ) {
				hold( 'focus', false );
			}
		} );

		// Visitors who touch or point at the slider may flip through it: have the neighbours ready.
		[ 'pointerenter', 'touchstart', 'focusin' ].forEach( ( type ) => main.addEventListener( type, engage, { once: true, passive: true } ) );

		const onVisibility = () => hold( 'hidden', doc.hidden );
		doc.addEventListener( 'visibilitychange', onVisibility );
		onVisibility();

		if ( isEditor() ) {
			hold( 'editor', true );
		}

		if ( 'IntersectionObserver' in window ) {
			hold( 'offscreen', true );
			const observer = new window.IntersectionObserver( ( entries ) => {
				if ( ! root.isConnected ) {
					observer.disconnect();
					doc.removeEventListener( 'visibilitychange', onVisibility );
					return;
				}
				const visible = entries[ entries.length - 1 ].isIntersecting;
				hold( 'offscreen', ! visible );
				if ( visible && ! seen ) {
					seen = true;
					warm();
				}
			}, { threshold: 0.35 } );
			observer.observe( root );
		} else {
			seen = true;
		}

		// Autoplay fetches the next picture once the page has loaded; other
		// sliders wait for the visitor (above), so they add nothing to page weight.
		if ( interval ) {
			afterLoad( () => {
				ready = true;
				warm();
			} );
		}

		// Reduced motion: start paused; the visitor can still press play.
		setStopped( interval > 0 && reducedMotion.matches );
		if ( fade ) {
			mark( current );
		} else {
			schedule();
		}
	}

	/* ---------------------------------------------------------------------
	 * Boot (the page, and each widget the Elementor editor re-renders)
	 * ------------------------------------------------------------------- */

	function initScope( scope ) {
		scope.querySelectorAll( '[data-stx-slider]' ).forEach( ( root ) => {
			if ( ! root.hasAttribute( 'data-stx-ready' ) ) {
				root.setAttribute( 'data-stx-ready', '' );
				init( root );
			}
		} );
	}

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', () => initScope( doc ) );
	} else {
		initScope( doc );
	}

	let hooked = false;
	function hookElementor() {
		if ( hooked || ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}
		hooked = true;
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/stx-slider.default', ( $scope ) => {
			if ( $scope && $scope[ 0 ] ) {
				initScope( $scope[ 0 ] );
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
