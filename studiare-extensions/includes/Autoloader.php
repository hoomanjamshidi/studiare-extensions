<?php
/**
 * PSR-4 style autoloader for the StudiareExt namespace.
 *
 * `StudiareExt\Modules\Bottom_Nav\Renderer` resolves to
 * `includes/Modules/Bottom_Nav/Renderer.php`.
 *
 * @package StudiareExt
 */

namespace StudiareExt;

defined( 'ABSPATH' ) || exit;

final class Autoloader {

	private const PREFIX = __NAMESPACE__ . '\\';

	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * @param string $class_name Fully-qualified class name.
	 */
	public static function load( string $class_name ): void {
		if ( 0 !== strpos( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$file     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
