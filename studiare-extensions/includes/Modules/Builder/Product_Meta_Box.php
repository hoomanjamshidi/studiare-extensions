<?php
/**
 * Product edit screen box: per-product template choice and the "what you
 * will learn" list shown by the Highlights widget.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Product_Meta_Box {

	public const META_HIGHLIGHTS = '_stx_highlights';

	private const NONCE = 'stx_builder_product';

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
	}

	public function add(): void {
		add_meta_box(
			'stx-builder-product',
			__( 'Studiare+ page template', 'studiare-extensions' ),
			array( $this, 'render' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * @param \WP_Post $post Product.
	 */
	public function render( $post ): void {
		$current    = (string) get_post_meta( $post->ID, Resolver::META_OVERRIDE, true );
		$highlights = (string) get_post_meta( $post->ID, self::META_HIGHLIGHTS, true );
		$labels     = Module::type_labels();
		$templates  = get_posts(
			array(
				'post_type'      => Template_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- admin only.
					array(
						'key'     => Template_Post_Type::META_TYPE,
						'value'   => Schema::SINGLE_TYPES,
						'compare' => 'IN',
					),
				),
			)
		);

		$automatic = $this->module->resolver()->single_for_product( (int) $post->ID );
		$auto_name = $automatic ? get_the_title( $automatic ) : __( 'Theme layout', 'studiare-extensions' );

		wp_nonce_field( self::NONCE, 'stx_builder_nonce' );
		?>
		<p>
			<label for="stx-template-choice"><strong><?php esc_html_e( 'Layout of this page', 'studiare-extensions' ); ?></strong></label>
			<select id="stx-template-choice" name="stx_template" style="width:100%;margin-top:6px">
				<option value="" <?php selected( $current, '' ); ?>>
					<?php
					/* translators: %s: template picked by the category rules. */
					echo esc_html( sprintf( __( 'Automatic (%s)', 'studiare-extensions' ), $auto_name ) );
					?>
				</option>
				<option value="theme" <?php selected( $current, 'theme' ); ?>><?php esc_html_e( 'Theme layout', 'studiare-extensions' ); ?></option>
				<?php foreach ( $templates as $template ) : ?>
					<?php $type = Template_Post_Type::type_of( (int) $template->ID ); ?>
					<option value="<?php echo esc_attr( (string) $template->ID ); ?>" <?php selected( $current, (string) $template->ID ); ?>>
						<?php echo esc_html( get_the_title( $template ) . ' — ' . ( $labels[ $type ] ?? '' ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php if ( ! $this->module->is_enabled() ) : ?>
			<p class="description"><?php esc_html_e( 'Page templates are switched off, so the theme layout is used until you enable them.', 'studiare-extensions' ); ?></p>
		<?php endif; ?>
		<p>
			<label for="stx-highlights"><strong><?php esc_html_e( 'What you will learn', 'studiare-extensions' ); ?></strong></label>
			<textarea id="stx-highlights" name="stx_highlights" rows="5" style="width:100%;margin-top:6px" placeholder="<?php esc_attr_e( 'One item per line', 'studiare-extensions' ); ?>"><?php echo esc_textarea( $highlights ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Shown by the "Highlights" widget of the page templates. One item per line.', 'studiare-extensions' ); ?></span>
		</p>
		<?php
	}

	/**
	 * @param int $post_id Product ID.
	 */
	public function save( $post_id ): void {
		if ( ! isset( $_POST['stx_builder_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['stx_builder_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$choice = isset( $_POST['stx_template'] ) ? sanitize_key( wp_unslash( $_POST['stx_template'] ) ) : '';
		if ( '' === $choice || ( 'theme' !== $choice && ! in_array( Template_Post_Type::type_of( (int) $choice ), Schema::SINGLE_TYPES, true ) ) ) {
			delete_post_meta( $post_id, Resolver::META_OVERRIDE );
		} else {
			update_post_meta( $post_id, Resolver::META_OVERRIDE, $choice );
		}

		$highlights = isset( $_POST['stx_highlights'] ) ? sanitize_textarea_field( wp_unslash( $_POST['stx_highlights'] ) ) : '';
		if ( '' === trim( $highlights ) ) {
			delete_post_meta( $post_id, self::META_HIGHLIGHTS );
		} else {
			update_post_meta( $post_id, self::META_HIGHLIGHTS, $highlights );
		}
	}
}
