<?php
/**
 * Comments and the comment form, printed by the theme's own comment template
 * (Studiare's comments.php), so replies, votes and the theme's styling keep
 * working. The widget only adds a heading and the box around it.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Post_Comments extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-comments';
	}

	public function get_title(): string {
		return __( 'Comments', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-comments';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Comments', 'studiare-extensions' ) );

		$this->add_control(
			'note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Shows the comments and the comment form of the post being viewed, as your theme prints them. Comments are switched on or off per post and in Settings → Discussion.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'boxed',
			array(
				'label'       => __( 'In a card', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Studiare already puts its comment form in a box; a card adds one around the comments too.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( $this->in_editor() || ! is_singular() ) {
			$this->editor_hint( __( 'The comments and the comment form appear here on the post.', 'studiare-extensions' ) );
			return;
		}

		if ( ! comments_open() && ! get_comments_number() ) {
			return;
		}

		echo '<div class="stx-comments' . ( 'yes' === $s['boxed'] ? ' stx-comments--boxed' : '' ) . '">';
		comments_template();
		echo '</div>';
	}
}
