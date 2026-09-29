<?php
/**
 * Product page designs.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

defined( 'ABSPATH' ) || exit;

final class Product {

	/** Gallery + buy area in one card, perks, tabs and similar products (from the supplied shop design). */
	public static function shop(): array {
		$gallery = El::box(
			array(
				'width'        => 48,
				'width_tablet' => 100,
			),
			array(
				El::w(
					'stx-product-gallery',
					array(
						'ratio'  => '1/1',
						'thumbs' => 'below',
						'fit'    => 'contain',
					)
				),
			)
		);

		$info = El::box(
			array(
				'width'        => 52,
				'width_tablet' => 100,
				'gap'          => 18,
			),
			array(
				El::box(
					array( 'gap' => 10 ),
					array(
						El::w(
							'stx-product-badges',
							array(
								'badges' => array(
									array(
										'_id'     => El::id(),
										'source'  => 'kind',
										'variant' => 'soft',
									),
									array(
										'_id'     => El::id(),
										'source'  => 'sale',
										'variant' => 'dark',
									),
								),
							)
						),
						El::w(
							'stx-product-title',
							array(
								'tag'  => 'h1',
								'size' => 'lg',
							)
						),
						El::w( 'stx-product-rating' ),
					)
				),
				El::w( 'stx-product-excerpt', array( 'tone' => 'body' ) ),
				El::w( 'stx-product-stock' ),
				El::box(
					array(
						'surface' => 'page',
						'pad'     => 18,
						'gap'     => 14,
					),
					array(
						El::w( 'stx-product-price' ),
						El::w( 'stx-add-to-cart', array( 'variation_style' => 'buttons' ) ),
					)
				),
				El::box(
					array(
						'dir'        => 'row',
						'dir_mobile' => 'column',
						'gap'        => 10,
					),
					array(
						Blocks::perk( 'gift', __( 'Free shipping', 'studiare-extensions' ), __( 'On orders over a set amount', 'studiare-extensions' ) ),
						Blocks::perk( 'discount', __( 'Easy returns', 'studiare-extensions' ), __( 'Within 7 days of delivery', 'studiare-extensions' ) ),
						Blocks::perk( 'lock', __( 'Secure payment', 'studiare-extensions' ), __( 'Bank gateway and instalments', 'studiare-extensions' ) ),
					)
				),
			)
		);

		$details = El::box(
			array(
				'width'        => 66,
				'width_tablet' => 100,
				'surface'      => 'card',
				'pad'          => array( 8, 26, 28 ),
				'pad_mobile'   => array( 8, 16, 20 ),
			),
			array(
				El::w(
					'stx-product-tabs',
					array(
						'tabs' => array(
							array(
								'_id'    => El::id(),
								'title'  => __( 'Description', 'studiare-extensions' ),
								'source' => 'description',
							),
							array(
								'_id'    => El::id(),
								'title'  => __( 'Specifications', 'studiare-extensions' ),
								'source' => 'specs',
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

		$promo = El::box(
			array(
				'width'        => 34,
				'width_tablet' => 100,
				'surface'      => 'dark',
				'pad'          => 22,
				'gap'          => 12,
				'sticky'       => 'desktop',
				'tag'          => 'aside',
			),
			array(
				Blocks::heading(
					__( 'Better together', 'studiare-extensions' ),
					'sm',
					array( 'eyebrow' => __( 'Special offer', 'studiare-extensions' ) )
				),
				Blocks::text( '<p>' . esc_html__( 'Pair this item with a related course and save on both. Edit this box in Elementor to promote any offer.', 'studiare-extensions' ) . '</p>' ),
				Blocks::button( __( 'See the offer', 'studiare-extensions' ), '#', 'accent', array( 'align' => 'stretch' ) ),
			)
		);

		return array(
			El::box(
				array(
					'boxed'      => 1240,
					'pad'        => array( 24, 20, 8 ),
					'pad_mobile' => array( 16, 16, 8 ),
					'gap'        => 22,
				),
				array(
					El::w( 'stx-product-breadcrumb' ),
					El::box(
						array(
							'surface'    => 'card',
							'dir'        => 'row',
							'dir_tablet' => 'column',
							'gap'        => 36,
							'pad'        => 24,
							'pad_mobile' => 16,
						),
						array( $gallery, $info )
					),
					El::box(
						array(
							'dir'        => 'row',
							'dir_tablet' => 'column',
							'gap'        => 28,
							'align'      => 'flex-start',
						),
						array( $details, $promo )
					),
				)
			),
			Blocks::related(
				__( 'Similar products', 'studiare-extensions' ),
				array(
					'source' => 'related',
					'ratio'  => '1/1',
				),
				1240
			),
			El::w( 'stx-mobile-buy-bar' ),
		);
	}

	/** Large gallery with a sticky summary and a section jump bar. */
	public static function showcase(): array {
		$summary = El::box(
			array(
				'width'         => 42,
				'width_tablet'  => 100,
				'gap'           => 18,
				'sticky'        => 'desktop',
				'sticky_offset' => 24,
			),
			array(
				El::w( 'stx-product-breadcrumb' ),
				El::w(
					'stx-product-title',
					array(
						'tag'  => 'h1',
						'size' => 'lg',
					)
				),
				El::w( 'stx-product-rating' ),
				El::w( 'stx-product-price' ),
				El::w( 'stx-product-excerpt', array( 'tone' => 'body' ) ),
				El::w( 'stx-add-to-cart' ),
				El::w( 'stx-product-stock' ),
				El::box(
					array(
						'surface' => 'page',
						'pad'     => array( 14, 16 ),
					),
					array(
						El::w(
							'stx-icon-list',
							array(
								'items' => array(
									array(
										'_id'  => El::id(),
										'text' => __( 'Fast delivery across the country', 'studiare-extensions' ),
										'icon' => 'gift',
									),
									array(
										'_id'  => El::id(),
										'text' => __( 'Original product guarantee', 'studiare-extensions' ),
										'icon' => 'award',
									),
									array(
										'_id'  => El::id(),
										'text' => __( 'Secure online payment', 'studiare-extensions' ),
										'icon' => 'lock',
									),
								),
							)
						),
					)
				),
				El::w(
					'stx-product-info',
					array(
						'layout'     => 'list',
						'show_icons' => '',
						'items'      => Blocks::facts( array( array( 'sku' ), array( 'categories' ), array( 'weight' ) ) ),
					)
				),
			)
		);

		return array(
			El::box(
				array(
					'boxed'      => true,
					'dir'        => 'row',
					'dir_tablet' => 'column',
					'gap'        => 48,
					'align'      => 'flex-start',
					'pad'        => array( 28, 20, 24 ),
					'pad_mobile' => array( 16, 16 ),
				),
				array(
					El::box(
						array(
							'width'        => 58,
							'width_tablet' => 100,
						),
						array(
							El::w(
								'stx-product-gallery',
								array(
									'ratio'  => '4/3',
									'thumbs' => 'side',
								)
							),
						)
					),
					$summary,
				)
			),
			El::box(
				array(
					'boxed'      => 1000,
					'pad'        => array( 32, 20 ),
					'pad_mobile' => array( 20, 16 ),
				),
				array(
					El::w(
						'stx-product-tabs',
						array(
							'layout' => 'sections',
							'tabs'   => array(
								array(
									'_id'    => El::id(),
									'title'  => __( 'Description', 'studiare-extensions' ),
									'source' => 'description',
								),
								array(
									'_id'    => El::id(),
									'title'  => __( 'Specifications', 'studiare-extensions' ),
									'source' => 'specs',
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
			),
			Blocks::related(
				__( 'You may also like', 'studiare-extensions' ),
				array(
					'source' => 'related',
					'ratio'  => '1/1',
				)
			),
			El::w( 'stx-mobile-buy-bar' ),
		);
	}

	/** Soft hero band, benefit tiles and an accordion. */
	public static function editorial(): array {
		return array(
			El::box(
				array(
					'surface'    => 'soft',
					'pad'        => array( 40, 20, 48 ),
					'pad_mobile' => array( 24, 16, 32 ),
					'tag'        => 'section',
				),
				array(
					El::box(
						array(
							'boxed'      => 1160,
							'dir'        => 'row',
							'dir_tablet' => 'column-reverse',
							'gap'        => 48,
							'align'      => 'center',
						),
						array(
							El::box(
								array(
									'width'        => 46,
									'width_tablet' => 100,
									'gap'          => 18,
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
									El::w( 'stx-product-price', array( 'layout' => 'inline' ) ),
									El::w( 'stx-add-to-cart' ),
									El::w( 'stx-product-rating' ),
								)
							),
							El::box(
								array(
									'width'        => 54,
									'width_tablet' => 100,
								),
								array(
									El::w(
										'stx-product-gallery',
										array(
											'ratio'    => '4/3',
											'thumbs'   => 'below',
											'fit'      => 'contain',
											'stage_bg' => '#ffffff',
										)
									),
								)
							),
						)
					),
				)
			),
			El::box(
				array(
					'boxed'      => 1160,
					'dir'        => 'row',
					'dir_mobile' => 'column',
					'gap'        => 14,
					'pad'        => array( 28, 20 ),
					'pad_mobile' => array( 20, 16 ),
				),
				array(
					Blocks::perk( 'award', __( 'Quality checked', 'studiare-extensions' ), __( 'Every item is inspected before shipping', 'studiare-extensions' ) ),
					Blocks::perk( 'gift', __( 'Gift-ready', 'studiare-extensions' ), __( 'Neat packaging, ready to give', 'studiare-extensions' ) ),
					Blocks::perk( 'support', __( 'Friendly support', 'studiare-extensions' ), __( 'Real people answer your questions', 'studiare-extensions' ) ),
				)
			),
			El::box(
				array(
					'boxed'      => 860,
					'pad'        => array( 12, 20, 12 ),
					'pad_mobile' => array( 8, 16 ),
				),
				array(
					El::w(
						'stx-product-tabs',
						array(
							'layout' => 'accordion',
							'tabs'   => array(
								array(
									'_id'    => El::id(),
									'title'  => __( 'Description', 'studiare-extensions' ),
									'source' => 'description',
								),
								array(
									'_id'    => El::id(),
									'title'  => __( 'Specifications', 'studiare-extensions' ),
									'source' => 'specs',
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
			),
			Blocks::related(
				__( 'Picked for you', 'studiare-extensions' ),
				array(
					'source' => 'related',
					'ratio'  => '1/1',
				),
				1160
			),
			El::w( 'stx-mobile-buy-bar' ),
		);
	}
}
