<?php
/**
 * Registry of button types (what a navigation item does when tapped).
 *
 * The metadata drives the admin editor: `fields` lists the type-specific
 * item settings the editor shows. Runtime behaviour lives in Item_Resolver
 * (PHP) and bottom-nav.js (actions).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Bottom_Nav;

defined( 'ABSPATH' ) || exit;

final class Item_Types {

	/**
	 * @return array<string, array{label:string, description:string, default_label:string, icon:string, fields:string[], requires:string}>
	 */
	public static function all(): array {
		return array(
			'home'        => array(
				'label'         => __( 'Home', 'studiare-extensions' ),
				'description'   => __( 'Links to the site front page.', 'studiare-extensions' ),
				'default_label' => __( 'Home', 'studiare-extensions' ),
				'icon'          => 'home',
				'fields'        => array(),
				'requires'      => '',
			),
			'link'        => array(
				'label'         => __( 'Custom link', 'studiare-extensions' ),
				'description'   => __( 'Any page, category, phone number or external URL.', 'studiare-extensions' ),
				'default_label' => __( 'Link', 'studiare-extensions' ),
				'icon'          => 'graduation',
				'fields'        => array( 'url', 'new_tab' ),
				'requires'      => '',
			),
			'search'      => array(
				'label'         => __( 'Search', 'studiare-extensions' ),
				'description'   => __( 'Opens a search sheet.', 'studiare-extensions' ),
				'default_label' => __( 'Search', 'studiare-extensions' ),
				'icon'          => 'search',
				'fields'        => array( 'search_post_types', 'search_live', 'sheet_title' ),
				'requires'      => '',
			),
			'cart'        => array(
				'label'         => __( 'Cart', 'studiare-extensions' ),
				'description'   => __( 'Live item count; opens the mini cart or the cart page.', 'studiare-extensions' ),
				'default_label' => __( 'Cart', 'studiare-extensions' ),
				'icon'          => 'bag',
				'fields'        => array( 'cart_action', 'hide_empty_badge', 'sheet_title' ),
				'requires'      => 'woocommerce',
			),
			'account'     => array(
				'label'         => __( 'Account', 'studiare-extensions' ),
				'description'   => __( 'User dashboard, or login for guests.', 'studiare-extensions' ),
				'default_label' => __( 'Account', 'studiare-extensions' ),
				'icon'          => 'user',
				'fields'        => array( 'guest_label', 'guest_action', 'show_avatar', 'url' ),
				'requires'      => '',
			),
			'menu'        => array(
				'label'         => __( 'Menu', 'studiare-extensions' ),
				'description'   => __( 'Opens the theme mobile menu or a WordPress menu.', 'studiare-extensions' ),
				'default_label' => __( 'Menu', 'studiare-extensions' ),
				'icon'          => 'menu',
				'fields'        => array( 'menu_source', 'menu_id', 'sheet_title' ),
				'requires'      => '',
			),
			'content'     => array(
				'label'         => __( 'Content sheet', 'studiare-extensions' ),
				'description'   => __( 'Shows an Elementor template or shortcode in a sheet.', 'studiare-extensions' ),
				'default_label' => __( 'More', 'studiare-extensions' ),
				'icon'          => 'more',
				'fields'        => array( 'content_source', 'content_id', 'content_html', 'sheet_title' ),
				'requires'      => '',
			),
			'back_to_top' => array(
				'label'         => __( 'Back to top', 'studiare-extensions' ),
				'description'   => __( 'Smoothly scrolls to the top of the page.', 'studiare-extensions' ),
				'default_label' => __( 'Top', 'studiare-extensions' ),
				'icon'          => 'arrow-up',
				'fields'        => array(),
				'requires'      => '',
			),
			'dark_mode'   => array(
				'label'         => __( 'Dark mode', 'studiare-extensions' ),
				'description'   => __( 'Toggles the Studiare dark mode.', 'studiare-extensions' ),
				'default_label' => __( 'Night mode', 'studiare-extensions' ),
				'icon'          => 'moon',
				'fields'        => array(),
				'requires'      => '',
			),
			'selector'    => array(
				'label'         => __( 'Click an element', 'studiare-extensions' ),
				'description'   => __( 'Advanced: clicks any element on the page by CSS selector.', 'studiare-extensions' ),
				'default_label' => __( 'Action', 'studiare-extensions' ),
				'icon'          => 'sparkles',
				'fields'        => array( 'selector', 'url' ),
				'requires'      => '',
			),
		);
	}

	/** @return string[] */
	public static function ids(): array {
		return array_keys( self::all() );
	}

	/**
	 * @param string $type Type id.
	 */
	public static function get( string $type ): ?array {
		return self::all()[ $type ] ?? null;
	}

	/**
	 * Whether the type's dependency (e.g. WooCommerce) is present.
	 *
	 * @param string $type Type id.
	 */
	public static function is_available( string $type ): bool {
		$definition = self::get( $type );
		if ( ! $definition ) {
			return false;
		}

		return 'woocommerce' !== $definition['requires'] || class_exists( 'WooCommerce' );
	}
}
