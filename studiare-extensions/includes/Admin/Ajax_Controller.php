<?php
/**
 * AJAX endpoints for the admin UI: save, reset and enable/disable modules.
 *
 * admin-ajax is used instead of the REST API on purpose: many hosts and
 * security plugins restrict REST, while admin-ajax is always available.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Admin;

use StudiareExt\Core\Module;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

final class Ajax_Controller {

	public const NONCE_ACTION = 'stx_admin';

	/** @var Plugin */
	private $plugin;

	/**
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function register(): void {
		add_action( 'wp_ajax_stx_save_settings', array( $this, 'save' ) );
		add_action( 'wp_ajax_stx_reset_settings', array( $this, 'reset' ) );
		add_action( 'wp_ajax_stx_toggle_module', array( $this, 'toggle' ) );
	}

	public function save(): void {
		$module = $this->authorize();

		// Settings arrive as one JSON document; sanitization happens in Module::sanitize().
		$raw      = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- verified in authorize(); sanitized by the module schema.
		$settings = json_decode( (string) $raw, true );

		if ( ! is_array( $settings ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings payload.', 'studiare-extensions' ) ), 400 );
		}

		wp_send_json_success( array( 'settings' => $module->save( $settings ) ) );
	}

	public function reset(): void {
		$module = $this->authorize();

		wp_send_json_success( array( 'settings' => $module->reset() ) );
	}

	public function toggle(): void {
		$module  = $this->authorize();
		$enabled = isset( $_POST['enabled'] ) && filter_var( wp_unslash( $_POST['enabled'] ), FILTER_VALIDATE_BOOLEAN ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified in authorize(); cast to bool.

		$module->set_enabled( $enabled );

		wp_send_json_success( array( 'enabled' => $module->is_enabled() ) );
	}

	/**
	 * Verifies nonce and capability and returns the targeted module.
	 * Ends the request with a JSON error when any check fails.
	 */
	private function authorize(): Module {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page and try again.', 'studiare-extensions' ) ), 403 );
		}

		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to change these settings.', 'studiare-extensions' ) ), 403 );
		}

		$id     = isset( $_POST['module'] ) ? sanitize_key( wp_unslash( $_POST['module'] ) ) : '';
		$module = $this->plugin->module( $id );

		if ( null === $module ) {
			wp_send_json_error( array( 'message' => __( 'Unknown feature.', 'studiare-extensions' ) ), 404 );
		}

		return $module;
	}
}
