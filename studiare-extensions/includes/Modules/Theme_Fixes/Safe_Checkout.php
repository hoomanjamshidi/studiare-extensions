<?php
/**
 * Fix: an error in another plugin's hook no longer strands the buyer on
 * the checkout page.
 *
 * Placing an order runs many hooks from the theme and other plugins
 * (order emails, SMS, course access, gateway code). When one of them throws
 * a PHP Error (for example PHPMailer on a host that disables `mail()`),
 * WooCommerce's checkout request ends with an error page instead of JSON,
 * and checkout.js shows only "There was an error processing your order…".
 * A free order (or a 100% coupon) has already been completed by then, and
 * a bank transfer or cheque order put on hold (its emails fail on that
 * status change), but the buyer stays on the checkout page with the course
 * still in the cart, and the next try is a repeat purchase.
 *
 * The fix runs WooCommerce's own checkout handler inside a `try`. When it
 * throws, the error is written to WooCommerce's log (source
 * `studiare-extensions`) and to the order's notes, and the buyer gets a
 * proper answer: an order the gateway already accepted goes to its
 * thank-you page, anything else gets a clear message. Errors that PHP cannot catch (memory, time limit)
 * are out of reach.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

defined( 'ABSPATH' ) || exit;

final class Safe_Checkout extends Fix {

	private const LOG_SOURCE = 'studiare-extensions';

	/** WooCommerce's handler for `?wc-ajax=checkout`. */
	private const HANDLER = array( 'WC_AJAX', 'checkout' );

	/** @var int Order created by the checkout being processed (0 before). */
	private $order_id = 0;

	public function id(): string {
		return 'safe_checkout';
	}

	public function title(): string {
		return __( 'Checkout error after placing an order', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'When the theme or another plugin fails while an order is being placed (for example while sending the order email), WooCommerce shows "There was an error processing your order" and the buyer stays on the checkout page, even though the order was already placed (a free order, or a bank transfer or cheque order waiting on hold). With this fix a placed order still opens its thank-you page, any other order gets a clear message, and the real error is saved in the order notes and in WooCommerce > Status > Logs (source: studiare-extensions), so it can be repaired.', 'studiare-extensions' );
	}

	public function in_use(): bool {
		return class_exists( 'WC_AJAX' );
	}

	public function boot(): void {
		$priority = class_exists( 'WC_AJAX' ) ? has_action( 'wc_ajax_checkout', self::HANDLER ) : false;
		if ( false === $priority ) {
			return; // Another plugin replaced WooCommerce's handler: leave it alone.
		}

		remove_action( 'wc_ajax_checkout', self::HANDLER, $priority );
		add_action( 'wc_ajax_checkout', array( $this, 'checkout' ), $priority );
		add_action( 'woocommerce_checkout_order_created', array( $this, 'remember_order' ) );
	}

	/**
	 * @param \WC_Order $order Order the checkout just created (or resumed).
	 */
	public function remember_order( $order ): void {
		if ( $order instanceof \WC_Order ) {
			$this->order_id = $order->get_id();
		}
	}

	/** WooCommerce's checkout handler, with a way back when a hook throws. */
	public function checkout(): void {
		$buffers = ob_get_level();

		try {
			\WC_AJAX::checkout();
		} catch ( \Throwable $error ) {
			// Drop whatever the failing code had printed: the answer must be JSON.
			while ( ob_get_level() > $buffers ) {
				ob_end_clean();
			}
			$this->recover( $error );
		}
	}

	/**
	 * Records the error and answers checkout.js. A placed order (a free order
	 * fails after payment_complete() has saved it, a bank transfer after its
	 * status became on hold) goes on to its thank-you page, with the cart
	 * emptied as WooCommerce would have done next.
	 *
	 * @param \Throwable $error What was thrown.
	 */
	private function recover( \Throwable $error ): void {
		$order = $this->order_id ? wc_get_order( $this->order_id ) : null;
		$order = $order instanceof \WC_Order ? $order : null;

		$this->log( $error, $order );

		if ( $order && self::placed( $order ) ) {
			wc_empty_cart();
			wp_send_json(
				array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_order_received_url(),
				)
			);
		}

		$message = $order
			/* translators: %s: order number */
			? sprintf( __( 'Your order could not be completed because of an error on the site. Please try again; if it happens again, contact us and mention order %s.', 'studiare-extensions' ), $order->get_order_number() )
			: __( 'Your order could not be completed because of an error on the site. Please try again; if it happens again, contact us.', 'studiare-extensions' );
		wc_add_notice( $message, 'error' );

		wp_send_json(
			array(
				'result'   => 'failure',
				'messages' => wc_print_notices( true ),
				'refresh'  => false,
				'reload'   => false,
			)
		);
	}

	/**
	 * Whether the gateway already accepted the order: paid (cash on delivery
	 * included, as "processing"), or on hold waiting for a bank transfer or
	 * cheque. A pending or failed order still needs the buyer at checkout.
	 *
	 * @param \WC_Order $order Order being placed.
	 */
	private static function placed( \WC_Order $order ): bool {
		return $order->is_paid() || $order->has_status( 'on-hold' );
	}

	/**
	 * Writes the error where the admin can find it: WooCommerce's log and,
	 * when there is one, the order's private notes.
	 *
	 * @param \Throwable     $error What was thrown.
	 * @param \WC_Order|null $order Order being placed.
	 */
	private function log( \Throwable $error, ?\WC_Order $order ): void {
		$summary = sprintf( '%s: %s (%s:%d)', get_class( $error ), $error->getMessage(), $error->getFile(), $error->getLine() );

		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->critical(
				'Checkout failed: ' . $summary,
				array(
					'source'   => self::LOG_SOURCE,
					'order_id' => $order ? $order->get_id() : 0,
					'trace'    => $error->getTraceAsString(),
				)
			);
		}

		if ( $order ) {
			/* translators: %s: PHP error message with file and line */
			$order->add_order_note( sprintf( __( 'Checkout error (caught by Studiare+): %s', 'studiare-extensions' ), $summary ) );
		}
	}
}
