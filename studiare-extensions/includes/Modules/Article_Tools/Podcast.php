<?php
/**
 * The podcast of one post: what the edit screen saves (`_stx_podcast`) and
 * the facts the player shows (audio address, title, cover, duration).
 *
 * The audio is a media library file (its current address is used, so moving
 * the uploads to another domain or a CDN keeps working) or any link the
 * editor pastes.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Article_Tools;

use StudiareExt\Core\Persian;
use StudiareExt\Core\Sanitizer;

defined( 'ABSPATH' ) || exit;

final class Podcast {

	public const META = '_stx_podcast';

	/** Seconds the "back" and "forward" buttons jump, as in podcast apps. */
	public const SKIP_BACK    = 15;
	public const SKIP_FORWARD = 30;

	/** Bars of the decorative waveform. */
	private const WAVE_BARS = 48;

	/** Player glyphs, shared by the post player and the Podcast list widget. */
	private const GLYPHS = array(
		'play'     => '<path d="M8 5.6v12.8a1.1 1.1 0 0 0 1.7.93l10-6.4a1.1 1.1 0 0 0 0-1.86l-10-6.4A1.1 1.1 0 0 0 8 5.6z"/>',
		'pause'    => '<rect x="6" y="5" width="4.2" height="14" rx="1.3"/><rect x="13.8" y="5" width="4.2" height="14" rx="1.3"/>',
		'back'     => '<path d="M3.5 12a8.5 8.5 0 1 0 2.5-6L3.5 8.5"/><path d="M3.5 3.5v5h5"/>',
		'forward'  => '<path d="M20.5 12a8.5 8.5 0 1 1-2.5-6l2.5 2.5"/><path d="M20.5 3.5v5h-5"/>',
		'previous' => '<rect x="5" y="5" width="2.6" height="14" rx="1"/><path d="M19 6.3v11.4a1 1 0 0 1-1.56.83l-8.3-5.7a1 1 0 0 1 0-1.66l8.3-5.7A1 1 0 0 1 19 6.3z"/>',
		'next'     => '<rect x="16.4" y="5" width="2.6" height="14" rx="1"/><path d="M5 6.3v11.4a1 1 0 0 0 1.56.83l8.3-5.7a1 1 0 0 0 0-1.66l-8.3-5.7A1 1 0 0 0 5 6.3z"/>',
	);

	/** Longest duration accepted (a day), against typos such as 1200:00:00. */
	private const MAX_DURATION = 86400;

	public static function defaults(): array {
		return array(
			'audio_id' => 0,
			'url'      => '',
			'title'    => '',
			'speaker'  => '',
			'cover_id' => 0,
			'duration' => 0,
		);
	}

	public static function fields(): array {
		return array(
			'audio_id' => array(
				'type' => 'int',
				'min'  => 0,
			),
			'url'      => array( 'type' => 'url' ),
			'title'    => array(
				'type'       => 'text',
				'max_length' => 120,
			),
			'speaker'  => array(
				'type'       => 'text',
				'max_length' => 80,
			),
			'cover_id' => array(
				'type' => 'int',
				'min'  => 0,
			),
			'duration' => array(
				'type' => 'int',
				'min'  => 0,
				'max'  => self::MAX_DURATION,
			),
		);
	}

	/**
	 * Saved podcast of a post, with every key present.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function stored( int $post_id ): array {
		$stored = get_post_meta( $post_id, self::META, true );

		return array_merge( self::defaults(), is_array( $stored ) ? array_intersect_key( $stored, self::defaults() ) : array() );
	}

	/**
	 * Sanitizes and saves the podcast of a post; without audio the meta is removed.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $input   Untrusted values (duration may be "12:34").
	 */
	public static function save( int $post_id, array $input ): void {
		if ( isset( $input['duration'] ) && is_scalar( $input['duration'] ) ) {
			$input['duration'] = self::parse_duration( (string) $input['duration'] );
		}

		$clean = Sanitizer::apply( self::fields(), $input, self::defaults() );

		if ( $clean['audio_id'] && ! wp_attachment_is( 'audio', $clean['audio_id'] ) ) {
			$clean['audio_id'] = 0;
		}
		if ( $clean['cover_id'] && ! wp_attachment_is_image( $clean['cover_id'] ) ) {
			$clean['cover_id'] = 0;
		}

		if ( '' === self::audio_url( $clean ) ) {
			delete_post_meta( $post_id, self::META );
			return;
		}

		update_post_meta( $post_id, self::META, $clean );
	}

	/**
	 * Address of the audio: the media file's current URL, else the pasted link.
	 *
	 * @param array $data Saved podcast.
	 */
	public static function audio_url( array $data ): string {
		$file = $data['audio_id'] ? wp_get_attachment_url( (int) $data['audio_id'] ) : '';

		return $file ? (string) $file : (string) $data['url'];
	}

	/**
	 * What the player shows for a post, or null when it has no podcast.
	 *
	 * @param \WP_Post $post Post.
	 * @return array{id:int, src:string, type:string, title:string, speaker:string, cover_id:int, artwork:string, duration:int, file_name:string, wave:int[]}|null
	 */
	public static function view( \WP_Post $post ): ?array {
		$data = self::stored( (int) $post->ID );
		$src  = self::audio_url( $data );

		if ( '' === $src ) {
			return null;
		}

		$duration = (int) $data['duration'];
		if ( ! $duration && $data['audio_id'] ) {
			$meta     = wp_get_attachment_metadata( (int) $data['audio_id'] );
			$duration = is_array( $meta ) ? (int) ( $meta['length'] ?? 0 ) : 0;
		}

		$cover_id = $data['cover_id'] ? (int) $data['cover_id'] : (int) get_post_thumbnail_id( $post );
		$path     = (string) wp_parse_url( $src, PHP_URL_PATH );
		$type     = wp_check_filetype( $path );

		return array(
			'id'        => (int) $post->ID,
			'src'       => $src,
			'type'      => $data['audio_id'] ? (string) get_post_mime_type( (int) $data['audio_id'] ) : (string) $type['type'],
			'title'     => '' !== $data['title'] ? $data['title'] : get_the_title( $post ),
			'speaker'   => $data['speaker'],
			'cover_id'  => $cover_id,
			'artwork'   => $cover_id ? (string) wp_get_attachment_image_url( $cover_id, 'medium' ) : '',
			'duration'  => $duration,
			'file_name' => wp_basename( $path ),
			'wave'      => self::waveform( (int) $post->ID ),
		);
	}

	/**
	 * One player glyph as inline SVG (back and forward are drawn with the
	 * stroke the stylesheet gives them, the others are filled).
	 *
	 * @param string $name       `play`, `pause`, `back`, `forward`, `previous` or `next`.
	 * @param string $class_name Class on the <svg>.
	 */
	public static function glyph( string $name, string $class_name = '' ): string {
		$class = '' !== $class_name ? ' class="' . esc_attr( $class_name ) . '"' : '';

		return '<svg' . $class . ' viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ( self::GLYPHS[ $name ] ?? '' ) . '</svg>';
	}

	/**
	 * A made-up episode with the shape of view(), for the settings preview.
	 */
	public static function sample(): array {
		return array(
			'id'        => 0,
			'src'       => '#',
			'type'      => 'audio/mpeg',
			'title'     => __( 'Five study habits that really work', 'studiare-extensions' ),
			'speaker'   => __( 'Read by the author', 'studiare-extensions' ),
			'cover_id'  => 0,
			'artwork'   => '',
			'duration'  => 754,
			'file_name' => 'episode.mp3',
			'wave'      => self::waveform( 7 ),
		);
	}

	/**
	 * Reads a duration typed as "12:34", "1:02:03" or seconds, with Latin or
	 * Persian digits. Anything else is 0 (the player then reads the file).
	 *
	 * @param string $text Typed duration.
	 */
	public static function parse_duration( string $text ): int {
		$text = trim( Persian::latin_digits( $text ) );

		if ( ! preg_match( '/^\d{1,6}(?::\d{1,2}){0,2}$/', $text ) ) {
			return 0;
		}

		$seconds = 0;
		foreach ( explode( ':', $text ) as $part ) {
			$seconds = $seconds * 60 + (int) $part;
		}

		return min( self::MAX_DURATION, $seconds );
	}

	/**
	 * "3:07" or "1:02:03", with Latin digits.
	 *
	 * @param int $seconds Seconds.
	 */
	public static function clock( int $seconds ): string {
		$hours   = intdiv( $seconds, 3600 );
		$minutes = intdiv( $seconds % 3600, 60 );
		$rest    = $seconds % 60;

		return $hours ? sprintf( '%d:%02d:%02d', $hours, $minutes, $rest ) : sprintf( '%d:%02d', $minutes, $rest );
	}

	/**
	 * Bar heights (percent) of the decorative waveform. Derived from the post
	 * ID, so each episode has its own shape that never changes between visits,
	 * softened like speech and lower at both ends.
	 *
	 * @param int $seed Post ID.
	 * @return int[]
	 */
	private static function waveform( int $seed ): array {
		$state = ( $seed % 2147483646 ) + 1;
		$raw   = array();
		for ( $i = 0; $i < self::WAVE_BARS; $i++ ) {
			// Park–Miller generator: the same post always draws the same bars.
			$state = ( $state * 48271 ) % 2147483647;
			$raw[] = 14 + ( $state % 87 );
		}

		$bars = array();
		$last = self::WAVE_BARS - 1;
		for ( $i = 0; $i <= $last; $i++ ) {
			$smooth   = ( $raw[ max( 0, $i - 1 ) ] + 3 * $raw[ $i ] + $raw[ min( $last, $i + 1 ) ] ) / 5;
			$envelope = 0.55 + 0.45 * sin( M_PI * $i / $last );
			$bars[]   = max( 12, (int) round( $smooth * $envelope ) );
		}

		return $bars;
	}
}
