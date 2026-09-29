<?php
/**
 * Front-end assets shared by every template and widget, plus the brand
 * tokens (`--stx-*` custom properties) they are styled with. The home page
 * widgets have their own stylesheet and script (HOME_HANDLE), and so do the
 * blog widgets (BLOG_HANDLE), the about/contact widgets (PAGES_HANDLE) and
 * the slider (SLIDER_HANDLE), so other pages do not load them.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Core\Asset;
use StudiareExt\Core\Persian;
use StudiareExt\Core\Search_Query;

defined( 'ABSPATH' ) || exit;

final class Assets {

	public const HANDLE = 'stx-builder';

	public const HOME_HANDLE = 'stx-builder-home';

	public const BLOG_HANDLE = 'stx-builder-blog';

	/** About us and contact us widgets (contact form, map, contact details, timeline). */
	public const PAGES_HANDLE = 'stx-builder-pages';

	/** Inline-only style: the brand tokens every Studiare+ stylesheet depends on. */
	public const TOKENS_HANDLE = 'stx-tokens';

	/** Slider widget: its own small stylesheet and script, needing only the tokens. */
	public const SLIDER_HANDLE = 'stx-slider';

	/** @var Module */
	private $module;

	/** @var bool */
	private $registered = false;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 1 );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_assets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets(): void {
		if ( $this->registered ) {
			return;
		}
		$this->registered = true;

		// No file: the tokens are small and needed before any Studiare+ stylesheet, so they print inline.
		wp_register_style( self::TOKENS_HANDLE, false, array(), STUDIARE_EXT_VERSION );
		wp_add_inline_style( self::TOKENS_HANDLE, $this->tokens_css() );

		wp_register_style( self::HANDLE, Asset::url( 'assets/modules/builder/css/builder.css' ), array( self::TOKENS_HANDLE ), STUDIARE_EXT_VERSION );

		wp_register_script(
			self::HANDLE,
			Asset::url( 'assets/modules/builder/js/builder.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script(
			self::HANDLE,
			'window.stxBuilder = ' . wp_json_encode(
				array(
					'breakpoint' => (int) $this->module->settings()['breakpoint'],
					'search'     => array(
						'url'      => Live_Search::url(),
						'minChars' => Search_Query::MIN_CHARS,
						'style'    => $this->module->settings()['options']['search_results'],
					),
					'i18n'       => array(
						'close'        => __( 'Close', 'studiare-extensions' ),
						'expand'       => __( 'Show more', 'studiare-extensions' ),
						'collapse'     => __( 'Show less', 'studiare-extensions' ),
						'increase'     => __( 'Increase quantity', 'studiare-extensions' ),
						'decrease'     => __( 'Decrease quantity', 'studiare-extensions' ),
						'searchFailed' => __( 'Live results are unavailable right now. Press Search to see all results.', 'studiare-extensions' ),
					),
				)
			) . ';',
			'before'
		);

		wp_register_style( self::HOME_HANDLE, Asset::url( 'assets/modules/builder/css/home.css' ), array( self::HANDLE ), STUDIARE_EXT_VERSION );
		wp_register_script(
			self::HOME_HANDLE,
			Asset::url( 'assets/modules/builder/js/home.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script(
			self::HOME_HANDLE,
			'window.stxHome = ' . wp_json_encode(
				array(
					'newsletterUrl' => admin_url( 'admin-ajax.php' ),
					'persianDigits' => (bool) $this->module->settings()['options']['persian_digits'] && Persian::is_site_persian(),
					'i18n'          => array(
						'ok'      => __( 'Thank you! You are on the list.', 'studiare-extensions' ),
						'invalid' => __( 'Please enter a valid email address.', 'studiare-extensions' ),
						'busy'    => __( 'Too many attempts. Please try again in a few minutes.', 'studiare-extensions' ),
						'error'   => __( 'That did not work. Please try again.', 'studiare-extensions' ),
					),
				)
			) . ';',
			'before'
		);

		wp_register_style( self::BLOG_HANDLE, Asset::url( 'assets/modules/builder/css/blog.css' ), array( self::HANDLE ), STUDIARE_EXT_VERSION );
		wp_register_script(
			self::BLOG_HANDLE,
			Asset::url( 'assets/modules/builder/js/blog.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script(
			self::BLOG_HANDLE,
			'window.stxBlog = ' . wp_json_encode(
				array(
					'i18n' => array(
						'copied' => __( 'Link copied.', 'studiare-extensions' ),
					),
				)
			) . ';',
			'before'
		);

		wp_register_style( self::PAGES_HANDLE, Asset::url( 'assets/modules/builder/css/pages.css' ), array( self::HANDLE ), STUDIARE_EXT_VERSION );
		wp_register_script(
			self::PAGES_HANDLE,
			Asset::url( 'assets/modules/builder/js/pages.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script(
			self::PAGES_HANDLE,
			'window.stxPages = ' . wp_json_encode(
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'i18n'    => Contact_Messages::error_messages(),
				)
			) . ';',
			'before'
		);

		// Printed inline: a slider usually opens the page, so its few kilobytes of
		// CSS are needed for the first paint anyway, and inline they cost no
		// render-blocking request.
		wp_register_style( self::SLIDER_HANDLE, false, array( self::TOKENS_HANDLE ), STUDIARE_EXT_VERSION );
		wp_add_inline_style( self::SLIDER_HANDLE, Asset::contents( 'assets/modules/builder/css/slider.css' ) );
		wp_register_script(
			self::SLIDER_HANDLE,
			Asset::url( 'assets/modules/builder/js/slider.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/** `:root` custom properties from the brand settings, then the derived tokens (tokens.css). */
	public function tokens_css(): string {
		$brand = $this->module->settings()['brand'];
		$vars  = array();

		foreach ( Schema::BRAND_DEFAULTS as $key => $fallback ) {
			$value  = '' !== $brand[ $key ] ? $brand[ $key ] : $fallback;
			$vars[] = '--stx-' . str_replace( '_', '-', $key ) . ':' . $value;
		}

		$vars[] = '--stx-radius:' . (int) $brand['radius'] . 'px';
		$vars[] = '--stx-bp:' . (int) $this->module->settings()['breakpoint'] . 'px';

		return ':root{' . implode( ';', $vars ) . '}' . self::compact_css( STUDIARE_EXT_DIR . 'assets/modules/builder/css/tokens.css' );
	}

	/**
	 * A small stylesheet without comments and extra whitespace, for printing inline.
	 *
	 * @param string $path Stylesheet path.
	 */
	private static function compact_css( string $path ): string {
		$css = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
		$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );

		return trim( (string) preg_replace( '/\s+/', ' ', $css ) );
	}
}
