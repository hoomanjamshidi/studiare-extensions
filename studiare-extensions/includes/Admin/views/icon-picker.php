<?php
/**
 * The icon picker dialog, included by the panels that offer the bundled
 * icons (bottom navigation buttons, support button channels). admin.js
 * drives it through `STX.iconPicker.open()`.
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;
?>
<dialog class="stx-dialog stx-icon-picker" id="stx-icon-picker" aria-labelledby="stx-icon-picker-title">
	<div class="stx-dialog__head">
		<h2 id="stx-icon-picker-title"><?php esc_html_e( 'Choose an icon', 'studiare-extensions' ); ?></h2>
		<button type="button" class="stx-icon-btn" data-stx-dialog-close aria-label="<?php esc_attr_e( 'Close', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
	</div>
	<div class="stx-icon-picker__search">
		<?php echo Icon_Library::svg( 'phosphor', 'search', false, 'stx-ico' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<input type="search" class="stx-input" data-stx-icon-search placeholder="<?php esc_attr_e( 'Search icons… (e.g. cart, سبد)', 'studiare-extensions' ); ?>">
	</div>
	<div class="stx-icon-picker__grid" data-stx-icon-grid role="listbox" aria-label="<?php esc_attr_e( 'Icons', 'studiare-extensions' ); ?>"></div>
</dialog>
