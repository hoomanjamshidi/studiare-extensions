<?php
/**
 * Key numbers ("+71 articles", "15 years"): value/label pairs with an
 * optional icon, plain (taking the colours of the box they sit in), in a
 * floating card, as tiles or as a band of large numbers.
 *
 * "Count up" animates each number from zero when it scrolls into view
 * (home.js). The real number is in the markup, so search engines, screen
 * readers, reduced motion and visitors without JavaScript get it as is.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Stats extends Home_Base {

	public function get_name(): string {
		return 'stx-stats';
	}

	public function get_title(): string {
		return __( 'Key numbers', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-counter';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Numbers', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'value',
			array(
				'label'   => __( 'Number', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '+100',
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'   => __( 'Label', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Students', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'icon',
			array(
				'label'   => __( 'Icon', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => self::icon_options(),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Numbers', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'value' => '+100',
						'label' => __( 'Students', 'studiare-extensions' ),
					),
					array(
						'value' => '+10',
						'label' => __( 'Courses', 'studiare-extensions' ),
					),
				),
				'title_field' => '{{{ value }}} {{{ label }}}',
			)
		);

		$this->add_control(
			'look',
			array(
				'label'   => __( 'Style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'plain',
				'options' => array(
					'plain' => __( 'Plain', 'studiare-extensions' ),
					'card'  => __( 'Floating card with dividers', 'studiare-extensions' ),
					'tiles' => __( 'Tiles', 'studiare-extensions' ),
					'band'  => __( 'Large numbers', 'studiare-extensions' ),
				),
			)
		);

		$this->add_columns_control( '.stx-stats', array( 4, 2, 2 ), 6, array( 'condition' => array( 'look' => array( 'tiles', 'band' ) ) ) );

		$this->add_control(
			'count_up',
			array(
				'label'       => __( 'Count up', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Numbers count up from zero when they scroll into view.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Numbers', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-stats' );
		$this->add_box_style( 'box', '.stx-stats', array( 'shadow' => true ) );
		$this->add_text_style( 'value', '.stx-stats__value', array( 'label' => __( 'Number', 'studiare-extensions' ) ) );
		$this->add_text_style( 'label', '.stx-stats__label', array( 'label' => __( 'Label', 'studiare-extensions' ) ) );
		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-stats__icon' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( empty( $s['items'] ) ) {
			return;
		}

		printf( '<dl class="stx-stats stx-stats--%s">', esc_attr( $s['look'] ) );
		foreach ( $s['items'] as $item ) {
			$icon = (string) ( $item['icon'] ?? '' );
			// Label first in the markup so screen readers say "Students: +100"; CSS puts the number on top.
			// The icon sits in the <dd>, because a <dl> row may only hold <dt> and <dd>.
			printf(
				'<div class="stx-stats__item"><dt class="stx-stats__label">%2$s</dt><dd class="stx-stats__value">%3$s<span class="stx-stats__num"%4$s>%1$s</span></dd></div>',
				esc_html( self::digits( $item['value'] ) ),
				esc_html( $item['label'] ),
				'' !== $icon ? '<span class="stx-stats__icon" aria-hidden="true">' . self::icon( $icon ) . '</span>' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
				'yes' === $s['count_up'] ? ' data-stx-count' : ''
			);
		}
		echo '</dl>';
	}
}
