<?php
/**
 * Category details shared by the category widgets (Category grid and Blog
 * categories): an icon for each category (Studiare's "Featured Icon", or a
 * bundled icon in turn, so a fresh site already looks finished) and a cover
 * picture taken from the category's newest post.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor;

use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Builder\Elementor\Widgets\Base;

defined( 'ABSPATH' ) || exit;

final class Category_Parts {

	/** Bundled icons given in turn to categories without their own. */
	private const ICONS = array( 'book', 'video', 'graduation', 'chart', 'heart', 'clock', 'star', 'compass' );

	/**
	 * Bundled icon key for the category at this position.
	 *
	 * @param int $index Position in the list.
	 */
	public static function fallback_icon( int $index ): string {
		return self::ICONS[ $index % count( self::ICONS ) ];
	}

	/**
	 * The icon picked for the category in Studiare, as an `<img>`, or ''.
	 *
	 * @param \WP_Term $term Category.
	 */
	public static function theme_icon( \WP_Term $term ): string {
		$id = Theme_Bridge::category_icon_id( $term );

		return $id ? (string) wp_get_attachment_image( $id, 'thumbnail', false, array( 'alt' => '' ) ) : '';
	}

	/**
	 * Icon markup for a category: the icon chosen in the widget, else
	 * Studiare's icon, else a bundled one.
	 *
	 * @param \WP_Term $term     Category.
	 * @param int      $index    Position in the list.
	 * @param string   $icon_key Icon chosen in the widget ('' for automatic).
	 */
	public static function mark( \WP_Term $term, int $index, string $icon_key = '' ): string {
		if ( '' !== $icon_key ) {
			return Base::icon( $icon_key );
		}

		$image = self::theme_icon( $term );

		return '' !== $image ? $image : Base::icon( self::fallback_icon( $index ) );
	}

	/**
	 * Picture of the newest post that has one in a blog category (its
	 * subcategories included), or 0.
	 *
	 * @param \WP_Term $term Blog category.
	 */
	public static function cover_id( \WP_Term $term ): int {
		$posts = get_posts(
			array(
				'cat'                 => $term->term_id,
				'numberposts'         => 1,
				'fields'              => 'ids',
				'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one post per category, only for picture looks.
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		return $posts ? (int) get_post_thumbnail_id( (int) $posts[0] ) : 0;
	}
}
