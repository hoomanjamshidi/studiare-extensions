<?php
/**
 * Post title (H1), with the category as a small link above it and the
 * hand-written excerpt as a lead paragraph below it.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;

defined( 'ABSPATH' ) || exit;

final class Post_Title extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-title';
	}

	public function get_title(): string {
		return __( 'Post title', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-post-title';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Title', 'studiare-extensions' ) );
		$this->add_post_note();

		$this->add_control(
			'category',
			array(
				'label'   => __( 'Category above the title', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'lead',
			array(
				'label'       => __( 'Excerpt below the title', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Shows the excerpt written for the post (none when it is empty).', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Size', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'lg',
				'options' => array(
					'md' => __( 'Medium', 'studiare-extensions' ),
					'lg' => __( 'Large', 'studiare-extensions' ),
					'xl' => __( 'Extra large', 'studiare-extensions' ),
				),
			)
		);

		$this->add_align_control( 'align', '.stx-posttitle' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Title', 'studiare-extensions' ) );
		$this->add_text_style( 'title', '.stx-posttitle__h' );
		$this->add_text_style( 'cat', '.stx-posttitle__cat', array( 'label' => __( 'Category', 'studiare-extensions' ) ) );
		$this->add_text_style( 'lead', '.stx-posttitle__lead', array( 'label' => __( 'Excerpt', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_post(
			static function ( \WP_Post $post ) use ( $s ) {
				echo '<div class="stx-posttitle stx-posttitle--' . esc_attr( $s['size'] ) . '">';

				if ( 'yes' === $s['category'] ) {
					$terms = Post_Parts::terms( $post, 'category' );
					if ( $terms ) {
						printf( '<a class="stx-posttitle__cat" href="%1$s">%2$s</a>', esc_url( (string) get_term_link( $terms[0] ) ), esc_html( html_entity_decode( $terms[0]->name, ENT_QUOTES, 'UTF-8' ) ) );
					}
				}

				echo '<h1 class="stx-posttitle__h">' . esc_html( get_the_title( $post ) ) . '</h1>';

				if ( 'yes' === $s['lead'] && has_excerpt( $post ) ) {
					echo '<p class="stx-posttitle__lead">' . esc_html( wp_strip_all_tags( get_the_excerpt( $post ) ) ) . '</p>';
				}

				echo '</div>';
			}
		);
	}
}
