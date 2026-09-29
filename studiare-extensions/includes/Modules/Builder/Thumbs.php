<?php
/**
 * Wireframe thumbnails for the admin cards (RTL, like the designs).
 * The live preview shows the real rendering; these are the quick glance.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Thumbs {

	private const INK    = '#2e373c';
	private const MUTED  = '#c9d1d6';
	private const LINE   = '#d5dce0';
	private const BG     = '#f4f6f7';
	private const MEDIA  = '#dde3e7';
	private const ACCENT = '#f8b000';
	private const WHITE  = '#ffffff';

	/**
	 * SVG markup for a preset key, a template kind or `theme`.
	 *
	 * @param string $key Preset key / kind.
	 */
	public static function svg( string $key ): string {
		$method = 'draw_' . str_replace( '-', '_', $key );
		$body   = method_exists( self::class, $method ) ? self::$method() : self::generic( $key );

		return '<svg class="stx-thumb" viewBox="0 0 240 150" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">' . $body . '</svg>';
	}

	/**
	 * @param float  $x      X.
	 * @param float  $y      Y.
	 * @param float  $w      Width.
	 * @param float  $h      Height.
	 * @param string $fill   Fill.
	 * @param float  $rx     Radius.
	 * @param string $stroke Stroke colour ('' for none).
	 */
	private static function r( $x, $y, $w, $h, string $fill, $rx = 2, string $stroke = '' ): string {
		return sprintf(
			'<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="%s"%s/>',
			$x,
			$y,
			$w,
			$h,
			$rx,
			$fill,
			'' !== $stroke ? ' stroke="' . $stroke . '" stroke-width="1"' : ''
		);
	}

	/**
	 * @param float  $cx   X.
	 * @param float  $cy   Y.
	 * @param float  $r    Radius.
	 * @param string $fill Fill.
	 */
	private static function c( $cx, $cy, $r, string $fill ): string {
		return sprintf( '<circle cx="%s" cy="%s" r="%s" fill="%s"/>', $cx, $cy, $r, $fill );
	}

	/** Text lines, right-aligned (RTL). */
	private static function lines( $right, $y, array $widths, string $fill = self::MUTED, $gap = 7, $h = 3.5 ): string {
		$out = '';
		foreach ( $widths as $i => $w ) {
			$out .= self::r( $right - $w, $y + $i * $gap, $w, $h, $fill, 1.5 );
		}

		return $out;
	}

	private static function play( $cx, $cy ): string {
		return self::c( $cx, $cy, 9, 'rgba(15,19,21,.55)' ) . sprintf( '<path d="M%1$s %2$sl7 4.5-7 4.5z" fill="#fff"/>', $cx - 2.5, $cy - 4.5 );
	}

	/** A faded page body under a header, or above a footer. */
	private static function page_body( $y, $h ): string {
		return self::r( 0, $y, 240, $h, self::BG, 0 )
			. self::r( 20, $y + 12, 200, 34, self::MEDIA, 6 )
			. self::lines( 220, $y + 54, array( 150, 120, 136 ) );
	}

	/* ---------------------------------------------------------------------
	 * Courses
	 * ------------------------------------------------------------------- */

	private static function draw_course_classic(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::lines( 232, 7, array( 70 ) )
			. self::r( 88, 17, 144, 70, self::MEDIA, 7 ) . self::play( 160, 52 )
			. self::r( 150, 93, 82, 7, self::INK ) . self::lines( 232, 105, array( 118, 96 ) )
			. self::r( 88, 124, 144, 18, self::WHITE, 5, self::LINE ) . self::r( 200, 139, 30, 2, self::ACCENT, 1 )
			. self::r( 8, 17, 74, 94, self::WHITE, 8, self::LINE )
			. self::r( 40, 26, 34, 7, self::INK ) . self::r( 56, 37, 18, 3.5, self::MUTED )
			. self::r( 15, 48, 60, 11, self::ACCENT, 4 ) . self::lines( 75, 67, array( 60, 60, 60, 44 ), self::MUTED, 9, 3 )
			. self::r( 8, 117, 74, 25, self::INK, 6 );
	}

	private static function draw_course_spotlight(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 0, 0, 240, 72, self::INK, 0 )
			. self::r( 118, 18, 114, 9, self::WHITE ) . self::lines( 232, 32, array( 104, 84 ), '#8a959b' )
			. self::c( 224, 56, 6, self::MUTED ) . self::r( 170, 53, 46, 5, '#8a959b' )
			. self::r( 10, 22, 88, 118, self::WHITE, 8, self::LINE ) . self::r( 16, 28, 76, 42, self::MEDIA, 5 ) . self::play( 54, 49 )
			. self::r( 54, 78, 38, 7, self::INK ) . self::r( 16, 92, 76, 11, self::ACCENT, 4 ) . self::lines( 92, 110, array( 76, 60, 70 ), self::MUTED, 8, 3 )
			. self::r( 106, 80, 126, 26, self::WHITE, 6, self::LINE )
			. self::r( 106, 112, 126, 9, self::WHITE, 4, self::LINE ) . self::r( 106, 124, 126, 9, self::WHITE, 4, self::LINE ) . self::r( 106, 136, 126, 9, self::WHITE, 4, self::LINE );
	}

	private static function draw_course_landing(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 60, 10, 120, 9, self::INK ) . self::r( 78, 24, 84, 4, self::MUTED ) . self::r( 90, 31, 60, 4, self::MUTED )
			. self::r( 124, 39, 36, 9, self::INK ) . self::r( 80, 38, 40, 11, self::ACCENT, 4 )
			. self::r( 36, 56, 168, 52, self::MEDIA, 8 ) . self::play( 120, 82 )
			. self::r( 36, 114, 39, 18, self::WHITE, 5, self::LINE ) . self::r( 79, 114, 39, 18, self::WHITE, 5, self::LINE ) . self::r( 122, 114, 39, 18, self::WHITE, 5, self::LINE ) . self::r( 165, 114, 39, 18, self::WHITE, 5, self::LINE )
			. self::r( 36, 138, 168, 10, '#fff5de', 4 );
	}

	/* ---------------------------------------------------------------------
	 * Products
	 * ------------------------------------------------------------------- */

	private static function draw_product_shop(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 8, 10, 224, 88, self::WHITE, 8, self::LINE )
			. self::r( 150, 16, 76, 58, '#eef1f3', 6 ) . self::r( 150, 78, 17, 14, '#eef1f3', 3 ) . self::r( 170, 78, 17, 14, '#eef1f3', 3 ) . self::r( 190, 78, 17, 14, '#eef1f3', 3 ) . self::r( 210, 78, 16, 14, '#eef1f3', 3 )
			. self::r( 80, 18, 62, 7, self::INK ) . self::r( 110, 29, 32, 4, self::ACCENT )
			. self::r( 16, 38, 126, 30, self::BG, 5 ) . self::r( 106, 43, 30, 7, self::INK ) . self::r( 22, 55, 114, 9, self::ACCENT, 3 )
			. self::r( 16, 74, 40, 18, self::WHITE, 4, self::LINE ) . self::r( 59, 74, 40, 18, self::WHITE, 4, self::LINE ) . self::r( 102, 74, 40, 18, self::WHITE, 4, self::LINE )
			. self::r( 82, 104, 150, 40, self::WHITE, 7, self::LINE ) . self::r( 196, 112, 28, 3, self::ACCENT ) . self::lines( 224, 122, array( 130, 110 ) )
			. self::r( 8, 104, 68, 40, self::INK, 7 ) . self::r( 16, 128, 52, 9, self::ACCENT, 3 );
	}

	private static function draw_product_showcase(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 100, 10, 116, 90, self::MEDIA, 7 ) . self::r( 220, 10, 12, 16, self::MEDIA, 3 ) . self::r( 220, 30, 12, 16, self::MEDIA, 3 ) . self::r( 220, 50, 12, 16, self::MEDIA, 3 )
			. self::r( 20, 14, 72, 7, self::INK ) . self::r( 60, 25, 32, 4, self::ACCENT ) . self::r( 56, 34, 36, 7, self::INK )
			. self::lines( 92, 46, array( 72, 60 ) ) . self::r( 10, 62, 82, 11, self::ACCENT, 4 ) . self::r( 10, 78, 82, 22, self::WHITE, 4, self::LINE )
			. self::r( 40, 110, 160, 10, self::WHITE, 5, self::LINE ) . self::r( 168, 117, 26, 2, self::ACCENT )
			. self::lines( 200, 128, array( 160, 140 ) );
	}

	private static function draw_product_editorial(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 0, 0, 240, 84, '#fff5de', 0 )
			. self::r( 136, 16, 96, 10, self::INK ) . self::lines( 232, 32, array( 90, 70 ) ) . self::r( 196, 48, 36, 7, self::INK ) . self::r( 150, 60, 82, 11, self::ACCENT, 4 )
			. self::r( 12, 10, 112, 64, self::WHITE, 7 )
			. self::r( 12, 92, 68, 22, self::WHITE, 5, self::LINE ) . self::r( 86, 92, 68, 22, self::WHITE, 5, self::LINE ) . self::r( 160, 92, 68, 22, self::WHITE, 5, self::LINE )
			. self::r( 40, 122, 160, 9, self::WHITE, 4, self::LINE ) . self::r( 40, 135, 160, 9, self::WHITE, 4, self::LINE );
	}

	/* ---------------------------------------------------------------------
	 * Headers
	 * ------------------------------------------------------------------- */

	private static function draw_header_classic(): string {
		return self::page_body( 30, 120 )
			. self::r( 0, 0, 240, 30, self::BG, 0 ) . self::r( 0, 29, 240, 1, self::LINE, 0 )
			. self::r( 198, 11, 34, 8, self::INK )
			. self::lines( 188, 13, array( 18 ), self::INK, 0, 4 ) . self::r( 170, 19, 18, 1.5, self::ACCENT ) . self::lines( 162, 13, array( 16 ), self::MUTED, 0, 4 ) . self::lines( 140, 13, array( 16 ), self::MUTED, 0, 4 )
			. self::r( 66, 9, 12, 12, self::WHITE, 3, self::LINE ) . self::r( 36, 9, 26, 12, self::WHITE, 3, self::LINE ) . self::r( 8, 9, 24, 12, self::INK, 3 );
	}

	private static function draw_header_centered(): string {
		return self::page_body( 40, 110 )
			. self::r( 0, 0, 240, 40, self::WHITE, 0 ) . self::r( 0, 39, 240, 1, self::LINE, 0 )
			. self::r( 100, 7, 40, 10, self::INK ) . self::r( 168, 7, 64, 11, self::BG, 4, self::LINE )
			. self::r( 22, 7, 28, 11, self::WHITE, 3, self::LINE ) . self::r( 8, 7, 11, 11, self::INK, 3 )
			. self::r( 128, 26, 22, 8, '#fff5de', 4 ) . self::lines( 122, 28, array( 18 ), self::MUTED, 0, 4 ) . self::lines( 100, 28, array( 18 ), self::MUTED, 0, 4 ) . self::lines( 172, 28, array( 18 ), self::MUTED, 0, 4 );
	}

	private static function draw_header_topbar(): string {
		return self::page_body( 38, 112 )
			. self::r( 0, 0, 240, 11, self::INK, 0 ) . self::r( 190, 4, 42, 3, '#8a959b' ) . self::c( 12, 5.5, 2.5, '#8a959b' ) . self::c( 20, 5.5, 2.5, '#8a959b' ) . self::c( 28, 5.5, 2.5, '#8a959b' )
			. self::r( 0, 11, 240, 27, self::WHITE, 0 ) . self::r( 0, 37, 240, 1, self::LINE, 0 )
			. self::r( 198, 20, 34, 8, self::INK ) . self::r( 158, 20, 26, 9, '#fff5de', 4 ) . self::lines( 152, 22, array( 18 ), self::MUTED, 0, 4 ) . self::lines( 130, 22, array( 18 ), self::MUTED, 0, 4 )
			. self::r( 40, 18, 40, 12, self::ACCENT, 4 ) . self::r( 24, 18, 12, 12, self::WHITE, 3, self::LINE ) . self::r( 8, 18, 12, 12, self::WHITE, 3, self::LINE );
	}

	private static function draw_header_market(): string {
		return self::page_body( 44, 106 )
			. self::r( 0, 0, 240, 44, self::WHITE, 0 ) . self::r( 0, 43, 240, 1, self::LINE, 0 ) . self::r( 0, 28, 240, 1, self::LINE, 0 )
			. self::r( 202, 9, 30, 9, self::INK ) . self::r( 70, 7, 124, 13, self::BG, 5, self::LINE )
			. self::r( 38, 7, 26, 13, self::WHITE, 4, self::LINE ) . self::r( 8, 7, 26, 13, self::ACCENT, 4 )
			. self::r( 204, 32, 28, 8, '#fff5de', 4 ) . self::lines( 198, 34, array( 22 ), self::MUTED, 0, 4 ) . self::lines( 172, 34, array( 22 ), self::MUTED, 0, 4 ) . self::lines( 146, 34, array( 22 ), self::MUTED, 0, 4 );
	}

	private static function draw_header_floating(): string {
		return self::page_body( 0, 150 )
			. '<rect x="10" y="7" width="220" height="24" rx="12" fill="#0f1315" opacity=".08" transform="translate(0 3)"/>'
			. self::r( 10, 6, 220, 24, self::WHITE, 12, self::LINE )
			. self::r( 196, 14, 26, 8, self::INK ) . self::r( 122, 14, 22, 8, '#fff5de', 4 ) . self::lines( 116, 16, array( 16 ), self::MUTED, 0, 4 ) . self::lines( 170, 16, array( 16 ), self::MUTED, 0, 4 )
			. self::c( 54, 18, 5.5, self::BG ) . self::r( 16, 12, 30, 12, self::ACCENT, 6 );
	}

	private static function draw_header_dark(): string {
		return self::page_body( 32, 118 )
			. self::r( 0, 0, 240, 32, self::INK, 0 )
			. self::r( 198, 12, 34, 8, self::WHITE )
			. self::lines( 150, 14, array( 18 ), self::WHITE, 0, 4 ) . self::r( 132, 20, 18, 1.5, self::ACCENT ) . self::lines( 126, 14, array( 16 ), '#8a959b', 0, 4 ) . self::lines( 104, 14, array( 16 ), '#8a959b', 0, 4 )
			. self::c( 64, 16, 4, '#8a959b' ) . self::c( 52, 16, 4, '#8a959b' ) . self::r( 8, 10, 34, 12, self::ACCENT, 4 );
	}

	private static function draw_header_minimal(): string {
		return self::page_body( 34, 116 )
			. self::r( 0, 0, 240, 34, self::BG, 0 )
			. self::r( 204, 13, 28, 8, self::INK )
			. self::lines( 158, 15, array( 16 ), self::INK, 0, 4 ) . self::lines( 136, 15, array( 16 ), self::MUTED, 0, 4 ) . self::lines( 114, 15, array( 16 ), self::MUTED, 0, 4 )
			. self::c( 38, 17, 4, self::INK ) . self::c( 26, 17, 4, self::INK ) . self::c( 14, 17, 4, self::INK );
	}

	/* ---------------------------------------------------------------------
	 * Footers
	 * ------------------------------------------------------------------- */

	private static function draw_footer_columns(): string {
		return self::page_body( 0, 78 )
			. self::r( 0, 78, 240, 72, self::WHITE, 0 ) . self::r( 0, 78, 240, 2.5, self::ACCENT, 0 )
			. self::r( 196, 88, 36, 8, self::INK ) . self::lines( 232, 100, array( 62, 50 ) ) . self::c( 226, 118, 4, self::BG ) . self::c( 216, 118, 4, self::BG ) . self::c( 206, 118, 4, self::BG )
			. self::r( 138, 88, 30, 5, self::INK ) . self::lines( 168, 98, array( 26, 22, 28 ) )
			. self::r( 90, 88, 30, 5, self::INK ) . self::lines( 120, 98, array( 26, 22, 28 ) )
			. self::r( 36, 88, 38, 5, self::INK ) . self::lines( 74, 98, array( 36, 34, 30 ) )
			. self::r( 0, 132, 240, 1, self::LINE, 0 ) . self::r( 170, 139, 62, 4, self::MUTED ) . self::r( 10, 136, 12, 10, self::BG, 2 ) . self::r( 26, 136, 12, 10, self::BG, 2 );
	}

	private static function draw_footer_dark(): string {
		return self::page_body( 0, 76 )
			. self::r( 0, 86, 240, 64, self::INK, 0 )
			. self::r( 18, 70, 204, 26, self::ACCENT, 8 ) . self::r( 130, 77, 84, 6, self::INK ) . self::r( 150, 86, 64, 3.5, '#8a5e00' ) . self::r( 26, 77, 44, 12, self::INK, 4 )
			. self::r( 196, 106, 36, 7, '#eaedef' ) . self::lines( 232, 117, array( 60, 48 ), '#6b7780' )
			. self::r( 118, 106, 30, 5, '#eaedef' ) . self::lines( 148, 116, array( 26, 22 ), '#6b7780' )
			. self::r( 40, 106, 34, 5, '#eaedef' ) . self::lines( 74, 116, array( 32, 28 ), '#6b7780' )
			. self::r( 0, 136, 240, 1, '#434d53', 0 ) . self::r( 170, 141, 62, 3.5, '#6b7780' );
	}

	private static function draw_footer_minimal(): string {
		return self::page_body( 0, 118 )
			. self::r( 0, 118, 240, 32, self::WHITE, 0 ) . self::r( 0, 118, 240, 1, self::LINE, 0 )
			. self::r( 200, 130, 30, 8, self::INK ) . self::lines( 150, 132, array( 16 ), self::MUTED, 0, 4 ) . self::lines( 130, 132, array( 16 ), self::MUTED, 0, 4 ) . self::lines( 110, 132, array( 16 ), self::MUTED, 0, 4 )
			. self::r( 10, 132, 54, 4, self::MUTED );
	}

	private static function draw_footer_centered(): string {
		return self::page_body( 0, 76 )
			. self::r( 0, 76, 240, 74, self::BG, 0 ) . self::r( 0, 76, 240, 1, self::LINE, 0 )
			. self::r( 102, 86, 36, 8, self::INK ) . self::r( 70, 100, 100, 3.5, self::MUTED ) . self::r( 84, 106, 72, 3.5, self::MUTED )
			. self::c( 104, 118, 4.5, self::WHITE ) . self::c( 116, 118, 4.5, self::WHITE ) . self::c( 128, 118, 4.5, self::WHITE ) . self::c( 140, 118, 4.5, self::WHITE )
			. self::lines( 170, 128, array( 20 ), self::MUTED, 0, 4 ) . self::lines( 144, 128, array( 20 ), self::MUTED, 0, 4 ) . self::lines( 118, 128, array( 20 ), self::MUTED, 0, 4 ) . self::lines( 92, 128, array( 20 ), self::MUTED, 0, 4 )
			. self::r( 90, 140, 60, 3.5, self::MUTED );
	}

	private static function draw_footer_trust(): string {
		return self::page_body( 0, 76 )
			. self::r( 0, 76, 240, 74, self::WHITE, 0 ) . self::r( 0, 76, 240, 1, self::LINE, 0 )
			. self::r( 150, 84, 82, 44, self::BG, 6 ) . self::r( 196, 90, 30, 6, self::INK ) . self::lines( 226, 100, array( 50, 44, 40 ) ) . self::r( 186, 119, 40, 6, self::ACCENT, 3 )
			. self::r( 112, 86, 28, 5, self::INK ) . self::lines( 140, 96, array( 26, 22, 24 ) )
			. self::r( 72, 86, 28, 5, self::INK ) . self::lines( 100, 96, array( 26, 22, 24 ) )
			. self::r( 10, 86, 24, 24, self::BG, 4, self::LINE ) . self::r( 38, 86, 24, 24, self::BG, 4, self::LINE )
			. self::r( 0, 134, 240, 1, self::LINE, 0 ) . self::r( 170, 140, 62, 3.5, self::MUTED );
	}

	private static function draw_footer_split(): string {
		return self::page_body( 0, 70 )
			. self::r( 0, 70, 240, 80, self::WHITE, 0 ) . self::r( 0, 70, 240, 1, self::LINE, 0 )
			. self::r( 150, 78, 82, 56, self::INK, 7 ) . self::r( 196, 86, 30, 7, self::WHITE ) . self::lines( 226, 97, array( 60, 48 ), '#6b7780' )
			. self::c( 220, 118, 4, '#434d53' ) . self::c( 210, 118, 4, '#434d53' ) . self::c( 200, 118, 4, '#434d53' ) . self::r( 158, 112, 14, 14, '#434d53', 3 )
			. self::r( 112, 82, 26, 5, self::INK ) . self::lines( 138, 92, array( 24, 20, 22 ) )
			. self::r( 70, 82, 26, 5, self::INK ) . self::lines( 96, 92, array( 24, 20, 22 ) )
			. self::r( 20, 82, 34, 5, self::INK ) . self::lines( 54, 92, array( 32, 30, 28 ) )
			. self::r( 0, 138, 240, 1, self::LINE, 0 ) . self::r( 170, 142, 62, 3.5, self::MUTED ) . self::lines( 60, 142, array( 50 ), self::MUTED, 0, 3.5 );
	}

	private static function draw_footer_support(): string {
		$card = static function ( $x ) {
			return self::r( $x, 80, 70, 22, self::BG, 5 ) . self::r( $x + 54, 85, 12, 12, '#fff5de', 3 ) . self::r( $x + 16, 86, 34, 4, self::INK ) . self::r( $x + 24, 94, 26, 3, self::MUTED );
		};

		return self::page_body( 0, 70 )
			. self::r( 0, 70, 240, 80, self::WHITE, 0 ) . self::r( 0, 70, 240, 1, self::LINE, 0 )
			. $card( 162 ) . $card( 85 ) . $card( 8 )
			. self::r( 202, 112, 30, 8, self::INK ) . self::lines( 150, 114, array( 16 ), self::MUTED, 0, 4 ) . self::lines( 130, 114, array( 16 ), self::MUTED, 0, 4 ) . self::lines( 110, 114, array( 16 ), self::MUTED, 0, 4 )
			. self::c( 34, 116, 4, self::BG ) . self::c( 24, 116, 4, self::BG ) . self::c( 14, 116, 4, self::BG )
			. self::r( 0, 132, 240, 1, self::LINE, 0 ) . self::r( 170, 139, 62, 3.5, self::MUTED ) . self::r( 10, 136, 12, 10, self::BG, 2 ) . self::r( 26, 136, 12, 10, self::BG, 2 );
	}

	private static function draw_footer_soft(): string {
		return self::page_body( 0, 70 )
			. self::r( 0, 70, 240, 80, '#fff5de', 0 )
			. self::r( 102, 78, 36, 8, self::INK ) . self::r( 72, 92, 96, 4, '#8a5e00' )
			. self::lines( 164, 102, array( 18 ), self::MUTED, 0, 4 ) . self::lines( 140, 102, array( 18 ), self::MUTED, 0, 4 ) . self::lines( 116, 102, array( 18 ), self::MUTED, 0, 4 ) . self::lines( 92, 102, array( 18 ), self::MUTED, 0, 4 )
			. self::r( 104, 112, 14, 14, self::WHITE, 3 ) . self::r( 122, 112, 14, 14, self::WHITE, 3 )
			. self::r( 0, 132, 240, 1, '#f1dca8', 0 ) . self::r( 170, 139, 62, 3.5, '#c9a55a' ) . self::c( 34, 140, 4, self::WHITE ) . self::c( 24, 140, 4, self::WHITE ) . self::c( 14, 140, 4, self::WHITE );
	}

	/* ---------------------------------------------------------------------
	 * Home pages
	 * ------------------------------------------------------------------- */

	/**
	 * Row of product cards: picture, title and price.
	 *
	 * @param float $y     Top.
	 * @param float $h     Height.
	 * @param int   $count Number of cards.
	 * @param float $x     Left edge.
	 * @param float $width Row width.
	 */
	private static function cards( $y, $h, int $count = 4, $x = 8, $width = 224 ): string {
		$gap = 6;
		$w   = ( $width - $gap * ( $count - 1 ) ) / $count;
		$out = '';
		for ( $i = 0; $i < $count; $i++ ) {
			$left = $x + $i * ( $w + $gap );
			$out .= self::r( $left, $y, $w, $h, self::WHITE, 4, self::LINE )
				. self::r( $left + 3, $y + 3, $w - 6, $h * 0.5, self::MEDIA, 3 )
				. self::r( $left + $w - 3 - $w * 0.6, $y + $h * 0.62, $w * 0.6, 3, self::INK, 1.5 )
				. self::r( $left + $w - 3 - $w * 0.35, $y + $h * 0.8, $w * 0.35, 3, self::ACCENT, 1.5 );
		}

		return $out;
	}

	private static function draw_home_complete(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 80, 8, 152, 48, self::ACCENT, 7 ) . self::c( 94, 12, 16, 'rgba(255,255,255,.18)' )
			. self::r( 196, 16, 28, 5, 'rgba(255,255,255,.55)', 2.5 ) . self::r( 150, 25, 74, 7, self::INK ) . self::r( 170, 35, 54, 7, self::INK )
			. self::r( 188, 45, 36, 7, self::WHITE, 3.5 ) . self::r( 158, 45, 26, 7, 'rgba(15,19,21,.12)', 3.5 )
			. self::r( 8, 8, 66, 22, self::INK, 6 ) . self::r( 34, 14, 34, 4, self::WHITE ) . self::r( 50, 22, 18, 3, self::ACCENT )
			. self::r( 8, 34, 66, 22, self::WHITE, 6, self::LINE ) . self::r( 34, 40, 34, 4, self::INK ) . self::r( 50, 48, 18, 3, self::ACCENT )
			. self::r( 8, 62, 224, 14, self::WHITE, 5 ) . self::r( 214, 65, 8, 8, '#fff5de', 2 ) . self::r( 158, 65, 8, 8, '#fff5de', 2 ) . self::r( 102, 65, 8, 8, '#fff5de', 2 ) . self::r( 46, 65, 8, 8, '#fff5de', 2 )
			. self::r( 160, 82, 72, 12, self::WHITE, 4 ) . self::r( 84, 82, 72, 12, self::WHITE, 4 ) . self::r( 8, 82, 72, 12, self::WHITE, 4 )
			. self::r( 0, 99, 240, 51, self::WHITE, 0 ) . self::cards( 104, 30 )
			. self::r( 8, 138, 224, 12, self::INK, 5 ) . self::r( 16, 141, 24, 6, self::ACCENT, 3 ) . self::r( 44, 141, 8, 6, '#434d53', 2 ) . self::r( 54, 141, 8, 6, '#434d53', 2 );
	}

	private static function draw_home_studiare(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. '<path d="M0 0h240v58c-40 10-80 14-120 14S40 68 0 58z" fill="' . self::ACCENT . '"/>'
			. self::r( 196, 12, 34, 5, 'rgba(255,255,255,.55)', 2.5 ) . self::r( 142, 21, 88, 8, self::INK ) . self::r( 162, 32, 68, 8, self::INK )
			. self::lines( 230, 44, array( 76 ), '#8a5e00' ) . self::r( 200, 52, 30, 8, self::WHITE, 4 ) . self::r( 170, 52, 26, 8, 'rgba(15,19,21,.12)', 4 )
			. self::r( 12, 10, 104, 56, self::INK, 6 ) . self::r( 16, 14, 96, 48, '#2b3338', 4 ) . self::play( 64, 38 )
			. self::r( 84, 58, 38, 14, self::WHITE, 4 )
			. self::r( 160, 82, 72, 12, self::WHITE, 4 ) . self::r( 84, 82, 72, 12, self::WHITE, 4 ) . self::r( 8, 82, 72, 12, self::WHITE, 4 )
			. self::r( 176, 100, 56, 20, '#cfd6db', 4 ) . self::play( 204, 110 ) . self::r( 118, 100, 54, 20, '#cfd6db', 4 ) . self::play( 145, 110 ) . self::r( 62, 100, 52, 20, '#cfd6db', 4 ) . self::play( 88, 110 ) . self::r( 8, 100, 50, 20, '#cfd6db', 4 ) . self::play( 33, 110 )
			. self::r( 0, 126, 240, 24, self::WHITE, 0 ) . self::cards( 129, 21 );
	}

	private static function draw_home_shop(): string {
		return self::r( 0, 0, 240, 150, self::WHITE, 0 )
			. self::r( 0, 0, 240, 62, '#fff9ec', 0 ) . self::r( 0, 61, 240, 1, self::LINE, 0 )
			. self::r( 186, 10, 44, 7, self::WHITE, 3.5, self::LINE ) . self::r( 136, 21, 94, 8, self::INK ) . self::r( 160, 32, 30, 8, self::ACCENT ) . self::r( 194, 32, 36, 8, self::INK )
			. self::lines( 230, 44, array( 80 ) ) . self::r( 200, 51, 30, 8, self::ACCENT, 3 ) . self::r( 166, 51, 30, 8, self::WHITE, 3, self::LINE )
			. self::r( 10, 8, 110, 46, '#f1ece0', 6, self::LINE )
			. self::r( 196, 68, 34, 12, self::WHITE, 4, self::LINE ) . self::r( 158, 68, 34, 12, self::WHITE, 4, self::LINE ) . self::r( 120, 68, 34, 12, self::WHITE, 4, self::LINE ) . self::r( 82, 68, 34, 12, self::WHITE, 4, self::LINE ) . self::r( 44, 68, 34, 12, self::WHITE, 4, self::LINE ) . self::r( 8, 68, 32, 12, self::WHITE, 4, self::LINE )
			. self::r( 170, 86, 60, 6, self::INK ) . self::cards( 96, 28 )
			. self::r( 8, 130, 224, 20, '#3b2600', 6 ) . self::r( 150, 135, 74, 5, self::WHITE ) . self::r( 40, 134, 40, 6, '#ffd270' ) . self::r( 16, 142, 64, 5, self::ACCENT, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Blog: post lists and single posts
	 * ------------------------------------------------------------------- */

	/**
	 * Row of pill buttons, centred on $cx.
	 *
	 * @param float $cx    Centre.
	 * @param float $y     Top.
	 * @param int   $count Number of pills (the first is dark, "active").
	 */
	private static function chips( $cx, $y, int $count ): string {
		$w    = 24;
		$gap  = 4;
		$left = $cx - ( $count * $w + ( $count - 1 ) * $gap ) / 2;
		$out  = '';
		for ( $i = 0; $i < $count; $i++ ) {
			$x    = $left + ( $count - 1 - $i ) * ( $w + $gap );
			$out .= 0 === $i ? self::r( $x, $y, $w, 8, self::INK, 4 ) : self::r( $x, $y, $w, 8, self::WHITE, 4, self::LINE );
		}

		return $out;
	}

	/**
	 * Post row: picture at the start (right), title and text lines beside it.
	 *
	 * @param float $x Left edge.
	 * @param float $y Top.
	 * @param float $w Width.
	 * @param float $h Height.
	 */
	private static function post_row( $x, $y, $w, $h ): string {
		$pic = $h - 8;

		return self::r( $x, $y, $w, $h, self::WHITE, 5, self::LINE )
			. self::r( $x + $w - 4 - $pic * 1.3, $y + 4, $pic * 1.3, $pic, self::MEDIA, 3 )
			. self::lines( $x + $w - 10 - $pic * 1.3, $y + 6, array( $w * 0.42, $w * 0.3 ), self::INK, 7, 3.5 )
			. self::lines( $x + $w - 10 - $pic * 1.3, $y + $h - 9, array( $w * 0.18 ), self::MUTED, 0, 3 );
	}

	private static function draw_archive_magazine(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 0, 0, 240, 46, '#fff5de', 0 )
			. self::r( 104, 8, 32, 4, '#e9c46a', 2 ) . self::r( 72, 15, 96, 8, self::INK ) . self::r( 86, 26, 68, 4, self::MUTED )
			. self::chips( 120, 33, 5 )
			. self::r( 8, 53, 224, 42, self::WHITE, 6, self::LINE ) . self::r( 124, 57, 104, 34, self::MEDIA, 4 )
			. self::r( 88, 60, 28, 3.5, self::ACCENT ) . self::lines( 116, 67, array( 96, 72 ), self::INK, 7, 4 ) . self::lines( 116, 83, array( 90 ) )
			. self::cards( 101, 43, 3 );
	}

	private static function draw_archive_sidebar(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::lines( 232, 7, array( 50 ), self::MUTED, 0, 3 ) . self::r( 150, 14, 82, 8, self::INK ) . self::lines( 232, 26, array( 110 ) )
			. self::post_row( 80, 36, 152, 34 ) . self::post_row( 80, 74, 152, 34 ) . self::post_row( 80, 112, 152, 34 )
			. self::r( 8, 36, 66, 22, self::WHITE, 5, self::LINE ) . self::r( 14, 44, 54, 8, self::BG, 3, self::LINE )
			. self::r( 8, 62, 66, 40, self::WHITE, 5, self::LINE ) . self::lines( 68, 68, array( 40, 48, 36, 44 ), self::MUTED, 8, 3 )
			. self::r( 8, 106, 66, 40, self::WHITE, 5, self::LINE )
			. self::r( 56, 112, 12, 12, self::MEDIA, 3 ) . self::lines( 52, 114, array( 34 ), self::INK, 0, 3 ) . self::c( 67, 112, 3, self::ACCENT )
			. self::r( 56, 128, 12, 12, self::MEDIA, 3 ) . self::lines( 52, 130, array( 30 ), self::INK, 0, 3 ) . self::c( 67, 128, 3, self::ACCENT );
	}

	private static function draw_archive_minimal(): string {
		$cards = '';
		foreach ( array( 162, 86, 10 ) as $x ) {
			$cards .= self::r( $x, 62, 68, 46, self::MEDIA, 6 )
				. self::lines( $x + 68, 114, array( 22 ), self::ACCENT, 0, 3 )
				. self::lines( $x + 68, 121, array( 64, 50 ), self::INK, 7, 4 )
				. self::lines( $x + 68, 138, array( 40 ), self::MUTED, 0, 3 );
		}

		return self::r( 0, 0, 240, 150, self::WHITE, 0 )
			. self::r( 102, 10, 36, 6, '#fff5de', 3 ) . self::r( 62, 21, 116, 10, self::INK ) . self::r( 80, 35, 80, 4, self::MUTED )
			. self::chips( 120, 46, 5 )
			. $cards;
	}

	private static function draw_post_classic(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 0, 0, 240, 2.5, self::ACCENT, 0 )
			. self::r( 76, 8, 156, 140, self::WHITE, 6, self::LINE )
			. self::r( 204, 14, 22, 5, '#fff5de', 2.5 ) . self::r( 110, 23, 116, 8, self::INK )
			. self::c( 222, 39, 4, self::MUTED ) . self::lines( 215, 37, array( 60 ), self::MUTED, 0, 3.5 )
			. self::r( 84, 47, 142, 46, self::MEDIA, 5 )
			. self::lines( 226, 99, array( 142, 130, 138, 110 ), self::MUTED, 7, 3.5 )
			. self::r( 170, 128, 56, 4, self::INK ) . self::lines( 226, 136, array( 120 ) )
			. self::r( 8, 8, 62, 52, self::WHITE, 6, self::LINE ) . self::lines( 62, 15, array( 30 ), self::INK, 0, 4 )
			. self::r( 14, 24, 48, 7, '#fff5de', 3 ) . self::lines( 62, 35, array( 44, 36, 40 ), self::MUTED, 7, 3 )
			. self::r( 8, 64, 62, 44, self::WHITE, 6, self::LINE )
			. self::r( 52, 70, 12, 12, self::MEDIA, 3 ) . self::lines( 48, 72, array( 30 ), self::INK, 0, 3 )
			. self::r( 52, 88, 12, 12, self::MEDIA, 3 ) . self::lines( 48, 90, array( 26 ), self::INK, 0, 3 )
			. self::r( 8, 112, 62, 36, self::INK, 6 ) . self::r( 14, 132, 50, 8, self::ACCENT, 3 );
	}

	private static function draw_post_focus(): string {
		return self::r( 0, 0, 240, 150, self::WHITE, 0 )
			. self::r( 0, 0, 240, 2.5, self::ACCENT, 0 )
			. self::r( 106, 10, 28, 5, '#fff5de', 2.5 ) . self::r( 58, 19, 124, 9, self::INK ) . self::r( 78, 31, 84, 9, self::INK )
			. self::r( 70, 44, 100, 3.5, self::MUTED )
			. self::c( 104, 55, 5, self::MUTED ) . self::r( 112, 52, 30, 3.5, self::INK ) . self::r( 112, 57, 22, 3, self::MUTED )
			. self::r( 16, 66, 208, 40, self::MEDIA, 6 )
			. self::r( 64, 112, 112, 10, self::WHITE, 4, self::LINE )
			. self::lines( 176, 128, array( 112, 104, 112 ), self::MUTED, 7, 3.5 );
	}

	private static function draw_post_cover(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 0, 0, 240, 2.5, self::ACCENT, 0 )
			. self::r( 0, 2.5, 240, 70, self::INK, 0 )
			. self::lines( 230, 12, array( 40 ), '#8a959b', 0, 3 ) . self::r( 132, 20, 98, 9, self::WHITE ) . self::r( 150, 32, 80, 9, self::WHITE )
			. self::lines( 230, 46, array( 92 ), '#8a959b', 0, 3.5 ) . self::c( 225, 59, 5, '#8a959b' ) . self::r( 186, 56, 32, 4, '#d9dfe3' )
			. self::r( 12, 12, 106, 52, '#4a555c', 6 )
			. self::c( 227, 86, 4.5, self::WHITE ) . self::c( 227, 98, 4.5, self::WHITE ) . self::c( 227, 110, 4.5, self::WHITE )
			. self::r( 68, 79, 148, 67, self::WHITE, 6, self::LINE ) . self::lines( 208, 88, array( 132, 120, 128, 96 ), self::MUTED, 8, 3.5 )
			. self::r( 150, 124, 58, 4.5, self::INK ) . self::lines( 208, 133, array( 124 ) )
			. self::r( 8, 79, 54, 52, self::WHITE, 6, self::LINE ) . self::lines( 56, 86, array( 30 ), self::INK, 0, 4 )
			. self::r( 13, 95, 44, 7, '#fff5de', 3 ) . self::lines( 56, 106, array( 40, 34, 38 ), self::MUTED, 7, 3 );
	}

	/* ---------------------------------------------------------------------
	 * About us and contact us pages
	 * ------------------------------------------------------------------- */

	/** Messenger colours for the button rows (Telegram, WhatsApp, Bale, Eitaa). */
	private const BRANDS = array( '#229ed9', '#1da851', '#00a383', '#f2651f' );

	/**
	 * Row of key numbers: icon, number and label per item.
	 *
	 * @param float  $y     Top.
	 * @param float  $h     Height.
	 * @param bool   $tiles Each number in a white tile (else large numbers with dividers).
	 * @param string $ink   Number colour.
	 */
	private static function numbers( $y, $h, bool $tiles, string $ink = self::INK ): string {
		$w   = 53;
		$out = '';
		for ( $i = 0; $i < 4; $i++ ) {
			$x   = 8 + $i * 57;
			$mid = $x + $w / 2;
			if ( $tiles ) {
				$out .= self::r( $x, $y, $w, $h, self::WHITE, 5, self::LINE ) . self::r( $mid - 5, $y + 4, 10, 8, '#fff5de', 2.5 );
			} elseif ( $i ) {
				$out .= self::r( $x - 2, $y + 2, 1, $h - 4, self::LINE, 0 );
			}
			$out .= self::r( $mid - 13, $y + ( $tiles ? 15 : 4 ), 26, 7, $ink, 2 ) . self::r( $mid - 16, $y + ( $tiles ? 25 : 15 ), 32, 3, self::MUTED, 1.5 );
		}

		return $out;
	}

	/**
	 * Timeline: a line with dots and small cards.
	 *
	 * @param float $y         Top.
	 * @param bool  $alternate Cards on both sides of a centred line (else a row of steps).
	 */
	private static function timeline( $y, bool $alternate ): string {
		if ( ! $alternate ) {
			$out = self::r( 8, $y + 3, 224, 1.5, self::LINE, 0 );
			for ( $i = 0; $i < 4; $i++ ) {
				$x    = 176 - $i * 56;
				$out .= self::c( $x + 46, $y + 4, 3, self::ACCENT ) . self::r( $x, $y + 11, 52, 26, self::WHITE, 4, self::LINE )
					. self::r( $x + 32, $y + 15, 16, 4, '#fff5de', 2 ) . self::lines( $x + 48, $y + 23, array( 34, 26 ) );
			}

			return $out;
		}

		$out = self::r( 119.25, $y, 1.5, 46, self::LINE, 0 );
		foreach ( array( 0, 1, 2 ) as $i ) {
			$cy   = $y + 6 + $i * 15;
			$left = 1 === $i;
			$out .= self::c( 120, $cy, 3, self::ACCENT )
				. self::r( $left ? 36 : 128, $cy - 5, 76, 11, self::WHITE, 3, self::LINE )
				. self::lines( $left ? 108 : 200, $cy - 1.5, array( $left ? 40 : 60 ) );
		}

		return $out;
	}

	/**
	 * Row of messenger buttons.
	 *
	 * @param float $right Right edge (RTL start).
	 * @param float $y     Top.
	 * @param float $w     Button width.
	 */
	private static function messengers( $right, $y, $w = 24 ): string {
		$out = '';
		foreach ( self::BRANDS as $i => $color ) {
			$out .= self::r( $right - ( $i + 1 ) * ( $w + 4 ) + 4, $y, $w, 8, $color, 4 );
		}

		return $out;
	}

	/**
	 * Contact form card: fields in a grid, the message box and the button.
	 *
	 * @param float $x Left.
	 * @param float $y Top.
	 * @param float $w Width.
	 * @param float $h Height.
	 */
	private static function form_card( $x, $y, $w, $h ): string {
		$half = ( $w - 18 ) / 2;
		$out  = self::r( $x, $y, $w, $h, self::WHITE, 6, self::LINE )
			. self::r( $x + $w - 6 - $half, $y + 5, $half, 8, self::BG, 3, self::LINE ) . self::r( $x + 6, $y + 5, $half, 8, self::BG, 3, self::LINE );
		$top  = $y + 17;

		// Tall cards also show the full-width topic field.
		if ( $h >= 60 ) {
			$out .= self::r( $x + 6, $top, $w - 12, 8, self::BG, 3, self::LINE );
			$top += 12;
		}

		return $out
			. self::r( $x + 6, $top, $w - 12, max( 4, $y + $h - 15 - $top ), self::BG, 3, self::LINE )
			. self::r( $x + $w - 46, $y + $h - 11, 40, 7, self::ACCENT, 3.5 );
	}

	/**
	 * Map with its pin (and, optionally, the address card on it).
	 *
	 * @param float $y    Top.
	 * @param float $h    Height.
	 * @param bool  $card Address card on the map's start corner.
	 */
	private static function map( $y, $h, bool $card = true ): string {
		$out = self::r( 8, $y, 224, $h, '#e6ecef', 5, self::LINE )
			. sprintf( '<path d="M8 %1$sH232M8 %2$sH232M70 %3$sV%4$sM150 %3$sV%4$s" stroke="#fff" stroke-width="3"/>', $y + $h * 0.35, $y + $h * 0.72, $y, $y + $h )
			. self::c( 110, $y + $h / 2 - 2, 5, self::ACCENT ) . self::c( 110, $y + $h / 2 - 2, 2, self::WHITE );

		if ( $card ) {
			$out .= self::r( 170, $y + $h - 24, 56, 20, self::WHITE, 4, self::LINE ) . self::lines( 220, $y + $h - 19, array( 30, 44 ) ) . self::r( 186, $y + $h - 9, 34, 3, self::ACCENT, 1.5 );
		}

		return $out;
	}

	private static function draw_about_story(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 0, 0, 240, 64, '#fff9ec', 0 )
			. self::r( 196, 9, 34, 6, self::WHITE, 3, self::LINE ) . self::r( 140, 20, 90, 8, self::INK ) . self::r( 166, 31, 36, 8, self::ACCENT ) . self::r( 206, 31, 24, 8, self::INK )
			. self::lines( 230, 44, array( 84 ) ) . self::r( 200, 52, 30, 8, self::ACCENT, 3 ) . self::r( 166, 52, 30, 8, self::WHITE, 3, self::LINE )
			. self::r( 12, 8, 104, 50, self::MEDIA, 6 ) . self::r( 88, 46, 40, 14, self::WHITE, 4 ) . self::r( 110, 50, 12, 3, self::INK ) . self::r( 94, 50, 12, 3, self::INK )
			. self::numbers( 70, 32, true )
			. self::timeline( 108, true );
	}

	private static function draw_about_minimal(): string {
		return self::r( 0, 0, 240, 150, self::WHITE, 0 )
			. self::r( 104, 8, 32, 4, self::MUTED ) . self::r( 50, 16, 140, 8, self::INK ) . self::r( 72, 27, 96, 8, self::INK ) . self::r( 70, 39, 100, 3.5, self::MUTED )
			. self::r( 122, 46, 30, 8, self::ACCENT, 4 ) . self::r( 88, 46, 30, 8, self::WHITE, 4, self::LINE )
			. self::r( 20, 60, 200, 38, self::MEDIA, 6 )
			. self::numbers( 104, 20, false )
			. self::r( 0, 128, 240, 22, self::BG, 0 ) . self::r( 168, 133, 62, 7, self::INK ) . self::lines( 230, 143, array( 54 ) )
			. self::r( 10, 132, 146, 6, self::WHITE, 3, self::LINE ) . self::r( 10, 141, 146, 6, self::WHITE, 3, self::LINE );
	}

	private static function draw_about_academy(): string {
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. '<path d="M0 0h240v54c-40 10-80 14-120 14S40 64 0 54z" fill="' . self::ACCENT . '"/>'
			. self::r( 196, 10, 34, 5, 'rgba(255,255,255,.55)', 2.5 ) . self::r( 142, 19, 88, 8, self::INK ) . self::r( 168, 30, 62, 8, self::INK )
			. self::r( 200, 43, 30, 8, self::WHITE, 4 ) . self::r( 166, 43, 30, 8, 'rgba(15,19,21,.12)', 4 )
			. self::r( 12, 8, 104, 52, self::INK, 6 ) . self::r( 16, 12, 96, 44, '#2b3338', 4 ) . self::play( 64, 34 )
			. self::r( 8, 76, 224, 34, self::INK, 7 ) . self::numbers( 81, 24, false, self::WHITE )
			. self::r( 8, 116, 224, 34, self::WHITE, 0 ) . self::timeline( 118, false );
	}

	private static function draw_contact_cards(): string {
		$cards = '';
		for ( $i = 0; $i < 4; $i++ ) {
			$x      = 176 - $i * 56;
			$cards .= self::r( $x, 44, 52, 20, self::WHITE, 4, self::LINE ) . self::r( $x + 38, 48, 10, 10, '#fff5de', 3 ) . self::lines( $x + 34, 49, array( 16, 24 ), self::MUTED, 6, 3 );
		}

		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 0, 0, 240, 38, '#fff9ec', 0 ) . self::r( 100, 7, 40, 5, self::WHITE, 2.5, self::LINE ) . self::r( 64, 16, 112, 8, self::INK ) . self::r( 78, 28, 84, 3.5, self::MUTED )
			. $cards
			. self::form_card( 92, 70, 140, 48 )
			. self::r( 8, 70, 80, 48, self::WHITE, 6, self::LINE ) . self::r( 42, 76, 40, 5, self::INK ) . self::lines( 82, 85, array( 60 ) )
			. self::r( 58, 94, 24, 8, self::BRANDS[0], 4 ) . self::r( 30, 94, 24, 8, self::BRANDS[1], 4 ) . self::r( 58, 105, 24, 8, self::BRANDS[2], 4 ) . self::r( 30, 105, 24, 8, self::BRANDS[3], 4 )
			. self::map( 124, 26, false );
	}

	private static function draw_contact_split(): string {
		$rows = '';
		for ( $i = 0; $i < 4; $i++ ) {
			$rows .= self::r( 214, 42 + $i * 14, 10, 10, 'rgba(255,255,255,.14)', 3 ) . self::lines( 208, 43 + $i * 14, array( 30, 44 ), '#8a959b', 5, 3 );
		}

		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 190, 8, 40, 5, self::WHITE, 2.5, self::LINE ) . self::r( 150, 16, 80, 8, self::INK ) . self::lines( 230, 28, array( 110 ) )
			. self::r( 152, 36, 80, 84, self::INK, 7 ) . $rows . self::r( 158, 99, 68, 1, 'rgba(255,255,255,.14)', 0 ) . self::messengers( 226, 105, 13 )
			. self::form_card( 8, 36, 138, 84 )
			. self::map( 126, 24 );
	}

	private static function draw_contact_support(): string {
		$depts = '';
		for ( $i = 0; $i < 3; $i++ ) {
			$x      = 160 - $i * 76;
			$depts .= self::r( $x, 66, 72, 24, self::WHITE, 4, self::LINE ) . self::r( $x + 56, 70, 12, 12, $i < 2 ? '#fff5de' : self::BRANDS[0], 3 ) . self::lines( $x + 52, 71, array( 26, 38, 30 ), self::MUTED, 6, 3 );
		}

		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. '<path d="M0 0h240v48c-40 10-80 14-120 14S40 58 0 48z" fill="' . self::ACCENT . '"/>'
			. self::r( 102, 7, 36, 5, 'rgba(255,255,255,.55)', 2.5 ) . self::r( 60, 16, 120, 9, self::INK ) . self::r( 76, 29, 88, 3.5, '#8a5e00' )
			. self::messengers( 176, 38 )
			. $depts
			. self::r( 128, 96, 104, 9, self::WHITE, 3, self::LINE ) . self::r( 128, 108, 104, 9, self::WHITE, 3, self::LINE ) . self::r( 128, 120, 104, 9, self::WHITE, 3, self::LINE )
			. self::form_card( 8, 96, 114, 34 )
			. self::map( 136, 14, false );
	}

	/* ---------------------------------------------------------------------
	 * Live search results (keys `search-<style>`)
	 * ------------------------------------------------------------------- */

	/**
	 * Search field above the results, magnifier at the start (right, RTL).
	 *
	 * @param string $stroke Border colour.
	 */
	private static function search_field( string $stroke ): string {
		return self::r( 20, 10, 200, 22, self::WHITE, 6, $stroke )
			. '<circle cx="207" cy="20" r="3.5" fill="none" stroke="#9aa8b2" stroke-width="1.5"/><path d="M204.5 22.5l-2.5 2.5" stroke="#9aa8b2" stroke-width="1.5" stroke-linecap="round"/>'
			. self::lines( 198, 19, array( 46 ), self::MUTED, 0, 3.5 );
	}

	private static function draw_search_compact(): string {
		$rows = '';
		foreach ( array( 46, 66, 86, 106 ) as $y ) {
			$rows .= self::r( 196, $y, 14, 14, self::MEDIA, 3 )
				. self::lines( 190, $y + 2, array( 84 ), self::INK, 0, 3.5 )
				. self::lines( 190, $y + 9, array( 30 ), self::ACCENT, 0, 3 );
		}

		return self::r( 0, 0, 240, 150, self::BG, 0 ) . self::search_field( self::LINE )
			. self::r( 20, 38, 200, 104, self::WHITE, 8, self::LINE ) . $rows
			. self::r( 20, 126, 200, 1, self::LINE, 0 ) . self::r( 95, 132, 50, 3.5, self::ACCENT, 1.5 );
	}

	private static function draw_search_detailed(): string {
		$rows = '';
		foreach ( array( 56, 82, 108 ) as $i => $y ) {
			$active = 0 === $i;
			$rows  .= ( $active ? self::r( 26, $y - 3, 188, 26, self::BG, 5 ) : '' )
				. self::r( 188, $y, 20, 20, self::MEDIA, 5 )
				. self::lines( 181, $y + 3, array( 96 ), self::INK, 0, 4 )
				. self::r( 146, $y + 2, 26, 6, '#fff5de', 2 ) . self::r( 148, $y + 3, 22, 4, '#d68800', 1.5 )
				. self::r( 163, $y + 12, 18, 6, '#fff5de', 3 )
				. self::lines( 159, $y + 13.5, array( 20 ), self::MUTED, 0, 3 ) . self::lines( 134, $y + 13.5, array( 16 ), self::MUTED, 0, 3 )
				. sprintf( '<path d="M34 %1$sl-4 4 4 4" fill="none" stroke="%2$s" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>', $y + 6, $active ? self::ACCENT : self::LINE );
		}

		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 17, 7, 206, 28, '#fff5de', 8 ) . self::search_field( self::ACCENT )
			. self::r( 20, 38, 200, 108, self::WHITE, 10, self::LINE )
			. self::r( 190, 45, 22, 3.5, self::MUTED, 1.5 ) . self::r( 28, 45, 30, 3.5, '#e3e8eb', 1.5 )
			. $rows
			. self::r( 20, 134, 200, 1, self::LINE, 0 ) . self::r( 90, 138.5, 60, 3.5, '#d68800', 1.5 );
	}

	/* ---------------------------------------------------------------------
	 * Generic
	 * ------------------------------------------------------------------- */

	/**
	 * Custom templates and the theme option.
	 *
	 * @param string $key Kind or `theme`.
	 */
	private static function generic( string $key ): string {
		if ( 'theme' === $key ) {
			return self::r( 0, 0, 240, 150, '#eef6f5', 0 )
				. self::r( 70, 40, 100, 70, self::WHITE, 10, '#b9d9d5' )
				. '<path d="M104 88l16-26 16 26z" fill="#26a69a" opacity=".85"/><circle cx="136" cy="62" r="6" fill="#26a69a" opacity=".6"/>';
		}

		if ( 'none' === $key ) {
			return self::r( 0, 0, 240, 150, self::BG, 0 ) . '<path d="M100 55l40 40M140 55l-40 40" stroke="#b6c0c6" stroke-width="6" stroke-linecap="round"/>';
		}

		if ( 'same' === $key ) {
			return self::r( 0, 0, 240, 150, self::BG, 0 )
				. self::r( 38, 34, 104, 70, self::WHITE, 7, self::LINE ) . self::r( 38, 34, 104, 14, self::INK, 7 )
				. self::r( 152, 44, 50, 80, self::WHITE, 8, self::LINE ) . self::r( 152, 44, 50, 14, self::INK, 8 );
		}

		// Custom template: blank page with the Elementor "e".
		return self::r( 0, 0, 240, 150, self::BG, 0 )
			. self::r( 80, 35, 80, 80, self::WHITE, 40, self::LINE )
			. '<path d="M107 60h6v30h-6zM118 60h16v6h-16zM118 72h16v6h-16zM118 84h16v6h-16z" fill="#92003b"/>';
	}
}
