<?php
/**
 * Studiare+ thank-you page (replaces WooCommerce's checkout/thankyou.php).
 *
 * A hero card with the order's state (an animated tick, a chip and the
 * buyer's name), the main buttons and the order facts; then what is
 * ready to open, each item with its own buttons (course, downloads,
 * player), and, beside WooCommerce's order details, the next steps.
 * WooCommerce's hooks run as in its own template, so the purchase steps
 * (`woocommerce_before_thankyou`), the gateways' instructions (bank
 * details) and the order details with downloads and addresses
 * (`woocommerce_thankyou`) keep working; Thank_You builds the rest.
 *
 * @var WC_Order|false $order
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;
use StudiareExt\Modules\Checkout\Thank_You;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- a WooCommerce template: its variables (in a function scope) and WooCommerce's own hooks.

// No order (a wrong or missing order key): WooCommerce shows only that the order was received.
if ( ! $order instanceof WC_Order ) {
	?>
	<div class="woocommerce-order stx-co stx-thanks is-received">
		<section class="stx-co__card stx-thanks__hero">
			<p class="stx-thanks__title"><?php echo wp_kses_post( apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'studiare-extensions' ), false ) ); ?></p>
		</section>
	</div>
	<?php
	return;
}

$stx_view = ( new Thank_You( $order, Plugin::instance()->module( 'checkout' )->settings()['thankyou'] ) )->view();
$stx_icon = array(
	'waiting' => 'clock',
	'failed'  => 'close',
);
// Icons of the ready items' buttons; a module's own kind gets the eye.
$stx_link_icons = array(
	'course'    => 'play',
	'audio'     => 'headphones',
	'download'  => 'download',
	'downloads' => 'list',
	'view'      => 'eye',
);

do_action( 'woocommerce_before_thankyou', $order->get_id() );
?>
<div class="woocommerce-order stx-co stx-thanks is-<?php echo esc_attr( $stx_view['state'] ); ?>">
	<section class="stx-co__card stx-thanks__hero" aria-labelledby="stx-thanks-title">
		<div class="stx-thanks__badge" aria-hidden="true">
			<?php if ( isset( $stx_icon[ $stx_view['state'] ] ) ) : ?>
				<?php echo Icon_Library::svg( 'phosphor', $stx_icon[ $stx_view['state'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
			<?php else : ?>
				<svg class="stx-thanks__tick" viewBox="0 0 52 52" focusable="false"><circle cx="26" cy="26" r="24"/><path d="M15.5 27.5l7 7L37 20"/></svg>
			<?php endif; ?>
			<?php if ( $stx_view['celebrate'] ) : ?>
				<span class="stx-thanks__burst">
					<?php for ( $stx_i = 0; $stx_i < 12; $stx_i++ ) : ?>
						<i style="--i:<?php echo (int) $stx_i; ?>"></i>
					<?php endfor; ?>
				</span>
			<?php endif; ?>
		</div>

		<p class="stx-thanks__chip"><?php echo esc_html( $stx_view['chip'] ); ?></p>
		<h2 class="stx-thanks__title" id="stx-thanks-title"><?php echo esc_html( $stx_view['title'] ); ?></h2>
		<p class="stx-thanks__lead"><?php echo wp_kses_post( $stx_view['lead_html'] ); ?></p>

		<?php if ( '' !== $stx_view['email'] ) : ?>
			<p class="stx-thanks__email">
				<?php echo Icon_Library::svg( 'phosphor', 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
				<span>
					<?php
					/* translators: %s: the customer's email address. */
					printf( esc_html__( 'The order details are also sent to %s.', 'studiare-extensions' ), '<bdi dir="ltr">' . esc_html( $stx_view['email'] ) . '</bdi>' );
					?>
				</span>
			</p>
		<?php endif; ?>

		<?php if ( $stx_view['actions'] ) : ?>
			<div class="stx-thanks__actions">
				<?php foreach ( $stx_view['actions'] as $stx_index => $stx_action ) : ?>
					<a class="stx-thanks__btn<?php echo 0 === $stx_index ? ' is-primary' : ''; ?>" href="<?php echo esc_url( $stx_action['url'] ); ?>"><?php echo esc_html( $stx_action['label'] ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $stx_view['note'] ) : ?>
			<p class="stx-thanks__note">
				<?php echo Icon_Library::svg( 'phosphor', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
				<span><?php echo esc_html( $stx_view['note'] ); ?></span>
			</p>
		<?php endif; ?>

		<ul class="stx-thanks__facts">
			<?php foreach ( $stx_view['facts'] as $stx_fact ) : ?>
				<li class="stx-thanks__fact">
					<span class="stx-thanks__fact-label"><?php echo esc_html( $stx_fact['label'] ); ?></span>
					<strong class="stx-thanks__fact-value"><?php echo wp_kses_post( $stx_fact['html'] ); ?></strong>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<?php if ( $stx_view['ready'] ) : ?>
		<section class="stx-thanks__ready" aria-labelledby="stx-thanks-ready">
			<h2 class="stx-thanks__heading" id="stx-thanks-ready"><?php echo Icon_Library::svg( 'phosphor', 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><?php esc_html_e( 'Ready to start', 'studiare-extensions' ); ?></h2>
			<ul class="stx-thanks__items">
				<?php foreach ( $stx_view['ready'] as $stx_item ) : ?>
					<li class="stx-co__card stx-thanks__item">
						<?php if ( '' !== $stx_item['url'] ) : ?>
							<a class="stx-thanks__item-image" href="<?php echo esc_url( $stx_item['url'] ); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_kses_post( $stx_item['image'] ); ?></a>
							<a class="stx-thanks__item-name" href="<?php echo esc_url( $stx_item['url'] ); ?>"><?php echo esc_html( $stx_item['name'] ); ?></a>
						<?php else : ?>
							<span class="stx-thanks__item-image"><?php echo wp_kses_post( $stx_item['image'] ); ?></span>
							<span class="stx-thanks__item-name"><?php echo esc_html( $stx_item['name'] ); ?></span>
						<?php endif; ?>
						<span class="stx-thanks__item-links">
							<?php foreach ( $stx_item['links'] as $stx_link ) : ?>
								<a class="stx-thanks__link is-<?php echo esc_attr( $stx_link['kind'] ); ?>" href="<?php echo esc_url( $stx_link['url'] ); ?>"<?php echo 'download' === $stx_link['kind'] ? ' title="' . esc_attr( $stx_link['label'] ) . '"' : ''; ?>>
									<?php echo Icon_Library::svg( 'phosphor', $stx_link_icons[ $stx_link['kind'] ] ?? 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
									<span><?php echo esc_html( $stx_link['label'] ); ?></span>
								</a>
							<?php endforeach; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<div class="stx-thanks__body<?php echo $stx_view['next'] ? ' has-side' : ''; ?>">
		<?php if ( $stx_view['next'] ) : ?>
			<aside class="stx-thanks__side">
				<section class="stx-co__card stx-thanks__next" aria-labelledby="stx-thanks-next">
					<h2 class="stx-thanks__heading" id="stx-thanks-next"><?php esc_html_e( 'What happens next', 'studiare-extensions' ); ?></h2>
					<ol class="stx-thanks__steps">
						<?php foreach ( $stx_view['next'] as $stx_step ) : ?>
							<li class="stx-thanks__step is-<?php echo esc_attr( $stx_step['status'] ); ?>"<?php echo 'current' === $stx_step['status'] ? ' aria-current="step"' : ''; ?>>
								<span class="stx-thanks__step-mark" aria-hidden="true"><?php echo 'done' === $stx_step['status'] ? Icon_Library::svg( 'phosphor', 'check' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
								<span class="stx-thanks__step-title"><?php echo esc_html( $stx_step['title'] ); ?></span>
								<span class="stx-thanks__step-text"><?php echo esc_html( $stx_step['text'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
				</section>
			</aside>
		<?php endif; ?>

		<div class="stx-thanks__main">
			<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
			<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		</div>
	</div>
</div>
