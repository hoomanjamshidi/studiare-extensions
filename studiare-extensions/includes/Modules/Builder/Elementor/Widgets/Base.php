<?php
/**
 * Base class for Studiare+ Elementor widgets.
 *
 * Defaults come from the brand tokens in builder.css, so widgets look right
 * without any styling; the style controls here only override them. Text
 * controls deliberately have no global typography default, so text keeps
 * the theme's Persian font instead of Elementor's default kit fonts.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Persian;
use StudiareExt\Modules\Builder\Assets;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Integration;

defined( 'ABSPATH' ) || exit;

abstract class Base extends Widget_Base {

	/** Arrow for slider buttons. It points left; CSS mirrors it for the other direction. */
	protected const CHEVRON = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

	public function get_categories(): array {
		return array( Integration::CATEGORY );
	}

	public function get_keywords(): array {
		return array( 'studiare', 'استادیار', 'stx' );
	}

	public function get_style_depends(): array {
		return array( Assets::HANDLE );
	}

	public function get_script_depends(): array {
		return array( Assets::HANDLE );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/** Output depends on the product/user, so Elementor must never cache it. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ---------------------------------------------------------------------
	 * Context helpers
	 * ------------------------------------------------------------------- */

	protected function product(): ?\WC_Product {
		return Context::product();
	}

	protected function in_editor(): bool {
		return Context::is_editor();
	}

	/**
	 * Runs a render callback with the product as global post/product. In the
	 * editor, prints a hint when there is no product to show.
	 *
	 * @param callable $callback Receives the product.
	 */
	protected function with_product( callable $callback ): void {
		$product = $this->product();

		if ( $product ) {
			Context::run( $product, $callback );
			return;
		}

		$this->editor_hint( __( 'Add a published product to see real data here.', 'studiare-extensions' ) );
	}

	/**
	 * Small notice visible only inside the Elementor editor.
	 *
	 * @param string $message Message.
	 */
	protected function editor_hint( string $message ): void {
		if ( $this->in_editor() ) {
			printf( '<div class="stx-w-hint">%s</div>', esc_html( $message ) );
		}
	}

	/**
	 * Bundled SVG icon in the pack chosen in the module settings.
	 *
	 * @param string $key        Semantic icon key.
	 * @param string $class_name Extra class.
	 */
	public static function icon( string $key, string $class_name = '' ): string {
		$module = Integration::module();
		$pack   = $module ? $module->settings()['options']['icon_pack'] : 'tabler';

		return Icon_Library::svg( $pack, $key, false, trim( 'stx-ico ' . $class_name ) );
	}

	/**
	 * Icon key → label, for SELECT controls.
	 *
	 * @param bool $with_none Whether "None" is offered first.
	 * @return array<string, string>
	 */
	protected static function icon_options( bool $with_none = true ): array {
		$options = $with_none ? array( '' => __( 'None', 'studiare-extensions' ) ) : array();
		foreach ( Icon_Library::icon_keys() as $key ) {
			$options[ $key ] = $key;
		}

		return $options;
	}

	/**
	 * Number/text with Persian digits when the site is Persian and the
	 * module option is on.
	 *
	 * @param string|int|float $text Text.
	 */
	public static function digits( $text ): string {
		return self::wants_persian_digits() && Persian::is_site_persian() ? Persian::digits( $text ) : (string) $text;
	}

	/**
	 * Markup with Persian digits in its text nodes (e.g. WooCommerce price
	 * HTML), under the same conditions as digits().
	 *
	 * @param string $html Trusted markup.
	 */
	public static function digits_html( string $html ): string {
		return self::wants_persian_digits() && Persian::is_site_persian() ? Persian::digits_html( $html ) : $html;
	}

	/**
	 * Localised number (Persian digits and separators when enabled).
	 *
	 * @param int|float $number   Number.
	 * @param int       $decimals Decimals.
	 */
	public static function num( $number, int $decimals = 0 ): string {
		return Persian::number( $number, $decimals, self::wants_persian_digits() );
	}

	/** The module's "Persian digits" option (on when the module is unavailable). */
	private static function wants_persian_digits(): bool {
		$module = Integration::module();

		return $module ? (bool) $module->settings()['options']['persian_digits'] : true;
	}

	/** `right`/`left` icon for start/end CHOOSE options (RTL aware). */
	protected static function start_icon(): string {
		return is_rtl() ? 'right' : 'left';
	}

	protected static function end_icon(): string {
		return is_rtl() ? 'left' : 'right';
	}

	/* ---------------------------------------------------------------------
	 * Control helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Responsive text alignment (start/center/end — flips with RTL).
	 *
	 * @param string $name          Control name.
	 * @param string $selector      Selector (relative to the widget).
	 * @param string $default_value Default value.
	 */
	protected function add_align_control( string $name, string $selector, string $default_value = '' ): void {
		$this->add_responsive_control(
			$name,
			array(
				'label'     => __( 'Alignment', 'studiare-extensions' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'start'  => array(
						'title' => __( 'Start', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::start_icon(),
					),
					'center' => array(
						'title' => __( 'Center', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-center',
					),
					'end'    => array(
						'title' => __( 'End', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::end_icon(),
					),
				),
				'default'   => $default_value,
				'selectors' => array(
					'{{WRAPPER}} ' . $selector => 'text-align: {{VALUE}}; --stx-justify: {{VALUE}};',
				),
			)
		);
	}

	/**
	 * Colour (+ optional hover colour) and typography for a text element.
	 *
	 * @param string $prefix   Control name prefix.
	 * @param string $selector Selector (relative to the widget).
	 * @param array  $args     `label`, `hover` (selector for hover colour).
	 */
	protected function add_text_style( string $prefix, string $selector, array $args = array() ): void {
		if ( ! empty( $args['label'] ) ) {
			$this->add_control(
				$prefix . '_heading',
				array(
					'label'     => $args['label'],
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);
		}

		$this->add_control(
			$prefix . '_color',
			array(
				'label'     => __( 'Colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ),
			)
		);

		if ( ! empty( $args['hover'] ) ) {
			$this->add_control(
				$prefix . '_hover_color',
				array(
					'label'     => __( 'Hover colour', 'studiare-extensions' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} ' . $args['hover'] => 'color: {{VALUE}};' ),
				)
			);
		}

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => $prefix . '_typography',
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
	}

	/**
	 * Background, border, radius, padding and shadow for a box.
	 *
	 * @param string $prefix   Control name prefix.
	 * @param string $selector Selector (relative to the widget).
	 * @param array  $args     `label`, `padding` (bool, default true), `shadow` (bool).
	 */
	protected function add_box_style( string $prefix, string $selector, array $args = array() ): void {
		$target = '{{WRAPPER}} ' . $selector;

		if ( ! empty( $args['label'] ) ) {
			$this->add_control(
				$prefix . '_box_heading',
				array(
					'label'     => $args['label'],
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);
		}

		$this->add_control(
			$prefix . '_bg',
			array(
				'label'     => __( 'Background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $target => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => $prefix . '_border',
				'selector' => $target,
			)
		);

		$this->add_responsive_control(
			$prefix . '_radius',
			array(
				'label'      => __( 'Border radius', 'studiare-extensions' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $target => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		if ( ! isset( $args['padding'] ) || $args['padding'] ) {
			$this->add_responsive_control(
				$prefix . '_padding',
				array(
					'label'      => __( 'Padding', 'studiare-extensions' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', 'em', 'rem' ),
					'selectors'  => array( $target => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
				)
			);
		}

		if ( ! empty( $args['shadow'] ) ) {
			$this->add_group_control(
				Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => $prefix . '_shadow',
					'selector' => $target,
				)
			);
		}
	}

	/**
	 * Normal/hover colours, typography, radius and padding for a button.
	 *
	 * @param string $prefix   Control name prefix.
	 * @param string $selector Button selector (relative to the widget).
	 */
	protected function add_button_style( string $prefix, string $selector ): void {
		$target = '{{WRAPPER}} ' . $selector;

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => $prefix . '_typography',
				'selector' => $target,
			)
		);

		$this->start_controls_tabs( $prefix . '_tabs' );

		foreach ( array( 'normal', 'hover' ) as $state ) {
			$this->start_controls_tab(
				$prefix . '_tab_' . $state,
				array( 'label' => 'normal' === $state ? __( 'Normal', 'studiare-extensions' ) : __( 'Hover', 'studiare-extensions' ) )
			);

			$state_selector = 'normal' === $state ? $target : $target . ':hover, ' . $target . ':focus-visible';

			$this->add_control(
				$prefix . '_' . $state . '_color',
				array(
					'label'     => __( 'Text colour', 'studiare-extensions' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $state_selector => 'color: {{VALUE}};' ),
				)
			);

			$this->add_control(
				$prefix . '_' . $state . '_bg',
				array(
					'label'     => __( 'Background', 'studiare-extensions' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $state_selector => 'background-color: {{VALUE}};' ),
				)
			);

			$this->add_control(
				$prefix . '_' . $state . '_border_color',
				array(
					'label'     => __( 'Border colour', 'studiare-extensions' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $state_selector => 'border-color: {{VALUE}};' ),
				)
			);

			$this->end_controls_tab();
		}

		$this->end_controls_tabs();

		$this->add_responsive_control(
			$prefix . '_radius',
			array(
				'label'      => __( 'Border radius', 'studiare-extensions' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'separator'  => 'before',
				'selectors'  => array( $target => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			$prefix . '_padding',
			array(
				'label'      => __( 'Padding', 'studiare-extensions' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( $target => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			$prefix . '_height',
			array(
				'label'      => __( 'Height', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 28,
						'max' => 90,
					),
				),
				'selectors'  => array( $target => 'min-height: {{SIZE}}{{UNIT}};' ),
			)
		);
	}

	/**
	 * Responsive "Columns" select that sets `--stx-cols` on the grid.
	 *
	 * @param string $selector Grid selector (relative to the widget).
	 * @param int[]  $defaults Desktop, tablet and phone columns.
	 * @param int    $max      Largest choice.
	 * @param array  $extra    More control arguments (e.g. `condition`).
	 */
	protected function add_columns_control( string $selector, array $defaults, int $max = 6, array $extra = array() ): void {
		$options = array();
		for ( $i = 1; $i <= $max; $i++ ) {
			$options[ (string) $i ] = (string) $i;
		}

		$this->add_responsive_control(
			'columns',
			array_merge(
				array(
					'label'          => __( 'Columns', 'studiare-extensions' ),
					'type'           => Controls_Manager::SELECT,
					'default'        => (string) $defaults[0],
					'tablet_default' => (string) $defaults[1],
					'mobile_default' => (string) $defaults[2],
					'options'        => $options,
					'selectors'      => array( '{{WRAPPER}} ' . $selector => '--stx-cols: {{VALUE}};' ),
				),
				$extra
			)
		);
	}

	/**
	 * "Space between" slider for a grid or list.
	 *
	 * @param string $selector Selector (relative to the widget).
	 */
	protected function add_gap_control( string $selector ): void {
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
				// --stx-gap lets slide rows size their cards (see .stx-rail__track).
				'selectors'  => array( '{{WRAPPER}} ' . $selector => 'gap: {{SIZE}}{{UNIT}}; --stx-gap: {{SIZE}}{{UNIT}};' ),
			)
		);
	}

	/**
	 * Starts a style section.
	 *
	 * @param string $id    Section id.
	 * @param string $label Label.
	 * @param array  $args  Extra section args (e.g. `condition`).
	 */
	protected function start_style_section( string $id, string $label, array $args = array() ): void {
		$this->start_controls_section(
			$id,
			array_merge(
				array(
					'label' => $label,
					'tab'   => Controls_Manager::TAB_STYLE,
				),
				$args
			)
		);
	}

	/**
	 * Starts a content section.
	 *
	 * @param string $id    Section id.
	 * @param string $label Label.
	 * @param array  $args  Extra section args.
	 */
	protected function start_content_section( string $id, string $label, array $args = array() ): void {
		$this->start_controls_section(
			$id,
			array_merge(
				array(
					'label' => $label,
					'tab'   => Controls_Manager::TAB_CONTENT,
				),
				$args
			)
		);
	}

	/** Editor note explaining where product data comes from. */
	protected function add_sample_note(): void {
		$this->add_control(
			'stx_sample_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Shows the product being viewed. While editing, a sample course/product is used — pick it in Studiare+ → Page templates.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);
	}
}
