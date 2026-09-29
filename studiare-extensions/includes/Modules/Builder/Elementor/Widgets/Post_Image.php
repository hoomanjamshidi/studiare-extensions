<?php
/**
 * The post's featured image, with its caption. Near the top of a post it is
 * usually the page's largest picture, so it loads first by default (see
 * Picture for the loading hints); a post without one shows nothing, or the
 * striped placeholder while editing.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Cards;
use StudiareExt\Modules\Builder\Elementor\Picture;

defined( 'ABSPATH' ) || exit;

final class Post_Image extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-image';
	}

	public function get_title(): string {
		return __( 'Featured image', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-featured-image';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Picture', 'studiare-extensions' ) );
		$this->add_post_note();

		$this->add_responsive_control(
			'ratio',
			array(
				'label'     => __( 'Picture shape', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '16/9',
				'options'   => array_merge(
					array( '' => __( 'Original', 'studiare-extensions' ) ),
					array( '21/9' => '21:9' ),
					Product_Grid::ratio_options()
				),
				'selectors' => array( '{{WRAPPER}} .stx-pimage__frame' => 'aspect-ratio: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'caption',
			array(
				'label'   => __( 'Caption', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'loading',
			array(
				'label'       => __( 'Loading', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'high',
				'options'     => array(
					'high' => __( 'Top of the page (load first)', 'studiare-extensions' ),
					'lazy' => __( 'Lower on the page (lazy)', 'studiare-extensions' ),
				),
				'description' => __( 'Near the top, the picture is usually the largest thing on the screen, so loading it first improves PageSpeed.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Picture', 'studiare-extensions' ) );
		$this->add_responsive_control(
			'radius',
			array(
				'label'      => __( 'Border radius', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-pimage__frame' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_text_style( 'caption', '.stx-pimage__caption', array( 'label' => __( 'Caption', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_post(
			function ( \WP_Post $post ) use ( $s ) {
				$id      = (int) get_post_thumbnail_id( $post );
				$picture = $id ? Picture::html(
					array( 'id' => $id ),
					array(),
					array(
						'loading'    => $s['loading'],
						'sizes'      => '(max-width: 1240px) 100vw, 1240px',
						'class'      => 'stx-pimage__img',
						'wrap_class' => 'stx-pimage__pic',
					)
				) : '';

				if ( '' === $picture ) {
					if ( $this->in_editor() ) {
						echo '<figure class="stx-pimage"><div class="stx-pimage__frame stx-pimage__frame--empty">' . Cards::placeholder( __( 'Featured image', 'studiare-extensions' ) ) . '</div></figure>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Cards.
					}
					return;
				}

				$caption = 'yes' === $s['caption'] ? (string) wp_get_attachment_caption( $id ) : '';

				printf(
					'<figure class="stx-pimage"><div class="stx-pimage__frame">%1$s</div>%2$s</figure>',
					$picture, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
					'' !== $caption ? '<figcaption class="stx-pimage__caption">' . esc_html( $caption ) . '</figcaption>' : ''
				);
			}
		);
	}
}
