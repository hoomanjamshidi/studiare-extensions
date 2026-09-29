<?php
/**
 * Colour helpers.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Color {

	/**
	 * Returns `rgba()` for a hex colour with the given opacity; non-hex
	 * colours are returned unchanged.
	 *
	 * @param string $color Hex colour (#rgb or #rrggbb).
	 * @param float  $alpha Opacity between 0 and 1.
	 */
	public static function alpha( string $color, float $alpha ): string {
		$hex = ltrim( $color, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
			return $color;
		}

		return sprintf(
			'rgba(%d, %d, %d, %s)',
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
			rtrim( rtrim( number_format( max( 0, min( 1, $alpha ) ), 2, '.', '' ), '0' ), '.' )
		);
	}
}
