<?php
/**
 * Course teacher(s) from Studiare's teacher posts.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Course_Teacher extends Base {

	public function get_name(): string {
		return 'stx-course-teacher';
	}

	public function get_title(): string {
		return __( 'Course teacher', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Teacher', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'card',
				'options' => array(
					'card'    => __( 'Card with bio', 'studiare-extensions' ),
					'compact' => __( 'Compact (photo and name)', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'label',
			array(
				'label'   => __( 'Small label', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'bio',
			array(
				'label'     => __( 'Bio', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'layout' => 'card' ),
			)
		);

		$this->add_control(
			'link',
			array(
				'label'   => __( 'Link to teacher page', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Teacher', 'studiare-extensions' ) );
		$this->add_box_style( 'card', '.stx-teacher', array( 'shadow' => true ) );
		$this->add_responsive_control(
			'photo_size',
			array(
				'label'      => __( 'Photo size', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 32,
						'max' => 160,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-teacher__photo' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_text_style( 'name', '.stx-teacher__name', array( 'label' => __( 'Name', 'studiare-extensions' ) ) );
		$this->add_text_style( 'role', '.stx-teacher__role', array( 'label' => __( 'Job title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'bio', '.stx-teacher__bio', array( 'label' => __( 'Bio', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			static function ( \WC_Product $product ) use ( $s ) {
				$html = Parts::teachers(
					$product,
					array(
						'layout' => $s['layout'],
						'bio'    => 'yes' === $s['bio'],
						'link'   => 'yes' === $s['link'],
						'label'  => (string) $s['label'],
					)
				);
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
			}
		);
	}
}
