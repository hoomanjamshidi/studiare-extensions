<?php
/**
 * "What you will learn" checklist, typed per product in the Studiare+ box
 * on the product edit screen.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Highlights extends Base {

	public function get_name(): string {
		return 'stx-product-highlights';
	}

	public function get_title(): string {
		return __( 'Highlights (what you will learn)', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-check-circle';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Highlights', 'studiare-extensions' ) );

		$this->add_control(
			'source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Items come from the "What you will learn" box on each product\'s edit screen. The widget hides itself when a product has none.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'What you will learn', 'studiare-extensions' ),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'     => __( 'Columns', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '2',
				'options'   => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
				),
				'selectors' => array( '{{WRAPPER}} .stx-hl' => '--stx-cols: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Highlights', 'studiare-extensions' ) );
		$this->add_box_style( 'box', '.stx-hl', array( 'shadow' => true ) );
		$this->add_text_style( 'title', '.stx-hl__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'item', '.stx-hl__item', array( 'label' => __( 'Items', 'studiare-extensions' ) ) );
		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-hl__icon' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			static function ( \WC_Product $product ) use ( $s ) {
				echo Parts::highlights( $product, array( 'title' => (string) $s['title'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
			}
		);
	}
}
