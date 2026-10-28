<?php
/**
 * Fix: after logging in through the popup, the account page opens logged in.
 *
 * WordPress 6.8+ prints speculation rules that let Chrome prefetch a link
 * the moment a visitor presses it. Studiare's login buttons are links to
 * the account page that open the login popup instead, so pressing one
 * prefetches the account page as a guest. Studiare Core logs in by AJAX
 * (`custom_login_register_action`, `verify_combined_otp`) and then sets
 * `window.location.href` to that same address, and Chrome shows the copy
 * it prefetched: the login form again, until the visitor refreshes.
 *
 * The fix leaves the account, cart and checkout pages out of prefetching:
 * they depend on who is logged in and what is in the cart, so a copy taken
 * earlier is never right for them. LiteSpeed Cache's "Instant Click"
 * (instant.page) prefetches links on hover the same way and does not skip
 * WooCommerce pages, so while it is on, links to them get
 * `data-no-instant`. WP Rocket's "Preload links" already skips them.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

defined( 'ABSPATH' ) || exit;

final class Fresh_Account_Page extends Fix {

	/** WooCommerce pages whose content changes with the session. */
	private const PAGES = array( 'myaccount', 'cart', 'checkout' );

	public function id(): string {
		return 'fresh_account_page';
	}

	public function title(): string {
		return __( 'Account page after popup login', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'After logging in through the login popup, the account page still shows the login form until the page is refreshed: the browser had loaded it in advance while the visitor was a guest. This fix stops the browser from loading the account, cart and checkout pages in advance, so they always open up to date.', 'studiare-extensions' );
	}

	public function boot(): void {
		add_filter( 'wp_speculation_rules_href_exclude_paths', array( $this, 'exclude_paths' ) );
		add_action( 'wp_footer', array( $this, 'skip_instant_click' ) );
	}

	/** Speculative loading (WordPress 6.8+, can be switched off) or LiteSpeed's Instant Click. */
	public function in_use(): bool {
		return ( function_exists( 'wp_get_speculation_rules_configuration' ) && null !== wp_get_speculation_rules_configuration() ) || $this->instant_click_on();
	}

	/**
	 * Marks links to the session pages with `data-no-instant`, which
	 * instant.page reads when the pointer reaches a link. Marked from a
	 * window capture listener, which runs before instant.page's document
	 * listener, so links added later (the login popup, menus) count too.
	 */
	public function skip_instant_click(): void {
		if ( ! $this->instant_click_on() ) {
			return;
		}

		$paths = array();
		foreach ( self::PAGES as $page ) {
			$path = (string) wp_parse_url( (string) wc_get_page_permalink( $page ), PHP_URL_PATH );
			if ( '' !== $path && '/' !== $path ) {
				$paths[] = trailingslashit( $path );
			}
		}
		if ( ! $paths ) {
			return;
		}

		wp_print_inline_script_tag(
			'(function(p){function m(e){var a=e.target&&e.target.closest&&e.target.closest("a[href]");if(a&&a.origin===location.origin&&p.some(function(x){return (a.pathname+"/").indexOf(x)===0;})){a.setAttribute("data-no-instant","");}}["mouseover","touchstart","mousedown"].forEach(function(t){window.addEventListener(t,m,{capture:true,passive:true});});})(' . wp_json_encode( $paths ) . ');',
			// `data-no-defer`: LiteSpeed must not delay this very script.
			array(
				'id'            => 'stx-fresh-account-page',
				'data-no-defer' => true,
			)
		);
	}

	/** Whether LiteSpeed Cache's Instant Click is switched on. */
	private function instant_click_on(): bool {
		return function_exists( 'wc_get_page_permalink' ) && defined( 'LSCWP_V' ) && (bool) apply_filters( 'litespeed_conf', 'util-instant_click' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache's API for reading its settings.
	}

	/**
	 * Adds the session pages (and everything under them, such as the account
	 * endpoints) to the paths the browser may not load in advance.
	 *
	 * @param array $paths Paths relative to the home URL; WordPress adds the home path.
	 */
	public function exclude_paths( $paths ): array {
		$paths = (array) $paths;
		if ( ! function_exists( 'wc_get_page_permalink' ) ) {
			return $paths;
		}

		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		foreach ( self::PAGES as $page ) {
			$path = $this->relative_path( (string) wc_get_page_permalink( $page ), $home );
			if ( '' !== $path ) {
				$paths[] = $path . '*';
			}
		}

		return array_values( array_unique( $paths ) );
	}

	/**
	 * A page's path below the home path, e.g. `/my-account/`, or '' when the
	 * page is missing or is the home page itself.
	 *
	 * @param string $url  Page address.
	 * @param string $home Path of the home URL (`/` or `/blog/`).
	 */
	private function relative_path( string $url, string $home ): string {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( '' === $path || 0 !== strpos( $path, $home ) ) {
			return '';
		}

		$relative = '/' . ltrim( substr( $path, strlen( $home ) ), '/' );

		return '/' === $relative ? '' : trailingslashit( $relative );
	}
}
