<?php
/**
 * AJAX endpoint behind the live results of the search sheet.
 *
 * admin-ajax is used (like the settings screen) because hosts and security
 * plugins often restrict the REST API, and admin-ajax responses are never
 * served from a page cache.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Bottom_Nav;

use StudiareExt\Core\Persian;
use StudiareExt\Core\Search_Query;

defined( 'ABSPATH' ) || exit;

final class Live_Search {

	public const ACTION = 'stx_live_search';

	private const RESULTS = 8;

	/** Placeholder icon per post type for results without a featured image. */
	private const TYPE_ICONS = array(
		'product' => 'graduation',
		'post'    => 'news',
	);

	/** @var Module */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/** Endpoint URL for bottom-nav.js; relative, so it is always same-origin. */
	public static function url(): string {
		return add_query_arg( 'action', self::ACTION, admin_url( 'admin-ajax.php', 'relative' ) );
	}

	/**
	 * Responds with rendered results for `term` within search item `item`.
	 *
	 * There is no nonce on purpose: the endpoint is read-only and returns the
	 * same published content as `/?s=`, while a nonce baked into a cached page
	 * would expire and break search for visitors. The request only names the
	 * item; its post types come from the saved settings, so a request cannot
	 * widen the search.
	 */
	public function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only endpoint, see above.
		$item_id = isset( $_GET['item'] ) ? sanitize_key( wp_unslash( $_GET['item'] ) ) : '';
		$term    = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		// phpcs:enable

		$item = $this->find_item( $item_id );
		if ( null === $item ) {
			wp_send_json_error( null, 404 );
		}

		$term = Search_Query::clean_term( $term );
		if ( ! Search_Query::is_searchable( $term ) ) {
			wp_send_json_success(
				array(
					'html'    => '',
					'message' => '',
				)
			);
		}

		$post_types = Search_Scope::post_types( $item );
		$show_type  = 1 !== count( $post_types );
		$pattern    = Search_Query::match_pattern( $term );
		$results    = array_map(
			static function ( $post ) use ( $show_type, $pattern ) {
				return self::result( $post, $show_type, $pattern );
			},
			Search_Query::find( $term, $post_types, self::RESULTS, $item )
		);

		$view = array(
			'results' => $results,
			'term'    => $term,
			'count'   => Persian::number( count( $results ) ),
			'all_url' => self::results_page_url( $term, $post_types ),
		);

		wp_send_json_success(
			array(
				'html'    => ( new Renderer( $this->module->settings(), array() ) )->search_results( $view ),
				'message' => Search_Query::summary( count( $results ), $term ),
			)
		);
	}

	/**
	 * The enabled search item with live results, or null.
	 *
	 * @param string $id Item id.
	 */
	private function find_item( string $id ): ?array {
		foreach ( $this->module->settings()['items'] as $item ) {
			if ( $id === $item['id'] && 'search' === $item['type'] && $item['enabled'] && $item['search_live'] ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Result view model for views/search-results.php: the shared one, plus a
	 * highlighted title, a placeholder icon and the date of non-products.
	 *
	 * @param \WP_Post $post      Found post.
	 * @param bool     $show_type Label the post type.
	 * @param string   $pattern   Pattern from Search_Query::match_pattern(), or ''.
	 */
	private static function result( \WP_Post $post, bool $show_type, string $pattern ): array {
		// "medium" keeps the original proportions; course covers are landscape.
		$result  = Search_Query::result( $post, $show_type, 'medium' );
		$persian = Persian::is_site_persian();

		$result['title_html'] = Search_Query::highlight( $result['title'], $pattern, 'stx-results__match' );
		$result['icon']       = self::TYPE_ICONS[ $post->post_type ] ?? 'notes';
		$result['date']       = '' === $result['price'] ? (string) get_the_date( '', $post ) : '';

		if ( $persian ) {
			$result['price'] = Persian::digits_html( $result['price'] );
			$result['date']  = Persian::digits( $result['date'] );
		}

		return $result;
	}

	/**
	 * The classic results page for the term, as the sheet's form opens it.
	 *
	 * @param string   $term       Search term.
	 * @param string[] $post_types Validated post types.
	 */
	private static function results_page_url( string $term, array $post_types ): string {
		$args      = array( 's' => rawurlencode( $term ) );
		$post_type = Search_Scope::for_results_page( $post_types );
		if ( '' !== $post_type ) {
			$args['post_type'] = $post_type;
		}

		return add_query_arg( $args, home_url( '/' ) );
	}
}
