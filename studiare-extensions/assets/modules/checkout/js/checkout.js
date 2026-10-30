/**
 * Studiare Extensions — cart & checkout pages.
 *
 * Small additions on top of WooCommerce's own cart.js and checkout.js, which
 * keep doing the work (AJAX updates, gateways, validation):
 *   - cart: updating the cart a moment after a quantity changes, and the
 *     total bar at the bottom of phones;
 *   - checkout: the order summary folded on phones, its total kept current;
 *   - both: the coupon box, which sends the code to WooCommerce's own AJAX
 *     endpoint and shows the answer under the field, and Latin digits in
 *     number fields typed with a Persian keyboard.
 * Without JavaScript the cart falls back to WooCommerce's plain coupon field
 * and "Update cart" button.
 */
( function () {
	'use strict';

	/**
	 * Options for tap, click and key listeners. WP Rocket's "Delay JavaScript"
	 * (3.17+) holds such listeners back, also in scripts it does not delay like
	 * this one, until the scripts it delays have loaded, and then replays the
	 * first tap as an untrusted copy (iOS will not start audio from one). Its
	 * own `isRocket` option lets a listener through; browsers ignore it.
	 */
	const UNDELAYED = { isRocket: true };

	const config = Object.assign( { autoUpdate: true, summary: 'collapsed', i18n: {} }, window.stxCheckout || {} );
	const doc = document;
	const phone = window.matchMedia( '(max-width: 767px)' );
	const wide = window.matchMedia( '(min-width: 992px)' );

	const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
	const ARABIC_DIGITS = '٠١٢٣٤٥٦٧٨٩';

	/**
	 * Runs `callback( jQuery )` now, or as soon as jQuery arrives: WooCommerce's
	 * events go through jQuery, which cache plugins may load later.
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

	/** @param {string} text Text with Persian or Arabic digits. */
	function latinDigits( text ) {
		return text.replace( /[۰-۹٠-٩]/g, ( digit ) => String( Math.max( PERSIAN_DIGITS.indexOf( digit ), ARABIC_DIGITS.indexOf( digit ) ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Cart: quantities
	 * ------------------------------------------------------------------- */

	let updateTimer = 0;

	/** Presses WooCommerce's "Update cart" (AJAX with its cart.js, a plain post without). */
	function updateCart() {
		const button = doc.querySelector( '.stx-cartpage button[name="update_cart"]' );
		if ( button ) {
			button.disabled = false;
			button.click();
		}
	}

	/**
	 * Updates the cart a moment after a quantity changes: typed (a native
	 * event) or through Studiare's −/+ buttons (a jQuery-only `change`).
	 */
	function initQuantities() {
		if ( ! config.autoUpdate ) {
			return;
		}

		const schedule = ( event ) => {
			if ( event.target.matches( '.stx-cartpage input.qty' ) ) {
				window.clearTimeout( updateTimer );
				updateTimer = window.setTimeout( updateCart, 600 );
			}
		};
		doc.addEventListener( 'change', schedule );
		withJQuery( ( $ ) => $( doc ).on( 'change', '.stx-cartpage input.qty', ( event ) => schedule( event ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Cart: total bar on phones
	 * ------------------------------------------------------------------- */

	function initCartBar() {
		const bar = doc.querySelector( '[data-stx-cart-bar]' );
		if ( ! bar || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		// Studiare's `.wrap` has a transform (its off-canvas menu slides it), which
		// pins a fixed element inside it to the page instead of the screen.
		doc.body.appendChild( bar );

		let summaryInView = false;
		const show = () => {
			// No checkout button once WooCommerce has replaced an emptied cart.
			bar.hidden = ! phone.matches || summaryInView || ! doc.querySelector( '.stx-cartpage .wc-proceed-to-checkout' );
			doc.body.classList.toggle( 'stx-cartpage-bar-on', ! bar.hidden );
		};
		const observer = new window.IntersectionObserver( ( entries ) => {
			summaryInView = entries.some( ( entry ) => entry.isIntersecting );
			show();
		} );
		const watch = () => {
			observer.disconnect();
			const target = doc.querySelector( '.stx-cartpage .wc-proceed-to-checkout' );
			if ( target ) {
				observer.observe( target );
			}
		};

		watch();
		show();
		phone.addEventListener( 'change', show );

		// WooCommerce replaces the totals after every change.
		withJQuery( ( $ ) => {
			$( doc.body ).on( 'updated_cart_totals', () => {
				const total = doc.querySelector( '.stx-cartpage .cart_totals .order-total td' );
				const slot = bar.querySelector( '[data-stx-bar-total]' );
				if ( total && slot ) {
					slot.innerHTML = total.innerHTML;
				}
				watch();
			} );
			$( doc.body ).on( 'wc_cart_emptied', show );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Checkout: order summary
	 * ------------------------------------------------------------------- */

	function initSummary() {
		const summary = doc.querySelector( '[data-stx-summary]' );
		if ( ! summary ) {
			return;
		}

		// Folded on phones and tablets when the admin wants; always open beside the form.
		if ( ! wide.matches && 'collapsed' === config.summary ) {
			summary.open = false;
		}
		wide.addEventListener( 'change', () => {
			if ( wide.matches ) {
				summary.open = true;
			}
		} );
		summary.querySelector( 'summary' ).addEventListener( 'click', ( event ) => {
			if ( wide.matches ) {
				event.preventDefault();
			}
		}, UNDELAYED );

		withJQuery( ( $ ) => {
			$( doc.body ).on( 'updated_checkout', () => {
				const total = summary.querySelector( '.woocommerce-checkout-review-order-table .order-total td' );
				const slot = summary.querySelector( '[data-stx-summary-total]' );
				if ( total && slot ) {
					slot.innerHTML = total.innerHTML;
				}
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Coupon box
	 * ------------------------------------------------------------------- */

	/** WooCommerce's AJAX settings of this page: the cart's or the checkout's. */
	function wcParams() {
		return doc.querySelector( 'form.checkout' ) ? window.wc_checkout_params : window.wc_cart_params;
	}

	/** Reloads the totals through WooCommerce's own script, or the whole page without it. */
	function refreshTotals() {
		const checkout = Boolean( doc.querySelector( 'form.checkout' ) );
		if ( ! window.jQuery && ! checkout ) {
			window.location.reload();
			return;
		}
		withJQuery( ( $ ) => {
			if ( checkout ) {
				$( doc.body ).trigger( 'update_checkout' );
			} else {
				$( doc ).trigger( 'wc_update_cart', [ true ] );
			}
		} );
	}

	/**
	 * @param {Element} box  Coupon box.
	 * @param {string}  text Message.
	 * @param {string}  tone `success`, `error` or ''.
	 */
	function say( box, text, tone ) {
		const message = box.querySelector( '[data-stx-coupon-message]' );
		message.textContent = text;
		message.className = 'stx-coupon__message' + ( tone ? ' is-' + tone : '' );
		message.hidden = ! text;
		box.open = true;
	}

	/**
	 * Sends a coupon request to WooCommerce and shows its answer in the box.
	 *
	 * @param {Element} box      Coupon box.
	 * @param {string}  endpoint `apply_coupon` or `remove_coupon`.
	 * @param {Object}  fields   Posted fields.
	 */
	async function couponRequest( box, endpoint, fields ) {
		const params = wcParams();
		const button = box.querySelector( '[data-stx-coupon-apply]' );
		if ( ! params ) {
			return;
		}

		const body = new window.FormData();
		body.append( 'security', 'apply_coupon' === endpoint ? params.apply_coupon_nonce : params.remove_coupon_nonce );
		Object.keys( fields ).forEach( ( name ) => body.append( name, fields[ name ] ) );

		box.classList.add( 'is-busy' );
		button.disabled = true;
		try {
			const response = await window.fetch( params.wc_ajax_url.replace( '%%endpoint%%', endpoint ), { method: 'POST', credentials: 'same-origin', body } );
			const holder = doc.createElement( 'div' );
			holder.innerHTML = await response.text();
			const notice = holder.querySelector( '.woocommerce-error, .woocommerce-message, .woocommerce-info, .is-error, .is-success, .is-info' );
			const failed = Boolean( holder.querySelector( '.woocommerce-error, .is-error' ) );
			say( box, notice ? notice.textContent.trim().replace( /\s+/g, ' ' ) : '', failed ? 'error' : 'success' );
			if ( ! failed ) {
				box.querySelector( '[data-stx-coupon-input]' ).value = '';
				refreshTotals();
			}
		} catch ( error ) {
			say( box, config.i18n.couponFailed || '', 'error' );
		} finally {
			box.classList.remove( 'is-busy' );
			button.disabled = false;
		}
	}

	function applyCoupon( box ) {
		const input = box.querySelector( '[data-stx-coupon-input]' );
		const code = input.value.trim();
		if ( ! code ) {
			say( box, config.i18n.enterCode || '', 'error' );
			input.focus();
			return;
		}

		const fields = { coupon_code: code };
		// Coupons limited to some emails are checked against the one typed at checkout.
		const email = doc.querySelector( 'form.checkout input[name="billing_email"]' );
		if ( email ) {
			fields.billing_email = email.value;
		}
		couponRequest( box, 'apply_coupon', fields );
	}

	function initCoupon() {
		const box = doc.querySelector( '[data-stx-coupon]' );
		if ( ! box ) {
			return;
		}

		box.querySelector( '[data-stx-coupon-apply]' ).addEventListener( 'click', () => applyCoupon( box ), UNDELAYED );
		box.querySelector( '[data-stx-coupon-input]' ).addEventListener( 'keydown', ( event ) => {
			// Inside the checkout form, Enter would place the order.
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				applyCoupon( box );
			}
		}, UNDELAYED );

		// "[Remove]" in the totals: the answer goes to the box too, not to a bar
		// at the top. Capture phase, so WooCommerce's own handler never runs.
		doc.addEventListener( 'click', ( event ) => {
			const link = event.target.closest( 'a.woocommerce-remove-coupon' );
			if ( ! link || ! link.closest( '.stx-co' ) ) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			couponRequest( box, 'remove_coupon', { coupon: link.dataset.coupon || '' } );
		}, { capture: true, isRocket: true } );
	}

	/* ---------------------------------------------------------------------
	 * Number fields
	 * ------------------------------------------------------------------- */

	function initDigits() {
		doc.addEventListener( 'input', ( event ) => {
			const input = event.target;
			if ( ! input.matches || ! input.matches( '[data-stx-digits]' ) || event.isComposing ) {
				return;
			}
			const value = latinDigits( input.value );
			if ( value !== input.value ) {
				const caret = input.selectionStart;
				input.value = value;
				if ( null !== caret ) {
					input.setSelectionRange( caret, caret );
				}
			}
		}, UNDELAYED );
	}

	function boot() {
		initQuantities();
		initCartBar();
		initSummary();
		initCoupon();
		initDigits();
	}

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
