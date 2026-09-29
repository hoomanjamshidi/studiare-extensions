<?php
/**
 * Removes everything the plugin created: one `studiare_ext_<module>` option per
 * module, page templates and their per-product settings, the newsletter list
 * and the contact messages. Pages made from the page designs are the site's
 * own content and stay.
 *
 * @package StudiareExt
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes plugin options on the current site.
 */
function studiare_ext_uninstall_site(): void {
	global $wpdb;

	// Page templates (Elementor documents of the `stx_template` post type), newsletter subscribers and contact messages.
	$studiare_ext_posts = get_posts(
		array(
			'post_type'      => array( 'stx_template', 'stx_subscriber', 'stx_message' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $studiare_ext_posts as $studiare_ext_post_id ) {
		wp_delete_post( $studiare_ext_post_id, true );
	}

	// Per-product choices saved by the page templates module, and the design a page came from.
	delete_post_meta_by_key( '_stx_template' );
	delete_post_meta_by_key( '_stx_highlights' );
	delete_post_meta_by_key( '_stx_design' );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'studiare_ext_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off cleanup.
	wp_cache_flush();
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $studiare_ext_site_id ) {
		switch_to_blog( $studiare_ext_site_id );
		studiare_ext_uninstall_site();
		restore_current_blog();
	}
} else {
	studiare_ext_uninstall_site();
}
