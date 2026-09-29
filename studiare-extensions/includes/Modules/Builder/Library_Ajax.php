<?php
/**
 * Admin AJAX for the template library (create, duplicate, restore, delete,
 * rename), pages made from page designs, and Elementor housekeeping (global
 * colours, containers).
 *
 * Uses the admin shell's nonce and capability, like Admin\Ajax_Controller.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Admin\Ajax_Controller;
use StudiareExt\Core\Sanitizer;
use StudiareExt\Modules\Builder\Presets\Catalog;

defined( 'ABSPATH' ) || exit;

final class Library_Ajax {

	/** @var Module */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp_ajax_stx_builder_library', array( $this, 'handle' ) );
	}

	public function handle(): void {
		if ( ! check_ajax_referer( Ajax_Controller::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page and try again.', 'studiare-extensions' ) ), 403 );
		}

		if ( ! Module::user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to change these settings.', 'studiare-extensions' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$op    = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$from  = isset( $_POST['from'] ) ? sanitize_key( wp_unslash( $_POST['from'] ) ) : '';
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$flags = array(
			'publish' => ! empty( $_POST['publish'] ),
			'front'   => ! empty( $_POST['front'] ),
		);
		// phpcs:enable

		$needs_elementor = array( 'install', 'create', 'duplicate', 'restore', 'create_page', 'sync_colors', 'activate_container' );
		if ( in_array( $op, $needs_elementor, true ) && ! Library::elementor_active() ) {
			wp_send_json_error( array( 'message' => __( 'Elementor is required to edit templates.', 'studiare-extensions' ) ), 400 );
		}

		if ( in_array( $op, array( 'duplicate', 'restore', 'delete', 'rename' ), true ) && ( ! Template_Post_Type::type_of( $id ) || ! current_user_can( 'edit_post', $id ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'studiare-extensions' ) ), 404 );
		}

		if ( 'create_page' === $op && ( ! Design_Pages::is_design( $id ) || ! current_user_can( 'publish_pages' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'studiare-extensions' ) ), 404 );
		}

		if ( 'set_front' === $op && ( 'page' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Page not found.', 'studiare-extensions' ) ), 404 );
		}

		$result = array();

		switch ( $op ) {
			case 'install':
				$result['count'] = Library::install_missing();
				break;

			case 'create':
				if ( '' === $title ) {
					$title = __( 'New template', 'studiare-extensions' );
				}
				$preset = Catalog::get( $from );
				if ( $preset ) {
					$new_id = Library::install( $from, 0, $title );
				} else {
					$new_id = Library::create_blank( $type, $title );
				}
				$this->fail_unless( $new_id );
				$result['id']      = $new_id;
				$result['editUrl'] = Template_Post_Type::edit_url( $new_id );
				break;

			case 'duplicate':
				$new_id = Library::duplicate( $id );
				$this->fail_unless( $new_id );
				$result['id'] = $new_id;
				break;

			case 'restore':
				$this->fail_unless( Library::restore( $id ) );
				break;

			case 'rename':
				$this->fail_unless( '' !== $title );
				wp_update_post(
					array(
						'ID'         => $id,
						'post_title' => $title,
					)
				);
				break;

			case 'delete':
				$this->fail_unless( (bool) wp_trash_post( $id ) );
				break;

			case 'create_page':
				if ( '' === $title ) {
					$title = Design_Pages::default_title( Template_Post_Type::type_of( $id ) );
				}
				// Only a home design can become the front page.
				$flags['front'] = $flags['front'] && 'home' === Template_Post_Type::type_of( $id );
				// The front page must be published, or visitors would get the posts list instead.
				$page_id = Design_Pages::create( $id, $title, $flags['publish'] || $flags['front'] ? 'publish' : 'draft' );
				$this->fail_unless( $page_id );
				if ( $flags['front'] ) {
					Design_Pages::set_front( $page_id );
				}
				$result['pageId']  = $page_id;
				$result['editUrl'] = Template_Post_Type::edit_url( $page_id );
				break;

			case 'set_front':
				$this->fail_unless( Design_Pages::set_front( $id ) );
				break;

			case 'sync_colors':
				// Colours as currently shown in the form (saved or not).
				$posted = isset( $_POST['colors'] ) ? json_decode( (string) wp_unslash( $_POST['colors'] ), true ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified above; each value sanitized below.
				$brand  = is_array( $posted ) ? $posted : $this->module->settings()['brand'];
				$colors = array();
				foreach ( Schema::BRAND_DEFAULTS as $key => $fallback ) {
					$value          = Sanitizer::color( $brand[ $key ] ?? '' );
					$colors[ $key ] = '' !== $value ? $value : $fallback;
				}
				$this->fail_unless( Library::sync_kit_colors( $colors ) );
				break;

			case 'activate_container':
				$this->fail_unless( Library::activate_container() );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown action.', 'studiare-extensions' ) ), 400 );
		}

		$result['templates'] = Library::all( $this->module );
		$result['pages']     = Design_Pages::all();
		$result['elementor'] = Library::elementor_status();

		wp_send_json_success( $result );
	}

	/**
	 * @param mixed $ok Truthy when the operation succeeded.
	 */
	private function fail_unless( $ok ): void {
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'That did not work. Please try again.', 'studiare-extensions' ) ), 500 );
		}
	}
}
