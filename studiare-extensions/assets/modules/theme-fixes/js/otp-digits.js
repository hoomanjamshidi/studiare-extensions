/**
 * Studiare Extensions — theme fix: Persian digits in the mobile login.
 *
 * Studiare Core's OTP forms reject a number typed with a Persian keyboard
 * (۰۹۱۲…). This script keeps their fields (all named `otp_…`) in Latin
 * digits while the visitor types, and turns a mobile number into the local
 * 09… form when it is pasted, when the field is left and just before the
 * theme reads it (send button, Enter, submit).
 *
 * Every listener sits on `document` in the capture phase: it runs before the
 * theme's own jQuery handlers (bubbling to `document`) and also covers forms
 * that a login popup inserts later.
 *
 * `normalize()` mirrors Otp_Digits::normalize() on the server; change them together.
 */
( function () {
	'use strict';

	const FIELD = 'input[name^="otp_"]';

	/**
	 * The OTP text field an event belongs to, if any.
	 *
	 * @param {EventTarget} target Event target.
	 * @return {HTMLInputElement|null} Field.
	 */
	function otpField( target ) {
		return target instanceof HTMLInputElement && target.matches( FIELD ) && /^(text|tel)$/.test( target.type ) ? target : null;
	}

	/**
	 * Latin digits only: Persian (U+06F0…) and Arabic-Indic (U+0660…) digits
	 * are converted, and spaces, dashes and invisible direction marks that a
	 * pasted number brings along are dropped.
	 *
	 * @param {string} value Text.
	 * @return {string} Digits.
	 */
	function digits( value ) {
		return value
			.replace( /[۰-۹]/g, ( digit ) => String( digit.charCodeAt( 0 ) - 0x06f0 ) )
			.replace( /[٠-٩]/g, ( digit ) => String( digit.charCodeAt( 0 ) - 0x0660 ) )
			.replace( /\D/g, '' );
	}

	/**
	 * The final value: digits, and for a mobile number the local form the
	 * theme expects (+98 912…, 0098 912… and 912… → 0912…). Only run on a
	 * finished number: while typing, "98…" or "9…" may still grow.
	 *
	 * @param {HTMLInputElement} input Field.
	 * @param {string}           value Text.
	 * @return {string} Normalized value.
	 */
	function normalize( input, value ) {
		const clean = digits( value );

		if ( ! /_phone$/.test( input.name ) ) {
			return clean;
		}

		if ( /^(?:00)?989\d{9}$/.test( clean ) ) {
			return '0' + clean.slice( -10 );
		}

		return /^9\d{9}$/.test( clean ) ? '0' + clean : clean;
	}

	/**
	 * Writes a new value; the caret stays where the visitor was typing.
	 *
	 * @param {HTMLInputElement} input Field.
	 * @param {string}           value New value.
	 * @param {number}           caret Caret position in the new value.
	 */
	function write( input, value, caret ) {
		if ( value === input.value ) {
			return;
		}

		input.value = value;

		// Safari moves focus to a field whose selection is set, so only touch the focused one.
		if ( document.activeElement === input ) {
			input.setSelectionRange( caret, caret );
		}
	}

	/**
	 * @param {HTMLInputElement} input Field.
	 */
	function finish( input ) {
		const value = normalize( input, input.value );
		write( input, value, value.length );
	}

	/**
	 * Finishes every OTP field of the form around an element.
	 *
	 * @param {Element} element Button, form or field.
	 */
	function finishForm( element ) {
		const form = element.closest( 'form' );
		if ( ! form ) {
			return;
		}

		form.querySelectorAll( FIELD ).forEach( ( input ) => {
			if ( otpField( input ) ) {
				finish( input );
			}
		} );
	}

	document.addEventListener( 'input', ( event ) => {
		const input = otpField( event.target );
		if ( ! input || event.isComposing ) {
			return;
		}

		const caret = input.selectionStart === null ? input.value.length : input.selectionStart;
		write( input, digits( input.value ), digits( input.value.slice( 0, caret ) ).length );
	}, true );

	// The theme caps the phone field at 11 characters, so a pasted "+98 912 345 6789"
	// would be cut short before it could be cleaned: clean the whole text instead.
	document.addEventListener( 'paste', ( event ) => {
		const input = otpField( event.target );
		if ( ! input || ! event.clipboardData ) {
			return;
		}

		event.preventDefault();

		const text = input.value.slice( 0, input.selectionStart ) + event.clipboardData.getData( 'text' ) + input.value.slice( input.selectionEnd );
		const value = normalize( input, text );
		write( input, value, value.length );
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	}, true );

	document.addEventListener( 'focusout', ( event ) => {
		const input = otpField( event.target );
		if ( input ) {
			finish( input );
		}
	}, true );

	// The theme sends on Enter from its own keydown handler, before any click or submit event.
	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' === event.key && otpField( event.target ) ) {
			finishForm( event.target );
		}
	}, true );

	// Values the browser restored or autofilled never went through a keystroke.
	document.addEventListener( 'click', ( event ) => {
		const button = event.target instanceof Element ? event.target.closest( 'button, input[type="submit"], input[type="button"]' ) : null;
		if ( button ) {
			finishForm( button );
		}
	}, true );

	document.addEventListener( 'submit', ( event ) => {
		if ( event.target instanceof HTMLFormElement ) {
			finishForm( event.target );
		}
	}, true );
}() );
