<?php
/**
 * Live search query and result helpers shared by every search UI of the
 * plugin (the bottom navigation's search sheet and the header search widget).
 *
 * It returns the same published content as WordPress' own results page
 * (`/?s=`), including WooCommerce's catalogue visibility, so a live result
 * never shows something the full results page would hide. It also leaves out
 * the pages that only frame other content (see hub_page_ids()).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Search_Query {

	/** Shorter terms return nothing; the front-end scripts do not send them. */
	public const MIN_CHARS = 2;

	private const MAX_CHARS = 100;

	/**
	 * Post types a visitor can search in: public types that WordPress search
	 * includes, minus media (attachments are not useful search results).
	 *
	 * @return array<string, string> Post type name => plural label.
	 */
	public static function searchable_types(): array {
		$types = array();

		foreach ( get_post_types(
			array(
				'public'              => true,
				'exclude_from_search' => false,
			),
			'objects'
		) as $type ) {
			if ( 'attachment' !== $type->name ) {
				$types[ $type->name ] = $type->labels->name;
			}
		}

		return $types;
	}

	/**
	 * Trimmed, length-limited search term.
	 *
	 * @param string $term Raw (already sanitized) term.
	 */
	public static function clean_term( string $term ): string {
		return mb_substr( trim( $term ), 0, self::MAX_CHARS );
	}

	/**
	 * Whether a term is long enough to search for.
	 *
	 * @param string $term Clean term.
	 */
	public static function is_searchable( string $term ): bool {
		return mb_strlen( $term ) >= self::MIN_CHARS;
	}

	/**
	 * Published posts matching a term.
	 *
	 * @param string   $term       Search term.
	 * @param string[] $post_types Validated post types; empty means every searchable one.
	 * @param int      $limit      Maximum number of results.
	 * @param array    $context    Passed to the filter: the bottom-navigation search item, or `source` => `header`.
	 * @return \WP_Post[]
	 */
	public static function find( string $term, array $post_types, int $limit, array $context ): array {
		$types = $post_types ? $post_types : array_keys( self::searchable_types() );
		$args  = array(
			's'                      => $term,
			'post_type'              => $types,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		);

		if ( in_array( 'product', $types, true ) && function_exists( 'wc_get_product_visibility_term_ids' ) ) {
			$args['tax_query'] = self::product_visibility_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- same query WooCommerce runs on its search page.
		}

		if ( in_array( 'page', $types, true ) ) {
			$args['post__not_in'] = self::hub_page_ids();
		}

		/**
		 * Filters the WP_Query arguments of the live search.
		 *
		 * @param array $args    Query arguments.
		 * @param array $context The bottom-navigation search item, or `source` => `header` for the header search widget.
		 */
		$args = (array) apply_filters( 'studiare_ext_live_search_args', $args, $context );

		return ( new \WP_Query( $args ) )->posts;
	}

	/**
	 * View model of one result.
	 *
	 * @param \WP_Post $post       Found post.
	 * @param bool     $show_type  Label the post type (when several types can match).
	 * @param string   $thumb_size Image size of the thumbnail.
	 * @return array{title:string, url:string, thumb:string, type:string, price:string}
	 */
	public static function result( \WP_Post $post, bool $show_type, string $thumb_size = 'thumbnail' ): array {
		$type    = get_post_type_object( $post->post_type );
		$product = 'product' === $post->post_type && function_exists( 'wc_get_product' ) ? wc_get_product( $post ) : null;

		return array(
			'title' => wp_strip_all_tags( get_the_title( $post ) ),
			'url'   => (string) get_permalink( $post ),
			'thumb' => get_the_post_thumbnail(
				$post,
				$thumb_size,
				array(
					'class'   => 'stx-results__img',
					'alt'     => '',
					'loading' => 'lazy',
				)
			),
			'type'  => $show_type && $type ? $type->labels->singular_name : '',
			'price' => $product ? (string) $product->get_price_html() : '',
		);
	}

	/**
	 * Regex matching any word of the term, to highlight titles. WordPress
	 * searches each word separately, so each one is highlighted.
	 *
	 * @param string $term Search term.
	 * @return string Pattern for highlight(), or '' when no word is long enough.
	 */
	public static function match_pattern( string $term ): string {
		$words = array();
		foreach ( (array) preg_split( '/\s+/u', $term ) as $word ) {
			if ( self::is_searchable( (string) $word ) ) {
				$words[] = preg_quote( (string) $word, '/' );
			}
		}

		return $words ? '/(' . implode( '|', $words ) . ')/iu' : '';
	}

	/**
	 * Escaped title with the matched words wrapped in <mark>.
	 *
	 * @param string $title      Plain title.
	 * @param string $pattern    Pattern from match_pattern(), or ''.
	 * @param string $class_name Class of the <mark> elements.
	 */
	public static function highlight( string $title, string $pattern, string $class_name ): string {
		$parts = '' !== $pattern ? preg_split( $pattern, $title, -1, PREG_SPLIT_DELIM_CAPTURE ) : false;
		if ( ! is_array( $parts ) ) {
			return esc_html( $title );
		}

		$html = '';
		foreach ( $parts as $index => $part ) {
			// Captured matches sit at odd indexes.
			$html .= 1 === $index % 2 ? '<mark class="' . esc_attr( $class_name ) . '">' . esc_html( $part ) . '</mark>' : esc_html( $part );
		}

		return $html;
	}

	/**
	 * Screen-reader summary of a result list.
	 *
	 * @param int    $count Number of results.
	 * @param string $term  Search term.
	 */
	public static function summary( int $count, string $term ): string {
		return $count
			/* translators: %d: number of search results. */
			? sprintf( _n( '%d result', '%d results', $count, 'studiare-extensions' ), $count )
			/* translators: %s: search term. */
			: sprintf( __( 'Nothing found for “%s”.', 'studiare-extensions' ), $term );
	}

	/**
	 * Pages that frame other content instead of being content themselves:
	 * the static front page, the posts page, WooCommerce's shop, cart,
	 * checkout and account pages, and the pages Studiare builds its header
	 * and footer from. Elementor saves a plain-text copy of everything such a
	 * page shows (course cards, menus) as its content, so they would match
	 * almost every search.
	 *
	 * @return int[]
	 */
	private static function hub_page_ids(): array {
		$ids = array(
			(int) get_option( 'page_for_posts' ),
			(int) Theme_Bridge::option( 'header_page_id', 0 ),
			(int) Theme_Bridge::option( 'footer_page_id', 0 ),
		);

		// `page_on_front` keeps its value after switching back to "latest posts".
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$ids[] = (int) get_option( 'page_on_front' );
		}

		if ( function_exists( 'wc_get_page_id' ) ) {
			foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $page ) {
				$ids[] = (int) wc_get_page_id( $page );
			}
		}

		// wc_get_page_id() returns -1 for pages the store has not set.
		$ids = array_filter(
			$ids,
			static function ( int $id ): bool {
				return $id > 0;
			}
		);

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Hides products the store keeps out of search (catalog visibility
	 * "Shop only" or "Hidden", and out-of-stock items when the store hides
	 * them), as WooCommerce does on its own search page. Posts and pages have
	 * no visibility terms, so `NOT IN` keeps them.
	 */
	private static function product_visibility_query(): array {
		$term_ids = wc_get_product_visibility_term_ids();
		$excluded = array( $term_ids['exclude-from-search'] ?? 0 );

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$excluded[] = $term_ids['outofstock'] ?? 0;
		}

		return array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array_values( array_filter( $excluded ) ),
				'operator' => 'NOT IN',
			),
		);
	}
}
