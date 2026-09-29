<?php
/**
 * Product/course title.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Product_Title extends Base {

	public function get_name(): string {
		return 'stx-product-title';
	}

	public function get_title(): string {
		return __( 'Product title', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-title';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Title', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'tag',
			array(
				'label'   => __( 'HTML tag', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h1',
				'options' => array(
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Size', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'lg',
				'options' => array(
					'xl' => __( 'Extra large', 'studiare-extensions' ),
					'lg' => __( 'Large', 'studiare-extensions' ),
					'md' => __( 'Medium', 'studiare-extensions' ),
				),
			)
		);

		$this->add_align_control( 'align', '.stx-ptitle' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Title', 'studiare-extensions' ) );
		$this->add_text_style( 'title', '.stx-ptitle' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s   = $this->get_settings_for_display();
		$tag = in_array( $s['tag'], array( 'h1', 'h2', 'h3', 'div' ), true ) ? $s['tag'] : 'h1';

		$this->with_product(
			static function ( \WC_Product $product ) use ( $tag, $s ) {
				printf(
					'<%1$s class="stx-ptitle stx-heading--%2$s">%3$s</%1$s>',
					esc_html( $tag ),
					esc_attr( $s['size'] ),
					esc_html( $product->get_name() )
				);
			}
		);
	}
}
