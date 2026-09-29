<?php
/**
 * Product reviews and review form (Studiare's review template when active).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Reviews extends Base {

	public function get_name(): string {
		return 'stx-product-reviews';
	}

	public function get_title(): string {
		return __( 'Product reviews', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-review';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Reviews', 'studiare-extensions' ) );
		$this->add_sample_note();
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Reviews', 'studiare-extensions' ) );
		$this->add_text_style( 'text', '.stx-reviews' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$this->with_product(
			static function ( \WC_Product $product ) {
				echo Parts::reviews( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce/theme template.
			}
		);
	}
}
