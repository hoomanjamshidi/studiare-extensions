<?php
/**
 * URLs (and, for inline printing, contents) of front-end asset files.
 *
 * tools/minify.mjs writes a minified copy (`*.min.css`, `*.min.js`) next to
 * each front-end file. Visitors get that copy, so PageSpeed and GTmetrix
 * report nothing to minify; with SCRIPT_DEBUG on, or when a copy is missing,
 * the readable source is served instead.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Asset {

	/**
	 * @param string $path Path inside the plugin, e.g. `assets/modules/builder/css/slider.css`.
	 */
	public static function url( string $path ): string {
		return STUDIARE_EXT_URL . self::served( $path );
	}

	/**
	 * Contents of the served copy, for printing inline.
	 *
	 * @param string $path Path inside the plugin.
	 */
	public static function contents( string $path ): string {
		return (string) file_get_contents( STUDIARE_EXT_DIR . self::served( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
	}

	/**
	 * The minified copy's path when it should be served, else the source's.
	 *
	 * @param string $path Path inside the plugin.
	 */
	private static function served( string $path ): string {
		$min = (string) preg_replace( '/\.(css|js)$/', '.min.$1', $path );

		if ( $min !== $path && ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) && is_readable( STUDIARE_EXT_DIR . $min ) ) {
			return $min;
		}

		return $path;
	}
}
