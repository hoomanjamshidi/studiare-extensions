<?php
/**
 * Audio products module: WooCommerce products whose audio buyers listen to
 * on the site (and may download), with a free preview for everyone else.
 *
 * The tracks are set in a box on the product edit screen; the audio of the
 * course's Studiare lessons can join them. The player shows in the product
 * description and, for buyers, on a "My audio" tab of the account page.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

use StudiareExt\Core\Color;
use StudiareExt\Core\Module as Base_Module;
use StudiareExt\Core\Sanitizer;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Module extends Base_Module {

	public function id(): string {
		return 'audio_products';
	}

	public function title(): string {
		return __( 'Audio products', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'Buyers listen to audio products on the site with a dedicated player, and visitors hear a free preview before buying.', 'studiare-extensions' );
	}

	public function icon(): string {
		return 'play';
	}

	public function defaults(): array {
		return Schema::defaults();
	}

	public function sanitize( array $input ): array {
		return Sanitizer::apply( Schema::fields(), $input, Schema::defaults() );
	}

	protected function boot(): void {
		// The tracks are WooCommerce products; without it there is nothing to sell or play.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		( new Stream( $this ) )->register();
		( new My_Account( $this ) )->register();

		if ( is_admin() ) {
			( new Edit_Box( $this ) )->register();
		} else {
			( new Frontend( $this ) )->register();
		}
	}

	public function render_admin(): void {
		$module = $this;
		require __DIR__ . '/views/admin.php';
	}

	public function enqueue_admin_assets(): void {
		// The preview renders with the real front-end stylesheet.
		wp_enqueue_style( Frontend::HANDLE, STUDIARE_EXT_URL . 'assets/modules/audio-products/css/audio-products.css', array(), STUDIARE_EXT_VERSION );

		wp_enqueue_script(
			'stx-audio-products-admin',
			STUDIARE_EXT_URL . 'assets/modules/audio-products/js/audio-products-admin.js',
			array( 'stx-admin' ),
			STUDIARE_EXT_VERSION,
			true
		);
	}

	public function admin_script_data(): array {
		return array(
			'themeAccent' => Theme_Bridge::palette()['primary'],
			'i18n'        => array(
				'label' => Schema::defaults()['player']['label'],
			),
		);
	}

	/**
	 * Inline colour tokens of the player: the accent (Studiare's primary
	 * colour unless the admin chose one) and the readable colour on top of it.
	 */
	public function color_vars(): string {
		$accent = $this->settings()['colors']['accent'];
		$vars   = '' !== $accent ? '--stx-ap-accent:' . $accent . ';' : '';

		return $vars . '--stx-ap-on-accent:' . Color::ink( '' !== $accent ? $accent : Theme_Bridge::palette()['primary'] );
	}

	/**
	 * Player markup for one product and the current visitor, or '' when it
	 * has no track this visitor can see.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $place   `product` (the product page) or `account`.
	 */
	public function player( \WC_Product $product, string $place ): string {
		$playlist = new Playlist( (int) $product->get_id(), get_current_user_id(), $this->settings() );
		if ( ! $playlist->tracks() ) {
			return '';
		}

		$settings = $this->settings();
		$player   = Player::view( $product, $playlist, $settings );
		$style    = $this->color_vars();

		ob_start();
		require __DIR__ . '/views/player.php';

		return (string) ob_get_clean();
	}
}
