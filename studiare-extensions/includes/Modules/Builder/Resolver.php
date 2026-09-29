<?php
/**
 * Decides which template renders the current request.
 *
 * Single product pages: the product's own choice (meta box) → the first
 * category rule that matches → the default for courses/products.
 * Blog: one layout for post lists (the posts page, categories, tags, authors,
 * dates and, when chosen, search results) and one for single posts.
 * Header/footer: one slot per device. Admins can force any template through
 * nonce-protected preview parameters, which is how the admin previews work.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Resolver {

	public const PREVIEW_NONCE = 'stx_builder_preview';
	public const META_OVERRIDE = '_stx_template';

	/** @var Module */
	private $module;

	/** @var array|null Memoized preview parameters. */
	private $preview = null;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Template for the current single product page; 0 means "theme layout".
	 */
	public function single_template(): int {
		if ( ! is_singular( 'product' ) ) {
			return 0;
		}

		$product_id = (int) get_queried_object_id();
		$preview    = $this->preview()['single'];

		if ( null !== $preview ) {
			return $this->to_single_id( $preview );
		}

		if ( ! $this->module->is_enabled() ) {
			return 0;
		}

		return $this->single_for_product( $product_id );
	}

	/**
	 * Template for the current blog page (post list or single post); 0 means
	 * "theme layout".
	 */
	public function blog_template(): int {
		$kind = $this->blog_kind();
		if ( '' === $kind ) {
			return 0;
		}

		$preview = $this->preview()[ $kind ];
		if ( null !== $preview ) {
			return $this->to_blog_id( $preview, $kind );
		}

		if ( ! $this->module->is_enabled() ) {
			return 0;
		}

		return $this->to_blog_id( $this->module->settings()['blog'][ $kind ], $kind );
	}

	/**
	 * Blog template kind this request shows: `archive`, `post` or ''.
	 * Search results count only when the setting says so, and only for blog
	 * searches (`post_type=post`, as the Search widget set to blog posts
	 * sends): other searches list products and pages, which post cards
	 * cannot show properly.
	 */
	public function blog_kind(): string {
		if ( is_singular( 'post' ) ) {
			return 'post';
		}

		if ( is_home() || is_category() || is_tag() || is_author() || is_date() ) {
			return 'archive';
		}

		if ( is_search() && array( 'post' ) === (array) get_query_var( 'post_type' ) && ( $this->module->settings()['blog']['search'] || null !== $this->preview()['archive'] ) ) {
			return 'archive';
		}

		return '';
	}

	/**
	 * Template a product would use (ignores previews). 0 = theme layout.
	 *
	 * @param int $product_id Product ID.
	 */
	public function single_for_product( int $product_id ): int {
		$override = (string) get_post_meta( $product_id, self::META_OVERRIDE, true );
		if ( '' !== $override ) {
			return $this->to_single_id( $override );
		}

		$kind     = Context::is_course( $product_id ) ? 'course' : 'product';
		$settings = $this->module->settings()[ $kind ];

		foreach ( $settings['rules'] as $rule ) {
			if ( $this->rule_matches( $rule, $product_id ) ) {
				return $this->to_single_id( $rule['template'] );
			}
		}

		return $this->to_single_id( $settings['default'] );
	}

	/**
	 * Resolved header/footer slots for this request.
	 *
	 * @param string $area `header` or `footer`.
	 * @return array{desktop: int|string, mobile: int|string} Template ID, `theme` or `none`.
	 */
	public function slots( string $area ): array {
		$settings = $this->module->settings()[ $area ];
		$preview  = $this->preview()[ $area ];

		if ( null !== $preview ) {
			// A forced preview shows the same template at every width.
			$desktop = $preview;
			$mobile  = 'same';
		} elseif ( $this->module->is_enabled() ) {
			$desktop = $settings['desktop'];
			$mobile  = $settings['mobile'];
		} else {
			$desktop = 'theme';
			$mobile  = 'same';
		}

		$desktop = $this->to_slot( $desktop, $area );
		$mobile  = 'same' === $mobile ? $desktop : $this->to_slot( $mobile, $area );

		return array(
			'desktop' => $desktop,
			'mobile'  => $mobile,
		);
	}

	/** Whether this request is an admin preview. */
	public function is_preview(): bool {
		$preview = $this->preview();

		return (bool) array_filter(
			$preview,
			static function ( $value ) {
				return null !== $value;
			}
		);
	}

	/**
	 * Preview URL for a template (or `theme`) of any kind.
	 *
	 * @param string $type Template kind.
	 * @param string $ref  Template ID or keyword.
	 */
	public function preview_url( string $type, string $ref ): string {
		if ( in_array( $type, Schema::PAGE_TYPES, true ) ) {
			// A page design is a whole page: its own URL shows it between the site's header and footer.
			return is_numeric( $ref ) ? (string) get_permalink( (int) $ref ) : '';
		}

		$args = array( '_stxnonce' => wp_create_nonce( self::PREVIEW_NONCE ) );

		if ( in_array( $type, Schema::BLOG_TYPES, true ) ) {
			$url = 'post' === $type ? self::sample_post_url() : self::blog_url();
			if ( '' === $url ) {
				return is_numeric( $ref ) ? (string) get_permalink( (int) $ref ) : '';
			}
			$args[ 'stx_preview_' . $type ] = $ref;

			return add_query_arg( $args, $url );
		}

		if ( in_array( $type, Schema::SINGLE_TYPES, true ) ) {
			$sample = Context::sample_id( $type );
			if ( ! $sample ) {
				// No product yet: show the template itself with demo data.
				return is_numeric( $ref ) ? (string) get_permalink( (int) $ref ) : '';
			}
			$args['stx_preview'] = $ref;

			return add_query_arg( $args, get_permalink( $sample ) );
		}

		$args[ 'stx_preview_' . $type ] = $ref;

		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * @param array $rule       Rule settings.
	 * @param int   $product_id Product ID.
	 */
	private function rule_matches( array $rule, int $product_id ): bool {
		$rule_terms = array_filter( array_map( 'intval', explode( ',', (string) $rule['terms'] ) ) );
		if ( ! $rule_terms ) {
			return false;
		}

		$product_terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $product_terms ) || ! $product_terms ) {
			return false;
		}

		$product_terms = array_map( 'intval', $product_terms );

		if ( $rule['children'] ) {
			foreach ( $product_terms as $term_id ) {
				$product_terms = array_merge( $product_terms, array_map( 'intval', get_ancestors( $term_id, 'product_cat', 'taxonomy' ) ) );
			}
		}

		return (bool) array_intersect( $rule_terms, $product_terms );
	}

	/**
	 * @param string $ref Stored reference.
	 */
	private function to_single_id( string $ref ): int {
		$id = is_numeric( $ref ) ? (int) $ref : 0;

		return Template_Post_Type::is_usable( $id, Schema::SINGLE_TYPES ) ? $id : 0;
	}

	/**
	 * @param string $ref  Stored reference.
	 * @param string $kind `archive` or `post`.
	 */
	private function to_blog_id( string $ref, string $kind ): int {
		$id = is_numeric( $ref ) ? (int) $ref : 0;

		return Template_Post_Type::is_usable( $id, array( $kind ) ) ? $id : 0;
	}

	/** The posts page (or the home page when it lists the posts). */
	public static function blog_url(): string {
		$page = (int) get_option( 'page_for_posts' );

		if ( 'page' === get_option( 'show_on_front' ) && $page ) {
			return (string) get_permalink( $page );
		}

		return home_url( '/' );
	}

	/** Newest published post, for previews ('' when there is none). */
	private static function sample_post_url(): string {
		$sample = Context::sample_post_id();

		return $sample ? (string) get_permalink( $sample ) : '';
	}

	/**
	 * @param string $ref  Stored reference.
	 * @param string $area `header` or `footer`.
	 * @return int|string
	 */
	private function to_slot( string $ref, string $area ) {
		if ( 'none' === $ref ) {
			return 'none';
		}

		$id = is_numeric( $ref ) ? (int) $ref : 0;

		return Template_Post_Type::is_usable( $id, array( $area ) ) ? $id : 'theme';
	}

	/**
	 * Reads preview parameters once. They only count for users who can edit
	 * templates and carry a valid nonce, so shared links do nothing.
	 *
	 * @return array{single: ?string, header: ?string, footer: ?string, archive: ?string, post: ?string}
	 */
	private function preview(): array {
		if ( null !== $this->preview ) {
			return $this->preview;
		}

		$this->preview = array(
			'single'  => null,
			'header'  => null,
			'footer'  => null,
			'archive' => null,
			'post'    => null,
		);

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- verified right below.
		$keys = array(
			'single'  => 'stx_preview',
			'header'  => 'stx_preview_header',
			'footer'  => 'stx_preview_footer',
			'archive' => 'stx_preview_archive',
			'post'    => 'stx_preview_post',
		);

		$requested = array();
		foreach ( $keys as $slot => $key ) {
			if ( isset( $_GET[ $key ] ) ) {
				$requested[ $slot ] = sanitize_key( wp_unslash( $_GET[ $key ] ) );
			}
		}

		if ( ! $requested ) {
			return $this->preview;
		}

		$nonce = isset( $_GET['_stxnonce'] ) ? sanitize_key( wp_unslash( $_GET['_stxnonce'] ) ) : '';
		// phpcs:enable

		if ( ! current_user_can( 'edit_pages' ) || ! wp_verify_nonce( $nonce, self::PREVIEW_NONCE ) ) {
			return $this->preview;
		}

		$this->preview = array_merge( $this->preview, $requested );

		return $this->preview;
	}
}
