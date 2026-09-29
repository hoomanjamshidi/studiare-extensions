<?php
/**
 * Plugin bootstrap: loads translations, instantiates feature modules and the admin UI.
 *
 * @package StudiareExt
 */

namespace StudiareExt;

use StudiareExt\Admin\Admin;
use StudiareExt\Core\Module;
use StudiareExt\Modules\Bottom_Nav\Module as Bottom_Nav_Module;
use StudiareExt\Modules\Builder\Module as Builder_Module;
use StudiareExt\Modules\Support_Button\Module as Support_Button_Module;
use StudiareExt\Modules\Theme_Fixes\Module as Theme_Fixes_Module;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public const TEXT_DOMAIN = 'studiare-extensions';

	/** @var Plugin|null */
	private static $instance = null;

	/** @var array<string, Module> Registered modules keyed by module id. */
	private $modules = array();

	/**
	 * Hooked on `init` (see the main plugin file) so translations are loaded
	 * before any module touches translatable strings (WP 6.7+ requirement).
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		load_plugin_textdomain( self::TEXT_DOMAIN, false, dirname( plugin_basename( STUDIARE_EXT_FILE ) ) . '/languages' );

		$this->register_modules();

		if ( is_admin() ) {
			( new Admin( $this ) )->register();
		}
	}

	/**
	 * Instantiates every module class. Third parties can add their own module
	 * (a subclass of Core\Module) through the `studiare_ext_modules` filter.
	 */
	private function register_modules(): void {
		$classes = apply_filters( 'studiare_ext_modules', array( Bottom_Nav_Module::class, Builder_Module::class, Support_Button_Module::class, Theme_Fixes_Module::class ) );

		foreach ( (array) $classes as $class ) {
			if ( ! is_string( $class ) || ! is_subclass_of( $class, Module::class ) ) {
				continue;
			}

			/** @var Module $module */
			$module                         = new $class();
			$this->modules[ $module->id() ] = $module;
			$module->register();
		}
	}

	/**
	 * @return array<string, Module>
	 */
	public function modules(): array {
		return $this->modules;
	}

	public function module( string $id ): ?Module {
		return $this->modules[ $id ] ?? null;
	}
}
