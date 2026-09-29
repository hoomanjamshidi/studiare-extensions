/**
 * Studiare Extensions — home page widgets.
 *
 * Everything here enhances markup that already works on its own: slides
 * swipe natively, category buttons are links, the countdown starts with the
 * right numbers and the newsletter form posts normally.
 */
( function () {
	'use strict';

	const config = window.stxHome || {};
	const i18n = config.i18n || {};
	const doc = document;
	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const isEditor = () => doc.body && doc.body.classList.contains( 'elementor-editor-active' );

	const PERSIAN = '۰۱۲۳۴۵۶۷۸۹';
	const digits = ( text ) => ( config.persianDigits ? String( text ).replace( /\d/g, ( d ) => PERSIAN[ d ] ) : String( text ) );

	/* ---------------------------------------------------------------------
	 * Slides: dots and autoplay on top of the scroll-snap track
	 * ------------------------------------------------------------------- */

	function initSlides( root ) {
		const track = root.querySelector( '.stx-slides__track' );
		const slides = Array.from( track.children );
		const dots = Array.from( root.querySelectorAll( '.stx-slides__dot' ) );
		const interval = parseInt( root.dataset.interval, 10 ) || 0;
		let current = 0;
		let timer = null;
		let paused = false;

		if ( slides.length < 2 ) {
			return;
		}

		// Works in both directions: in RTL scrollLeft runs from 0 to negative values.
		const offsetOf = ( index ) => slides[ index ].offsetLeft - slides[ 0 ].offsetLeft;

		function mark( index ) {
			current = index;
			dots.forEach( ( dot, i ) => {
				dot.classList.toggle( 'is-active', i === index );
				if ( i === index ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );
		}

		function go( index ) {
			const target = ( index + slides.length ) % slides.length;
			// Scrolls the track only, never the page, so autoplay cannot move the visitor.
			track.scrollTo( { left: offsetOf( target ), behavior: reducedMotion.matches ? 'auto' : 'smooth' } );
			mark( target );
		}

		let settle = null;
		track.addEventListener( 'scroll', () => {
			window.clearTimeout( settle );
			settle = window.setTimeout( () => {
				const index = Math.round( Math.abs( track.scrollLeft ) / track.clientWidth );
				if ( index !== current && slides[ index ] ) {
					mark( index );
				}
			}, 80 );
		}, { passive: true } );

		dots.forEach( ( dot, index ) => dot.addEventListener( 'click', () => {
			go( index );
			restart();
		} ) );

		function stop() {
			window.clearInterval( timer );
			timer = null;
		}

		function restart() {
			stop();
			// No autoplay for reduced motion, in the editor, or while the visitor holds the slider.
			if ( interval && ! paused && ! reducedMotion.matches && ! isEditor() && ! doc.hidden ) {
				timer = window.setInterval( () => go( current + 1 ), interval );
			}
		}

		const hold = ( state ) => () => {
			paused = state;
			restart();
		};

		root.addEventListener( 'pointerenter', hold( true ) );
		root.addEventListener( 'pointerleave', hold( false ) );
		root.addEventListener( 'focusin', hold( true ) );
		root.addEventListener( 'focusout', ( event ) => {
			if ( ! root.contains( event.relatedTarget ) ) {
				hold( false )();
			}
		} );
		track.addEventListener( 'touchstart', hold( true ), { passive: true } );
		doc.addEventListener( 'visibilitychange', restart );

		restart();
	}

	/* ---------------------------------------------------------------------
	 * Product grid: category buttons switch panels in place
	 * ------------------------------------------------------------------- */

	function initFilter( root ) {
		const chips = Array.from( root.querySelectorAll( '[data-stx-panel]' ) );

		chips.forEach( ( chip ) => chip.addEventListener( 'click', ( event ) => {
			const panel = doc.getElementById( chip.dataset.stxPanel );
			if ( ! panel ) {
				return; // Follow the link to the category page.
			}

			event.preventDefault();
			chips.forEach( ( other ) => {
				const active = other === chip;
				other.classList.toggle( 'is-active', active );
				if ( active ) {
					other.setAttribute( 'aria-current', 'true' );
				} else {
					other.removeAttribute( 'aria-current' );
				}
				const target = doc.getElementById( other.dataset.stxPanel );
				if ( target ) {
					target.hidden = ! active;
				}
			} );
		} ) );
	}

	/* ---------------------------------------------------------------------
	 * Slide rows: arrow buttons and mouse dragging on the scroll-snap track
	 * ------------------------------------------------------------------- */

	function initRail( root ) {
		const track = root.querySelector( '.stx-rail__track' );
		const arrows = Array.from( root.querySelectorAll( '.stx-rail__arrow' ) );
		const rtl = window.getComputedStyle( track ).direction === 'rtl';
		let frame = 0;

		function update() {
			frame = 0;
			// In RTL scrollLeft runs from 0 to negative values.
			const position = Math.abs( track.scrollLeft );
			const max = track.scrollWidth - track.clientWidth;
			const hadFocus = arrows.find( ( arrow ) => arrow === doc.activeElement );

			arrows.forEach( ( arrow ) => {
				arrow.hidden = max <= 1;
				arrow.disabled = arrow.dataset.stxStep === '1' ? position >= max - 1 : position <= 1;
			} );

			// A button that just reached the end cannot keep focus; hand it to the other one.
			if ( hadFocus && hadFocus.disabled ) {
				const other = arrows.find( ( arrow ) => ! arrow.disabled && ! arrow.hidden );
				if ( other ) {
					other.focus();
				}
			}
		}

		const schedule = () => {
			if ( ! frame ) {
				frame = window.requestAnimationFrame( update );
			}
		};

		// Arrows sit level with the middle of the pictures, clear of the card text.
		function measure() {
			const media = track.querySelector( '.stx-hcard__media' );
			if ( media && media.offsetHeight ) {
				const top = media.getBoundingClientRect().top - root.getBoundingClientRect().top;
				root.style.setProperty( '--stx-rail-mid', Math.round( top + ( media.offsetHeight / 2 ) ) + 'px' );
			}
			schedule();
		}

		arrows.forEach( ( arrow ) => arrow.addEventListener( 'click', () => {
			// One view at a time; snapping lines the first card up with the edge.
			const step = parseInt( arrow.dataset.stxStep, 10 ) * track.clientWidth * ( rtl ? -1 : 1 );
			track.scrollBy( { left: step, behavior: reducedMotion.matches ? 'auto' : 'smooth' } );
		} ) );

		track.addEventListener( 'scroll', schedule, { passive: true } );
		// Also fires when a hidden category panel is shown (its size changes from zero).
		if ( window.ResizeObserver ) {
			new window.ResizeObserver( measure ).observe( track );
		} else {
			window.addEventListener( 'resize', measure );
		}
		measure();

		// Touch screens and trackpads scroll natively; a mouse needs dragging.
		let drag = null;
		let dragged = false;

		track.addEventListener( 'pointerdown', ( event ) => {
			if ( event.pointerType === 'mouse' && event.button === 0 ) {
				drag = { x: event.clientX, left: track.scrollLeft, moved: false };
			}
		} );

		track.addEventListener( 'pointermove', ( event ) => {
			if ( ! drag ) {
				return;
			}
			const dx = event.clientX - drag.x;
			if ( ! drag.moved ) {
				// A small wobble is still a click.
				if ( Math.abs( dx ) < 6 ) {
					return;
				}
				drag.moved = true;
				track.classList.add( 'is-dragging' );
				track.setPointerCapture( event.pointerId );
				window.getSelection().removeAllRanges();
			}
			track.scrollLeft = drag.left - dx;
		} );

		const release = () => {
			if ( drag && drag.moved ) {
				track.classList.remove( 'is-dragging' );
				// The click that ends a drag must not open the card under the pointer.
				// It fires right after pointerup, before this timer.
				dragged = true;
				window.setTimeout( () => {
					dragged = false;
				} );
			}
			drag = null;
		};

		track.addEventListener( 'pointerup', release );
		track.addEventListener( 'pointercancel', release );
		track.addEventListener( 'click', ( event ) => {
			if ( dragged ) {
				event.preventDefault();
				event.stopPropagation();
			}
		}, true );
		// Links and pictures would otherwise start the browser's own drag and drop.
		track.addEventListener( 'dragstart', ( event ) => event.preventDefault() );
	}

	/* ---------------------------------------------------------------------
	 * Countdown
	 * ------------------------------------------------------------------- */

	const countdowns = new Set();
	let ticker = null;

	function tick() {
		const now = Date.now();

		countdowns.forEach( ( box ) => {
			if ( ! box.isConnected ) {
				countdowns.delete( box );
				return;
			}

			let end = parseInt( box.dataset.end, 10 );
			const period = parseInt( box.dataset.period, 10 ) || 0;

			// A repeating offer on a cached page moves on to the current period.
			while ( period && end <= now ) {
				end += period;
			}
			box.dataset.end = String( end );

			let left = Math.max( 0, Math.floor( ( end - now ) / 1000 ) );
			const parts = { days: Math.floor( left / 86400 ) };
			left %= 86400;
			parts.hours = Math.floor( left / 3600 );
			parts.minutes = Math.floor( ( left % 3600 ) / 60 );
			parts.seconds = left % 60;

			Object.keys( parts ).forEach( ( unit ) => {
				const cell = box.querySelector( '[data-unit="' + unit + '"]' );
				const text = digits( unit === 'days' ? parts[ unit ] : String( parts[ unit ] ).padStart( 2, '0' ) );
				if ( cell && cell.textContent !== text ) {
					cell.textContent = text;
				}
			} );
		} );

		if ( ! countdowns.size ) {
			window.clearInterval( ticker );
			ticker = null;
		}
	}

	function initCountdown( box ) {
		countdowns.add( box );
		if ( ! ticker ) {
			ticker = window.setInterval( tick, 1000 );
		}
		tick();
	}

	/* ---------------------------------------------------------------------
	 * Key numbers: count up from zero when scrolled into view
	 * ------------------------------------------------------------------- */

	const COUNT_DURATION = 1600;

	/**
	 * Splits "+۳٬۲۰۰ نفر" into the text around the number, its value and how
	 * it is written (digit set, group and decimal separators), so every step
	 * of the count looks like the final number.
	 */
	function parseCount( text ) {
		const match = text.match( /[0-9۰-۹٠-٩]+(?:[.,٫٬][0-9۰-۹٠-٩]+)*/ );
		if ( ! match ) {
			return null;
		}

		const raw = match[ 0 ];
		let zero = 48;
		if ( /[۰-۹]/.test( raw ) ) {
			zero = 0x06f0;
		} else if ( /[٠-٩]/.test( raw ) ) {
			zero = 0x0660;
		}
		const latin = raw.replace( /[۰-۹٠-٩]/g, ( d ) => String( d.charCodeAt( 0 ) - zero ) );
		const fraction = latin.match( /[.٫](\d+)$/ );

		return {
			before: text.slice( 0, match.index ),
			after: text.slice( match.index + raw.length ),
			value: parseFloat( latin.replace( /[,٬]/g, '' ).replace( '٫', '.' ) ),
			decimals: fraction ? fraction[ 1 ].length : 0,
			group: ( raw.match( /[,٬]/ ) || [ '' ] )[ 0 ],
			point: ( raw.match( /[.٫]/ ) || [ '.' ] )[ 0 ],
			zero,
		};
	}

	function formatCount( value, spec ) {
		const [ whole, fraction ] = value.toFixed( spec.decimals ).split( '.' );
		let out = spec.group ? whole.replace( /\B(?=(\d{3})+(?!\d))/g, spec.group ) : whole;
		if ( fraction ) {
			out += spec.point + fraction;
		}
		if ( spec.zero !== 48 ) {
			out = out.replace( /\d/g, ( d ) => String.fromCharCode( spec.zero + Number( d ) ) );
		}
		return spec.before + out + spec.after;
	}

	function initCount( number ) {
		const final = number.textContent;
		const spec = parseCount( final );
		if ( ! spec || ! spec.value || reducedMotion.matches || isEditor() || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		// Screen readers get the real number once; the moving one is hidden from them.
		const label = doc.createElement( 'span' );
		label.className = 'screen-reader-text';
		label.textContent = final;
		number.after( label );
		number.setAttribute( 'aria-hidden', 'true' );

		// The final number's width is kept while counting, so nothing around it moves.
		number.style.minWidth = Math.ceil( number.getBoundingClientRect().width ) + 'px';
		number.textContent = formatCount( 0, spec );

		const observer = new window.IntersectionObserver( ( entries ) => {
			if ( ! entries.some( ( entry ) => entry.isIntersecting ) ) {
				return;
			}
			observer.disconnect();

			const start = window.performance.now();
			const step = ( now ) => {
				const progress = Math.min( 1, ( now - start ) / COUNT_DURATION );
				// Ease out: fast at first, settling on the final number.
				number.textContent = progress < 1 ? formatCount( spec.value * ( 1 - Math.pow( 1 - progress, 3 ) ), spec ) : final;
				if ( progress < 1 ) {
					window.requestAnimationFrame( step );
				}
			};
			window.requestAnimationFrame( step );
		}, { threshold: 0.6 } );

		observer.observe( number );
	}

	/* ---------------------------------------------------------------------
	 * Newsletter: send in the background, answer in place
	 * ------------------------------------------------------------------- */

	function initNewsletter( form ) {
		const note = form.querySelector( '.stx-news__note' );

		const say = ( message, error ) => {
			note.textContent = message;
			note.classList.toggle( 'is-error', error );
		};

		form.addEventListener( 'submit', async ( event ) => {
			if ( ! config.newsletterUrl || ! window.fetch ) {
				return;
			}

			event.preventDefault();
			if ( form.classList.contains( 'is-busy' ) ) {
				return;
			}
			form.classList.add( 'is-busy' );
			say( '', false );

			let status = 'error';
			try {
				const response = await fetch( config.newsletterUrl, { method: 'POST', body: new FormData( form ), credentials: 'same-origin' } );
				const json = await response.json();
				status = ( json && json.data && json.data.status ) || ( json && json.success ? 'ok' : 'error' );
			} catch ( error ) {
				status = 'error';
			}

			form.classList.remove( 'is-busy' );

			if ( status === 'ok' ) {
				say( form.dataset.success || i18n.ok || '', false );
				form.reset();
			} else {
				say( i18n[ status ] || i18n.error || '', true );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Boot (the page, and each widget the Elementor editor re-renders)
	 * ------------------------------------------------------------------- */

	function initScope( scope ) {
		const once = ( selector, init ) => {
			scope.querySelectorAll( selector ).forEach( ( element ) => {
				if ( ! element.hasAttribute( 'data-stx-ready' ) ) {
					element.setAttribute( 'data-stx-ready', '' );
					init( element );
				}
			} );
		};

		once( '[data-stx-slides]', initSlides );
		once( '[data-stx-filter]', initFilter );
		once( '[data-stx-rail]', initRail );
		once( '[data-stx-countdown]', initCountdown );
		once( '[data-stx-count]', initCount );
		once( '[data-stx-newsletter]', initNewsletter );
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
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', ( $scope ) => {
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
