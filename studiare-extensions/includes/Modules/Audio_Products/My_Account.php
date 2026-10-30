<?php
/**
 * "My audio" tab of the WooCommerce account page: the audio products the
 * customer bought, one card each, so they can listen again without looking
 * for the product. A card opens that product's player on its own page
 * (`/my-account/stx-audio/<product ID>/`, the endpoint's value), so a long
 * library never becomes one endless page of players.
 *
 * Registered as a WooCommerce query var, so WooCommerce adds the endpoint,
 * and every menu of Studiare's account page (top icons, mobile menu,
 * breadcrumb; all built from `wc_get_account_menu_items()`) lists it. The
 * tab shows to every logged-in customer, with a note when they own no
 * audio yet, so customers always know where to find their audio. The
 * Studiare+ thank-you page links each bought audio product to its player
 * here, and (`account.courses`) Studiare's "My courses" tab sends audio
 * courses here too.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class My_Account {

	public const ENDPOINT = 'stx-audio';

	/** @var Module */
	private $module;

	/** @var int[]|null Audio products the current customer owns. */
	private $owned = null;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		if ( empty( $this->module->settings()['account']['enabled'] ) ) {
			return;
		}

		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_var' ) );
		add_action( 'init', array( $this, 'maybe_flush_rules' ), 99 );
		// After Studiare's own menu filters (98: log out last, 99: hidden tabs), so a chosen place counts in the final menu.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ), 100 );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( $this, 'title' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'menu_icon_css' ) );
		add_filter( 'studiare_ext_thankyou_item_links', array( $this, 'thankyou_link' ), 10, 2 );

		if ( ! empty( $this->module->settings()['account']['courses'] ) ) {
			add_action( 'template_redirect', array( $this, 'course_to_player' ) );
		}
	}

	/**
	 * Studiare's "My courses" tab (`/my-account/my-courses/?courseid=<ID>`,
	 * plain `inc/lib/sc-courses/sc-courses-class.php`, no filter for the card
	 * link) shows a course's lesson list. An audio course has no lessons, so
	 * its buyer is sent to the course's player here instead. Courses with
	 * lessons, and products the visitor does not own, stay with the theme.
	 */
	public function course_to_player(): void {
		global $wp;

		if ( ! is_user_logged_in() || ! isset( $wp->query_vars['my-courses'] ) ) {
			return;
		}

		// The theme registers `courseid` as a WooCommerce query var; read the link's own value if it did not.
		$product_id = absint( get_query_var( 'courseid' ) ? get_query_var( 'courseid' ) : wp_unslash( $_GET['courseid'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a read-only link, absint() cleans it.
		if ( ! $product_id || Theme_Bridge::has_lessons( $product_id ) || ! in_array( $product_id, $this->owned_products(), true ) ) {
			return;
		}

		$album = $this->album( $product_id );
		if ( $album ) {
			wp_safe_redirect( $album['url'] );
			exit;
		}
	}

	/**
	 * "Listen" on the thank-you page's card of a bought audio product: its
	 * player on this tab, ready to play. Its `collection` (this tab) is the
	 * page's main button when every item bought is audio.
	 *
	 * @param array       $links   Buttons so far.
	 * @param \WC_Product $product Bought product.
	 */
	public function thankyou_link( $links, $product ): array {
		$links = (array) $links;
		$album = $product instanceof \WC_Product && in_array( $product->get_id(), $this->owned_products(), true ) ? $this->album( $product->get_id() ) : null;
		if ( $album ) {
			$links[] = array(
				'kind'       => 'audio',
				'label'      => __( 'Listen', 'studiare-extensions' ),
				'url'        => $album['url'],
				'collection' => array(
					/* translators: %s: name of the account tab, e.g. "My audio". */
					'label' => sprintf( __( 'Go to %s', 'studiare-extensions' ), $this->title() ),
					'url'   => wc_get_account_endpoint_url( self::ENDPOINT ),
				),
			);
		}

		return $links;
	}

	/**
	 * @param array $vars WooCommerce query vars.
	 */
	public function add_query_var( $vars ): array {
		$vars                   = (array) $vars;
		$vars[ self::ENDPOINT ] = self::ENDPOINT;

		return $vars;
	}

	/**
	 * Rebuilds the rewrite rules when they lack the endpoint (first run, or
	 * rules rebuilt while the tab was off), after WooCommerce added it.
	 */
	public function maybe_flush_rules(): void {
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) ) {
			return; // Plain permalinks: endpoints work as query strings.
		}

		foreach ( array_keys( $rules ) as $pattern ) {
			if ( false !== strpos( (string) $pattern, self::ENDPOINT ) ) {
				return;
			}
		}

		flush_rewrite_rules( false );
	}

	/**
	 * Adds the tab after "Downloads" (or before "Log out").
	 *
	 * @param array $items Endpoint => label.
	 */
	public function add_menu_item( $items ): array {
		$items = (array) $items;
		if ( ! is_user_logged_in() ) {
			return $items;
		}

		unset( $items[ self::ENDPOINT ] );
		$keys     = array_keys( $items );
		$position = (int) $this->module->settings()['account']['position'];
		$index    = $position > 0 ? $position - 1 : self::automatic_index( $keys );

		// "Log out" stays last.
		$logout = array_search( 'customer-logout', $keys, true );
		$index  = min( $index, false === $logout ? count( $keys ) : $logout );

		return array_slice( $items, 0, $index, true ) + array( self::ENDPOINT => $this->title() ) + array_slice( $items, $index, null, true );
	}

	/**
	 * The automatic place: after Downloads, else after Orders, else last.
	 *
	 * @param string[] $keys Menu endpoints in order.
	 */
	private static function automatic_index( array $keys ): int {
		foreach ( array( 'downloads', 'orders' ) as $after ) {
			$found = array_search( $after, $keys, true );
			if ( false !== $found ) {
				return $found + 1;
			}
		}

		return count( $keys );
	}

	public function title(): string {
		$label = $this->module->settings()['account']['label'];

		return '' !== $label ? $label : __( 'My audio', 'studiare-extensions' );
	}

	/**
	 * Headphones for the tab in Studiare's account menus, in the theme's own
	 * Font Awesome so it matches the other items. Studiare picks each icon in
	 * woocommerce-dashboard.css by the item's class and falls back to a list
	 * icon; the top icon bar adds the endpoint as a class on its <i>. The
	 * selectors repeat Studiare's, so they win by coming later.
	 */
	public function menu_icon_css(): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
			return;
		}

		$link      = '.woocommerce-MyAccount-navigation-link--' . self::ENDPOINT;
		$selectors = array(
			'.woocommerce-account .woocommerce-MyAccount-navigation ul li' . $link . ' a:before', // Side menu.
			'.top_accoun_nav_item' . $link . ' a:before', // Top bar and mobile menu.
			'.fa-list.' . self::ENDPOINT . '::before', // Top icon bar.
		);

		echo '<style id="stx-audio-menu-icon">' . esc_html( implode( ',', $selectors ) ) . '{content:"\\f025"}</style>' . "\n";
	}

	public function enqueue_assets(): void {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( self::ENDPOINT ) ) {
			Frontend::enqueue();
		}
	}

	/**
	 * The library, or one product's player when the address names a product
	 * the customer owns (anything else falls back to the library).
	 *
	 * @param string $value Endpoint value: a product ID, or ''.
	 */
	public function render( $value = '' ): void {
		$product_id = absint( $value );
		$product    = $product_id && in_array( $product_id, $this->owned_products(), true ) ? wc_get_product( $product_id ) : null;
		$player     = $product ? $this->module->player( $product, 'account' ) : '';
		$style      = $this->module->color_vars();

		if ( '' !== $player ) {
			$back       = wc_get_account_endpoint_url( self::ENDPOINT );
			$back_label = $this->title();
			require __DIR__ . '/views/account-album.php';
			return;
		}

		$albums = array();
		foreach ( $this->owned_products() as $owned_id ) {
			$album = $this->album( $owned_id );
			if ( $album ) {
				$albums[] = $album;
			}
		}

		require __DIR__ . '/views/account.php';
	}

	/**
	 * One card of the library: what the customer can hear of a product.
	 *
	 * @param int $product_id Owned product.
	 * @return array{title:string, cover_id:int, facts:string[], url:string}|null Null without tracks.
	 */
	private function album( int $product_id ): ?array {
		$product = wc_get_product( $product_id );
		$tracks  = $product ? ( new Playlist( $product_id, get_current_user_id(), $this->module->settings() ) )->tracks() : array();
		if ( ! $tracks ) {
			return null;
		}

		return array(
			'title'    => $product->get_name(),
			'cover_id' => (int) $product->get_image_id(),
			'facts'    => Player::facts( count( $tracks ), (int) array_sum( array_column( $tracks, 'duration' ) ) ),
			'url'      => wc_get_endpoint_url( self::ENDPOINT, (string) $product_id, wc_get_page_permalink( 'myaccount' ) ),
		);
	}

	/**
	 * Audio products the logged-in customer paid for (Playlist::owned()).
	 *
	 * @return int[]
	 */
	private function owned_products(): array {
		if ( null === $this->owned ) {
			$user_id     = get_current_user_id();
			$this->owned = $user_id ? array_values(
				array_filter(
					Album::product_ids(),
					static function ( int $product_id ) use ( $user_id ): bool {
						return Theme_Bridge::user_bought_product( $user_id, $product_id );
					}
				)
			) : array();
		}

		return $this->owned;
	}
}
