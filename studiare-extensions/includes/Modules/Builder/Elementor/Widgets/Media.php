<?php
/**
 * Picture or video poster in a frame (none, card, or a dark "screen"), with a
 * play button that opens the video in the lightbox (YouTube, Aparat, Vimeo
 * or a video file). Until a picture is chosen it shows a labelled striped
 * placeholder, like the supplied designs.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Cards;

defined( 'ABSPATH' ) || exit;

final class Media extends Home_Base {

	public function get_name(): string {
		return 'stx-media';
	}

	public function get_title(): string {
		return __( 'Picture / video', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-youtube';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Picture / video', 'studiare-extensions' ) );

		$this->add_control(
			'image',
			array(
				'label' => __( 'Picture', 'studiare-extensions' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$this->add_control(
			'placeholder',
			array(
				'label'       => __( 'Placeholder text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Your picture here', 'studiare-extensions' ),
				'description' => __( 'Shown until you choose a picture.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'video',
			array(
				'label'       => __( 'Video link', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'https://www.aparat.com/v/…',
				'description' => __( 'YouTube, Aparat, Vimeo or a video file. Adds a play button that opens the video.', 'studiare-extensions' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'link',
			array(
				'label'     => __( 'Link', 'studiare-extensions' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'video' => '' ),
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'   => __( 'Shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '16/10',
				'options' => array_merge(
					Product_Grid::ratio_options(),
					array(
						'16/11' => '16:11',
						'21/9'  => '21:9',
					)
				),
			)
		);

		$this->add_control(
			'frame',
			array(
				'label'   => __( 'Frame', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'   => __( 'None', 'studiare-extensions' ),
					'card'   => __( 'Card with border', 'studiare-extensions' ),
					'device' => __( 'Dark screen', 'studiare-extensions' ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Picture / video', 'studiare-extensions' ) );
		$this->add_box_style( 'frame', '.stx-media', array( 'shadow' => true ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$video = trim( (string) $s['video'] );

		$picture = ! empty( $s['image']['id'] )
			? wp_get_attachment_image(
				(int) $s['image']['id'],
				'large',
				false,
				array(
					'class'   => 'stx-media__img',
					'loading' => 'lazy',
				)
			)
			// A "screen" frame stands for a video, so its placeholder shows a play sign.
			: Cards::placeholder( (string) $s['placeholder'], 'device' === $s['frame'] ? 'play' : 'image' );

		$stage = $picture;
		if ( '' !== $video ) {
			$stage .= sprintf(
				'<button type="button" class="stx-media__play" data-stx-video="%1$s" aria-label="%2$s">%3$s</button>',
				esc_url( $video ),
				esc_attr__( 'Play video', 'studiare-extensions' ),
				self::icon( 'play' )
			);
		} elseif ( ! empty( $s['link']['url'] ) ) {
			$this->add_link_attributes( 'link', $s['link'] );
			$this->add_render_attribute( 'link', 'class', 'stx-media__link' );
			$stage = '<a ' . $this->get_render_attribute_string( 'link' ) . '>' . $stage . '</a>';
		}

		printf(
			'<div class="stx-media stx-media--%1$s%2$s"><div class="stx-media__stage" style="--stx-ratio:%3$s">%4$s</div></div>',
			esc_attr( $s['frame'] ),
			'' !== $video ? ' has-video' : '',
			esc_attr( $s['ratio'] ),
			$stage // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup, placeholder and escaped attributes.
		);
	}
}
