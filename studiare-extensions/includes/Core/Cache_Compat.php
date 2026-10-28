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
 *   already `defer`; they stay out of delaying.
 * - "Remove unused CSS" keeps only the CSS the page's HTML uses when it is
 *   analysed, so states the scripts add later (`is-playing`, open sheets,
 *   dark mode) would lose their styles. The plugin's stylesheets and inline
 *   styles are kept whole.
 *
 * Filter names and matching were read from WP Rocket 3.24 and LiteSpeed
 * Cache 7.9 (Engine/Optimization/DelayJS/HTML.php, RUCSS/Controller/UsedCSS.php;
 * src/optimize.cls.php, src/ucss.cls.php). Without those plugins the
 * filters simply never run.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Cache_Compat {

	/** Every handle of the plugin starts with this, so do the tag ids WordPress prints. */
	private const HANDLE_PREFIX = 'stx-';

	public function register(): void {
		// WP Rocket.
		add_filter( 'rocket_delay_js_exclusions', array( $this, 'rocket_delay_js' ) );
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
		$exclusions[] = 'window.stx';

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
