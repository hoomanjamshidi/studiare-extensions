<?php
/**
 * Price with old price and discount badge.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Price extends Base {

	public function get_name(): string {
		return 'stx-product-price';
	}

	public function get_title(): string {
		return __( 'Product price', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-price';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Price', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'stack',
				'options' => array(
					'stack'  => __( 'Old price above', 'studiare-extensions' ),
					'inline' => __( 'On one line', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'badge',
			array(
				'label'   => __( 'Discount badge', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'badge_format',
			array(
				'label'       => __( 'Badge text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				/* translators: {percent} is replaced with the discount percentage. */
				'default'     => __( '{percent}% off', 'studiare-extensions' ),
				'description' => __( '{percent} is replaced with the percentage.', 'studiare-extensions' ),
				'condition'   => array( 'badge' => 'yes' ),
			)
		);

		$this->add_control(
			'free_label',
			array(
				'label'   => __( 'Text for free items', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Free', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Size', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'lg',
				'options' => array(
					'lg' => __( 'Large', 'studiare-extensions' ),
					'md' => __( 'Medium', 'studiare-extensions' ),
					'sm' => __( 'Small', 'studiare-extensions' ),
				),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'studiare-extensions' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Start', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::start_icon(),
					),
					'center'     => array(
						'title' => __( 'Center', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'End', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::end_icon(),
					),
				),
				'selectors' => array( '{{WRAPPER}} .stx-price' => '--stx-justify: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Price', 'studiare-extensions' ) );
		$this->add_text_style( 'now', '.stx-price__now', array( 'label' => __( 'Price', 'studiare-extensions' ) ) );
		$this->add_text_style( 'old', '.stx-price__old', array( 'label' => __( 'Old price', 'studiare-extensions' ) ) );
		$this->add_text_style( 'currency', '.stx-price .woocommerce-Price-currencySymbol', array( 'label' => __( 'Currency', 'studiare-extensions' ) ) );

		$this->add_control(
			'badge_heading',
			array(
				'label'     => __( 'Discount badge', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'badge_color',
			array(
				'label'     => __( 'Text colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-price__badge' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'badge_bg',
			array(
				'label'     => __( 'Background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-price__badge' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			static function ( \WC_Product $product ) use ( $s ) {
				echo '<div class="stx-price-wrap stx-price-wrap--' . esc_attr( $s['size'] ) . '">';
				$html = Parts::price(
					$product,
					array(
						'layout'       => $s['layout'],
						'badge'        => 'yes' === $s['badge'],
						'badge_format' => (string) $s['badge_format'],
						'free_label'   => (string) $s['free_label'],
					)
				);
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
				echo '</div>';
			}
		);
	}
}
