<?php
/**
 * Studiare+ cart page (replaces WooCommerce's cart/cart.php).
 *
 * The items are cards in a list instead of a table, with the totals in a
 * sticky column beside them. WooCommerce's filters and hooks run as in its
 * own template, and the classes its cart script needs stay in place:
 * `.woocommerce-cart-form__contents`, `.cart_item`, `.product-remove > a`,
 * `button[name="update_cart"]`. Studiare adds −/+ buttons to the quantity
 * field itself (codebean_woocommerce.php). The coupon box sits under the totals and
 * shows its messages there (checkout.js); without JavaScript the plain
 * WooCommerce coupon field in the form takes over.
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;
use StudiareExt\Modules\Checkout\Pages;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- a WooCommerce template: its variables (in a function scope) and WooCommerce's own hooks.

$stx_settings = Plugin::instance()->module( 'checkout' )->settings()['cart'];
$stx_count    = WC()->cart->get_cart_contents_count();

do_action( 'woocommerce_before_cart' );

Pages::steps( 1, ! empty( $stx_settings['steps'] ) );
?>
<div class="stx-co stx-cartpage<?php echo empty( $stx_settings['auto_update'] ) ? '' : ' is-auto'; ?>">
	<form class="woocommerce-cart-form stx-cartpage__form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<div class="stx-cartpage__head">
			<h2 class="stx-cartpage__title"><?php esc_html_e( 'Your cart', 'studiare-extensions' ); ?></h2>
			<span class="stx-cartpage__count">
				<?php
				/* translators: %s: number of items. */
				echo esc_html( sprintf( _n( '%s item', '%s items', $stx_count, 'studiare-extensions' ), \StudiareExt\Core\Persian::number( $stx_count ) ) );
				?>
			</span>
		</div>

		<ul class="shop_table cart woocommerce-cart-form__contents stx-cartpage__items">
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				$visible    = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

				if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
					continue;
				}

				// WooCommerce 11.2+ adds the chosen "Any" attributes of a variation to its name; older versions show the stored name.
				$cart_item_name    = method_exists( WC()->cart, 'get_item_product_name' ) ? WC()->cart->get_item_product_name( $cart_item, $_product ) : $_product->get_name();
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key );
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
				$single            = $_product->is_sold_individually();
				?>
				<li class="woocommerce-cart-form__cart-item stx-cartpage__item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<div class="product-thumbnail stx-cartpage__thumb">
						<?php
						if ( $product_permalink ) {
							printf( '<a href="%s" tabindex="-1" aria-hidden="true">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's image markup.
						} else {
							echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's image markup.
						}
						?>
					</div>

					<div class="product-name stx-cartpage__name">
						<?php
						if ( $product_permalink ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $cart_item_name ), $cart_item, $cart_item_key ) );
						} else {
							echo wp_kses_post( $product_name );
						}

						do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

						echo wc_get_formatted_cart_item_data( $cart_item, false, $cart_item_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's escaped item data.

						if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'studiare-extensions' ) . '</p>', $product_id ) );
						}
						?>
						<span class="product-price stx-cartpage__unit">
							<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's price markup. ?>
						</span>
					</div>

					<div class="product-quantity stx-cartpage__qty<?php echo $single ? ' is-single' : ''; ?>">
						<?php
						$product_quantity = woocommerce_quantity_input(
							array(
								'input_name'   => "cart[{$cart_item_key}][qty]",
								'input_value'  => $cart_item['quantity'],
								'max_value'    => $single ? 1 : $_product->get_max_purchase_quantity(),
								'min_value'    => $single ? 1 : 0,
								'product_name' => $product_name,
							),
							$_product,
							false
						);
						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's quantity markup.
						?>
					</div>

					<div class="product-subtotal stx-cartpage__subtotal">
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's price markup. ?>
					</div>

					<div class="product-remove stx-cartpage__remove">
						<?php
						echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's filter, escaped below.
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a role="button" href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								/* translators: %s: product name. */
								esc_attr( sprintf( __( 'Remove %s from cart', 'studiare-extensions' ), wp_strip_all_tags( $product_name ) ) ),
								esc_attr( $product_id ),
								esc_attr( $_product->get_sku() ),
								Icon_Library::svg( 'phosphor', 'trash' )
							),
							$cart_item_key
						);
						?>
					</div>
				</li>
				<?php
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>
		</ul>

		<div class="actions stx-cartpage__actions">
			<?php if ( wc_coupons_enabled() ) : ?>
				<div class="coupon stx-cartpage__coupon-plain">
					<label for="coupon_code"><?php esc_html_e( 'Coupon code', 'studiare-extensions' ); ?></label>
					<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" dir="ltr" autocomplete="off">
					<button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'studiare-extensions' ); ?>"><?php esc_html_e( 'Apply', 'studiare-extensions' ); ?></button>
					<?php do_action( 'woocommerce_cart_coupon' ); ?>
				</div>
			<?php endif; ?>

			<button type="submit" class="button stx-cartpage__update" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'studiare-extensions' ); ?>"><?php esc_html_e( 'Update cart', 'studiare-extensions' ); ?></button>

			<?php do_action( 'woocommerce_cart_actions' ); ?>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>

			<?php if ( ! empty( $stx_settings['continue'] ) ) : ?>
				<a class="stx-cartpage__continue" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Continue shopping', 'studiare-extensions' ); ?></a>
			<?php endif; ?>
		</div>

		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

	<aside class="cart-collaterals stx-cartpage__side">
		<div class="stx-co__card stx-cartpage__summary">
			<?php do_action( 'woocommerce_cart_collaterals' ); ?>

			<?php
			if ( wc_coupons_enabled() && 'hidden' !== $stx_settings['coupon'] ) {
				Pages::coupon_box( 'open' === $stx_settings['coupon'] );
			}
			?>
		</div>

		<?php if ( '' !== $stx_settings['note'] ) : ?>
			<p class="stx-co__note"><?php echo Icon_Library::svg( 'phosphor', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php echo esc_html( $stx_settings['note'] ); ?></span></p>
		<?php endif; ?>
	</aside>

	<?php if ( ! empty( $stx_settings['mobile_bar'] ) ) : ?>
		<?php // `stx-co` of its own: checkout.js moves the bar to <body>. ?>
		<div class="stx-co stx-cartpage__bar" data-stx-cart-bar hidden>
			<span class="stx-cartpage__bar-total">
				<span class="stx-cartpage__bar-label"><?php esc_html_e( 'Total', 'studiare-extensions' ); ?></span>
				<span data-stx-bar-total><?php wc_cart_totals_order_total_html(); ?></span>
			</span>
			<a class="stx-cartpage__bar-go" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'studiare-extensions' ); ?></a>
		</div>
	<?php endif; ?>
</div>

<?php
if ( ! empty( $stx_settings['cross_sells'] ) ) {
	woocommerce_cross_sell_display();
}

do_action( 'woocommerce_after_cart' );
