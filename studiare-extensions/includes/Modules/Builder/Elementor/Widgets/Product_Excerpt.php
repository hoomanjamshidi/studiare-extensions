<?php
/**
 * Product short description.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Product_Excerpt extends Base {

	public function get_name(): string {
		return 'stx-product-excerpt';
	}

	public function get_title(): string {
		return __( 'Product short description', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-description';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Short description', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'tone',
			array(
				'label'   => __( 'Tone', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'lead',
				'options' => array(
					'body'  => __( 'Body text', 'studiare-extensions' ),
					'lead'  => __( 'Lead (larger)', 'studiare-extensions' ),
					'muted' => __( 'Muted (smaller)', 'studiare-extensions' ),
				),
			)
		);

		$this->add_align_control( 'align', '.stx-text' );

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => __( 'Max width', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'ch', '%' ),
				'range'      => array(
					'ch' => array(
						'min' => 20,
						'max' => 120,
					),
					'px' => array(
						'min' => 200,
						'max' => 1200,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-text' => 'max-width: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Text', 'studiare-extensions' ) );
		$this->add_text_style( 'text', '.stx-text' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$excerpt = (string) $product->get_short_description();

				if ( '' === trim( $excerpt ) ) {
					$this->editor_hint( __( 'The sample product has no short description.', 'studiare-extensions' ) );
					return;
				}

				printf(
					'<div class="stx-text stx-text--%1$s">%2$s</div>',
					esc_attr( $s['tone'] ),
					wp_kses_post( wpautop( do_shortcode( $excerpt ) ) )
				);
			}
		);
	}
}
