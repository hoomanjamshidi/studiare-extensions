<?php
/**
 * "My audio" tab of the account page: one player per audio product the
 * customer owns, or a link to the shop when there is none.
 *
 * @var string[] $players Player markup (Module::player()).
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.
?>
<div class="stx-ap-account">
	<?php if ( $players ) : ?>
		<?php echo implode( '', $players ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in views/player.php. ?>
	<?php else : ?>
		<div class="woocommerce-info">
			<?php esc_html_e( 'You have no audio products yet.', 'studiare-extensions' ); ?>
			<a class="button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'studiare-extensions' ); ?></a>
		</div>
	<?php endif; ?>
</div>
