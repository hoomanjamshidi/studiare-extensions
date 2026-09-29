<?php
/**
 * Full product description (the product's main content).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Content extends Base {

	public function get_name(): string {
		return 'stx-product-content';
	}

	public function get_title(): string {
		return __( 'Product description', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-post-content';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Description', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'collapse',
			array(
				'label'       => __( '"Show more" for long text', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Long descriptions are cut and a button reveals the rest.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'height',
			array(
				'label'     => __( 'Visible height', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 420,
				'min'       => 120,
				'max'       => 2000,
				'condition' => array( 'collapse' => 'yes' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Description', 'studiare-extensions' ) );
		$this->add_text_style( 'text', '.stx-desc' );
		$this->add_text_style( 'headings', '.stx-desc :is(h2,h3,h4)', array( 'label' => __( 'Headings inside', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			static function ( \WC_Product $product ) use ( $s ) {
				$html = Parts::description(
					$product,
					array(
						'collapse' => 'yes' === $s['collapse'],
						'height'   => (int) $s['height'],
					)
				);
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- post content.
			}
		);
	}
}
