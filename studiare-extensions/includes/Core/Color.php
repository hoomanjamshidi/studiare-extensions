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

	/**
	 * Text colour for a solid background: white or near-black, whichever
	 * contrasts more (WCAG relative luminance). Light theme colours such as
	 * amber get dark text, where white would fail contrast. Non-hex colours
	 * get white.
	 *
	 * @param string $background Hex colour (#rgb or #rrggbb).
	 */
	public static function ink( string $background ): string {
		$hex = ltrim( $background, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
			return '#ffffff';
		}

		$channel = static function ( string $pair ): float {
			$value = hexdec( $pair ) / 255;
			return $value <= 0.03928 ? $value / 12.92 : ( ( $value + 0.055 ) / 1.055 ) ** 2.4;
		};

		$luminance = 0.2126 * $channel( substr( $hex, 0, 2 ) ) + 0.7152 * $channel( substr( $hex, 2, 2 ) ) + 0.0722 * $channel( substr( $hex, 4, 2 ) );

		// Contrast with white is 1.05 / (L + 0.05); with #111111 (L ≈ 0.0056) it is (L + 0.05) / 0.0556.
		return 1.05 / ( $luminance + 0.05 ) >= ( $luminance + 0.05 ) / 0.0556 ? '#ffffff' : '#111111';
	}
}
