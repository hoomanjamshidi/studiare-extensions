<?php
/**
 * Sends audio to the player: `/?stx_audio=<product>&track=<key>&part=play|download`.
 *
 * The real address of a file never reaches the page. Each request asks
 * Playlist what this visitor may hear, then sends the full file, only the
 * first seconds of it (a preview is cut by bytes, so the rest never leaves
 * the server) or the separate preview file, with HTTP Range support so
 * the player can seek. Files on another host are a redirect after the
 * same check. Answers are private and never cached, because they depend on
 * who is asking; the page itself can be cached as usual.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

defined( 'ABSPATH' ) || exit;

final class Stream {

	private const QUERY_VAR = 'stx_audio';

	/** Bytes read and sent at a time. */
	private const CHUNK = 262144;

	/** @var Module */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		// Before the theme or WooCommerce redirect or build the page.
		add_action( 'parse_request', array( $this, 'maybe_send' ), 0 );
	}

	/**
	 * Address of a track's audio.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $key        Track key.
	 * @param string $part       `play` or `download`.
	 */
	public static function url( int $product_id, string $key, string $part = 'play' ): string {
		return add_query_arg(
			array(
				self::QUERY_VAR => $product_id,
				'track'         => $key,
				'part'          => $part,
			),
			home_url( '/' )
		);
	}

	public function maybe_send(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only file request; access is checked per visitor below.
		if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		$product_id = absint( $_GET[ self::QUERY_VAR ] );
		$key        = isset( $_GET['track'] ) ? sanitize_key( wp_unslash( $_GET['track'] ) ) : '';
		$download   = isset( $_GET['part'] ) && 'download' === $_GET['part'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$track = $this->track( $product_id, $key );
		if ( ! $track || ( $download && ! $track['download'] ) ) {
			$this->refuse();
		}

		$source = 'preview' === $track['state'] ? $track['preview'] : $track['full'];
		if ( '' === $source['path'] ) {
			// Another host: the link is only given to those who may listen.
			$this->no_cache();
			wp_redirect( $source['url'], 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- the admin's own link to their file host.
			exit;
		}

		$limit = $track['limit'] ? $this->clip_bytes( $source['path'], $track['limit'], $track['duration'] ) : 0;
		$name  = sanitize_file_name( $track['title'] ) . '.' . pathinfo( $source['path'], PATHINFO_EXTENSION );

		$this->send( $source['path'], '' !== $source['mime'] ? $source['mime'] : 'application/octet-stream', $limit, $download ? $name : '' );
	}

	/**
	 * The track if this visitor may hear it, else null.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $key        Track key.
	 */
	private function track( int $product_id, string $key ): ?array {
		if ( ! $product_id || '' === $key || 'product' !== get_post_type( $product_id ) ) {
			return null;
		}

		$post = get_post( $product_id );
		if ( ! $post || ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $product_id ) ) || post_password_required( $post ) ) {
			return null;
		}

		$track = ( new Playlist( $product_id, get_current_user_id(), $this->module->settings() ) )->track( $key );

		return $track && 'locked' !== $track['state'] ? $track : null;
	}

	/**
	 * Bytes that hold the first seconds of an MP3: the ID3 tag plus the same
	 * share of the sound as of the length, with two seconds to spare (the
	 * player stops at the limit itself).
	 *
	 * @param string $path     Local file.
	 * @param int    $seconds  Preview length.
	 * @param int    $duration Length of the whole file.
	 */
	private function clip_bytes( string $path, int $seconds, int $duration ): int {
		$size = (int) filesize( $path );
		$tag  = Files::id3_size( $path );

		return min( $size, $tag + (int) ceil( ( $size - $tag ) * ( $seconds + 2 ) / max( 1, $duration ) ) );
	}

	/**
	 * Tells page caches and CDNs never to keep an answer: it depends on who
	 * asks, and a cached full file would reach visitors who did not buy.
	 */
	private function no_cache(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- the constant cache plugins read.
		}
		nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0, no-transform' );
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
		header( 'CDN-Cache-Control: no-store' );
	}

	/**
	 * Sends a local file (or its first `$limit` bytes), honouring one HTTP Range.
	 *
	 * @param string $path     Local file.
	 * @param string $mime     Media type.
	 * @param int    $limit    Bytes the visitor may get (0 = the whole file).
	 * @param string $download File name to save as, or '' to play.
	 */
	private function send( string $path, string $mime, int $limit, string $download ): void {
		$size  = $limit ? $limit : (int) filesize( $path );
		$range = $this->range( $size );

		if ( null === $range ) {
			status_header( 416 );
			header( 'Content-Range: bytes */' . $size );
			exit;
		}

		list( $start, $end ) = $range;
		$partial             = 0 !== $start || $end !== $size - 1;

		$this->clean_output();
		$this->no_cache();
		status_header( $partial ? 206 : 200 );
		header( 'Content-Type: ' . $mime );
		header( 'Accept-Ranges: bytes' );
		header( 'Content-Length: ' . ( $end - $start + 1 ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'Content-Disposition: ' . ( '' !== $download ? 'attachment; filename*=UTF-8\'\'' . rawurlencode( $download ) : 'inline' ) );
		if ( $partial ) {
			header( 'Content-Range: bytes ' . $start . '-' . $end . '/' . $size );
		}

		if ( 'HEAD' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			exit;
		}

		$this->stream( $path, $start, $end - $start + 1 );
		exit;
	}

	/**
	 * The requested byte range [start, end], the whole file without a Range
	 * header, or null when the range cannot be served.
	 *
	 * @param int $size Bytes available.
	 * @return int[]|null
	 */
	private function range( int $size ): ?array {
		$header = isset( $_SERVER['HTTP_RANGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ) : '';

		if ( ! preg_match( '/^bytes=(\d*)-(\d*)$/', trim( $header ), $match ) || ( '' === $match[1] && '' === $match[2] ) ) {
			return $size > 0 ? array( 0, $size - 1 ) : null;
		}

		if ( '' === $match[1] ) {
			// "bytes=-500": the last 500 bytes.
			$start = max( 0, $size - (int) $match[2] );
			$end   = $size - 1;
		} else {
			$start = (int) $match[1];
			$end   = '' === $match[2] ? $size - 1 : min( (int) $match[2], $size - 1 );
		}

		return $start <= $end && $start < $size ? array( $start, $end ) : null;
	}

	/**
	 * @param string $path   Local file.
	 * @param int    $start  First byte.
	 * @param int    $length Bytes to send.
	 */
	private function stream( string $path, int $start, int $length ): void {
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 ); // Long files on slow connections.
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions -- reads a local file in chunks; WP_Filesystem has no partial reads.
		$handle = fopen( $path, 'rb' );
		if ( ! $handle ) {
			return;
		}

		fseek( $handle, $start );
		while ( $length > 0 && ! feof( $handle ) && ! connection_aborted() ) {
			$chunk = fread( $handle, min( self::CHUNK, $length ) );
			if ( false === $chunk || '' === $chunk ) {
				break;
			}
			$length -= strlen( $chunk );
			echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary audio data.
			flush();
		}
		fclose( $handle );
		// phpcs:enable WordPress.WP.AlternativeFunctions
	}

	/** Drops output buffers (and compression) that would hold or alter the file. */
	private function clean_output(): void {
		@ini_set( 'zlib.output_compression', 'Off' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.Risky -- hosts may forbid it.

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
	}

	/** @return never */
	private function refuse(): void {
		$this->no_cache();
		status_header( 403 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html__( 'You cannot listen to this file.', 'studiare-extensions' );
		exit;
	}
}
