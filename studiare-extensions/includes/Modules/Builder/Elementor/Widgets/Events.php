<?php
/**
 * Upcoming events (workshops, live sessions): a date tile, title, time, place
 * and a sign-up link. Day and month are typed as text, so any calendar
 * (Jalali or Gregorian) reads naturally.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Events extends Home_Base {

	public function get_name(): string {
		return 'stx-events';
	}

	public function get_title(): string {
		return __( 'Events', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Events', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'day',
			array(
				'label'   => __( 'Day', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '12',
			)
		);
		$repeater->add_control(
			'month',
			array(
				'label'   => __( 'Month', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Workshop title', 'studiare-extensions' ),
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'time',
			array(
				'label'   => __( 'Time', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'place',
			array(
				'label'   => __( 'Place', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'link_text',
			array(
				'label'   => __( 'Link text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Sign up', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'studiare-extensions' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Events', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'title' => __( 'Workshop title', 'studiare-extensions' ) ),
					array( 'title' => __( 'Workshop title', 'studiare-extensions' ) ),
					array( 'title' => __( 'Workshop title', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->add_columns_control( '.stx-events', array( 3, 2, 1 ) );

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
		$this->add_gap_control( '.stx-events' );
		$this->add_box_style( 'card', '.stx-event' );
		$this->add_control(
			'date_color',
			array(
				'label'     => __( 'Date colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-event__date' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'date_bg',
			array(
				'label'     => __( 'Date background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-event__date' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'title', '.stx-event__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( empty( $s['items'] ) ) {
			return;
		}

		printf( '<div class="stx-events%s">', 'yes' === $s['mobile_scroll'] ? ' stx-swipe' : '' );

		$detail_icons = array(
			'time'  => 'clock',
			'place' => 'map-pin',
		);

		foreach ( $s['items'] as $index => $item ) {
			$details = '';
			foreach ( $detail_icons as $field => $icon ) {
				if ( '' !== (string) $item[ $field ] ) {
					$details .= '<span class="stx-event__detail">' . self::icon( $icon ) . '<span>' . esc_html( self::digits( $item[ $field ] ) ) . '</span></span>';
				}
			}

			$link = '';
			if ( '' !== (string) $item['link_text'] && ! empty( $item['link']['url'] ) ) {
				$key = 'link_' . $index;
				$this->add_link_attributes( $key, $item['link'] );
				$this->add_render_attribute( $key, 'class', 'stx-event__link' );
				$link = '<a ' . $this->get_render_attribute_string( $key ) . '>' . esc_html( $item['link_text'] ) . '<span class="screen-reader-text">: ' . esc_html( $item['title'] ) . '</span> <span aria-hidden="true">' . ( is_rtl() ? '←' : '→' ) . '</span></a>';
			}

			printf(
				'<article class="stx-event"><span class="stx-event__date"><b>%1$s</b><span>%2$s</span></span><div class="stx-event__body"><h3 class="stx-event__title">%3$s</h3>%4$s%5$s</div></article>',
				esc_html( self::digits( $item['day'] ) ),
				esc_html( $item['month'] ),
				esc_html( $item['title'] ),
				$details, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				$link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
		}

		echo '</div>';
	}
}
