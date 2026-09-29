<?php
/**
 * Trust badges (e-Namad, Samandehi…): paste the badge code or add an image.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Trust_Badges extends Base {

	public function get_name(): string {
		return 'stx-trust-badges';
	}

	public function get_title(): string {
		return __( 'Trust badges (e-Namad)', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-lock';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'enamad', 'اینماد', 'نماد', 'samandehi' ) );
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Badges', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			array(
				'label'   => __( 'Name', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Trust badge', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'code',
			array(
				'label'       => __( 'Badge code', 'studiare-extensions' ),
				'type'        => Controls_Manager::CODE,
				'language'    => 'html',
				'rows'        => 6,
				'default'     => '',
				'description' => __( 'Paste the code given by e-Namad / Samandehi. Leave empty to use an image instead.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'image',
			array(
				'label'     => __( 'Image', 'studiare-extensions' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array( 'code' => '' ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'     => __( 'Link', 'studiare-extensions' ),
				'type'      => Controls_Manager::URL,
				'condition' => array( 'code' => '' ),
			)
		);

		$this->add_control(
			'badges',
			array(
				'label'       => __( 'Badges', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'label' => __( 'e-Namad', 'studiare-extensions' ) ),
					array( 'label' => __( 'Samandehi', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ label }}}',
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
				'selectors' => array( '{{WRAPPER}} .stx-trust' => 'justify-content: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Badges', 'studiare-extensions' ) );
		$this->add_responsive_control(
			'size',
			array(
				'label'      => __( 'Badge size', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 60,
						'max' => 200,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-trust' => '--stx-trust: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_box_style( 'badge', '.stx-trust__item' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( empty( $s['badges'] ) ) {
			return;
		}

		echo '<div class="stx-trust">';
		foreach ( $s['badges'] as $badge ) {
			echo '<div class="stx-trust__item">';

			if ( '' !== trim( (string) $badge['code'] ) ) {
				// Same trust model as Elementor's HTML widget: only users allowed
				// unfiltered HTML can save raw code (Elementor filters it otherwise).
				echo $badge['code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} elseif ( ! empty( $badge['image']['url'] ) ) {
				$img = sprintf( '<img src="%1$s" alt="%2$s" loading="lazy" decoding="async">', esc_url( $badge['image']['url'] ), esc_attr( $badge['label'] ) );
				if ( ! empty( $badge['link']['url'] ) ) {
					$img = '<a href="' . esc_url( $badge['link']['url'] ) . '" target="_blank" rel="noopener">' . $img . '</a>';
				}
				echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			} else {
				echo '<span class="stx-trust__placeholder">' . self::icon( 'lock' ) . '<span>' . esc_html( $badge['label'] ) . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG, escaped text.
			}

			echo '</div>';
		}
		echo '</div>';
	}
}
