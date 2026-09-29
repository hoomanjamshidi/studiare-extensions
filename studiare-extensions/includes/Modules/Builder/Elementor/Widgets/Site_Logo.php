<?php
/**
 * Site logo: Studiare's logo setting → WordPress custom logo → site name,
 * with an optional custom image and a dark-mode variant.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Site_Logo extends Base {

	public function get_name(): string {
		return 'stx-site-logo';
	}

	public function get_title(): string {
		return __( 'Site logo', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-site-logo';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Logo', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Logo', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'   => __( 'Site logo (Studiare / WordPress)', 'studiare-extensions' ),
					'custom' => __( 'Custom image', 'studiare-extensions' ),
					'text'   => __( 'Site name as text', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'image',
			array(
				'label'     => __( 'Image', 'studiare-extensions' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array( 'source' => 'custom' ),
			)
		);

		$this->add_control(
			'dark_image',
			array(
				'label'       => __( 'Dark mode image', 'studiare-extensions' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => __( 'Optional. Shown when Studiare\'s dark mode is on.', 'studiare-extensions' ),
				'condition'   => array( 'source!' => 'text' ),
			)
		);

		$this->add_control(
			'text',
			array(
				'label'       => __( 'Text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => get_bloginfo( 'name' ),
				'condition'   => array( 'source' => 'text' ),
			)
		);

		$this->add_responsive_control(
			'width',
			array(
				'label'      => __( 'Width', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 40,
						'max' => 400,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 150,
				),
				'selectors'  => array( '{{WRAPPER}} .stx-logo' => '--stx-logo-w: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'source!' => 'text' ),
			)
		);

		// Square or tall logos would make a header very high at the chosen width.
		$this->add_responsive_control(
			'height',
			array(
				'label'       => __( 'Height', 'studiare-extensions' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min' => 20,
						'max' => 200,
					),
				),
				'description' => __( 'Sizes the logo by height, so square logos do not make the header tall. The width above then works as a maximum.', 'studiare-extensions' ),
				'selectors'   => array( '{{WRAPPER}} .stx-logo' => '--stx-logo-h: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'source!' => 'text' ),
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
				'selectors' => array( '{{WRAPPER}} .stx-logo-wrap' => 'justify-content: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Text logo', 'studiare-extensions' ), array( 'condition' => array( 'source' => 'text' ) ) );
		$this->add_text_style( 'text', '.stx-logo__text' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$name = get_bloginfo( 'name' );
		$url  = '';

		if ( 'custom' === $s['source'] && ! empty( $s['image']['url'] ) ) {
			$url = $s['image']['url'];
		} elseif ( 'auto' === $s['source'] ) {
			$url = $this->site_logo_url();
		}

		$by_height = ! empty( $s['height']['size'] ) || ! empty( $s['height_tablet']['size'] ) || ! empty( $s['height_mobile']['size'] );

		printf(
			'<div class="stx-logo-wrap"><a class="stx-logo%1$s" href="%2$s" rel="home">',
			$by_height ? ' stx-logo--by-height' : '',
			esc_url( home_url( '/' ) )
		);

		if ( '' === $url ) {
			$text = 'text' === $s['source'] && '' !== (string) $s['text'] ? $s['text'] : $name;
			echo '<span class="stx-logo__text">' . esc_html( $text ) . '</span>';
		} else {
			printf( '<img class="stx-logo__img" src="%1$s" alt="%2$s" decoding="async">', esc_url( $url ), esc_attr( $name ) );
			if ( ! empty( $s['dark_image']['url'] ) ) {
				printf( '<img class="stx-logo__img stx-logo__img--dark" src="%1$s" alt="%2$s" decoding="async" loading="lazy">', esc_url( $s['dark_image']['url'] ), esc_attr( $name ) );
			}
		}

		echo '</a></div>';
	}

	private function site_logo_url(): string {
		$studiare = Theme_Bridge::option( 'custom_logo_image' );
		if ( is_array( $studiare ) && ! empty( $studiare['url'] ) ) {
			return (string) $studiare['url'];
		}

		$logo_id = (int) get_theme_mod( 'custom_logo' );

		return $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	}
}
