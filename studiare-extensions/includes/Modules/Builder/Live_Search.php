<?php
/**
 * AJAX endpoint behind the live results of the Search widget (header search
 * field and search overlay).
 *
 * admin-ajax is used because hosts and security plugins often restrict the
 * REST API, and admin-ajax responses are never served from a page cache.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Core\Search_Query;

defined( 'ABSPATH' ) || exit;

final class Live_Search {

	public const ACTION = 'stx_builder_search';

	/** Result counts the widget may ask for. */
	public const MIN_RESULTS = 3;

	public const MAX_RESULTS = 10;

	/** Widget "Search in" choice → post types ([] = every searchable type). */
	private const SCOPES = array(
		'any'     => array(),
		'product' => array( 'product' ),
		'post'    => array( 'post' ),
	);

	/** @var Module */
	private $module;

	/**
	 * @param Module $module Owning module (its settings pick the results style).
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/** Endpoint URL for builder.js; relative, so it is always same-origin. */
	public static function url(): string {
		return add_query_arg( 'action', self::ACTION, admin_url( 'admin-ajax.php', 'relative' ) );
	}

	/**
	 * Responds with rendered results for `term`, plus the results style so
	 * builder.js styles the dropdown to match even on a page cached before
	 * the setting changed.
	 *
	 * There is no nonce on purpose: the endpoint is read-only and returns the
	 * same published content as `/?s=`, while a nonce baked into a cached page
	 * would expire and break search for visitors. `scope` and `limit` are
	 * whitelisted, so a request cannot search more than the results page does.
	 */
	public function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only endpoint, see above.
		$term  = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		$scope = isset( $_GET['scope'] ) ? sanitize_key( wp_unslash( $_GET['scope'] ) ) : 'any';
		$limit = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 6;
		// phpcs:enable

		$term = Search_Query::clean_term( $term );
		if ( ! Search_Query::is_searchable( $term ) ) {
			wp_send_json_success(
				array(
					'html'    => '',
					'message' => '',
				)
			);
		}

		$scope      = isset( self::SCOPES[ $scope ] ) ? $scope : 'any';
		$post_types = array_values( array_intersect( self::SCOPES[ $scope ], array_keys( Search_Query::searchable_types() ) ) );
		$posts      = Search_Query::find(
			$term,
			$post_types,
			max( self::MIN_RESULTS, min( self::MAX_RESULTS, $limit ) ),
			array(
				'source' => 'header',
				'scope'  => $scope,
			)
		);

		$style = $this->module->settings()['options']['search_results'];
		if ( 'compact' === $style ) {
			$show_type = 1 !== count( $post_types );
			$html      = Search_Results::compact( $posts, $term, $show_type );
		} else {
			$html = Search_Results::detailed( $posts, $term, $scope );
		}

		wp_send_json_success(
			array(
				'html'    => $html,
				'message' => Search_Query::summary( count( $posts ), $term ),
				'style'   => $style,
			)
		);
	}
}
