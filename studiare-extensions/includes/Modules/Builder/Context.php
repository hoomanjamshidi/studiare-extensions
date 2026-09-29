<?php
/**
 * Resolves which product the product widgets describe, and which post the
 * blog post widgets describe.
 *
 * On the site that is the product (or post) being viewed. Inside the
 * Elementor editor (where the edited post is a template, not a product) it is
 * a sample course, product or post, so templates are designed with real data
 * instead of guesses.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Context {

	/** @var \WC_Product[] Products pushed by run(). */
	private static $stack = array();

	/** @var array<string, int> Memoized sample product IDs per kind. */
	private static $samples = array();

	/** @var int|null Memoized sample post ID. */
	private static $sample_post = null;

	/** @var Module|null */
	private static $module = null;

	/**
	 * @param Module $module Owning module (for the preview product settings).
	 */
	public static function init( Module $module ): void {
		self::$module = $module;
	}

	public static function has_woo(): bool {
		return function_exists( 'wc_get_product' );
	}

	/** The product the current widget should describe, if any. */
	public static function product(): ?\WC_Product {
		if ( ! self::has_woo() ) {
			return null;
		}

		if ( self::$stack ) {
			return end( self::$stack );
		}

		if ( is_singular( 'product' ) ) {
			$product = wc_get_product( get_queried_object_id() );
			if ( $product ) {
				return $product;
			}
		}

		global $product;
		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		$template_id = self::current_template_id();
		if ( $template_id ) {
			$sample = self::sample_id( Template_Post_Type::type_of( $template_id ) );
			return $sample ? wc_get_product( $sample ) : null;
		}

		return null;
	}

	/**
	 * Runs a callback with the product set up as the global post/product, so
	 * WooCommerce template functions (add to cart form, reviews…) work in the
	 * editor too. Always restores the previous globals.
	 *
	 * @param \WC_Product $product  Product.
	 * @param callable    $callback Receives the product.
	 * @return mixed Callback result.
	 */
	public static function run( \WC_Product $product, callable $callback ) {
		global $post;

		$previous_post    = $post;
		$previous_product = $GLOBALS['product'] ?? null;
		$product_post     = get_post( $product->get_id() );
		$switch           = $product_post && ( ! $previous_post instanceof \WP_Post || (int) $previous_post->ID !== (int) $product_post->ID );

		self::$stack[] = $product;

		if ( $switch ) {
			$post = $product_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
			setup_postdata( $post );
		}
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's global, restored below.

		try {
			return $callback( $product );
		} finally {
			array_pop( self::$stack );
			$GLOBALS['product'] = $previous_product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's global.
			if ( $switch ) {
				$post = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				if ( $post instanceof \WP_Post ) {
					setup_postdata( $post );
				}
			}
		}
	}

	/**
	 * The blog post the current widget should describe: the post being
	 * viewed, the post of a loop the widget sits in, else (in the editor or
	 * preview of a template that is not a post list) the newest post.
	 * On a post list the global post is merely the first post of the list,
	 * so it only counts inside a loop.
	 */
	public static function post(): ?\WP_Post {
		if ( is_singular( 'post' ) ) {
			$viewed = get_post( get_queried_object_id() );
			return $viewed instanceof \WP_Post ? $viewed : null;
		}

		$current = $GLOBALS['post'] ?? null;
		if ( in_the_loop() && $current instanceof \WP_Post && 'post' === $current->post_type ) {
			return $current;
		}

		$template_id = self::current_template_id();
		if ( $template_id && 'archive' !== Template_Post_Type::type_of( $template_id ) ) {
			$sample = self::sample_post_id();
			return $sample ? get_post( $sample ) : null;
		}

		return null;
	}

	/**
	 * Runs a callback with the post set up as the global post, so template
	 * tags (the content, comments) work in the editor too. Always restores
	 * the previous global post.
	 *
	 * @param \WP_Post $target   Post.
	 * @param callable $callback Receives the post.
	 * @return mixed Callback result.
	 */
	public static function run_post( \WP_Post $target, callable $callback ) {
		global $post;

		$previous = $post;
		$switch   = ! $previous instanceof \WP_Post || (int) $previous->ID !== (int) $target->ID;

		if ( $switch ) {
			$post = $target; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
			setup_postdata( $post );
		}

		try {
			return $callback( $target );
		} finally {
			if ( $switch ) {
				$post = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				if ( $post instanceof \WP_Post ) {
					setup_postdata( $post );
				}
			}
		}
	}

	/** Newest published post with a picture (else the newest post), for editing and previews. */
	public static function sample_post_id(): int {
		if ( null !== self::$sample_post ) {
			return self::$sample_post;
		}

		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		$ids = get_posts(
			array_merge(
				$args,
				array( 'meta_key' => '_thumbnail_id' ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one lookup, editor and previews only.
			)
		);
		if ( ! $ids ) {
			$ids = get_posts( $args );
		}

		self::$sample_post = $ids ? (int) $ids[0] : 0;

		return self::$sample_post;
	}

	/** Whether we are inside the Elementor editor or its preview iframe. */
	public static function is_editor(): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;

		return ( isset( $elementor->editor ) && $elementor->editor->is_edit_mode() )
			|| ( isset( $elementor->preview ) && $elementor->preview->is_preview_mode() );
	}

	/**
	 * Whether a product is a Studiare course (`_studiare_course = yes`).
	 *
	 * @param int $product_id Product ID.
	 */
	public static function is_course( int $product_id ): bool {
		return Theme_Bridge::is_course( $product_id );
	}

	/**
	 * Product used to preview/edit templates of a kind: the one chosen in the
	 * settings, else the newest published course (or non-course product).
	 *
	 * @param string $type Template kind.
	 */
	public static function sample_id( string $type ): int {
		if ( ! self::has_woo() || ! in_array( $type, Schema::SINGLE_TYPES, true ) ) {
			return 0;
		}

		if ( isset( self::$samples[ $type ] ) ) {
			return self::$samples[ $type ];
		}

		$chosen = self::$module ? (int) self::$module->settings()[ $type ]['preview_id'] : 0;
		if ( $chosen && 'publish' === get_post_status( $chosen ) && 'product' === get_post_type( $chosen ) ) {
			self::$samples[ $type ] = $chosen;
			return $chosen;
		}

		$meta_query = 'course' === $type
			? array(
				array(
					'key'   => '_studiare_course',
					'value' => 'yes',
				),
			)
			: array(
				'relation' => 'OR',
				array(
					'key'     => '_studiare_course',
					'value'   => 'yes',
					'compare' => '!=',
				),
				array(
					'key'     => '_studiare_course',
					'compare' => 'NOT EXISTS',
				),
			);

		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one lookup, previews only.
			)
		);

		// A site without courses still gets a sample for course templates.
		if ( ! $ids ) {
			$ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
		}

		self::$samples[ $type ] = $ids ? (int) $ids[0] : 0;

		return self::$samples[ $type ];
	}

	/** The template being edited/previewed, when the queried post is one. */
	private static function current_template_id(): int {
		$id = 0;

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) ) {
			$document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $document ) {
				$id = (int) $document->get_main_id();
			}
		}

		if ( ! $id || Template_Post_Type::POST_TYPE !== get_post_type( $id ) ) {
			$id = is_singular( Template_Post_Type::POST_TYPE ) ? (int) get_queried_object_id() : 0;
		}

		return Template_Post_Type::POST_TYPE === get_post_type( $id ) ? $id : 0;
	}
}
