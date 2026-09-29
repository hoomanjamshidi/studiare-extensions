<?php
/**
 * Plugin Name:       Studiare Extensions
 * Description:       Extra features for the Studiare LMS theme: a customizable mobile bottom navigation, Elementor page templates for courses, products, headers and footers, and a floating support button.
 * Version:           1.7.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hooman Jamshidi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       studiare-extensions
 * Domain Path:       /languages
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;

define( 'STUDIARE_EXT_VERSION', '1.7.0' );
define( 'STUDIARE_EXT_FILE', __FILE__ );
define( 'STUDIARE_EXT_DIR', plugin_dir_path( __FILE__ ) );
define( 'STUDIARE_EXT_URL', plugin_dir_url( __FILE__ ) );
define( 'STUDIARE_EXT_MIN_PHP', '7.4' );

// Bail out gracefully on unsupported PHP instead of fatalling on newer syntax.
if ( version_compare( PHP_VERSION, STUDIARE_EXT_MIN_PHP, '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: minimum PHP version. */
						__( 'Studiare Extensions requires PHP %s or newer.', 'studiare-extensions' ),
						STUDIARE_EXT_MIN_PHP
					)
				)
			);
		}
	);
	return;
}

require_once STUDIARE_EXT_DIR . 'includes/Autoloader.php';
\StudiareExt\Autoloader::register();

// Boot on `init` (early priority) so translations are available to every module.
add_action( 'init', array( \StudiareExt\Plugin::class, 'instance' ), 1 );
