<?php
/**
 * Remembers which products a user paid for, between requests.
 *
 * Theme_Bridge::user_bought_product() falls back to Studiare's
 * `studi_has_bought_items()`, a join over postmeta and the order item tables
 * that no index covers. It returns false (and so runs) for every product a
 * student did not buy, and shop pages ask for every product they list, so a
 * logged-in student cost one such query per product on each page view.
 *
 * Answers are stored per user in one transient, stamped with WooCommerce's
 * `orders` transient version: WooCommerce bumps it whenever an order is
 * saved or changes status, and uses it for its own wc_customer_bought_product()
 * cache, so a new purchase is seen at once.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Purchase_Cache {

	/** Transient name prefix; the user ID follows. */
	public const PREFIX = 'studiare_ext_bought_';

	/** Kept short so a user's list stays small and stale entries never pile up. */
	private const TTL = 12 * HOUR_IN_SECONDS;

	/** @var array<int, array{version:string, products:array<int, bool>}> Loaded lists, keyed by user ID. */
	private static $users = array();

	/** @var array<int, true> Users whose list changed during this request. */
	private static $dirty = array();

	/**
	 * The remembered answer, or null when the product was never checked for
	 * this user since their last order change.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 */
	public static function get( int $user_id, int $product_id ): ?bool {
		$products = self::load( $user_id )['products'];

		return isset( $products[ $product_id ] ) ? $products[ $product_id ] : null;
	}

	/**
	 * Stores an answer; the transient is written once, at the end of the request.
	 *
	 * @param int  $user_id    User ID.
	 * @param int  $product_id Product ID.
	 * @param bool $bought     Whether the user paid for the product.
	 */
	public static function set( int $user_id, int $product_id, bool $bought ): void {
		self::load( $user_id );
		self::$users[ $user_id ]['products'][ $product_id ] = $bought;

		if ( ! self::$dirty && '' !== self::version() ) {
			add_action( 'shutdown', array( self::class, 'save' ) );
		}
		self::$dirty[ $user_id ] = true;
	}

	/** Writes the lists that changed. Hooked to `shutdown` by set(). */
	public static function save(): void {
		foreach ( array_keys( self::$dirty ) as $user_id ) {
			set_transient( self::PREFIX . $user_id, self::$users[ $user_id ], self::TTL );
		}

		self::$dirty = array();
	}

	/**
	 * @param int $user_id User ID.
	 * @return array{version:string, products:array<int, bool>}
	 */
	private static function load( int $user_id ): array {
		if ( isset( self::$users[ $user_id ] ) ) {
			return self::$users[ $user_id ];
		}

		$version = self::version();
		$stored  = '' !== $version ? get_transient( self::PREFIX . $user_id ) : false;

		self::$users[ $user_id ] = array(
			'version'  => $version,
			'products' => is_array( $stored ) && ( $stored['version'] ?? '' ) === $version && is_array( $stored['products'] ?? null ) ? $stored['products'] : array(),
		);

		return self::$users[ $user_id ];
	}

	/** WooCommerce's order cache version; empty (nothing kept between requests) without WooCommerce. */
	private static function version(): string {
		return class_exists( '\WC_Cache_Helper' ) ? (string) \WC_Cache_Helper::get_transient_version( 'orders' ) : '';
	}
}
