<?php
/**
 * Front end of the cart & checkout module: swaps WooCommerce's cart,
 * checkout and thank-you templates for the Studiare+ designs, loads their
 * assets on those pages only and prints the purchase steps.
 *
 * The templates keep WooCommerce's hooks and the classes its scripts rely on
 * (`.woocommerce-cart-form__contents`, `.cart_item`, `.product-remove > a`,
 * `#coupon_code`, `form.checkout`, `#order_review`, `#payment`), so AJAX
 * updates, coupons, payment gateways and other plugins keep working. The
 * swap uses the `wc_get_template` filter, which runs on every call, unlike
 * the located path WooCommerce keeps in the object cache.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Checkout;

use StudiareExt\Core\Asset;

defined( 'ABSPATH' ) || exit;

final class Pages {

	private const HANDLE = 'stx-checkout';

	/** @var array Module settings. */
	private $settings;

	/**
	 * @param array $settings Module settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_filter( 'wc_get_template', array( $this, 'template' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'woocommerce_before_cart', array( $this, 'prepare_cart' ), 1 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'prepare_checkout' ), 1 );
		add_action( 'woocommerce_before_thankyou', array( $this, 'thankyou_steps' ), 1 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'free_item_price' ), 20, 2 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'free_item_price' ), 20, 2 );

		if ( '' !== $this->settings['checkout']['button'] ) {
			add_filter( 'woocommerce_order_button_text', array( $this, 'button_text' ), 20 );
		}
	}

	/**
	 * @param string $template      Located template file.
	 * @param string $template_name Template name, e.g. `cart/cart.php`.
	 */
	public function template( $template, $template_name ) {
		if ( 'cart/cart.php' === $template_name && $this->ours( 'cart' ) ) {
			return __DIR__ . '/views/cart.php';
		}
		if ( 'checkout/form-checkout.php' === $template_name && $this->ours( 'checkout' ) ) {
			return __DIR__ . '/views/form-checkout.php';
		}
		if ( 'checkout/thankyou.php' === $template_name && $this->ours( 'thankyou' ) ) {
			return __DIR__ . '/views/thankyou.php';
		}

		return $template;
	}

	public function enqueue(): void {
		$page = $this->page();
		if ( '' === $page || ! $this->ours( $page ) ) {
			return;
		}

		wp_enqueue_style( self::HANDLE, Asset::url( 'assets/modules/checkout/css/checkout.css' ), array( Module::TOKENS_HANDLE ), STUDIARE_EXT_VERSION );

		if ( 'thankyou' === $page ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			Asset::url( 'assets/modules/checkout/js/checkout.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script(
			self::HANDLE,
			'window.stxCheckout = ' . wp_json_encode(
				array(
					'autoUpdate' => ! empty( $this->settings['cart']['auto_update'] ),
					'summary'    => $this->settings['checkout']['summary'],
					'i18n'       => array(
						'enterCode'    => __( 'Please enter a coupon code.', 'studiare-extensions' ),
						'couponFailed' => __( 'The coupon could not be checked. Please try again.', 'studiare-extensions' ),
					),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * @param string[] $classes Body classes.
	 */
	public function body_class( $classes ) {
		$page = $this->page();
		if ( '' !== $page && $this->ours( $page ) ) {
			$classes[] = 'stx-co-page';
			$classes[] = 'stx-co-' . $page;
			// Ours replace Studiare's step bar (`.studiare-checkout-steps`).
			if ( ! empty( $this->settings[ $page ]['steps'] ) ) {
				$classes[] = 'stx-co-steps';
			}
		}

		return $classes;
	}

	/** Cross-sells move under the cart, out of the narrow summary column. */
	public function prepare_cart(): void {
		if ( $this->ours( 'cart' ) ) {
			remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		}
	}

	/**
	 * The payment methods get their own card in the form (the summary keeps
	 * the order table), and the summary's coupon box replaces WooCommerce's
	 * "Have a coupon?" bar above the form.
	 */
	public function prepare_checkout(): void {
		if ( $this->ours( 'checkout' ) ) {
			remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}
	}

	public function thankyou_steps(): void {
		if ( $this->ours( 'thankyou' ) ) {
			self::steps( 3, ! empty( $this->settings['thankyou']['steps'] ) );
		}
	}

	/**
	 * @param string $text WooCommerce's text.
	 */
	public function button_text( $text ) {
		return '' !== $this->settings['checkout']['button'] ? $this->settings['checkout']['button'] : $text;
	}

	/**
	 * A free course reads "Free" on our pages, as on the theme's product
	 * cards: the Persian zero (۰) is a small dot, so "۰ تومان" looked like a
	 * missing price.
	 *
	 * @param string $html      Price markup.
	 * @param array  $cart_item Cart item.
	 */
	public function free_item_price( $html, $cart_item ) {
		$page    = $this->page();
		$product = is_array( $cart_item ) && isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! in_array( $page, array( 'cart', 'checkout' ), true ) || ! $this->ours( $page ) || ! $product instanceof \WC_Product || 0.0 !== (float) $product->get_price() ) {
			return $html;
		}

		return self::free_price();
	}

	/** "Free" in place of a zero price (the cart, checkout and mini cart). */
	public static function free_price(): string {
		return '<span class="amount stx-co-free">' . esc_html__( 'Free', 'studiare-extensions' ) . '</span>';
	}

	/**
	 * Prints the purchase steps. The last one is access for courses and
	 * downloads, tracking for anything shipped (as Studiare's own bar).
	 *
	 * @param int  $current Current step, 1 to 3.
	 * @param bool $show    Whether the admin shows them.
	 */
	public static function steps( int $current, bool $show ): void {
		if ( ! $show ) {
			return;
		}

		$digital = 3 === $current ? self::order_is_digital() : Billing_Fields::cart_is_digital();
		require __DIR__ . '/views/steps.php';
	}

	/** Whether the order on the thank-you page holds only courses and downloads. */
	private static function order_is_digital(): bool {
		$order = wc_get_order( absint( get_query_var( 'order-received' ) ) );

		return ! $order instanceof \WC_Order || Thank_You::is_digital( $order );
	}

	/**
	 * Prints the coupon box with room for its messages under it.
	 *
	 * @param bool $open Show the field at once instead of behind "Have a coupon?".
	 */
	public static function coupon_box( bool $open ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- read by the included view.
		require __DIR__ . '/views/coupon.php';
	}

	/**
	 * Whether a page uses the Studiare+ design.
	 *
	 * @param string $page `cart`, `checkout` or `thankyou`.
	 */
	private function ours( string $page ): bool {
		return 'stx' === $this->settings[ $page ]['design'];
	}

	/** `cart`, `checkout`, `thankyou` or '' for any other page. */
	private function page(): string {
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return 'thankyou';
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url() ) {
			return 'checkout';
		}

		return '';
	}
}
