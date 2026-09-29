<?php
/**
 * Course page designs.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

defined( 'ABSPATH' ) || exit;

final class Course {

	/** Cover, tabs and a sticky buy box (from the supplied course design). */
	public static function classic(): array {
		$main = El::box(
			array(
				'width'        => 66,
				'width_tablet' => 100,
				'gap'          => 28,
			),
			array(
				El::w(
					'stx-product-gallery',
					array(
						'ratio'      => '16/9',
						'thumbs'     => 'none',
						'sale_badge' => '',
					)
				),
				El::box(
					array( 'gap' => 14 ),
					array(
						El::w( 'stx-product-badges' ),
						El::w(
							'stx-product-title',
							array(
								'tag'  => 'h1',
								'size' => 'lg',
							)
						),
						El::w(
							'stx-product-excerpt',
							array(
								'tone'      => 'lead',
								'max_width' => El::size( '62ch' ),
							)
						),
						El::w(
							'stx-product-info',
							array(
								'layout'     => 'inline',
								'show_icons' => '',
								'items'      => Blocks::facts(
									array(
										array( 'rating' ),
										array( 'lessons', __( 'lessons', 'studiare-extensions' ) ),
										array( 'duration' ),
										array( 'students', __( 'students', 'studiare-extensions' ) ),
									)
								),
							)
						),
					)
				),
				// Phones and tablets: the buy card right under the title (the aside is hidden there).
				Blocks::buy_card( array( 'duration', 'lessons', 'level', 'certificate' ), array( 'hide' => array( 'desktop' ) ) ),
				El::w(
					'stx-product-tabs',
					array(
						'tabs' => array(
							array(
								'_id'    => El::id(),
								'title'  => __( 'Overview', 'studiare-extensions' ),
								'source' => 'overview',
							),
							array(
								'_id'    => El::id(),
								'title'  => __( 'Curriculum', 'studiare-extensions' ),
								'source' => 'curriculum',
							),
							array(
								'_id'    => El::id(),
								'title'  => __( 'Teacher', 'studiare-extensions' ),
								'source' => 'teacher',
							),
							array(
								'_id'    => El::id(),
								/* translators: {count} is replaced with the number of reviews. */
								'title'  => __( 'Reviews ({count})', 'studiare-extensions' ),
								'source' => 'reviews',
							),
						),
					)
				),
			)
		);

		$aside = El::box(
			array(
				'width'         => 34,
				'width_tablet'  => 100,
				'gap'           => 14,
				'sticky'        => 'desktop',
				'sticky_offset' => 24,
				'tag'           => 'aside',
				'hide'          => array( 'tablet', 'mobile' ),
			),
			array(
				Blocks::buy_card( array( 'duration', 'lessons', 'level', 'certificate', 'language' ) ),
				El::box(
					array(
						'surface' => 'dark',
						'dir'     => 'row',
						'justify' => 'space-between',
						'align'   => 'center',
						'wrap'    => 'wrap',
						'gap'     => 10,
						'pad'     => array( 16, 18 ),
					),
					array(
						Blocks::text( '<p>' . esc_html__( 'Questions before you buy?', 'studiare-extensions' ) . '</p>', 'body' ),
						Blocks::button( __( 'Contact us', 'studiare-extensions' ), '#', 'light', array( 'size' => 'sm' ) ),
					)
				),
			)
		);

		return array(
			El::box(
				array(
					'boxed'      => true,
					'pad'        => array( 24, 20, 8 ),
					'pad_mobile' => array( 16, 16, 8 ),
					'gap'        => 22,
				),
				array(
					El::w( 'stx-product-breadcrumb' ),
					El::box(
						array(
							'dir'        => 'row',
							'dir_tablet' => 'column',
							'gap'        => 28,
							'align'      => 'flex-start',
						),
						array( $main, $aside )
					),
				)
			),
			Blocks::related( __( 'Related courses', 'studiare-extensions' ) ),
			El::w( 'stx-mobile-buy-bar' ),
		);
	}

	/**
	 * Dark hero with the teacher and a buy card beside it that starts at the
	 * top of the hero and stays in view while scrolling.
	 *
	 * The hero is a block inside the main column whose dark background is
	 * stretched to the screen edges (class `stx-bleed`), so the buy card can
	 * sit in the next column from the very top instead of being pulled up
	 * over a hero of unknown height.
	 */
	public static function spotlight(): array {
		$card = static function ( array $hide ): array {
			return El::box(
				array(
					'surface' => 'card',
					'pad'     => 14,
					'gap'     => 16,
					'hide'    => $hide,
					'set'     => array( 'css_classes' => 'stx-buycard' ),
				),
				array(
					El::w(
						'stx-product-gallery',
						array(
							'ratio'      => '16/9',
							'thumbs'     => 'none',
							'sale_badge' => '',
						)
					),
					El::box(
						array(
							'pad' => array( 4, 10, 10 ),
							'gap' => 16,
						),
						array(
							El::w( 'stx-product-price' ),
							El::w( 'stx-add-to-cart' ),
							El::w(
								'stx-product-info',
								array(
									'layout' => 'list',
									'items'  => Blocks::facts( array( array( 'lessons' ), array( 'level' ), array( 'certificate' ), array( 'language' ) ) ),
								)
							),
						)
					),
				)
			);
		};

		$section = static function ( string $title, array $widget ): array {
			return El::box(
				array( 'gap' => 16 ),
				array( Blocks::heading( $title, 'md' ), $widget )
			);
		};

		$hero = El::box(
			array(
				'surface'    => 'dark',
				'gap'        => 16,
				'pad'        => array( 40, 0, 48 ),
				'pad_mobile' => array( 28, 0, 32 ),
				'tag'        => 'section',
				'set'        => array( 'css_classes' => 'stx-bleed' ),
			),
			array(
				El::w( 'stx-product-breadcrumb' ),
				El::w( 'stx-product-badges' ),
				El::w(
					'stx-product-title',
					array(
						'tag'  => 'h1',
						'size' => 'xl',
					)
				),
				El::w( 'stx-product-excerpt', array( 'tone' => 'lead' ) ),
				El::w(
					'stx-product-info',
					array(
						'layout'     => 'inline',
						'show_icons' => 'yes',
						'items'      => Blocks::facts(
							array(
								array( 'rating' ),
								array( 'students', __( 'students', 'studiare-extensions' ) ),
								array( 'duration' ),
								array( 'updated' ),
							)
						),
					)
				),
				El::w(
					'stx-course-teacher',
					array(
						'layout' => 'compact',
						'label'  => __( 'Taught by', 'studiare-extensions' ),
					)
				),
			)
		);

		$main = El::box(
			array(
				'width'        => 62,
				'width_tablet' => 100,
				'gap'          => 40,
				'gap_mobile'   => 28,
			),
			array(
				$hero,
				// Tablets and phones: the buy card right under the hero (the aside is hidden there).
				$card( array( 'desktop' ) ),
				El::w( 'stx-product-highlights' ),
				El::w( 'stx-course-curriculum', array( 'title' => __( 'Curriculum', 'studiare-extensions' ) ) ),
				$section(
					__( 'About this course', 'studiare-extensions' ),
					El::w(
						'stx-product-content',
						array(
							'collapse' => 'yes',
							'height'   => 480,
						)
					)
				),
				$section( __( 'Your teacher', 'studiare-extensions' ), El::w( 'stx-course-teacher' ) ),
				$section( __( 'Student reviews', 'studiare-extensions' ), El::w( 'stx-product-reviews' ) ),
			)
		);

		$aside = El::box(
			array(
				'width'         => 38,
				'sticky'        => 'desktop',
				'sticky_offset' => 24,
				'tag'           => 'aside',
				'margin'        => array( 32, 0, 0, 0 ),
				'hide'          => array( 'tablet', 'mobile' ),
			),
			array( $card( array() ) )
		);

		return array(
			El::box(
				array(
					'boxed'      => true,
					'dir'        => 'row',
					'dir_tablet' => 'column',
					'gap'        => 36,
					'align'      => 'flex-start',
					'pad'        => array( 0, 20 ),
					'pad_mobile' => array( 0, 16 ),
					'set'        => array( 'css_classes' => 'stx-spotlight' ),
				),
				array( $main, $aside )
			),
			Blocks::related( __( 'More courses', 'studiare-extensions' ) ),
			El::w( 'stx-mobile-buy-bar' ),
		);
	}

	/** Centred landing page with a closing call to action. */
	public static function landing(): array {
		$center = array( 'align' => 'center' );

		return array(
			El::box(
				array(
					'boxed'      => 960,
					'align'      => 'center',
					'gap'        => 18,
					'pad'        => array( 48, 20, 28 ),
					'pad_mobile' => array( 28, 16, 20 ),
					'tag'        => 'section',
				),
				array(
					El::w( 'stx-product-breadcrumb', $center ),
					El::w( 'stx-product-badges', array( 'align' => 'center' ) ),
					El::w(
						'stx-product-title',
						array_merge(
							$center,
							array(
								'tag'  => 'h1',
								'size' => 'xl',
							)
						)
					),
					El::w(
						'stx-product-excerpt',
						array(
							'tone'      => 'lead',
							'align'     => 'center',
							'max_width' => El::size( '60ch' ),
						)
					),
					El::box(
						array(
							'dir'        => 'row',
							'dir_mobile' => 'column',
							'align'      => 'center',
							'justify'    => 'center',
							'gap'        => 20,
							'set'        => array( 'css_classes' => 'stx-landing-buy' ),
						),
						array(
							El::w(
								'stx-product-price',
								array(
									'layout' => 'inline',
									'align'  => 'center',
								)
							),
							El::w( 'stx-add-to-cart', array( 'full_width' => '' ) ),
						)
					),
					El::w(
						'stx-product-info',
						array(
							'layout'     => 'inline',
							'show_icons' => '',
							'items'      => Blocks::facts(
								array(
									array( 'rating' ),
									array( 'students', __( 'students', 'studiare-extensions' ) ),
									array( 'duration' ),
								)
							),
							'align'      => 'center',
						)
					),
				)
			),
			El::box(
				array(
					'boxed'      => 1100,
					'pad'        => array( 0, 20 ),
					'pad_mobile' => array( 0, 16 ),
				),
				array(
					El::w(
						'stx-product-gallery',
						array(
							'ratio'      => '16/9',
							'thumbs'     => 'none',
							'sale_badge' => '',
							'radius'     => El::px( 24 ),
						)
					),
				)
			),
			El::box(
				array(
					'boxed'      => 1100,
					'pad'        => array( 28, 20 ),
					'pad_mobile' => array( 20, 16 ),
				),
				array(
					El::w(
						'stx-product-info',
						array(
							'layout'         => 'tiles',
							'columns'        => '4',
							'columns_tablet' => '2',
							'columns_mobile' => '2',
							'items'          => Blocks::facts( array( array( 'duration' ), array( 'lessons' ), array( 'level' ), array( 'certificate' ) ) ),
						)
					),
				)
			),
			El::box(
				array(
					'boxed'      => 820,
					'gap'        => 44,
					'pad'        => array( 24, 20, 24 ),
					'pad_mobile' => array( 16, 16 ),
				),
				array(
					El::w( 'stx-product-highlights' ),
					El::box(
						array( 'gap' => 16 ),
						array(
							Blocks::heading( __( 'Curriculum', 'studiare-extensions' ), 'lg' ),
							El::w( 'stx-course-curriculum', array( 'title' => '' ) ),
						)
					),
					El::box(
						array( 'gap' => 16 ),
						array(
							Blocks::heading( __( 'Your teacher', 'studiare-extensions' ), 'lg' ),
							El::w( 'stx-course-teacher' ),
						)
					),
					El::box(
						array( 'gap' => 16 ),
						array(
							Blocks::heading( __( 'About this course', 'studiare-extensions' ), 'lg' ),
							El::w(
								'stx-product-content',
								array(
									'collapse' => 'yes',
									'height'   => 520,
								)
							),
						)
					),
					El::box(
						array( 'gap' => 16 ),
						array(
							Blocks::heading( __( 'Student reviews', 'studiare-extensions' ), 'lg' ),
							El::w( 'stx-product-reviews' ),
						)
					),
				)
			),
			El::box(
				array(
					'boxed'      => 1100,
					'pad'        => array( 32, 20 ),
					'pad_mobile' => array( 24, 16 ),
				),
				array(
					El::box(
						array(
							'surface'    => 'accent-soft',
							'dir'        => 'row',
							'dir_tablet' => 'column',
							'justify'    => 'space-between',
							'align'      => 'center',
							'gap'        => 24,
							'pad'        => array( 32, 36 ),
							'pad_mobile' => array( 24, 20 ),
						),
						array(
							El::box(
								array(
									'gap'  => 8,
									'grow' => true,
								),
								array(
									Blocks::heading( __( 'Ready to start?', 'studiare-extensions' ), 'lg' ),
									Blocks::text( '<p>' . esc_html__( 'Lifetime access, learn at your own pace and ask your questions on the site.', 'studiare-extensions' ) . '</p>' ),
								)
							),
							El::box(
								array(
									'dir'     => 'row',
									'align'   => 'center',
									'gap'     => 16,
									'wrap'    => 'wrap',
									'justify' => 'center',
									'width'   => 'auto',
									'set'     => array( '_flex_size' => 'none' ),
								),
								array(
									El::w( 'stx-product-price', array( 'layout' => 'stack' ) ),
									El::w( 'stx-add-to-cart', array( 'full_width' => '' ) ),
								)
							),
						)
					),
				)
			),
			Blocks::related( __( 'More courses', 'studiare-extensions' ), array(), 1100 ),
			El::w( 'stx-mobile-buy-bar' ),
		);
	}
}
