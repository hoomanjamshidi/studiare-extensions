<?php
/**
 * Building blocks shared by several presets.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

use StudiareExt\Core\Site;

defined( 'ABSPATH' ) || exit;

final class Blocks {

	/**
	 * Heading widget.
	 *
	 * @param string $title Title.
	 * @param string $size  xl|lg|md|sm|xs.
	 * @param array  $more  Extra settings (eyebrow, subtitle, align, tag…).
	 * @param array  $o     Widget options (width, grow, fill).
	 */
	public static function heading( string $title, string $size = 'md', array $more = array(), array $o = array() ): array {
		return El::w(
			'stx-heading',
			array_merge(
				array(
					'title' => $title,
					'size'  => $size,
					'tag'   => in_array( $size, array( 'sm', 'xs' ), true ) ? 'h3' : 'h2',
				),
				$more
			),
			$o
		);
	}

	/**
	 * Section title of the home designs: « title ——— link.
	 *
	 * @param string $title    Title.
	 * @param string $action   Link text at the end ('' for none).
	 * @param string $url      Link.
	 * @param string $subtitle Text under the title.
	 */
	public static function section_title( string $title, string $action = '', string $url = '', string $subtitle = '' ): array {
		return self::heading(
			$title,
			'md',
			array(
				'decor'       => 'rule',
				'subtitle'    => $subtitle,
				'action_text' => $action,
				'action_link' => self::link( $url ),
			)
		);
	}

	/**
	 * Large title (and text) with a link at the end of the row.
	 *
	 * @param string $title    Title.
	 * @param string $subtitle Text under the title.
	 * @param string $action   Link text.
	 * @param string $url      Link.
	 */
	public static function row_title( string $title, string $subtitle, string $action, string $url ): array {
		return El::box(
			array(
				'dir'     => 'row',
				'justify' => 'space-between',
				'align'   => 'flex-end',
				'wrap'    => 'wrap',
				'gap'     => 16,
			),
			array(
				self::heading( $title, 'lg', array( 'subtitle' => $subtitle ), array( 'width' => 'auto' ) ),
				self::button( $action . self::arrow(), $url, 'link' ),
			)
		);
	}

	/**
	 * Text widget.
	 *
	 * @param string $html Paragraph HTML.
	 * @param string $tone body|lead|muted.
	 * @param array  $more Extra settings.
	 */
	public static function text( string $html, string $tone = 'body', array $more = array() ): array {
		return El::w(
			'stx-text',
			array_merge(
				array(
					'content' => $html,
					'tone'    => $tone,
				),
				$more
			)
		);
	}

	/**
	 * Button widget.
	 *
	 * @param string $text    Text.
	 * @param string $url     Link.
	 * @param string $variant Variant.
	 * @param array  $more    Extra settings.
	 */
	public static function button( string $text, string $url, string $variant = 'accent', array $more = array() ): array {
		return El::w(
			'stx-button',
			array_merge(
				array(
					'text'    => $text,
					'link'    => self::link( $url ),
					'variant' => $variant,
				),
				$more
			)
		);
	}

	/**
	 * Row of buttons.
	 *
	 * @param array $buttons List of [text, url, variant].
	 * @param bool  $pill    Fully rounded buttons.
	 */
	public static function buttons( array $buttons, bool $pill ): array {
		$children = array();
		foreach ( $buttons as $button ) {
			$children[] = self::button( $button[0], $button[1], $button[2], array( 'size' => 'lg' ) + ( $pill ? self::pill() : array() ) );
		}

		return El::box(
			array(
				'dir'   => 'row',
				'wrap'  => 'wrap',
				'gap'   => 12,
				'width' => 'auto',
			),
			$children
		);
	}

	/** Button settings for a fully rounded button. */
	public static function pill(): array {
		return array( 'btn_radius' => El::dims( 999 ) );
	}

	/* ---------------------------------------------------------------------
	 * Page bands (home, about and contact designs)
	 * ------------------------------------------------------------------- */

	/**
	 * Full-width band on a brand surface, content boxed to the site width.
	 *
	 * @param string $surface  Brand surface.
	 * @param array  $children Content.
	 * @param array  $o        Container options overriding the defaults.
	 */
	public static function band( string $surface, array $children, array $o = array() ): array {
		return El::box(
			array_merge(
				array(
					'boxed'      => true,
					'surface'    => $surface,
					'tag'        => 'section',
					'gap'        => 20,
					'pad'        => array( 24, 20 ),
					'pad_mobile' => array( 18, 16 ),
					'set'        => array( 'css_classes' => 'stx-band' ),
				),
				$o
			),
			$children
		);
	}

	/** Padding of a white band between grey ones. */
	public static function white_band(): array {
		return array(
			'pad'        => array( 44, 20 ),
			'pad_mobile' => array( 32, 16 ),
		);
	}

	/**
	 * Padding of the last band, which ends the page before the footer.
	 *
	 * @param int $bottom Bottom padding on wide screens.
	 */
	public static function last_band( int $bottom = 60 ): array {
		return array(
			'pad'        => array( 24, 20, $bottom ),
			'pad_mobile' => array( 18, 16, 40 ),
		);
	}

	/**
	 * Buy card: price, add to cart and key facts.
	 *
	 * @param array $facts Info items (source keys) for the list under the button.
	 * @param array $o     `note` (text under the button), `pad`, `hide` (devices).
	 */
	public static function buy_card( array $facts, array $o = array() ): array {
		$items = array();
		foreach ( $facts as $source ) {
			$items[] = array(
				'_id'    => El::id(),
				'source' => $source,
				'icon'   => 'auto',
			);
		}

		return El::box(
			array(
				'surface'    => 'card',
				'pad'        => $o['pad'] ?? 26,
				'pad_mobile' => 20,
				'gap'        => 18,
				'hide'       => $o['hide'] ?? array(),
				'set'        => array( 'css_classes' => 'stx-buycard' ),
			),
			array(
				El::w( 'stx-product-price', array( 'size' => 'lg' ) ),
				El::w(
					'stx-add-to-cart',
					array(
						'note'      => $o['note'] ?? '',
						'note_icon' => 'wallet',
					)
				),
				$items ? El::w(
					'stx-product-info',
					array(
						'items'  => $items,
						'layout' => 'list',
					)
				) : null,
			)
		);
	}

	/**
	 * "Related" section: heading row with a "see all" link, then a grid.
	 *
	 * @param string   $title    Section title.
	 * @param array    $settings Related widget settings.
	 * @param int|true $boxed    Content width (true: the site's container width).
	 */
	public static function related( string $title, array $settings = array(), $boxed = true ): array {
		return El::box(
			array(
				'boxed'      => $boxed,
				'pad'        => array( 56, 20, 72 ),
				'pad_mobile' => array( 40, 16, 56 ),
				'gap'        => 20,
				'tag'        => 'section',
			),
			array(
				El::box(
					array(
						'dir'     => 'row',
						'justify' => 'space-between',
						'align'   => 'flex-end',
						'wrap'    => 'wrap',
						'gap'     => 12,
					),
					array(
						self::heading( $title, 'lg', array( 'tag' => 'h2' ) ),
						self::button( __( 'See all', 'studiare-extensions' ) . self::arrow(), self::shop_url(), 'link', array( 'size' => 'sm' ) ),
					)
				),
				El::w( 'stx-related-products', array_merge( array( 'source' => 'same_kind' ), $settings ) ),
			)
		);
	}

	/**
	 * Small benefit tile: icon, title and one line of text.
	 *
	 * @param string $icon  Bundled icon key.
	 * @param string $title Title.
	 * @param string $text  Text.
	 */
	public static function perk( string $icon, string $title, string $text ): array {
		return El::box(
			array(
				'surface'      => 'card',
				'pad'          => array( 14, 16 ),
				'gap'          => 4,
				'grow'         => true,
				'width'        => 30,
				'width_mobile' => 100,
			),
			array(
				El::w(
					'stx-icon-list',
					array(
						'items'                       => array(
							array(
								'_id'  => El::id(),
								'text' => $title,
								'icon' => $icon,
							),
						),
						'icon_style'                  => 'plain',
						'text_typography_typography'  => 'custom',
						'text_typography_font_weight' => '800',
					)
				),
				self::text( '<p>' . esc_html( $text ) . '</p>', 'muted' ),
			)
		);
	}

	/**
	 * Info items for the product-info widget.
	 *
	 * @param array $specs List of [source, suffix?, label?].
	 */
	public static function facts( array $specs ): array {
		$items = array();
		foreach ( $specs as $spec ) {
			$spec    = (array) $spec;
			$items[] = array(
				'_id'    => El::id(),
				'source' => $spec[0],
				'suffix' => $spec[1] ?? '',
				'label'  => $spec[2] ?? '',
				'icon'   => 'auto',
			);
		}

		return $items;
	}

	/* ---------------------------------------------------------------------
	 * Values
	 * ------------------------------------------------------------------- */

	/**
	 * URL control value.
	 *
	 * @param string $url Address.
	 */
	public static function link( string $url ): array {
		return array(
			'url'         => $url,
			'is_external' => '',
			'nofollow'    => '',
		);
	}

	/** Arrow pointing forward in the reading direction. */
	public static function arrow(): string {
		return Site::is_rtl() ? ' ←' : ' →';
	}

	public static function shop_url(): string {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}

	/** The posts page (or the home page when the front page lists posts). */
	public static function blog_url(): string {
		$url = get_post_type_archive_link( 'post' );

		return $url ? $url : home_url( '/' );
	}
}
