<?php
/**
 * Registry of contact channels (Telegram, WhatsApp, Bale, Eitaa, …): labels,
 * brand colours, glyphs, and how the admin's input (a username, a number or
 * a link) becomes the URL visitors open.
 *
 * `url()` is mirrored by `channelUrl()` in support-button-admin.js, which
 * shows the resolved link under each field. Change them together.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Support_Button;

use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;

final class Channels {

	/**
	 * Bale and Eitaa are missing from every bundled icon pack, so their
	 * official glyphs (bale.ai, web.eitaa.com) ship here, redrawn with
	 * `currentColor` and rounded coordinates.
	 */
	private const BALE_GLYPH  = '<svg viewBox="0 0 24 25" fill="currentColor"><path d="M13.38 2.09H13.37C13.22 2.07 13.07 2.06 12.92 2.04L12.76 2.03L12.43 2.01H11.52L11.31 2.02L11.13 2.03L10.92 2.06L10.73 2.08L10.53 2.11L10.34 2.14L10.14 2.17L9.96 2.21L9.75 2.25L9.58 2.29L9.38 2.35L9.21 2.4L9.01 2.46L8.84 2.51L8.65 2.58L8.48 2.64L8.29 2.71L8.12 2.78L7.93 2.87L7.77 2.93L7.58 3.03L7.43 3.1L7.24 3.2L7.1 3.28L6.91 3.4L6.77 3.47L6.57 3.6L6.15 3.89C6.15 3.89 4.32 2.5 3.6 2.12C3.43 2.04 3.24 1.99 3.06 2C2.87 2.01 2.68 2.06 2.52 2.16C2.36 2.26 2.23 2.39 2.14 2.56C2.05 2.72 2 2.91 2 3.09V12.01C2 14.55 2.96 16.99 4.7 18.84C6.44 20.7 8.81 21.82 11.34 21.99H11.43L11.74 22H12.48C12.57 22 12.65 21.99 12.74 21.98L12.99 21.96L13.25 21.93L13.49 21.9L13.74 21.86L13.98 21.81C14.06 21.8 14.15 21.78 14.23 21.76L14.47 21.7L14.72 21.64L14.94 21.57L15.18 21.49L15.41 21.42L15.65 21.33L15.87 21.23L16.09 21.14L16.31 21.04L16.53 20.93L16.74 20.82L16.96 20.69L17.17 20.58L17.38 20.45L17.57 20.32L17.77 20.17L17.97 20.04C18.03 19.99 18.1 19.94 18.16 19.89L18.35 19.74L18.53 19.58L18.71 19.43L18.89 19.25L19.06 19.1L19.23 18.92L19.39 18.75L19.56 18.56L19.71 18.39L19.86 18.19L20.01 18.01L20.15 17.8L20.28 17.61C20.33 17.54 20.37 17.47 20.42 17.4L20.55 17.2C20.59 17.13 20.63 17.06 20.67 16.99C20.71 16.92 20.75 16.85 20.79 16.79L20.9 16.56L21.01 16.35C21.05 16.28 21.08 16.2 21.11 16.12C21.15 16.04 21.18 15.98 21.21 15.91C21.24 15.84 21.28 15.75 21.31 15.67L21.39 15.46C21.42 15.37 21.45 15.29 21.48 15.2L21.55 14.99C21.58 14.9 21.6 14.81 21.63 14.73C21.66 14.64 21.67 14.58 21.69 14.51C21.7 14.44 21.73 14.33 21.75 14.25C21.77 14.16 21.78 14.1 21.8 14.03L21.85 13.75L21.89 13.54C21.9 13.43 21.91 13.34 21.92 13.23C21.93 13.13 21.94 13.1 21.95 13.03C21.96 12.96 21.97 12.81 21.98 12.69C21.98 12.64 21.99 12.58 21.99 12.52C21.99 12.36 22 12.2 22 12.04V12.01C22 9.59 21.13 7.26 19.54 5.44C17.96 3.62 15.77 2.43 13.38 2.09ZM17.84 10.73L12.03 16.54C11.66 16.9 11.17 17.11 10.65 17.11C10.13 17.11 9.64 16.9 9.27 16.54L6.16 13.43C5.82 13.05 5.64 12.57 5.66 12.07C5.67 11.56 5.88 11.09 6.23 10.73C6.58 10.38 7.06 10.17 7.56 10.16C8.06 10.15 8.55 10.33 8.92 10.66L10.65 12.4L15.08 7.96C15.45 7.63 15.94 7.45 16.44 7.46C16.94 7.47 17.42 7.68 17.77 8.03C18.12 8.39 18.33 8.86 18.34 9.37C18.36 9.87 18.18 10.36 17.84 10.73Z"/></svg>';
	private const EITAA_GLYPH = '<svg viewBox="11.7 7.4 84 84" fill="currentColor"><path d="M88.8 57C91.9 53.3 88.4 50.2 84.9 53.4C75.5 61.9 62.5 71.4 49.8 72.7C48.5 74.7 47.5 77.8 47 80.1C46.8 81.3 46.5 81.4 45.7 81.1C41.2 79.4 37.5 74.7 36.3 70.3C28.2 66.2 24.9 58 27.9 48.6C32 35.8 47.3 26.7 60.7 28.3C78.7 30.4 76.2 46.6 60.6 48.3C53.6 49.1 43.7 45.7 45 37.5C37.7 42 35.4 51.5 42.4 57.6C37.1 63.9 37.5 72.6 44.5 77.6C44.6 77.7 44.9 77.7 45 77.3C47.2 69.2 52 64.8 58.1 62.1C69.9 56.9 81.2 48.7 83.6 36.5C86.1 23.5 79 12.7 65.8 10.4C59.4 9.3 53.1 10.4 47.3 12.7C30.5 19.3 17.2 37.1 15.5 55.3C14.8 63.1 16.2 70.4 19.5 76.3C26.8 89.2 42.2 92.8 55.9 87.9C65.6 84.5 72.2 77.3 79.5 68.2C82.5 64.4 85.6 60.6 88.8 57Z"/></svg>';

	/**
	 * Channel definitions. `color` is a CSS background (brand colours are
	 * darkened just enough for 3:1 contrast with the white glyph); an empty
	 * colour means the theme's primary colour.
	 *
	 * @return array<string, array{label:string, color:string, placeholder:string, help:string}>
	 */
	public static function all(): array {
		return array(
			'telegram'  => array(
				'label'       => __( 'Telegram', 'studiare-extensions' ),
				'color'       => '#229ed9',
				'placeholder' => '@username',
				'help'        => __( 'Username (with or without @), a t.me link, or a phone number with the country code.', 'studiare-extensions' ),
			),
			'whatsapp'  => array(
				'label'       => __( 'WhatsApp', 'studiare-extensions' ),
				'color'       => '#1da851',
				'placeholder' => '0912 345 6789',
				'help'        => __( 'Mobile number (09… or with the country code) or a wa.me link.', 'studiare-extensions' ),
			),
			'bale'      => array(
				'label'       => __( 'Bale', 'studiare-extensions' ),
				'color'       => '#00a383',
				'placeholder' => '@username',
				'help'        => __( 'Username (with or without @) or a ble.ir link.', 'studiare-extensions' ),
			),
			'eitaa'     => array(
				'label'       => __( 'Eitaa', 'studiare-extensions' ),
				'color'       => '#f2651f',
				'placeholder' => '@username',
				'help'        => __( 'Username (with or without @) or an eitaa.com link.', 'studiare-extensions' ),
			),
			'instagram' => array(
				'label'       => __( 'Instagram', 'studiare-extensions' ),
				'color'       => 'linear-gradient(45deg, #f58529, #dd2a7b 55%, #8134af)',
				'placeholder' => '@username',
				'help'        => __( 'Username. Visitors land straight in a direct message (ig.me).', 'studiare-extensions' ),
			),
			'phone'     => array(
				'label'       => __( 'Call us', 'studiare-extensions' ),
				'color'       => '',
				'placeholder' => '021 1234 5678',
				'help'        => __( 'Any phone number. On phones, tapping it starts the call.', 'studiare-extensions' ),
			),
			'email'     => array(
				'label'       => __( 'Email', 'studiare-extensions' ),
				'color'       => '',
				'placeholder' => 'support@example.com',
				'help'        => __( 'An email address.', 'studiare-extensions' ),
			),
			'link'      => array(
				'label'       => __( 'Support page', 'studiare-extensions' ),
				'color'       => '',
				'placeholder' => 'https://',
				'help'        => __( 'Any page or link: tickets, FAQ or an online chat service.', 'studiare-extensions' ),
			),
		);
	}

	/** @return string[] Channel ids in their default order. */
	public static function ids(): array {
		return array_keys( self::all() );
	}

	/**
	 * Switched-on channels that resolve to a link, in the saved order. Used by
	 * the floating button and by the Contact details widget, so the admin
	 * types each channel once.
	 *
	 * @param array $settings Support button settings.
	 * @return array<int, array{id:string, label:string, value:string, note:string, url:string, external:bool, glyph:string, brand:string}>
	 */
	public static function ready( array $settings ): array {
		$definitions = self::all();
		$list        = array();

		foreach ( $settings['order'] as $id ) {
			$channel = $settings['channels'][ $id ] ?? null;
			if ( ! $channel || ! $channel['enabled'] || ! isset( $definitions[ $id ] ) ) {
				continue;
			}

			$url = self::url( $id, $channel );
			if ( '' === $url ) {
				continue;
			}

			$list[] = array(
				'id'       => $id,
				'label'    => '' !== $channel['label'] ? $channel['label'] : $definitions[ $id ]['label'],
				'value'    => trim( (string) $channel['value'] ),
				'note'     => $channel['note'],
				'url'      => $url,
				'external' => self::is_external( $url ),
				'glyph'    => self::glyph( $id, $channel['icon'] ),
				'brand'    => $settings['colors']['brand'] ? $definitions[ $id ]['color'] : '',
			);
		}

		return $list;
	}

	/**
	 * Inline SVG glyph of a channel. The custom link uses the icon the admin
	 * picked; the others use their brand glyph.
	 *
	 * @param string $id   Channel id.
	 * @param string $icon Semantic icon key for the custom link.
	 */
	public static function glyph( string $id, string $icon = 'chat' ): string {
		switch ( $id ) {
			case 'telegram':
				return Icon_Library::svg( 'phosphor', 'telegram', true );
			case 'whatsapp':
			case 'instagram':
				return Icon_Library::svg( 'bootstrap', $id );
			case 'bale':
				return Icon_Library::decorate( self::BALE_GLYPH );
			case 'eitaa':
				return Icon_Library::decorate( self::EITAA_GLYPH );
			case 'phone':
				return Icon_Library::svg( 'phosphor', 'phone', true );
			case 'email':
				return Icon_Library::svg( 'phosphor', 'mail', true );
		}

		return Icon_Library::svg( 'phosphor', $icon, true );
	}

	/**
	 * The link a channel opens, or '' when the input cannot make one.
	 *
	 * @param string $id      Channel id.
	 * @param array  $channel Saved channel settings (`value`, `message`).
	 */
	public static function url( string $id, array $channel ): string {
		$value = self::latin_digits( trim( (string) $channel['value'] ) );
		if ( '' === $value ) {
			return '';
		}

		switch ( $id ) {
			case 'telegram':
				return self::is_phone( $value ) ? 'https://t.me/+' . self::international( $value ) : self::handle_url( $value, 'https://t.me/' );
			case 'whatsapp':
				return self::whatsapp_url( $value, (string) $channel['message'] );
			case 'bale':
				return self::handle_url( $value, 'https://ble.ir/' );
			case 'eitaa':
				return self::handle_url( $value, 'https://eitaa.com/' );
			case 'instagram':
				return self::handle_url( $value, 'https://ig.me/m/' );
			case 'phone':
				$number = ( '+' === $value[0] ? '+' : '' ) . preg_replace( '/\D/', '', $value );
				return strlen( $number ) >= 3 ? 'tel:' . $number : '';
			case 'email':
				return is_email( $value ) ? 'mailto:' . $value : '';
			case 'link':
				return self::is_url( $value ) || '/' === $value[0] ? esc_url_raw( self::with_scheme( $value ) ) : '';
		}

		return '';
	}

	/**
	 * Whether a link leaves the site; those open in a new tab so visitors
	 * keep the course page they were reading.
	 *
	 * @param string $url Resolved channel URL.
	 */
	public static function is_external( string $url ): bool {
		if ( 0 !== strpos( $url, 'http' ) ) {
			return false;
		}

		return wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * A pasted link is kept as is; a username becomes `<base><username>`.
	 *
	 * @param string $value Admin input.
	 * @param string $base  Profile URL prefix.
	 */
	private static function handle_url( string $value, string $base ): string {
		if ( self::is_url( $value ) ) {
			return esc_url_raw( self::with_scheme( $value ) );
		}

		$handle = ltrim( $value, '@' );

		return preg_match( '/^[A-Za-z0-9_.]{2,64}$/', $handle ) ? $base . $handle : '';
	}

	/**
	 * @param string $value   Number or wa.me link.
	 * @param string $message Optional first message typed for the visitor.
	 */
	private static function whatsapp_url( string $value, string $message ): string {
		if ( self::is_url( $value ) ) {
			return esc_url_raw( self::with_scheme( $value ) );
		}

		$number = self::international( $value );
		if ( strlen( $number ) < 8 ) {
			return '';
		}

		return 'https://wa.me/' . $number . ( '' === trim( $message ) ? '' : '?text=' . rawurlencode( trim( $message ) ) );
	}

	/**
	 * Digits in international format without "+". Iranian mobiles written
	 * locally (09…) get the 98 country code, since wa.me and t.me need it.
	 *
	 * @param string $value Phone number as typed.
	 */
	private static function international( string $value ): string {
		$digits = (string) preg_replace( '/\D/', '', $value );

		if ( '+' !== $value[0] && 0 === strpos( $digits, '00' ) ) {
			return substr( $digits, 2 );
		}

		if ( '+' !== $value[0] && preg_match( '/^09\d{9}$/', $digits ) ) {
			return '98' . substr( $digits, 1 );
		}

		return $digits;
	}

	/**
	 * @param string $value Admin input.
	 */
	private static function is_phone( string $value ): bool {
		return (bool) preg_match( '/^\+?[\d\s()-]{8,}$/', $value );
	}

	/**
	 * A scheme, or a host followed by a path (`t.me/name`). A dotted name
	 * without a slash stays a username, since Instagram allows dots.
	 *
	 * @param string $value Admin input.
	 */
	private static function is_url( string $value ): bool {
		return (bool) preg_match( '#^(https?://|[a-z0-9-]+(\.[a-z0-9-]+)+/)#i', $value );
	}

	/**
	 * @param string $value URL that may lack its scheme.
	 */
	private static function with_scheme( string $value ): string {
		return preg_match( '#^([a-z][a-z0-9+.-]*:|/)#i', $value ) ? $value : 'https://' . $value;
	}

	/**
	 * Admins often type numbers with a Persian keyboard.
	 *
	 * @param string $value Text with Persian or Arabic-Indic digits.
	 */
	private static function latin_digits( string $value ): string {
		return strtr(
			$value,
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
			)
		);
	}
}
