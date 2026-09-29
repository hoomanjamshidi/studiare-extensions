<?php
/**
 * Base class for one theme fix.
 *
 * A fix repairs one known problem of the Studiare theme (or of its Studiare
 * Core plugin) without touching their files. Fixes are listed in Fixes and
 * each one has its own switch, so an admin can turn off a single fix (for
 * example once the theme repairs the problem itself) and keep the rest.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

defined( 'ABSPATH' ) || exit;

abstract class Fix {

	/** Unique machine id, also the settings key under `fixes`. */
	abstract public function id(): string;

	/** Short (translated) name for the admin list. */
	abstract public function title(): string;

	/** (Translated) explanation of what goes wrong in the theme and what the fix changes. */
	abstract public function description(): string;

	/** Registers the fix's hooks. Called only when the fix is switched on. */
	abstract public function boot(): void;

	/**
	 * Whether the theme feature this fix repairs is in use on this site. The
	 * admin shows it next to the switch, so nobody wonders why a fix seems idle.
	 */
	public function in_use(): bool {
		return true;
	}
}
