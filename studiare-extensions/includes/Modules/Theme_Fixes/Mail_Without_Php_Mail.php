<?php
/**
 * Fix: a host that disables PHP's mail() no longer stops orders half way.
 *
 * Many shared hosts switch off PHP's `mail()` and expect an SMTP plugin.
 * PHPMailer then calls a function that does not exist and PHP throws an
 * Error, which `wp_mail()` does not catch (it only catches PHPMailer's own
 * Exception). WooCommerce sends its order emails from the order status
 * change, so the Error ends the request right there: everything hooked
 * after the emails never runs. That includes Persian WooCommerce SMS, which
 * sends the buyer's and the admin's texts on `woocommerce_order_status_changed`
 * (priority 99, after the emails), course access granted on that change,
 * and the gateway's return page after a paid order (the buyer sees an error
 * page). Safe_Checkout catches it at checkout, but the skipped hooks are
 * lost.
 *
 * The fix steps in only while PHPMailer would use `mail()` and that
 * function is missing, so an SMTP plugin is never touched. It tries the
 * server's sendmail program instead when PHP may run it; otherwise it
 * empties the recipients, so PHPMailer refuses with its own Exception,
 * `wp_mail()` returns false and reports `wp_mail_failed` as for any failed
 * email, and the order goes on (texts included). The email itself still
 * needs an SMTP plugin: the admin is told so in the log.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

defined( 'ABSPATH' ) || exit;

final class Mail_Without_Php_Mail extends Fix {

	private const LOG_SOURCE = 'studiare-extensions';

	/** PHP functions PHPMailer's sendmail transport calls without checking. */
	private const SENDMAIL_FUNCTIONS = array( 'popen', 'pclose', 'escapeshellcmd', 'escapeshellarg' );

	/** @var bool Whether this request already wrote the log line. */
	private $logged = false;

	public function id(): string {
		return 'mail_without_php_mail';
	}

	public function title(): string {
		return __( 'Order emails on hosts without PHP mail()', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'This host has switched off PHP\'s mail() function. Every email WordPress sends then crashes the page that sends it: after a purchase the order stops half way, so the SMS texts to the buyer and the admin (Persian WooCommerce SMS), the order emails and the payment return page all fail. With this fix the email is sent through the server\'s sendmail program when possible, or skipped, and the order carries on normally. To deliver the emails themselves, install an SMTP plugin (for example WP Mail SMTP or FluentSMTP) or ask the host to enable mail().', 'studiare-extensions' );
	}

	/** Only hosts without PHP's mail() have the problem. */
	public function in_use(): bool {
		return ! function_exists( 'mail' );
	}

	public function boot(): void {
		if ( $this->in_use() ) {
			// Last, so the transport an SMTP plugin chose is what we see.
			add_action( 'phpmailer_init', array( $this, 'guard' ), PHP_INT_MAX );
		}
	}

	/**
	 * Swaps the missing mail() transport for one that cannot crash.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer The mailer wp_mail() is about to send with.
	 */
	public function guard( $phpmailer ): void {
		if ( ! is_object( $phpmailer ) || 'mail' !== $phpmailer->Mailer ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer's property.
			return;
		}

		if ( self::sendmail_allowed() ) {
			// A missing or refused program makes PHPMailer throw its own Exception, which wp_mail() catches.
			$phpmailer->isSendmail();
			return;
		}

		// No recipients: send() throws PHPMailer's "provide an address" Exception before reaching mail().
		$phpmailer->clearAllRecipients();
		$this->log();
	}

	private static function sendmail_allowed(): bool {
		foreach ( self::SENDMAIL_FUNCTIONS as $function ) {
			if ( ! function_exists( $function ) ) {
				return false;
			}
		}

		return true;
	}

	/** One line per request in WooCommerce's log, so the admin learns why emails are missing. */
	private function log(): void {
		if ( $this->logged || ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$this->logged = true;
		wc_get_logger()->warning(
			'An email was not sent: PHP mail() is disabled on this host. Install an SMTP plugin or ask the host to enable mail().',
			array( 'source' => self::LOG_SOURCE )
		);
	}
}
