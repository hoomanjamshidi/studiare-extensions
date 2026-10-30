<?php
/**
 * Persian for WooCommerce's cart and checkout messages when WooCommerce's own
 * translation is missing (its language pack is downloaded separately from
 * WordPress.org, and on some hosts it never arrives).
 *
 * Only strings WooCommerce left untranslated are replaced, by the plugin's
 * own translation of the same English text, so a site with WooCommerce's
 * Persian pack keeps it, and an English site sees no change.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Checkout;

defined( 'ABSPATH' ) || exit;

final class Woo_Texts {

	/** @var array<string, string>|null English => translated, for this request. */
	private $texts = null;

	public function register(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		add_filter( 'gettext_woocommerce', array( $this, 'text' ), 10, 2 );
		add_filter( 'gettext_with_context_woocommerce', array( $this, 'text_with_context' ), 10, 3 );
		add_filter( 'ngettext_woocommerce', array( $this, 'plural' ), 10, 4 );
	}

	/**
	 * @param string $translation WooCommerce's translation.
	 * @param string $text        English text.
	 */
	public function text( $translation, $text ) {
		if ( $translation !== $text ) {
			return $translation;
		}

		$texts = $this->texts();

		return $texts[ $text ] ?? $translation;
	}

	/**
	 * @param string $translation WooCommerce's translation.
	 * @param string $text        English text.
	 * @param string $context     Context.
	 */
	public function text_with_context( $translation, $text, $context ) {
		if ( $translation === $text && 'Billing %s' === $text && 'checkout-validation' === $context ) {
			/* translators: %s: field label in a checkout error, e.g. "First name". */
			return _x( 'Billing %s', 'checkout-validation', 'studiare-extensions' );
		}

		return $translation;
	}

	/**
	 * @param string $translation WooCommerce's translation.
	 * @param string $single      English singular.
	 * @param string $plural      English plural.
	 * @param int    $number      Count.
	 */
	public function plural( $translation, $single, $plural, $number ) {
		if ( ( $translation === $single || $translation === $plural ) && '%s has been added to your cart.' === $single ) {
			/* translators: %s: product names. */
			return _n( '%s has been added to your cart.', '%s have been added to your cart.', (int) $number, 'studiare-extensions' );
		}

		return $translation;
	}

	/** @return array<string, string> English => translated. */
	private function texts(): array {
		if ( null !== $this->texts ) {
			return $this->texts;
		}

		// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment -- WooCommerce's own texts; their placeholders keep WooCommerce's meaning.
		$this->texts = array(
			// Cart.
			'Cart updated.'                                => __( 'Cart updated.', 'studiare-extensions' ),
			'%s removed.'                                  => __( '%s removed.', 'studiare-extensions' ),
			'Undo?'                                        => __( 'Undo?', 'studiare-extensions' ),
			'View cart'                                    => __( 'View cart', 'studiare-extensions' ),
			'Continue shopping'                            => __( 'Continue shopping', 'studiare-extensions' ),
			'Your cart is currently empty.'                => __( 'Your cart is currently empty.', 'studiare-extensions' ),
			'Return to shop'                               => __( 'Return to shop', 'studiare-extensions' ),
			'You cannot add another "%s" to your cart.'    => __( 'You cannot add another "%s" to your cart.', 'studiare-extensions' ),
			'Sorry, this product cannot be purchased.'     => __( 'Sorry, this product cannot be purchased.', 'studiare-extensions' ),
			'An item which is no longer available was removed from your cart.' => __( 'An item which is no longer available was removed from your cart.', 'studiare-extensions' ),
			'Cart totals'                                  => __( 'Cart totals', 'studiare-extensions' ),
			'Proceed to checkout'                          => __( 'Proceed to checkout', 'studiare-extensions' ),
			'Subtotal'                                     => __( 'Subtotal', 'studiare-extensions' ),
			'Total'                                        => __( 'Total', 'studiare-extensions' ),
			'Coupon: %s'                                   => __( 'Coupon: %s', 'studiare-extensions' ),
			'[Remove]'                                     => __( '[Remove]', 'studiare-extensions' ),
			'Free!'                                        => __( 'Free!', 'studiare-extensions' ),

			// Coupons.
			'Coupon code applied successfully.'            => __( 'Coupon code applied successfully.', 'studiare-extensions' ),
			'Coupon code removed successfully.'            => __( 'Coupon code removed successfully.', 'studiare-extensions' ),
			'Coupon has been removed.'                     => __( 'Coupon has been removed.', 'studiare-extensions' ),
			'Please enter a coupon code.'                  => __( 'Please enter a coupon code.', 'studiare-extensions' ),
			'Coupon "%s" cannot be applied because it does not exist.' => __( 'Coupon "%s" cannot be applied because it does not exist.', 'studiare-extensions' ),
			'Coupon "%s" cannot be applied because it is not valid.' => __( 'Coupon "%s" cannot be applied because it is not valid.', 'studiare-extensions' ),
			'Coupon "%s" has expired.'                     => __( 'Coupon "%s" has expired.', 'studiare-extensions' ),
			'Coupon code "%s" already applied!'            => __( 'Coupon code "%s" already applied!', 'studiare-extensions' ),
			'Sorry, coupon "%s" is not applicable to your cart contents.' => __( 'Sorry, coupon "%s" is not applicable to your cart contents.', 'studiare-extensions' ),
			'Sorry, coupon "%s" is not applicable to selected products.' => __( 'Sorry, coupon "%s" is not applicable to selected products.', 'studiare-extensions' ),
			'Sorry, coupon "%s" has already been applied and cannot be used in conjunction with other coupons.' => __( 'Sorry, coupon "%s" has already been applied and cannot be used in conjunction with other coupons.', 'studiare-extensions' ),
			'Sorry, coupon "%s" is not valid for sale items.' => __( 'Sorry, coupon "%s" is not valid for sale items.', 'studiare-extensions' ),
			'The minimum spend for coupon "%1$s" is %2$s.' => __( 'The minimum spend for coupon "%1$s" is %2$s.', 'studiare-extensions' ),
			'The maximum spend for coupon "%1$s" is %2$s.' => __( 'The maximum spend for coupon "%1$s" is %2$s.', 'studiare-extensions' ),
			'Usage limit for coupon "%s" has been reached.' => __( 'Usage limit for coupon "%s" has been reached.', 'studiare-extensions' ),
			'Please enter a valid email to use coupon code "%s".' => __( 'Please enter a valid email to use coupon code "%s".', 'studiare-extensions' ),

			// Checkout.
			'%s is a required field.'                      => __( '%s is a required field.', 'studiare-extensions' ),
			'%s is not a valid phone number.'              => __( '%s is not a valid phone number.', 'studiare-extensions' ),
			'%s is not a valid email address.'             => __( '%s is not a valid email address.', 'studiare-extensions' ),
			'%s is not a valid postcode / ZIP.'            => __( '%s is not a valid postcode / ZIP.', 'studiare-extensions' ),
			'Please read and accept the terms and conditions to proceed with your order.' => __( 'Please read and accept the terms and conditions to proceed with your order.', 'studiare-extensions' ),
			'Invalid payment method.'                      => __( 'Invalid payment method.', 'studiare-extensions' ),
			/* translators: %s: address of the customer's order history. */
			'There was an error processing your order. Please check for any charges in your payment method and review your <a href="%s">order history</a> before placing the order again.' => __( 'There was an error processing your order. Please check for any charges in your payment method and review your <a href="%s">order history</a> before placing the order again.', 'studiare-extensions' ),
			'Place order'                                  => __( 'Place order', 'studiare-extensions' ),
			'Your order'                                   => __( 'Your order', 'studiare-extensions' ),
			'Product'                                      => __( 'Product', 'studiare-extensions' ),
			'Billing details'                              => __( 'Billing details', 'studiare-extensions' ),
			'Additional information'                       => __( 'Additional information', 'studiare-extensions' ),
			'Order notes'                                  => __( 'Order notes', 'studiare-extensions' ),
			'Notes about your order, e.g. special notes for delivery.' => __( 'Notes about your order, e.g. special notes for delivery.', 'studiare-extensions' ),
			'Returning customer?'                          => __( 'Returning customer?', 'studiare-extensions' ),
			'Click here to login'                          => __( 'Click here to login', 'studiare-extensions' ),
			'Have a coupon?'                               => __( 'Have a coupon?', 'studiare-extensions' ),
			'Click here to enter your code'                => __( 'Click here to enter your code', 'studiare-extensions' ),
			'optional'                                     => __( 'optional', 'studiare-extensions' ),

			// Billing field labels (the panel can rename them too).
			'First name'                                   => __( 'First name', 'studiare-extensions' ),
			'Last name'                                    => __( 'Last name', 'studiare-extensions' ),
			'Phone'                                        => __( 'Phone', 'studiare-extensions' ),
			'Email address'                                => __( 'Email address', 'studiare-extensions' ),
			'Company name'                                 => __( 'Company name', 'studiare-extensions' ),
			'Country / Region'                             => __( 'Country / Region', 'studiare-extensions' ),
			'Street address'                               => __( 'Street address', 'studiare-extensions' ),
			'House number and street name'                 => __( 'House number and street name', 'studiare-extensions' ),
			'Apartment, suite, unit, etc. (optional)'      => __( 'Apartment, suite, unit, etc. (optional)', 'studiare-extensions' ),
			'Town / City'                                  => __( 'Town / City', 'studiare-extensions' ),
			'State / County'                               => __( 'State / County', 'studiare-extensions' ),
			'Province'                                     => __( 'Province', 'studiare-extensions' ),
			'Postcode / ZIP'                               => __( 'Postcode / ZIP', 'studiare-extensions' ),

			// Thank-you page.
			'Thank you. Your order has been received.'     => __( 'Thank you. Your order has been received.', 'studiare-extensions' ),
			'Order details'                                => __( 'Order details', 'studiare-extensions' ),
		);
		// phpcs:enable

		return $this->texts;
	}
}
