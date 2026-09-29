<?php
/**
 * Hero slider: badge, title, text and two buttons per slide, with an
 * optional picture at the end side.
 *
 * Slides sit in a horizontal scroll-snap track, so they swipe natively and
 * stay usable without JavaScript; home.js adds the dots and autoplay.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Slides extends Home_Base {

	public function get_name(): string {
		return 'stx-slides';
	}

	public function get_title(): string {
		return __( 'Hero slides', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-slides';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_slides', __( 'Slides', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'badge',
			array(
				'label'       => __( 'Small label', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => __( 'Slide title', 'studiare-extensions' ),
				'description' => __( 'Put words between <mark> and </mark> to highlight them.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => '',
			)
		);
		$buttons = array(
			''  => __( 'Button', 'studiare-extensions' ),
			'2' => __( 'Second button', 'studiare-extensions' ),
		);
		foreach ( $buttons as $suffix => $label ) {
			$repeater->add_control(
				'button' . $suffix . '_text',
				array(
					'label'     => $label,
					'type'      => Controls_Manager::TEXT,
					'default'   => '',
					'separator' => 'before',
				)
			);
			$repeater->add_control(
				'button' . $suffix . '_link',
				array(
					'label'   => __( 'Link', 'studiare-extensions' ),
					'type'    => Controls_Manager::URL,
					'dynamic' => array( 'active' => true ),
				)
			);
		}
		$repeater->add_control(
			'image',
			array(
				'label'       => __( 'Picture (optional)', 'studiare-extensions' ),
				'type'        => Controls_Manager::MEDIA,
				'separator'   => 'before',
				'description' => __( 'Shown beside the text on wide screens and under it on phones.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'slides',
			array(
				'label'       => __( 'Slides', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'title'       => __( 'Slide title', 'studiare-extensions' ),
						'text'        => __( 'A short sentence about what visitors find here.', 'studiare-extensions' ),
						'button_text' => __( 'Get started', 'studiare-extensions' ),
					),
				),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->add_control(
			'first_h1',
			array(
				'label'       => __( 'First title is the page\'s main heading (H1)', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Turn off if the page already has an H1.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'     => __( 'Change slides automatically', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'interval',
			array(
				'label'     => __( 'Seconds per slide', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 6,
				'min'       => 3,
				'max'       => 20,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_box', __( 'Box', 'studiare-extensions' ) );

		$this->add_control(
			'look',
			array(
				'label'   => __( 'Look', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'gradient',
				'options' => array(
					'gradient' => __( 'Accent gradient', 'studiare-extensions' ),
					'dark'     => __( 'Dark', 'studiare-extensions' ),
					'soft'     => __( 'Soft accent', 'studiare-extensions' ),
					'card'     => __( 'Card', 'studiare-extensions' ),
				),
			)
		);

		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => __( 'Minimum height', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 200,
						'max' => 800,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-slides' => '--stx-slides-h: {{SIZE}}{{UNIT}};' ),
			)
		);

		// `background` (not only the colour) so a flat colour replaces the gradient.
		$this->add_control(
			'box_bg',
			array(
				'label'     => __( 'Background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-slides' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'box_radius',
			array(
				'label'      => __( 'Border radius', 'studiare-extensions' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .stx-slides' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'box_padding',
			array(
				'label'      => __( 'Padding', 'studiare-extensions' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .stx-slides__slide' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_text_style( 'title', '.stx-slides__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'text', '.stx-slides__text', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s      = $this->get_settings_for_display();
		$slides = array_values( (array) $s['slides'] );
		$count  = count( $slides );

		if ( ! $count ) {
			return;
		}

		$base_id  = 'stx-slides-' . $this->get_id();
		$interval = 'yes' === $s['autoplay'] && $count > 1 ? max( 3, (int) $s['interval'] ) * 1000 : 0;

		printf(
			'<div class="stx-slides stx-slides--%1$s" data-stx-slides data-interval="%2$d" role="region" aria-roledescription="%3$s" aria-label="%4$s">',
			esc_attr( $s['look'] ),
			(int) $interval,
			esc_attr__( 'carousel', 'studiare-extensions' ),
			esc_attr( wp_strip_all_tags( (string) $slides[0]['title'] ) )
		);
		echo '<span class="stx-slides__glow" aria-hidden="true"></span>';
		echo '<div class="stx-slides__track">';

		foreach ( $slides as $index => $slide ) {
			$this->render_slide( $slide, $index, $count, $base_id, 0 === $index && 'yes' === $s['first_h1'] );
		}

		echo '</div>';

		if ( $count > 1 ) {
			echo '<div class="stx-slides__dots">';
			for ( $i = 0; $i < $count; $i++ ) {
				printf(
					'<button type="button" class="stx-slides__dot%1$s" aria-controls="%2$s" aria-label="%3$s"%4$s></button>',
					0 === $i ? ' is-active' : '',
					esc_attr( $base_id . '-' . $i ),
					/* translators: 1: slide number, 2: number of slides. */
					esc_attr( sprintf( __( 'Slide %1$s of %2$s', 'studiare-extensions' ), self::num( $i + 1 ), self::num( $count ) ) ),
					0 === $i ? ' aria-current="true"' : ''
				);
			}
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * @param array  $slide   Slide settings.
	 * @param int    $index   Position.
	 * @param int    $count   Number of slides.
	 * @param string $base_id ID prefix.
	 * @param bool   $is_h1   Whether the title is the page's H1.
	 */
	private function render_slide( array $slide, int $index, int $count, string $base_id, bool $is_h1 ): void {
		$tag     = $is_h1 ? 'h1' : 'h2';
		$buttons = '';

		$variants = array(
			''  => 'primary',
			'2' => 'secondary',
		);
		foreach ( $variants as $suffix => $variant ) {
			$text = (string) ( $slide[ 'button' . $suffix . '_text' ] ?? '' );
			if ( '' === $text ) {
				continue;
			}

			$key = 'button' . $suffix . '_' . $index;
			$this->add_render_attribute( $key, 'class', 'stx-slides__btn stx-slides__btn--' . $variant );
			if ( ! empty( $slide[ 'button' . $suffix . '_link' ]['url'] ) ) {
				$this->add_link_attributes( $key, $slide[ 'button' . $suffix . '_link' ] );
			}
			$buttons .= '<a ' . $this->get_render_attribute_string( $key ) . '>' . esc_html( $text ) . '</a>';
		}

		$image = ! empty( $slide['image']['id'] )
			? wp_get_attachment_image(
				(int) $slide['image']['id'],
				'large',
				false,
				array(
					'class'   => 'stx-slides__img',
					'loading' => 0 === $index ? 'eager' : 'lazy',
				)
			)
			: '';

		printf(
			'<div class="stx-slides__slide%1$s" id="%2$s" role="group" aria-roledescription="%3$s" aria-label="%4$s">',
			'' !== $image ? ' has-image' : '',
			esc_attr( $base_id . '-' . $index ),
			esc_attr__( 'slide', 'studiare-extensions' ),
			/* translators: 1: slide number, 2: number of slides. */
			esc_attr( sprintf( __( 'Slide %1$s of %2$s', 'studiare-extensions' ), self::num( $index + 1 ), self::num( $count ) ) )
		);
		echo '<div class="stx-slides__body">';
		if ( '' !== (string) $slide['badge'] ) {
			echo '<span class="stx-slides__badge">' . esc_html( $slide['badge'] ) . '</span>';
		}
		printf( '<%1$s class="stx-slides__title">%2$s</%1$s>', esc_html( $tag ), wp_kses_post( nl2br( (string) $slide['title'] ) ) );
		if ( '' !== (string) $slide['text'] ) {
			echo '<p class="stx-slides__text">' . wp_kses_post( nl2br( (string) $slide['text'] ) ) . '</p>';
		}
		if ( '' !== $buttons ) {
			echo '<div class="stx-slides__actions">' . $buttons . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
		echo '</div>';
		if ( '' !== $image ) {
			echo '<div class="stx-slides__media">' . $image . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
		}
		echo '</div>';
	}
}
