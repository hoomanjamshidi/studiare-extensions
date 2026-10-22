<?php
/**
 * Front end of the article tools: adds the podcast player and the "Save as
 * PDF" button to the content of the blog post being viewed, and loads their
 * assets on those posts only.
 *
 * Everything is added through `the_content`, so it shows in Studiare's own
 * post layout, in Studiare+ blog templates (whose content widget runs the
 * same filter) and in posts built with Elementor alike. Nothing depends on
 * the visitor's device, so the output is safe with page caching.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Article_Tools;

use StudiareExt\Core\Asset;
use StudiareExt\Core\Persian;

defined( 'ABSPATH' ) || exit;

final class Frontend {

	private const HANDLE = 'stx-article-tools';

	/** After wpautop (10) and shortcodes (11), so our markup is left as written. */
	private const CONTENT_PRIORITY = 20;

	/** @var Module */
	private $module;

	/** @var array<int, array{pdf:bool, podcast:?array}> What each post shows, per request. */
	private $parts = array();

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ), self::CONTENT_PRIORITY );
	}

	public function enqueue_assets(): void {
		$post = $this->viewed_post();
		if ( ! $post || ! $this->has_parts( $post ) ) {
			return;
		}

		wp_enqueue_style( self::HANDLE, Asset::url( 'assets/modules/article-tools/css/article-tools.css' ), array(), STUDIARE_EXT_VERSION );
		wp_add_inline_style( self::HANDLE, $this->page_numbers_css() );

		wp_enqueue_script(
			self::HANDLE,
			Asset::url( 'assets/modules/article-tools/js/article-tools.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script( self::HANDLE, 'window.stxArticleTools = ' . wp_json_encode( $this->script_data() ) . ';', 'before' );
	}

	/**
	 * Adds the tools around the content of the post being viewed.
	 *
	 * @param string $content Filtered post content.
	 */
	public function filter_content( $content ) {
		$post = $this->is_article_content() ? $this->viewed_post() : null;
		if ( ! $post || ! $this->has_parts( $post ) ) {
			return $content;
		}

		$parts    = $this->parts( $post );
		$settings = $this->module->settings()['pdf'];
		$before   = '';
		$after    = '';

		if ( $parts['pdf'] ) {
			$before .= $this->print_head( $post );
			$before .= 'bottom' !== $settings['position'] ? $this->pdf_button( 'top' ) : '';
			$after  .= 'top' !== $settings['position'] ? $this->pdf_button( 'bottom' ) : '';
			$after  .= $settings['source'] ? $this->print_foot( $post ) : '';
		}

		if ( $parts['podcast'] ) {
			$before .= $this->podcast( $parts['podcast'] );
		}

		return $before . $content . $after;
	}

	/**
	 * Whether this `the_content` call prints the main text of the viewed post,
	 * rather than an excerpt, a meta description or a related post.
	 */
	private function is_article_content(): bool {
		if ( doing_filter( 'get_the_excerpt' ) || doing_action( 'wp_head' ) || is_feed() || is_embed() ) {
			return false;
		}

		return (int) get_the_ID() === (int) get_queried_object_id();
	}

	/** The blog post being viewed, when the tools may appear on it. */
	private function viewed_post(): ?\WP_Post {
		if ( ! is_singular( 'post' ) || $this->is_page_builder_preview() ) {
			return null;
		}

		$post = get_post( get_queried_object_id() );

		// A password form stands in for the content until the password is given.
		return $post instanceof \WP_Post && ! post_password_required( $post ) ? $post : null;
	}

	/** Inside Elementor's editing canvas the tools would sit on top of the post being designed. */
	private function is_page_builder_preview(): bool {
		return class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance->preview )
			&& \Elementor\Plugin::$instance->preview->is_preview_mode();
	}

	/**
	 * @param \WP_Post $post Post.
	 */
	private function has_parts( \WP_Post $post ): bool {
		$parts = $this->parts( $post );

		return $parts['pdf'] || null !== $parts['podcast'];
	}

	/**
	 * What a post shows: the PDF button (unless switched off for the post)
	 * and its podcast, if it has one.
	 *
	 * @param \WP_Post $post Post.
	 * @return array{pdf:bool, podcast:?array}
	 */
	private function parts( \WP_Post $post ): array {
		if ( ! isset( $this->parts[ $post->ID ] ) ) {
			$settings = $this->module->settings();

			$this->parts[ $post->ID ] = array(
				'pdf'     => $settings['pdf']['enabled'] && '1' !== get_post_meta( $post->ID, Edit_Box::META_PDF_OFF, true ),
				'podcast' => $settings['podcast']['enabled'] ? Podcast::view( $post ) : null,
			);
		}

		return $this->parts[ $post->ID ];
	}

	/**
	 * @param string $place `top` or `bottom`.
	 */
	private function pdf_button( string $place ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- read by the included view.
		$label = $this->module->settings()['pdf']['label'];
		$label = '' !== $label ? $label : __( 'Save as PDF', 'studiare-extensions' );
		$style = $this->module->color_vars();

		ob_start();
		require __DIR__ . '/views/pdf.php';

		return (string) ob_get_clean();
	}

	/**
	 * @param array $pod Podcast::view().
	 */
	private function podcast( array $pod ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- read by the included view.
		$options = $this->module->settings()['podcast'];
		$style   = $this->module->color_vars();

		ob_start();
		require __DIR__ . '/views/podcast.php';

		return (string) ob_get_clean();
	}

	/**
	 * @param \WP_Post $post Post.
	 */
	private function print_head( \WP_Post $post ): string {
		$date    = $this->post_date( $post );
		$picture = (bool) $this->module->settings()['pdf']['picture'];

		ob_start();
		require __DIR__ . '/views/print-head.php';

		return (string) ob_get_clean();
	}

	/**
	 * @param \WP_Post $post Post.
	 */
	private function print_foot( \WP_Post $post ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- read by the included view.
		ob_start();
		require __DIR__ . '/views/print-foot.php';

		return (string) ob_get_clean();
	}

	/**
	 * Publish date: Jalali on Persian sites, the site's date format elsewhere.
	 *
	 * @param \WP_Post $post Post.
	 */
	private function post_date( \WP_Post $post ): string {
		$date = get_post_datetime( $post );

		if ( $date && Persian::is_site_persian() ) {
			return Persian::digits( Persian::jalali_date( $date ) );
		}

		return (string) get_the_date( '', $post );
	}

	/**
	 * Page numbers at the foot of each printed page, in Persian digits on
	 * Persian sites. Browsers that draw page margin boxes (Chrome 131+) also
	 * leave out their own date/address header and footer then.
	 */
	private function page_numbers_css(): string {
		$style = Persian::is_site_persian() ? 'persian' : 'decimal';

		return '@media print{@page{@bottom-center{content:counter(page,' . $style . ');font-size:9pt;color:#6b7280}}}';
	}

	/** Settings and strings for article-tools.js. */
	private function script_data(): array {
		return array(
			'persian' => Persian::is_site_persian(),
			'i18n'    => array(
				'play'      => __( 'Play', 'studiare-extensions' ),
				'pause'     => __( 'Pause', 'studiare-extensions' ),
				/* translators: %s: playback speed, e.g. 1.5×. */
				'speed'     => __( 'Playback speed: %s', 'studiare-extensions' ),
				/* translators: 1: time listened, 2: length of the episode. */
				'position'  => __( '%1$s of %2$s', 'studiare-extensions' ),
				'preparing' => __( 'Preparing…', 'studiare-extensions' ),
			),
		);
	}
}
