<?php
/**
 * Keeps WP Rocket's and LiteSpeed Cache's page optimizations from breaking
 * the plugin's front end. Their page caching needs nothing: the plugin's
 * markup is the same for every guest and every per-visitor answer (audio
 * streams) sets DONOTCACHEPAGE, which both respect.
 *
 * - "Delay JavaScript" loads scripts only at the first tap. LiteSpeed does
 *   not replay that tap and WP Rocket replays it as an untrusted event
 *   (iOS then refuses to start audio), so the first tap on play, a sheet
 *   or the menu button would be lost. The plugin's scripts are small and
 *   already `defer`; they stay out of delaying. WP Rocket (3.17+) also holds
 *   back the tap listeners of scripts it does not delay: the scripts pass
 *   its `isRocket` option, and rocket-taps.js keeps Rocket from cancelling
 *   and replaying taps they already handled.
 * - "Load JavaScript deferred" wraps inline scripts that mention jQuery in a
 *   DOMContentLoaded listener, which runs after the deferred file that needs
 *   them; "Combine JavaScript files" would copy each page's own settings
 *   into a new combined file. The plugin's inline settings (`window.stx…`)
 *   stay as they are.
 * - "Remove unused CSS" keeps only the CSS the page's HTML uses when it is
 *   analysed, so states the scripts add later (`is-playing`, open sheets,
 *   dark mode) would lose their styles. The plugin's stylesheets and inline
 *   styles are kept whole.
 *
 * Filter names and matching were read from WP Rocket 3.24 and LiteSpeed
 * Cache 7.9 (Engine/Optimization/DelayJS/HTML.php, DeferJS/DeferJS.php,
 * Minify/JS/Combine.php, RUCSS/Controller/UsedCSS.php,
 * assets/js/lazyload-scripts.min.js; src/optimize.cls.php, src/ucss.cls.php).
 * Without those plugins the filters simply never run.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Cache_Compat {

	/** Every handle of the plugin starts with this, so do the tag ids WordPress prints. */
	private const HANDLE_PREFIX = 'stx-';

	/** The plugin's inline settings scripts start with their object, `window.stx…`. */
	private const INLINE_MARKER = 'window.stx';

	public function register(): void {
		// WP Rocket.
		add_filter( 'rocket_delay_js_exclusions', array( $this, 'rocket_delay_js' ) );
		add_filter( 'rocket_defer_inline_exclusions', array( $this, 'rocket_inline_js' ) );
		add_filter( 'rocket_excluded_inline_js_content', array( $this, 'rocket_inline_js' ) );
		add_action( 'wp_head', array( $this, 'print_rocket_taps' ), 1 );
		add_filter( 'rocket_rucss_safelist', array( $this, 'rocket_rucss_files' ) );
		add_filter( 'rocket_rucss_inline_atts_exclusions', array( $this, 'rocket_rucss_inline' ) );

		// LiteSpeed Cache ("JS Deferred/Delayed" and the same list in Guest Mode).
		add_filter( 'litespeed_optm_js_defer_exc', array( $this, 'litespeed_js' ) );
		add_filter( 'litespeed_optm_gm_js_exc', array( $this, 'litespeed_js' ) );
		add_filter( 'litespeed_ucss_whitelist', array( $this, 'litespeed_ucss' ) );
	}

	/**
	 * WP Rocket matches each pattern (a regex, with `+`, `?ver` and `#`
	 * escaped) against the whole <script> tag; WordPress gives every tag of
	 * a handle, inline ones included, the id `<handle>-js[-before|-after]`.
	 *
	 * @param array $patterns Excluded patterns.
	 */
	public function rocket_delay_js( $patterns ): array {
		$patterns   = (array) $patterns;
		$patterns[] = 'id=["\']' . self::HANDLE_PREFIX;

		return $patterns;
	}

	/**
	 * Inline scripts "Load JavaScript deferred" and "Combine JavaScript files"
	 * leave in place; both match a substring of the script's text. The defer
	 * filter may hand over a `|`-separated string (older Rocket versions).
	 *
	 * @param array|string $exclusions Excluded snippets.
	 */
	public function rocket_inline_js( $exclusions ): array {
		$exclusions   = is_string( $exclusions ) ? explode( '|', $exclusions ) : (array) $exclusions;
		$exclusions[] = self::INLINE_MARKER;

		return $exclusions;
	}

	/**
	 * Prints rocket-taps.js while WP Rocket delays JavaScript; its own id
	 * keeps it out of delaying like the plugin's other scripts.
	 */
	public function print_rocket_taps(): void {
		if ( ! function_exists( 'get_rocket_option' ) || ! get_rocket_option( 'delay_js' ) ) {
			return;
		}

		wp_print_inline_script_tag( Asset::contents( 'assets/core/js/rocket-taps.js' ), array( 'id' => self::HANDLE_PREFIX . 'rocket-taps' ) );
	}

	/**
	 * Stylesheet files RUCSS keeps whole, in WP Rocket's documented format
	 * (`/wp-content/plugins/some-plugin/(.*).css`).
	 *
	 * @param array $safelist Safelist entries.
	 */
	public function rocket_rucss_files( $safelist ): array {
		$safelist   = (array) $safelist;
		$safelist[] = wp_make_link_relative( STUDIARE_EXT_URL ) . 'assets/(.*).css';

		return $safelist;
	}

	/**
	 * Inline <style> tags RUCSS leaves alone; matched literally against the
	 * tag's attributes (WordPress quoted ids with ' before 6.4 and " since).
	 *
	 * @param array $exclusions Attribute snippets.
	 */
	public function rocket_rucss_inline( $exclusions ): array {
		$exclusions   = (array) $exclusions;
		$exclusions[] = 'id="' . self::HANDLE_PREFIX;
		$exclusions[] = "id='" . self::HANDLE_PREFIX;

		return $exclusions;
	}

	/**
	 * LiteSpeed matches substrings: the script's address for files, its
	 * text for inline scripts (ours all start with `window.stx`).
	 *
	 * @param mixed $exclusions Excluded snippets.
	 */
	public function litespeed_js( $exclusions ): array {
		$exclusions   = (array) $exclusions;
		$exclusions[] = wp_make_link_relative( STUDIARE_EXT_URL ) . 'assets/';
		$exclusions[] = self::INLINE_MARKER;

		return $exclusions;
	}

	/**
	 * Selectors UCSS always keeps (`*` is LiteSpeed's wildcard). Every rule
	 * of the plugin names one of its `stx-` classes.
	 *
	 * @param mixed $selectors Allowed selectors.
	 */
	public function litespeed_ucss( $selectors ): array {
		$selectors   = (array) $selectors;
		$selectors[] = '.' . self::HANDLE_PREFIX . '*';
		$selectors[] = '*' . self::HANDLE_PREFIX . '*';

		return $selectors;
	}
}
