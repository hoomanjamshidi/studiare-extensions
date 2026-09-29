<?php
/**
 * The `stx_template` post type: Elementor documents for course/product pages,
 * headers, footers and home page designs.
 *
 * Templates are edited only with Elementor. They are publicly queryable so
 * Elementor's preview iframe can load them, but visitors without edit rights
 * get a 404; editors see the template on a blank canvas.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Template_Post_Type {

	public const POST_TYPE   = 'stx_template';
	public const META_TYPE   = '_stx_type';
	public const META_PRESET = '_stx_preset';

	/** @var Module */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		// The plugin boots on `init`, so the post type can be registered right away.
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Studiare+ templates', 'studiare-extensions' ),
					'singular_name' => __( 'Studiare+ template', 'studiare-extensions' ),
					'edit_item'     => __( 'Edit template', 'studiare-extensions' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => true,
				'rewrite'             => false,
				'query_var'           => self::POST_TYPE,
				'has_archive'         => false,
				'hierarchical'        => false,
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'elementor', 'revisions', 'author' ),
			)
		);

		add_post_type_support( self::POST_TYPE, 'elementor' );

		add_action( 'template_redirect', array( $this, 'guard_frontend' ), 1 );
		add_action( 'load-edit.php', array( $this, 'redirect_list_screen' ) );
		add_action( 'load-post.php', array( $this, 'redirect_classic_editor' ) );
		add_filter( 'elementor/document/urls/exit_to_dashboard', array( $this, 'exit_url' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( $this, 'remove_from_sitemap' ) );
		add_filter( 'display_post_states', array( $this, 'post_states' ), 10, 2 );
	}

	/**
	 * Kind of a template (`course`, `product`, `header`, `footer`, `home`), or '' when
	 * the post is not a template.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function type_of( int $post_id ): string {
		if ( $post_id <= 0 || self::POST_TYPE !== get_post_type( $post_id ) ) {
			return '';
		}

		$type = (string) get_post_meta( $post_id, self::META_TYPE, true );

		return in_array( $type, Schema::TYPES, true ) ? $type : '';
	}

	/**
	 * Whether the post is a usable (published) template of one of the kinds.
	 *
	 * @param int      $post_id Post ID.
	 * @param string[] $types   Accepted kinds.
	 */
	public static function is_usable( int $post_id, array $types ): bool {
		return in_array( self::type_of( $post_id ), $types, true ) && 'publish' === get_post_status( $post_id );
	}

	/** Visitors never see raw templates; editors get the Elementor canvas. */
	public function guard_frontend(): void {
		if ( ! is_singular( self::POST_TYPE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', get_queried_object_id() ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}

		// Search engines must not index template previews.
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
	}

	/** The list table is replaced by the module's own library tab. */
	public function redirect_list_screen(): void {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.

		if ( self::POST_TYPE === $post_type ) {
			wp_safe_redirect( $this->module->library_url() );
			exit;
		}
	}

	/** Templates only make sense in Elementor, so skip the block editor. */
	public function redirect_classic_editor(): void {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.
		$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'edit' !== $action || self::POST_TYPE !== get_post_type( $post_id ) || ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		wp_safe_redirect( self::edit_url( $post_id ) );
		exit;
	}

	/**
	 * @param string $url      Default exit URL.
	 * @param object $document Elementor document.
	 */
	public function exit_url( $url, $document ) {
		if ( is_object( $document ) && method_exists( $document, 'get_main_id' ) && self::POST_TYPE === get_post_type( $document->get_main_id() ) ) {
			return $this->module->library_url();
		}

		return $url;
	}

	/**
	 * @param array $post_types Post types in the core sitemap.
	 */
	public function remove_from_sitemap( $post_types ) {
		unset( $post_types[ self::POST_TYPE ] );

		return $post_types;
	}

	/**
	 * Adds "Header", "Course page"… next to titles wherever WordPress lists posts.
	 *
	 * @param string[] $states Post states.
	 * @param \WP_Post $post   Post.
	 */
	public function post_states( $states, $post ) {
		if ( $post instanceof \WP_Post && self::POST_TYPE === $post->post_type ) {
			$labels = Module::type_labels();
			$type   = self::type_of( (int) $post->ID );
			if ( isset( $labels[ $type ] ) ) {
				$states['stx_type'] = $labels[ $type ];
			}
		}

		return $states;
	}

	/**
	 * Elementor editor URL for a template.
	 *
	 * @param int $post_id Template ID.
	 */
	public static function edit_url( int $post_id ): string {
		return add_query_arg(
			array(
				'post'   => $post_id,
				'action' => 'elementor',
			),
			admin_url( 'post.php' )
		);
	}
}
