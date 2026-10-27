<?php
/**
 * Product page of an audio product: adds the player to the description
 * (before or after the text) and loads its assets on those pages only.
 *
 * Added through `the_content`, which Studiare's product page
 * (`woocommerce/sc_templates/studi_content.php`), WooCommerce's description
 * tab and Elementor product templates all run. What a visitor may hear is
 * decided per visitor, and the files themselves are checked again by Stream
 * on every request, so a cached page never opens a paid track.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

use StudiareExt\Core\Asset;
use StudiareExt\Core\Persian;

defined( 'ABSPATH' ) || exit;

final class Frontend {

	public const HANDLE = 'stx-audio-products';

	/** After wpautop (10) and shortcodes (11), so the markup is left as written. */
	private const CONTENT_PRIORITY = 20;

	/** @var Module */
	private $module;

	/** @var bool Whether the player was already added (the description can be filtered twice). */
	private $printed = false;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ), self::CONTENT_PRIORITY );
	}

	public function enqueue_assets(): void {
		if ( $this->viewed_product() ) {
			self::enqueue();
		}
	}

	/** Loads the player's stylesheet and script (also used by the account tab). */
	public static function enqueue(): void {
		wp_enqueue_style( self::HANDLE, Asset::url( 'assets/modules/audio-products/css/audio-products.css' ), array(), STUDIARE_EXT_VERSION );
		wp_enqueue_script(
			self::HANDLE,
			Asset::url( 'assets/modules/audio-products/js/audio-products.js' ),
			array(),
			STUDIARE_EXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script( self::HANDLE, 'window.stxAudioProducts = ' . wp_json_encode( self::script_data() ) . ';', 'before' );
	}

	/**
	 * Adds the player to the description of the product being viewed.
	 *
	 * @param string $content Filtered content.
	 */
	public function filter_content( $content ) {
		if ( $this->printed || doing_filter( 'get_the_excerpt' ) || doing_action( 'wp_head' ) || (int) get_the_ID() !== (int) get_queried_object_id() ) {
			return $content;
		}

		$product = $this->viewed_product();
		$player  = $product ? $this->module->player( $product, 'product' ) : '';
		if ( '' === $player ) {
			return $content;
		}

		$this->printed = true;

		return 'bottom' === $this->module->settings()['player']['position'] ? $content . $player : $player . $content;
	}

	/** The product being viewed, when it has audio. */
	private function viewed_product(): ?\WC_Product {
		if ( ! is_singular( 'product' ) || is_feed() || is_embed() || $this->is_page_builder_preview() ) {
			return null;
		}

		$product_id = (int) get_queried_object_id();
		if ( ! metadata_exists( 'post', $product_id, Album::META ) || post_password_required( $product_id ) ) {
			return null;
		}

		$product = wc_get_product( $product_id );

		return $product instanceof \WC_Product ? $product : null;
	}

	/** Inside Elementor's editing canvas the player would sit on top of the template being designed. */
	private function is_page_builder_preview(): bool {
		return class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance->preview )
			&& \Elementor\Plugin::$instance->preview->is_preview_mode();
	}

	/** Strings and settings for audio-products.js. */
	private static function script_data(): array {
		return array(
			'persian' => Persian::is_site_persian(),
			'i18n'    => array(
				/* translators: %s: track title. */
				'play'     => __( 'Play: %s', 'studiare-extensions' ),
				/* translators: %s: track title. */
				'pause'    => __( 'Pause: %s', 'studiare-extensions' ),
				/* translators: %s: playback speed, e.g. 1.5×. */
				'speed'    => __( 'Playback speed: %s', 'studiare-extensions' ),
				/* translators: 1: time listened, 2: length of the track. */
				'position' => __( '%1$s of %2$s', 'studiare-extensions' ),
			),
		);
	}
}
