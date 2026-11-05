<?php
/**
 * Hides the page templates feature from the admin until an admin opens the
 * secret unlock link.
 *
 * The feature is sold separately, so the plugin ships with it out of sight:
 * no menu item, dashboard card, product box or admin page. Opening
 * `admin.php?page=studiare-ext&stx_builder=<key>` reveals it for that admin
 * (user meta, so other admins still do not see it), and
 * `&stx_builder=off` hides it again. Only the key's SHA-256 hash is in the
 * source, so reading the plugin files does not give the link away; a site
 * can choose its own key with `STUDIARE_EXT_BUILDER_KEY` in wp-config.php.
 *
 * Only the admin UI is hidden. Templates already in use keep rendering for
 * visitors and the Elementor widgets keep working, so locking never changes
 * the site.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Admin\Admin;

defined( 'ABSPATH' ) || exit;

final class Access {

	/** Query argument of the unlock link. */
	public const QUERY_ARG = 'stx_builder';

	/** Value of the query argument that hides the feature again. */
	private const LOCK_VALUE = 'off';

	/** User meta that marks an admin who opened the link. */
	private const USER_META = 'studiare_ext_builder_access';

	/** SHA-256 of the default key (the key itself is never stored in the plugin). */
	private const KEY_HASH = 'b2c5c11caa17cf46426ffa1285f6c3f7ed4ac57a303ba5291195736c66d83bbe';

	/** @var Module */
	private $module;

	/**
	 * @param Module $module Builder module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'admin_init', array( $this, 'handle_link' ) );
	}

	/** Whether the current user may see the feature in the admin. */
	public static function granted(): bool {
		$user_id = get_current_user_id();

		return $user_id && current_user_can( Admin::CAPABILITY ) && get_user_meta( $user_id, self::USER_META, true );
	}

	/** Unlocks (right key) or locks (`off`) the feature for the current admin. */
	public function handle_link(): void {
		if ( ! isset( $_GET[ self::QUERY_ARG ] ) || ! current_user_can( Admin::CAPABILITY ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the key is the secret; it only changes the viewer's own menu.
			return;
		}

		$value = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_ARG ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( self::LOCK_VALUE === $value ) {
			delete_user_meta( get_current_user_id(), self::USER_META );
			wp_safe_redirect( admin_url( 'admin.php?page=' . Admin::MENU_SLUG ) );
			exit;
		}

		// A wrong key does nothing visible, so the link cannot be guessed by watching for a change.
		if ( ! self::matches( $value ) ) {
			return;
		}

		update_user_meta( get_current_user_id(), self::USER_META, 1 );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->module->admin_slug() ) );
		exit;
	}

	/**
	 * @param string $key Key from the link.
	 */
	private static function matches( string $key ): bool {
		if ( '' === $key ) {
			return false;
		}

		$expected = defined( 'STUDIARE_EXT_BUILDER_KEY' ) && '' !== (string) STUDIARE_EXT_BUILDER_KEY
			? hash( 'sha256', (string) STUDIARE_EXT_BUILDER_KEY )
			: self::KEY_HASH;

		return hash_equals( $expected, hash( 'sha256', $key ) );
	}
}
