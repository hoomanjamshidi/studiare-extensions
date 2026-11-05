<?php
/**
 * Fix: the checkboxes of the WooCommerce orders list select the order again.
 *
 * WooCommerce opens an order when any cell of its row is clicked: wc-orders.js
 * binds `click` on `.wp-list-table tbody td` (orders screen, HPOS or the
 * older `shop_order` list) and skips only links and buttons. WordPress 7.1
 * prints the checkbox cell as `<td class="check-column">` instead of a `<th>`,
 * so up to WooCommerce 11.0 ticking an order's checkbox opened that order and
 * bulk actions could not be used. WooCommerce 11.1 excludes `.check-column`
 * itself; this fix does the same for older versions.
 *
 * WooCommerce's row handler leaves clicks on `.no-link` (and everything in
 * it) alone, so a capture listener gives the clicked checkbox cell that class
 * before the click reaches WooCommerce's delegated document handler. Rows
 * added later count too, and WordPress's own checkbox handling (shift-click
 * ranges, "select all") is untouched.
 *
 * Not a Studiare bug, but it shows up on Studiare sites that keep an older
 * WooCommerce, so it lives with the other repairs.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

defined( 'ABSPATH' ) || exit;

final class Order_List_Checkboxes extends Fix {

	/** WordPress version that prints the checkbox cell as a `td`. */
	private const WP_TD_CELLS = '7.1';

	/** WooCommerce version whose row handler skips the checkbox cell. */
	private const WC_FIXED = '11.1.0';

	public function id(): string {
		return 'order_list_checkboxes';
	}

	public function title(): string {
		return __( 'Order list checkboxes', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'In the WooCommerce orders list, ticking an order\'s checkbox opens the order instead of selecting it, so bulk actions cannot be used. This happens with WordPress 7.1 and WooCommerce versions before 11.1. This fix makes the checkboxes select orders again.', 'studiare-extensions' );
	}

	public function boot(): void {
		if ( $this->in_use() ) {
			// After WooCommerce registers `wc-orders` (priority 10).
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		}
	}

	/** Only the combination that has the problem: WordPress 7.1+ with WooCommerce before 11.1. */
	public function in_use(): bool {
		global $wp_version;

		return defined( 'WC_VERSION' )
			&& version_compare( WC_VERSION, self::WC_FIXED, '<' )
			&& version_compare( (string) $wp_version, self::WP_TD_CELLS, '>=' );
	}

	/**
	 * Attaches the script to WooCommerce's orders list script, so it prints
	 * exactly on the screens that load the row handler (and before it).
	 */
	public function enqueue(): void {
		wp_add_inline_script(
			'wc-orders',
			'document.addEventListener("click",function(e){var c=e.target&&e.target.closest&&e.target.closest(".wp-list-table tbody .check-column");if(c){c.classList.add("no-link");}},true);',
			'before'
		);
	}
}
