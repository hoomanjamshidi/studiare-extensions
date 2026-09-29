<?php
/**
 * Course facts / product details: duration, lessons, level, students,
 * rating, SKU, custom fields… as a list, tiles or an inline row.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Info extends Base {

	public function get_name(): string {
		return 'stx-product-info';
	}

	public function get_title(): string {
		return __( 'Course & product facts', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Facts', 'studiare-extensions' ) );
		$this->add_sample_note();

		$sources = array();
		foreach ( Parts::info_sources() as $key => $source ) {
			$sources[ $key ] = $source['label'];
		}

		$repeater = new Repeater();
		$repeater->add_control(
			'source',
			array(
				'label'   => __( 'Show', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'duration',
				'options' => $sources,
			)
		);
		$repeater->add_control(
			'extra',
			array(
				'label'       => __( 'Field key / text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Custom field: the meta key. Fixed text: the value to show.', 'studiare-extensions' ),
				'condition'   => array( 'source' => array( 'meta', 'text' ) ),
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'       => __( 'Label', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Automatic', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'suffix',
			array(
				'label'   => __( 'After the value', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'icon',
			array(
				'label'   => __( 'Icon', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array( 'auto' => __( 'Automatic', 'studiare-extensions' ) ) + self::icon_options(),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Items', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'source' => 'duration' ),
					array( 'source' => 'lessons' ),
					array( 'source' => 'level' ),
					array( 'source' => 'students' ),
				),
				'title_field' => '{{{ label || source }}}',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'list',
				'options' => array(
					'list'   => __( 'Rows (label — value)', 'studiare-extensions' ),
					'tiles'  => __( 'Tiles', 'studiare-extensions' ),
					'inline' => __( 'One line', 'studiare-extensions' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'     => __( 'Columns', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '4',
				'options'   => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors' => array( '{{WRAPPER}} .stx-info' => '--stx-cols: {{VALUE}};' ),
				'condition' => array( 'layout' => 'tiles' ),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'studiare-extensions' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Start', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::start_icon(),
					),
					'center'     => array(
						'title' => __( 'Center', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'End', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::end_icon(),
					),
				),
				'selectors' => array( '{{WRAPPER}} .stx-info' => 'justify-content: {{VALUE}};' ),
				'condition' => array( 'layout' => 'inline' ),
			)
		);

		$this->add_control(
			'show_icons',
			array(
				'label'   => __( 'Icons', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'       => __( 'Hide empty items', 'studiare-extensions' ),
				'description' => __( 'Items without a value on the current product are left out.', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Facts', 'studiare-extensions' ) );
		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Space between', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-info' => '--stx-gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-info__icon' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'label', '.stx-info__label', array( 'label' => __( 'Label', 'studiare-extensions' ) ) );
		$this->add_text_style( 'value', '.stx-info__value', array( 'label' => __( 'Value', 'studiare-extensions' ) ) );
		$this->add_box_style( 'item', '.stx-info__item', array( 'label' => __( 'Item box', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$sources = Parts::info_sources();
				$html    = '';
				$editor  = Context::is_editor();

				foreach ( (array) $s['items'] as $item ) {
					$source = isset( $sources[ $item['source'] ] ) ? $item['source'] : 'text';
					$value  = Parts::info_value( $product, $source, (string) $item['extra'] );

					if ( '' === $value ) {
						if ( 'yes' === $s['hide_empty'] && ! $editor ) {
							continue;
						}
						$value = '—';
					}

					if ( 'rating' === $source ) {
						$value = '★ ' . $value;
					}

					$icon_key = 'auto' === $item['icon'] ? $sources[ $source ]['icon'] : $item['icon'];
					$icon     = 'yes' === $s['show_icons'] && '' !== $icon_key ? '<span class="stx-info__icon">' . self::icon( $icon_key ) . '</span>' : '';
					$suffix   = '' !== (string) $item['suffix'] ? ' ' . $item['suffix'] : '';

					$html .= sprintf(
						'<li class="stx-info__item stx-info__item--%1$s">%2$s<span class="stx-info__label">%3$s</span><span class="stx-info__value">%4$s</span></li>',
						esc_attr( $source ),
						$icon,
						esc_html( Parts::info_label( $product, $source, (string) $item['label'] ) ),
						esc_html( self::digits( $value . $suffix ) )
					);
				}

				if ( '' !== $html ) {
					echo '<ul class="stx-info stx-info--' . esc_attr( $s['layout'] ) . '">' . $html . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				}
			}
		);
	}
}
