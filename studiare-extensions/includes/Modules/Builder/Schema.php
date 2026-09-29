<?php
/**
 * Settings schema and defaults for the page templates module.
 *
 * Template references are stored as strings: a template post ID, or one of
 * the keywords `theme` (use Studiare's own layout), `none` (print nothing)
 * and `same` (mobile slot mirrors the desktop slot).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;

final class Schema {

	public const MAX_RULES = 30;

	/** Template kinds, in the order the admin shows them. */
	public const TYPES = array( 'course', 'product', 'header', 'footer', 'home', 'archive', 'post', 'about', 'contact' );

	/**
	 * Whole-page designs: "Create page" copies them into a regular page, and
	 * they are edited and previewed with the site's header and footer.
	 */
	public const PAGE_TYPES = array( 'home', 'about', 'contact' );

	/** Kinds rendered on single product pages. */
	public const SINGLE_TYPES = array( 'course', 'product' );

	/** Kinds rendered on blog pages: post lists (archives) and single posts. */
	public const BLOG_TYPES = array( 'archive', 'post' );

	/** Looks of the Search widget's live results; the first is the default. */
	public const SEARCH_STYLES = array( 'detailed', 'compact' );

	/** Brand tokens → default value (taken from the supplied designs). */
	public const BRAND_DEFAULTS = array(
		'accent'        => '#f8b000',
		'accent_strong' => '#d68800',
		'accent_soft'   => '#fff5de',
		'on_accent'     => '#ffffff',
		'ink'           => '#0f1315',
		'text'          => '#3e4950',
		'muted'         => '#5f717c',
		'bg'            => '#f4f6f7',
		'card'          => '#ffffff',
		'line'          => '#d5dce0',
		'dark'          => '#2e373c',
		'success'       => '#1e8e5a',
		'danger'        => '#c0392b',
	);

	public static function defaults(): array {
		$single = array(
			'default'    => 'theme',
			'rules'      => array(),
			'preview_id' => 0,
		);

		return array(
			'enabled'    => false,
			'course'     => $single,
			'product'    => $single,
			'header'     => array(
				'desktop'        => 'theme',
				'mobile'         => 'same',
				'sticky_desktop' => 'none',
				'sticky_mobile'  => 'none',
			),
			'footer'     => array(
				'desktop' => 'theme',
				'mobile'  => 'same',
			),
			'blog'       => array(
				'archive' => 'theme',
				'post'    => 'theme',
				'search'  => false,
			),
			'breakpoint' => 1024,
			'options'    => array(
				'hide_theme_title' => true,
				'icon_pack'        => 'tabler',
				'persian_digits'   => true,
				'search_results'   => self::SEARCH_STYLES[0],
			),
			'brand'      => array_merge(
				array_fill_keys( array_keys( self::BRAND_DEFAULTS ), '' ),
				array( 'radius' => 18 )
			),
		);
	}

	public static function rule_defaults(): array {
		return array(
			'id'       => '',
			'terms'    => '',
			'children' => true,
			'template' => 'theme',
		);
	}

	public static function fields(): array {
		$ref    = array( 'type' => 'key' );
		$sticky = array(
			'type'    => 'enum',
			'options' => array( 'none', 'always', 'scroll_up' ),
		);

		$single = array(
			'type'   => 'group',
			'fields' => array(
				'default'    => $ref,
				'rules'      => array(
					'type'     => 'list',
					'max'      => self::MAX_RULES,
					'fields'   => array(
						'id'       => array( 'type' => 'key' ),
						'terms'    => array( 'type' => 'id_list' ),
						'children' => array( 'type' => 'bool' ),
						'template' => $ref,
					),
					'defaults' => self::rule_defaults(),
				),
				'preview_id' => array(
					'type' => 'int',
					'min'  => 0,
				),
			),
		);

		$brand = array_fill_keys( array_keys( self::BRAND_DEFAULTS ), array( 'type' => 'color' ) );

		$brand['radius'] = array(
			'type' => 'int',
			'min'  => 0,
			'max'  => 40,
		);

		return array(
			'enabled'    => array( 'type' => 'bool' ),
			'course'     => $single,
			'product'    => $single,
			'header'     => array(
				'type'   => 'group',
				'fields' => array(
					'desktop'        => $ref,
					'mobile'         => $ref,
					'sticky_desktop' => $sticky,
					'sticky_mobile'  => $sticky,
				),
			),
			'footer'     => array(
				'type'   => 'group',
				'fields' => array(
					'desktop' => $ref,
					'mobile'  => $ref,
				),
			),
			'blog'       => array(
				'type'   => 'group',
				'fields' => array(
					'archive' => $ref,
					'post'    => $ref,
					'search'  => array( 'type' => 'bool' ),
				),
			),
			'breakpoint' => array(
				'type' => 'int',
				'min'  => 480,
				'max'  => 1600,
			),
			'options'    => array(
				'type'   => 'group',
				'fields' => array(
					'hide_theme_title' => array( 'type' => 'bool' ),
					'icon_pack'        => array(
						'type'    => 'enum',
						'options' => array_values( array_diff( Icon_Library::pack_ids(), array( Icon_Library::FONT_AWESOME ) ) ),
					),
					'persian_digits'   => array( 'type' => 'bool' ),
					'search_results'   => array(
						'type'    => 'enum',
						'options' => self::SEARCH_STYLES,
					),
				),
			),
			'brand'      => array(
				'type'   => 'group',
				'fields' => $brand,
			),
		);
	}
}
