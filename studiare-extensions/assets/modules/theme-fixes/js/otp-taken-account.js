/**
 * Studiare Extensions — theme fix: taken email or mobile in the mobile sign-up.
 *
 * Studiare Core's combined OTP form shows its sign-up fields (`.st_crf`) once
 * it has decided the number is new, but its "send code" request carries only
 * the number. While those fields are on screen, this script adds the sign-up
 * email and username (and the flag `stx_register`) to that request, so
 * Otp_Taken_Account can say which one is already registered before an SMS is
 * spent. The theme shows the answer in its own toast.
 *
 * "Change mobile number" hides the fields but leaves the terms box ticked,
 * and a ticked box makes the theme sign a new number up at once: the code
 * went out with details nobody checked, and checking them then would refuse
 * fields the visitor can no longer see. So the box is unticked as well, and
 * the form starts over from the number, as on first use: a number with an
 * account logs in, a new one brings the fields back (still filled in).
 *
 * The theme's script is jQuery, so the fields are added by a jQuery AJAX
 * prefilter, which sees the request data after it was turned into a string.
 * jQuery may arrive late when a cache plugin delays scripts, hence the retry.
 */
( function () {
	'use strict';

	/** WP Rocket lets listeners with this flag through while it delays scripts. */
	const UNDELAYED = { isRocket: true };

	const FORM = '#combined-otp-form';
	const ACTION = 'action=check_and_send_combined_otp';

	/** Sign-up fields the server checks, by the theme's field ids. */
	const FIELDS = [ 'sr_email', 'sr_username' ];

	/**
	 * The theme's sign-up fields, while they are on screen.
	 *
	 * @return {HTMLElement|null} Field group.
	 */
	function visibleSignUpFields() {
		const group = document.querySelector( FORM + ' .st_crf' );
		return group && group.offsetParent !== null ? group : null;
	}

	/**
	 * Adds the sign-up fields to the theme's "send code" request.
	 *
	 * @param {Object} options jQuery AJAX options (data already a string).
	 */
	function addSignUpFields( options ) {
		if ( typeof options.data !== 'string' || options.data.indexOf( ACTION ) === -1 ) {
			return;
		}

		const group = visibleSignUpFields();
		if ( ! group ) {
			return;
		}

		// The theme prints only the fields switched on in its options.
		const extra = [ 'stx_register=1' ];
		FIELDS.forEach( ( id ) => {
			const field = group.querySelector( '#' + id );
			if ( field && field.value.trim() !== '' ) {
				extra.push( id + '=' + encodeURIComponent( field.value.trim() ) );
			}
		} );

		options.data += '&' + extra.join( '&' );
	}

	/**
	 * "Change mobile number": untick the terms box the theme leaves ticked,
	 * and bring back the send button. Once a code went out, the theme hides
	 * that button for its countdown; "change" stops the countdown, which was
	 * the only thing that showed the button again, so the new number could
	 * not be sent without reloading the page.
	 *
	 * @param {MouseEvent} event Click.
	 */
	function restartOnNumberChange( event ) {
		const change = event.target instanceof Element ? event.target.closest( '.change-phone-btn' ) : null;
		const form = change ? change.closest( FORM ) : null;
		if ( ! form ) {
			return;
		}

		const terms = form.querySelector( '#accept_terms' );
		if ( terms ) {
			terms.checked = false;
		}

		const send = form.querySelector( '#send-combined-otp' );
		if ( send ) {
			send.style.removeProperty( 'display' );
		}
	}

	/**
	 * Runs a callback once jQuery exists.
	 *
	 * @param {Function} callback Receives jQuery.
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

	// Capture phase on `document`: also covers a login popup inserted later.
	document.addEventListener( 'click', restartOnNumberChange, Object.assign( { capture: true }, UNDELAYED ) );
	withJQuery( ( $ ) => $.ajaxPrefilter( addSignUpFields ) );
}() );
