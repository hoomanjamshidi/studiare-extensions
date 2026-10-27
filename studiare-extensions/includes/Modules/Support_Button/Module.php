<?php
/**
 * Floating support button module: a corner button that opens the site's
 * contact channels (Telegram, WhatsApp, Bale, Eitaa, …).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Support_Button;

use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Module as Base_Module;
use StudiareExt\Core\Sanitizer;

defined( 'ABSPATH' ) || exit;

final class Module extends Base_Module {

	public function id(): string {
		return 'support_button';
	}

	public function title(): string {
		return __( 'Floating support button', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'A corner button that opens Telegram, WhatsApp, Bale, Eitaa, Rubika, your own channels and more, and moves out of the way of the bottom navigation.', 'studiare-extensions' );
	}

	public function icon(): string {
		return 'support';
	}

	public function defaults(): array {
		return Schema::defaults();
	}

	public function sanitize( array $input ): array {
		$clean           = Sanitizer::apply( Schema::fields(), $input, Schema::defaults() );
		$clean['custom'] = Schema::valid_custom( $clean['custom'] );
		$clean['order']  = Schema::complete_order( $clean['order'], $clean['custom'] );

		return $clean;
	}

	protected function boot(): void {
		( new Frontend( $this ) )->register();
	}

	/**
	 * Adds channels introduced in newer versions to a saved order, and fills
	 * keys added to custom channels since they were saved.
	 *
	 * @param array $settings Settings merged with defaults.
	 */
	protected function normalize( array $settings ): array {
		$custom = is_array( $settings['custom'] ) ? $settings['custom'] : array();

		$settings['custom'] = array_map(
			static function ( $channel ): array {
				return array_merge( Schema::custom_defaults(), is_array( $channel ) ? $channel : array() );
			},
			$custom
		);
		$settings['order']  = Schema::complete_order( is_array( $settings['order'] ) ? $settings['order'] : array(), $settings['custom'] );

		return $settings;
	}

	public function render_admin(): void {
		$module = $this;
		require __DIR__ . '/views/admin.php';
	}

	public function enqueue_admin_assets(): void {
		// Custom channels can use an image from the media library as their icon.
		wp_enqueue_media();

		// The preview renders with the real front-end stylesheet.
		wp_enqueue_style( 'stx-support-button', STUDIARE_EXT_URL . 'assets/modules/support-button/css/support-button.css', array(), STUDIARE_EXT_VERSION );

		wp_enqueue_script(
			'stx-support-button-admin',
			STUDIARE_EXT_URL . 'assets/modules/support-button/js/support-button-admin.js',
			array( 'stx-admin', 'jquery-ui-sortable' ),
			STUDIARE_EXT_VERSION,
			true
		);
	}

	public function admin_script_data(): array {
		$channels = array();
		foreach ( Channels::all() as $id => $channel ) {
			$channels[ $id ] = $channel + array( 'glyph' => Channels::glyph( $id ) );
		}

		// Every bundled icon, filled like the channel glyphs: the main button's
		// choices and the icons a custom channel can pick.
		$icons = array();
		foreach ( Icon_Library::icon_keys() as $key ) {
			$icons[ $key ] = Icon_Library::svg( 'phosphor', $key, true );
		}

		return array(
			'channels'       => $channels,
			'icons'          => $icons,
			'catalog'        => Icon_Library::catalog()['icons'],
			'customDefaults' => Schema::custom_defaults(),
			'customPrefix'   => Schema::CUSTOM_PREFIX,
			'maxCustom'      => Schema::MAX_CUSTOM,
			'closeIcon'      => Icon_Library::svg( 'phosphor', 'close' ),
			'i18n'           => array(
				'notSet'        => __( 'Not set', 'studiare-extensions' ),
				'invalid'       => __( 'Check the value', 'studiare-extensions' ),
				'off'           => __( 'Off', 'studiare-extensions' ),
				'opens'         => __( 'Opens:', 'studiare-extensions' ),
				'support'       => __( 'Support', 'studiare-extensions' ),
				'close'         => __( 'Close', 'studiare-extensions' ),
				'needsName'     => __( 'Add a name', 'studiare-extensions' ),
				'newChannel'    => __( 'New channel', 'studiare-extensions' ),
				'deleteTitle'   => __( 'Delete this channel?', 'studiare-extensions' ),
				/* translators: %s: channel name. */
				'deleteMessage' => __( '"%s" will be removed from the support button.', 'studiare-extensions' ),
				'delete'        => __( 'Delete', 'studiare-extensions' ),
				/* translators: %d: maximum number of custom channels. */
				'maxReached'    => sprintf( __( 'You can make up to %d channels of your own.', 'studiare-extensions' ), Schema::MAX_CUSTOM ),
			),
		);
	}
}
