<?php
/**
 * Studiare+ mini cart: swaps WooCommerce's `cart/mini-cart.php` (Studiare
 * overrides it too) while `mini.design` is `stx`, and loads its stylesheet.
 *
 * The template is the content of every `.widget_shopping_cart_content`:
 * Studiare's side panel (`.sc-cart-offcanvas`, printed on `wp_footer`, which
 * the theme and the Studiare+ header Cart widget open), the bottom
 * navigation's cart sheet and Studiare's older top-bar dropdown. WooCommerce
 * refreshes it after every add or remove through the `get_refreshed_fragments`
 * fragment, which renders the same template, so the swap reaches all of
 * them. The panel is on every page, so the stylesheet is too; Studiare's own
 * script still opens and closes it.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Checkout;

use StudiareExt\Core\Asset;

defined( 'ABSPATH' ) || exit;

final class Mini_Cart {

	private const HANDLE = 'stx-mini-cart';

	/** @var array Module settings. */
	private $settings;

	/**
	 * @param array $settings Module settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		if ( 'stx' !== $this->settings['mini']['design'] ) {
			return;
		}

		add_filter( 'wc_get_template', array( $this, 'template' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * @param string $template      Located template file.
	 * @param string $template_name Template name.
	 */
	public function template( $template, $template_name ) {
		return 'cart/mini-cart.php' === $template_name ? __DIR__ . '/views/mini-cart.php' : $template;
	}

	public function enqueue(): void {
		wp_enqueue_style( self::HANDLE, Asset::url( 'assets/modules/checkout/css/mini-cart.css' ), array( Module::TOKENS_HANDLE ), STUDIARE_EXT_VERSION );
	}

	/**
	 * `stx-mc-on` scopes the restyled side panel frame (Studiare's markup).
	 *
	 * @param string[] $classes Body classes.
	 */
	public function body_class( $classes ) {
		$classes[] = 'stx-mc-on';

		return $classes;
	}

	/**
	 * What the buyer saves against the regular prices: sale prices, plus
	 * coupons applied in the cart. Prices as the shop shows them (with or
	 * without tax), 0 when there is nothing to show.
	 */
	public static function savings(): float {
		$cart = WC()->cart;
		if ( ! $cart ) {
			return 0.0;
		}

		$saved = (float) $cart->get_discount_total() + ( $cart->display_prices_including_tax() ? (float) $cart->get_discount_tax() : 0.0 );

		foreach ( $cart->get_cart() as $item ) {
			$product = $item['data'] ?? null;
			if ( $product instanceof \WC_Product && $product->is_on_sale() ) {
				$regular = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
				$saved  += max( 0.0, (float) $regular - (float) wc_get_price_to_display( $product ) ) * (int) $item['quantity'];
			}
		}

		return $saved;
	}
}
