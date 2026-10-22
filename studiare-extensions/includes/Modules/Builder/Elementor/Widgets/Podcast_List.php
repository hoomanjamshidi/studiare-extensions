<?php
/**
 * Podcast list: blog posts that have a podcast (Article tools), as numbered
 * rows of title and short text, each a link to the post's player, beside an
 * optional picture.
 *
 * Every published post with a podcast is listed, or only those in chosen
 * categories, or those in the category being viewed. The podcast is read
 * from the post meta, so the list works even while the Article tools module
 * is off (the link then opens the post without a player).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Article_Tools\Podcast;

defined( 'ABSPATH' ) || exit;

final class Podcast_List extends Home_Base {

	/** Most rows a list may ask for. */
	private const MAX_ITEMS = 20;

	/** Two-tone arrow of the rows. It points left; CSS mirrors it on LTR sites. */
	private const ARROW = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 4l-8 8" opacity=".45"/><path d="M7 12l8 8"/></svg>';

	public function get_name(): string {
		return 'stx-podcast-list';
	}

	public function get_title(): string {
		return __( 'Podcast list', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-headphones';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'podcast', 'audio', 'پادکست', 'صوت' ) );
	}

	protected function register_controls(): void {
		$this->register_list_controls();
		$this->register_picture_controls();
		$this->register_style_controls();
	}

	private function register_list_controls(): void {
		$this->start_content_section( 'section_list', __( 'Podcasts', 'studiare-extensions' ) );

		$this->add_control(
			'note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Lists blog posts that have a podcast (set in the "Podcast" box of the post editor).', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Posts', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => array(
					'all'      => __( 'All categories', 'studiare-extensions' ),
					'category' => __( 'From categories', 'studiare-extensions' ),
					'current'  => __( 'Category being viewed', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'categories',
			array(
				'label'       => __( 'Categories', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => Post_Grid::category_options(),
				'description' => __( 'Subcategories are included.', 'studiare-extensions' ),
				'condition'   => array( 'source' => 'category' ),
			)
		);

		$this->add_control(
			'current_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'On a category page, the podcasts of that category; on other pages, all podcasts.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array( 'source' => 'current' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => __( 'Number of podcasts', 'studiare-extensions' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => self::MAX_ITEMS,
				'default' => 4,
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => __( 'Newest first', 'studiare-extensions' ),
					'ASC'  => __( 'Oldest first', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'   => __( 'Text under the title', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'excerpt',
				'options' => array(
					'excerpt' => __( 'Post excerpt', 'studiare-extensions' ),
					'speaker' => __( 'Speaker', 'studiare-extensions' ),
					''        => __( 'None', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'numbers',
			array(
				'label'   => __( 'Numbers', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'duration',
			array(
				'label'   => __( 'Duration', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'dividers',
			array(
				'label'   => __( 'Lines between rows', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => __( 'Title tag', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'span' => __( 'Plain text', 'studiare-extensions' ),
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_picture_controls(): void {
		$this->start_content_section( 'section_picture', __( 'Picture', 'studiare-extensions' ) );

		$this->add_control(
			'image',
			array(
				'label'       => __( 'Picture', 'studiare-extensions' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array(
					'id'  => 0,
					'url' => '',
				),
				'description' => __( 'Optional. Leave empty for the list alone.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'image_shape',
			array(
				'label'   => __( 'Shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'blob',
				'options' => array(
					'blob'    => __( 'Free shape with decorations', 'studiare-extensions' ),
					'circle'  => __( 'Circle', 'studiare-extensions' ),
					'rounded' => __( 'Rounded', 'studiare-extensions' ),
					'plain'   => __( 'As uploaded', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'image_side',
			array(
				'label'   => __( 'Position', 'studiare-extensions' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'end',
				'toggle'  => false,
				'options' => array(
					'start' => array(
						'title' => __( 'Start', 'studiare-extensions' ),
						'icon'  => 'eicon-h-align-' . self::start_icon(),
					),
					'end'   => array(
						'title' => __( 'End', 'studiare-extensions' ),
						'icon'  => 'eicon-h-align-' . self::end_icon(),
					),
				),
			)
		);

		$this->add_control(
			'image_phone',
			array(
				'label'        => __( 'Show on phones', 'studiare-extensions' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'description'  => __( 'On narrow screens the picture sits above the list.', 'studiare-extensions' ),
				'return_value' => 'yes',
			)
		);

		$this->add_responsive_control(
			'image_width',
			array(
				'label'      => __( 'Picture width', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min' => 20,
						'max' => 50,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-pods' => '--stx-pods-media: {{SIZE}}%;' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_style_controls(): void {
		$this->start_style_section( 'section_style_rows', __( 'Rows', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-pods__list' );
		$this->add_box_style( 'row', '.stx-pods__link', array( 'label' => __( 'Row', 'studiare-extensions' ) ) );

		$this->add_control(
			'row_hover_bg',
			array(
				'label'     => __( 'Hover background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-pods__link' => '--stx-pods-hover: {{VALUE}};' ),
			)
		);

		$this->add_text_style( 'title', '.stx-pods__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'num', '.stx-pods__num', array( 'label' => __( 'Number', 'studiare-extensions' ) ) );
		$this->add_text_style( 'sub', '.stx-pods__sub', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );

		$this->add_control(
			'arrow_heading',
			array(
				'label'     => __( 'Arrow', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => __( 'Colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-pods__go' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'      => __( 'Size', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 14,
						'max' => 56,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-pods__go' => '--stx-pods-arrow: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$rows = $this->query( $s );

		if ( ! $rows ) {
			$this->editor_hint( __( 'No post has a podcast yet. Add one in the "Podcast" box of the post editor.', 'studiare-extensions' ) );
			return;
		}

		$image_id = isset( $s['image']['id'] ) ? (int) $s['image']['id'] : 0;
		$classes  = array( 'stx-pods' );
		if ( $image_id ) {
			$classes[] = 'stx-pods--media';
			$classes[] = 'stx-pods--media-' . ( 'start' === $s['image_side'] ? 'start' : 'end' );
			$classes[] = 'stx-pods--' . sanitize_html_class( (string) $s['image_shape'] );
			if ( 'yes' !== $s['image_phone'] ) {
				$classes[] = 'stx-pods--media-no-phone';
			}
		}
		if ( 'yes' === $s['dividers'] ) {
			$classes[] = 'stx-pods--dividers';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"><div class="stx-pods__inner">';
		echo '<ol class="stx-pods__list">';
		foreach ( $rows as $index => $row ) {
			$this->render_row( $row['post'], $row['pod'], $index + 1, $s );
		}
		echo '</ol>';

		if ( $image_id ) {
			echo '<div class="stx-pods__media">' . wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
				$image_id,
				'large',
				false,
				array(
					'class'   => 'stx-pods__img',
					'loading' => 'lazy',
					'sizes'   => '(max-width: 767px) 80vw, 420px',
				)
			) . '</div>';
		}

		echo '</div></div>';
	}

	/**
	 * One row: number, title, text and duration, linked to the post's player.
	 *
	 * @param \WP_Post $post   Post with a podcast.
	 * @param array    $pod    Podcast::view().
	 * @param int      $number Position in the list.
	 * @param array    $s      Widget settings.
	 */
	private function render_row( \WP_Post $post, array $pod, int $number, array $s ): void {
		$tag = in_array( $s['title_tag'], array( 'h2', 'h3', 'h4', 'span' ), true ) ? $s['title_tag'] : 'h3';
		$sub = $this->subtitle( $post, $pod, (string) $s['subtitle'] );

		echo '<li class="stx-pods__item">';
		// The player carries id="stx-podcast", so the link lands on it.
		echo '<a class="stx-pods__link" href="' . esc_url( get_permalink( $post ) . '#stx-podcast' ) . '">';
		echo '<span class="stx-pods__body">';
		printf( '<%s class="stx-pods__title">', esc_html( $tag ) );
		if ( 'yes' === $s['numbers'] ) {
			// The list is an <ol>: screen readers already say the number.
			echo '<span class="stx-pods__num" aria-hidden="true">' . esc_html( self::num( $number ) ) . ':</span> ';
		}
		echo esc_html( $pod['title'] ) . sprintf( '</%s>', esc_html( $tag ) );
		if ( '' !== $sub ) {
			echo '<span class="stx-pods__sub">' . esc_html( $sub ) . '</span>';
		}
		echo '</span>';

		if ( 'yes' === $s['duration'] && $pod['duration'] ) {
			$clock = Podcast::clock( (int) $pod['duration'] );
			echo '<span class="stx-pods__time"><bdi dir="ltr">' . esc_html( self::digits( $clock ) ) . '</bdi></span>';
		}

		echo '<span class="stx-pods__go" aria-hidden="true">' . self::ARROW . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG.
		echo '</a></li>';
	}

	/**
	 * Text under a title: the post's own excerpt (else the start of its text)
	 * or the podcast's speaker.
	 *
	 * @param \WP_Post $post   Post.
	 * @param array    $pod    Podcast::view().
	 * @param string   $source `excerpt`, `speaker` or ''.
	 */
	private function subtitle( \WP_Post $post, array $pod, string $source ): string {
		if ( 'speaker' === $source ) {
			return (string) $pod['speaker'];
		}
		if ( 'excerpt' !== $source ) {
			return '';
		}

		// Not get_the_excerpt(): without a written excerpt it runs the content
		// filters (Elementor renders the whole post) once per row.
		$text = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content );

		return wp_trim_words( wp_strip_all_tags( $text ), 14 );
	}

	/**
	 * Published posts with a podcast, newest (or oldest) first. A post whose
	 * audio file was deleted is skipped, so the query asks for a few more.
	 *
	 * @param array $s Widget settings.
	 * @return array<int, array{post:\WP_Post, pod:array}>
	 */
	private function query( array $s ): array {
		$count = max( 1, min( self::MAX_ITEMS, (int) $s['count'] ) );
		$args  = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'has_password'        => false,
			'posts_per_page'      => $count + 5,
			'order'               => 'ASC' === $s['order'] ? 'ASC' : 'DESC',
			'orderby'             => 'date',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one indexed key, a few rows.
				array(
					'key'     => Podcast::META,
					'compare' => 'EXISTS',
				),
			),
		);

		$cats = $this->category_ids( $s );
		if ( $cats ) {
			// category__in leaves out subcategories; a tax query includes them.
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the point of the filter.
				array(
					'taxonomy'         => 'category',
					'terms'            => $cats,
					'include_children' => true,
				),
			);
		}

		$rows = array();
		foreach ( get_posts( $args ) as $post ) {
			$pod = Podcast::view( $post );
			if ( $pod ) {
				$rows[] = array(
					'post' => $post,
					'pod'  => $pod,
				);
			}
		}

		return array_slice( $rows, 0, $count );
	}

	/**
	 * Categories to filter by, none for all.
	 *
	 * @param array $s Widget settings.
	 * @return int[]
	 */
	private function category_ids( array $s ): array {
		if ( 'category' === $s['source'] ) {
			return array_values( array_filter( array_map( 'intval', (array) $s['categories'] ) ) );
		}

		if ( 'current' === $s['source'] && is_category() ) {
			return array( (int) get_queried_object_id() );
		}

		return array();
	}
}
