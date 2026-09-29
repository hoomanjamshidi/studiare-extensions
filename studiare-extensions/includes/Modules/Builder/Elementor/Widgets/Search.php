<?php
/**
 * Search: an inline field or an icon that opens a full-screen search, with
 * optional live results under the field (see Live_Search and builder.js).
 * Without JavaScript the form still submits to the normal results page.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Live_Search;

defined( 'ABSPATH' ) || exit;

final class Search extends Base {

	public function get_name(): string {
		return 'stx-search';
	}

	public function get_title(): string {
		/* translators: %s: widget name. */
		return sprintf( __( '%s (Studiare+)', 'studiare-extensions' ), __( 'Search', 'studiare-extensions' ) );
	}

	public function get_icon(): string {
		return 'eicon-search';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Search', 'studiare-extensions' ) );

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'field',
				'options' => array(
					'field' => __( 'Search field', 'studiare-extensions' ),
					'icon'  => __( 'Icon that opens a search overlay', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'placeholder',
			array(
				'label'   => __( 'Placeholder', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Search courses and products…', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'post_type',
			array(
				'label'   => __( 'Search in', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'product',
				'options' => array(
					'any'     => __( 'Everything', 'studiare-extensions' ),
					'product' => __( 'Products & courses', 'studiare-extensions' ),
					'post'    => __( 'Blog posts', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'live',
			array(
				'label'       => __( 'Live results while typing', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Shows matching items under the field. Enter still opens the full results page.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'live_limit',
			array(
				'label'     => __( 'Number of live results', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 6,
				'min'       => Live_Search::MIN_RESULTS,
				'max'       => Live_Search::MAX_RESULTS,
				'condition' => array( 'live' => 'yes' ),
			)
		);

		$this->add_control(
			'icon_boxed',
			array(
				'label'     => __( 'Icon in a box', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'layout' => 'icon' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Search', 'studiare-extensions' ) );
		$this->add_responsive_control(
			'field_width',
			array(
				'label'      => __( 'Field width', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 120,
						'max' => 800,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-search--field' => 'width: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'layout' => 'field' ),
			)
		);
		$this->add_responsive_control(
			'field_height',
			array(
				'label'      => __( 'Height', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 32,
						'max' => 72,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-search' => '--stx-control-h: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'field_bg',
			array(
				'label'     => __( 'Background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-search' => '--stx-field-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'field_border',
			array(
				'label'     => __( 'Border colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-search' => '--stx-field-line: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Text & icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-search' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'field_typography',
				'selector' => '{{WRAPPER}} .stx-search__input',
			)
		);
		$this->end_controls_section();
	}

	/**
	 * @param array  $s          Settings.
	 * @param string $form_class Extra form class.
	 */
	private function form( array $s, string $form_class ): string {
		$post_type = in_array( $s['post_type'], array( 'product', 'post' ), true ) ? $s['post_type'] : '';
		$id        = 'stx-s-' . $this->get_id() . '-' . sanitize_html_class( $form_class );
		$live      = 'yes' === $s['live'] && ! $this->in_editor();

		$live_attrs = '';
		$live_panel = '';
		if ( $live ) {
			$live_attrs = sprintf(
				' data-stx-live="%1$s" data-stx-live-limit="%2$d"',
				esc_attr( '' !== $post_type ? $post_type : 'any' ),
				(int) $s['live_limit']
			);
			$live_panel = sprintf(
				'<div class="stx-live" id="%1$s-live" hidden><div class="stx-live__body" data-stx-live-results></div><button type="submit" class="stx-live__all">%3$s%2$s</button></div><span class="screen-reader-text" aria-live="polite" data-stx-live-status></span>',
				esc_attr( $id ),
				esc_html__( 'See all results', 'studiare-extensions' ),
				self::icon( 'search' )
			);
		}

		return sprintf(
			'<form role="search" method="get" class="stx-search %1$s" action="%2$s"%3$s><label class="screen-reader-text" for="%4$s">%5$s</label><input type="search" id="%4$s" class="stx-search__input" name="s" placeholder="%6$s" value="%7$s"%8$s>%9$s<button type="submit" class="stx-search__submit" aria-label="%5$s">%10$s</button>%11$s</form>',
			esc_attr( $form_class ),
			esc_url( home_url( '/' ) ),
			$live_attrs,
			esc_attr( $id ),
			esc_attr__( 'Search', 'studiare-extensions' ),
			esc_attr( $s['placeholder'] ),
			esc_attr( get_search_query() ),
			$live ? ' autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="' . esc_attr( $id . '-live' ) . '"' : '',
			'' !== $post_type ? '<input type="hidden" name="post_type" value="' . esc_attr( $post_type ) . '">' : '',
			self::icon( 'search' ),
			$live_panel
		);
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( 'field' === $s['layout'] ) {
			echo $this->form( $s, 'stx-search--field' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in form().
			return;
		}

		$layer = 'stx-search-layer-' . $this->get_id();

		printf(
			'<button type="button" class="stx-iconbtn%1$s" aria-expanded="false" aria-controls="%2$s" data-stx-open="%2$s" aria-label="%3$s">%4$s</button>',
			'yes' === $s['icon_boxed'] ? ' stx-iconbtn--boxed' : '',
			esc_attr( $layer ),
			esc_attr__( 'Search', 'studiare-extensions' ),
			self::icon( 'search' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
		);

		printf(
			'<div class="stx-overlay" id="%1$s" data-stx-layer hidden><div class="stx-overlay__backdrop" data-stx-close></div><div class="stx-overlay__panel" role="dialog" aria-modal="true" aria-label="%2$s">%3$s<button type="button" class="stx-overlay__close" data-stx-close aria-label="%4$s">%5$s</button></div></div>',
			esc_attr( $layer ),
			esc_attr__( 'Search', 'studiare-extensions' ),
			$this->form( $s, 'stx-search--overlay' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in form().
			esc_attr__( 'Close', 'studiare-extensions' ),
			self::icon( 'close' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
		);
	}
}
