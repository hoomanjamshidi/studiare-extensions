<?php
/**
 * Coupon box of the cart and checkout summaries. checkout.js applies the
 * code through WooCommerce's own AJAX endpoint and writes the answer, good
 * or bad, under the field instead of in a notice bar at the top. The field
 * has no name, so the checkout form never posts it.
 *
 * @var bool $open Show the field at once instead of behind "Have a coupon?".
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;
?>
<details class="stx-coupon" data-stx-coupon<?php echo $open ? ' open' : ''; ?>>
	<summary class="stx-coupon__toggle"><?php echo Icon_Library::svg( 'phosphor', 'discount' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php esc_html_e( 'Have a coupon?', 'studiare-extensions' ); ?></span></summary>
	<div class="stx-coupon__body">
		<label class="screen-reader-text" for="stx-coupon-code"><?php esc_html_e( 'Coupon code', 'studiare-extensions' ); ?></label>
		<div class="stx-coupon__row">
			<input type="text" class="stx-coupon__input" id="stx-coupon-code" placeholder="<?php esc_attr_e( 'Enter your coupon code', 'studiare-extensions' ); ?>" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false" enterkeyhint="go" data-stx-coupon-input>
			<button type="button" class="stx-coupon__apply" data-stx-coupon-apply><?php esc_html_e( 'Apply', 'studiare-extensions' ); ?></button>
		</div>
		<p class="stx-coupon__message" role="status" aria-live="polite" data-stx-coupon-message hidden></p>
	</div>
</details>
