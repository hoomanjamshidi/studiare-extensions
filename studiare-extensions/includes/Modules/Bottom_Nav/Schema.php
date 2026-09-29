<?php
/**
 * Settings schema and defaults for the bottom navigation module.
 *
 * `fields()` feeds Core\Sanitizer; `defaults()` mirrors its shape. Colors
 * default to an empty string, which means "follow the Studiare theme".
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Bottom_Nav;

use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;

final class Schema {

	public const MAX_ITEMS = 8;

	/**
	 * Color slots. Each slot is exposed to CSS as `--stx-bn-l-<slot>` (light)
	 * and `--stx-bn-d-<slot>` (dark); bottom-nav.css resolves them, with theme
	 * fallbacks, into the `--stx-bn-c-<slot>` tokens the components use.
	 */
	public const COLOR_SLOTS = array( 'bg', 'icon', 'label', 'active', 'active-bg', 'badge-bg', 'badge-text', 'border', 'fab-bg', 'fab-icon' );

	public static function defaults(): array {
		$no_colors = array_fill_keys( self::COLOR_SLOTS, '' );

		return array(
			'enabled'       => true,
			'style'         => Styles::DEFAULT_STYLE,
			'icon_pack'     => 'phosphor',
			'fa_weight'     => 'fal',
			'active_filled' => true,
			'icon_stroke'   => 1.75,
			'label_mode'    => 'always',
			'items'         => self::default_items(),
			'layout'        => array(
				'breakpoint' => 768,
				'height'     => 64,
				'icon_size'  => 24,
				'radius'     => 22,
				'offset'     => 12,
				'max_width'  => 560,
				'shadow'     => 'soft',
				'glass'      => true,
				'motion'     => 'spring',
			),
			'typography'    => array(
				'font_source' => 'theme_body',
				'font_family' => '',
				'font_size'   => 11,
				'font_weight' => '500',
			),
			'colors'        => $no_colors,
			'dark'          => array(
				'mode'   => 'auto',
				'colors' => $no_colors,
			),
			'behavior'      => array(
				'hide_on_scroll'         => false,
				'haptic'                 => true,
				'replace_theme_nav'      => true,
				'hide_theme_back_to_top' => true,
				'lift_fixed_elements'    => true,
				'z_index'                => 9990,
			),
			'visibility'    => array(
				'hide_on_checkout' => true,
				'hide_on_cart'     => false,
				'hide_on_course'   => false,
				'hide_on_product'  => false,
				'hide_for_ids'     => '',
			),
		);
	}

	public static function fields(): array {
		$color_fields = array_fill_keys( self::COLOR_SLOTS, array( 'type' => 'color' ) );

		return array(
			'enabled'       => array( 'type' => 'bool' ),
			'style'         => array(
				'type'    => 'enum',
				'options' => Styles::ids(),
			),
			'icon_pack'     => array(
				'type'    => 'enum',
				'options' => Icon_Library::pack_ids(),
			),
			'fa_weight'     => array(
				'type'    => 'enum',
				'options' => Icon_Library::FA_WEIGHTS,
			),
			'active_filled' => array( 'type' => 'bool' ),
			'icon_stroke'   => array(
				'type' => 'float',
				'min'  => 1,
				'max'  => 2.5,
			),
			'label_mode'    => array(
				'type'    => 'enum',
				'options' => array( 'always', 'active', 'never' ),
			),
			'items'         => array(
				'type'     => 'list',
				'max'      => self::MAX_ITEMS,
				'fields'   => self::item_fields(),
				'defaults' => self::item_defaults(),
			),
			'layout'        => array(
				'type'   => 'group',
				'fields' => array(
					'breakpoint' => array(
						'type' => 'int',
						'min'  => 360,
						'max'  => 1400,
					),
					'height'     => array(
						'type' => 'int',
						'min'  => 52,
						'max'  => 88,
					),
					'icon_size'  => array(
						'type' => 'int',
						'min'  => 18,
						'max'  => 32,
					),
					'radius'     => array(
						'type' => 'int',
						'min'  => 0,
						'max'  => 40,
					),
					'offset'     => array(
						'type' => 'int',
						'min'  => 0,
						'max'  => 32,
					),
					'max_width'  => array(
						'type' => 'int',
						'min'  => 320,
						'max'  => 1000,
					),
					'shadow'     => array(
						'type'    => 'enum',
						'options' => array( 'none', 'soft', 'medium', 'strong' ),
					),
					'glass'      => array( 'type' => 'bool' ),
					'motion'     => array(
						'type'    => 'enum',
						'options' => array( 'spring', 'smooth', 'none' ),
					),
				),
			),
			'typography'    => array(
				'type'   => 'group',
				'fields' => array(
					'font_source' => array(
						'type'    => 'enum',
						'options' => array( 'theme_body', 'theme_menu', 'inherit', 'custom' ),
					),
					'font_family' => array( 'type' => 'font_family' ),
					'font_size'   => array(
						'type' => 'int',
						'min'  => 9,
						'max'  => 15,
					),
					'font_weight' => array(
						'type'    => 'enum',
						'options' => array( '400', '500', '600', '700' ),
					),
				),
			),
			'colors'        => array(
				'type'   => 'group',
				'fields' => $color_fields,
			),
			'dark'          => array(
				'type'   => 'group',
				'fields' => array(
					'mode'   => array(
						'type'    => 'enum',
						'options' => array( 'auto', 'off' ),
					),
					'colors' => array(
						'type'   => 'group',
						'fields' => $color_fields,
					),
				),
			),
			'behavior'      => array(
				'type'   => 'group',
				'fields' => array(
					'hide_on_scroll'         => array( 'type' => 'bool' ),
					'haptic'                 => array( 'type' => 'bool' ),
					'replace_theme_nav'      => array( 'type' => 'bool' ),
					'hide_theme_back_to_top' => array( 'type' => 'bool' ),
					'lift_fixed_elements'    => array( 'type' => 'bool' ),
					'z_index'                => array(
						'type' => 'int',
						'min'  => 1,
						'max'  => 2147483000,
					),
				),
			),
			'visibility'    => array(
				'type'   => 'group',
				'fields' => array(
					'hide_on_checkout' => array( 'type' => 'bool' ),
					'hide_on_cart'     => array( 'type' => 'bool' ),
					'hide_on_course'   => array( 'type' => 'bool' ),
					'hide_on_product'  => array( 'type' => 'bool' ),
					'hide_for_ids'     => array( 'type' => 'id_list' ),
				),
			),
		);
	}

	/** Defaults for a single navigation item; type-specific keys are ignored by other types. */
	public static function item_defaults(): array {
		return array(
			'id'                => '',
			'type'              => 'link',
			'enabled'           => true,
			'label'             => '',
			'icon_source'       => 'pack',
			'icon'              => 'home',
			'icon_fa'           => '',
			'icon_image'        => '',
			'icon_svg'          => '',
			'url'               => '',
			'new_tab'           => false,
			'visibility'        => 'all',
			'featured'          => false,
			'badge'             => '',
			'sheet_title'       => '',
			'search_post_types' => array(),
			'search_live'       => true,
			'cart_action'       => 'auto',
			'hide_empty_badge'  => true,
			'guest_label'       => '',
			'guest_action'      => 'page',
			'show_avatar'       => false,
			'menu_source'       => 'theme',
			'menu_id'           => 0,
			'content_source'    => 'shortcode',
			'content_id'        => 0,
			'content_html'      => '',
			'selector'          => '',
		);
	}

	public static function item_fields(): array {
		return array(
			'id'                => array( 'type' => 'key' ),
			'type'              => array(
				'type'    => 'enum',
				'options' => Item_Types::ids(),
			),
			'enabled'           => array( 'type' => 'bool' ),
			'label'             => array(
				'type'       => 'text',
				'max_length' => 40,
			),
			'icon_source'       => array(
				'type'    => 'enum',
				'options' => array( 'pack', 'fontawesome', 'image', 'svg' ),
			),
			'icon'              => array(
				'type'    => 'enum',
				'options' => Icon_Library::icon_keys(),
			),
			'icon_fa'           => array( 'type' => 'css_class' ),
			'icon_image'        => array( 'type' => 'url' ),
			'icon_svg'          => array( 'type' => 'svg' ),
			'url'               => array( 'type' => 'url' ),
			'new_tab'           => array( 'type' => 'bool' ),
			'visibility'        => array(
				'type'    => 'enum',
				'options' => array( 'all', 'guests', 'members' ),
			),
			'featured'          => array( 'type' => 'bool' ),
			'badge'             => array(
				'type'       => 'text',
				'max_length' => 12,
			),
			'sheet_title'       => array(
				'type'       => 'text',
				'max_length' => 60,
			),
			'search_post_types' => array( 'type' => 'key_list' ),
			'search_live'       => array( 'type' => 'bool' ),
			'cart_action'       => array(
				'type'    => 'enum',
				'options' => array( 'auto', 'sheet', 'page', 'checkout' ),
			),
			'hide_empty_badge'  => array( 'type' => 'bool' ),
			'guest_label'       => array(
				'type'       => 'text',
				'max_length' => 40,
			),
			'guest_action'      => array(
				'type'    => 'enum',
				'options' => array( 'page', 'modal' ),
			),
			'show_avatar'       => array( 'type' => 'bool' ),
			'menu_source'       => array(
				'type'    => 'enum',
				'options' => array( 'theme', 'wp_menu' ),
			),
			'menu_id'           => array(
				'type' => 'int',
				'min'  => 0,
			),
			'content_source'    => array(
				'type'    => 'enum',
				'options' => array( 'shortcode', 'elementor' ),
			),
			'content_id'        => array(
				'type' => 'int',
				'min'  => 0,
			),
			'content_html'      => array( 'type' => 'html' ),
			'selector'          => array( 'type' => 'css_selector' ),
		);
	}

	/**
	 * Builds a new item of the given type with its default label and icon.
	 *
	 * @param string $type      Item type id.
	 * @param array  $overrides Extra values.
	 */
	public static function make_item( string $type, array $overrides = array() ): array {
		$definition = Item_Types::get( $type ) ?? Item_Types::get( 'link' );

		return array_merge(
			self::item_defaults(),
			array(
				'id'    => self::new_item_id(),
				'type'  => $type,
				'label' => $definition['default_label'],
				'icon'  => $definition['icon'],
			),
			$overrides
		);
	}

	/** Random, stable item id used for DOM ids and admin list keys. */
	public static function new_item_id(): string {
		return 'i' . strtolower( wp_generate_password( 8, false, false ) );
	}

	/**
	 * Sensible starter set for an LMS: home, courses, search, cart and account.
	 * Falls back to non-shop items when WooCommerce is not active.
	 * Default items get fixed ids so markup is stable before the first save.
	 */
	private static function default_items(): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array(
				self::make_item( 'home', array( 'id' => 'home' ) ),
				self::make_item( 'search', array( 'id' => 'search' ) ),
				self::make_item( 'menu', array( 'id' => 'menu' ) ),
				self::make_item( 'account', array( 'id' => 'account' ) ),
			);
		}

		return array(
			self::make_item( 'home', array( 'id' => 'home' ) ),
			self::make_item(
				'link',
				array(
					'id'    => 'courses',
					'label' => __( 'Courses', 'studiare-extensions' ),
					'icon'  => 'graduation',
					'url'   => (string) wc_get_page_permalink( 'shop' ),
				)
			),
			self::make_item( 'search', array( 'id' => 'search' ) ),
			self::make_item( 'cart', array( 'id' => 'cart' ) ),
			self::make_item( 'account', array( 'id' => 'account' ) ),
		);
	}
}
