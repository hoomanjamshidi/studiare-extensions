<?php
/**
 * Prints Elementor templates outside the post content (headers, footers,
 * single product layouts).
 *
 * Elementor normally discovers its CSS while rendering, which for content
 * printed after `wp_head` means late styles and a flash of unstyled content.
 * enqueue() is therefore called during `wp_enqueue_scripts` for every
 * template the request will print.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Renderer {

	/** @var int[] Templates already enqueued. */
	private static $enqueued = array();

	public static function elementor_ready(): bool {
		return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend );
	}

	/**
	 * Enqueues Elementor's frontend assets, the template's generated CSS and
	 * the module stylesheets and scripts. Must run on `wp_enqueue_scripts`.
	 *
	 * @param int $template_id Template ID.
	 */
	public static function enqueue( int $template_id ): void {
		if ( ! self::elementor_ready() || in_array( $template_id, self::$enqueued, true ) ) {
			return;
		}

		self::$enqueued[] = $template_id;

		$elementor = \Elementor\Plugin::$instance;
		$elementor->frontend->enqueue_styles();

		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			\Elementor\Core\Files\CSS\Post::create( $template_id )->enqueue();
		}

		// A header or footer may hold home page widgets too (a newsletter form, a FAQ…).
		$handles = array( Assets::HANDLE, Assets::HOME_HANDLE );
		if ( in_array( Template_Post_Type::type_of( $template_id ), Schema::BLOG_TYPES, true ) ) {
			$handles[] = Assets::BLOG_HANDLE;
		}

		foreach ( $handles as $handle ) {
			wp_enqueue_style( $handle );
			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Rendered template markup, wrapped for styling hooks.
	 *
	 * @param int $template_id Template ID.
	 */
	public static function render( int $template_id ): string {
		if ( ! self::elementor_ready() ) {
			return '';
		}

		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id );

		if ( '' === trim( (string) $content ) ) {
			return '';
		}

		return sprintf(
			'<div class="stx-tpl stx-tpl--%1$s" data-stx-template="%2$d">%3$s</div>',
			esc_attr( Template_Post_Type::type_of( $template_id ) ),
			$template_id,
			$content
		);
	}
}
