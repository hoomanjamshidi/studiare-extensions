<?php
/**
 * Post edit screen box: the post's podcast (audio file, title, speaker,
 * cover, duration) and a switch that hides the PDF button on that post.
 *
 * A classic meta box, so it works in the block editor and the classic
 * editor alike.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Article_Tools;

defined( 'ABSPATH' ) || exit;

final class Edit_Box {

	/** Set ('1') on posts whose author switched the PDF button off. */
	public const META_PDF_OFF = '_stx_pdf_off';

	private const NONCE  = 'stx_article_tools';
	private const HANDLE = 'stx-article-tools-edit';

	/** @var Module */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		if ( ! $this->has_podcast() && ! $this->has_pdf() ) {
			return;
		}

		add_action( 'add_meta_boxes_post', array( $this, 'add' ) );
		add_action( 'save_post_post', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add(): void {
		add_meta_box(
			'stx-article-tools',
			__( 'Studiare+ article tools', 'studiare-extensions' ),
			array( $this, 'render' ),
			'post',
			'side',
			'default'
		);
	}

	/**
	 * @param string $hook Admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( self::HANDLE, STUDIARE_EXT_URL . 'assets/modules/article-tools/css/edit-box.css', array(), STUDIARE_EXT_VERSION );

		if ( ! $this->has_podcast() ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( self::HANDLE, STUDIARE_EXT_URL . 'assets/modules/article-tools/js/edit-box.js', array( 'media-editor' ), STUDIARE_EXT_VERSION, true );
		wp_localize_script(
			self::HANDLE,
			'stxArticleEdit',
			array(
				'i18n' => array(
					'audioTitle'  => __( 'Choose the podcast audio', 'studiare-extensions' ),
					'coverTitle'  => __( 'Choose the podcast cover', 'studiare-extensions' ),
					'choose'      => __( 'Use this file', 'studiare-extensions' ),
					'chooseImage' => __( 'Use this picture', 'studiare-extensions' ),
				),
			)
		);
	}

	/**
	 * @param \WP_Post $post Post being edited.
	 */
	public function render( $post ): void {
		$podcast     = Podcast::stored( (int) $post->ID );
		$audio_url   = Podcast::audio_url( $podcast );
		$pdf_off     = '1' === get_post_meta( $post->ID, self::META_PDF_OFF, true );
		$has_podcast = $this->has_podcast();
		$has_pdf     = $this->has_pdf();

		wp_nonce_field( self::NONCE, 'stx_article_tools_nonce' );
		require __DIR__ . '/views/edit-box.php';
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ): void {
		if ( ! isset( $_POST['stx_article_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['stx_article_tools_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( $this->has_podcast() ) {
			// Each field goes through Podcast::save(), which applies the schema in Podcast::fields().
			$input = isset( $_POST['stx_podcast'] ) && is_array( $_POST['stx_podcast'] ) ? wp_unslash( $_POST['stx_podcast'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by Podcast::save().
			Podcast::save( (int) $post_id, $input );
		}

		if ( $this->has_pdf() ) {
			if ( ! empty( $_POST['stx_pdf_off'] ) ) {
				update_post_meta( $post_id, self::META_PDF_OFF, '1' );
			} else {
				delete_post_meta( $post_id, self::META_PDF_OFF );
			}
		}
	}

	private function has_podcast(): bool {
		return ! empty( $this->module->settings()['podcast']['enabled'] );
	}

	private function has_pdf(): bool {
		return ! empty( $this->module->settings()['pdf']['enabled'] );
	}
}
