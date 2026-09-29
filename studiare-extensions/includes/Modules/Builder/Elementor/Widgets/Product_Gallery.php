<?php
/**
 * Product images with thumbnails, sale badge, zoom and the course intro
 * video (Studiare's `_studiare_course_video` / Aparat fields).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Product_Gallery extends Base {

	public function get_name(): string {
		return 'stx-product-gallery';
	}

	public function get_title(): string {
		return __( 'Product gallery', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-images';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Gallery', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'thumbs',
			array(
				'label'   => __( 'Thumbnails', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'below',
				'options' => array(
					'below' => __( 'Below the image', 'studiare-extensions' ),
					'side'  => __( 'Beside the image', 'studiare-extensions' ),
					'none'  => __( 'Hidden', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'   => __( 'Image shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1/1',
				'options' => array(
					'1/1'   => __( 'Square', 'studiare-extensions' ),
					'4/3'   => '4:3',
					'3/2'   => '3:2',
					'16/10' => '16:10',
					'16/9'  => __( '16:9 (video)', 'studiare-extensions' ),
					'auto'  => __( 'Original', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'fit',
			array(
				'label'     => __( 'Fit', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => __( 'Fill (crop)', 'studiare-extensions' ),
					'contain' => __( 'Fit (no crop)', 'studiare-extensions' ),
				),
				'condition' => array( 'ratio!' => 'auto' ),
			)
		);

		$this->add_control(
			'sale_badge',
			array(
				'label'   => __( 'Discount badge', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'video',
			array(
				'label'       => __( 'Intro video', 'studiare-extensions' ),
				'description' => __( 'Plays the course intro video set in Studiare\'s product settings.', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'zoom',
			array(
				'label'   => __( 'Open image in lightbox', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Gallery', 'studiare-extensions' ) );
		$this->add_responsive_control(
			'radius',
			array(
				'label'      => __( 'Border radius', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-gallery' => '--stx-gallery-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'stage_bg',
			array(
				'label'     => __( 'Image background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-gallery' => '--stx-gallery-bg: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'thumb_size',
			array(
				'label'      => __( 'Thumbnail size', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 40,
						'max' => 140,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-gallery' => '--stx-thumb: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'thumbs!' => 'none' ),
			)
		);
		$this->add_control(
			'thumb_active',
			array(
				'label'     => __( 'Active thumbnail border', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-gallery' => '--stx-thumb-active: {{VALUE}};' ),
				'condition' => array( 'thumbs!' => 'none' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$ids = array_values( array_filter( array_merge( array( (int) $product->get_image_id() ), array_map( 'intval', $product->get_gallery_image_ids() ) ) ) );
				$ids = array_values( array_unique( $ids ) );

				$style = 'auto' !== $s['ratio'] ? sprintf( '--stx-ratio:%s;--stx-fit:%s', $s['ratio'], $s['fit'] ) : '';

				printf(
					'<div class="stx-gallery stx-gallery--thumbs-%1$s%2$s" style="%3$s" data-stx-gallery%4$s>',
					esc_attr( $s['thumbs'] ),
					'auto' === $s['ratio'] ? ' stx-gallery--natural' : '',
					esc_attr( $style ),
					'yes' === $s['zoom'] ? ' data-stx-zoom' : ''
				);

				echo '<div class="stx-gallery__stage">';

				if ( $ids ) {
					foreach ( $ids as $index => $image_id ) {
						$full = (string) wp_get_attachment_image_url( $image_id, 'full' );
						echo '<figure class="stx-gallery__slide' . ( 0 === $index ? ' is-active' : '' ) . '" data-full="' . esc_url( $full ) . '">';
						echo wp_get_attachment_image(
							$image_id,
							'woocommerce_single',
							false,
							array(
								'class'   => 'stx-gallery__img',
								'loading' => 0 === $index ? 'eager' : 'lazy',
							)
						);
						echo '</figure>';
					}
				} else {
					echo '<figure class="stx-gallery__slide is-active">' . wc_placeholder_img( 'woocommerce_single', array( 'class' => 'stx-gallery__img' ) ) . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce markup.
				}

				$video = 'yes' === $s['video'] ? $this->intro_video( $product->get_id() ) : '';
				if ( '' !== $video ) {
					printf(
						'<button type="button" class="stx-gallery__play" data-stx-video="%1$s" aria-label="%2$s">%3$s</button>',
						esc_url( $video ),
						esc_attr__( 'Play intro video', 'studiare-extensions' ),
						self::icon( 'play' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
					);
				}

				if ( 'yes' === $s['sale_badge'] && $product->is_on_sale() && $product->is_type( 'simple' ) && (float) $product->get_regular_price() > 0 ) {
					$percent = (int) round( ( 1 - (float) $product->get_price() / (float) $product->get_regular_price() ) * 100 );
					if ( $percent > 0 ) {
						/* translators: {percent} is replaced with the discount percentage. */
						echo '<span class="stx-gallery__badge">' . esc_html( self::digits( str_replace( '{percent}', (string) $percent, __( '{percent}% off', 'studiare-extensions' ) ) ) ) . '</span>';
					}
				}

				echo '</div>';

				if ( 'none' !== $s['thumbs'] && count( $ids ) > 1 ) {
					echo '<div class="stx-gallery__thumbs" role="tablist" aria-label="' . esc_attr__( 'Product images', 'studiare-extensions' ) . '">';
					foreach ( $ids as $index => $image_id ) {
						printf(
							'<button type="button" class="stx-gallery__thumb%1$s" data-index="%2$d" aria-label="%3$s">%4$s</button>',
							0 === $index ? ' is-active' : '',
							(int) $index,
							/* translators: %d: image number. */
							esc_attr( sprintf( __( 'Image %d', 'studiare-extensions' ), $index + 1 ) ),
							wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'loading' => 'lazy' ) )
						);
					}
					echo '</div>';
				}

				echo '</div>';
			}
		);
	}

	/**
	 * Intro video URL from Studiare's product fields.
	 *
	 * @param int $product_id Product ID.
	 */
	private function intro_video( int $product_id ): string {
		$aparat = (string) get_post_meta( $product_id, '_studiare_course_video_aparat', true );
		if ( '' !== $aparat ) {
			return 'https://www.aparat.com/v/' . rawurlencode( basename( untrailingslashit( $aparat ) ) );
		}

		return (string) get_post_meta( $product_id, '_studiare_course_video', true );
	}
}
