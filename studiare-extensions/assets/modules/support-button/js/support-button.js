/**
 * Studiare Extensions — floating support button.
 *
 * The menu is a native <details>, so it opens and its links work without
 * this script. The script adds what <details> lacks: closing on outside
 * taps and Esc, a closing animation, closing once a channel opens, and the
 * timed greeting bubble (shown once per browser session).
 */
( function () {
	'use strict';

	const root = document.querySelector( '[data-stx-sb]' );
	if ( ! root ) {
		return;
	}

	const menu = root.querySelector( '.stx-sb__menu' );
	const toggle = root.querySelector( '.stx-sb__toggle' );
	const greeting = root.querySelector( '.stx-sb__greeting' );
	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const GREETING_KEY = 'stxSupportGreeting';

	/* ---------------------------------------------------------------------
	 * Menu
	 * ------------------------------------------------------------------- */

	/**
	 * Plays the closing animation, then closes the <details>.
	 *
	 * @param {boolean} restoreFocus Move focus back to the button (Esc).
	 */
	function closeMenu( restoreFocus ) {
		if ( ! menu.open || root.classList.contains( 'is-closing' ) ) {
			return;
		}

		if ( restoreFocus ) {
			toggle.focus();
		}

		if ( reducedMotion.matches ) {
			menu.open = false;
			return;
		}

		const panel = menu.querySelector( '.stx-sb__panel' );
		let done = false;
		const finish = () => {
			if ( ! done ) {
				done = true;
				root.classList.remove( 'is-closing' );
				menu.open = false;
			}
		};

		root.classList.add( 'is-closing' );
		panel.addEventListener( 'animationend', finish, { once: true } );
		// Safety net in case the animation never runs (e.g. the panel is hidden).
		window.setTimeout( finish, 300 );
	}

	function initMenu() {
		// Clicking the button of an open menu animates the close instead of snapping shut.
		toggle.addEventListener( 'click', ( event ) => {
			if ( menu.open ) {
				event.preventDefault();
				closeMenu( false );
			}
		} );

		menu.addEventListener( 'toggle', () => {
			if ( menu.open ) {
				hideGreeting( true );
			}
		} );

		document.addEventListener( 'click', ( event ) => {
			if ( menu.open && ! root.contains( event.target ) ) {
				closeMenu( false );
			}
		} );

		document.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' && menu.open ) {
				closeMenu( root.contains( document.activeElement ) );
			}
		} );

		// Messenger links open another app or tab; tidy up behind them.
		menu.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( '.stx-sb__channel' ) ) {
				window.setTimeout( () => closeMenu( false ), 150 );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Greeting
	 * ------------------------------------------------------------------- */

	function greetingDismissed() {
		try {
			return window.sessionStorage.getItem( GREETING_KEY ) === '1';
		} catch ( error ) {
			return false;
		}
	}

	/**
	 * @param {boolean} remember Keep it hidden for the rest of the session.
	 */
	function hideGreeting( remember ) {
		if ( ! greeting ) {
			return;
		}

		greeting.hidden = true;

		if ( remember ) {
			try {
				window.sessionStorage.setItem( GREETING_KEY, '1' );
			} catch ( error ) {
				// Without storage the greeting may simply show again on the next page.
			}
		}
	}

	function initGreeting() {
		if ( ! greeting || greetingDismissed() ) {
			return;
		}

		const delay = Math.max( 0, parseInt( root.dataset.greetingDelay, 10 ) || 0 ) * 1000;

		window.setTimeout( () => {
			if ( ! menu || ! menu.open ) {
				greeting.hidden = false;
			}
		}, delay );

		greeting.querySelector( '[data-stx-sb-dismiss]' ).addEventListener( 'click', () => hideGreeting( true ) );

		greeting.querySelector( '[data-stx-sb-greeting]' ).addEventListener( 'click', () => {
			hideGreeting( true );
			if ( menu ) {
				menu.open = true;
				toggle.focus();
			} else {
				toggle.click(); // A single channel: the button is the link.
			}
		} );
	}

	if ( menu ) {
		initMenu();
	}
	initGreeting();
}() );
