<?php
/**
 * Admin shell: menu pages, assets and the shared layout every module renders into.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Admin;

use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Module;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

final class Admin {

	public const CAPABILITY = 'manage_options';
	public const MENU_SLUG  = 'studiare-ext';

	/** Icon pack used for the admin UI's own icons. */
	private const UI_ICON_PACK = 'phosphor-duotone';

	/** @var Plugin */
	private $plugin;

	/** @var string[] Hook suffixes of our admin pages. */
	private $page_hooks = array();

	/**
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( STUDIARE_EXT_FILE ), array( $this, 'action_links' ) );

		( new Ajax_Controller( $this->plugin ) )->register();
	}

	public function register_menu(): void {
		$this->page_hooks[] = add_menu_page(
			__( 'Studiare Extensions', 'studiare-extensions' ),
			__( 'Studiare+', 'studiare-extensions' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' ),
			$this->menu_icon(),
			59
		);

		$this->page_hooks[] = add_submenu_page(
			self::MENU_SLUG,
			__( 'Studiare Extensions', 'studiare-extensions' ),
			__( 'Dashboard', 'studiare-extensions' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' )
		);

		foreach ( $this->plugin->modules() as $module ) {
			$this->page_hooks[] = add_submenu_page(
				self::MENU_SLUG,
				$module->title(),
				$module->title(),
				self::CAPABILITY,
				$module->admin_slug(),
				array( $this, 'render' )
			);
		}
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$admin   = $this;
		$modules = $this->plugin->modules();
		$module  = $this->current_module();

		require __DIR__ . '/views/layout.php';
	}

	/**
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		$module = $this->current_module();

		wp_enqueue_style( 'stx-admin', STUDIARE_EXT_URL . 'assets/admin/css/admin.css', array(), STUDIARE_EXT_VERSION );
		wp_enqueue_script( 'stx-admin', STUDIARE_EXT_URL . 'assets/admin/js/admin.js', array(), STUDIARE_EXT_VERSION, true );

		wp_add_inline_script(
			'stx-admin',
			'window.stxAdmin = ' . wp_json_encode(
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( Ajax_Controller::NONCE_ACTION ),
					'module'     => $module ? $module->id() : '',
					'settings'   => $module ? $module->settings() : null,
					'moduleData' => $module ? $module->admin_script_data() : new \stdClass(),
					'i18n'       => $this->js_strings(),
				)
			) . ';',
			'before'
		);

		if ( $module ) {
			$module->enqueue_admin_assets();
		}
	}

	/**
	 * @param string $classes Space separated admin body classes.
	 */
	public function body_class( $classes ): string {
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->id, $this->page_hooks, true ) ) {
			$classes .= ' stx-admin-page';
		}

		return (string) $classes;
	}

	/**
	 * @param string[] $links Plugin row links.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( $this->page_url() ), esc_html__( 'Settings', 'studiare-extensions' ) )
		);

		return $links;
	}

	/** The module whose page is being viewed, or null on the dashboard. */
	public function current_module(): ?Module {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing.

		foreach ( $this->plugin->modules() as $module ) {
			if ( $module->admin_slug() === $page ) {
				return $module;
			}
		}

		return null;
	}

	/**
	 * @param Module|null $module Module, or null for the dashboard.
	 */
	public function page_url( ?Module $module = null ): string {
		return admin_url( 'admin.php?page=' . ( $module ? $module->admin_slug() : self::MENU_SLUG ) );
	}

	/**
	 * Inline SVG icon for the admin UI.
	 *
	 * @param string $key Semantic icon key.
	 */
	public function icon( string $key ): string {
		return Icon_Library::svg( self::UI_ICON_PACK, $key, false, 'stx-ico' );
	}

	/** Strings used by admin.js (module scripts add their own). */
	private function js_strings(): array {
		return array(
			'saved'        => __( 'Settings saved.', 'studiare-extensions' ),
			'saveFailed'   => __( 'Saving failed. Please try again.', 'studiare-extensions' ),
			'saving'       => __( 'Saving…', 'studiare-extensions' ),
			'save'         => __( 'Save changes', 'studiare-extensions' ),
			'resetDone'    => __( 'Settings were reset to defaults.', 'studiare-extensions' ),
			'resetTitle'   => __( 'Reset all settings?', 'studiare-extensions' ),
			'resetMessage' => __( 'Every option of this feature goes back to its default. This cannot be undone.', 'studiare-extensions' ),
			'resetConfirm' => __( 'Yes, reset', 'studiare-extensions' ),
			'cancel'       => __( 'Cancel', 'studiare-extensions' ),
			'unsavedLeave' => __( 'You have unsaved changes. Leave anyway?', 'studiare-extensions' ),
			'enabled'      => __( 'Feature enabled.', 'studiare-extensions' ),
			'disabled'     => __( 'Feature disabled.', 'studiare-extensions' ),
			'themeDefault' => __( 'Theme default', 'studiare-extensions' ),
			'resetColor'   => __( 'Use theme default', 'studiare-extensions' ),
		);
	}

	/** Base64 SVG for the admin menu (WordPress recolours it to match the admin scheme). */
	private function menu_icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="black" d="M4 3h7v7H4zM13 3h7v7h-7zM4 13h7v7H4zM16.5 12.5l1.1 2.4 2.4 1.1-2.4 1.1-1.1 2.4-1.1-2.4-2.4-1.1 2.4-1.1z"/></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- required by add_menu_page().
	}
}
