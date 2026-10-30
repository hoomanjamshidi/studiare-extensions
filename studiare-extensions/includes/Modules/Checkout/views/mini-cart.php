<?php
/**
 * Studiare+ mini cart (replaces WooCommerce's and Studiare's cart/mini-cart.php).
 *
 * Items as small cards (picture, name, price, remove), then a footer with
 * the subtotal, what the buyer saves, the checkout button first and the
 * cart second, and the cart page's reassurance note; an empty cart gets a
 * short invitation to the shop. WooCommerce's filters and hooks run as in
 * its own template, and its remove script needs only
 * `a.remove_from_cart_button` with its data attributes inside
 * `.widget_shopping_cart_content`. Layout classes are our own (`stx-mc__*`):
 * Studiare and the bottom navigation style WooCommerce's mini cart classes.
 *
 * @var array $args WooCommerce's mini cart arguments (`list_class`).
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Persian;
use StudiareExt\Modules\Checkout\Mini_Cart;
use StudiareExt\Modules\Checkout\Pages;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- a WooCommerce template: its variables (in a function scope) and WooCommerce's own hooks.

$stx_settings = Plugin::instance()->module( 'checkout' )->settings();
$stx_persian  = Persian::is_site_persian();

do_action( 'woocommerce_before_mini_cart' );
?>
<div class="stx-mc">
	<?php if ( WC()->cart && ! WC()->cart->is_empty() ) : ?>
		<?php $stx_count = WC()->cart->get_cart_contents_count(); ?>
		<p class="stx-mc__count">
			<?php
			/* translators: %s: number of items. */
			echo esc_html( sprintf( _n( '%s item', '%s items', $stx_count, 'studiare-extensions' ), $stx_persian ? Persian::number( $stx_count ) : $stx_count ) );
			?>
		</p>

		<ul class="woocommerce-mini-cart stx-mc__list <?php echo esc_attr( $args['list_class'] ?? '' ); ?>">
			<?php
			do_action( 'woocommerce_before_mini_cart_contents' );

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				$visible    = apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key );

				if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
					continue;
				}

				// WooCommerce 11.2+ adds the chosen "Any" attributes of a variation to its name; older versions show the stored name.
				$cart_item_name    = method_exists( WC()->cart, 'get_item_product_name' ) ? WC()->cart->get_item_product_name( $cart_item, $_product ) : $_product->get_name();
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
				$product_price     = 0.0 === (float) $_product->get_price() ? Pages::free_price() : apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$quantity          = (int) $cart_item['quantity'];
				$quantity_text     = $quantity > 1 ? sprintf( '%s &times; %s', $stx_persian ? Persian::number( $quantity ) : $quantity, $product_price ) : $product_price;
				?>
				<li class="stx-mc__item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
					<?php if ( $product_permalink ) : ?>
						<a class="stx-mc__thumb" href="<?php echo esc_url( $product_permalink ); ?>" tabindex="-1" aria-hidden="true"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's image markup. ?></a>
					<?php else : ?>
						<span class="stx-mc__thumb"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's image markup. ?></span>
					<?php endif; ?>

					<div class="stx-mc__info">
						<?php if ( $product_permalink ) : ?>
							<a class="stx-mc__name" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_name ); ?></a>
						<?php else : ?>
							<span class="stx-mc__name"><?php echo wp_kses_post( $product_name ); ?></span>
						<?php endif; ?>
						<?php echo wc_get_formatted_cart_item_data( $cart_item, false, $cart_item_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's escaped item data. ?>
						<?php echo apply_filters( 'woocommerce_widget_cart_item_quantity', '<span class="quantity stx-mc__price">' . $quantity_text . '</span>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's price markup. ?>
					</div>

					<?php
					echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's filter, escaped below.
						'woocommerce_cart_item_remove_link',
						sprintf(
							'<a role="button" href="%s" class="remove remove_from_cart_button stx-mc__remove" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s" data-success_message="%s">%s</a>',
							esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
							/* translators: %s: product name. */
							esc_attr( sprintf( __( 'Remove %s from cart', 'studiare-extensions' ), wp_strip_all_tags( $product_name ) ) ),
							esc_attr( $product_id ),
							esc_attr( $cart_item_key ),
							esc_attr( $_product->get_sku() ),
							/* translators: %s: product name. */
							esc_attr( sprintf( __( '"%s" has been removed from your cart', 'studiare-extensions' ), wp_strip_all_tags( $product_name ) ) ),
							Icon_Library::svg( 'phosphor', 'trash' )
						),
						$cart_item_key
					);
					?>
				</li>
				<?php
			}

			do_action( 'woocommerce_mini_cart_contents' );
			?>
		</ul>

		<div class="stx-mc__footer">
			<?php $stx_saved = ! empty( $stx_settings['mini']['savings'] ) ? Mini_Cart::savings() : 0.0; ?>
			<?php if ( $stx_saved > 0 ) : ?>
				<p class="stx-mc__saved">
					<?php echo Icon_Library::svg( 'phosphor', 'discount' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
					<span><?php esc_html_e( 'You save', 'studiare-extensions' ); ?></span>
					<strong><?php echo wp_kses_post( wc_price( $stx_saved ) ); ?></strong>
				</p>
			<?php endif; ?>

			<p class="stx-mc__total"><?php do_action( 'woocommerce_widget_shopping_cart_total' ); ?></p>

			<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>
			<p class="stx-mc__actions"><?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?></p>
			<?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>

			<?php if ( '' !== $stx_settings['cart']['note'] ) : ?>
				<p class="stx-mc__note"><?php echo Icon_Library::svg( 'phosphor', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php echo esc_html( $stx_settings['cart']['note'] ); ?></span></p>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="stx-mc__empty">
			<span class="stx-mc__empty-icon" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
			<p class="stx-mc__empty-title"><?php esc_html_e( 'Your cart is empty', 'studiare-extensions' ); ?></p>
			<p class="stx-mc__empty-text"><?php esc_html_e( 'Find a course you like and take the first step.', 'studiare-extensions' ); ?></p>
			<a class="stx-mc__shop" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Browse the courses', 'studiare-extensions' ); ?></a>
		</div>
	<?php endif; ?>
</div>
<?php
do_action( 'woocommerce_after_mini_cart' );
