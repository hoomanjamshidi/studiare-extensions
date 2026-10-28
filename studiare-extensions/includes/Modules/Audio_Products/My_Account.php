<?php
/**
 * "My audio" tab of the WooCommerce account page: the player of every
 * audio product the customer bought, so they can listen again without
 * looking for the product.
 *
 * Registered as a WooCommerce query var, so WooCommerce adds the endpoint,
 * and every menu of Studiare's account page (top icons, mobile menu,
 * breadcrumb; all built from `wc_get_account_menu_items()`) lists it. The
 * tab shows to every logged-in customer, with a note when they own no
 * audio yet, so customers always know where to find their audio.
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
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( $this, 'title' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'menu_icon_css' ) );
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

		$after = isset( $items['downloads'] ) ? 'downloads' : 'orders';
		$menu  = array();
		foreach ( $items as $endpoint => $label ) {
			if ( 'customer-logout' === $endpoint && ! isset( $menu[ self::ENDPOINT ] ) ) {
				$menu[ self::ENDPOINT ] = $this->title();
			}
			$menu[ $endpoint ] = $label;
			if ( $endpoint === $after ) {
				$menu[ self::ENDPOINT ] = $this->title();
			}
		}
		if ( ! isset( $menu[ self::ENDPOINT ] ) ) {
			$menu[ self::ENDPOINT ] = $this->title();
		}

		return $menu;
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

	public function render(): void {
		$players = array();
		foreach ( $this->owned_products() as $product_id ) {
			$product = wc_get_product( $product_id );
			$player  = $product ? $this->module->player( $product, 'account' ) : '';
			if ( '' !== $player ) {
				$players[] = $player;
			}
		}

		require __DIR__ . '/views/account.php';
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
