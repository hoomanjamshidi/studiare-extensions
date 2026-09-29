<?php
/**
 * Base class for every feature module.
 *
 * A module owns one option row (`studiare_ext_<id>`), declares its defaults and
 * sanitizer, and boots its runtime hooks only when enabled. The admin shell
 * discovers modules through Plugin::modules() and renders their panels, so
 * adding a feature means adding one Module subclass — nothing else changes.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

abstract class Module {

	private const OPTION_PREFIX = 'studiare_ext_';

	/** @var array|null Memoized settings merged with defaults. */
	private $settings = null;

	/** Unique machine id, e.g. `bottom_nav`. */
	abstract public function id(): string;

	/** Human readable (translated) title. */
	abstract public function title(): string;

	/** One-line (translated) description for the dashboard card. */
	abstract public function description(): string;

	/** Semantic icon key (see assets/icons/catalog.json) used in the admin UI. */
	abstract public function icon(): string;

	/** Full default settings, including `enabled`. */
	abstract public function defaults(): array;

	/**
	 * Turns untrusted input (from the admin AJAX save) into safe settings.
	 *
	 * @param array $input Raw decoded JSON.
	 */
	abstract public function sanitize( array $input ): array;

	/** Registers runtime hooks. Called only when the module is enabled. */
	abstract protected function boot(): void;

	/** Prints the module's settings panel inside the admin shell. */
	abstract public function render_admin(): void;

	/** Called by the plugin bootstrap on `init`. */
	public function register(): void {
		if ( $this->is_enabled() ) {
			$this->boot();
		}
	}

	public function option_name(): string {
		return self::OPTION_PREFIX . $this->id();
	}

	/** Admin page slug for this module, e.g. `studiare-ext-bottom-nav`. */
	public function admin_slug(): string {
		return 'studiare-ext-' . str_replace( '_', '-', $this->id() );
	}

	public function settings(): array {
		if ( null === $this->settings ) {
			$stored         = get_option( $this->option_name(), array() );
			$merged         = Arr::merge_defaults( $this->defaults(), is_array( $stored ) ? $stored : array() );
			$this->settings = $this->normalize( $merged );
		}

		return $this->settings;
	}

	public function is_enabled(): bool {
		return ! empty( $this->settings()['enabled'] );
	}

	/**
	 * Sanitizes and persists settings; returns the stored (normalized) result.
	 *
	 * @param array $input Raw settings.
	 */
	public function save( array $input ): array {
		update_option( $this->option_name(), $this->sanitize( $input ) );
		$this->settings = null;

		return $this->settings();
	}

	public function set_enabled( bool $enabled ): void {
		$settings            = $this->settings();
		$settings['enabled'] = $enabled;
		update_option( $this->option_name(), $settings );
		$this->settings = null;
	}

	public function reset(): array {
		delete_option( $this->option_name() );
		$this->settings = null;

		return $this->settings();
	}

	/**
	 * Hook for upgrading stored data after defaults are merged in, e.g. filling
	 * new keys inside list items. Override when the settings contain lists.
	 *
	 * @param array $settings Settings merged with defaults.
	 */
	protected function normalize( array $settings ): array {
		return $settings;
	}

	/** Enqueues module-specific admin assets on the module's own page. */
	public function enqueue_admin_assets(): void {}

	/**
	 * Extra data exposed to the module's admin script (besides settings).
	 */
	public function admin_script_data(): array {
		return array();
	}
}
