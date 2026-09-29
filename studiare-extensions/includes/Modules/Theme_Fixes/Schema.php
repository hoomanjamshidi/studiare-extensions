<?php
/**
 * Settings schema and defaults for the theme fixes: one switch per fix.
 *
 * Every fix is on by default, including fixes added in newer versions
 * (the defaults merge fills in their keys).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

defined( 'ABSPATH' ) || exit;

final class Schema {

	public static function defaults(): array {
		return array(
			'enabled' => true,
			'fixes'   => array_fill_keys( Fixes::ids(), true ),
		);
	}

	public static function fields(): array {
		return array(
			'enabled' => array( 'type' => 'bool' ),
			'fixes'   => array(
				'type'   => 'group',
				'fields' => array_fill_keys( Fixes::ids(), array( 'type' => 'bool' ) ),
			),
		);
	}
}
