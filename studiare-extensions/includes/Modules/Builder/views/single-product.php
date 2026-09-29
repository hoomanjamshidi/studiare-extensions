<?php
/**
 * Single product/course page rendered with a Studiare+ Elementor template.
 *
 * Loaded through `template_include`, so it runs in the global scope. The
 * theme header/footer stay in place; WooCommerce's notice, structured data
 * and "after product" hooks keep firing for plugins that depend on them.
 *
 * @package StudiareExt
 */

use StudiareExt\Modules\Builder\Module;
use StudiareExt\Modules\Builder\Renderer;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file.

$stx_module      = Plugin::instance()->module( 'builder' );
$stx_template_id = $stx_module instanceof Module ? $stx_module->single()->template_id() : 0;

get_header( 'shop' );

while ( have_posts() ) :
	the_post();

	global $product;
	$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WooCommerce's own templates do the same.

	if ( function_exists( 'sc_studi_gt_set_post_view' ) ) {
		sc_studi_gt_set_post_view(); // Studiare's view counter.
	}

	/** This action is documented in woocommerce/templates/content-single-product.php */
	do_action( 'woocommerce_before_single_product' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.

	if ( post_password_required() ) {
		echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup.
		continue;
	}
	?>
	<?php
	// WooCommerce's `product` class is left out on purpose: its `.woocommerce div.product …`
	// layout rules would otherwise restyle widgets inside the Elementor design.
	$stx_classes = array_diff( wc_get_product_class( 'stx-single', $product ), array( 'product' ) );
	?>
	<div id="product-<?php the_ID(); ?>" class="<?php echo esc_attr( implode( ' ', $stx_classes ) ); ?>">
		<?php echo Renderer::render( $stx_template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor output. ?>
	</div>
	<?php
	if ( $product && isset( WC()->structured_data ) ) {
		WC()->structured_data->generate_product_data( $product );
	}

	/** This action is documented in woocommerce/templates/content-single-product.php */
	do_action( 'woocommerce_after_single_product' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
endwhile;

get_footer( 'shop' );
