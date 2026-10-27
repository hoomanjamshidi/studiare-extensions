<?php
/**
 * Where an audio file lives and what it is: a file on this server (sent by
 * Stream, so its real address is never printed) or a link to another host.
 *
 * Files uploaded from the product box go to WooCommerce's protected
 * `woocommerce_uploads` folder, whose addresses the web server refuses;
 * only Stream can read them from disk.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

defined( 'ABSPATH' ) || exit;

final class Files {

	/**
	 * A playable source: `path` (a readable local file) or `url` (another host).
	 *
	 * @param int    $attachment_id Media library file, or 0.
	 * @param string $url           Link, used when there is no attachment.
	 * @return array{path:string, url:string, mime:string, length:int}|null
	 */
	public static function source( int $attachment_id, string $url ): ?array {
		if ( $attachment_id ) {
			$path = (string) get_attached_file( $attachment_id );

			return is_readable( $path ) ? self::local( $path, $attachment_id ) : null;
		}

		if ( '' === $url ) {
			return null;
		}

		$path = self::uploads_path( $url );
		if ( '' !== $path ) {
			return self::local( $path, (int) attachment_url_to_postid( $url ) );
		}

		$type = wp_check_filetype( (string) wp_parse_url( $url, PHP_URL_PATH ) );

		return array(
			'path'   => '',
			'url'    => $url,
			'mime'   => (string) $type['type'],
			'length' => 0,
		);
	}

	/**
	 * Length in seconds that WordPress read when the file was uploaded.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public static function attachment_length( int $attachment_id ): int {
		$meta = wp_get_attachment_metadata( $attachment_id );

		return is_array( $meta ) ? (int) ( $meta['length'] ?? 0 ) : 0;
	}

	/**
	 * Whether the first seconds of a file can be cut by bytes: MP3 frames
	 * stand on their own, while an M4A/AAC file usually keeps its index at
	 * the end and does not play when cut. The file's own first bytes decide,
	 * because WordPress gives .m4a files the `audio/mpeg` type too.
	 *
	 * @param string $path Local file.
	 */
	public static function is_mp3( string $path ): bool {
		$head = (string) file_get_contents( $path, false, null, 0, 3 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- 3 bytes of a local file.
		if ( 3 !== strlen( $head ) ) {
			return false;
		}

		// An ID3 tag, or straight away an MPEG audio frame (11 sync bits, layer III).
		return 'ID3' === $head || ( 0xff === ord( $head[0] ) && 0xe2 === ( ord( $head[1] ) & 0xe6 ) );
	}

	/**
	 * Size in bytes of an MP3's leading ID3v2 tag (cover art and the like),
	 * which plays no sound and is left out when the preview length is worked out.
	 *
	 * @param string $path Local file.
	 */
	public static function id3_size( string $path ): int {
		$head = (string) file_get_contents( $path, false, null, 0, 10 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- 10 bytes of a local file.
		if ( 10 !== strlen( $head ) || 'ID3' !== substr( $head, 0, 3 ) ) {
			return 0;
		}

		// The size is "synchsafe": four bytes of seven bits each.
		$size = 0;
		for ( $i = 6; $i < 10; $i++ ) {
			$size = ( $size << 7 ) | ( ord( $head[ $i ] ) & 0x7f );
		}

		return $size + 10;
	}

	/**
	 * @param string $path          Local file.
	 * @param int    $attachment_id Attachment of the file, or 0.
	 * @return array{path:string, url:string, mime:string, length:int}
	 */
	private static function local( string $path, int $attachment_id ): array {
		$type = wp_check_filetype( $path );

		return array(
			'path'   => $path,
			'url'    => '',
			'mime'   => $attachment_id ? (string) get_post_mime_type( $attachment_id ) : (string) $type['type'],
			'length' => $attachment_id ? self::attachment_length( $attachment_id ) : 0,
		);
	}

	/**
	 * The file behind a link into this site's uploads folder, or ''. Studiare
	 * lessons store their audio as a link, which usually points there.
	 *
	 * @param string $url Link.
	 */
	private static function uploads_path( string $url ): string {
		$uploads = wp_get_upload_dir();
		$base    = set_url_scheme( (string) $uploads['baseurl'], 'https' );
		$link    = set_url_scheme( strtok( $url, '?#' ), 'https' );

		if ( 0 !== strpos( $link, trailingslashit( $base ) ) ) {
			return '';
		}

		$root = realpath( (string) $uploads['basedir'] );
		$path = realpath( $uploads['basedir'] . '/' . rawurldecode( substr( $link, strlen( $base ) + 1 ) ) );

		// realpath() resolves "../", so a crafted link cannot leave the uploads folder.
		return $root && $path && 0 === strpos( $path, $root . DIRECTORY_SEPARATOR ) && is_readable( $path ) ? $path : '';
	}
}
