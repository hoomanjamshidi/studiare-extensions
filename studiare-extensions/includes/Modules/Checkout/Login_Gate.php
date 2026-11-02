<?php
/**
 * Login before checkout: guests never reach the checkout page, however they
 * get there (cart button, mini cart, bottom nav, a typed address), and come
 * back to it, not to the account dashboard, once they log in.
 *
 * The gate is on the server (`template_redirect`), so it holds without
 * JavaScript and on cached pages. A guest is sent to the cart with Studiare's
 * login popup open (`popup`) or to the account page (`account`), and a short
 * cookie remembers the purchase. Every way of logging in ends on a page
 * view: Studiare's popup form posts to the account page (its `redirect`
 * field is not a valid address, so WooCommerce goes there), Studiare Core's
 * AJAX and OTP logins set `location.href` to it, others reload the page. The
 * first page a logged-in visitor with that cookie opens therefore sends them
 * on to the checkout.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Checkout;

use StudiareExt\Core\Asset;

defined( 'ABSPATH' ) || exit;

final class Login_Gate {

	private const HANDLE = 'stx-co-login';

	/** Remembers that a guest was on the way to the checkout. */
	private const COOKIE = 'stx_co_login';

	/** Added to the cart address to open the login popup there. */
	private const QUERY_VAR = 'stx-login';

	private const LIFETIME = HOUR_IN_SECONDS;

	/** @var array `login` settings. */
	private $settings;

	/**
	 * @param array $settings `login` settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		// Before WooCommerce's own redirects (priority 10), so a guest never
		// sees the checkout and a returning buyer skips the account page.
		add_action( 'template_redirect', array( $this, 'resume' ), 4 );
		add_action( 'template_redirect', array( $this, 'guard' ), 5 );
		add_action( 'woocommerce_checkout_process', array( $this, 'refuse_guest' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
	}

	/** Sends a guest with a cart from the checkout page to the login. */
	public function guard(): void {
		if ( is_user_logged_in() || ! is_checkout() || is_wc_endpoint_url() || ! $this->has_cart() ) {
			return;
		}

		$this->remember();
		wc_add_notice( $this->message(), 'notice' );
		wp_safe_redirect( $this->login_url() );
		exit;
	}

	/** Sends a visitor who just logged in on to the checkout. */
	public function resume(): void {
		if ( ! isset( $_COOKIE[ self::COOKIE ] ) || ! is_user_logged_in() ) {
			return;
		}

		$this->forget();
		if ( is_checkout() || ! $this->has_cart() ) {
			return;
		}

		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	/** An order posted by a guest (a page cached or opened before the gate) is refused. */
	public function refuse_guest(): void {
		if ( ! is_user_logged_in() ) {
			wc_add_notice( $this->message(), 'error' );
		}
	}

	/**
	 * In popup mode the cart page opens the popup instead of following its
	 * checkout links, and on arrival from the gate. The cart page is never
	 * cached, so its guest check is reliable.
	 */
	public function enqueue(): void {
		if ( 'popup' !== $this->settings['mode'] || is_user_logged_in() || ! is_cart() ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			Asset::url( 'assets/modules/checkout/js/login-gate.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script(
			self::HANDLE,
			'window.stxCheckoutLogin = ' . wp_json_encode(
				array(
					'checkoutUrl' => wc_get_checkout_url(),
					'accountUrl'  => wc_get_page_permalink( 'myaccount' ),
					'queryVar'    => self::QUERY_VAR,
					'cookie'      => self::COOKIE,
					'path'        => self::cookie_path(),
					'lifetime'    => self::LIFETIME,
				)
			) . ';',
			'before'
		);
	}

	private function has_cart(): bool {
		return WC()->cart instanceof \WC_Cart && ! WC()->cart->is_empty();
	}

	private function login_url(): string {
		if ( 'account' === $this->settings['mode'] ) {
			return wc_get_page_permalink( 'myaccount' );
		}

		return add_query_arg( self::QUERY_VAR, '1', wc_get_cart_url() );
	}

	private function message(): string {
		$message = (string) $this->settings['message'];

		return '' !== $message ? $message : __( 'Please log in to continue your purchase.', 'studiare-extensions' );
	}

	/** Not HttpOnly: login-gate.js sets the same cookie when it opens the popup. */
	private function remember(): void {
		$this->set_cookie( '1', time() + self::LIFETIME );
	}

	private function forget(): void {
		unset( $_COOKIE[ self::COOKIE ] );
		$this->set_cookie( '', time() - YEAR_IN_SECONDS );
	}

	/**
	 * @param string $value   Cookie value.
	 * @param int    $expires Expiry time.
	 */
	private function set_cookie( string $value, int $expires ): void {
		if ( headers_sent() ) {
			return;
		}

		setcookie(
			self::COOKIE,
			$value,
			array(
				'expires'  => $expires,
				'path'     => self::cookie_path(),
				'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			)
		);
	}

	private static function cookie_path(): string {
		return COOKIEPATH ? COOKIEPATH : '/';
	}
}
