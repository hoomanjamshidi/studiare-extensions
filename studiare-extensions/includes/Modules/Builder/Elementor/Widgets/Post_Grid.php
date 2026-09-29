<?php
/**
 * Post grid: the newest posts (or those of chosen categories, the most
 * commented, related to the post being viewed, or picked by hand) as blog
 * cards, simple cards, rows, a compact list, editorial cards or picture
 * cards with a play button for podcasts and videos.
 *
 * On blog templates it lists the posts of the page being viewed (a category,
 * tag, author, search…) with page numbers. "Magazine" shows the first post
 * large beside a list of the others; "Highlight the first post" stretches
 * the newest post across the grid on the first page.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Cards;

defined( 'ABSPATH' ) || exit;

final class Post_Grid extends Home_Base {

	/** Card styles that show each detail (the controls follow the same map). */
	private const DETAILS = array(
		'badge'   => array( 'card', 'minimal', 'list' ),
		'date'    => array( 'card', 'minimal', 'list', 'mini' ),
		'author'  => array( 'card', 'minimal', 'list' ),
		'reading' => array( 'card', 'minimal', 'list' ),
		'excerpt' => array( 'card', 'simple', 'minimal', 'list' ),
		'button'  => array( 'card', 'simple', 'minimal', 'list' ),
	);

	public function get_name(): string {
		return 'stx-post-grid';
	}

	public function get_title(): string {
		return __( 'Post grid', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'posts', 'blog', 'podcast', 'articles' ) );
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_query', __( 'Posts', 'studiare-extensions' ) );

		$this->add_control(
			'post_type',
			array(
				'label'   => __( 'Content type', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => self::post_type_options(),
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Which posts', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'latest',
				'options' => array(
					'latest'   => __( 'Newest', 'studiare-extensions' ),
					'category' => __( 'From categories', 'studiare-extensions' ),
					'popular'  => __( 'Most commented', 'studiare-extensions' ),
					'related'  => __( 'Related to the post being viewed', 'studiare-extensions' ),
					'current'  => __( 'Posts of the page being viewed (blog pages)', 'studiare-extensions' ),
					'manual'   => __( 'Hand-picked', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'current_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Lists the posts of the blog page, category, tag, author or search being viewed, as many per page as set in Settings → Reading. While editing, the newest posts are shown.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array( 'source' => 'current' ),
			)
		);

		$this->add_control(
			'categories',
			array(
				'label'       => __( 'Categories', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => self::category_options(),
				'label_block' => true,
				'condition'   => array(
					'source'    => 'category',
					'post_type' => 'post',
				),
			)
		);

		$this->add_control(
			'ids',
			array(
				'label'       => __( 'Post IDs', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '12, 34, 56',
				'description' => __( 'Comma-separated, in the order to show them.', 'studiare-extensions' ),
				'label_block' => true,
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'     => __( 'Number of posts', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4,
				'min'       => 1,
				'max'       => 12,
				'condition' => array( 'source!' => 'current' ),
			)
		);

		$this->add_control(
			'pagination',
			array(
				'label'     => __( 'Page numbers', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'source' => 'current' ),
			)
		);

		$this->add_control(
			'empty_text',
			array(
				'label'       => __( 'Text when there are no posts', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'No posts found. Try another word or category.', 'studiare-extensions' ),
				'label_block' => true,
				'condition'   => array( 'source' => 'current' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'     => __( 'Layout', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'grid',
				'options'   => array(
					'grid'     => __( 'Grid', 'studiare-extensions' ),
					'magazine' => __( 'Magazine (first post large, a list beside it)', 'studiare-extensions' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'lead',
			array(
				'label'       => __( 'Highlight the first post', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'The newest post spans the whole row (on the first page only).', 'studiare-extensions' ),
				'condition'   => array( 'layout' => 'grid' ),
			)
		);

		$this->add_columns_control( '.stx-posts', array( 4, 2, 1 ) );

		$this->add_control(
			'mobile_scroll',
			array(
				'label'       => __( 'Swipe row on phones', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Cards sit side by side and scroll sideways instead of stacking.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_content_section( 'section_card', __( 'Cards', 'studiare-extensions' ) );

		$this->add_control(
			'card',
			array(
				'label'     => __( 'Card style', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'card',
				'options'   => array(
					'card'    => __( 'Blog card (category, date, button)', 'studiare-extensions' ),
					'simple'  => __( 'Simple (picture, title, link)', 'studiare-extensions' ),
					'minimal' => __( 'Editorial (no box, larger title)', 'studiare-extensions' ),
					'list'    => __( 'Row (picture beside the text)', 'studiare-extensions' ),
					'mini'    => __( 'Compact list (small picture, for sidebars)', 'studiare-extensions' ),
					'media'   => __( 'Picture with play button (podcasts, videos)', 'studiare-extensions' ),
				),
				'condition' => array( 'layout' => 'grid' ),
			)
		);

		$this->add_control(
			'numbers',
			array(
				'label'     => __( 'Numbers (1, 2, 3…)', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array(
					'layout' => 'grid',
					'card'   => 'mini',
				),
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'   => __( 'Picture shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '16/9',
				'options' => Product_Grid::ratio_options(),
			)
		);

		$this->add_control(
			'badge',
			array(
				'label'      => __( 'Category', 'studiare-extensions' ),
				'type'       => Controls_Manager::SWITCHER,
				'default'    => 'yes',
				'conditions' => self::detail_conditions( self::DETAILS['badge'] ),
			)
		);

		$this->add_control(
			'date',
			array(
				'label'      => __( 'Date', 'studiare-extensions' ),
				'type'       => Controls_Manager::SWITCHER,
				'default'    => 'yes',
				'conditions' => self::detail_conditions( self::DETAILS['date'] ),
			)
		);

		$this->add_control(
			'author',
			array(
				'label'      => __( 'Author', 'studiare-extensions' ),
				'type'       => Controls_Manager::SWITCHER,
				'default'    => 'yes',
				'conditions' => self::detail_conditions( self::DETAILS['author'] ),
			)
		);

		$this->add_control(
			'reading',
			array(
				'label'      => __( 'Reading time', 'studiare-extensions' ),
				'type'       => Controls_Manager::SWITCHER,
				'default'    => '',
				'conditions' => self::detail_conditions( self::DETAILS['reading'] ),
			)
		);

		$this->add_control(
			'excerpt',
			array(
				'label'      => __( 'Excerpt', 'studiare-extensions' ),
				'type'       => Controls_Manager::SWITCHER,
				'default'    => '',
				'conditions' => self::detail_conditions( self::DETAILS['excerpt'] ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'      => __( 'Button text', 'studiare-extensions' ),
				'type'       => Controls_Manager::TEXT,
				'default'    => __( 'Read more', 'studiare-extensions' ),
				'conditions' => self::detail_conditions( self::DETAILS['button'] ),
			)
		);

		$this->add_control(
			'media_icon',
			array(
				'label'     => __( 'Icon on the picture', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'play',
				'options'   => self::icon_options(),
				'condition' => array(
					'layout' => 'grid',
					'card'   => 'media',
				),
			)
		);

		$this->add_control(
			'meta_key',
			array(
				'label'       => __( 'Custom field under the title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'duration',
				'description' => __( 'Name of a custom field, e.g. an episode\'s length. The date is shown when it is empty.', 'studiare-extensions' ),
				'condition'   => array(
					'layout' => 'grid',
					'card'   => 'media',
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Cards', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-posts' );
		$this->add_box_style( 'card', '.stx-post', array( 'shadow' => true ) );
		$this->add_text_style(
			'title',
			'.stx-post__title',
			array(
				'label' => __( 'Title', 'studiare-extensions' ),
				'hover' => '.stx-post__title a:hover',
			)
		);
		$this->add_text_style( 'meta', '.stx-post__meta', array( 'label' => __( 'Date and author', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$current = 'current' === $s['source'];
		$posts   = $current ? $this->page_posts() : $this->query( $s );

		if ( ! $posts ) {
			if ( $current && '' !== (string) $s['empty_text'] ) {
				echo '<p class="stx-empty-note">' . esc_html( $s['empty_text'] ) . '</p>';
			} else {
				$this->editor_hint( __( 'No posts to show yet.', 'studiare-extensions' ) );
			}
			return;
		}

		$magazine = 'magazine' === $s['layout'];
		$style    = $magazine ? 'magazine' : (string) $s['card'];
		// The lead post spans the row on the first page only; later pages are a plain grid.
		$lead  = $magazine || ( 'yes' === $s['lead'] && ( ! $current || $this->page_number() < 2 ) );
		$swipe = 'yes' === $s['mobile_scroll'] && ! $magazine && ! in_array( $style, array( 'list', 'mini' ), true );

		printf(
			'<div class="stx-posts stx-posts--%1$s%2$s%3$s" style="--stx-ratio:%4$s%5$s">',
			esc_attr( $style ),
			$swipe ? ' stx-swipe' : '',
			'mini' === $style && 'yes' === $s['numbers'] ? ' stx-posts--numbers' : '',
			esc_attr( $s['ratio'] ),
			// The magazine's large post spans as many rows as there are listed posts.
			$magazine ? ';--stx-rows:' . esc_attr( (string) max( 1, count( $posts ) - 1 ) ) : ''
		);
		foreach ( array_values( $posts ) as $index => $post ) {
			$card = $magazine ? ( $index ? 'mini' : 'minimal' ) : $style;
			if ( $lead && 0 === $index && ! $magazine ) {
				$card = 'list';
			}
			echo Cards::post( $post, $this->card_options( $s, $card, $lead && 0 === $index, $current ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Cards.
		}
		echo '</div>';

		if ( $current && 'yes' === $s['pagination'] ) {
			echo $this->pagination(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in pagination().
		}
	}

	/**
	 * Card options for one card: each detail only where the card style shows it.
	 *
	 * @param array  $s       Settings.
	 * @param string $style   Card style.
	 * @param bool   $lead    Whether it is the highlighted first post.
	 * @param bool   $listing Whether the grid lists a blog page (titles become H2).
	 */
	private function card_options( array $s, string $style, bool $lead, bool $listing ): array {
		$shows = static function ( string $detail ) use ( $s, $style ): bool {
			$setting = 'button' === $detail ? 'button_text' : $detail;
			return in_array( $style, self::DETAILS[ $detail ], true ) && '' !== (string) $s[ $setting ] && 'no' !== (string) $s[ $setting ];
		};

		return array(
			'style'       => $style,
			'lead'        => $lead,
			'badge'       => $shows( 'badge' ),
			'date'        => $shows( 'date' ),
			'author'      => $shows( 'author' ),
			'reading'     => $shows( 'reading' ),
			'excerpt'     => $lead || $shows( 'excerpt' ),
			'button_text' => $shows( 'button' ) ? (string) $s['button_text'] : '',
			'media_icon'  => (string) $s['media_icon'],
			'meta_key'    => (string) $s['meta_key'],
			'heading'     => $listing ? 'h2' : 'h3',
		);
	}

	/**
	 * Posts of the blog page being viewed; the newest posts while editing a
	 * template (or anywhere that is not a post list).
	 *
	 * @return \WP_Post[]
	 */
	private function page_posts(): array {
		if ( self::is_listing() ) {
			global $wp_the_query;
			return array_filter(
				(array) $wp_the_query->posts,
				static function ( $post ) {
					return $post instanceof \WP_Post;
				}
			);
		}

		return get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, (int) get_option( 'posts_per_page' ) ),
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);
	}

	/** Whether the main query is a post list (blog page, archive or search). */
	private static function is_listing(): bool {
		return is_home() || is_archive() || is_search();
	}

	private function page_number(): int {
		return max( 1, (int) get_query_var( 'paged' ) );
	}

	/**
	 * Page numbers for the post list. While editing, a sample shows where
	 * they will be.
	 */
	private function pagination(): string {
		global $wp_the_query;

		$arrow = static function ( string $label, bool $next ): string {
			return '<span class="stx-pages__arrow' . ( $next ? ' stx-pages__arrow--next' : '' ) . '" aria-hidden="true">' . self::CHEVRON . '</span><span class="screen-reader-text">' . esc_html( $label ) . '</span>';
		};

		if ( self::is_listing() ) {
			$links = paginate_links(
				array(
					'total'     => (int) $wp_the_query->max_num_pages,
					'current'   => $this->page_number(),
					'mid_size'  => 1,
					'type'      => 'array',
					'prev_text' => $arrow( __( 'Previous page', 'studiare-extensions' ), false ),
					'next_text' => $arrow( __( 'Next page', 'studiare-extensions' ), true ),
				)
			);
		} elseif ( $this->in_editor() ) {
			$links = array(
				'<span aria-current="page" class="page-numbers current">1</span>',
				'<a class="page-numbers" href="#">2</a>',
				'<a class="page-numbers" href="#">3</a>',
				'<a class="next page-numbers" href="#">' . $arrow( __( 'Next page', 'studiare-extensions' ), true ) . '</a>',
			);
		} else {
			$links = array();
		}

		if ( ! is_array( $links ) || ! $links ) {
			return '';
		}

		return '<nav class="stx-pages" aria-label="' . esc_attr__( 'Pages', 'studiare-extensions' ) . '"><ul><li>' . implode( '</li><li>', array_map( array( Base::class, 'digits_html' ), $links ) ) . '</li></ul></nav>';
	}

	/**
	 * @param array $s Settings.
	 * @return \WP_Post[]
	 */
	private function query( array $s ): array {
		$type = array_key_exists( $s['post_type'], self::post_type_options() ) ? $s['post_type'] : 'post';

		$args = array(
			'post_type'           => $type,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 12, (int) $s['count'] ) ),
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		// The post being viewed is never suggested to itself.
		$viewed = is_singular() ? (int) get_queried_object_id() : 0;
		if ( 'related' === $s['source'] ) {
			$post   = Context::post();
			$viewed = $post ? (int) $post->ID : $viewed;
		}
		if ( $viewed ) {
			$args['post__not_in'] = array( $viewed );
		}

		switch ( $s['source'] ) {
			case 'category':
				$cats = array_filter( array_map( 'intval', (array) $s['categories'] ) );
				if ( $cats && 'post' === $type ) {
					$args['category__in'] = $cats;
				}
				break;
			case 'popular':
				$args['orderby'] = array(
					'comment_count' => 'DESC',
					'date'          => 'DESC',
				);
				break;
			case 'related':
				return $this->related( $args, $viewed );
			case 'manual':
				$ids              = array_filter( array_map( 'absint', explode( ',', (string) $s['ids'] ) ) );
				$args['post__in'] = $ids ? $ids : array( 0 );
				$args['orderby']  = 'post__in';
				break;
		}

		return get_posts( $args );
	}

	/**
	 * Posts sharing a category with the post being viewed, topped up with
	 * the newest posts so the row is always full.
	 *
	 * @param array $args   Base query.
	 * @param int   $viewed Post being viewed (0 for none).
	 * @return \WP_Post[]
	 */
	private function related( array $args, int $viewed ): array {
		$cats  = $viewed ? wp_get_post_categories( $viewed ) : array();
		$posts = $cats ? get_posts( array_merge( $args, array( 'category__in' => $cats ) ) ) : array();

		$missing = $args['posts_per_page'] - count( $posts );
		if ( $missing > 0 ) {
			$args['posts_per_page'] = $missing;
			$args['post__not_in']   = array_merge( (array) ( $args['post__not_in'] ?? array() ), wp_list_pluck( $posts, 'ID' ) );
			$posts                  = array_merge( $posts, get_posts( $args ) );
		}

		return $posts;
	}

	/**
	 * Shows a detail control for the card styles that have that detail (and
	 * for the magazine layout, whose cards have them all).
	 *
	 * @param string[] $cards Card styles.
	 */
	private static function detail_conditions( array $cards ): array {
		return array(
			'relation' => 'or',
			'terms'    => array(
				array(
					'name'     => 'layout',
					'operator' => '===',
					'value'    => 'magazine',
				),
				array(
					'name'     => 'card',
					'operator' => 'in',
					'value'    => $cards,
				),
			),
		);
	}

	/** @return array<string, string> Post type → label (public types with their own pages). */
	private static function post_type_options(): array {
		$options = array( 'post' => __( 'Blog posts', 'studiare-extensions' ) );

		foreach ( get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'objects'
		) as $type ) {
			// Products have their own grid; templates and Elementor's library are not content.
			if ( ! in_array( $type->name, array( 'product', 'stx_template', 'elementor_library', 'e-landing-page' ), true ) ) {
				$options[ $type->name ] = $type->labels->name;
			}
		}

		return $options;
	}

	/** @return array<string, string> Category ID → name. */
	private static function category_options(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'number'     => 300,
				'orderby'    => 'name',
			)
		);

		$options = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$options[ (string) $term->term_id ] = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
		}

		return $options;
	}
}
