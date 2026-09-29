<?php
/**
 * Promo card: small label, title and a call to action, the whole card being
 * one link (the side cards next to the hero slider).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Promo_Card extends Home_Base {

	public function get_name(): string {
		return 'stx-promo-card';
	}

	public function get_title(): string {
		return __( 'Promo card', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-call-to-action';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Card', 'studiare-extensions' ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'       => __( 'Small label', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'New', 'studiare-extensions' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => __( 'Card title', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'link_text',
			array(
				'label'   => __( 'Link text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'See more', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'studiare-extensions' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
				'default' => array( 'url' => '#' ),
			)
		);

		$this->add_control(
			'look',
			array(
				'label'   => __( 'Look', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => array(
					'dark'     => __( 'Dark', 'studiare-extensions' ),
					'card'     => __( 'Card', 'studiare-extensions' ),
					'soft'     => __( 'Soft accent', 'studiare-extensions' ),
					'gradient' => __( 'Accent gradient', 'studiare-extensions' ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Card', 'studiare-extensions' ) );

		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => __( 'Minimum height', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 80,
						'max' => 500,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-promo' => 'min-height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_box_style( 'card', '.stx-promo' );
		$this->add_text_style( 'title', '.stx-promo__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'eyebrow', '.stx-promo__eyebrow', array( 'label' => __( 'Small label', 'studiare-extensions' ) ) );
		$this->add_text_style( 'cta', '.stx-promo__cta', array( 'label' => __( 'Link text', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s   = $this->get_settings_for_display();
		$tag = ! empty( $s['link']['url'] ) ? 'a' : 'div';

		$this->add_render_attribute( 'card', 'class', array( 'stx-promo', 'stx-promo--' . $s['look'] ) );
		if ( 'a' === $tag ) {
			$this->add_link_attributes( 'card', $s['link'] );
		}

		printf( '<%1$s %2$s>', esc_html( $tag ), $this->get_render_attribute_string( 'card' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Elementor.
		if ( '' !== (string) $s['eyebrow'] ) {
			echo '<span class="stx-promo__eyebrow">' . esc_html( $s['eyebrow'] ) . '</span>';
		}
		echo '<span class="stx-promo__title">' . wp_kses_post( nl2br( (string) $s['title'] ) ) . '</span>';
		if ( '' !== (string) $s['link_text'] ) {
			echo '<span class="stx-promo__cta">' . esc_html( $s['link_text'] ) . '<span class="stx-promo__arrow" aria-hidden="true">' . ( is_rtl() ? '←' : '→' ) . '</span></span>';
		}
		printf( '</%s>', esc_html( $tag ) );
	}
}
