<?php
/**
 * "My audio" tab of the WooCommerce account page: the player of every
 * audio product the customer bought, so they can listen again without
 * looking for the product.
 *
 * Registered as a WooCommerce query var, so WooCommerce adds the endpoint
 * and Studiare's account menu (`wc_get_account_menu_items()`) lists it. The
 * tab only shows to customers who own an audio product.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class My_Account {

	public const ENDPOINT = 'stx-audio';

	/** Remembers which endpoint the rewrite rules were flushed for. */
	private const RULES_OPTION = 'studiare_ext_audio_endpoint';

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
	}

	/**
	 * @param array $vars WooCommerce query vars.
	 */
	public function add_query_var( $vars ): array {
		$vars                   = (array) $vars;
		$vars[ self::ENDPOINT ] = self::ENDPOINT;

		return $vars;
	}

	/** New endpoints need fresh rewrite rules once, after WooCommerce added them. */
	public function maybe_flush_rules(): void {
		if ( self::ENDPOINT !== get_option( self::RULES_OPTION ) ) {
			flush_rewrite_rules( false );
			update_option( self::RULES_OPTION, self::ENDPOINT );
		}
	}

	/**
	 * Adds the tab after "Downloads" (or before "Log out").
	 *
	 * @param array $items Endpoint => label.
	 */
	public function add_menu_item( $items ): array {
		$items = (array) $items;
		if ( ! $this->owned_products() ) {
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
	 * Audio products the logged-in customer bought (or reaches through a
	 * Studiare subscription).
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
						return Theme_Bridge::user_has_course( $user_id, $product_id );
					}
				)
			) : array();
		}

		return $this->owned;
	}
}
