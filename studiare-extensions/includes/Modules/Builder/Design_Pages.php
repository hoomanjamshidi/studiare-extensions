<?php
/**
 * Regular WordPress pages made from the page designs (home, about us,
 * contact us).
 *
 * A design stays in the template library as an `stx_template`; "Create page"
 * copies its Elementor content into a new page, which the site owner then
 * edits like any other Elementor page. The page only remembers which design
 * it came from, so the admin can list it.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Design_Pages {

	/** Template ID of the design a page was made from. */
	public const META_DESIGN = '_stx_design';

	/** Safety cap for the admin list. */
	private const LIST_LIMIT = 50;

	/**
	 * Meta shared by page designs and the pages made from them: Elementor's
	 * full-width template (the site header and footer around a full-width
	 * canvas) and Studiare's own page options that hide its title bar, which
	 * would otherwise sit between the header and the hero.
	 *
	 * @return array<string, string>
	 */
	public static function page_meta(): array {
		return array(
			'_wp_page_template'             => 'elementor_header_footer',
			'_studiare_disable_title'       => 'on',
			'_studiare_disable_breadcrumbs' => 'on',
		);
	}

	/**
	 * Creates a page from a page design.
	 *
	 * @param int    $design_id Page design (template) ID.
	 * @param string $title     Page title.
	 * @param string $status    `publish` or `draft`.
	 * @return int Page ID, 0 on failure.
	 */
	public static function create( int $design_id, string $title, string $status ): int {
		if ( ! self::is_design( $design_id ) ) {
			return 0;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish' === $status ? 'publish' : 'draft',
				'post_title'     => $title,
				'comment_status' => 'closed',
				'meta_input'     => array_merge( self::page_meta(), array( self::META_DESIGN => $design_id ) ),
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return 0;
		}

		Library::copy_content( $design_id, (int) $page_id );

		return (int) $page_id;
	}

	/**
	 * Title for a new page when the admin leaves the name empty.
	 *
	 * @param string $kind Page design kind.
	 */
	public static function default_title( string $kind ): string {
		$titles = array(
			'about'   => __( 'About us', 'studiare-extensions' ),
			'contact' => __( 'Contact us', 'studiare-extensions' ),
		);

		return $titles[ $kind ] ?? __( 'Home page', 'studiare-extensions' );
	}

	/**
	 * Whether a template is a page design (a whole page, not a part).
	 *
	 * @param int $template_id Template ID.
	 */
	public static function is_design( int $template_id ): bool {
		return in_array( Template_Post_Type::type_of( $template_id ), Schema::PAGE_TYPES, true );
	}

	/**
	 * Makes a published page the site's front page.
	 *
	 * @param int $page_id Page ID.
	 */
	public static function set_front( int $page_id ): bool {
		if ( 'page' !== get_post_type( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
			return false;
		}

		// A page cannot be the front page and the posts page at once.
		if ( (int) get_option( 'page_for_posts' ) === $page_id ) {
			update_option( 'page_for_posts', 0 );
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );

		return true;
	}

	/** ID of the static front page, 0 when the front page lists posts. */
	public static function front_id(): int {
		return 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
	}

	/**
	 * Pages made from page designs, newest first, for the admin list.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => self::LIST_LIMIT,
				'meta_key'       => self::META_DESIGN, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin screen, a handful of pages.
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$front = self::front_id();
		$list  = array();

		foreach ( $pages as $page ) {
			$id     = (int) $page->ID;
			$design = (int) get_post_meta( $id, self::META_DESIGN, true );
			$live   = 'publish' === $page->post_status;
			$exists = $design && get_post( $design );

			$list[] = array(
				'id'      => $id,
				'title'   => html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ),
				'status'  => $page->post_status,
				'isFront' => $id === $front,
				'design'  => $exists ? html_entity_decode( get_the_title( $design ), ENT_QUOTES, 'UTF-8' ) : '',
				// '' when the design was deleted; the admin then offers every action.
				'kind'    => $exists ? Template_Post_Type::type_of( $design ) : '',
				'editUrl' => Template_Post_Type::edit_url( $id ),
				'viewUrl' => $live ? (string) get_permalink( $id ) : (string) get_preview_post_link( $id ),
			);
		}

		return $list;
	}
}
