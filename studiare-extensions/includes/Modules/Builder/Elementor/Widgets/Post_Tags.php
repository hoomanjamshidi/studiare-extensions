<?php
/**
 * Tags as small links: the tags of the post being viewed, or the site's
 * most used tags (a sidebar tag list).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;

defined( 'ABSPATH' ) || exit;

final class Post_Tags extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-tags';
	}

	public function get_title(): string {
		return __( 'Tags', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-tags';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Tags', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Which tags', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => array(
					'post'    => __( 'Tags of the post being viewed', 'studiare-extensions' ),
					'popular' => __( 'Most used tags of the site', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'     => __( 'Number of tags', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 12,
				'min'       => 1,
				'max'       => 40,
				'condition' => array( 'source' => 'popular' ),
			)
		);

		$this->add_control(
			'label',
			array(
				'label'   => __( 'Text before the tags', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Tags:', 'studiare-extensions' ),
			)
		);

		$this->add_align_control( 'align', '.stx-tags' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Tags', 'studiare-extensions' ) );
		$this->add_text_style( 'label', '.stx-tags__label', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );
		$this->add_text_style(
			'tag',
			'.stx-tags__tag',
			array(
				'label' => __( 'Tags', 'studiare-extensions' ),
				'hover' => '.stx-tags__tag:hover',
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$tags = $this->tags( $s );

		if ( ! $tags ) {
			$this->editor_hint( __( 'No tags to show.', 'studiare-extensions' ) );
			return;
		}

		$links = '';
		foreach ( $tags as $tag ) {
			$links .= sprintf( '<a class="stx-tags__tag" href="%1$s">%2$s</a>', esc_url( (string) get_term_link( $tag ) ), esc_html( html_entity_decode( $tag->name, ENT_QUOTES, 'UTF-8' ) ) );
		}

		printf(
			'<div class="stx-tags">%1$s%2$s</div>',
			'' !== (string) $s['label'] ? '<span class="stx-tags__label">' . esc_html( $s['label'] ) . '</span>' : '',
			$links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		);
	}

	/**
	 * @param array $s Settings.
	 * @return \WP_Term[]
	 */
	private function tags( array $s ): array {
		if ( 'popular' === $s['source'] ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'post_tag',
					'orderby'    => 'count',
					'order'      => 'DESC',
					'number'     => max( 1, min( 40, (int) $s['count'] ) ),
					'hide_empty' => true,
				)
			);

			return is_array( $terms ) ? $terms : array();
		}

		$post = Context::post();

		return $post ? Post_Parts::terms( $post, 'post_tag' ) : array();
	}
}
