<?php
/**
 * Title of a post list page: the blog's own title and text on the posts
 * page, else the category or tag (with its description), the author (with
 * photo and biography), the date or the search, plus the number of posts.
 *
 * A category's description can be shortened behind "Read more" or left out.
 * Shortened keeps the whole text in the page, so search engines still read
 * it; it is never hidden with CSS alone, which they treat as hidden content.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;

defined( 'ABSPATH' ) || exit;

final class Archive_Title extends Blog_Base {

	public function get_name(): string {
		return 'stx-archive-title';
	}

	public function get_title(): string {
		return __( 'Blog page title', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-archive-title';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Title', 'studiare-extensions' ) );

		$this->add_control(
			'note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Categories, tags, authors and searches show their own name and description. The texts below are for the main blog page.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'   => __( 'Small title on the blog page', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Blog', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => __( 'Blog page title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Title of the blog page', 'studiare-extensions' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'text',
			array(
				'label'   => __( 'Blog page text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __( 'Practical articles, guides and news to help you learn faster.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'term_text',
			array(
				'label'       => __( 'Category and tag description', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'show',
				'options'     => array(
					'show'     => __( 'Show', 'studiare-extensions' ),
					'collapse' => __( 'Two lines with "Read more"', 'studiare-extensions' ),
					'hide'     => __( 'Hide', 'studiare-extensions' ),
				),
				'description' => __( '"Read more" keeps the whole text in the page for search engines. "Hide" leaves it out of the page; the meta description from your SEO plugin is not affected.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => __( 'Number of posts', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Size', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'lg',
				'options' => array(
					'md' => __( 'Medium', 'studiare-extensions' ),
					'lg' => __( 'Large', 'studiare-extensions' ),
					'xl' => __( 'Extra large', 'studiare-extensions' ),
				),
			)
		);

		$this->add_align_control( 'align', '.stx-archive' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Title', 'studiare-extensions' ) );
		$this->add_text_style( 'title', '.stx-archive__title' );
		$this->add_text_style( 'eyebrow', '.stx-archive__eyebrow', array( 'label' => __( 'Small title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'text', '.stx-archive__text', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );
		$this->add_text_style(
			'more',
			'.stx-archive__more',
			array(
				'label' => __( '"Read more" button', 'studiare-extensions' ),
				'hover' => '.stx-archive__more:hover',
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$info = Post_Parts::archive( (string) $s['title'], (string) $s['text'] );

		if ( 'blog' === $info['kind'] ) {
			$info['eyebrow'] = (string) $s['eyebrow'];
		}

		echo '<header class="stx-archive stx-archive--' . esc_attr( $s['size'] ) . ' stx-archive--' . esc_attr( $info['kind'] ) . '">';

		if ( $info['author'] ) {
			echo Post_Parts::avatar( $info['author'], 160, 'stx-archive__avatar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core avatar markup.
		}

		if ( '' !== $info['eyebrow'] ) {
			echo '<span class="stx-archive__eyebrow">' . esc_html( $info['eyebrow'] ) . '</span>';
		}

		echo '<h1 class="stx-archive__title">' . esc_html( $info['title'] ) . '</h1>';

		$term_text = 'term' === $info['kind'] ? (string) $s['term_text'] : 'show';
		if ( 'hide' !== $term_text && '' !== trim( $info['text'] ) ) {
			$this->render_text( trim( $info['text'] ), 'collapse' === $term_text );
		}

		if ( 'yes' === $s['count'] ) {
			/* translators: %s: number of posts. */
			$label = sprintf( _n( '%s post', '%s posts', $info['count'], 'studiare-extensions' ), self::num( $info['count'] ) );
			if ( 'search' === $info['kind'] && ! $info['count'] ) {
				$label = __( 'Nothing found', 'studiare-extensions' );
			}
			echo '<span class="stx-archive__count">' . esc_html( $label ) . '</span>';
		}

		echo '</header>';
	}

	/**
	 * The description paragraph. Shortened, it is clamped to two lines and
	 * blog.js reveals the "Read more" button only when the text is longer;
	 * without JavaScript the CSS shows the whole text.
	 *
	 * @param string $text      Plain text.
	 * @param bool   $collapsed Whether it starts shortened.
	 */
	private function render_text( string $text, bool $collapsed ): void {
		if ( ! $collapsed ) {
			echo '<p class="stx-archive__text">' . esc_html( $text ) . '</p>';
			return;
		}

		$id = 'stx-archive-text-' . $this->get_id();

		echo '<div class="stx-archive__clamp" data-stx-clamp>';
		echo '<p class="stx-archive__text" id="' . esc_attr( $id ) . '">' . esc_html( $text ) . '</p>';
		printf(
			'<button type="button" class="stx-archive__more" aria-controls="%1$s" aria-expanded="false" data-less="%2$s" hidden>%3$s</button>',
			esc_attr( $id ),
			esc_attr__( 'Show less', 'studiare-extensions' ),
			esc_html__( 'Read more', 'studiare-extensions' )
		);
		echo '</div>';
	}
}
