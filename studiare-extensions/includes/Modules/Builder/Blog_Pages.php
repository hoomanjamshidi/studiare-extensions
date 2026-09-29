<?php
/**
 * Renders blog pages with an Elementor template instead of Studiare's
 * layout: post lists (the posts page, categories, tags, authors, dates and
 * optionally search results) and single posts. The theme header and footer
 * stay in place, and so do WordPress's comments, which the Comments widget
 * prints with Studiare's own comment template.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Blog_Pages {

	/** @var Module */
	private $module;

	/** @var int Template for this request (0 = theme layout). */
	private $template_id = 0;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp', array( $this, 'setup' ) );
		add_filter( 'template_include', array( $this, 'template_include' ), 99 );
	}

	/** Resolves the template once the main query is known. */
	public function setup(): void {
		if ( is_admin() || is_feed() || is_embed() || ! Renderer::elementor_ready() ) {
			return;
		}

		$this->template_id = $this->module->resolver()->blog_template();

		if ( ! $this->template_id ) {
			return;
		}

		add_action(
			'wp_enqueue_scripts',
			function () {
				Renderer::enqueue( $this->template_id );
			},
			20
		);

		add_filter( 'body_class', array( $this, 'body_class' ) );

		if ( $this->module->settings()['options']['hide_theme_title'] ) {
			// The template brings its own title and breadcrumb.
			add_filter( 'load_studipage_title', '__return_empty_string', PHP_INT_MAX );
		}

		if ( $this->module->resolver()->is_preview() ) {
			nocache_headers();
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
		}
	}

	/**
	 * @param string $template Template file chosen by WordPress.
	 */
	public function template_include( $template ) {
		if ( $this->template_id && '' !== $this->module->resolver()->blog_kind() ) {
			return __DIR__ . '/views/blog.php';
		}

		return $template;
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		$classes[] = 'stx-tpl-page';
		$classes[] = 'stx-tpl-page--' . Template_Post_Type::type_of( $this->template_id );

		return $classes;
	}

	public function template_id(): int {
		return $this->template_id;
	}
}
