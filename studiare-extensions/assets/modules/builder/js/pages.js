/**
 * Studiare Extensions — about us and contact us widgets.
 *
 * Both enhance markup that works on its own: the contact form posts
 * normally and the map's button opens the map in a new tab.
 */
( function () {
	'use strict';

	const config = window.stxPages || {};
	const i18n = config.i18n || {};
	const doc = document;

	/* ---------------------------------------------------------------------
	 * Contact form: send in the background, mark fields, answer in place
	 * ------------------------------------------------------------------- */

	function initContact( form ) {
		const status = form.querySelector( '.stx-cform__status' );
		const button = form.querySelector( 'button[type="submit"]' );

		const say = ( message, ok ) => {
			status.textContent = message;
			status.classList.toggle( 'is-ok', ok );
			status.classList.toggle( 'is-error', ! ok && message !== '' );
		};

		const markInvalid = ( names ) => {
			form.querySelectorAll( '.stx-cform__input' ).forEach( ( field ) => {
				if ( names.includes( field.name ) ) {
					field.setAttribute( 'aria-invalid', 'true' );
				} else {
					field.removeAttribute( 'aria-invalid' );
				}
			} );
			const first = form.querySelector( '[aria-invalid="true"]' );
			if ( first ) {
				// A select turned into select2 by Studiare is hidden; focus its visible box.
				const box = first.nextElementSibling && first.nextElementSibling.querySelector( '.select2-selection' );
				( box || first ).focus();
			}
		};

		// Native reset leaves select2's shown text behind; a change event refreshes it.
		const reset = () => {
			form.reset();
			form.querySelectorAll( 'select' ).forEach( ( select ) => select.dispatchEvent( new Event( 'change', { bubbles: true } ) ) );
		};

		// A field the visitor corrects is no longer marked.
		form.addEventListener( 'input', ( event ) => {
			if ( event.target.hasAttribute( 'aria-invalid' ) ) {
				event.target.removeAttribute( 'aria-invalid' );
			}
		} );

		form.addEventListener( 'submit', async ( event ) => {
			if ( ! config.ajaxUrl || ! window.fetch ) {
				return;
			}

			event.preventDefault();
			if ( form.classList.contains( 'is-busy' ) ) {
				return;
			}
			form.classList.add( 'is-busy' );
			form.setAttribute( 'aria-busy', 'true' );
			button.disabled = true;
			say( '', false );

			let result = { status: 'error' };
			try {
				const response = await fetch( config.ajaxUrl, { method: 'POST', body: new FormData( form ), credentials: 'same-origin' } );
				const json = await response.json();
				result = ( json && json.data ) || { status: json && json.success ? 'ok' : 'error' };
			} catch ( error ) {
				result = { status: 'error' };
			}

			form.classList.remove( 'is-busy' );
			form.removeAttribute( 'aria-busy' );
			button.disabled = false;

			if ( result.status === 'ok' ) {
				reset();
				markInvalid( [] );
				say( form.dataset.success || i18n.ok || '', true );
				return;
			}

			say( i18n[ result.status ] || i18n.error || '', false );
			if ( result.status === 'invalid' && Array.isArray( result.fields ) ) {
				markInvalid( result.fields );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Map: load the iframe when the visitor asks for it
	 * ------------------------------------------------------------------- */

	function initMap( facade ) {
		const button = facade.querySelector( '[data-stx-map-load]' );
		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', ( event ) => {
			event.preventDefault();

			const frame = doc.createElement( 'iframe' );
			frame.className = 'stx-map__iframe';
			frame.src = facade.dataset.stxMap;
			frame.title = facade.dataset.title || '';
			frame.referrerPolicy = 'no-referrer-when-downgrade';
			frame.allowFullscreen = true;
			facade.replaceWith( frame );
			// The button is gone; keep keyboard users where they were.
			frame.focus();
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

		once( '[data-stx-contact]', initContact );
		once( '[data-stx-map]', initMap );
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
