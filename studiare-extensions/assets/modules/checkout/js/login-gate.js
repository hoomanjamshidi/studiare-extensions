/**
 * Studiare Extensions — login before checkout (popup mode, cart page, guests).
 *
 * The server keeps guests off the checkout page and sends them here with
 * `?stx-login=1`; this script opens Studiare's login popup then, and also
 * when a checkout link is tapped, so that round trip is skipped. Without the
 * popup on the page (the theme's header button is not set to the account)
 * it goes to the account page. The cookie tells the server to continue to
 * the checkout after the login.
 */
( function () {
	'use strict';

	/** Lets the tap listener through WP Rocket's "Delay JavaScript" (see checkout.js). */
	const UNDELAYED = { isRocket: true };

	const config = window.stxCheckoutLogin;
	const doc = document;
	if ( ! config ) {
		return;
	}

	/**
	 * Page address without trailing slash, query or hash; plain permalinks
	 * keep `page_id`.
	 *
	 * @param {string} href Address.
	 */
	function pageKey( href ) {
		const url = new URL( href, window.location.href );
		const pageId = url.searchParams.get( 'page_id' );

		return url.origin + url.pathname.replace( /\/+$/, '' ) + ( pageId ? '?page_id=' + pageId : '' );
	}

	const checkoutKey = pageKey( config.checkoutUrl );

	function remember() {
		const secure = 'https:' === window.location.protocol ? '; Secure' : '';
		doc.cookie = config.cookie + '=1; path=' + config.path + '; max-age=' + config.lifetime + '; SameSite=Lax' + secure;
	}

	/** Studiare's popup needs its opener and the container it loads the form into. */
	function popupOpener() {
		const opener = doc.querySelector( '.register-modal-opener' );

		return opener && doc.getElementById( 'ajax-login-container' ) ? opener : null;
	}

	function openLogin() {
		remember();
		// Studiare's opener toggles the popup: clicking it while the popup is
		// open (opened by a tap or the header button) closes it mid-login.
		if ( doc.body.classList.contains( 'modal-login-open' ) ) {
			return;
		}
		const opener = popupOpener();
		if ( opener ) {
			opener.click();
		} else {
			window.location.href = config.accountUrl;
		}
	}

	/**
	 * Runs `callback( jQuery )` now, or as soon as jQuery arrives: cache
	 * plugins may load it later.
	 *
	 * @param {Function} callback Callback.
	 */
	function withJQuery( callback ) {
		if ( window.jQuery ) {
			callback( window.jQuery );
			return;
		}
		const events = [ 'load', 'rocket-allScriptsLoaded', 'DOMContentLiteSpeedLoaded' ];
		const retry = () => {
			if ( ! window.jQuery ) {
				return;
			}
			events.forEach( ( name ) => window.removeEventListener( name, retry ) );
			callback( window.jQuery );
		};
		events.forEach( ( name ) => window.addEventListener( name, retry ) );
	}

	doc.addEventListener(
		'click',
		( event ) => {
			if ( event.defaultPrevented || 0 !== event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
				return;
			}
			const link = event.target instanceof Element ? event.target.closest( 'a[href]' ) : null;
			if ( ! link || pageKey( link.href ) !== checkoutKey ) {
				return;
			}
			event.preventDefault();
			openLogin();
		},
		Object.assign( { capture: true }, UNDELAYED )
	);

	const url = new URL( window.location.href );
	if ( ! url.searchParams.has( config.queryVar ) ) {
		return;
	}

	// A reload or a shared link should not open the popup again.
	url.searchParams.delete( config.queryVar );
	window.history.replaceState( window.history.state, '', url.href );

	// Studiare binds the opener in a jQuery ready handler of its footer
	// script, which jQuery runs before this one's. Not on `load`: on a slow
	// site that came many seconds later, after the visitor had opened the
	// popup and started typing, and the extra click closed it.
	withJQuery( ( $ ) => $( openLogin ) );
}() );
