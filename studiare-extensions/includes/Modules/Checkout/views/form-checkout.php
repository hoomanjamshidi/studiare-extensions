<?php
/**
 * Studiare+ checkout page (replaces WooCommerce's checkout/form-checkout.php).
 *
 * Two numbered cards, the buyer's details and the payment methods, beside a
 * sticky order summary (a collapsible bar on phones) that also holds the
 * coupon box. Everything stays inside `form.checkout` with WooCommerce's
 * hooks and ids (`#customer_details`, `#order_review`, `#payment`), so its
 * checkout script, the gateways and other plugins work unchanged; Pages
 * moves `woocommerce_checkout_payment()` out of the order review hook.
 *
 * @var WC_Checkout $checkout
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;
use StudiareExt\Modules\Checkout\Pages;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- a WooCommerce template: its variables (in a function scope) and WooCommerce's own hooks.

$stx_settings = Plugin::instance()->module( 'checkout' )->settings()['checkout'];

Pages::steps( 2, ! empty( $stx_settings['steps'] ) );

do_action( 'woocommerce_before_checkout_form', $checkout );

// Registration off and required: only logged-in customers can buy.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo '<p class="stx-co stx-co__must-login">' . esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'studiare-extensions' ) ) ) . '</p>';
	return;
}
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout stx-co stx-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php esc_attr_e( 'Checkout', 'studiare-extensions' ); ?>">
	<div class="stx-checkout__main">
		<?php if ( $checkout->get_checkout_fields() ) : ?>
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<section class="stx-co__card stx-checkout__section" id="customer_details">
				<h2 class="stx-checkout__title"><span class="stx-checkout__num" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span><?php esc_html_e( 'Your details', 'studiare-extensions' ); ?></h2>
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</section>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
		<?php endif; ?>

		<section class="stx-co__card stx-checkout__section stx-checkout__pay">
			<h2 class="stx-checkout__title"><span class="stx-checkout__num" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'wallet' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span><?php esc_html_e( 'Payment', 'studiare-extensions' ); ?></h2>
			<?php woocommerce_checkout_payment(); ?>
			<?php if ( '' !== $stx_settings['note'] ) : ?>
				<p class="stx-co__note"><?php echo Icon_Library::svg( 'phosphor', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php echo esc_html( $stx_settings['note'] ); ?></span></p>
			<?php endif; ?>
		</section>
	</div>

	<aside class="stx-checkout__side">
		<details class="stx-co__card stx-checkout__summary" data-stx-summary open>
			<summary class="stx-checkout__summary-head">
				<span class="stx-checkout__summary-title"><?php echo Icon_Library::svg( 'phosphor', 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span id="order_review_heading"><?php esc_html_e( 'Your order', 'studiare-extensions' ); ?></span></span>
				<span class="stx-checkout__summary-total" data-stx-summary-total><?php wc_cart_totals_order_total_html(); ?></span>
				<span class="stx-checkout__summary-chevron" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
			</summary>

			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

			<?php
			if ( wc_coupons_enabled() && 'hidden' !== $stx_settings['coupon'] ) {
				Pages::coupon_box( false );
			}
			?>
		</details>
	</aside>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
