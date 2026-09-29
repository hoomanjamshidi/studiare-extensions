<?php
/**
 * Button with brand variants. Inherits the theme font.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Button extends Base {

	public function get_name(): string {
		return 'stx-button';
	}

	public function get_title(): string {
		/* translators: %s: widget name. */
		return sprintf( __( '%s (Studiare+)', 'studiare-extensions' ), __( 'Button', 'studiare-extensions' ) );
	}

	public function get_icon(): string {
		return 'eicon-button';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	/** @return array<string, string> */
	public static function variants(): array {
		return array(
			'accent'  => __( 'Accent', 'studiare-extensions' ),
			'dark'    => __( 'Dark', 'studiare-extensions' ),
			'outline' => __( 'Outline', 'studiare-extensions' ),
			'soft'    => __( 'Soft', 'studiare-extensions' ),
			'light'   => __( 'Light (for dark areas)', 'studiare-extensions' ),
			'white'   => __( 'White (for accent areas)', 'studiare-extensions' ),
			'link'    => __( 'Text link', 'studiare-extensions' ),
		);
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Button', 'studiare-extensions' ) );

		$this->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Click here', 'studiare-extensions' ),
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
			'variant',
			array(
				'label'   => __( 'Style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'accent',
				'options' => self::variants(),
			)
		);

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Size', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'md',
				'options' => array(
					'sm' => __( 'Small', 'studiare-extensions' ),
					'md' => __( 'Medium', 'studiare-extensions' ),
					'lg' => __( 'Large', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'   => __( 'Icon', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => self::icon_options(),
			)
		);

		$this->add_control(
			'icon_position',
			array(
				'label'     => __( 'Icon position', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'before',
				'options'   => array(
					'before' => __( 'Before text', 'studiare-extensions' ),
					'after'  => __( 'After text', 'studiare-extensions' ),
				),
				'condition' => array( 'icon!' => '' ),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'                => __( 'Alignment', 'studiare-extensions' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
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
					'stretch'    => array(
						'title' => __( 'Full width', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'selectors_dictionary' => array(
					'flex-start' => 'justify-content: flex-start; --stx-btn-grow: 0',
					'center'     => 'justify-content: center; --stx-btn-grow: 0',
					'flex-end'   => 'justify-content: flex-end; --stx-btn-grow: 0',
					'stretch'    => 'justify-content: flex-start; --stx-btn-grow: 1',
				),
				'selectors'            => array( '{{WRAPPER}} .stx-btn-wrap' => '{{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Button', 'studiare-extensions' ) );
		$this->add_button_style( 'btn', '.stx-btn' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->add_render_attribute( 'button', 'class', array( 'stx-btn', 'stx-btn--' . $s['variant'], 'stx-btn--' . $s['size'] ) );

		if ( ! empty( $s['link']['url'] ) ) {
			$this->add_link_attributes( 'button', $s['link'] );
			$tag = 'a';
		} else {
			$this->add_render_attribute( 'button', 'type', 'button' );
			$tag = 'button';
		}

		$icon = '' !== $s['icon'] ? self::icon( $s['icon'] ) : '';
		$text = '<span>' . esc_html( $s['text'] ) . '</span>';
		$body = 'after' === $s['icon_position'] ? $text . $icon : $icon . $text;

		printf(
			'<div class="stx-btn-wrap"><%1$s %2$s>%3$s</%1$s></div>',
			esc_html( $tag ),
			$this->get_render_attribute_string( 'button' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Elementor.
			$body // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above; bundled SVG.
		);
	}
}
