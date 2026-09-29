<?php
/**
 * Cart button with live count (WooCommerce fragments). Opens Studiare's
 * off-canvas mini cart when the theme provides one.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Cart extends Base {

	public function get_name(): string {
		return 'stx-cart';
	}

	public function get_title(): string {
		/* translators: %s: widget name. */
		return sprintf( __( '%s (Studiare+)', 'studiare-extensions' ), __( 'Cart', 'studiare-extensions' ) );
	}

	public function get_icon(): string {
		return 'eicon-cart';
	}

	public function get_script_depends(): array {
		return array( 'stx-builder', 'wc-cart-fragments' );
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Cart', 'studiare-extensions' ) );

		$this->add_control(
			'label',
			array(
				'label'   => __( 'Text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Cart', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'show_total',
			array(
				'label'   => __( 'Cart total', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'variant',
			array(
				'label'   => __( 'Style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'outline',
				'options' => array(
					'outline' => __( 'Outlined button', 'studiare-extensions' ),
					'solid'   => __( 'Filled button', 'studiare-extensions' ),
					'icon'    => __( 'Icon with badge', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'action',
			array(
				'label'   => __( 'On click', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'     => __( 'Studiare mini cart (else cart page)', 'studiare-extensions' ),
					'cart'     => __( 'Cart page', 'studiare-extensions' ),
					'checkout' => __( 'Checkout', 'studiare-extensions' ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Cart', 'studiare-extensions' ) );
		$this->add_button_style( 'btn', '.stx-cart' );
		$this->add_control(
			'badge_bg',
			array(
				'label'     => __( 'Count background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-cart-count' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'badge_color',
			array(
				'label'     => __( 'Count colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-cart-count' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	/** Count badge markup (also used for cart fragments). */
	public static function count_html(): string {
		$count = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;

		return sprintf( '<span class="stx-cart-count%1$s" data-count="%2$d">%3$s</span>', $count ? '' : ' is-empty', $count, esc_html( self::num( $count ) ) );
	}

	/** Cart subtotal markup (also used for cart fragments). */
	public static function total_html(): string {
		$total = function_exists( 'WC' ) && WC()->cart ? (string) WC()->cart->get_cart_subtotal() : '';

		return '<span class="stx-cart-total">' . Base::digits_html( $total ) . '</span>';
	}

	/**
	 * Keeps every cart button in sync after AJAX add to cart.
	 *
	 * @param array $fragments Selector → HTML.
	 */
	public static function fragments( $fragments ) {
		$fragments = is_array( $fragments ) ? $fragments : array();

		$fragments['span.stx-cart-count'] = self::count_html();
		$fragments['span.stx-cart-total'] = self::total_html();

		return $fragments;
	}

	protected function render(): void {
		if ( ! function_exists( 'wc_get_cart_url' ) ) {
			$this->editor_hint( __( 'WooCommerce is required for the cart.', 'studiare-extensions' ) );
			return;
		}

		$s   = $this->get_settings_for_display();
		$url = 'checkout' === $s['action'] ? wc_get_checkout_url() : wc_get_cart_url();

		printf(
			'<a class="stx-cart stx-cart--%1$s" href="%2$s" data-stx-cart="%3$s" aria-label="%4$s">%5$s%6$s%7$s%8$s</a>',
			esc_attr( $s['variant'] ),
			esc_url( $url ),
			esc_attr( $s['action'] ),
			esc_attr( '' !== (string) $s['label'] ? $s['label'] : __( 'Cart', 'studiare-extensions' ) ),
			self::icon( 'bag' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
			'icon' !== $s['variant'] && '' !== (string) $s['label'] ? '<span class="stx-cart__label">' . esc_html( $s['label'] ) . '</span>' : '',
			'yes' === $s['show_total'] ? self::total_html() : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in total_html().
			self::count_html() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in count_html().
		);
	}
}
