<?php
/**
 * Shared plumbing for the public forms behind the widgets (newsletter,
 * contact): the handler hooks, the honeypot, a per-visitor rate limit, the
 * answer (JSON for the widget scripts, a redirect back to the form without
 * JavaScript) and the admin's CSV download.
 *
 * There is no nonce: the forms sit on cached public pages where a nonce
 * would expire, so the honeypot and the rate limit keep bots out instead.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Admin\Admin;

defined( 'ABSPATH' ) || exit;

abstract class Public_Form {

	/** Name of the honeypot field (hidden from people, filled in by bots). */
	public const HONEYPOT = 'stx_website';

	/** Posts read per query while exporting. */
	private const EXPORT_BATCH = 100;

	/** @var string Form ID to scroll back to after a plain (no-JS) submit. */
	private $anchor = '';

	/** Query argument that carries the result back to the page without JavaScript. */
	abstract protected function result_arg(): string;

	/**
	 * Hooks a form handler for admin-post.php (plain form) and admin-ajax.php
	 * (the widget's script), for visitors and logged-in users alike.
	 *
	 * @param string   $action  Form `action` field.
	 * @param callable $handler Handler.
	 */
	protected function add_handler( string $action, callable $handler ): void {
		foreach ( array( 'admin_post_', 'admin_post_nopriv_', 'wp_ajax_', 'wp_ajax_nopriv_' ) as $hook ) {
			add_action( $hook . $action, $handler );
		}
	}

	/**
	 * Reads the fields every form sends. A filled honeypot ends the request
	 * with a fake success, so bots learn nothing.
	 */
	protected function start(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- public form on cached pages; see the class docblock.
		$this->anchor = isset( $_POST['anchor'] ) ? sanitize_html_class( wp_unslash( $_POST['anchor'] ) ) : '';
		$honeypot     = isset( $_POST[ self::HONEYPOT ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::HONEYPOT ] ) ) : '';
		// phpcs:enable

		if ( '' !== $honeypot ) {
			$this->respond( 'ok' );
		}
	}

	/**
	 * Counts this visitor's submissions; false once they exceed the limit.
	 *
	 * @param string $bucket Short name of the counter (one per form type).
	 * @param int    $limit  Submissions allowed per window.
	 * @param int    $window Window in seconds.
	 */
	protected function within_rate_limit( string $bucket, int $limit, int $window ): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'stx_' . $bucket . '_' . md5( $ip . wp_salt( 'nonce' ) );

		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return false;
		}

		set_transient( $key, $count + 1, $window );

		return true;
	}

	/**
	 * JSON for the widget's script; a redirect back to the form otherwise.
	 *
	 * @param string $status `ok`, or an error status the widget has a message for.
	 * @param array  $extra  More data for the script (e.g. the invalid fields).
	 */
	protected function respond( string $status, array $extra = array() ): void {
		if ( wp_doing_ajax() ) {
			$data = array_merge( $extra, array( 'status' => $status ) );
			if ( 'ok' === $status ) {
				wp_send_json_success( $data );
			}
			$codes = array(
				'invalid' => 400,
				'busy'    => 429,
			);
			wp_send_json_error( $data, $codes[ $status ] ?? 500 );
		}

		$arg  = $this->result_arg();
		$back = wp_get_referer();
		$back = add_query_arg( $arg, $status, remove_query_arg( $arg, $back ? $back : home_url( '/' ) ) );

		wp_safe_redirect( '' !== $this->anchor ? $back . '#' . $this->anchor : $back );
		exit;
	}

	/**
	 * Streams every private post of a type as a CSV download, oldest first.
	 *
	 * @param string   $post_type Post type.
	 * @param string   $name      File name prefix (the date is added).
	 * @param string[] $header    Column names.
	 * @param callable $row       Receives a WP_Post, returns its cells.
	 */
	protected static function export_csv( string $post_type, string $name, array $header, callable $row ): void {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'studiare-extensions' ), 403 );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $name . '-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming a download.
		// A byte order mark lets Excel read the file as UTF-8.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		// An explicit, empty escape character: RFC 4180 quoting only (PHP 8.4 deprecates the default).
		$put = static function ( array $cells ) use ( $out ): void {
			fputcsv( $out, $cells, ',', '"', '' );
		};

		$put( $header );

		$page = 1;
		do {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'private',
					'posts_per_page' => self::EXPORT_BATCH,
					'paged'          => $page,
					'orderby'        => 'date',
					'order'          => 'ASC',
					'no_found_rows'  => true,
				)
			);

			foreach ( $posts as $post ) {
				$put( $row( $post ) );
			}

			++$page;
			$more = count( $posts ) === self::EXPORT_BATCH;
		} while ( $more );

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Nonce-protected admin-post.php link.
	 *
	 * @param string $action Admin-post action (also the nonce action).
	 */
	protected static function admin_action_url( string $action ): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . $action ), $action );
	}

	/** URL the forms post to without JavaScript. */
	public static function form_url(): string {
		return admin_url( 'admin-post.php' );
	}
}
