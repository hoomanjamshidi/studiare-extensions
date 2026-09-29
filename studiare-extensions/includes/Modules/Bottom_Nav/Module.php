<?php
/**
 * Mobile bottom navigation module.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Bottom_Nav;

use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Module as Base_Module;
use StudiareExt\Core\Sanitizer;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Module extends Base_Module {

	public function id(): string {
		return 'bottom_nav';
	}

	public function title(): string {
		return __( 'Mobile bottom navigation', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'An app-like tab bar for phones with five styles, live cart count and fully custom buttons.', 'studiare-extensions' );
	}

	public function icon(): string {
		return 'grid';
	}

	public function defaults(): array {
		return Schema::defaults();
	}

	public function sanitize( array $input ): array {
		$clean          = Sanitizer::apply( Schema::fields(), $input, Schema::defaults() );
		$clean['items'] = $this->sanitize_items( $clean['items'] );

		return $clean;
	}

	protected function boot(): void {
		( new Frontend( $this ) )->register();
		( new Live_Search( $this ) )->register();
	}

	/**
	 * Fills keys added in newer plugin versions into previously saved items.
	 *
	 * @param array $settings Settings merged with defaults.
	 */
	protected function normalize( array $settings ): array {
		$item_defaults = Schema::item_defaults();

		$settings['items'] = array_map(
			static function ( $item ) use ( $item_defaults ) {
				$item = is_array( $item ) ? self::upgrade_item( $item ) : array();
				return array_merge( $item_defaults, array_intersect_key( $item, $item_defaults ) );
			},
			is_array( $settings['items'] ) ? $settings['items'] : array()
		);

		return $settings;
	}

	/**
	 * Converts item keys renamed in newer versions. 1.1 replaced the single
	 * `search_post_type` ('any' or one type) with the `search_post_types` list.
	 *
	 * @param array $item Saved item.
	 */
	private static function upgrade_item( array $item ): array {
		if ( isset( $item['search_post_type'] ) && ! isset( $item['search_post_types'] ) ) {
			$item['search_post_types'] = 'any' === $item['search_post_type'] ? array() : array( $item['search_post_type'] );
		}

		return $item;
	}

	public function render_admin(): void {
		$module = $this;
		require __DIR__ . '/views/admin.php';
	}

	public function enqueue_admin_assets(): void {
		wp_enqueue_media();

		// The preview renders with the real front-end stylesheet.
		wp_enqueue_style( 'stx-bottom-nav', STUDIARE_EXT_URL . 'assets/modules/bottom-nav/css/bottom-nav.css', array(), STUDIARE_EXT_VERSION );

		if ( Theme_Bridge::is_active() ) {
			// Font Awesome from the theme, for the "Font Awesome" icon pack.
			wp_enqueue_style( 'stx-theme-fontawesome', get_template_directory_uri() . '/assets/css/fonawesomeall.min.css', array(), STUDIARE_EXT_VERSION );
		}

		wp_enqueue_script(
			'stx-bottom-nav-admin',
			STUDIARE_EXT_URL . 'assets/modules/bottom-nav/js/bottom-nav-admin.js',
			array( 'stx-admin', 'jquery-ui-sortable' ),
			STUDIARE_EXT_VERSION,
			true
		);
	}

	public function admin_script_data(): array {
		$settings = $this->settings();

		return array(
			'styles'       => Styles::all(),
			'itemTypes'    => $this->available_item_types(),
			'itemDefaults' => Schema::item_defaults(),
			'maxItems'     => Schema::MAX_ITEMS,
			'colorSlots'   => Schema::COLOR_SLOTS,
			'iconPack'     => array(
				// Preloaded so the first preview paint needs no request.
				'id'    => $settings['icon_pack'],
				'icons' => Icon_Library::FONT_AWESOME === $settings['icon_pack'] ? array() : Icon_Library::pack( $settings['icon_pack'] ),
			),
			'iconPackUrl'  => STUDIARE_EXT_URL . 'assets/icons/packs/',
			'catalog'      => Icon_Library::catalog(),
			'theme'        => array(
				'active'   => Theme_Bridge::is_active(),
				'palette'  => Theme_Bridge::palette(),
				'fonts'    => Theme_Bridge::fonts(),
				'darkMode' => Theme_Bridge::dark_mode_available(),
			),
			'hasWoo'       => class_exists( 'WooCommerce' ),
			'avatarUrl'    => (string) get_avatar_url( get_current_user_id(), array( 'size' => 64 ) ),
			'menus'        => $this->menu_choices(),
			'templates'    => $this->elementor_template_choices(),
			'postTypes'    => Search_Scope::choices(),
			'i18n'         => $this->admin_strings(),
		);
	}

	/** Strings used by bottom-nav-admin.js. */
	private function admin_strings(): array {
		return array(
			'needsWoo'      => __( 'Requires WooCommerce', 'studiare-extensions' ),
			'needsUrl'      => __( 'Add a URL', 'studiare-extensions' ),
			'needsMenu'     => __( 'Choose a menu', 'studiare-extensions' ),
			'needsContent'  => __( 'Add content', 'studiare-extensions' ),
			'needsDarkMode' => __( 'Dark mode is off in Studiare', 'studiare-extensions' ),
			'needsSelector' => __( 'Add a selector or URL', 'studiare-extensions' ),
			'hiddenOnSite'  => __( 'This button stays hidden on the site until this is fixed.', 'studiare-extensions' ),
			'guestsOnly'    => __( 'Guests only', 'studiare-extensions' ),
			'membersOnly'   => __( 'Members only', 'studiare-extensions' ),
			'login'         => __( 'Login', 'studiare-extensions' ),
			'deleteTitle'   => __( 'Delete this button?', 'studiare-extensions' ),
			/* translators: %s: button label. */
			'deleteMessage' => __( '"%s" will be removed from the bar.', 'studiare-extensions' ),
			'delete'        => __( 'Delete', 'studiare-extensions' ),
			/* translators: %d: maximum number of buttons. */
			'maxReached'    => sprintf( __( 'You can add up to %d buttons.', 'studiare-extensions' ), Schema::MAX_ITEMS ),
			'chooseImage'   => __( 'Choose an icon image', 'studiare-extensions' ),
			'useImage'      => __( 'Use this image', 'studiare-extensions' ),
			'noIcons'       => __( 'No icons match your search.', 'studiare-extensions' ),
			'noneOption'    => __( '— Select —', 'studiare-extensions' ),
		);
	}

	/**
	 * Guarantees unique ids and a single featured item.
	 *
	 * @param array $items Sanitized items.
	 */
	private function sanitize_items( array $items ): array {
		$seen_ids     = array();
		$has_featured = false;

		foreach ( $items as &$item ) {
			if ( '' === $item['id'] || isset( $seen_ids[ $item['id'] ] ) ) {
				$item['id'] = Schema::new_item_id();
			}
			$seen_ids[ $item['id'] ] = true;

			if ( $item['featured'] ) {
				$item['featured'] = ! $has_featured;
				$has_featured     = true;
			}
		}
		unset( $item );

		return $items;
	}

	/** Item types whose dependencies are met, for the admin "add button" menu. */
	private function available_item_types(): array {
		$types = array();
		foreach ( Item_Types::all() as $id => $type ) {
			$type['available'] = Item_Types::is_available( $id );
			$types[ $id ]      = $type;
		}

		return $types;
	}

	/** @return array<int, array{id:int, name:string}> */
	private function menu_choices(): array {
		return array_map(
			static function ( $menu ) {
				return array(
					'id'   => (int) $menu->term_id,
					'name' => $menu->name,
				);
			},
			wp_get_nav_menus()
		);
	}

	/**
	 * Elementor library templates plus pages, for the "content sheet" type.
	 *
	 * @return array<int, array{id:int, name:string}>
	 */
	private function elementor_template_choices(): array {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => array( 'elementor_library', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_key'       => '_elementor_edit_mode', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin only, bounded.
				'meta_value'     => 'builder', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return array_map(
			static function ( $post ) {
				return array(
					'id'   => (int) $post->ID,
					'name' => get_the_title( $post ) . ( 'page' === $post->post_type ? ' — ' . __( 'page', 'studiare-extensions' ) : '' ),
				);
			},
			$posts
		);
	}
}
