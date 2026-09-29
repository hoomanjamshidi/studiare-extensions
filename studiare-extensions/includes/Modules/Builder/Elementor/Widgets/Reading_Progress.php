<?php
/**
 * Reading progress: a thin bar across the top of the screen that fills as
 * the visitor reads the post. blog.js measures the post content (the whole
 * page when there is none) and moves the bar with a transform only, so it
 * never causes layout work while scrolling.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Reading_Progress extends Blog_Base {

	public function get_name(): string {
		return 'stx-reading-progress';
	}

	public function get_title(): string {
		return __( 'Reading progress bar', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-progress-tracker';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Reading progress bar', 'studiare-extensions' ) );

		$this->add_control(
			'note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'A thin bar at the top of the screen that fills while the post is read. Place it anywhere in the template.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'color',
			array(
				'label'     => __( 'Colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-progress__bar' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'height',
			array(
				'label'      => __( 'Height', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 2,
						'max' => 8,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-progress' => 'height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		if ( $this->in_editor() ) {
			$this->editor_hint( __( 'Reading progress bar (shown at the top of the screen on the site).', 'studiare-extensions' ) );
			return;
		}

		echo '<div class="stx-progress" data-stx-progress aria-hidden="true"><span class="stx-progress__bar"></span></div>';
	}
}
