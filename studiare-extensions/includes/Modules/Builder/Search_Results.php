<?php
/**
 * Markup of the Search widget's live results, in the look chosen under
 * Studiare+ → Page templates → Header:
 *
 * - `compact`: small thumbnail, title, type and price.
 * - `detailed`: large thumbnail, highlighted match, a type badge, the
 *   category and one fact per item (lessons of a course, reading time of a
 *   post), a result count and popular topics when nothing matches.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Core\Persian;
use StudiareExt\Core\Search_Query;
use StudiareExt\Modules\Builder\Elementor\Widgets\Base;

defined( 'ABSPATH' ) || exit;

final class Search_Results {

	/** Reading speed behind the "min read" of posts. */
	private const WORDS_PER_MINUTE = 200;

	/** Topics suggested when nothing matches. */
	private const TOPICS = 4;

	/** Taxonomy whose first term is shown as a result's category. */
	private const CATEGORY_TAXONOMIES = array(
		'product' => 'product_cat',
		'post'    => 'category',
	);

	/** Placeholder icon per kind, for results without a featured image. */
	private const KIND_ICONS = array(
		'course'  => 'graduation',
		'product' => 'bag',
		'post'    => 'news',
	);

	/**
	 * @param \WP_Post[] $posts     Found posts.
	 * @param string     $term      Search term.
	 * @param bool       $show_type Label the post type (when several types can match).
	 */
	public static function compact( array $posts, string $term, bool $show_type ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $term is read by the included view.
		$results = array_map(
			static function ( $post ) use ( $show_type ) {
				return Search_Query::result( $post, $show_type );
			},
			$posts
		);

		ob_start();
		require __DIR__ . '/views/search-results.php';

		return (string) ob_get_clean();
	}

	/**
	 * @param \WP_Post[] $posts Found posts.
	 * @param string     $term  Search term.
	 * @param string     $scope Widget "Search in" choice; picks the taxonomy of the suggested topics.
	 */
	public static function detailed( array $posts, string $term, string $scope ): string {
		if ( $posts ) {
			// One query for the categories of every result instead of one per result.
			update_object_term_cache( wp_list_pluck( $posts, 'ID' ), array_unique( wp_list_pluck( $posts, 'post_type' ) ) );
		}

		$pattern = Search_Query::match_pattern( $term );
		$results = array_map(
			static function ( $post ) use ( $pattern ) {
				return self::card( $post, $pattern );
			},
			$posts
		);

		$view = array(
			'results' => $results,
			'term'    => $term,
			/* translators: %s: number of search results. */
			'count'   => sprintf( _n( '%s result', '%s results', count( $results ), 'studiare-extensions' ), Base::num( count( $results ) ) ),
			'topics'  => $results ? array() : self::topics( $scope ),
		);

		ob_start();
		require __DIR__ . '/views/search-results-detailed.php';

		return (string) ob_get_clean();
	}

	/**
	 * View model of one detailed result.
	 *
	 * @param \WP_Post $post    Found post.
	 * @param string   $pattern Pattern from Search_Query::match_pattern(), or ''.
	 * @return array{url:string, thumb:string, title_html:string, kind:string, kind_label:string, icon:string, facts:string[], price:string}
	 */
	private static function card( \WP_Post $post, string $pattern ): array {
		$result = Search_Query::result( $post, true );
		$kind   = self::kind( $post );

		return array(
			'url'        => $result['url'],
			'thumb'      => $result['thumb'],
			'title_html' => Search_Query::highlight( $result['title'], $pattern, 'stx-live__match' ),
			'kind'       => $kind,
			'kind_label' => self::kind_label( $kind, $result['type'] ),
			'icon'       => self::KIND_ICONS[ $kind ] ?? 'notes',
			'facts'      => array_values( array_filter( array( self::category( $post ), self::detail( $post, $kind ) ) ) ),
			'price'      => Base::digits_html( $result['price'] ),
		);
	}

	/**
	 * `course` or `product` for products (Studiare courses are products),
	 * else the post type.
	 *
	 * @param \WP_Post $post Post.
	 */
	private static function kind( \WP_Post $post ): string {
		if ( 'product' === $post->post_type ) {
			return Context::is_course( $post->ID ) ? 'course' : 'product';
		}

		return $post->post_type;
	}

	/**
	 * Badge text: short names for the common kinds, the post type label otherwise.
	 *
	 * @param string $kind       Kind from kind().
	 * @param string $type_label Singular post type label.
	 */
	private static function kind_label( string $kind, string $type_label ): string {
		$labels = array(
			'course'  => _x( 'Course', 'search result badge', 'studiare-extensions' ),
			'product' => _x( 'Product', 'search result badge', 'studiare-extensions' ),
			'post'    => _x( 'Article', 'search result badge', 'studiare-extensions' ),
			'page'    => _x( 'Page', 'search result badge', 'studiare-extensions' ),
		);

		return $labels[ $kind ] ?? $type_label;
	}

	/**
	 * First category of a product or post, skipping the fallback one.
	 *
	 * @param \WP_Post $post Post.
	 */
	private static function category( \WP_Post $post ): string {
		$taxonomy = self::CATEGORY_TAXONOMIES[ $post->post_type ] ?? '';
		$terms    = '' !== $taxonomy ? get_the_terms( $post, $taxonomy ) : false;
		if ( ! is_array( $terms ) ) {
			return '';
		}

		foreach ( $terms as $term ) {
			if ( self::default_term( $taxonomy ) !== $term->term_id ) {
				return html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
			}
		}

		return '';
	}

	/**
	 * One fact that helps choose: lessons of a course, reading time of a post.
	 *
	 * @param \WP_Post $post Post.
	 * @param string   $kind Kind from kind().
	 */
	private static function detail( \WP_Post $post, string $kind ): string {
		if ( 'course' === $kind ) {
			return self::lessons( $post->ID );
		}

		return 'post' === $kind ? self::reading_time( $post ) : '';
	}

	/**
	 * "18 lessons": the count typed in Studiare's course fields, else the
	 * lessons listed in the curriculum. Free text (for example "18 videos")
	 * is shown as typed.
	 *
	 * @param int $product_id Course product.
	 */
	private static function lessons( int $product_id ): string {
		$typed = Persian::latin_digits( trim( (string) get_post_meta( $product_id, '_studiare_course_lesseons', true ) ) );
		if ( '' !== $typed && ! ctype_digit( $typed ) ) {
			return Base::digits( $typed );
		}

		$count = '' !== $typed ? (int) $typed : self::listed_lessons( $product_id );

		/* translators: %s: number of lessons. */
		return $count ? sprintf( _n( '%s lesson', '%s lessons', $count, 'studiare-extensions' ), Base::num( $count ) ) : '';
	}

	/**
	 * Lessons in the curriculum (Studiare's `lessons_group` meta). The IDs
	 * are counted, not loaded, so a search stays one query per result at most.
	 *
	 * @param int $product_id Course product.
	 */
	private static function listed_lessons( int $product_id ): int {
		$groups = get_post_meta( $product_id, 'lessons_group', true );
		$count  = 0;

		foreach ( is_array( $groups ) ? $groups : array() as $group ) {
			$list   = is_array( $group ) ? (array) ( $group['lessons_list'] ?? array() ) : array();
			$count += count( array_filter( $list, 'is_numeric' ) );
		}

		return $count;
	}

	/**
	 * "6 min read" from the word count of the content.
	 *
	 * @param \WP_Post $post Post.
	 */
	private static function reading_time( \WP_Post $post ): string {
		$words   = preg_split( '/\s+/u', wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), -1, PREG_SPLIT_NO_EMPTY );
		$minutes = max( 1, (int) ceil( ( is_array( $words ) ? count( $words ) : 0 ) / self::WORDS_PER_MINUTE ) );

		/* translators: %s: reading time in minutes. */
		return sprintf( __( '%s min read', 'studiare-extensions' ), Base::num( $minutes ) );
	}

	/**
	 * Most used categories, linked to their archives, for the "nothing found"
	 * state. Links (not new searches) because WordPress search does not look
	 * at categories, and they work without JavaScript.
	 *
	 * @param string $scope Widget "Search in" choice.
	 * @return array<int, array{name:string, url:string}>
	 */
	private static function topics( string $scope ): array {
		$taxonomy = 'post' === $scope || ! taxonomy_exists( 'product_cat' ) ? 'category' : 'product_cat';
		$terms    = get_terms(
			array(
				'taxonomy' => $taxonomy,
				'orderby'  => 'count',
				'order'    => 'DESC',
				'number'   => self::TOPICS,
				'exclude'  => array( self::default_term( $taxonomy ) ),
			)
		);

		$topics = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$url = get_term_link( $term );
			if ( is_string( $url ) ) {
				$topics[] = array(
					'name' => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
					'url'  => $url,
				);
			}
		}

		return $topics;
	}

	/**
	 * The fallback term ("Uncategorized"), which tells visitors nothing.
	 *
	 * @param string $taxonomy `product_cat` or `category`.
	 */
	private static function default_term( string $taxonomy ): int {
		return (int) get_option( 'product_cat' === $taxonomy ? 'default_product_cat' : 'default_category' );
	}
}
