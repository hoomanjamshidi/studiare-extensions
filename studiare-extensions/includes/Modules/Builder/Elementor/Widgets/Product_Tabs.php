<?php
/**
 * Tabs (or accordion, or stacked sections with a jump bar) that combine
 * description, curriculum, teacher, reviews, specifications, highlights and
 * custom content.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Tabs extends Base {

	public function get_name(): string {
		return 'stx-product-tabs';
	}

	public function get_title(): string {
		return __( 'Product tabs', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-tabs';
	}

	/** @return array<string, string> */
	private static function sources(): array {
		return array(
			'overview'    => __( 'Overview (highlights + description)', 'studiare-extensions' ),
			'description' => __( 'Description', 'studiare-extensions' ),
			'curriculum'  => __( 'Curriculum', 'studiare-extensions' ),
			'teacher'     => __( 'Teacher', 'studiare-extensions' ),
			'reviews'     => __( 'Reviews', 'studiare-extensions' ),
			'specs'       => __( 'Specifications', 'studiare-extensions' ),
			'highlights'  => __( 'Highlights', 'studiare-extensions' ),
			'custom'      => __( 'Custom content', 'studiare-extensions' ),
		);
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Tabs', 'studiare-extensions' ) );
		$this->add_sample_note();

		$repeater = new Repeater();
		$repeater->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Tab', 'studiare-extensions' ),
				'description' => __( 'For reviews, {count} is replaced with the number of reviews.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'source',
			array(
				'label'   => __( 'Content', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'description',
				'options' => self::sources(),
			)
		);
		$repeater->add_control(
			'content',
			array(
				'label'     => __( 'Text', 'studiare-extensions' ),
				'type'      => Controls_Manager::WYSIWYG,
				'default'   => '',
				'condition' => array( 'source' => 'custom' ),
			)
		);
		$repeater->add_control(
			'curriculum_mode',
			array(
				'label'     => __( 'Curriculum shows', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'theme',
				'options'   => Course_Curriculum::mode_options(),
				'condition' => array( 'source' => 'curriculum' ),
			)
		);

		$this->add_control(
			'tabs',
			array(
				'label'       => __( 'Tabs', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'title'  => __( 'Description', 'studiare-extensions' ),
						'source' => 'description',
					),
					array(
						'title'  => __( 'Curriculum', 'studiare-extensions' ),
						'source' => 'curriculum',
					),
					array(
						'title'  => __( 'Teacher', 'studiare-extensions' ),
						'source' => 'teacher',
					),
					array(
						/* translators: {count} is replaced with the number of reviews. */
						'title'  => __( 'Reviews ({count})', 'studiare-extensions' ),
						'source' => 'reviews',
					),
				),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'tabs',
				'options' => array(
					'tabs'      => __( 'Tabs', 'studiare-extensions' ),
					'accordion' => __( 'Accordion', 'studiare-extensions' ),
					'sections'  => __( 'All sections with a jump bar', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'sticky_nav',
			array(
				'label'     => __( 'Sticky jump bar', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'layout' => 'sections' ),
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'       => __( 'Hide empty tabs', 'studiare-extensions' ),
				'description' => __( 'E.g. no curriculum tab on regular products.', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_nav', __( 'Tab bar', 'studiare-extensions' ) );
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'tab_typography',
				'selector' => '{{WRAPPER}} .stx-tabs__tab',
			)
		);
		$this->add_control(
			'tab_color',
			array(
				'label'     => __( 'Text colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-tabs__tab' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'tab_active_color',
			array(
				'label'     => __( 'Active text colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-tabs__tab.is-active, {{WRAPPER}} .stx-tabs__tab:hover' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'tab_line',
			array(
				'label'     => __( 'Active line colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-tabs' => '--stx-tab-line: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'nav_bg',
			array(
				'label'     => __( 'Bar background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-tabs__nav' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_style_section( 'section_panel', __( 'Content area', 'studiare-extensions' ) );
		$this->add_box_style( 'panel', '.stx-tabs__panel', array( 'shadow' => true ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$tabs = array();
				foreach ( (array) $s['tabs'] as $index => $tab ) {
					$html = $this->tab_html( $product, $tab );

					if ( 'yes' === $s['hide_empty'] && '' === trim( $html ) && ! $this->in_editor() ) {
						continue;
					}

					$tabs[] = array(
						'id'    => 'stx-tab-' . $this->get_id() . '-' . $index,
						'title' => str_replace( '{count}', self::num( (int) $product->get_review_count() ), (string) $tab['title'] ),
						'html'  => $html,
					);
				}

				if ( ! $tabs ) {
					return;
				}

				switch ( $s['layout'] ) {
					case 'accordion':
						$this->render_accordion( $tabs );
						break;
					case 'sections':
						$this->render_sections( $tabs, 'yes' === $s['sticky_nav'] );
						break;
					default:
						$this->render_tabs( $tabs );
				}
			}
		);
	}

	/**
	 * @param \WC_Product $product Product.
	 * @param array       $tab     Repeater item.
	 */
	private function tab_html( \WC_Product $product, array $tab ): string {
		switch ( $tab['source'] ) {
			case 'overview':
				$description = Parts::description( $product );
				return Parts::highlights( $product, array( 'title' => __( 'What you will learn', 'studiare-extensions' ) ) )
					. ( '' !== $description ? '<h3 class="stx-tabs__subheading">' . esc_html__( 'About this course', 'studiare-extensions' ) . '</h3>' . $description : '' );
			case 'description':
				return Parts::description( $product );
			case 'curriculum':
				return Parts::curriculum(
					$product,
					array(
						'mode'    => $tab['curriculum_mode'] ?? 'theme',
						'summary' => true,
					)
				);
			case 'teacher':
				return Parts::teachers( $product );
			case 'reviews':
				return Parts::reviews( $product );
			case 'specs':
				return Parts::specs( $product );
			case 'highlights':
				return Parts::highlights( $product );
			case 'custom':
				return '' !== trim( (string) $tab['content'] ) ? '<div class="stx-desc">' . wp_kses_post( $this->parse_text_editor( (string) $tab['content'] ) ) . '</div>' : '';
		}

		return '';
	}

	/**
	 * @param array $tabs Prepared tabs.
	 */
	private function render_tabs( array $tabs ): void {
		echo '<div class="stx-tabs stx-tabs--tabs" data-stx-tabs><div class="stx-tabs__nav" role="tablist">';
		foreach ( $tabs as $index => $tab ) {
			printf(
				'<button type="button" class="stx-tabs__tab%1$s" role="tab" id="%2$s-tab" aria-controls="%2$s" aria-selected="%3$s" tabindex="%4$s">%5$s</button>',
				0 === $index ? ' is-active' : '',
				esc_attr( $tab['id'] ),
				0 === $index ? 'true' : 'false',
				0 === $index ? '0' : '-1',
				esc_html( $tab['title'] )
			);
		}
		echo '</div>';

		foreach ( $tabs as $index => $tab ) {
			printf(
				'<div class="stx-tabs__panel" role="tabpanel" id="%1$s" aria-labelledby="%1$s-tab"%2$s>%3$s</div>',
				esc_attr( $tab['id'] ),
				0 === $index ? '' : ' hidden',
				$tab['html'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
			);
		}
		echo '</div>';
	}

	/**
	 * @param array $tabs Prepared tabs.
	 */
	private function render_accordion( array $tabs ): void {
		echo '<div class="stx-tabs stx-tabs--accordion">';
		foreach ( $tabs as $index => $tab ) {
			printf(
				'<details class="stx-tabs__item" id="%1$s"%2$s><summary class="stx-tabs__tab">%3$s<span class="stx-tabs__sign" aria-hidden="true"></span></summary><div class="stx-tabs__panel">%4$s</div></details>',
				esc_attr( $tab['id'] ),
				0 === $index ? ' open' : '',
				esc_html( $tab['title'] ),
				$tab['html'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
			);
		}
		echo '</div>';
	}

	/**
	 * @param array $tabs   Prepared tabs.
	 * @param bool  $sticky Sticky jump bar.
	 */
	private function render_sections( array $tabs, bool $sticky ): void {
		echo '<div class="stx-tabs stx-tabs--sections" data-stx-spy><nav class="stx-tabs__nav' . ( $sticky ? ' is-sticky' : '' ) . '">';
		foreach ( $tabs as $index => $tab ) {
			printf( '<a class="stx-tabs__tab%1$s" href="#%2$s">%3$s</a>', 0 === $index ? ' is-active' : '', esc_attr( $tab['id'] ), esc_html( $tab['title'] ) );
		}
		echo '</nav>';

		foreach ( $tabs as $tab ) {
			printf(
				'<section class="stx-tabs__panel" id="%1$s"><h2 class="stx-tabs__heading">%2$s</h2>%3$s</section>',
				esc_attr( $tab['id'] ),
				esc_html( $tab['title'] ),
				$tab['html'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
			);
		}
		echo '</div>';
	}
}
