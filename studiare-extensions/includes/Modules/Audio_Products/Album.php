<?php
/**
 * The audio of one product, as the product edit screen saves it
 * (`_stx_audio`): its tracks, whether buyers may download them and whether
 * the audio of the course's Studiare lessons joins the list.
 *
 * Each track has a full file (a media library file, or a link to another
 * host) and a free preview: none, the whole track, its first seconds (the
 * server sends only that part of the file) or a separate preview file.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

use StudiareExt\Core\Sanitizer;
use StudiareExt\Modules\Article_Tools\Podcast;

defined( 'ABSPATH' ) || exit;

final class Album {

	public const META = '_stx_audio';

	/** How a visitor who has not bought the product can listen to a track. */
	public const PREVIEWS = array( 'none', 'free', 'clip', 'file' );

	/** Most tracks one product can hold. */
	private const MAX_TRACKS = 200;

	/** Track ids: `t` and eight letters or digits, made once and kept, so listening positions survive reordering. */
	private const ID_PATTERN = '/^t[a-z0-9]{8}$/';

	public static function defaults(): array {
		return array(
			'download' => true,
			'lessons'  => false,
			'tracks'   => array(),
		);
	}

	public static function track_defaults(): array {
		return array(
			'id'         => '',
			'title'      => '',
			'audio_id'   => 0,
			'url'        => '',
			'duration'   => 0,
			'preview'    => 'clip',
			'clip'       => 60,
			'preview_id' => 0,
		);
	}

	public static function fields(): array {
		return array(
			'download' => array( 'type' => 'bool' ),
			'lessons'  => array( 'type' => 'bool' ),
			'tracks'   => array(
				'type'     => 'list',
				'max'      => self::MAX_TRACKS,
				'defaults' => self::track_defaults(),
				'fields'   => array(
					'id'         => array( 'type' => 'key' ),
					'title'      => array(
						'type'       => 'text',
						'max_length' => 160,
					),
					'audio_id'   => array(
						'type' => 'int',
						'min'  => 0,
					),
					'url'        => array( 'type' => 'url' ),
					'duration'   => array(
						'type' => 'int',
						'min'  => 0,
						'max'  => 86400,
					),
					'preview'    => array(
						'type'    => 'enum',
						'options' => self::PREVIEWS,
					),
					'clip'       => array(
						'type' => 'int',
						'min'  => Schema::MIN_CLIP,
						'max'  => Schema::MAX_CLIP,
					),
					'preview_id' => array(
						'type' => 'int',
						'min'  => 0,
					),
				),
			),
		);
	}

	/**
	 * Saved audio of a product, with every key present.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function stored( int $product_id ): array {
		$stored = get_post_meta( $product_id, self::META, true );
		$album  = array_merge( self::defaults(), is_array( $stored ) ? array_intersect_key( $stored, self::defaults() ) : array() );

		$album['tracks'] = array_map(
			static function ( $track ): array {
				return array_merge( self::track_defaults(), is_array( $track ) ? $track : array() );
			},
			is_array( $album['tracks'] ) ? array_values( $album['tracks'] ) : array()
		);

		return $album;
	}

	/**
	 * Sanitizes and saves the audio of a product. Tracks without a file are
	 * dropped; with no track and no lesson audio the meta is removed, so the
	 * product is no longer an audio product.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $input      Untrusted values (durations may be typed as "12:34").
	 */
	public static function save( int $product_id, array $input ): void {
		if ( isset( $input['tracks'] ) && is_array( $input['tracks'] ) ) {
			foreach ( $input['tracks'] as $index => $track ) {
				if ( is_array( $track ) && isset( $track['duration'] ) && is_scalar( $track['duration'] ) ) {
					$input['tracks'][ $index ]['duration'] = Podcast::parse_duration( (string) $track['duration'] );
				}
			}
		}

		$album           = Sanitizer::apply( self::fields(), $input, self::defaults() );
		$album['tracks'] = self::clean_tracks( $album['tracks'] );

		if ( ! $album['tracks'] && ! $album['lessons'] ) {
			delete_post_meta( $product_id, self::META );
			return;
		}

		update_post_meta( $product_id, self::META, $album );
	}

	/**
	 * IDs of the published products that have audio.
	 *
	 * @return int[]
	 */
	public static function product_ids(): array {
		return array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'meta_key'       => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one indexed key, only on the account page.
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			)
		);
	}

	/**
	 * Keeps the tracks that have a file, with valid attachments, a lasting id
	 * and a duration (read from the media library when none was typed).
	 *
	 * @param array $tracks Sanitized tracks.
	 */
	private static function clean_tracks( array $tracks ): array {
		$clean = array();
		$seen  = array();

		foreach ( $tracks as $track ) {
			if ( $track['audio_id'] && ! wp_attachment_is( 'audio', $track['audio_id'] ) ) {
				$track['audio_id'] = 0;
			}
			if ( $track['preview_id'] && ! wp_attachment_is( 'audio', $track['preview_id'] ) ) {
				$track['preview_id'] = 0;
			}
			// The pasted link is only kept for files from another host.
			if ( $track['audio_id'] ) {
				$track['url'] = '';
			}
			if ( ! $track['audio_id'] && '' === $track['url'] ) {
				continue;
			}

			if ( ! preg_match( self::ID_PATTERN, $track['id'] ) || isset( $seen[ $track['id'] ] ) ) {
				$track['id'] = 't' . strtolower( wp_generate_password( 8, false, false ) );
			}
			$seen[ $track['id'] ] = true;

			if ( ! $track['duration'] && $track['audio_id'] ) {
				$track['duration'] = Files::attachment_length( $track['audio_id'] );
			}

			$clean[] = $track;
		}

		return $clean;
	}
}
