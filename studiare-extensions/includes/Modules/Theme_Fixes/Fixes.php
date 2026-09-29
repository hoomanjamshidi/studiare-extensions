<?php
/**
 * Registry of the theme fixes.
 *
 * Adding a fix means writing a Fix subclass and listing it here; its switch,
 * default (on) and admin row follow automatically.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

defined( 'ABSPATH' ) || exit;

final class Fixes {

	/** Fix classes, in the order the admin lists them. */
	private const CLASSES = array(
		Otp_Digits::class,
	);

	/** @var array<string, Fix>|null Memoized instances keyed by fix id. */
	private static $fixes = null;

	/**
	 * @return array<string, Fix>
	 */
	public static function all(): array {
		if ( null === self::$fixes ) {
			self::$fixes = array();
			foreach ( self::CLASSES as $class ) {
				$fix                       = new $class();
				self::$fixes[ $fix->id() ] = $fix;
			}
		}

		return self::$fixes;
	}

	/**
	 * @return string[]
	 */
	public static function ids(): array {
		return array_keys( self::all() );
	}
}
