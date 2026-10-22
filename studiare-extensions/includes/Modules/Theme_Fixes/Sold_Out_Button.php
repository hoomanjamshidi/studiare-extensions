<?php
/**
 * Fix: a full course shows a proper disabled button instead of a grey box.
 *
 * When a course is out of stock, Studiare prints WooCommerce's stock line
 * (`p.stock.out-of-stock.scoutstock`, «ظرفیت تکمیل شد!») where the buy button
 * was, styled as a flat #777 box with a dark border, and WooCommerce paints
 * its text red: hard to read and unlike the rest of the course card.
 *
 * The fix marks that line as a disabled button for assistive technology and
 * prints a small stylesheet that gives it the buy button's size with a soft
 * red tint and readable text.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Theme_Fixes;

use StudiareExt\Core\Asset;

defined( 'ABSPATH' ) || exit;

final class Sold_Out_Button extends Fix {

	private const HANDLE = 'stx-sold-out-button';

	/** Studiare adds this class to the stock line of courses only. */
	private const COURSE_CLASS = 'scoutstock';

	public function id(): string {
		return 'sold_out_button';
	}

	public function title(): string {
		return __( 'Sold-out course button', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'When a course is full, the theme replaces the buy button with a grey box and small red "Capacity is full!" text that is hard to read. This fix shows it as a disabled button the size of the buy button, with a soft red tint and readable text.', 'studiare-extensions' );
	}

	public function boot(): void {
		add_filter( 'woocommerce_get_stock_html', array( $this, 'mark_disabled' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds `role="button" aria-disabled="true"` to a course's sold-out line,
	 * so screen readers announce an unavailable button rather than a paragraph.
	 *
	 * @param string $html Stock HTML from wc_get_stock_html().
	 */
	public function mark_disabled( $html ) {
		if ( ! is_string( $html ) || false === strpos( $html, self::COURSE_CLASS ) || false !== strpos( $html, 'aria-disabled' ) ) {
			return $html;
		}

		return (string) preg_replace( '/^(\s*<p\b)/u', '$1 role="button" aria-disabled="true"', $html, 1 );
	}

	/** The stock line shows only on product pages; a few lines, printed inline. */
	public function enqueue(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		wp_register_style( self::HANDLE, false, array(), STUDIARE_EXT_VERSION );
		wp_enqueue_style( self::HANDLE );
		wp_add_inline_style( self::HANDLE, Asset::contents( 'assets/modules/theme-fixes/css/sold-out-button.css' ) );
	}
}
