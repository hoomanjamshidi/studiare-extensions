<?php
/**
 * Read-only bridge to the Studiare theme.
 *
 * Studiare stores its Redux options in the `codebean_option` row and prints
 * them as CSS custom properties (`--primary_color`, `--font_body-font-family`,
 * `--dark_primary_color`, ...). Modules default to those variables so they
 * follow the theme automatically; this class supplies the resolved values for
 * places where CSS variables are not available (admin previews, fallbacks).
 *
 * Everything here degrades gracefully when Studiare is not the active theme.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Theme_Bridge {

	private const THEME_SLUG  = 'studiare';
	private const OPTION_NAME = 'codebean_option';

	/** Studiare's own defaults, used when an option was never saved. */
	private const FALLBACKS = array(
		'primary'      => '#26a69a',
		'secondary'    => '#4ecdc4',
		'text'         => '#7d7e7f',
		'dark_surface' => '#150550',
		'dark_bg'      => '#020134',
		'dark_text'    => '#ffffff',
	);

	/**
	 * Term meta where Studiare keeps the "Featured Icon" (attachment ID) of a
	 * category: blog categories and product categories use different keys.
	 */
	private const CATEGORY_ICON_META = array(
		'category'    => 'sc_studi_blog_cat_icon',
		'product_cat' => 'sc_studi_cat_icon',
	);

	/** Term meta with a blog category's "Featured Color" (product categories have none). */
	private const CATEGORY_COLOR_META = 'sc_studi_blog_cat_color';

	/** @var array|null Memoized Redux options. */
	private static $options = null;

	/** True when Studiare (or a child theme of it) is active. */
	public static function is_active(): bool {
		return self::THEME_SLUG === get_template();
	}

	/**
	 * Returns a raw Studiare option.
	 *
	 * @param string $key      Redux option id.
	 * @param mixed  $fallback Returned when the option is missing or empty.
	 * @return mixed
	 */
	public static function option( string $key, $fallback = null ) {
		if ( null === self::$options ) {
			$stored        = get_option( self::OPTION_NAME, array() );
			self::$options = is_array( $stored ) ? $stored : array();
		}

		$value = self::$options[ $key ] ?? null;

		return ( null === $value || '' === $value ) ? $fallback : $value;
	}

	/**
	 * Resolved theme colors, for previews and CSS variable fallbacks.
	 *
	 * @return array{primary:string,secondary:string,text:string,dark_surface:string,dark_bg:string,dark_text:string}
	 */
	public static function palette(): array {
		$body_font  = self::option( 'font_body', array() );
		$body_color = Sanitizer::color( is_array( $body_font ) ? ( $body_font['color'] ?? '' ) : '' );

		return array(
			'primary'      => self::color_option( 'primary_color', self::FALLBACKS['primary'] ),
			'secondary'    => self::color_option( 'secondary_color', self::FALLBACKS['secondary'] ),
			'text'         => '' !== $body_color ? $body_color : self::FALLBACKS['text'],
			'dark_surface' => self::color_option( 'dark_primary_color', self::FALLBACKS['dark_surface'] ),
			'dark_bg'      => self::color_option( 'dark_secondary_color', self::FALLBACKS['dark_bg'] ),
			'dark_text'    => self::color_option( 'dark_light_color', self::FALLBACKS['dark_text'] ),
		);
	}

	/**
	 * Font families configured in Studiare's Typography panel.
	 *
	 * @return array{body:string,menu:string}
	 */
	public static function fonts(): array {
		$family = static function ( $option ): string {
			return is_array( $option ) ? (string) ( $option['font-family'] ?? '' ) : '';
		};

		return array(
			'body' => $family( self::option( 'font_body' ) ),
			'menu' => $family( self::option( 'menu_heading' ) ),
		);
	}

	/**
	 * Whether a user may take a course: the same test Studiare's own lesson
	 * list uses (`inc/studi_lessons.php`), so our "enrolled" state always
	 * matches which lessons the theme unlocks.
	 *
	 * Note that `studi_has_bought_items()` returns the *strings* "true" and
	 * "false"; casting its result to bool would make every user a student.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 */
	public static function user_has_course( int $user_id, int $product_id ): bool {
		if ( ! $user_id || ! $product_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( $user && function_exists( 'wc_customer_bought_product' ) && wc_customer_bought_product( (string) $user->user_email, $user_id, $product_id ) ) {
			return true;
		}

		if ( function_exists( 'studi_has_bought_items' ) && 'true' === (string) studi_has_bought_items( $user_id, $product_id ) ) {
			return true;
		}

		// Studiare treats any subscription of the user as access to every course.
		return class_exists( '\Studiare_Subscription_Manager' )
			&& method_exists( '\Studiare_Subscription_Manager', 'get_user_subscriptions' )
			&& ! empty( \Studiare_Subscription_Manager::get_user_subscriptions( $user_id ) );
	}

	/**
	 * Whether a product is a Studiare course (`_studiare_course = yes`).
	 *
	 * @param int $product_id Product ID.
	 */
	public static function is_course( int $product_id ): bool {
		$is_course = 'yes' === get_post_meta( $product_id, '_studiare_course', true );

		/**
		 * Filters whether a product counts as a course (page templates, bottom navigation).
		 *
		 * @param bool $is_course  Detected value.
		 * @param int  $product_id Product ID.
		 */
		return (bool) apply_filters( 'studiare_ext_is_course', $is_course, $product_id );
	}

	/** Whether the theme's dark mode feature is switched on. */
	public static function dark_mode_available(): bool {
		return self::is_active() && (bool) self::option( 'sc_darkmode_ready', false );
	}

	/**
	 * Icon picked for a category in Studiare's category screen, or 0.
	 *
	 * @param \WP_Term $term Blog or product category.
	 */
	public static function category_icon_id( \WP_Term $term ): int {
		$key = self::CATEGORY_ICON_META[ $term->taxonomy ] ?? '';

		return '' !== $key ? absint( get_term_meta( $term->term_id, $key, true ) ) : 0;
	}

	/**
	 * Colour picked for a blog category in Studiare's category screen, as a
	 * hex colour, or ''.
	 *
	 * @param \WP_Term $term Blog category.
	 */
	public static function category_color( \WP_Term $term ): string {
		if ( 'category' !== $term->taxonomy ) {
			return '';
		}

		return (string) sanitize_hex_color( (string) get_term_meta( $term->term_id, self::CATEGORY_COLOR_META, true ) );
	}

	/**
	 * Removes Studiare's built-in mobile bottom bar (markup and assets) so it
	 * never renders on top of ours.
	 */
	public static function disable_native_bottom_nav(): void {
		// The theme hooks its renderer on `wp_footer` while loading functions.php.
		remove_action( 'wp_footer', 'sc_adding_btm_menu_for_mobile' );

		// Its CSS/JS handles live in encoded theme files, so match them by source.
		$dequeue = static function (): void {
			foreach ( array( wp_styles(), wp_scripts() ) as $registry ) {
				foreach ( $registry->queue as $handle ) {
					$src = $registry->registered[ $handle ]->src ?? '';
					if ( is_string( $src ) && false !== strpos( $src, 'mobile-btm-menu' ) ) {
						$registry->dequeue( $handle );
					}
				}
			}
		};

		add_action( 'wp_enqueue_scripts', $dequeue, PHP_INT_MAX );
		add_action( 'wp_print_footer_scripts', $dequeue, 1 );
	}

	/**
	 * @param string $key      Redux color option id.
	 * @param string $fallback Color used when unset or invalid.
	 */
	private static function color_option( string $key, string $fallback ): string {
		$value = self::option( $key, '' );

		// Redux "color_rgba"/"link_color" fields store arrays.
		if ( is_array( $value ) ) {
			$value = $value['color'] ?? ( $value['regular'] ?? '' );
		}

		$color = Sanitizer::color( $value );

		return '' !== $color ? $color : $fallback;
	}
}
