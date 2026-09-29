<?php
/**
 * The post's text, styled for long reading: comfortable line length and
 * spacing, headings, lists, quotes, code, tables and pictures, in light and
 * dark mode. Headings get anchors, which the table of contents links to.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;

defined( 'ABSPATH' ) || exit;

final class Post_Content extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-content';
	}

	public function get_title(): string {
		return __( 'Post content', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-post-content';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Content', 'studiare-extensions' ) );
		$this->add_post_note();

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Text size', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'md',
				'options' => array(
					'sm' => __( 'Small', 'studiare-extensions' ),
					'md' => __( 'Medium', 'studiare-extensions' ),
					'lg' => __( 'Large', 'studiare-extensions' ),
				),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'       => __( 'Line length', 'studiare-extensions' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'ch' ),
				'range'       => array(
					'px' => array(
						'min' => 480,
						'max' => 1200,
					),
				),
				'description' => __( 'Lines of about 70 characters are the easiest to read. Leave empty to fill the column.', 'studiare-extensions' ),
				'selectors'   => array( '{{WRAPPER}} .stx-prose' => 'max-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Content', 'studiare-extensions' ) );
		$this->add_text_style( 'text', '.stx-prose' );
		$this->add_text_style( 'headings', '.stx-prose :is(h2, h3, h4)', array( 'label' => __( 'Headings', 'studiare-extensions' ) ) );
		$this->add_control(
			'link_color',
			array(
				'label'     => __( 'Link colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-prose' => '--stx-prose-link: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_post(
			static function ( \WP_Post $post ) use ( $s ) {
				echo '<div class="stx-prose stx-prose--' . esc_attr( $s['size'] ) . '">';
				echo Post_Parts::content( $post )['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the post content after WordPress's own filters.
				wp_link_pages(
					array(
						'before' => '<nav class="stx-prose__pages" aria-label="' . esc_attr__( 'Post pages', 'studiare-extensions' ) . '">',
						'after'  => '</nav>',
					)
				);
				echo '</div>';
			}
		);
	}
}
