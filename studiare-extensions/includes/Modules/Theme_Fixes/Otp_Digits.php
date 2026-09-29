<?php
/**
 * Fix: the theme's mobile login (OTP) accepts numbers typed with a Persian
 * keyboard.
 *
 * Studiare Core's combined OTP form checks the number with `/^[0-9]{11}$/`
 * in the browser, and only its code field converts Persian digits, so
 * ۰۹۱۲… fails with "must be exactly 11 digits". The older login and
 * registration forms convert nothing and send the Persian digits as they are.
 *
 * otp-digits.js rewrites the fields to Latin digits while the visitor types
 * and turns a mobile number into the local 09… form that the theme expects.
 * The same rewrite runs on the theme's AJAX requests, for pages cached
 * before the fix was switched on. The theme's handlers are encoded, so both
 * work from the outside only: form field names (`otp_…`) and AJAX actions.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

use StudiareExt\Core\Asset;
use StudiareExt\Core\Persian;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Otp_Digits extends Fix {

	private const HANDLE = 'stx-otp-digits';

	/** Studiare Core's OTP requests: the combined form, then the older login and registration forms. */
	private const ACTIONS = array(
		'check_and_send_combined_otp',
		'verify_combined_otp',
		'otp_send_verification_code',
		'otp_validate_otp',
		'otp_login_validate_otp',
	);

	/** Every OTP field is named `otp_…`: otp_phone, otp_code, otp_reg_phone, otp_reg, otp_back. */
	private const FIELD_PREFIX = 'otp_';

	public function id(): string {
		return 'otp_digits';
	}

	public function title(): string {
		return __( 'Persian digits in the mobile login', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'The theme\'s "log in with mobile" form rejects a number typed with a Persian keyboard (۰۹۱۲…) with "must be exactly 11 digits". This fix turns the digits into English ones as they are typed, and also accepts numbers written with +98, with 0098 or without the first zero.', 'studiare-extensions' );
	}

	/** The OTP forms exist only while Studiare's "Activate OTP" option is on. */
	public function in_use(): bool {
		return (bool) Theme_Bridge::option( 'otp', false );
	}

	public function boot(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// Before Studiare Core's handlers, which use the default priority.
		foreach ( self::ACTIONS as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, 'normalize_request' ), 1 );
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'normalize_request' ), 1 );
		}
	}

	/**
	 * The theme loads its OTP scripts on every page (the login popup can open
	 * anywhere), so this tiny script follows them.
	 */
	public function enqueue(): void {
		if ( ! $this->in_use() ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			Asset::url( 'assets/modules/theme-fixes/js/otp-digits.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Rewrites the OTP fields of the current request before the theme reads
	 * them. The theme's handler still checks its own nonce and input.
	 */
	public function normalize_request(): void {
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- only digits are kept; Studiare Core verifies the request.
		foreach ( $_POST as $name => $value ) {
			if ( ! is_string( $value ) || 0 !== strpos( (string) $name, self::FIELD_PREFIX ) ) {
				continue;
			}

			$_POST[ $name ] = self::normalize( (string) $name, $value );
			if ( isset( $_REQUEST[ $name ] ) ) {
				$_REQUEST[ $name ] = $_POST[ $name ];
			}
		}
		// phpcs:enable
	}

	/**
	 * Latin digits only (codes and numbers have nothing else); a mobile
	 * number also becomes local: +98 912…, 0098 912… and 912… → 0912….
	 * Mirrors `normalize()` in otp-digits.js.
	 *
	 * @param string $name  Field name.
	 * @param string $value Value as sent.
	 */
	private static function normalize( string $name, string $value ): string {
		$digits = (string) preg_replace( '/\D/u', '', Persian::latin_digits( $value ) );

		if ( '_phone' !== substr( $name, -6 ) ) {
			return $digits;
		}

		if ( preg_match( '/^(?:00)?989\d{9}$/', $digits ) ) {
			return '0' . substr( $digits, -10 );
		}

		return preg_match( '/^9\d{9}$/', $digits ) ? '0' . $digits : $digits;
	}
}
