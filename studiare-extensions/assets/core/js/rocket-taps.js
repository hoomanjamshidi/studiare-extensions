/**
 * Studiare Extensions — first taps under WP Rocket's "Delay JavaScript".
 *
 * Printed inline in <head> by Core\Cache_Compat, only while WP Rocket's Delay
 * JavaScript is on. Until the visitor's first interaction has loaded the
 * delayed scripts, Rocket's own window listener cancels every click (so links
 * and <summary> do nothing) and keeps it to replay later. The plugin's
 * scripts handle their taps at once (their listeners pass `isRocket`), so a
 * replay would repeat the action: a sheet that just opened would close, a
 * track that started would pause. Taps inside the plugin's own components
 * therefore stop at `document` while Rocket is waiting; the plugin's
 * listeners, and every other listener on `document`, still see them.
 */
( function () {
	'use strict';

	/**
	 * Components whose taps the plugin's scripts handle themselves, and the
	 * thank-you page, whose plain links should open on the first tap.
	 */
	const OURS = '.stx-bn, .stx-sheet, [data-stx-sb], [data-stx-ap], [data-stx-pod], [data-stx-pdf], .stx-drawer, .stx-overlay, .stx-lightbox, .stx-tocbar, .stx-buybar, .stx-thanks, [class*="elementor-widget-stx-"]';

	/** WooCommerce and Studiare controls inside them; their scripts get Rocket's replay. */
	const THEIRS = '.ajax_add_to_cart, .remove_from_cart_button, .register-modal-opener, form.cart';

	let finished = false;

	window.addEventListener( 'rocket-allScriptsLoaded', () => {
		// Rocket replays the taps it kept up to half a second after this event.
		window.setTimeout( () => {
			finished = true;
		}, 1000 );
	} );

	/** Rocket delays scripts on this page and has not run them all yet. */
	function waiting() {
		return ! finished && !! document.querySelector( 'script[type$="rocketlazyloadscript"], script[data-rocket-status]' );
	}

	[ 'click', 'keydown', 'input' ].forEach( ( type ) => {
		document.addEventListener( type, ( event ) => {
			const target = event.target;
			if ( target.closest && target.closest( OURS ) && ! target.closest( THEIRS ) && waiting() ) {
				event.stopPropagation();
			}
		}, { isRocket: true } );
	} );
}() );
