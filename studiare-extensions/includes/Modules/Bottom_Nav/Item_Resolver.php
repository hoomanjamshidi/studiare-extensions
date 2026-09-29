<?php
/**
 * Turns saved navigation items into view models for the current request.
 *
 * A view model contains everything the templates need (tag, href, action,
 * icon markup, badge, sheet) so the views stay free of business logic.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Bottom_Nav;

use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Item_Resolver {

	/** Cart badges show "99+" above this count. */
	private const BADGE_CAP = 99;

	/** Theme menu locations tried when the Studiare off-canvas menu is unavailable. */
	private const MENU_LOCATIONS = array( 'mobile-menu', 'main-menu', 'primary', 'menu-1' );

	/** @var array Module settings. */
	private $settings;

	/**
	 * @param array $settings Module settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Visible items for the current visitor, with `current` and `featured` resolved.
	 *
	 * @return array<int, array> View models.
	 */
	public function resolve(): array {
		$items = array();

		foreach ( $this->settings['items'] as $item ) {
			if ( ! $this->is_visible( $item ) ) {
				continue;
			}

			$model = $this->build( $item );
			if ( null !== $model ) {
				$items[] = $model;
			}
		}

		$this->mark_current( $items );
		$this->mark_featured( $items );

		return $items;
	}

	/**
	 * Cart badge markup, shared by the initial render and WooCommerce fragments.
	 *
	 * @param bool $hide_empty Hide the badge when the cart is empty.
	 */
	public static function cart_badge_html( bool $hide_empty = true ): string {
		$count = 0;
		if ( function_exists( 'WC' ) && WC()->cart ) {
			$count = (int) WC()->cart->get_cart_contents_count();
		}

		$classes = array( 'stx-bn__badge', 'stx-bn-cart-count' );
		if ( 0 === $count && $hide_empty ) {
			$classes[] = 'is-empty';
		}

		return sprintf(
			'<span class="%1$s" data-count="%2$d">%3$s</span>',
			esc_attr( implode( ' ', $classes ) ),
			$count,
			esc_html( $count > self::BADGE_CAP ? self::BADGE_CAP . '+' : (string) $count )
		);
	}

	/**
	 * @param array $item Saved item.
	 */
	private function is_visible( array $item ): bool {
		if ( empty( $item['enabled'] ) || ! Item_Types::is_available( $item['type'] ) ) {
			return false;
		}

		if ( 'guests' === $item['visibility'] && is_user_logged_in() ) {
			return false;
		}

		if ( 'members' === $item['visibility'] && ! is_user_logged_in() ) {
			return false;
		}

		if ( 'dark_mode' === $item['type'] && ! Theme_Bridge::dark_mode_available() ) {
			return false;
		}

		return true;
	}

	/**
	 * Builds the view model, or null when the item cannot work on this site.
	 *
	 * @param array $item Saved item.
	 */
	private function build( array $item ): ?array {
		$type_label = Item_Types::get( $item['type'] )['default_label'] ?? '';
		$label      = '' !== $item['label'] ? $item['label'] : $type_label;

		$model = array(
			'id'          => $item['id'],
			'type'        => $item['type'],
			'label'       => $label,
			'href'        => '',
			'target'      => '',
			'action'      => '',
			'data'        => array(),
			'icon'        => $this->icon_html( $item, false ),
			'icon_active' => $this->icon_html( $item, true ),
			'icon_alt'    => '',
			'badge'       => '' !== $item['badge'] ? '<span class="stx-bn__badge stx-bn__badge--text">' . esc_html( $item['badge'] ) . '</span>' : '',
			'featured'    => false,
			'current'     => false,
			'match'       => '',
			'sheet'       => null,
		);

		$method = 'build_' . $item['type'];

		return method_exists( $this, $method ) ? $this->$method( $model, $item ) : null;
	}

	/**
	 * @param array $model View model.
	 */
	private function build_home( array $model ): array {
		$model['href']  = home_url( '/' );
		$model['match'] = 'home';

		return $model;
	}

	/**
	 * @param array $model View model.
	 * @param array $item  Saved item.
	 */
	private function build_link( array $model, array $item ): ?array {
		if ( '' === $item['url'] ) {
			return null;
		}

		$model['href']  = $item['url'];
		$model['match'] = 'url';
		if ( $item['new_tab'] ) {
			$model['target'] = '_blank';
		}

		return $model;
	}

	/**
	 * @param array $model View model.
	 * @param array $item  Saved item.
	 */
	private function build_search( array $model, array $item ): array {
		$model['href']   = home_url( '/?s=' );
		$model['action'] = 'sheet';
		$model['match']  = 'search';
		$model['sheet']  = array(
			'kind'      => 'search',
			'title'     => $this->sheet_title( $item, $model['label'] ),
			'item_id'   => $item['id'],
			'live'      => (bool) $item['search_live'],
			'post_type' => Search_Scope::for_results_page( Search_Scope::post_types( $item ) ),
		);

		return $model;
	}

	/**
	 * @param array $model View model.
	 * @param array $item  Saved item.
	 */
	private function build_cart( array $model, array $item ): array {
		$action = $item['cart_action'];

		$model['href']  = 'checkout' === $action ? wc_get_checkout_url() : wc_get_cart_url();
		$model['match'] = 'cart';
		$model['badge'] = self::cart_badge_html( $item['hide_empty_badge'] ) . $model['badge'];
		$model['data']  = array( 'hide-empty' => $item['hide_empty_badge'] ? '1' : '0' );

		if ( in_array( $action, array( 'auto', 'sheet' ), true ) ) {
			// "auto" prefers Studiare's off-canvas cart and falls back to our sheet.
			$model['action'] = 'auto' === $action ? 'cart' : 'sheet';
			$model['sheet']  = array(
				'kind'  => 'cart',
				'title' => $this->sheet_title( $item, $model['label'] ),
			);
		}

		return $model;
	}

	/**
	 * @param array $model View model.
	 * @param array $item  Saved item.
	 */
	private function build_account( array $model, array $item ): array {
		$has_woo        = function_exists( 'wc_get_page_permalink' );
		$model['match'] = 'account';

		if ( is_user_logged_in() ) {
			$default_url   = $has_woo ? wc_get_page_permalink( 'myaccount' ) : admin_url( 'profile.php' );
			$model['href'] = '' !== $item['url'] ? $item['url'] : $default_url;

			if ( $item['show_avatar'] ) {
				$avatar = get_avatar_url( get_current_user_id(), array( 'size' => 64 ) );
				if ( $avatar ) {
					$model['icon']        = sprintf( '<img class="stx-bn__avatar" src="%s" alt="" width="32" height="32" loading="lazy" decoding="async">', esc_url( $avatar ) );
					$model['icon_active'] = '';
				}
			}

			return $model;
		}

		$model['label'] = '' !== $item['guest_label'] ? $item['guest_label'] : __( 'Login', 'studiare-extensions' );
		$model['href']  = $has_woo ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();

		if ( 'modal' === $item['guest_action'] ) {
			$model['action'] = 'login-modal';
		}

		return $model;
	}

	/**
	 * @param array $model View model.
	 * @param array $item  Saved item.
	 */
	private function build_menu( array $model, array $item ): ?array {
		$title = $this->sheet_title( $item, $model['label'] );

		if ( 'wp_menu' === $item['menu_source'] ) {
			if ( ! $item['menu_id'] || ! wp_get_nav_menu_object( $item['menu_id'] ) ) {
				return null;
			}

			$model['action'] = 'sheet';
			$model['sheet']  = array(
				'kind'     => 'menu',
				'title'    => $title,
				'menu'     => (int) $item['menu_id'],
				'location' => '',
			);

			return $model;
		}

		if ( Theme_Bridge::is_active() ) {
			$model['action'] = 'theme-menu';
			return $model;
		}

		// Not on Studiare: show the theme's main menu in a sheet instead.
		foreach ( self::MENU_LOCATIONS as $location ) {
			if ( has_nav_menu( $location ) ) {
				$model['action'] = 'sheet';
				$model['sheet']  = array(
					'kind'     => 'menu',
					'title'    => $title,
					'menu'     => 0,
					'location' => $location,
				);

				return $model;
			}
		}

		return null;
	}

	/**
	 * @param array $model View model.
	 * @param array $item  Saved item.
	 */
	private function build_content( array $model, array $item ): ?array {
		$uses_elementor = 'elementor' === $item['content_source'];

		if ( $uses_elementor ? ! $item['content_id'] : '' === trim( $item['content_html'] ) ) {
			return null;
		}

		$model['action'] = 'sheet';
		$model['sheet']  = array(
			'kind'       => 'content',
			'title'      => $this->sheet_title( $item, $model['label'] ),
			'source'     => $item['content_source'],
			'content_id' => (int) $item['content_id'],
			'html'       => $item['content_html'],
		);

		return $model;
	}

	/**
	 * @param array $model View model.
	 */
	private function build_back_to_top( array $model ): array {
		$model['action'] = 'top';

		return $model;
	}

	/**
	 * @param array $model View model.
	 * @param array $item  Saved item.
	 */
	private function build_dark_mode( array $model, array $item ): array {
		$model['action'] = 'dark';

		// Shown instead of the main icon while dark mode is on.
		$alt_item          = array_merge( $item, array( 'icon' => 'moon' === $item['icon'] ? 'sun' : 'moon' ) );
		$model['icon_alt'] = 'pack' === $item['icon_source'] ? $this->icon_html( $alt_item, false ) : '';

		return $model;
	}

	/**
	 * @param array $model View model.
	 * @param array $item  Saved item.
	 */
	private function build_selector( array $model, array $item ): ?array {
		if ( '' === $item['selector'] && '' === $item['url'] ) {
			return null;
		}

		$model['href']   = $item['url'];
		$model['action'] = '' !== $item['selector'] ? 'selector' : '';
		$model['data']   = array( 'selector' => $item['selector'] );

		return $model;
	}

	/**
	 * Icon markup for the item, or its filled "active" variant.
	 * Returns an empty string for the active variant when there is none.
	 *
	 * @param array $item   Saved item.
	 * @param bool  $active Build the active variant.
	 */
	private function icon_html( array $item, bool $active ): string {
		$pack          = $this->settings['icon_pack'];
		$wants_variant = $active && $this->settings['active_filled'];

		switch ( $item['icon_source'] ) {
			case 'image':
				if ( $active || '' === $item['icon_image'] ) {
					break;
				}
				return sprintf( '<img class="stx-bn__img" src="%s" alt="" width="24" height="24" loading="lazy" decoding="async">', esc_url( $item['icon_image'] ) );

			case 'svg':
				if ( $active || '' === $item['icon_svg'] ) {
					break;
				}
				return Icon_Library::decorate( Icon_Library::sanitize_svg( $item['icon_svg'] ) );

			case 'fontawesome':
				if ( '' === $item['icon_fa'] ) {
					break;
				}
				if ( $active ) {
					return $wants_variant ? self::fa_icon( self::fa_solid( $item['icon_fa'] ) ) : '';
				}
				return self::fa_icon( $item['icon_fa'] );
		}

		if ( $active && ! $wants_variant ) {
			return '';
		}

		// Default: the global icon pack.
		if ( Icon_Library::FONT_AWESOME === $pack ) {
			$classes = Icon_Library::fa_class( $item['icon'], $this->settings['fa_weight'] );
			return self::fa_icon( $active ? self::fa_solid( $classes ) : $classes );
		}

		if ( $active && ! Icon_Library::has_active_variant( $pack, $item['icon'] ) ) {
			return '';
		}

		return Icon_Library::svg( $pack, $item['icon'], $active );
	}

	/**
	 * @param string $classes Font Awesome class list.
	 */
	private static function fa_icon( string $classes ): string {
		return '<i class="' . esc_attr( $classes ) . '" aria-hidden="true"></i>';
	}

	/**
	 * Swaps the weight prefix for the solid one; brand icons are left alone.
	 *
	 * @param string $classes Font Awesome class list.
	 */
	private static function fa_solid( string $classes ): string {
		return (string) preg_replace( '/\bfa[lrd]\b/', 'fas', $classes );
	}

	/**
	 * @param array  $item  Saved item.
	 * @param string $label Resolved item label.
	 */
	private function sheet_title( array $item, string $label ): string {
		return '' !== $item['sheet_title'] ? $item['sheet_title'] : $label;
	}

	/**
	 * Marks the first item that matches the current request.
	 *
	 * @param array $items View models (by reference).
	 */
	private function mark_current( array &$items ): void {
		foreach ( $items as &$item ) {
			if ( $this->matches_request( $item ) ) {
				$item['current'] = true;
				break;
			}
		}
		unset( $item );
	}

	/**
	 * @param array $item View model.
	 */
	private function matches_request( array $item ): bool {
		switch ( $item['match'] ) {
			case 'home':
				return is_front_page();
			case 'search':
				return is_search();
			case 'cart':
				return function_exists( 'is_cart' ) && ( is_cart() || is_checkout() );
			case 'account':
				return function_exists( 'is_account_page' ) ? is_account_page() : false;
			case 'url':
				return self::is_current_url( $item['href'] ) || self::is_shop_link( $item['href'] );
		}

		return false;
	}

	/**
	 * The shop page can be served from another URL (e.g. `?post_type=product`
	 * with plain permalinks), so a link to it is current whenever is_shop().
	 *
	 * @param string $url Item URL.
	 */
	private static function is_shop_link( string $url ): bool {
		if ( ! function_exists( 'is_shop' ) || ! is_shop() ) {
			return false;
		}

		return untrailingslashit( $url ) === untrailingslashit( (string) wc_get_page_permalink( 'shop' ) );
	}

	/**
	 * Compares a link with the current request: host, path and — for plain
	 * permalinks such as `/?page_id=5` — the link's query parameters.
	 *
	 * @param string $url Item URL.
	 */
	private static function is_current_url( string $url ): bool {
		$target = wp_parse_url( $url );
		if ( ! is_array( $target ) || ( empty( $target['path'] ) && empty( $target['host'] ) && empty( $target['query'] ) ) ) {
			return false;
		}

		$home_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! empty( $target['host'] ) && strtolower( $target['host'] ) !== strtolower( $home_host ) ) {
			return false;
		}

		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only compared.
		$current_path = (string) wp_parse_url( (string) $request_uri, PHP_URL_PATH );
		$target_path  = self::normalize_path( $target['path'] ?? '/' );

		if ( self::normalize_path( $current_path ) !== $target_path ) {
			return false;
		}

		// Every query parameter of the link must be present in the request.
		parse_str( $target['query'] ?? '', $target_query );
		if ( $target_query ) {
			foreach ( $target_query as $key => $value ) {
				$current = $_GET[ $key ] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput -- read-only comparison.
				if ( ! is_scalar( $current ) || ! is_scalar( $value ) || (string) wp_unslash( $current ) !== (string) $value ) {
					return false;
				}
			}

			return true;
		}

		// A bare link to the site root only matches the front page, not `/?s=…` and friends.
		$home_path = self::normalize_path( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );

		return $target_path !== $home_path || is_front_page();
	}

	/**
	 * @param string $path URL path.
	 */
	private static function normalize_path( string $path ): string {
		$path = strtolower( rawurldecode( $path ) );

		return '/' . trim( $path, '/' );
	}

	/**
	 * Flags the item rendered as the raised centre button in styles that have one:
	 * the item marked "featured", or the middle item otherwise.
	 *
	 * @param array $items View models (by reference).
	 */
	private function mark_featured( array &$items ): void {
		if ( ! $items || ! Styles::has_featured_button( $this->settings['style'] ) ) {
			return;
		}

		$featured_ids = wp_list_pluck(
			array_filter(
				$this->settings['items'],
				static function ( $item ) {
					return ! empty( $item['featured'] );
				}
			),
			'id'
		);

		$index = (int) floor( count( $items ) / 2 );
		foreach ( $items as $i => $item ) {
			if ( in_array( $item['id'], $featured_ids, true ) ) {
				$index = $i;
				break;
			}
		}

		$items[ $index ]['featured'] = true;
	}
}
