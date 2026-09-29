<?php
/**
 * Timeline ("our story"): milestones with a year, a title and a short text,
 * on a vertical line, alternating on both sides of it, or in a row of
 * steps. Every look is a single column on phones.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Timeline extends Page_Base {

	public function get_name(): string {
		return 'stx-timeline';
	}

	public function get_title(): string {
		return __( 'Timeline', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-time-line';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'timeline', 'history', 'story', 'milestones', 'تاریخچه', 'داستان' ) );
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Milestones', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'year',
			array(
				'label'   => __( 'Year or date', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '1400',
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Milestone', 'studiare-extensions' ),
				'label_block' => true,
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

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Milestones', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'title' => __( 'Milestone', 'studiare-extensions' ) ),
					array( 'title' => __( 'Milestone', 'studiare-extensions' ) ),
					array( 'title' => __( 'Milestone', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ year }}} · {{{ title }}}',
			)
		);

		$this->add_control(
			'look',
			array(
				'label'   => __( 'Style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'line',
				'options' => array(
					'line'      => __( 'Line on the side', 'studiare-extensions' ),
					'alternate' => __( 'Both sides of the line', 'studiare-extensions' ),
					'row'       => __( 'Steps in a row', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => __( 'Title tag', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Milestones', 'studiare-extensions' ) );
		$this->add_control(
			'line_color',
			array(
				'label'     => __( 'Line and dots', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-tl' => '--stx-tl-accent: {{VALUE}};' ),
			)
		);
		$this->add_box_style( 'card', '.stx-tl__card', array( 'shadow' => true ) );
		$this->add_text_style( 'year', '.stx-tl__year', array( 'label' => __( 'Year', 'studiare-extensions' ) ) );
		$this->add_text_style( 'title', '.stx-tl__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'text', '.stx-tl__text', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( empty( $s['items'] ) ) {
			return;
		}

		$tag = in_array( $s['title_tag'], array( 'h2', 'h3', 'h4', 'div' ), true ) ? $s['title_tag'] : 'h3';

		printf( '<ol class="stx-tl stx-tl--%s">', esc_attr( $s['look'] ) );
		foreach ( $s['items'] as $item ) {
			echo '<li class="stx-tl__item"><span class="stx-tl__dot" aria-hidden="true"></span><div class="stx-tl__card">';
			if ( '' !== trim( (string) $item['year'] ) ) {
				printf( '<span class="stx-tl__year">%s</span>', esc_html( self::digits( $item['year'] ) ) );
			}
			printf( '<%1$s class="stx-tl__title">%2$s</%1$s>', tag_escape( $tag ), esc_html( $item['title'] ) );
			if ( '' !== trim( (string) $item['text'] ) ) {
				printf( '<p class="stx-tl__text">%s</p>', nl2br( esc_html( self::digits( $item['text'] ) ) ) );
			}
			echo '</div></li>';
		}
		echo '</ol>';
	}
}
