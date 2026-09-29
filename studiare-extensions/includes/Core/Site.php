<?php
/**
 * Facts about the public site (as opposed to the current admin user).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Site {

	/** Language codes written right-to-left. */
	private const RTL_LANGUAGES = array( 'ar', 'arq', 'ary', 'azb', 'ckb', 'dv', 'fa', 'haz', 'he', 'ps', 'sd', 'ug', 'ur', 'yi' );

	/**
	 * Whether the site's front end is RTL. Unlike is_rtl(), this ignores the
	 * admin user's own language, so previews match what visitors see.
	 */
	public static function is_rtl(): bool {
		return in_array( strtok( get_locale(), '_' ), self::RTL_LANGUAGES, true );
	}

	/** `rtl` or `ltr`, for `dir` attributes on previews. */
	public static function direction(): string {
		return self::is_rtl() ? 'rtl' : 'ltr';
	}
}
