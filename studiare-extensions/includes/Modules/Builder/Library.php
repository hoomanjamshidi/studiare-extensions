<?php
/**
 * Template library: installs the ready-made designs as Elementor documents
 * and lists, duplicates, restores and deletes templates.
 *
 * Presets are installed as regular `stx_template` posts, so from then on they
 * are ordinary Elementor documents the site owner edits freely; "restore"
 * rewrites a preset from its original definition.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Modules\Builder\Presets\Catalog;

defined( 'ABSPATH' ) || exit;

final class Library {

	/** Option mapping preset keys to the posts they were installed as. */
	public const OPTION = 'studiare_ext_builder_presets';

	/** Plugin version whose preset designs were last applied. */
	private const VERSION_OPTION = 'studiare_ext_builder_presets_version';

	/** Hash of the preset data at install time, to detect later edits. */
	private const META_HASH = '_stx_preset_hash';

	public static function elementor_active(): bool {
		return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' );
	}

	/** @return array{active: bool, container: bool, version: string, installUrl: string} */
	public static function elementor_status(): array {
		$active    = self::elementor_active();
		$container = false;

		if ( $active && isset( \Elementor\Plugin::$instance->experiments ) ) {
			$container = (bool) \Elementor\Plugin::$instance->experiments->is_feature_active( 'container' );
		}

		return array(
			'active'     => $active,
			'container'  => $container,
			'version'    => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '',
			'installUrl' => admin_url( 'plugin-install.php?s=elementor&tab=search&type=term' ),
		);
	}

	/** Installs every preset that is not installed yet (or was deleted). */
	public static function install_missing(): int {
		if ( ! self::elementor_active() ) {
			return 0;
		}

		$installed = self::installed_map();
		$count     = 0;

		foreach ( Catalog::keys() as $key ) {
			$post_id = $installed[ $key ] ?? 0;
			if ( $post_id && Template_Post_Type::POST_TYPE === get_post_type( $post_id ) && 'trash' !== get_post_status( $post_id ) ) {
				continue;
			}

			if ( self::install( $key ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * After a plugin update: rewrites the ready-made templates nobody has
	 * edited with their improved designs, and installs designs added in this
	 * version. Edited templates are left alone ("Restore original" updates
	 * them on request), and deleted ones are not brought back.
	 */
	public static function maybe_upgrade(): void {
		// admin_init also runs for visitors' admin-ajax requests; wait for an administrator's page load.
		if ( wp_doing_ajax() || ! Module::user_can_manage() || ! self::elementor_active() || STUDIARE_EXT_VERSION === get_option( self::VERSION_OPTION ) ) {
			return;
		}

		$installed = get_option( self::OPTION, false );

		// A fresh site installs everything on the first visit to the settings screen instead.
		if ( is_array( $installed ) ) {
			foreach ( Catalog::keys() as $key ) {
				$post_id = (int) ( $installed[ $key ] ?? 0 );

				if ( ! $post_id ) {
					self::install( $key );
				} elseif ( Template_Post_Type::POST_TYPE === get_post_type( $post_id ) && 'trash' !== get_post_status( $post_id ) && ! self::is_modified( $post_id ) ) {
					self::install( $key, $post_id );
				}
			}
		}

		update_option( self::VERSION_OPTION, STUDIARE_EXT_VERSION, false );
	}

	/**
	 * Creates (or, with `$post_id`, overwrites) a template from a preset.
	 *
	 * @param string $key     Preset key.
	 * @param int    $post_id Existing template to overwrite.
	 * @param string $title   Title for a new template (defaults to the preset label).
	 * @return int Template ID, 0 on failure.
	 */
	public static function install( string $key, int $post_id = 0, string $title = '' ): int {
		$preset = Catalog::get( $key );
		if ( ! $preset ) {
			return 0;
		}

		$elements = Catalog::build( $key );
		$is_new   = 0 === $post_id;

		if ( $is_new ) {
			$post_id = self::insert_post( $preset['type'], '' !== $title ? $title : $preset['label'] );
			if ( ! $post_id ) {
				return 0;
			}
		}

		update_post_meta( $post_id, Template_Post_Type::META_PRESET, $key );
		self::save_elements( $post_id, $elements );

		// Only the first install of a preset is tracked as "the" preset post.
		$installed = self::installed_map();
		if ( $is_new && ( empty( $installed[ $key ] ) || 'trash' === get_post_status( (int) $installed[ $key ] ) || ! get_post( (int) $installed[ $key ] ) ) ) {
			$installed[ $key ] = $post_id;
			update_option( self::OPTION, $installed, false );
		}

		return $post_id;
	}

	/**
	 * Empty template of a kind.
	 *
	 * @param string $type  Template kind.
	 * @param string $title Title.
	 */
	public static function create_blank( string $type, string $title ): int {
		if ( ! in_array( $type, Schema::TYPES, true ) ) {
			return 0;
		}

		$post_id = self::insert_post( $type, $title );
		if ( $post_id ) {
			self::save_elements( $post_id, array() );
		}

		return $post_id;
	}

	/**
	 * Copies a template (Elementor data and page settings included).
	 *
	 * @param int $source_id Template to copy.
	 */
	public static function duplicate( int $source_id ): int {
		$type = Template_Post_Type::type_of( $source_id );
		if ( ! $type ) {
			return 0;
		}

		/* translators: %s: original template name. */
		$post_id = self::insert_post( $type, sprintf( __( '%s (copy)', 'studiare-extensions' ), get_the_title( $source_id ) ) );
		if ( ! $post_id ) {
			return 0;
		}

		$preset = (string) get_post_meta( $source_id, Template_Post_Type::META_PRESET, true );
		if ( '' !== $preset ) {
			update_post_meta( $post_id, Template_Post_Type::META_PRESET, $preset );
		}

		self::copy_content( $source_id, $post_id );

		return $post_id;
	}

	/**
	 * Copies the Elementor content and page settings of one document into
	 * another, with new element IDs.
	 *
	 * @param int $source_id Document to copy from.
	 * @param int $target_id Document to copy into (a template or a page).
	 */
	public static function copy_content( int $source_id, int $target_id ): void {
		$data = json_decode( (string) get_post_meta( $source_id, '_elementor_data', true ), true );
		self::save_elements( $target_id, is_array( $data ) ? self::fresh_ids( $data ) : array() );

		$page_settings = get_post_meta( $source_id, '_elementor_page_settings', true );
		if ( is_array( $page_settings ) ) {
			update_post_meta( $target_id, '_elementor_page_settings', $page_settings );
		}
	}

	/**
	 * Rewrites a preset template with its original design.
	 *
	 * @param int $post_id Template ID.
	 */
	public static function restore( int $post_id ): bool {
		$key = (string) get_post_meta( $post_id, Template_Post_Type::META_PRESET, true );

		return '' !== $key && Catalog::get( $key ) && self::install( $key, $post_id ) === $post_id;
	}

	/**
	 * Every template, for the admin UI.
	 *
	 * @param Module $module Module (for preview URLs).
	 */
	public static function all( Module $module ): array {
		$posts = get_posts(
			array(
				'post_type'      => Template_Post_Type::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- a site has a few dozen templates at most; this is a safety cap.
				'orderby'        => 'date',
				'order'          => 'ASC',
			)
		);

		$catalog = Catalog::all();
		$list    = array();

		foreach ( $posts as $post ) {
			$id     = (int) $post->ID;
			$type   = Template_Post_Type::type_of( $id );
			$preset = (string) get_post_meta( $id, Template_Post_Type::META_PRESET, true );

			if ( ! $type ) {
				continue;
			}

			$list[] = array(
				'id'         => $id,
				'type'       => $type,
				'title'      => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
				'preset'     => isset( $catalog[ $preset ] ) ? $preset : '',
				'modified'   => self::is_modified( $id ),
				'status'     => $post->post_status,
				'editUrl'    => Template_Post_Type::edit_url( $id ),
				'previewUrl' => $module->resolver()->preview_url( $type, (string) $id ),
				'thumb'      => Thumbs::svg( isset( $catalog[ $preset ] ) ? $preset : $type ),
			);
		}

		return $list;
	}

	/** Preset metadata for the admin UI. */
	public static function catalog_for_js(): array {
		$out = array();

		foreach ( Catalog::all() as $key => $preset ) {
			$out[] = array(
				'key'         => $key,
				'type'        => $preset['type'],
				'label'       => $preset['label'],
				'description' => $preset['description'],
				'thumb'       => Thumbs::svg( $key ),
			);
		}

		return $out;
	}

	/**
	 * Adds the brand tokens to Elementor's global colours (Site Settings),
	 * so they appear in every colour picker. Existing Studiare+ entries are
	 * updated in place; other global colours are untouched.
	 *
	 * @param array<string, string> $colors Token → colour.
	 */
	public static function sync_kit_colors( array $colors ): bool {
		if ( ! self::elementor_active() || ! isset( \Elementor\Plugin::$instance->kits_manager ) ) {
			return false;
		}

		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( ! $kit || ! $kit->get_id() ) {
			return false;
		}

		$labels = array(
			'accent' => __( 'Studiare+ accent', 'studiare-extensions' ),
			'ink'    => __( 'Studiare+ headings', 'studiare-extensions' ),
			'text'   => __( 'Studiare+ text', 'studiare-extensions' ),
			'muted'  => __( 'Studiare+ muted', 'studiare-extensions' ),
			'bg'     => __( 'Studiare+ background', 'studiare-extensions' ),
			'line'   => __( 'Studiare+ border', 'studiare-extensions' ),
			'dark'   => __( 'Studiare+ dark', 'studiare-extensions' ),
		);

		$custom = $kit->get_settings( 'custom_colors' );
		$custom = is_array( $custom ) ? $custom : array();
		$custom = array_values(
			array_filter(
				$custom,
				static function ( $item ) {
					return ! ( is_array( $item ) && isset( $item['_id'] ) && 0 === strpos( (string) $item['_id'], 'stx' ) );
				}
			)
		);

		foreach ( $labels as $token => $label ) {
			if ( empty( $colors[ $token ] ) ) {
				continue;
			}
			$custom[] = array(
				'_id'   => 'stx' . str_replace( '_', '', $token ),
				'title' => $label,
				'color' => $colors[ $token ],
			);
		}

		$kit->update_settings( array( 'custom_colors' => $custom ) );

		if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return true;
	}

	/** Switches on Elementor's Flexbox Container feature (needed by the designs). */
	public static function activate_container(): bool {
		if ( ! self::elementor_active() ) {
			return false;
		}

		update_option( 'elementor_experiment-container', 'active' );

		return true;
	}

	/** @return array<string, int> */
	private static function installed_map(): array {
		$map = get_option( self::OPTION, array() );

		return is_array( $map ) ? array_map( 'intval', $map ) : array();
	}

	/**
	 * @param string $type  Template kind.
	 * @param string $title Title.
	 */
	private static function insert_post( string $type, string $title ): int {
		$meta = array(
			Template_Post_Type::META_TYPE => $type,
			// Blank canvas while editing: no theme header/footer around the template.
			'_wp_page_template'           => 'elementor_canvas',
		);

		if ( in_array( $type, Schema::PAGE_TYPES, true ) ) {
			// A page design is a whole page: edit and preview it the way the pages made from it look.
			$meta = array_merge( $meta, Design_Pages::page_meta() );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => Template_Post_Type::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $title,
				'meta_input'  => $meta,
			),
			true
		);

		return is_wp_error( $post_id ) ? 0 : (int) $post_id;
	}

	/**
	 * Saves Elementor data through Elementor's document API when possible, so
	 * the data is normalised exactly like an editor save. Falls back to raw
	 * meta (e.g. WP-CLI without a user) and clears the generated CSS.
	 *
	 * @param int   $post_id  Template ID.
	 * @param array $elements Elementor elements.
	 */
	private static function save_elements( int $post_id, array $elements ): void {
		$saved = false;

		if ( self::elementor_active() && isset( \Elementor\Plugin::$instance->documents ) ) {
			$document = \Elementor\Plugin::$instance->documents->get( $post_id, false );
			if ( $document ) {
				$document->set_is_built_with_elementor( true );
				$saved = (bool) $document->save( array( 'elements' => $elements ) );
			}
		}

		if ( ! $saved ) {
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_template_type', 'wp-post' );
			update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.16.0' );
			update_metadata( 'post', $post_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );

			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				\Elementor\Core\Files\CSS\Post::create( $post_id )->delete();
			}
		}

		update_post_meta( $post_id, self::META_HASH, self::data_hash( $post_id ) );
	}

	/**
	 * @param int $post_id Template ID.
	 */
	private static function is_modified( int $post_id ): bool {
		$stored = (string) get_post_meta( $post_id, self::META_HASH, true );

		return '' !== $stored && self::data_hash( $post_id ) !== $stored;
	}

	/**
	 * @param int $post_id Template ID.
	 */
	private static function data_hash( int $post_id ): string {
		return md5( (string) get_post_meta( $post_id, '_elementor_data', true ) );
	}

	/**
	 * New element IDs for copied data, so copies never share IDs (and CSS).
	 *
	 * @param array $elements Elementor elements.
	 */
	private static function fresh_ids( array $elements ): array {
		foreach ( $elements as &$element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$element['id'] = Presets\El::id();
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$element['elements'] = self::fresh_ids( $element['elements'] );
			}
		}
		unset( $element );

		return $elements;
	}
}
