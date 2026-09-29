<?php
/**
 * Testimonials: star rating, quote, and the person's photo (or initial),
 * name and course.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Testimonials extends Home_Base {

	private const STAR = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="m12 3 2.6 5.6 6 .8-4.4 4.2 1.1 6.1L12 16.9 6.7 19.7l1.1-6.1L3.4 9.4l6-.8z"/></svg>';

	public function get_name(): string {
		return 'stx-testimonials';
	}

	public function get_title(): string {
		return __( 'Testimonials', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-testimonial';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Testimonials', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Quote', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => __( 'What the student said about the course.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'name',
			array(
				'label'   => __( 'Name', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Student name', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'role',
			array(
				'label'       => __( 'Course or role', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'rating',
			array(
				'label'   => __( 'Stars', 'studiare-extensions' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 0,
				'max'     => 5,
			)
		);
		$repeater->add_control(
			'photo',
			array(
				'label'       => __( 'Photo', 'studiare-extensions' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => __( 'Without a photo, the first letter of the name is shown.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Testimonials', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'name' => __( 'Student name', 'studiare-extensions' ) ),
					array( 'name' => __( 'Student name', 'studiare-extensions' ) ),
					array( 'name' => __( 'Student name', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ name }}}',
			)
		);

		$this->add_control(
			'look',
			array(
				'label'   => __( 'Card style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'soft',
				'options' => array(
					'soft' => __( 'Tinted', 'studiare-extensions' ),
					'card' => __( 'White card', 'studiare-extensions' ),
				),
			)
		);

		$this->add_columns_control( '.stx-quotes', array( 3, 2, 1 ) );

		$this->add_control(
			'mobile_scroll',
			array(
				'label'       => __( 'Swipe row on phones', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Cards sit side by side and scroll sideways instead of stacking.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Cards', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-quotes' );
		$this->add_box_style( 'card', '.stx-quote' );
		$this->add_control(
			'star_color',
			array(
				'label'     => __( 'Star colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-quote__stars' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'text', '.stx-quote__text', array( 'label' => __( 'Quote', 'studiare-extensions' ) ) );
		$this->add_text_style( 'name', '.stx-quote__name', array( 'label' => __( 'Name', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( empty( $s['items'] ) ) {
			return;
		}

		printf( '<div class="stx-quotes stx-quotes--%1$s%2$s">', esc_attr( $s['look'] ), 'yes' === $s['mobile_scroll'] ? ' stx-swipe' : '' );

		foreach ( $s['items'] as $item ) {
			$stars  = max( 0, min( 5, (int) $item['rating'] ) );
			$name   = (string) $item['name'];
			$avatar = ! empty( $item['photo']['id'] )
				? wp_get_attachment_image(
					(int) $item['photo']['id'],
					'thumbnail',
					false,
					array(
						'class'   => 'stx-quote__avatar',
						'alt'     => '',
						'loading' => 'lazy',
					)
				)
				: '<span class="stx-quote__avatar" aria-hidden="true">' . esc_html( mb_substr( trim( $name ), 0, 1 ) ) . '</span>';

			echo '<figure class="stx-quote">';
			if ( $stars ) {
				printf(
					'<span class="stx-quote__stars" role="img" aria-label="%1$s">%2$s</span>',
					/* translators: %s: number of stars out of 5. */
					esc_attr( sprintf( __( 'Rated %s out of 5', 'studiare-extensions' ), self::num( $stars ) ) ),
					str_repeat( self::STAR, $stars ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				);
			}
			echo '<blockquote class="stx-quote__text">' . wp_kses_post( nl2br( (string) $item['text'] ) ) . '</blockquote>';
			echo '<figcaption class="stx-quote__who">' . $avatar . '<span class="stx-quote__id"><span class="stx-quote__name">' . esc_html( $name ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup or escaped initial.
			if ( '' !== (string) $item['role'] ) {
				echo '<span class="stx-quote__role">' . esc_html( $item['role'] ) . '</span>';
			}
			echo '</span></figcaption></figure>';
		}

		echo '</div>';
	}
}
