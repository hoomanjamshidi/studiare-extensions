<?php
/**
 * Which post types a search button covers.
 *
 * Saved post types are re-checked on every request, because the plugin that
 * registered one may since have been deactivated. An empty list means
 * "everything WordPress search normally includes".
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Bottom_Nav;

use StudiareExt\Core\Search_Query;

defined( 'ABSPATH' ) || exit;

final class Search_Scope {

	/**
	 * Post types an admin can search in.
	 *
	 * @return array<string, string> Post type name => plural label.
	 */
	public static function choices(): array {
		return Search_Query::searchable_types();
	}

	/**
	 * The item's saved post types that can still be searched.
	 *
	 * @param array $item Saved search item.
	 * @return string[] Empty when the item searches everything.
	 */
	public static function post_types( array $item ): array {
		return array_values( array_intersect( $item['search_post_types'], array_keys( self::choices() ) ) );
	}

	/**
	 * The `post_type` sent to the classic results page (`/?s=`), or '' for
	 * everything.
	 *
	 * Only a single type can be sent: Studiare reads `post_type` from the URL
	 * as one string (an array empties its search). With several types the
	 * results page searches everything, a superset, so nothing the admin
	 * chose goes missing; the live results stay exact.
	 *
	 * @param string[] $post_types Validated post types.
	 */
	public static function for_results_page( array $post_types ): string {
		return 1 === count( $post_types ) ? $post_types[0] : '';
	}
}
