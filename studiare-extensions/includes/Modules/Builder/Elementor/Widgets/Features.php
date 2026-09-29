<?php
/**
 * Feature list: icon (or a character such as ٪ or ∞), title and one line of
 * text per item, as a single bar with dividers, open tiles or cards.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Features extends Home_Base {

	public function get_name(): string {
		return 'stx-features';
	}

	public function get_title(): string {
		return __( 'Features', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-info-box';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Items', 'studiare-extensions' ) );

		$repeater = new Repeater();
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
			'mark',
			array(
				'label'       => __( 'Or a character', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '٪',
				'description' => __( 'Shown instead of the icon, e.g. ٪ ∞ ✓ or a number.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Feature', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => '',
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
				'label'       => __( 'Items', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'title' => __( 'Feature', 'studiare-extensions' ) ),
					array( 'title' => __( 'Feature', 'studiare-extensions' ) ),
					array( 'title' => __( 'Feature', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'strip',
				'options' => array(
					'strip' => __( 'One bar with dividers', 'studiare-extensions' ),
					'tiles' => __( 'Open tiles', 'studiare-extensions' ),
					'cards' => __( 'Cards', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'icon_style',
			array(
				'label'   => __( 'Icon box', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'soft',
				'options' => array(
					'soft'    => __( 'Soft accent', 'studiare-extensions' ),
					'outline' => __( 'White with border', 'studiare-extensions' ),
				),
			)
		);

		$this->add_columns_control( '.stx-feats', array( 4, 2, 1 ) );

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Items', 'studiare-extensions' ) );
		$this->add_box_style( 'box', '.stx-feats', array( 'shadow' => true ) );
		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-feat__icon' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'title', '.stx-feat__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'text', '.stx-feat__text', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( empty( $s['items'] ) ) {
			return;
		}

		printf( '<ul class="stx-feats stx-feats--%1$s stx-feats--icon-%2$s">', esc_attr( $s['layout'] ), esc_attr( $s['icon_style'] ) );

		foreach ( $s['items'] as $index => $item ) {
			if ( '' !== trim( (string) $item['mark'] ) ) {
				$icon = '<span class="stx-feat__mark">' . esc_html( self::digits( $item['mark'] ) ) . '</span>';
			} else {
				$icon = '' !== $item['icon'] ? self::icon( $item['icon'] ) : '';
			}

			$body = '<span class="stx-feat__title">' . esc_html( $item['title'] ) . '</span>';
			if ( '' !== (string) $item['text'] ) {
				$body .= '<span class="stx-feat__text">' . esc_html( self::digits( $item['text'] ) ) . '</span>';
			}

			$inner = ( '' !== $icon ? '<span class="stx-feat__icon" aria-hidden="true">' . $icon . '</span>' : '' ) . '<span class="stx-feat__body">' . $body . '</span>';

			if ( ! empty( $item['link']['url'] ) ) {
				$key = 'link_' . $index;
				$this->add_link_attributes( $key, $item['link'] );
				$this->add_render_attribute( $key, 'class', 'stx-feat' );
				echo '<li><a ' . $this->get_render_attribute_string( $key ) . '>' . $inner . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			} else {
				echo '<li><div class="stx-feat">' . $inner . '</div></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			}
		}

		echo '</ul>';
	}
}
