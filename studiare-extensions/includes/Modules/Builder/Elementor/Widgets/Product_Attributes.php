<?php
/**
 * Specifications table: visible attributes, weight, dimensions and extra rows.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Attributes extends Base {

	public function get_name(): string {
		return 'stx-product-attributes';
	}

	public function get_title(): string {
		return __( 'Product specifications', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-table';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Specifications', 'studiare-extensions' ) );
		$this->add_sample_note();

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			array(
				'label'   => __( 'Label', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'value',
			array(
				'label'   => __( 'Value', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'extra',
			array(
				'label'       => __( 'Extra rows (same for every product)', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(),
				'title_field' => '{{{ label }}}',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Specifications', 'studiare-extensions' ) );
		$this->add_text_style( 'label', '.stx-specs dt', array( 'label' => __( 'Label', 'studiare-extensions' ) ) );
		$this->add_text_style( 'value', '.stx-specs dd', array( 'label' => __( 'Value', 'studiare-extensions' ) ) );
		$this->add_control(
			'divider',
			array(
				'label'     => __( 'Divider colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-specs__row' => 'border-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			static function ( \WC_Product $product ) use ( $s ) {
				$extra = array();
				foreach ( (array) $s['extra'] as $row ) {
					if ( '' !== (string) $row['label'] && '' !== (string) $row['value'] ) {
						$extra[] = array(
							'label' => (string) $row['label'],
							'value' => (string) $row['value'],
						);
					}
				}

				echo Parts::specs( $product, $extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
			}
		);
	}
}
