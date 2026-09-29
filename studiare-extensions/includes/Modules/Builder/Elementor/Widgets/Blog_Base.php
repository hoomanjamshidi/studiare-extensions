<?php
/**
 * Base for the blog widgets (post title, content, table of contents, share
 * buttons…). They add the blog stylesheet and script, so pages without them
 * never load those files.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use StudiareExt\Modules\Builder\Assets;
use StudiareExt\Modules\Builder\Context;

defined( 'ABSPATH' ) || exit;

abstract class Blog_Base extends Base {

	public function get_style_depends(): array {
		return array( Assets::HANDLE, Assets::BLOG_HANDLE );
	}

	public function get_script_depends(): array {
		return array( Assets::HANDLE, Assets::BLOG_HANDLE );
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'blog', 'post', 'article', 'وبلاگ', 'مقاله' ) );
	}

	/**
	 * Runs a render callback with the post as the global post. In the editor,
	 * prints a hint when there is no post to show.
	 *
	 * @param callable $callback Receives the post.
	 */
	protected function with_post( callable $callback ): void {
		$post = Context::post();

		if ( $post ) {
			Context::run_post( $post, $callback );
			return;
		}

		$this->editor_hint( __( 'Publish a blog post to see real data here.', 'studiare-extensions' ) );
	}

	/** Editor note explaining where post data comes from. */
	protected function add_post_note(): void {
		$this->add_control(
			'stx_post_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Shows the post being viewed. While editing, the newest post is used.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);
	}
}
