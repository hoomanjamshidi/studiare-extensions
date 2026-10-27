<?php
/**
 * What the player of an audio product shows: the product, its tracks with
 * their states and addresses, and the buy or log-in prompt. views/player.php
 * prints it; the settings preview prints sample() the same way.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

use StudiareExt\Core\Persian;
use StudiareExt\Modules\Article_Tools\Podcast;

defined( 'ABSPATH' ) || exit;

final class Player {

	/**
	 * @param \WC_Product $product  Product.
	 * @param Playlist    $playlist Its tracks for the current visitor.
	 * @param array       $settings Module settings.
	 */
	public static function view( \WC_Product $product, Playlist $playlist, array $settings ): array {
		$product_id = (int) $product->get_id();
		$tracks     = array();

		foreach ( $playlist->tracks() as $track ) {
			$tracks[] = array(
				'key'      => $track['key'],
				'title'    => $track['title'],
				'section'  => $track['section'],
				'duration' => $track['duration'],
				'state'    => $track['state'],
				'limit'    => $track['limit'],
				'note'     => self::note( $track, $playlist->owned() ),
				'src'      => 'locked' !== $track['state'] ? Stream::url( $product_id, $track['key'] ) : '',
				'download' => $track['download'] ? Stream::url( $product_id, $track['key'], 'download' ) : '',
			);
		}

		$cover_id = (int) $product->get_image_id();

		return array(
			'id'       => $product_id,
			'title'    => $product->get_name(),
			'cover_id' => $cover_id,
			'artwork'  => $cover_id ? (string) wp_get_attachment_image_url( $cover_id, 'medium' ) : '',
			'owned'    => $playlist->owned(),
			'tracks'   => $tracks,
			'total'    => (int) array_sum( array_column( $tracks, 'duration' ) ),
			'buy'      => $playlist->owned() || ! $settings['preview']['cta'] ? null : self::buy( $product ),
			'login'    => is_user_logged_in() ? '' : (string) wc_get_page_permalink( 'myaccount' ),
		);
	}

	/**
	 * A made-up album with the shape of view(), for the settings preview.
	 */
	public static function sample(): array {
		$track = static function ( string $key, string $title, int $duration, string $state, int $limit, string $note ): array {
			return array(
				'key'      => $key,
				'title'    => $title,
				'section'  => '',
				'duration' => $duration,
				'state'    => $state,
				'limit'    => $limit,
				'note'     => $note,
				'src'      => 'locked' !== $state ? '#' : '',
				'download' => '',
			);
		};

		return array(
			'id'       => 0,
			'title'    => __( 'The habits of great learners (audio book)', 'studiare-extensions' ),
			'cover_id' => 0,
			'artwork'  => '',
			'owned'    => false,
			'tracks'   => array(
				$track( 's1', __( 'Introduction', 'studiare-extensions' ), 312, 'full', 0, __( 'Free', 'studiare-extensions' ) ),
				$track( 's2', __( 'Chapter one: attention', 'studiare-extensions' ), 1490, 'preview', 60, self::preview_note( 60 ) ),
				$track( 's3', __( 'Chapter two: memory', 'studiare-extensions' ), 1725, 'locked', 0, __( 'After purchase', 'studiare-extensions' ) ),
			),
			'total'    => 3527,
			'buy'      => array(
				'url'  => '#',
				'text' => __( 'Buy', 'studiare-extensions' ),
			),
			'login'    => '',
		);
	}

	/**
	 * "3:07" or "1:02:03", in Persian digits on Persian sites.
	 *
	 * @param int $seconds Seconds.
	 */
	public static function clock( int $seconds ): string {
		$time = Podcast::clock( $seconds );

		return Persian::is_site_persian() ? Persian::digits( $time ) : $time;
	}

	/**
	 * The short note beside a track: why it is closed, or what a visitor
	 * who has not bought the product may hear.
	 *
	 * @param array $track Playlist track.
	 * @param bool  $owned Whether the visitor bought the product.
	 */
	private static function note( array $track, bool $owned ): string {
		if ( 'preview' === $track['state'] ) {
			return $track['limit'] ? self::preview_note( $track['limit'] ) : __( 'Preview', 'studiare-extensions' );
		}

		if ( 'locked' === $track['state'] ) {
			if ( 'login' === $track['reason'] ) {
				return __( 'Log in to listen', 'studiare-extensions' );
			}
			if ( 'date' === $track['reason'] ) {
				/* translators: %s: date the lesson opens. */
				return sprintf( __( 'Opens on %s', 'studiare-extensions' ), self::date( $track['opens'] ) );
			}

			return __( 'After purchase', 'studiare-extensions' );
		}

		return $owned ? '' : __( 'Free', 'studiare-extensions' );
	}

	/**
	 * @param int $seconds Preview length.
	 */
	private static function preview_note( int $seconds ): string {
		/* translators: %s: length of the free preview, e.g. 1:00. */
		return sprintf( __( 'Preview %s', 'studiare-extensions' ), self::clock( $seconds ) );
	}

	/**
	 * Buy button of a product that can be bought now: simple products go
	 * straight to the cart, others to their page to choose an option.
	 *
	 * @param \WC_Product $product Product.
	 * @return array{url:string, text:string}|null
	 */
	private static function buy( \WC_Product $product ): ?array {
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return null;
		}

		return array(
			'url'  => $product->is_type( 'simple' ) ? (string) $product->add_to_cart_url() : (string) $product->get_permalink(),
			'text' => (string) $product->single_add_to_cart_text(),
		);
	}

	/**
	 * A lesson's opening date (Studiare stores it in local time).
	 *
	 * @param int $timestamp Local timestamp.
	 */
	private static function date( int $timestamp ): string {
		if ( Persian::is_site_persian() ) {
			return Persian::digits( Persian::jalali_date( new \DateTimeImmutable( '@' . $timestamp ) ) );
		}

		return date_i18n( (string) get_option( 'date_format' ), $timestamp );
	}
}
