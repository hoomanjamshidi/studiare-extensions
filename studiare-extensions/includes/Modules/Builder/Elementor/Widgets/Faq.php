<?php
/**
 * Frequently asked questions as an accordion, with optional FAQ structured
 * data for search engines.
 *
 * Built on <details>: it opens without JavaScript, and the shared `name`
 * attribute keeps only one answer open in browsers that support it.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Faq extends Home_Base {

	public function get_name(): string {
		return 'stx-faq';
	}

	public function get_title(): string {
		return __( 'FAQ', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-accordion';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'faq', 'accordion', 'questions' ) );
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Questions', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'question',
			array(
				'label'       => __( 'Question', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'A common question?', 'studiare-extensions' ),
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'answer',
			array(
				'label'   => __( 'Answer', 'studiare-extensions' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => '<p>' . __( 'A short, clear answer.', 'studiare-extensions' ) . '</p>',
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Questions', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'question' => __( 'A common question?', 'studiare-extensions' ) ),
					array( 'question' => __( 'A common question?', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ question }}}',
			)
		);

		$this->add_control(
			'open_first',
			array(
				'label'   => __( 'First answer open', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'one_open',
			array(
				'label'   => __( 'Close the others when one opens', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'schema',
			array(
				'label'       => __( 'FAQ data for Google', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Adds FAQPage structured data. Turn it off if an SEO plugin already adds it for this page.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Questions', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-faq' );
		$this->add_box_style( 'item', '.stx-faq__item' );
		$this->add_text_style( 'question', '.stx-faq__q', array( 'label' => __( 'Question', 'studiare-extensions' ) ) );
		$this->add_text_style( 'answer', '.stx-faq__a', array( 'label' => __( 'Answer', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( empty( $s['items'] ) ) {
			return;
		}

		$group  = 'yes' === $s['one_open'] ? ' name="stx-faq-' . esc_attr( $this->get_id() ) . '"' : '';
		$schema = array();

		echo '<div class="stx-faq">';
		foreach ( $s['items'] as $index => $item ) {
			$answer = wp_kses_post( $this->parse_text_editor( (string) $item['answer'] ) );

			printf(
				'<details class="stx-faq__item"%1$s%2$s><summary class="stx-faq__q"><span>%3$s</span><span class="stx-faq__sign" aria-hidden="true"></span></summary><div class="stx-faq__a">%4$s</div></details>',
				$group, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				0 === $index && 'yes' === $s['open_first'] ? ' open' : '',
				esc_html( $item['question'] ),
				$answer // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses above.
			);

			$schema[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( (string) $item['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $answer ),
				),
			);
		}
		echo '</div>';

		if ( 'yes' === $s['schema'] && ! $this->in_editor() ) {
			printf(
				'<script type="application/ld+json">%s</script>',
				wp_json_encode(
					array(
						'@context'   => 'https://schema.org',
						'@type'      => 'FAQPage',
						'mainEntity' => $schema,
					),
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
				)
			);
		}
	}
}
