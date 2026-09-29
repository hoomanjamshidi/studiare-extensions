<?php
/**
 * Blog categories as links: a row of buttons that scrolls sideways on
 * phones (with "All" first), or a list with post counts for sidebars. The
 * category being viewed is marked.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Resolver;

defined( 'ABSPATH' ) || exit;

final class Post_Categories extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-categories';
	}

	public function get_title(): string {
		return __( 'Blog categories', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Categories', 'studiare-extensions' ) );

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Look', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'chips',
				'options' => array(
					'chips' => __( 'Row of buttons', 'studiare-extensions' ),
					'list'  => __( 'List with post counts', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'all_label',
			array(
				'label'       => __( '"All" button', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'All posts', 'studiare-extensions' ),
				'description' => __( 'Links to the blog page. Leave empty to hide.', 'studiare-extensions' ),
				'condition'   => array( 'layout' => 'chips' ),
			)
		);

		$this->add_control(
			'counts',
			array(
				'label'   => __( 'Post counts', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'top_only',
			array(
				'label'   => __( 'Main categories only', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => __( 'Number of categories', 'studiare-extensions' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 40,
			)
		);

		$this->add_align_control( 'align', '.stx-terms' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Categories', 'studiare-extensions' ) );
		$this->add_text_style(
			'link',
			'.stx-terms__link',
			array( 'hover' => '.stx-terms__link:hover' )
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => max( 1, min( 40, (int) $s['count'] ) ),
				'exclude'    => array( (int) get_option( 'default_category' ) ),
				'parent'     => 'yes' === $s['top_only'] ? 0 : '',
			)
		);
		$terms = is_array( $terms ) ? $terms : array();

		if ( ! $terms ) {
			$this->editor_hint( __( 'No categories with posts yet.', 'studiare-extensions' ) );
			return;
		}

		$chips  = 'chips' === $s['layout'];
		$counts = 'yes' === $s['counts'];
		$links  = '';

		if ( $chips && '' !== (string) $s['all_label'] ) {
			$links .= $this->link( (string) $s['all_label'], Resolver::blog_url(), is_home(), null );
		}

		foreach ( $terms as $term ) {
			$links .= $this->link(
				html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
				(string) get_term_link( $term ),
				is_category( $term->term_id ),
				$counts ? (int) $term->count : null
			);
		}

		printf(
			'<nav class="stx-terms stx-terms--%1$s" aria-label="%2$s"><ul class="stx-terms__list">%3$s</ul></nav>',
			esc_attr( $s['layout'] ),
			esc_attr__( 'Blog categories', 'studiare-extensions' ),
			$links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link().
		);
	}

	/**
	 * @param string   $label   Label.
	 * @param string   $url     URL.
	 * @param bool     $current Whether it is the page being viewed.
	 * @param int|null $count   Post count (null to hide).
	 */
	private function link( string $label, string $url, bool $current, ?int $count ): string {
		return sprintf(
			'<li><a class="stx-terms__link%1$s" href="%2$s"%3$s><span class="stx-terms__name">%4$s</span>%5$s</a></li>',
			$current ? ' is-active' : '',
			esc_url( $url ),
			$current ? ' aria-current="page"' : '',
			esc_html( $label ),
			null !== $count ? '<span class="stx-terms__count">' . esc_html( self::num( $count ) ) . '</span>' : ''
		);
	}
}
