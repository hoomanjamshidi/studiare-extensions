<?php
/**
 * Persian text helpers: Eastern Arabic-Indic digits and Jalali dates.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Persian {

	private const LATIN   = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	private const PERSIAN = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	private const ARABIC  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );

	/** Month names of the Solar Hijri calendar (calendar data, the same in every language). */
	private const JALALI_MONTHS = array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );

	/** Whether the site's front end is Persian. */
	public static function is_site_persian(): bool {
		return 'fa' === strtok( get_locale(), '_' );
	}

	/**
	 * Converts Latin digits (and separators) to Persian ones.
	 *
	 * @param string|int|float $text Text or number.
	 */
	public static function digits( $text ): string {
		// Decimal point and thousands comma between digits: ۰٫۶ and ۱٬۲۰۰.
		$text = (string) preg_replace( array( '/(?<=\d)\.(?=\d)/u', '/(?<=\d),(?=\d{3}\b)/u' ), array( '٫', '٬' ), (string) $text );

		return str_replace( self::LATIN, self::PERSIAN, $text );
	}

	/**
	 * Converts Persian and Arabic digits to Latin ones, to read numbers that
	 * admins typed with a Persian keyboard.
	 *
	 * @param string $text Text.
	 */
	public static function latin_digits( string $text ): string {
		return str_replace( array_merge( self::PERSIAN, self::ARABIC ), array_merge( self::LATIN, self::LATIN ), $text );
	}

	/**
	 * Converts the digits in the text nodes of trusted markup (e.g. WooCommerce
	 * price HTML), leaving tags, attributes and entities such as `&#x62A;` intact.
	 *
	 * @param string $html Markup.
	 */
	public static function digits_html( string $html ): string {
		$converted = preg_replace_callback(
			'/(^|>)([^<]+)/u',
			static function ( $found ) {
				$text = preg_replace_callback(
					'/&#?[a-z0-9]+;|[0-9]+(?:[.,][0-9]+)*/i',
					static function ( $part ) {
						return '&' === $part[0][0] ? $part[0] : self::digits( $part[0] );
					},
					$found[2]
				);

				return $found[1] . $text;
			},
			$html
		);

		return is_string( $converted ) ? $converted : $html;
	}

	/**
	 * Localised number: Persian digits on Persian sites, `number_format_i18n` elsewhere.
	 *
	 * @param int|float $number   Number.
	 * @param int       $decimals Decimals.
	 * @param bool      $persian  Whether Persian digits are wanted.
	 */
	public static function number( $number, int $decimals = 0, bool $persian = true ): string {
		if ( $persian && self::is_site_persian() ) {
			// Persian separators: ٫ (decimal) and ٬ (thousands), e.g. ۴٫۸ and ۱٬۲۴۰.
			return self::digits( number_format( (float) $number, $decimals, '٫', '٬' ) );
		}

		return number_format_i18n( (float) $number, $decimals );
	}

	/** Current year in the Solar Hijri (Jalali) calendar. */
	public static function jalali_year(): int {
		$now = current_datetime();

		return self::gregorian_to_jalali( (int) $now->format( 'Y' ), (int) $now->format( 'n' ), (int) $now->format( 'j' ) )[0];
	}

	/**
	 * Jalali date such as "14 مهر 1404", with Latin digits (see digits()).
	 * Computed from the stored date, so it is right whether or not a Jalali
	 * date plugin already converts WordPress's own date functions.
	 *
	 * @param \DateTimeInterface $date Date in the site's time zone.
	 */
	public static function jalali_date( \DateTimeInterface $date ): string {
		list( $year, $month, $day ) = self::gregorian_to_jalali( (int) $date->format( 'Y' ), (int) $date->format( 'n' ), (int) $date->format( 'j' ) );

		return $day . ' ' . self::jalali_month( $month ) . ' ' . $year;
	}

	/**
	 * Name of a Jalali month.
	 *
	 * @param int $month Month number, 1 (Farvardin) to 12 (Esfand).
	 */
	public static function jalali_month( int $month ): string {
		return self::JALALI_MONTHS[ max( 1, min( 12, $month ) ) - 1 ];
	}

	/**
	 * Gregorian → Jalali conversion (the well-known jdf algorithm).
	 *
	 * @param int $gy Year.
	 * @param int $gm Month.
	 * @param int $gd Day.
	 * @return int[] Year, month, day.
	 */
	public static function gregorian_to_jalali( int $gy, int $gm, int $gd ): array {
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];
		$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			--$days;
			$jy   += intdiv( $days, 365 );
			$days %= 365;
		}

		if ( $days < 186 ) {
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return array( $jy, $jm, $jd );
	}
}
