<?php
/**
 * Course curriculum from Studiare's lessons.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Course_Curriculum extends Base {

	public function get_name(): string {
		return 'stx-course-curriculum';
	}

	public function get_title(): string {
		return __( 'Course curriculum', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-accordion';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'lessons', 'sections', 'سرفصل', 'درس' ) );
	}

	/** @return array<string, string> */
	public static function mode_options(): array {
		return array(
			'theme'  => __( 'Always Studiare\'s full lesson list', 'studiare-extensions' ),
			'auto'   => __( 'Automatic: summary for visitors, full lessons for students', 'studiare-extensions' ),
			'styled' => __( 'Always the summary', 'studiare-extensions' ),
		);
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Curriculum', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Curriculum', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'       => __( 'What to show', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'theme',
				'options'     => self::mode_options(),
				'label_block' => true,
				'description' => __( 'The summary lists sections and lessons; public lessons and free previews play in a lightbox. Students who bought the course need Studiare\'s full list to watch and download lessons.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'open_first',
			array(
				'label'   => __( 'Open the first section', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'summary',
			array(
				'label'   => __( 'Counts and "open all"', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Sections', 'studiare-extensions' ) );
		$this->add_box_style( 'section', '.stx-curr__section', array( 'padding' => false ) );
		$this->add_text_style( 'section_title', '.stx-curr__title', array( 'label' => _x( 'Section title', 'course curriculum', 'studiare-extensions' ) ) );
		$this->add_control(
			'number_color',
			array(
				'label'     => __( 'Number colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-curr__num' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'lesson', '.stx-curr__lesson-title', array( 'label' => __( 'Lessons', 'studiare-extensions' ) ) );
		$this->add_control(
			'preview_color',
			array(
				'label'     => __( 'Preview link colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-curr__preview' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			static function ( \WC_Product $product ) use ( $s ) {
				$html = Parts::curriculum(
					$product,
					array(
						'mode'       => $s['mode'],
						'open_first' => 'yes' === $s['open_first'],
						'summary'    => 'yes' === $s['summary'],
						'title'      => (string) $s['title'],
					)
				);
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts / Studiare markup.
			}
		);
	}
}
