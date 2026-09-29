<?php
/**
 * Stock status line with a coloured dot.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Product_Stock extends Base {

	public function get_name(): string {
		return 'stx-product-stock';
	}

	public function get_title(): string {
		return __( 'Product stock', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-stock';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Stock', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'in_stock_text',
			array(
				'label'       => __( 'In stock text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'WooCommerce text', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'hide_virtual',
			array(
				'label'       => __( 'Hide for downloads & courses', 'studiare-extensions' ),
				'description' => __( 'Digital items are always available, so the line is usually noise there.', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Stock', 'studiare-extensions' ) );
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'selector' => '{{WRAPPER}} .stx-stock',
			)
		);
		$this->add_control(
			'in_color',
			array(
				'label'     => __( 'In stock colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-stock--in' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'low_color',
			array(
				'label'     => __( 'Low / out of stock colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-stock--out, {{WRAPPER}} .stx-stock--low' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				if ( 'yes' === $s['hide_virtual'] && ( $product->is_virtual() || $product->is_downloadable() ) && ! $this->in_editor() ) {
					return;
				}

				$availability = $product->get_availability();
				$text         = (string) ( $availability['availability'] ?? '' );
				$state        = $product->is_in_stock() ? 'in' : 'out';

				if ( 'in' === $state && $product->managing_stock() && $product->get_stock_quantity() !== null && $product->get_stock_quantity() <= (int) get_option( 'woocommerce_notify_low_stock_amount', 2 ) ) {
					$state = 'low';
				}

				if ( 'in' === $state && '' !== (string) $s['in_stock_text'] ) {
					$text = (string) $s['in_stock_text'];
				}

				if ( '' === $text ) {
					$text = $product->is_in_stock() ? __( 'In stock', 'studiare-extensions' ) : __( 'Out of stock', 'studiare-extensions' );
				}

				printf( '<p class="stx-stock stx-stock--%1$s"><i aria-hidden="true"></i>%2$s</p>', esc_attr( $state ), esc_html( self::digits( $text ) ) );
			}
		);
	}
}
