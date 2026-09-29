<?php
/**
 * Small array helpers.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Arr {

	/**
	 * Recursively fills missing keys of `$values` from `$defaults`.
	 *
	 * Associative arrays are merged key by key; lists (e.g. nav items) are
	 * treated as a single value, so a stored list fully replaces the default.
	 *
	 * @param array $defaults Default structure.
	 * @param array $values   Stored values.
	 */
	public static function merge_defaults( array $defaults, array $values ): array {
		$result = $defaults;

		foreach ( $values as $key => $value ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				continue; // Drop keys that no longer exist in the schema.
			}

			$default = $defaults[ $key ];

			if ( is_array( $default ) && ! self::is_list( $default ) && is_array( $value ) ) {
				$result[ $key ] = self::merge_defaults( $default, $value );
			} else {
				$result[ $key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Polyfill of PHP 8.1's array_is_list(); an empty array counts as a list.
	 *
	 * @param array $values Array to check.
	 */
	public static function is_list( array $values ): bool {
		return array() === $values || array_keys( $values ) === range( 0, count( $values ) - 1 );
	}
}
