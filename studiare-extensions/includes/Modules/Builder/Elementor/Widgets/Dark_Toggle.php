<?php
/**
 * Dark mode switch that drives Studiare's own dark mode.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

final class Dark_Toggle extends Base {

	public function get_name(): string {
		return 'stx-dark-toggle';
	}

	public function get_title(): string {
		return __( 'Dark mode switch', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-adjust';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Dark mode', 'studiare-extensions' ) );

		$this->add_control(
			'only_when_available',
			array(
				'label'       => __( 'Hide when dark mode is off in Studiare', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Studiare → Theme options → dark mode must be enabled for the switch to do anything.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'boxed',
			array(
				'label'   => __( 'In a box', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Switch', 'studiare-extensions' ) );
		$this->add_control(
			'color',
			array(
				'label'     => __( 'Icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-iconbtn' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'bg',
			array(
				'label'     => __( 'Background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-iconbtn' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( 'yes' === $s['only_when_available'] && ! Theme_Bridge::dark_mode_available() && ! $this->in_editor() ) {
			return;
		}

		printf(
			'<button type="button" class="stx-iconbtn stx-dark-toggle%1$s" data-stx-dark aria-label="%2$s">%3$s%4$s</button>',
			'yes' === $s['boxed'] ? ' stx-iconbtn--boxed' : '',
			esc_attr__( 'Toggle dark mode', 'studiare-extensions' ),
			self::icon( 'moon', 'stx-dark-toggle__moon' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
			self::icon( 'sun', 'stx-dark-toggle__sun' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
		);
	}
}
