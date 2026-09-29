<?php
/**
 * Replaces the theme header/footer with Elementor templates — one per device.
 *
 * Studiare prints its header between the top bar template part and the
 * `load_studipage_title` filter, and its footer between `get_footer` and
 * `wp_footer`. Those regions are captured with output buffering, so each
 * device slot can show a template, nothing, or the theme's own markup.
 * Both device variants are printed and switched with a CSS media query,
 * which keeps full-page caching intact (no server-side device sniffing).
 *
 * Other themes get a best-effort fallback: our header on `wp_body_open`,
 * our footer before `wp_footer`, and common theme header/footer selectors
 * hidden with CSS.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Header_Footer {

	/** Studiare template parts that open the header region. */
	private const HEADER_PARTS = '#/inc/templates/header/(top-bar|header-main|header-two)$|/elementor/templates/el_header$#';

	/** @var Module */
	private $module;

	/** @var array{desktop:int|string, mobile:int|string}|null */
	private $header = null;

	/** @var array{desktop:int|string, mobile:int|string}|null */
	private $footer = null;

	/** @var int|null Output buffer level our capture started at. */
	private $header_level = null;

	/** @var int|null */
	private $footer_level = null;

	/** @var bool */
	private $header_printed = false;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp', array( $this, 'setup' ) );
	}

	public function setup(): void {
		if ( is_admin() || is_feed() || is_embed() || wp_doing_ajax() || ! Renderer::elementor_ready() || $this->is_part_template() ) {
			return;
		}

		$header = $this->module->resolver()->slots( 'header' );
		$footer = $this->module->resolver()->slots( 'footer' );

		$this->header = $this->is_theme_only( $header ) ? null : $header;
		$this->footer = $this->is_theme_only( $footer ) ? null : $footer;

		if ( ! $this->header && ! $this->footer ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );

		if ( $this->module->resolver()->is_preview() ) {
			nocache_headers();
		}

		if ( Theme_Bridge::is_active() ) {
			if ( $this->header ) {
				add_action( 'get_template_part', array( $this, 'maybe_capture_header' ), 10, 1 );
				add_filter( 'load_studipage_title', array( $this, 'finish_header' ), PHP_INT_MIN );
			}
			if ( $this->footer ) {
				add_action( 'get_footer', array( $this, 'capture_footer' ), PHP_INT_MAX );
				add_action( 'wp_footer', array( $this, 'finish_footer' ), PHP_INT_MIN );
			}
			return;
		}

		if ( $this->header ) {
			add_action( 'wp_body_open', array( $this, 'print_header' ), 5 );
		}
		if ( $this->footer ) {
			add_action( 'wp_footer', array( $this, 'print_footer' ), 5 );
		}
	}

	public function enqueue(): void {
		foreach ( array( $this->header, $this->footer ) as $slots ) {
			foreach ( (array) $slots as $slot ) {
				if ( is_int( $slot ) ) {
					Renderer::enqueue( $slot );
				}
			}
		}

		wp_enqueue_style( Assets::HANDLE );
		wp_enqueue_script( Assets::HANDLE );
		wp_add_inline_style( Assets::HANDLE, $this->inline_css() );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( $this->header ) {
			$classes[] = 'stx-hf-header-on';
		}
		if ( $this->footer ) {
			$classes[] = 'stx-hf-footer-on';
		}

		return $classes;
	}

	/* ---------------------------------------------------------------------
	 * Studiare: header
	 * ------------------------------------------------------------------- */

	/**
	 * Starts capturing when the theme begins printing its header region.
	 *
	 * @param string $slug Template part slug.
	 */
	public function maybe_capture_header( $slug ): void {
		if ( null !== $this->header_level || $this->header_printed || ! preg_match( self::HEADER_PARTS, (string) $slug ) ) {
			return;
		}

		ob_start();
		$this->header_level = ob_get_level();
	}

	/**
	 * Runs right after the theme header: swaps the captured markup for ours.
	 * Hooked to a filter, so it passes the value through untouched.
	 *
	 * @param mixed $value Page title markup (built by later callbacks).
	 * @return mixed
	 */
	public function finish_header( $value ) {
		if ( $this->header_printed ) {
			return $value;
		}

		$theme_html = '';
		if ( null !== $this->header_level && ob_get_level() === $this->header_level ) {
			$theme_html = (string) ob_get_clean();
		}
		$this->header_level = null;

		echo $this->markup( 'header', $this->header, $theme_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor/theme markup.
		$this->header_printed = true;

		return $value;
	}

	/* ---------------------------------------------------------------------
	 * Studiare: footer
	 * ------------------------------------------------------------------- */

	public function capture_footer(): void {
		if ( null !== $this->footer_level ) {
			return;
		}

		ob_start();
		$this->footer_level = ob_get_level();
	}

	/** Runs first on `wp_footer`: replaces the theme footer inside the captured HTML. */
	public function finish_footer(): void {
		if ( null === $this->footer_level ) {
			return;
		}

		if ( ob_get_level() !== $this->footer_level ) {
			// Someone else's buffer is still open: leave everything as it is.
			$this->footer_level = null;
			return;
		}

		$html               = (string) ob_get_clean();
		$this->footer_level = null;

		echo $this->replace_theme_footer( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme/Elementor markup.
	}

	/**
	 * @param string $html Everything footer.php printed before `wp_footer`.
	 */
	private function replace_theme_footer( string $html ): string {
		$start_markers = array( '<footer id="footer"', '<!-- new elementor footer start -->' );
		$end_marker    = '<!-- new elementor footer end -->';

		$start = false;
		foreach ( $start_markers as $marker ) {
			$position = strpos( $html, $marker );
			if ( false !== $position && ( false === $start || $position < $start ) ) {
				$start = $position;
			}
		}

		$end = false !== $start ? strpos( $html, $end_marker, $start ) : false;

		if ( false !== $start && false !== $end ) {
			$end       += strlen( $end_marker );
			$theme_html = substr( $html, $start, $end - $start );

			return substr( $html, 0, $start ) . $this->footer_anchor() . $this->markup( 'footer', $this->footer, $theme_html ) . substr( $html, $end );
		}

		if ( $this->theme_footer_disabled() ) {
			return $html;
		}

		// Unknown footer markup: print ours before the wrapper closes and hide the theme's.
		$ours  = $this->markup( 'footer', $this->footer, '' ) . '<style>#footer:not(.stx-hf #footer){display:none!important}</style>';
		$close = strrpos( $html, '<!-- end .wrap -->' );
		if ( false !== $close ) {
			$div = strrpos( substr( $html, 0, $close ), '</div>' );
			if ( false !== $div ) {
				return substr( $html, 0, $div ) . $ours . substr( $html, $div );
			}
		}

		return $html . $ours;
	}

	/**
	 * Empty stand-in for the theme's `#footer` once it is replaced, at the
	 * same spot: Studiare's inline script on product pages reads
	 * `$('#footer').offset().top` on every scroll (to hide its fixed buy
	 * button near the footer) and throws when the element is missing.
	 */
	private function footer_anchor(): string {
		return in_array( 'theme', $this->footer, true ) ? '' : '<div id="footer" class="stx-hf__anchor" aria-hidden="true"></div>';
	}

	/** Studiare lets single pages switch the footer off; respect that. */
	private function theme_footer_disabled(): bool {
		$post_id = is_singular() ? (int) get_queried_object_id() : 0;

		return $post_id && (
			'off' === get_post_meta( $post_id, '_studiare_footer_type', true )
			|| 'off' === get_post_meta( $post_id, '_studiare_footerr_type', true )
		);
	}

	/* ---------------------------------------------------------------------
	 * Other themes
	 * ------------------------------------------------------------------- */

	public function print_header(): void {
		if ( $this->header_printed ) {
			return;
		}

		echo $this->markup( 'header', $this->header, '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->header_printed = true;
	}

	public function print_footer(): void {
		echo $this->markup( 'footer', $this->footer, '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/* ---------------------------------------------------------------------
	 * Markup
	 * ------------------------------------------------------------------- */

	/**
	 * Header/footer wrapper with one slot per device. Identical slots are
	 * printed once.
	 *
	 * @param string $area       `header` or `footer`.
	 * @param array  $slots      Resolved slots.
	 * @param string $theme_html Captured theme markup for `theme` slots.
	 */
	private function markup( string $area, array $slots, string $theme_html ): string {
		$render = static function ( $slot ) use ( $theme_html ): string {
			if ( 'theme' === $slot ) {
				return $theme_html;
			}

			return is_int( $slot ) ? Renderer::render( $slot ) : '';
		};

		if ( $slots['desktop'] === $slots['mobile'] ) {
			$inner = '<div class="stx-hf__slot">' . $render( $slots['desktop'] ) . '</div>';
		} else {
			$inner = '<div class="stx-hf__slot stx-hf__slot--desktop">' . $render( $slots['desktop'] ) . '</div>'
				. '<div class="stx-hf__slot stx-hf__slot--mobile">' . $render( $slots['mobile'] ) . '</div>';
		}

		$attrs = '';
		if ( 'header' === $area ) {
			$settings = $this->module->settings()['header'];
			$attrs    = sprintf(
				' data-stx-sticky-desktop="%s" data-stx-sticky-mobile="%s"',
				esc_attr( is_int( $slots['desktop'] ) ? $settings['sticky_desktop'] : 'none' ),
				esc_attr( is_int( $slots['mobile'] ) ? $settings['sticky_mobile'] : 'none' )
			);
		}

		return sprintf( '<div class="stx-hf stx-hf--%1$s"%2$s>%3$s</div>', esc_attr( $area ), $attrs, $inner );
	}

	/** Device switch and fallback hiding rules. */
	private function inline_css(): string {
		$bp  = (int) $this->module->settings()['breakpoint'];
		$css = sprintf(
			'@media (max-width:%1$dpx){.stx-hf__slot--desktop{display:none!important}}@media (min-width:%2$dpx){.stx-hf__slot--mobile{display:none!important}}',
			$bp,
			$bp + 1
		);

		if ( ! Theme_Bridge::is_active() ) {
			/**
			 * Selectors of the active theme's own header/footer, hidden when a
			 * Studiare+ template replaces them on non-Studiare themes.
			 *
			 * @param array{header: string[], footer: string[]} $selectors Selectors.
			 */
			$selectors = apply_filters(
				'studiare_ext_builder_theme_selectors',
				array(
					'header' => array( '#masthead', 'body > header.site-header', '.site > header.site-header', '#site-header' ),
					'footer' => array( '#colophon', 'body > footer.site-footer', '.site > footer.site-footer', '#site-footer' ),
				)
			);

			foreach ( array( 'header', 'footer' ) as $area ) {
				if ( $this->{$area} && ! in_array( 'theme', $this->{$area}, true ) && ! empty( $selectors[ $area ] ) ) {
					$css .= implode( ',', array_map( 'strval', $selectors[ $area ] ) ) . '{display:none!important}';
				}
			}
		}

		return $css;
	}

	/**
	 * @param array $slots Resolved slots.
	 */
	private function is_theme_only( array $slots ): bool {
		return 'theme' === $slots['desktop'] && 'theme' === $slots['mobile'];
	}

	/**
	 * Whether a header, footer or single template is being viewed on its own.
	 * Page designs (home, about, contact) are whole pages, so they get the site
	 * header and footer.
	 */
	private function is_part_template(): bool {
		return is_singular( Template_Post_Type::POST_TYPE ) && ! in_array( Template_Post_Type::type_of( (int) get_queried_object_id() ), Schema::PAGE_TYPES, true );
	}
}
