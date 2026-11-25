<?php
/**
 * Fix: the mobile sign-up says clearly when an email, username or mobile
 * number already belongs to an account.
 *
 * Studiare Core's combined OTP form decides "log in or sign up" from the
 * mobile number alone (its `sc_otp_tbl` table). A new number opens the
 * sign-up fields (name, email, username, password, as set in the theme's
 * options), but nothing checks them before the code is sent, and the
 * encoded handler that creates the account (`verify_combined_otp`) answers
 * a taken email or username with no usable message: the visitor gets
 * "unknown error" after typing the SMS code. The same happens to a number
 * that is the login name of an account made some other way (by email, at
 * checkout, by an admin) when the theme builds usernames from the mobile.
 *
 * The fix answers both requests first, with the theme's own JSON shape
 * (`data.message`, shown in its toast):
 * - `check_and_send_combined_otp`, when otp-taken-account.js has added the
 *   sign-up fields (`stx_register`), so no SMS is spent on a sign-up that
 *   cannot succeed;
 * - `verify_combined_otp` in `register` mode, for pages cached before the
 *   fix was switched on and visitors without the script.
 * The theme still checks its nonce and the code on every request it handles.
 *
 * "Change mobile number" hides the sign-up fields but leaves the terms box
 * ticked, which makes the theme sign the next number up at once, with
 * details nobody checked. The script unticks it, so the form starts over.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

use StudiareExt\Core\Asset;
use StudiareExt\Core\Persian;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Otp_Taken_Account extends Fix {

	private const HANDLE = 'stx-otp-taken-account';

	/**
	 * Lookups a visitor may run per window at the send step, which needs no
	 * SMS code. Beyond that the fix stays out of the way and the theme's own
	 * anti-spam limits apply, so the form cannot be used to test addresses
	 * in bulk.
	 */
	private const LOOKUP_LIMIT  = 10;
	private const LOOKUP_WINDOW = 10 * MINUTE_IN_SECONDS;

	public function id(): string {
		return 'otp_taken_account';
	}

	public function title(): string {
		return __( 'Taken email or mobile in the mobile sign-up', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'When someone signs up through the theme\'s "log in with mobile" form with an email, username or mobile number that already belongs to an account, the theme sends the code anyway and then shows "unknown error". This fix checks them before the code is sent and says which one is already registered.', 'studiare-extensions' );
	}

	/** The OTP forms exist only while Studiare's "Activate OTP" option is on. */
	public function in_use(): bool {
		return (bool) Theme_Bridge::option( 'otp', false );
	}

	public function boot(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// Priority 2: after Otp_Digits (1) has cleaned the number, before Studiare Core (10).
		foreach ( array( 'wp_ajax_', 'wp_ajax_nopriv_' ) as $prefix ) {
			add_action( $prefix . 'check_and_send_combined_otp', array( $this, 'check_before_sending' ), 2 );
			add_action( $prefix . 'verify_combined_otp', array( $this, 'check_before_creating' ), 2 );
		}
	}

	/** Loads on every page with the theme's OTP scripts (the login popup can open anywhere). */
	public function enqueue(): void {
		if ( ! $this->in_use() ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			Asset::url( 'assets/modules/theme-fixes/js/otp-taken-account.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Send step: only when the sign-up fields came along (they are on
	 * screen, so the theme already chose "sign up"). A number that logs in
	 * through the theme is still left to the theme, so leftover field values
	 * can never stand between a member and their account.
	 */
	public function check_before_sending(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only flag; Studiare Core verifies the request.
		if ( empty( $_POST['stx_register'] ) || self::has_otp_login( self::posted( 'otp_phone' ) ) || ! $this->within_lookup_limit() ) {
			return;
		}

		$this->refuse_taken();
	}

	/** Code step: the theme is about to create the account. */
	public function check_before_creating(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only; Studiare Core verifies the request.
		$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
		if ( 'register' === $mode ) {
			$this->refuse_taken();
		}
	}

	/** Ends the request with the theme's error shape when something is taken. */
	private function refuse_taken(): void {
		$message = self::taken_message( self::posted( 'sr_email' ), self::posted( 'sr_username' ), self::posted( 'otp_phone' ) );
		if ( '' !== $message ) {
			wp_send_json_error( array( 'message' => $message ) );
		}
	}

	/**
	 * What is already registered, as the message for the visitor; '' when
	 * nothing is. Email first: it is the field people mistype least and the
	 * one that tells them they already have an account.
	 *
	 * @param string $email    Sign-up email ('' when the field is off or empty).
	 * @param string $username Sign-up username ('' when the field is off or empty).
	 * @param string $mobile   Mobile number as sent.
	 */
	public static function taken_message( string $email, string $username, string $mobile ): string {
		if ( '' !== $email && is_email( $email ) && email_exists( $email ) ) {
			return __( 'This email is already registered. Log in to that account, or sign up with another email.', 'studiare-extensions' );
		}

		if ( '' !== $username && username_exists( sanitize_user( $username, true ) ) ) {
			return __( 'This username is already taken. Please choose another one.', 'studiare-extensions' );
		}

		if ( self::mobile_is_login( $mobile ) ) {
			return __( 'This mobile number is already registered on another account. Log in with that account\'s email or username.', 'studiare-extensions' );
		}

		return '';
	}

	/**
	 * Whether an account uses the number as its login name. The theme names
	 * new accounts after the mobile by default, so such an account cannot be
	 * created a second time.
	 *
	 * @param string $mobile Mobile number as sent.
	 */
	private static function mobile_is_login( string $mobile ): bool {
		foreach ( self::mobile_forms( $mobile ) as $login ) {
			if ( username_exists( $login ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the number logs in through the theme: Studiare Core keeps the
	 * numbers of OTP accounts in its own table, `{prefix}sc_otp_tbl`
	 * (`user_id`, `mobile_number`), and logs such a number in instead of
	 * signing it up. No table (Studiare Core missing or older): false.
	 *
	 * @param string $mobile Mobile number as sent.
	 */
	private static function has_otp_login( string $mobile ): bool {
		global $wpdb;

		$forms = self::mobile_forms( $mobile );
		$table = $wpdb->prefix . 'sc_otp_tbl';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Studiare Core's own table, read once per send request; the name comes from $wpdb->prefix.
		if ( ! $forms || $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			return false;
		}

		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$table} WHERE mobile_number IN (" . implode( ', ', array_fill( 0, count( $forms ), '%s' ) ) . ') LIMIT 1',
				$forms
			)
		);
		// phpcs:enable

		return null !== $found;
	}

	/**
	 * The forms a stored number may take (0912…, 912…, 98912…, +98912…);
	 * none when the value is not an Iranian mobile.
	 *
	 * @param string $mobile Mobile number as sent.
	 * @return string[]
	 */
	private static function mobile_forms( string $mobile ): array {
		$local = Persian::iran_mobile( $mobile );
		if ( ! preg_match( '/^09\d{9}$/', $local ) ) {
			return array();
		}

		$national = substr( $local, 1 );
		return array( $local, $national, '98' . $national, '+98' . $national );
	}

	/**
	 * One posted text field, trimmed.
	 *
	 * @param string $name Field name.
	 */
	private static function posted( string $name ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only lookups; Studiare Core verifies the request.
		return isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) ) : '';
	}

	/** Counts this visitor's send-step lookups; false once the limit is reached. */
	private function within_lookup_limit(): bool {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key   = 'stx_otp_taken_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= self::LOOKUP_LIMIT ) {
			return false;
		}

		set_transient( $key, $count + 1, self::LOOKUP_WINDOW );
		return true;
	}
}
