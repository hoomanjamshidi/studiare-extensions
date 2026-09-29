<?php
/**
 * Offer price and button for promotion bands: typed by hand, or taken from a
 * product (its sale and regular price, and an add-to-cart button).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;

defined( 'ABSPATH' ) || exit;

final class Offer_Price extends Home_Base {

	public function get_name(): string {
		return 'stx-offer-price';
	}

	public function get_title(): string {
		return __( 'Offer price', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-price-table';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Offer', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Price from', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => array(
					'manual'  => __( 'Typed below', 'studiare-extensions' ),
					'product' => __( 'A product', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'studiare-extensions' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => '',
				'min'         => 1,
				'description' => __( 'The button adds this product to the cart.', 'studiare-extensions' ),
				'condition'   => array( 'source' => 'product' ),
			)
		);

		$this->add_control(
			'price',
			array(
				'label'     => __( 'Price', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'old_price',
			array(
				'label'     => __( 'Old price (crossed out)', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'     => __( 'Button text', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Buy now', 'studiare-extensions' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'button_link',
			array(
				'label'     => __( 'Button link', 'studiare-extensions' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'stack',
				'options' => array(
					'stack'  => __( 'Price above the button', 'studiare-extensions' ),
					'inline' => __( 'Price beside the button', 'studiare-extensions' ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Offer', 'studiare-extensions' ) );
		$this->add_text_style( 'price', '.stx-offer__price', array( 'label' => __( 'Price', 'studiare-extensions' ) ) );
		$this->add_text_style( 'old', '.stx-offer__old', array( 'label' => __( 'Old price', 'studiare-extensions' ) ) );
		$this->add_control(
			'button_heading',
			array(
				'label'     => __( 'Button', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_button_style( 'btn', '.stx-offer .stx-btn' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$product = 'product' === $s['source'] && Context::has_woo() ? wc_get_product( (int) $s['product_id'] ) : null;

		if ( $product ) {
			$price  = $product->is_on_sale() ? wc_price( (float) wc_get_price_to_display( $product ) ) : $product->get_price_html();
			$old    = $product->is_on_sale() && $product->is_type( 'simple' ) ? wc_price( (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) ) : '';
			$button = $this->product_button( $product, (string) $s['button_text'] );
		} else {
			if ( 'product' === $s['source'] ) {
				$this->editor_hint( __( 'Enter the ID of a published product.', 'studiare-extensions' ) );
				return;
			}
			$price  = esc_html( $s['price'] );
			$old    = esc_html( $s['old_price'] );
			$button = $this->link_button( $s );
		}

		printf( '<div class="stx-offer stx-offer--%s">', esc_attr( $s['layout'] ) );
		if ( '' !== $price ) {
			echo '<div class="stx-offer__prices">';
			echo '<span class="stx-offer__price">' . self::digits_html( $price ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped or WooCommerce price markup.
			if ( '' !== $old ) {
				echo '<del class="stx-offer__old">' . self::digits_html( $old ) . '</del>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped or WooCommerce price markup.
			}
			echo '</div>';
		}
		echo $button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helpers.
		echo '</div>';
	}

	/**
	 * WooCommerce's AJAX add-to-cart button for simple products, a link to
	 * the product for the rest.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $text    Button text.
	 */
	private function product_button( \WC_Product $product, string $text ): string {
		$ajax = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' );

		if ( $ajax && wp_script_is( 'wc-add-to-cart', 'registered' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}

		return sprintf(
			'<a href="%1$s" class="stx-btn stx-btn--accent stx-btn--lg%2$s" data-quantity="1" data-product_id="%3$d" rel="nofollow"><span>%4$s</span></a>',
			esc_url( $ajax ? $product->add_to_cart_url() : $product->get_permalink() ),
			$ajax ? ' add_to_cart_button ajax_add_to_cart' : '',
			$product->get_id(),
			esc_html( $text )
		);
	}

	/**
	 * @param array $s Settings.
	 */
	private function link_button( array $s ): string {
		if ( '' === (string) $s['button_text'] ) {
			return '';
		}

		$this->add_render_attribute( 'button', 'class', 'stx-btn stx-btn--accent stx-btn--lg' );
		if ( ! empty( $s['button_link']['url'] ) ) {
			$this->add_link_attributes( 'button', $s['button_link'] );
		}

		return '<a ' . $this->get_render_attribute_string( 'button' ) . '><span>' . esc_html( $s['button_text'] ) . '</span></a>';
	}
}
