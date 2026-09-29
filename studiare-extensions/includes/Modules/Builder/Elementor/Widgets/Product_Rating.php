<?php
/**
 * Star rating with average and review count.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Rating extends Base {

	public function get_name(): string {
		return 'stx-product-rating';
	}

	public function get_title(): string {
		return __( 'Product rating', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-rating';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Rating', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'format',
			array(
				'label'       => __( 'Text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				/* translators: keep {average} and {count}. */
				'default'     => __( '{average} from {count} reviews', 'studiare-extensions' ),
				'description' => __( '{average} and {count} are replaced automatically.', 'studiare-extensions' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'link',
			array(
				'label'   => __( 'Link to reviews', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'   => __( 'Hide without reviews', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Rating', 'studiare-extensions' ) );
		$this->add_control(
			'star_color',
			array(
				'label'     => __( 'Star colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-stars' => '--stx-star: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'text', '.stx-rating__text' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$html = Parts::rating(
					$product,
					array(
						'format'     => (string) $s['format'],
						'link'       => 'yes' === $s['link'],
						'hide_empty' => 'yes' === $s['hide_empty'] && ! $this->in_editor(),
					)
				);

				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
			}
		);
	}
}
