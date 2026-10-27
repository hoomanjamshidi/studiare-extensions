<?php
/**
 * "Studiare+ audio" box on the product edit screen: the product's tracks,
 * each with its free preview, plus the download and Studiare lesson
 * switches. Saved to `_stx_audio` through Album::save().
 *
 * Files uploaded from this box go to WooCommerce's protected
 * `woocommerce_uploads` folder (edit-box.js sets the same upload type as
 * WooCommerce's own downloadable files), so only Stream can send them.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

defined( 'ABSPATH' ) || exit;

final class Edit_Box {

	private const NONCE  = 'stx_audio_products';
	private const HANDLE = 'stx-audio-products-edit';

	/** @var Module */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'add_meta_boxes_product', array( $this, 'add' ) );
		add_action( 'save_post_product', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add(): void {
		add_meta_box(
			'stx-audio-products',
			__( 'Studiare+ audio', 'studiare-extensions' ),
			array( $this, 'render' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * @param string $hook Admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( self::HANDLE, STUDIARE_EXT_URL . 'assets/modules/audio-products/css/edit-box.css', array(), STUDIARE_EXT_VERSION );
		wp_enqueue_script( self::HANDLE, STUDIARE_EXT_URL . 'assets/modules/audio-products/js/edit-box.js', array( 'media-editor' ), STUDIARE_EXT_VERSION, true );
		wp_localize_script(
			self::HANDLE,
			'stxAudioEdit',
			array(
				'protect' => class_exists( 'WC_Admin_Upload_Downloadable_Product' ),
				'i18n'    => array(
					'audioTitle'   => __( 'Choose the track file', 'studiare-extensions' ),
					'previewTitle' => __( 'Choose the preview file', 'studiare-extensions' ),
					'choose'       => __( 'Use this file', 'studiare-extensions' ),
					'remove'       => __( 'Remove this track?', 'studiare-extensions' ),
				),
			)
		);
	}

	/**
	 * @param \WP_Post $post Product being edited.
	 */
	public function render( $post ): void {
		$album       = Album::stored( (int) $post->ID );
		$has_lessons = Lessons::exist( (int) $post->ID );
		$blank       = array_merge( Album::track_defaults(), array( 'clip' => (int) $this->module->settings()['preview']['seconds'] ) );

		wp_nonce_field( self::NONCE, 'stx_audio_nonce' );
		require __DIR__ . '/views/edit-box.php';
	}

	/**
	 * @param int $post_id Product ID.
	 */
	public function save( $post_id ): void {
		if ( ! isset( $_POST['stx_audio_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['stx_audio_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Every value goes through Album::save(), which applies the schema in Album::fields().
		$input = isset( $_POST['stx_audio'] ) && is_array( $_POST['stx_audio'] ) ? wp_unslash( $_POST['stx_audio'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by Album::save().

		// Unchecked boxes are not posted at all.
		$input['download'] = ! empty( $input['download'] );
		$input['lessons']  = ! empty( $input['lessons'] );

		Album::save( (int) $post_id, $input );
	}
}
