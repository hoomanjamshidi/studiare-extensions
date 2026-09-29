<?php
/**
 * Heading with optional eyebrow label, subtitle and end link. The "rule"
 * decoration is the section title of the home designs: « title ——— link.
 * Inherits the theme font.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Heading extends Base {

	public function get_name(): string {
		return 'stx-heading';
	}

	public function get_title(): string {
		/* translators: %s: widget name. */
		return sprintf( __( '%s (Studiare+)', 'studiare-extensions' ), __( 'Heading', 'studiare-extensions' ) );
	}

	public function get_icon(): string {
		return 'eicon-t-letter';
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Heading', 'studiare-extensions' ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'       => __( 'Small label above', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);

		$this->add_control(
			'eyebrow_style',
			array(
				'label'     => __( 'Label style', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'text',
				'options'   => array(
					'text'    => __( 'Text', 'studiare-extensions' ),
					'pill'    => __( 'Pill', 'studiare-extensions' ),
					'outline' => __( 'Pill with border', 'studiare-extensions' ),
				),
				'condition' => array( 'eyebrow!' => '' ),
			)
		);

		$this->add_control(
			'eyebrow_icon',
			array(
				'label'     => __( 'Label icon', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => self::icon_options(),
				'condition' => array( 'eyebrow!' => '' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => __( 'Section title', 'studiare-extensions' ),
				'label_block' => true,
				'description' => __( 'Put words between <mark> and </mark> to show them in the accent colour.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'       => __( 'Text below', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '',
				'label_block' => true,
			)
		);

		$this->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'studiare-extensions' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'decor',
			array(
				'label'   => __( 'Decoration', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none' => __( 'None', 'studiare-extensions' ),
					'rule' => __( 'Marker and line', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'action_text',
			array(
				'label'       => __( 'Link at the end', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'See all', 'studiare-extensions' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'action_link',
			array(
				'label'     => __( 'Link at the end: address', 'studiare-extensions' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'action_text!' => '' ),
			)
		);

		$this->add_control(
			'tag',
			array(
				'label'   => __( 'HTML tag', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'p'    => 'p',
					'span' => 'span',
				),
			)
		);

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Size', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'md',
				'options' => array(
					'xl' => __( 'Extra large', 'studiare-extensions' ),
					'lg' => __( 'Large', 'studiare-extensions' ),
					'md' => __( 'Medium', 'studiare-extensions' ),
					'sm' => __( 'Small', 'studiare-extensions' ),
					'xs' => __( 'Extra small', 'studiare-extensions' ),
				),
			)
		);

		$this->add_align_control( 'align', '.stx-heading' );

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Heading', 'studiare-extensions' ) );
		$this->add_text_style( 'title', '.stx-heading__title', array( 'hover' => '.stx-heading__title a:hover' ) );
		$this->add_text_style( 'eyebrow', '.stx-heading__eyebrow', array( 'label' => __( 'Small label', 'studiare-extensions' ) ) );
		$this->add_text_style( 'subtitle', '.stx-heading__sub', array( 'label' => __( 'Text below', 'studiare-extensions' ) ) );
		$this->add_text_style(
			'action',
			'.stx-heading__action',
			array(
				'label' => __( 'Link at the end', 'studiare-extensions' ),
				'hover' => '.stx-heading__action:hover',
			)
		);

		$this->add_control(
			'decor_color',
			array(
				'label'     => __( 'Marker and line colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-heading__mark' => 'color: {{VALUE}};' ),
				'condition' => array( 'decor' => 'rule' ),
			)
		);

		$this->add_responsive_control(
			'spacing',
			array(
				'label'      => __( 'Spacing', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'separator'  => 'before',
				'selectors'  => array( '{{WRAPPER}} .stx-heading' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$tag   = in_array( $s['tag'], array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span' ), true ) ? $s['tag'] : 'h2';
		$title = wp_kses_post( nl2br( (string) $s['title'] ) );
		$rule  = 'rule' === ( $s['decor'] ?? 'none' );

		if ( ! empty( $s['link']['url'] ) ) {
			$this->add_link_attributes( 'link', $s['link'] );
			$title = '<a ' . $this->get_render_attribute_string( 'link' ) . '>' . $title . '</a>';
		}

		$title  = sprintf( '<%1$s class="stx-heading__title">%2$s</%1$s>', esc_html( $tag ), $title );
		$action = '';
		if ( '' !== (string) ( $s['action_text'] ?? '' ) ) {
			$this->add_render_attribute( 'action', 'class', 'stx-heading__action' );
			if ( ! empty( $s['action_link']['url'] ) ) {
				$this->add_link_attributes( 'action', $s['action_link'] );
			}
			$action = '<a ' . $this->get_render_attribute_string( 'action' ) . '>' . esc_html( $s['action_text'] ) . '</a>';
		}

		if ( $rule || '' !== $action ) {
			$title = self::row( $title, $action, $rule );
		}

		$eyebrow_style = $s['eyebrow_style'] ?? 'text';
		?>
		<div class="stx-heading stx-heading--<?php echo esc_attr( $s['size'] ); ?><?php echo $rule ? ' stx-heading--rule' : ''; ?>">
			<?php if ( '' !== (string) $s['eyebrow'] ) : ?>
				<span class="stx-heading__eyebrow stx-heading__eyebrow--<?php echo esc_attr( $eyebrow_style ); ?>">
					<?php echo '' !== (string) ( $s['eyebrow_icon'] ?? '' ) ? self::icon( $s['eyebrow_icon'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
					<?php echo esc_html( $s['eyebrow'] ); ?>
				</span>
			<?php endif; ?>
			<?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses and escaped above. ?>
			<?php if ( '' !== (string) $s['subtitle'] ) : ?>
				<p class="stx-heading__sub"><?php echo wp_kses_post( nl2br( (string) $s['subtitle'] ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Title row: « marker, title, a line filling the row, then an end link
	 * or buttons. Also used by widgets that put their own controls in the
	 * title row (e.g. the Product grid's category buttons).
	 *
	 * @param string $title_html Title element.
	 * @param string $end_html   Markup at the end of the row ('' for none).
	 * @param bool   $rule       Whether to show the marker and the line.
	 */
	public static function row( string $title_html, string $end_html, bool $rule ): string {
		return sprintf(
			'<div class="stx-heading__row">%1$s%2$s%3$s%4$s</div>',
			// Bidi mirroring draws « as » in right-to-left text, as in the design, and keeps it « in left-to-right text.
			$rule ? '<span class="stx-heading__mark" aria-hidden="true">«</span>' : '',
			$title_html,
			$rule ? '<span class="stx-heading__line" aria-hidden="true"></span>' : '',
			$end_html
		);
	}
}
