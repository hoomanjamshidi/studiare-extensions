<?php
/**
 * Footer designs.
 *
 * On phones every footer centres its content (class `stx-foot--center-mobile`,
 * see builder.css): stacked columns that keep the desktop's start alignment
 * look ragged next to a centred copyright line and badges.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

use StudiareExt\Modules\Builder\Schema;

defined( 'ABSPATH' ) || exit;

final class Footer {

	/** About, two link columns, contact details, bottom bar with trust badges. */
	public static function columns(): array {
		return self::footer(
			array(
				'surface' => 'card',
				'class'   => 'stx-foot--accent',
			),
			array(
				El::box(
					array(
						'boxed'      => true,
						'dir'        => 'row',
						'wrap'       => 'wrap',
						'gap'        => array( 32, 40 ),
						'gap_mobile' => array( 28, 16 ),
						'pad'        => array( 52, 20, 36 ),
						'pad_mobile' => array( 36, 16, 24 ),
					),
					array(
						self::column( 30, 100, array( self::logo(), self::about(), self::social() ) ),
						self::column( 18, 46, array( self::col_title( __( 'Quick links', 'studiare-extensions' ) ), self::links() ) ),
						self::column( 18, 46, array( self::col_title( __( 'Courses & products', 'studiare-extensions' ) ), self::links( 'product_cat' ) ) ),
						self::column( 22, 100, array( self::col_title( __( 'Contact us', 'studiare-extensions' ) ), self::contact_list() ) ),
					)
				),
				self::bottom_bar( array( self::copyright(), El::w( 'stx-trust-badges', array( 'size' => El::px( 72 ) ) ) ) ),
			)
		);
	}

	/** Call-to-action band over a dark footer. */
	public static function dark(): array {
		return self::footer(
			array(),
			array(
				El::box(
					array(
						'boxed'      => 1180,
						'pad'        => array( 0, 20 ),
						'pad_mobile' => array( 0, 16 ),
						'margin'     => array( 24, 0, -56, 0 ),
						'set'        => array( 'z_index' => 2 ),
					),
					array(
						El::box(
							array(
								'surface'    => 'accent',
								'dir'        => 'row',
								'dir_tablet' => 'column',
								'justify'    => 'space-between',
								'align'      => 'center',
								'gap'        => 20,
								'pad'        => array( 28, 32 ),
								'pad_mobile' => array( 24, 20 ),
							),
							array(
								El::box(
									array(
										'gap'  => 6,
										'grow' => true,
									),
									array(
										Blocks::heading( __( 'Start learning today', 'studiare-extensions' ), 'md' ),
										Blocks::text( '<p>' . esc_html__( 'Join thousands of students and learn at your own pace.', 'studiare-extensions' ) . '</p>' ),
									)
								),
								Blocks::button( __( 'Browse courses', 'studiare-extensions' ), self::shop_url(), 'dark', array( 'size' => 'lg' ) ),
							)
						),
					)
				),
				El::box(
					array(
						'surface' => 'dark',
						'set'     => array( 'css_classes' => 'stx-band' ),
					),
					array(
						El::box(
							array(
								'boxed'      => true,
								'dir'        => 'row',
								'wrap'       => 'wrap',
								'gap'        => array( 28, 40 ),
								'pad'        => array( 96, 20, 32 ),
								'pad_mobile' => array( 84, 16, 24 ),
							),
							array(
								self::column( 36, 100, array( El::w( 'stx-site-logo', array( 'source' => 'text' ) ), self::about(), self::social( 'dark' ) ) ),
								self::column( 24, 100, array( self::col_title( __( 'Quick links', 'studiare-extensions' ) ), self::links() ) ),
								self::column( 28, 100, array( self::col_title( __( 'Contact us', 'studiare-extensions' ) ), self::contact_list() ) ),
							)
						),
						self::bottom_bar( array( self::copyright() ) ),
					)
				),
			)
		);
	}

	/** One slim row: logo, links and copyright. */
	public static function minimal(): array {
		return self::footer(
			array(
				'surface' => 'card',
				'class'   => 'stx-bar--line-top',
			),
			array(
				El::box(
					array(
						'boxed'      => true,
						'dir'        => 'row',
						'dir_tablet' => 'column',
						'justify'    => 'space-between',
						'align'      => 'center',
						'gap'        => 16,
						'pad'        => array( 22, 20 ),
					),
					array(
						self::logo( 36 ),
						self::inline_links(),
						self::copyright(),
					)
				),
			)
		);
	}

	/** Everything centred. */
	public static function centered(): array {
		return self::footer(
			array(
				'surface' => 'page',
				'class'   => 'stx-bar--line-top',
			),
			array(
				El::box(
					array(
						'boxed'      => 900,
						'align'      => 'center',
						'gap'        => 18,
						'pad'        => array( 48, 20, 28 ),
						'pad_mobile' => array( 36, 16, 24 ),
					),
					array(
						self::logo( 44, 'center' ),
						self::about( 'center' ),
						self::social( 'light', 16, 'center' ),
						self::inline_links(),
						self::copyright( 'center' ),
					)
				),
			)
		);
	}

	/** Support card, links and a large trust-badge area. */
	public static function trust(): array {
		return self::footer(
			array(
				'surface' => 'card',
				'class'   => 'stx-bar--line-top',
			),
			array(
				El::box(
					array(
						'boxed'      => true,
						'dir'        => 'row',
						'wrap'       => 'wrap',
						'gap'        => array( 28, 32 ),
						'gap_mobile' => array( 28, 16 ),
						'pad'        => array( 44, 20, 28 ),
						'pad_mobile' => array( 32, 16, 20 ),
					),
					array(
						El::box(
							array(
								'surface'      => 'page',
								'width'        => 32,
								'width_tablet' => 100,
								'gap'          => 14,
								'pad'          => 24,
							),
							array(
								Blocks::heading( __( 'Need help?', 'studiare-extensions' ), 'sm' ),
								self::contact_list(),
								Blocks::button( __( 'Send a ticket', 'studiare-extensions' ), '#', 'accent', array( 'size' => 'sm' ) ),
							)
						),
						self::column( 18, 46, array( self::col_title( __( 'Quick links', 'studiare-extensions' ) ), self::links() ), 30 ),
						self::column( 18, 46, array( self::col_title( __( 'Categories', 'studiare-extensions' ) ), self::links( 'product_cat' ) ), 30 ),
						self::column(
							24,
							100,
							array(
								self::col_title( __( 'Trust badges', 'studiare-extensions' ) ),
								El::w( 'stx-trust-badges', array( 'size' => El::px( 104 ) ) ),
							),
							30
						),
					)
				),
				self::bottom_bar( array( self::copyright(), self::social() ) ),
			)
		);
	}

	/** Dark brand panel beside three link columns. */
	public static function split(): array {
		return self::footer(
			array( 'surface' => 'card' ),
			array(
				El::box(
					array(
						'boxed'      => true,
						'dir'        => 'row',
						'dir_tablet' => 'column',
						'gap'        => 40,
						'pad'        => array( 40, 20, 32 ),
						'pad_mobile' => array( 20, 12, 24 ),
					),
					array(
						El::box(
							array(
								'surface'      => 'dark',
								'width'        => 34,
								'width_tablet' => 100,
								'gap'          => 16,
								'pad'          => 28,
								'pad_mobile'   => array( 28, 20 ),
							),
							array(
								El::w( 'stx-site-logo', array( 'source' => 'text' ) ),
								self::about(),
								self::social( 'dark' ),
								El::w( 'stx-trust-badges', array( 'size' => El::px( 76 ) ) ),
							)
						),
						El::box(
							array(
								'dir'          => 'row',
								'wrap'         => 'wrap',
								'gap'          => array( 28, 32 ),
								'gap_mobile'   => array( 28, 16 ),
								'grow'         => true,
								'width_tablet' => 100,
								'pad'          => array( 12, 0, 0 ),
							),
							array(
								self::column( 28, 46, array( self::col_title( __( 'Quick links', 'studiare-extensions' ) ), self::links() ) ),
								self::column( 28, 46, array( self::col_title( __( 'Courses & products', 'studiare-extensions' ) ), self::links( 'product_cat' ) ) ),
								self::column( 36, 100, array( self::col_title( __( 'Contact us', 'studiare-extensions' ) ), self::contact_list() ) ),
							)
						),
					)
				),
				self::bottom_bar( array( self::copyright(), self::inline_links() ) ),
			)
		);
	}

	/** Three contact cards on top, then the menu, social links and copyright. */
	public static function support(): array {
		$card = static function ( array $item, string $title ): array {
			return El::box(
				array(
					'surface'      => 'page',
					'dir'          => 'row',
					'align'        => 'center',
					'gap'          => 14,
					'pad'          => array( 18, 20 ),
					'grow'         => true,
					'width'        => 30,
					'width_tablet' => 100,
				),
				array(
					El::w(
						'stx-icon-list',
						array(
							'items'                       => array( $item ),
							'icon_style'                  => 'boxed',
							'text_typography_typography'  => 'custom',
							'text_typography_font_weight' => '700',
						)
					),
					El::w(
						'stx-text',
						array(
							'content' => '<p>' . esc_html( $title ) . '</p>',
							'tone'    => 'muted',
						),
						array( 'hide' => array( 'mobile' ) )
					),
				)
			);
		};

		$items = self::contact_items();

		return self::footer(
			array(
				'surface' => 'card',
				'class'   => 'stx-bar--line-top',
			),
			array(
				El::box(
					array(
						'boxed'      => true,
						'gap'        => 28,
						'pad'        => array( 40, 20, 28 ),
						'pad_mobile' => array( 28, 16, 20 ),
					),
					array(
						El::box(
							array(
								'dir'        => 'row',
								'dir_tablet' => 'column',
								'gap'        => 14,
							),
							array(
								$card( $items[0], __( 'Saturday to Wednesday, 9 to 17', 'studiare-extensions' ) ),
								$card( $items[1], __( 'We answer within one working day', 'studiare-extensions' ) ),
								$card( $items[2], __( 'Visit us by appointment', 'studiare-extensions' ) ),
							)
						),
						El::box(
							array(
								'dir'        => 'row',
								'dir_tablet' => 'column',
								'justify'    => 'space-between',
								'align'      => 'center',
								'gap'        => 18,
							),
							array(
								self::logo( 40 ),
								self::inline_links(),
								self::social(),
							)
						),
					)
				),
				self::bottom_bar( array( self::copyright(), El::w( 'stx-trust-badges', array( 'size' => El::px( 64 ) ) ) ) ),
			)
		);
	}

	/** Soft accent band, centred: logo, links, badges and social icons. */
	public static function soft(): array {
		return self::footer(
			array( 'surface' => 'soft' ),
			array(
				El::box(
					array(
						'boxed'      => 1000,
						'align'      => 'center',
						'gap'        => 22,
						'pad'        => array( 52, 20, 24 ),
						'pad_mobile' => array( 36, 16, 20 ),
					),
					array(
						self::logo( 48, 'center' ),
						Blocks::heading(
							__( 'Learn skills that move your career forward', 'studiare-extensions' ),
							'sm',
							array(
								'align' => 'center',
								'tag'   => 'div',
							)
						),
						self::inline_links(),
						El::w(
							'stx-icon-list',
							array(
								'layout' => 'horizontal',
								'items'  => self::contact_items( false ),
							)
						),
						El::w(
							'stx-trust-badges',
							array(
								'size'  => El::px( 80 ),
								'align' => 'center',
							)
						),
					)
				),
				self::bottom_bar( array( self::copyright(), self::social() ), 1000 ),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Parts (some are shared with the header designs)
	 * ------------------------------------------------------------------- */

	/**
	 * Elementor's native Social Icons widget with brand colours.
	 *
	 * @param string $tone  `light` or `dark` background.
	 * @param int    $size  Icon size.
	 * @param string $align Alignment on desktop: start|center (phones always centre).
	 */
	public static function social( string $tone = 'light', int $size = 16, string $align = 'start' ): array {
		$list = array();
		foreach ( array( 'fab fa-instagram', 'fab fa-telegram', 'fab fa-whatsapp', 'fab fa-youtube' ) as $icon ) {
			$list[] = array(
				'_id'         => El::id(),
				'social_icon' => array(
					'value'   => $icon,
					'library' => 'fa-brands',
				),
				'link'        => array(
					'url'         => '#',
					'is_external' => 'true',
					'nofollow'    => '',
				),
			);
		}

		$dark = 'dark' === $tone;

		return El::w(
			'social-icons',
			array(
				'social_icon_list'      => $list,
				'shape'                 => 'rounded',
				'align'                 => 'center' === $align ? 'center' : El::start(),
				'align_mobile'          => 'center',
				'icon_color'            => 'custom',
				'icon_primary_color'    => $dark ? 'rgba(255,255,255,0.08)' : '#EEF1F3',
				'icon_secondary_color'  => $dark ? '#EAEDEF' : Schema::BRAND_DEFAULTS['text'],
				'hover_primary_color'   => Schema::BRAND_DEFAULTS['accent'],
				'hover_secondary_color' => '#FFFFFF',
				'icon_size'             => El::px( $size ),
				// 16px icon + padding = a 44px tap target.
				'icon_padding'          => array(
					'unit'  => 'px',
					'size'  => max( 10, (int) round( ( 44 - $size ) / 2 ) ),
					'sizes' => array(),
				),
				'icon_spacing'          => El::px( 8 ),
				'border_radius'         => El::dims( 10 ),
			)
		);
	}

	/**
	 * Placeholder contact details for the icon list widget.
	 *
	 * @param bool $address Include the address line.
	 */
	public static function contact_items( bool $address = true ): array {
		$items = array(
			array(
				'_id'  => El::id(),
				'text' => '021-00000000',
				'icon' => 'phone',
				'ltr'  => 'yes',
				'link' => array( 'url' => 'tel:02100000000' ),
			),
			array(
				'_id'  => El::id(),
				'text' => 'info@example.com',
				'icon' => 'mail',
				'ltr'  => 'yes',
				'link' => array( 'url' => 'mailto:info@example.com' ),
			),
		);

		if ( $address ) {
			$items[] = array(
				'_id'  => El::id(),
				'text' => __( 'Your address goes here', 'studiare-extensions' ),
				'icon' => 'map-pin',
			);
		}

		return $items;
	}

	/**
	 * Template elements: one outer `<footer>` container.
	 *
	 * @param array $o        `surface`, `class`.
	 * @param array $children Children.
	 */
	private static function footer( array $o, array $children ): array {
		return array(
			El::box(
				array(
					'surface' => $o['surface'] ?? '',
					'tag'     => 'footer',
					'set'     => array( 'css_classes' => trim( 'stx-foot stx-foot--center-mobile ' . ( $o['class'] ?? '' ) ) ),
				),
				$children
			),
		);
	}

	/**
	 * Footer column.
	 *
	 * @param int   $width        Width on desktop (%).
	 * @param int   $mobile       Width on phones (%): 46 puts two link lists side by side.
	 * @param array $children     Children.
	 * @param int   $width_tablet Width on tablets (%).
	 */
	private static function column( int $width, int $mobile, array $children, int $width_tablet = 46 ): array {
		return El::box(
			array(
				'width'        => $width,
				'width_tablet' => $width_tablet,
				'width_mobile' => $mobile,
				'gap'          => 14,
			),
			$children
		);
	}

	/**
	 * Copyright line and a second item, split across a full-width line.
	 *
	 * @param array    $children Children.
	 * @param int|true $boxed    Content width.
	 */
	private static function bottom_bar( array $children, $boxed = true ): array {
		return El::box(
			array(
				'boxed' => $boxed,
				'set'   => array( 'css_classes' => 'stx-bar--line-top' ),
			),
			array(
				El::box(
					array(
						'dir'        => 'row',
						'dir_mobile' => 'column',
						'justify'    => 'space-between',
						'align'      => 'center',
						'gap'        => 14,
						'pad'        => array( 16, 20 ),
						'pad_mobile' => array( 16, 12, 20 ),
					),
					$children
				),
			)
		);
	}

	/**
	 * Logo sized by height.
	 *
	 * @param int    $height Height.
	 * @param string $align  Alignment on desktop.
	 */
	private static function logo( int $height = 44, string $align = '' ): array {
		$settings = array(
			'width'  => El::px( 170 ),
			'height' => El::px( $height ),
		);
		if ( '' !== $align ) {
			$settings['align'] = $align;
		}

		return El::w( 'stx-site-logo', $settings );
	}

	/**
	 * Short text about the school.
	 *
	 * @param string $align Alignment on desktop.
	 */
	private static function about( string $align = '' ): array {
		return Blocks::text(
			'<p>' . esc_html__( 'A short line about your school: what you teach, who it is for and why students trust you.', 'studiare-extensions' ) . '</p>',
			'muted',
			'' !== $align ? array( 'align' => $align ) : array()
		);
	}

	/**
	 * Column heading.
	 *
	 * @param string $title Title.
	 */
	private static function col_title( string $title ): array {
		return Blocks::heading( $title, 'xs', array( 'tag' => 'h3' ) );
	}

	/** Contact list with placeholder details. */
	private static function contact_list(): array {
		return El::w( 'stx-icon-list', array( 'items' => self::contact_items() ) );
	}

	/**
	 * Vertical link list from a menu (or the product categories).
	 *
	 * @param string $source `menu` or `product_cat`.
	 */
	private static function links( string $source = 'menu' ): array {
		return El::w(
			'stx-nav-menu',
			array(
				'source' => $source,
				'layout' => 'vertical',
				'toggle' => 'never',
				'depth'  => '1',
			)
		);
	}

	/** One line of top-level menu links. */
	private static function inline_links(): array {
		return El::w(
			'stx-nav-menu',
			array(
				'pointer' => 'none',
				'toggle'  => 'never',
				'depth'   => '1',
				'align'   => 'center',
			)
		);
	}

	/**
	 * @param string $align Alignment (start|center).
	 */
	private static function copyright( string $align = '' ): array {
		return El::w( 'stx-copyright', '' !== $align ? array( 'align' => $align ) : array() );
	}

	private static function shop_url(): string {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '#';
	}
}
