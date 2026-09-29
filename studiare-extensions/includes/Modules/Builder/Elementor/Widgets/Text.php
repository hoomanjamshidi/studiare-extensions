<?php
/**
 * Rich text block. Inherits the theme font.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Text extends Base {

	public function get_name(): string {
		return 'stx-text';
	}

	public function get_title(): string {
		/* translators: %s: widget name. */
		return sprintf( __( '%s (Studiare+)', 'studiare-extensions' ), __( 'Text', 'studiare-extensions' ) );
	}

	public function get_icon(): string {
		return 'eicon-text';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Text', 'studiare-extensions' ) );

		$this->add_control(
			'content',
			array(
				'label'   => __( 'Text', 'studiare-extensions' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => '<p>' . __( 'Write your text here.', 'studiare-extensions' ) . '</p>',
			)
		);

		$this->add_control(
			'tone',
			array(
				'label'   => __( 'Tone', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'body',
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
					'px' => array(
						'min' => 200,
						'max' => 1200,
					),
					'ch' => array(
						'min' => 20,
						'max' => 120,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-text' => 'max-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Text', 'studiare-extensions' ) );
		$this->add_text_style( 'text', '.stx-text' );

		$this->add_control(
			'link_color',
			array(
				'label'     => __( 'Link colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-text a' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		printf(
			'<div class="stx-text stx-text--%1$s">%2$s</div>',
			esc_attr( $s['tone'] ),
			wp_kses_post( $this->parse_text_editor( (string) $s['content'] ) )
		);
	}
}
