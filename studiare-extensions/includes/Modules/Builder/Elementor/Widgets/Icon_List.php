<?php
/**
 * List of items with bundled icons or step numbers (contact details,
 * features, links, the steps of a learning path).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Icon_List extends Base {

	public function get_name(): string {
		return 'stx-icon-list';
	}

	public function get_title(): string {
		/* translators: %s: widget name. */
		return sprintf( __( '%s (Studiare+)', 'studiare-extensions' ), __( 'Icon list', 'studiare-extensions' ) );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Items', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'text',
			array(
				'label'       => __( 'Text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'List item', 'studiare-extensions' ),
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'icon',
			array(
				'label'   => __( 'Icon', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'check',
				'options' => self::icon_options(),
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
		$repeater->add_control(
			'ltr',
			array(
				'label'       => __( 'Left-to-right text', 'studiare-extensions' ),
				'description' => __( 'For phone numbers, emails and URLs.', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Items', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'text' => __( 'List item', 'studiare-extensions' ),
						'icon' => 'check',
					),
					array(
						'text' => __( 'List item', 'studiare-extensions' ),
						'icon' => 'check',
					),
				),
				'title_field' => '{{{ text }}}',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'vertical',
				'options' => array(
					'vertical'   => __( 'Vertical', 'studiare-extensions' ),
					'horizontal' => __( 'Horizontal', 'studiare-extensions' ),
					'grid'       => __( 'Two columns', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'icon_style',
			array(
				'label'   => __( 'Icon style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'plain',
				'options' => array(
					'plain'  => __( 'Plain', 'studiare-extensions' ),
					'boxed'  => __( 'In a box', 'studiare-extensions' ),
					'number' => __( 'Numbers (1, 2, 3…)', 'studiare-extensions' ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'List', 'studiare-extensions' ) );

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Space between', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-ilist' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-ilist__icon' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => __( 'Icon size', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 48,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-ilist__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_text_style( 'text', '.stx-ilist__text', array( 'hover' => 'a.stx-ilist__item:hover .stx-ilist__text' ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( empty( $s['items'] ) ) {
			return;
		}

		// Numbered steps are an ordered list for screen readers too.
		$list_tag = 'number' === $s['icon_style'] ? 'ol' : 'ul';

		echo '<' . esc_html( $list_tag ) . ' class="stx-ilist stx-ilist--' . esc_attr( $s['layout'] ) . ' stx-ilist--icon-' . esc_attr( $s['icon_style'] ) . '">';

		foreach ( $s['items'] as $index => $item ) {
			if ( 'number' === $s['icon_style'] ) {
				$icon = '<span class="stx-ilist__icon-wrap stx-ilist__num" aria-hidden="true">' . esc_html( self::num( $index + 1 ) ) . '</span>';
			} else {
				$icon = '' !== $item['icon'] ? '<span class="stx-ilist__icon-wrap">' . self::icon( $item['icon'], 'stx-ilist__icon' ) . '</span>' : '';
			}
			$text = sprintf( '<span class="stx-ilist__text"%s>%s</span>', 'yes' === $item['ltr'] ? ' dir="ltr"' : '', esc_html( $item['text'] ) );

			echo '<li>';
			if ( ! empty( $item['link']['url'] ) ) {
				$key = 'link_' . $index;
				$this->add_link_attributes( $key, $item['link'] );
				$this->add_render_attribute( $key, 'class', 'stx-ilist__item' );
				echo '<a ' . $this->get_render_attribute_string( $key ) . '>' . $icon . $text . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			} else {
				echo '<span class="stx-ilist__item">' . $icon . $text . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</li>';
		}

		echo '</' . esc_html( $list_tag ) . '>';
	}
}
