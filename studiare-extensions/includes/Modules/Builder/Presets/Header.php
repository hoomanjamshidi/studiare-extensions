<?php
/**
 * Header designs.
 *
 * Every design has two layouts: the desktop rows (hidden on tablets and
 * phones) and a compact bar built for small screens (hidden on desktop):
 * menu button, logo, search and cart in one 64px row. Squeezing a desktop
 * row onto a phone wraps buttons onto a second line, so the two are kept
 * separate. Any design can still be picked separately per device.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

defined( 'ABSPATH' ) || exit;

final class Header {

	/** Logo, menu, search, cart and login in one row. */
	public static function classic(): array {
		return self::header(
			array(
				'surface' => 'page',
				'class'   => 'stx-bar--line',
			),
			array(
				self::desktop_row(
					array(
						self::logo(),
						self::menu( 'underline', array( 'grow' => true ) ),
						self::actions(
							array(
								El::w( 'stx-search', array( 'layout' => 'icon' ) ),
								El::w( 'stx-cart', array( 'variant' => 'outline' ) ),
								El::w( 'stx-account', array( 'variant' => 'dark' ) ),
							)
						),
					)
				),
				self::mobile_bar(),
			)
		);
	}

	/** Logo in the middle, search and account around it, menu row below. */
	public static function centered(): array {
		$third = static function ( string $justify, array $children ): array {
			return El::box(
				array(
					'width'   => 33,
					'dir'     => 'row',
					'align'   => 'center',
					'justify' => $justify,
					'gap'     => 10,
				),
				$children
			);
		};

		return self::header(
			array(
				'surface' => 'card',
				'class'   => 'stx-bar--line',
			),
			array(
				El::box(
					array(
						'boxed' => true,
						'pad'   => array( 18, 20, 8 ),
						'hide'  => array( 'tablet', 'mobile' ),
					),
					array(
						El::box(
							array(
								'dir'   => 'row',
								'align' => 'center',
								'gap'   => 16,
							),
							array(
								$third(
									'flex-start',
									array(
										El::w(
											'stx-search',
											array(
												'layout' => 'field',
												'field_width' => El::size( 100 ),
											),
											array( 'grow' => true )
										),
									)
								),
								$third( 'center', array( self::logo( 56, 200, 'center' ) ) ),
								$third(
									'flex-end',
									array(
										El::w( 'stx-account', array( 'variant' => 'outline' ) ),
										El::w( 'stx-cart', array( 'variant' => 'icon' ) ),
									)
								),
							)
						),
						El::box(
							array( 'pad' => array( 12, 0, 4 ) ),
							array( self::menu( 'pill', array(), array( 'align' => 'center' ) ) )
						),
					)
				),
				self::mobile_bar( 'center' ),
			)
		);
	}

	/** Contact strip on top, then logo, menu and a call-to-action. */
	public static function topbar(): array {
		return self::header(
			array(),
			array(
				El::box(
					array(
						'surface' => 'dark',
						'hide'    => array( 'tablet', 'mobile' ),
						'set'     => array( 'css_classes' => 'stx-band' ),
					),
					array(
						El::box(
							array(
								'boxed'   => true,
								'dir'     => 'row',
								'align'   => 'center',
								'justify' => 'space-between',
								'gap'     => 16,
								'pad'     => array( 6, 20 ),
							),
							array(
								El::w(
									'stx-icon-list',
									array(
										'layout' => 'horizontal',
										'items'  => Footer::contact_items( false ),
										'text_typography_typography' => 'custom',
										'text_typography_font_size' => El::px( 13 ),
									)
								),
								Footer::social( 'dark', 13 ),
							)
						),
					)
				),
				El::box(
					array(
						'surface' => 'card',
						'set'     => array( 'css_classes' => 'stx-band stx-bar--line' ),
					),
					array(
						self::desktop_row(
							array(
								self::logo(),
								self::menu( 'pill', array( 'grow' => true ) ),
								self::actions(
									array(
										self::search_icon(),
										El::w( 'stx-cart', array( 'variant' => 'icon' ) ),
										El::w( 'stx-account', array( 'variant' => 'icon' ) ),
										Blocks::button( __( 'Free consultation', 'studiare-extensions' ), '#', 'accent', array( 'size' => 'sm' ) ),
									)
								),
							)
						),
						self::mobile_bar(),
					)
				),
			)
		);
	}

	/** Wide search, then a categories button and the menu — for large catalogues. */
	public static function market(): array {
		return self::header(
			array(
				'surface' => 'card',
				'class'   => 'stx-bar--line',
			),
			array(
				El::box(
					array( 'hide' => array( 'tablet', 'mobile' ) ),
					array(
						self::desktop_row(
							array(
								self::logo(),
								El::w(
									'stx-search',
									array(
										'layout'      => 'field',
										'field_width' => El::size( 100 ),
										'placeholder' => __( 'Search among all products…', 'studiare-extensions' ),
									),
									array( 'grow' => true )
								),
								self::actions(
									array(
										El::w( 'stx-account', array( 'variant' => 'outline' ) ),
										El::w(
											'stx-cart',
											array(
												'variant' => 'solid',
												'show_total' => 'yes',
											)
										),
									)
								),
							),
							array( 'gap' => 28 )
						),
						El::box(
							array( 'set' => array( 'css_classes' => 'stx-bar--line-top' ) ),
							array(
								El::box(
									array(
										'boxed' => true,
										'dir'   => 'row',
										'align' => 'center',
										'gap'   => 18,
										'pad'   => array( 6, 20 ),
									),
									array(
										El::w(
											'stx-nav-menu',
											array(
												'source' => 'product_cat',
												'toggle' => 'always',
												'toggle_label' => __( 'Categories', 'studiare-extensions' ),
												'drawer_title' => __( 'Categories', 'studiare-extensions' ),
												'depth'  => '2',
												'_css_classes' => 'stx-nav--cat-button',
											)
										),
										self::menu( 'none', array( 'grow' => true ) ),
									)
								),
							)
						),
					)
				),
				self::mobile_bar( 'start', array( 'search' => 'row' ) ),
			)
		);
	}

	/** Rounded floating bar with a dark mode switch. */
	public static function floating(): array {
		return array(
			El::box(
				array(
					'tag'        => 'header',
					'pad'        => array( 12, 16 ),
					'pad_mobile' => array( 8, 10 ),
					'set'        => array( 'css_classes' => 'stx-bar stx-bar--float' ),
				),
				array(
					El::box(
						array(
							'boxed'   => true,
							'surface' => 'card',
							'dir'     => 'row',
							'align'   => 'center',
							'justify' => 'space-between',
							'gap'     => 16,
							'pad'     => array( 8, 10, 8, 20 ),
							'hide'    => array( 'tablet', 'mobile' ),
							'set'     => array( 'css_classes' => 'stx-pill-bar' ),
						),
						array(
							self::logo( 40, 150 ),
							self::menu( 'pill', array( 'grow' => true ), array( 'align' => 'center' ) ),
							self::actions(
								array(
									El::w( 'stx-dark-toggle' ),
									El::w( 'stx-search', array( 'layout' => 'icon' ) ),
									El::w( 'stx-account', array( 'variant' => 'accent' ) ),
								)
							),
						)
					),
					self::mobile_bar(
						'start',
						array(
							'surface' => 'card',
							'class'   => 'stx-pill-bar',
							'pad'     => array( 6, 8, 6, 14 ),
							'min_h'   => 56,
							'account' => false,
							'dark'    => true,
						)
					),
				)
			),
		);
	}

	/** Dark bar with light text and an accent login button. */
	public static function dark(): array {
		return self::header(
			array( 'surface' => 'dark' ),
			array(
				self::desktop_row(
					array(
						self::logo(),
						self::menu( 'underline', array( 'grow' => true ), array( 'align' => 'center' ) ),
						self::actions(
							array(
								El::w( 'stx-search', array( 'layout' => 'icon' ) ),
								El::w( 'stx-cart', array( 'variant' => 'icon' ) ),
								El::w( 'stx-account', array( 'variant' => 'accent' ) ),
							)
						),
					),
					array( 'min_h' => 76 )
				),
				self::mobile_bar( 'start', array( 'account' => false ) ),
			)
		);
	}

	/** Airy bar: logo, centred menu and icon-only actions. */
	public static function minimal(): array {
		return self::header(
			array( 'surface' => 'page' ),
			array(
				self::desktop_row(
					array(
						self::logo( 40, 150 ),
						self::menu( 'none', array( 'grow' => true ), array( 'align' => 'center' ) ),
						self::actions(
							array(
								self::search_icon(),
								El::w( 'stx-cart', array( 'variant' => 'icon' ) ),
								El::w( 'stx-account', array( 'variant' => 'icon' ) ),
							),
							4
						),
					),
					array( 'min_h' => 80 )
				),
				self::mobile_bar( 'center', array( 'account' => false ) ),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Parts
	 * ------------------------------------------------------------------- */

	/**
	 * Template elements: one outer `<header>` container.
	 *
	 * @param array $o        `surface`, `class`.
	 * @param array $children Children.
	 */
	private static function header( array $o, array $children ): array {
		return array(
			El::box(
				array(
					'surface' => $o['surface'] ?? '',
					'tag'     => 'header',
					'set'     => array( 'css_classes' => trim( 'stx-bar ' . ( $o['class'] ?? '' ) ) ),
				),
				$children
			),
		);
	}

	/**
	 * One boxed desktop row (hidden on tablets and phones).
	 *
	 * @param array $children Children.
	 * @param array $o        `gap`, `min_h`.
	 */
	private static function desktop_row( array $children, array $o = array() ): array {
		return El::box(
			array(
				'boxed' => true,
				'dir'   => 'row',
				'align' => 'center',
				'gap'   => $o['gap'] ?? 24,
				'pad'   => array( 12, 20 ),
				'min_h' => $o['min_h'] ?? 72,
				'hide'  => array( 'tablet', 'mobile' ),
			),
			$children
		);
	}

	/**
	 * The bar shown on tablets and phones.
	 *
	 * @param string $layout `start` (menu and logo at the start, icons at the end) or `center` (logo in the middle).
	 * @param array  $o      `account` (bool), `search` (`icon`|`row`), `dark` (dark mode switch),
	 *                       `surface`, `class`, `pad`, `min_h`.
	 */
	private static function mobile_bar( string $layout = 'start', array $o = array() ): array {
		$o += array(
			'account' => true,
			'search'  => 'icon',
			'dark'    => false,
			'surface' => '',
			'class'   => '',
			'pad'     => array( 8, 12 ),
			'min_h'   => 64,
		);

		$menu   = El::w(
			'stx-nav-menu',
			array(
				'toggle'      => 'always',
				'drawer_side' => 'start',
			)
		);
		$logo   = El::w(
			'stx-site-logo',
			array(
				'width_mobile'  => El::px( 130 ),
				'width_tablet'  => El::px( 150 ),
				'height_mobile' => El::px( 38 ),
				'height_tablet' => El::px( 42 ),
				'align'         => 'center' === $layout ? 'center' : 'flex-start',
			)
		);
		$search = 'icon' === $o['search'] ? self::search_icon() : null;
		$dark   = $o['dark'] ? El::w( 'stx-dark-toggle' ) : null;
		$cart   = El::w( 'stx-cart', array( 'variant' => 'icon' ) );
		$user   = $o['account'] ? El::w( 'stx-account', array( 'variant' => 'icon' ) ) : null;

		$group = static function ( string $justify, array $children, int $width = 0 ): array {
			$box = array(
				'dir'         => 'row',
				'align'       => 'center',
				'justify'     => $justify,
				'gap'         => 2,
				'wrap_tablet' => 'nowrap',
				'wrap_mobile' => 'nowrap',
			);
			if ( $width ) {
				$box['width']        = $width;
				$box['width_tablet'] = $width;
				$box['width_mobile'] = $width;
			} else {
				$box['width'] = 'auto';
			}

			return El::box( $box, $children );
		};

		if ( 'center' === $layout ) {
			$row = array(
				$group( 'flex-start', array( $menu, $search ), 30 ),
				$group( 'center', array( $logo ), 40 ),
				$group( 'flex-end', array( $dark, $user, $cart ), 30 ),
			);
		} else {
			$row = array(
				$group( 'flex-start', array( $menu, $logo ) ),
				$group( 'flex-end', array( $dark, $search, $user, $cart ) ),
			);
		}

		$bar = El::box(
			array(
				'surface'     => $o['surface'],
				'dir'         => 'row',
				'align'       => 'center',
				'justify'     => 'space-between',
				'wrap_tablet' => 'nowrap',
				'wrap_mobile' => 'nowrap',
				'gap'         => 8,
				'pad'         => $o['pad'],
				'min_h'       => $o['min_h'],
				'set'         => array( 'css_classes' => $o['class'] ),
			),
			$row
		);

		$search_row = 'row' === $o['search'] ? El::box(
			array( 'pad' => array( 0, 12, 12 ) ),
			array(
				El::w(
					'stx-search',
					array(
						'layout'       => 'field',
						'field_width'  => El::size( 100 ),
						'field_height' => El::px( 44 ),
					)
				),
			)
		) : null;

		return El::box(
			array(
				'hide' => array( 'desktop' ),
				'set'  => array( 'css_classes' => 'stx-mbar' ),
			),
			array( $bar, $search_row )
		);
	}

	/** Search icon without a frame, for rows of plain icon buttons. */
	private static function search_icon(): array {
		return El::w(
			'stx-search',
			array(
				'layout'     => 'icon',
				'icon_boxed' => '',
			)
		);
	}

	/**
	 * Site logo sized by height, so square image logos stay compact.
	 *
	 * @param int    $height Height on desktop.
	 * @param int    $width  Maximum width on desktop.
	 * @param string $align  Alignment.
	 */
	private static function logo( int $height = 48, int $width = 180, string $align = '' ): array {
		$settings = array(
			'width'  => El::px( $width ),
			'height' => El::px( $height ),
		);
		if ( '' !== $align ) {
			$settings['align'] = $align;
		}

		return El::w( 'stx-site-logo', $settings );
	}

	/**
	 * Main menu for a desktop row (no drawer: phones use the mobile bar).
	 *
	 * @param string $pointer  underline|pill|none.
	 * @param array  $o        Widget options for El::w() (`grow`).
	 * @param array  $settings Extra settings.
	 */
	private static function menu( string $pointer, array $o = array(), array $settings = array() ): array {
		return El::w(
			'stx-nav-menu',
			array_merge(
				array(
					'pointer' => $pointer,
					'toggle'  => 'never',
				),
				$settings
			),
			$o
		);
	}

	/**
	 * Group of buttons at the end of a row; never shrinks, so buttons keep their text on one line.
	 *
	 * @param array $children Widgets.
	 * @param int   $gap      Space between them.
	 */
	private static function actions( array $children, int $gap = 10 ): array {
		return El::box(
			array(
				'dir'   => 'row',
				'align' => 'center',
				'gap'   => $gap,
				'width' => 'auto',
				'set'   => array( '_flex_size' => 'none' ),
			),
			$children
		);
	}
}
