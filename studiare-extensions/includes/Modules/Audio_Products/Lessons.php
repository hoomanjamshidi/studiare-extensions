<?php
/**
 * Read-only bridge to the audio of Studiare's course lessons.
 *
 * A Studiare product lists its lessons in `lessons_group` (sections, each
 * with `title` and the lesson IDs in `lessons_list`); each lesson keeps its
 * fields in `lesson_data[0]`: `lesson_title`, `lesson_audio` (a link),
 * `private_lesson` ('' = open to all, `lesson_free_users` = logged-in
 * visitors, `on`/`lesson_protected` = buyers) and the release rules
 * `lesson_available_date` / `lesson_available_after_days`. Playlist applies
 * the theme's rules (`inc/studi_lessons.php`), stricter in one place: the
 * player opens "logged-in users" lessons to buyers only, so it never opens
 * a lesson the course page keeps closed.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

defined( 'ABSPATH' ) || exit;

final class Lessons {

	/** Studiare's quiz post type, listed among the lessons but without audio. */
	private const QUIZ_TYPE = 'sc-quiz';

	/**
	 * The lessons of a product that have audio, in course order.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array{id:int, title:string, section:string, url:string, rule:string, date:int, after_days:int}>
	 */
	public static function audio( int $product_id ): array {
		$tracks = array();

		foreach ( self::sections( $product_id ) as $section ) {
			$title = isset( $section['title'] ) && is_string( $section['title'] ) ? $section['title'] : '';

			foreach ( (array) ( $section['lessons_list'] ?? array() ) as $lesson_id ) {
				$track = is_numeric( $lesson_id ) ? self::lesson( (int) $lesson_id, $title ) : null;
				if ( $track ) {
					$tracks[] = $track;
				}
			}
		}

		return $tracks;
	}

	/**
	 * Whether a product has Studiare lessons at all (the edit box only offers
	 * the lesson switch then).
	 *
	 * @param int $product_id Product ID.
	 */
	public static function exist( int $product_id ): bool {
		return (bool) self::sections( $product_id );
	}

	/**
	 * @param int $product_id Product ID.
	 */
	private static function sections( int $product_id ): array {
		$groups = get_post_meta( $product_id, 'lessons_group', true );

		return is_array( $groups ) ? array_filter( $groups, 'is_array' ) : array();
	}

	/**
	 * @param int    $lesson_id Lesson ID.
	 * @param string $section   Title of its section.
	 */
	private static function lesson( int $lesson_id, string $section ): ?array {
		if ( self::QUIZ_TYPE === get_post_type( $lesson_id ) || 'publish' !== get_post_status( $lesson_id ) ) {
			return null;
		}

		$data = get_post_meta( $lesson_id, 'lesson_data', true );
		$item = is_array( $data ) && isset( $data[0] ) && is_array( $data[0] ) ? $data[0] : array();
		$url  = isset( $item['lesson_audio'] ) && is_string( $item['lesson_audio'] ) ? esc_url_raw( trim( $item['lesson_audio'] ) ) : '';

		if ( '' === $url ) {
			return null;
		}

		$title = isset( $item['lesson_title'] ) && is_string( $item['lesson_title'] ) && '' !== trim( $item['lesson_title'] ) ? $item['lesson_title'] : get_the_title( $lesson_id );
		$date  = isset( $item['lesson_available_date'] ) && is_string( $item['lesson_available_date'] ) ? strtotime( trim( $item['lesson_available_date'] ) ) : false;

		return array(
			'id'         => $lesson_id,
			'title'      => wp_strip_all_tags( (string) $title ),
			'section'    => wp_strip_all_tags( $section ),
			'url'        => $url,
			'rule'       => self::rule( (string) ( $item['private_lesson'] ?? '' ) ),
			'date'       => false !== $date ? (int) $date : 0,
			'after_days' => max( 0, (int) ( $item['lesson_available_after_days'] ?? 0 ) ),
		);
	}

	/**
	 * Who may listen: `all`, `login` (any logged-in visitor) or `buyer`.
	 *
	 * @param string $privacy Studiare's `private_lesson` value.
	 */
	private static function rule( string $privacy ): string {
		if ( 'lesson_free_users' === $privacy ) {
			return 'login';
		}

		return in_array( $privacy, array( 'on', 'lesson_protected' ), true ) ? 'buyer' : 'all';
	}

	/**
	 * When a lesson opens for a buyer who must wait some days after buying
	 * (Studiare's "available after N days"), or 0 when that is unknown.
	 * Studiare keeps the purchase time behind its own cached helper.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 * @param int $days       Days after the purchase.
	 */
	public static function opens_after_purchase( int $user_id, int $product_id, int $days ): int {
		if ( ! $days || ! $user_id || ! function_exists( 'studi_get_user_last_purchase_time_cached' ) ) {
			return 0;
		}

		$bought = studi_get_user_last_purchase_time_cached( $user_id, $product_id );

		return false !== $bought ? (int) strtotime( '+' . $days . ' days', (int) $bought ) : 0;
	}
}
