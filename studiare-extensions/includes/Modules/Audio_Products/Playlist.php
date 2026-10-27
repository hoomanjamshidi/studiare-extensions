<?php
/**
 * The tracks of one product as one visitor may hear them.
 *
 * Every track gets a state: `full` (bought, free, or an editor of the
 * product), `preview` (only the free preview: its first seconds or a
 * separate file) or `locked` (with the reason: `buy`, `login` or `date`).
 * The player is printed from this list and Stream serves files only after
 * asking it again, so the rules live in one place.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Playlist {

	/** @var int */
	private $product_id;

	/** @var int */
	private $user_id;

	/** @var array Module settings. */
	private $settings;

	/** @var array|null Resolved tracks, built once. */
	private $tracks = null;

	/** @var bool|null */
	private $owned = null;

	/**
	 * @param int   $product_id Product ID.
	 * @param int   $user_id    Visitor (0 for guests).
	 * @param array $settings   Module settings.
	 */
	public function __construct( int $product_id, int $user_id, array $settings ) {
		$this->product_id = $product_id;
		$this->user_id    = $user_id;
		$this->settings   = $settings;
	}

	public function product_id(): int {
		return $this->product_id;
	}

	/** Whether the visitor bought the product (or edits it, to check the files). */
	public function owned(): bool {
		if ( null === $this->owned ) {
			$this->owned = $this->user_id && ( Theme_Bridge::user_has_course( $this->user_id, $this->product_id ) || user_can( $this->user_id, 'edit_post', $this->product_id ) );
		}

		return $this->owned;
	}

	/**
	 * Tracks with their state and sources (file paths stay on the server).
	 *
	 * @return array<int, array{key:string, title:string, section:string, duration:int, state:string, reason:string, opens:int, limit:int, full:?array, preview:?array, download:bool}>
	 */
	public function tracks(): array {
		if ( null === $this->tracks ) {
			$album        = Album::stored( $this->product_id );
			$this->tracks = array();

			foreach ( $album['tracks'] as $track ) {
				$this->tracks[] = $this->own_track( $track, (bool) $album['download'] );
			}
			if ( $album['lessons'] ) {
				foreach ( Lessons::audio( $this->product_id ) as $lesson ) {
					$this->tracks[] = $this->lesson_track( $lesson, (bool) $album['download'] );
				}
			}

			$this->tracks = array_values( array_filter( $this->tracks ) );
		}

		return $this->tracks;
	}

	/**
	 * One track by its key, or null.
	 *
	 * @param string $key Track key.
	 */
	public function track( string $key ): ?array {
		foreach ( $this->tracks() as $track ) {
			if ( $track['key'] === $key ) {
				return $track;
			}
		}

		return null;
	}

	/** Whether any track can be played by this visitor (fully or as a preview). */
	public function playable(): bool {
		foreach ( $this->tracks() as $track ) {
			if ( 'locked' !== $track['state'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $track    Saved track (Album::track_defaults() shape).
	 * @param bool  $download Whether buyers may download.
	 */
	private function own_track( array $track, bool $download ): ?array {
		$full = Files::source( (int) $track['audio_id'], (string) $track['url'] );
		if ( ! $full ) {
			return null;
		}

		$view = $this->base( (string) $track['id'], (string) $track['title'], '', (int) $track['duration'] ? (int) $track['duration'] : $full['length'], $full, $download );

		if ( $this->owned() || 'free' === $track['preview'] ) {
			return $view;
		}

		if ( 'file' === $track['preview'] ) {
			return $this->with_preview( $view, Files::source( (int) $track['preview_id'], '' ), 0 );
		}

		return 'clip' === $track['preview'] ? $this->with_clip( $view, (int) $track['clip'] ) : $this->locked( $view, 'buy' );
	}

	/**
	 * @param array $lesson   Lessons::audio() item.
	 * @param bool  $download Whether buyers may download.
	 */
	private function lesson_track( array $lesson, bool $download ): ?array {
		$full = Files::source( 0, $lesson['url'] );
		if ( ! $full ) {
			return null;
		}

		$view = $this->base( 'l' . $lesson['id'], $lesson['title'], $lesson['section'], $full['length'], $full, $download );

		if ( user_can( $this->user_id, 'edit_post', $this->product_id ) ) {
			return $view;
		}

		// As in the theme: privacy first, then the release date for those who may listen.
		if ( 'login' === $lesson['rule'] && ! $this->user_id ) {
			return $this->locked( $view, 'login' );
		}
		if ( 'buyer' === $lesson['rule'] && ! $this->owned() ) {
			return $this->settings['preview']['lessons'] ? $this->with_clip( $view, (int) $this->settings['preview']['seconds'] ) : $this->locked( $view, 'buy' );
		}

		$opens = max( $lesson['date'], $this->owned() ? Lessons::opens_after_purchase( $this->user_id, $this->product_id, $lesson['after_days'] ) : 0 );
		if ( $opens > current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Studiare compares its local-time dates with this.
			$view['opens'] = $opens;
			return $this->locked( $view, 'date' );
		}

		return $view;
	}

	/**
	 * A track playable in full.
	 *
	 * @param string $key      Track key.
	 * @param string $title    Title.
	 * @param string $section  Course section (lessons only).
	 * @param int    $duration Seconds, or 0 when unknown.
	 * @param array  $full     Files::source() of the full file.
	 * @param bool   $download Whether the product allows downloads (to buyers).
	 */
	private function base( string $key, string $title, string $section, int $duration, array $full, bool $download ): array {
		return array(
			'key'      => $key,
			'title'    => '' !== $title ? $title : __( 'Untitled track', 'studiare-extensions' ),
			'section'  => $section,
			'duration' => $duration,
			'state'    => 'full',
			'reason'   => '',
			'opens'    => 0,
			'limit'    => 0,
			'full'     => $full,
			'preview'  => null,
			// Downloading is for buyers; free tracks are only played to others.
			'download' => $download && $this->owned(),
		);
	}

	/**
	 * @param array      $view    Track.
	 * @param array|null $preview Files::source() of the preview.
	 * @param int        $limit   Seconds the preview may play (0 = all of it).
	 */
	private function with_preview( array $view, ?array $preview, int $limit ): array {
		if ( ! $preview ) {
			return $this->locked( $view, 'buy' );
		}

		$view['state']    = 'preview';
		$view['reason']   = 'buy';
		$view['limit']    = $limit;
		$view['preview']  = $preview;
		$view['full']     = null;
		$view['download'] = false;

		return $view;
	}

	/**
	 * The first seconds of the full file. Only a local MP3 of known length
	 * can be cut by the server; anything else stays locked rather than
	 * sending the whole file to a visitor who has not bought it.
	 *
	 * @param array $view    Track.
	 * @param int   $seconds Preview length.
	 */
	private function with_clip( array $view, int $seconds ): array {
		$full = $view['full'];
		if ( '' === $full['path'] || ! Files::is_mp3( $full['path'] ) || ! $view['duration'] ) {
			return $this->locked( $view, 'buy' );
		}

		return $this->with_preview( $view, $full, min( $seconds, $view['duration'] ) );
	}

	/**
	 * @param array  $view   Track.
	 * @param string $reason `buy`, `login` or `date`.
	 */
	private function locked( array $view, string $reason ): array {
		$view['state']    = 'locked';
		$view['reason']   = $reason;
		$view['full']     = null;
		$view['preview']  = null;
		$view['download'] = false;

		return $view;
	}
}
