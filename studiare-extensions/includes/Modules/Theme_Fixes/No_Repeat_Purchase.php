<?php
/**
 * Fix: a student cannot buy a course they already own.
 *
 * Studiare refuses a second purchase only when a logged-in buyer adds the
 * course to the cart (`sd_bought_before_woocommerce_add_to_cart_validation`).
 * Other ways in skip that check: a guest adds the course and logs in at
 * checkout (the cart is kept), and the learning path bundle button calls
 * `WC()->cart->add_to_cart()` directly. The course then reaches checkout and
 * is paid for again, or the order fails. On the product page the theme only
 * relabels the buy button and disables it from a footer script.
 *
 * The fix makes an owned product not purchasable for its owner, which
 * WooCommerce enforces everywhere: the buy form is not printed (a note takes
 * its place), add to cart refuses it, and every page load drops it from the
 * cart (with our own note instead of WooCommerce's "can no longer be
 * purchased"), so checkout never sees it. "Owned" follows the theme's own
 * check (Theme_Bridge::user_bought_product()), and products the theme allows
 * to be bought again (`_studiare_sc_canbuy_again`) are left alone.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

use StudiareExt\Core\Asset;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class No_Repeat_Purchase extends Fix {

	private const HANDLE = 'stx-owned-course';

	/** @var array<string, bool> "<user>:<product>" => owned, for this request. */
	private $owned = array();

	public function id(): string {
		return 'no_repeat_purchase';
	}

	public function title(): string {
		return __( 'No second purchase of an owned course', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'The theme stops a second purchase only when a logged-in student adds the course to the cart. A course added before logging in, or through a learning path bundle, reaches checkout again, where it is paid for twice or the order fails with "There was an error processing your order". With this fix students see "You are a student of this course" instead of the buy button, an owned course leaves the cart with a short note, and checkout never takes it. Products set to "can be bought again" in the theme are not affected.', 'studiare-extensions' );
	}

	public function in_use(): bool {
		return function_exists( 'WC' );
	}

	public function boot(): void {
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'filter_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_cart_item_removed_message', array( $this, 'removed_message' ), 10, 2 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'print_note' ), 31 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Owned products are not purchasable for their owner. Admin screens are
	 * left alone, so staff can still add any product to an order there.
	 *
	 * @param bool        $purchasable Value so far.
	 * @param \WC_Product $product     Product or variation.
	 */
	public function filter_purchasable( $purchasable, $product ) {
		if ( ! $purchasable || ! $product instanceof \WC_Product || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $purchasable;
		}

		return ! $this->is_owned( $product );
	}

	/**
	 * Explains why an owned course left the cart; WooCommerce's own text
	 * ("can no longer be purchased, contact us") reads like a problem.
	 *
	 * @param string      $message WooCommerce's message.
	 * @param \WC_Product $product Removed product.
	 */
	public function removed_message( $message, $product ) {
		if ( ! $product instanceof \WC_Product || ! $this->is_owned( $product ) ) {
			return $message;
		}

		/* translators: %s: product name */
		return sprintf( __( 'You already own &ldquo;%s&rdquo;, so it was removed from your cart.', 'studiare-extensions' ), esc_html( $product->get_name() ) );
	}

	/**
	 * Takes the buy form's place (WooCommerce prints none for a product that
	 * is not purchasable), with the theme's own "purchased" text when set.
	 */
	public function print_note(): void {
		global $product;
		if ( ! $product instanceof \WC_Product || ! $this->is_owned( $product ) ) {
			return;
		}

		$id   = $product->get_id();
		$text = (string) get_post_meta( $id, '_studiare_product_single_sc_purchased_producct_text', true );
		if ( '' === $text ) {
			$text = (string) Theme_Bridge::option( 'product_single_sc_purchased_producct_text', '' );
		}
		if ( '' === $text ) {
			$text = __( 'You are a student of this course', 'studiare-extensions' );
		}

		echo '<p class="stx-owned-course">' . esc_html( $text ) . '</p>';
	}

	/** The note shows only on product pages; a few lines, printed inline. */
	public function enqueue(): void {
		if ( ! is_user_logged_in() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		wp_register_style( self::HANDLE, false, array(), STUDIARE_EXT_VERSION );
		wp_enqueue_style( self::HANDLE );
		wp_add_inline_style( self::HANDLE, Asset::contents( 'assets/modules/theme-fixes/css/owned-course.css' ) );
	}

	/**
	 * Whether the logged-in visitor bought this product (a variation counts
	 * for its parent, as in the theme's check) and may not buy it again.
	 * Memoized: shop pages ask for every product, and the theme's own check
	 * runs a database query each time.
	 *
	 * @param \WC_Product $product Product or variation.
	 */
	private function is_owned( \WC_Product $product ): bool {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$key        = $user_id . ':' . $product_id;
		if ( ! isset( $this->owned[ $key ] ) ) {
			$this->owned[ $key ] = ! $this->can_buy_again( $product_id ) && Theme_Bridge::user_bought_product( $user_id, $product_id );
		}

		return $this->owned[ $key ];
	}

	/**
	 * The theme's per-product "can be bought again" switch (anything but
	 * "no", the default, allows it), read the way the theme reads it.
	 *
	 * @param int $product_id Product ID.
	 */
	private function can_buy_again( int $product_id ): bool {
		$value = get_post_meta( $product_id, '_studiare_sc_canbuy_again', true );

		return '' !== (string) $value && 'no' !== $value;
	}
}
