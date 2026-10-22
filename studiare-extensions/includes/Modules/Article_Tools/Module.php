<?php
/**
 * Article tools module: a "Save as PDF" button on every blog post and a
 * podcast player at the top of posts that have an audio version.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Article_Tools;

use StudiareExt\Core\Color;
use StudiareExt\Core\Module as Base_Module;
use StudiareExt\Core\Sanitizer;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Module extends Base_Module {

	public function id(): string {
		return 'article_tools';
	}

	public function title(): string {
		return __( 'Article tools', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'A "Save as PDF" button on every blog post, and a podcast player at the top of posts that have an audio version.', 'studiare-extensions' );
	}

	public function icon(): string {
		return 'headphones';
	}

	public function defaults(): array {
		return Schema::defaults();
	}

	public function sanitize( array $input ): array {
		return Sanitizer::apply( Schema::fields(), $input, Schema::defaults() );
	}

	protected function boot(): void {
		if ( is_admin() ) {
			( new Edit_Box( $this ) )->register();
		} else {
			( new Frontend( $this ) )->register();
		}
	}

	public function render_admin(): void {
		$module = $this;
		require __DIR__ . '/views/admin.php';
	}

	public function enqueue_admin_assets(): void {
		// The preview renders with the real front-end stylesheet.
		wp_enqueue_style( 'stx-article-tools', STUDIARE_EXT_URL . 'assets/modules/article-tools/css/article-tools.css', array(), STUDIARE_EXT_VERSION );

		wp_enqueue_script(
			'stx-article-tools-admin',
			STUDIARE_EXT_URL . 'assets/modules/article-tools/js/article-tools-admin.js',
			array( 'stx-admin' ),
			STUDIARE_EXT_VERSION,
			true
		);
	}

	public function admin_script_data(): array {
		return array(
			'themeAccent' => Theme_Bridge::palette()['primary'],
			'i18n'        => array(
				'pdfLabel'     => __( 'Save as PDF', 'studiare-extensions' ),
				'podcastLabel' => __( 'Listen to this article', 'studiare-extensions' ),
			),
		);
	}

	/**
	 * Inline colour tokens of the player and the PDF button. The accent is
	 * left to the stylesheet (Studiare's primary colour) unless the admin
	 * chose one; the colour on top of it is worked out here for contrast.
	 */
	public function color_vars(): string {
		$accent = $this->settings()['colors']['accent'];
		$vars   = '' !== $accent ? '--stx-at-accent:' . $accent . ';' : '';

		return $vars . '--stx-at-on-accent:' . Color::ink( '' !== $accent ? $accent : Theme_Bridge::palette()['primary'] );
	}
}
