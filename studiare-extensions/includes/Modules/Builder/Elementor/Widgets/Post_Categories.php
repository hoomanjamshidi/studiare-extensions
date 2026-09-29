<?php
/**
 * Blog categories in nine looks.
 *
 * Navigation looks link to the category pages and mark the one being
 * viewed: a row of buttons or tabs (both scroll sideways on phones, with
 * "All posts" first), a sidebar list with post counts, the same list with
 * subcategories, and a compact drop-down (a native <details>, so it opens
 * without JavaScript). Showcase looks present the categories themselves:
 * icon cards, picture cards, the name over the picture, or each category
 * with its newest posts.
 *
 * The categories come from the site (main ones or all), are the
 * subcategories of the category being viewed, or are picked by hand with an
 * optional picture and icon each. The colour and icon set for a category in
 * Studiare (Posts → Categories) are used when present. Post counts include
 * the posts of subcategories.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Builder\Elementor\Cards;
use StudiareExt\Modules\Builder\Elementor\Category_Parts;
use StudiareExt\Modules\Builder\Elementor\Picture;
use StudiareExt\Modules\Builder\Resolver;

defined( 'ABSPATH' ) || exit;

final class Post_Categories extends Blog_Base {

	/** Looks that link to the category pages and mark the one being viewed. */
	private const NAV_LOOKS = array( 'chips', 'tabs', 'list', 'tree', 'dropdown' );

	/** Looks that present the categories as cards. */
	private const CARD_LOOKS = array( 'cards', 'covers', 'overlay', 'posts' );

	/** Looks that look up posts for every category (a query each), so they show at most MAX_QUERIED. */
	private const QUERY_LOOKS = array( 'covers', 'overlay', 'posts' );

	private const MAX_QUERIED = 12;

	/** @var array<int, \WP_Term>|null Blog categories with posts by ID, counts including subcategories. */
	private static $terms = null;

	public function get_name(): string {
		return 'stx-post-categories';
	}

	public function get_title(): string {
		return __( 'Blog categories', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'category', 'categories', 'دسته', 'دسته‌بندی' ) );
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Categories', 'studiare-extensions' ) );
		$this->add_look_controls();
		$this->add_source_controls();
		$this->add_detail_controls();
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Links', 'studiare-extensions' ), array( 'condition' => array( 'layout' => self::NAV_LOOKS ) ) );
		$this->add_text_style(
			'link',
			'.stx-terms__link',
			array( 'hover' => '.stx-terms__link:hover' )
		);
		$this->end_controls_section();

		$this->start_style_section( 'section_cards', __( 'Cards', 'studiare-extensions' ), array( 'condition' => array( 'layout' => self::CARD_LOOKS ) ) );
		$this->add_gap_control( '.stx-bcats' );
		$this->add_box_style( 'card', '.stx-bcat', array( 'shadow' => true ) );
		$this->add_text_style( 'name', '.stx-bcat__name', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'count', '.stx-bcat__count', array( 'label' => __( 'Small text', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	/** Look, grouped into navigation and showcase. */
	private function add_look_controls(): void {
		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Look', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'chips',
				'groups'  => array(
					array(
						'label'   => __( 'Navigation (marks the category being viewed)', 'studiare-extensions' ),
						'options' => array(
							'chips'    => __( 'Row of buttons', 'studiare-extensions' ),
							'tabs'     => __( 'Tabs with an underline', 'studiare-extensions' ),
							'list'     => __( 'List with post counts', 'studiare-extensions' ),
							'tree'     => __( 'List with subcategories', 'studiare-extensions' ),
							'dropdown' => __( 'Drop-down menu', 'studiare-extensions' ),
						),
					),
					array(
						'label'   => __( 'Showcase', 'studiare-extensions' ),
						'options' => array(
							'cards'   => __( 'Icon cards', 'studiare-extensions' ),
							'covers'  => __( 'Picture cards', 'studiare-extensions' ),
							'overlay' => __( 'Name over the picture', 'studiare-extensions' ),
							'posts'   => __( 'With their newest posts', 'studiare-extensions' ),
						),
					),
				),
			)
		);
	}

	/** Which categories, in which order. */
	private function add_source_controls(): void {
		$this->add_control(
			'source',
			array(
				'label'       => __( 'Categories', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'    => __( 'From the site', 'studiare-extensions' ),
					'current' => __( 'Subcategories of the category being viewed', 'studiare-extensions' ),
					'manual'  => __( 'My own list', 'studiare-extensions' ),
				),
				'description' => __( 'On a category page, "Subcategories" lists its subcategories (or its sister categories when it has none); on other pages, the main categories.', 'studiare-extensions' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'top_only',
			array(
				'label'     => __( 'Main categories only', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'source'  => 'auto',
					'layout!' => 'tree',
				),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'term',
			array(
				'label'   => __( 'Category', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array( '' => __( 'Choose…', 'studiare-extensions' ) ) + self::term_options(),
			)
		);
		$repeater->add_control(
			'image',
			array(
				'label'       => __( 'Picture', 'studiare-extensions' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => __( 'For the picture looks. Empty: the picture of the newest post in the category.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'icon',
			array(
				'label'   => __( 'Icon', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array( '' => __( 'Automatic', 'studiare-extensions' ) ) + self::icon_options( false ),
			)
		);

		$this->add_control(
			'picks',
			array(
				'label'       => __( 'Categories', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(),
				'title_field' => self::pick_title_template(),
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'     => __( 'Order', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'count',
				'options'   => array(
					'count' => __( 'Most posts first', 'studiare-extensions' ),
					'name'  => __( 'By name', 'studiare-extensions' ),
				),
				'condition' => array( 'source!' => 'manual' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'       => __( 'Number of categories', 'studiare-extensions' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 10,
				'min'         => 1,
				'max'         => 40,
				/* translators: %d: largest number of categories. */
				'description' => sprintf( __( 'The picture looks and "With their newest posts" show at most %d.', 'studiare-extensions' ), self::MAX_QUERIED ),
				'condition'   => array( 'source!' => 'manual' ),
			)
		);
	}

	/** What each category shows, per look. */
	private function add_detail_controls(): void {
		$this->add_control(
			'all_label',
			array(
				'label'       => __( '"All" button', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'All posts', 'studiare-extensions' ),
				'description' => __( 'Links to the blog page. Leave empty to hide.', 'studiare-extensions' ),
				'separator'   => 'before',
				'condition'   => array( 'layout' => array( 'chips', 'tabs', 'dropdown' ) ),
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
			'show_description',
			array(
				'label'       => __( 'Category description', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'The description written for the category in Posts → Categories.', 'studiare-extensions' ),
				'condition'   => array( 'layout' => array( 'cards', 'covers', 'posts' ) ),
			)
		);

		$this->add_control(
			'theme_colors',
			array(
				'label'       => __( 'Category colours', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Uses the "Featured Color" set for each category in Posts → Categories (Studiare).', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'posts_count',
			array(
				'label'     => __( 'Posts per category', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 6,
				'condition' => array( 'layout' => 'posts' ),
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'     => __( 'Picture shape', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '4/3',
				'options'   => Product_Grid::ratio_options(),
				'condition' => array( 'layout' => array( 'covers', 'overlay' ) ),
			)
		);

		$this->add_columns_control( '.stx-bcats', array( 3, 2, 1 ), 6, array( 'condition' => array( 'layout' => self::CARD_LOOKS ) ) );

		$this->add_control(
			'mobile_scroll',
			array(
				'label'     => __( 'Swipe row on phones', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array( 'layout' => self::CARD_LOOKS ),
			)
		);

		$this->add_align_control( 'align', '.stx-terms', '', array( 'condition' => array( 'layout' => array( 'chips', 'tabs', 'dropdown' ) ) ) );
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$look  = in_array( $s['layout'], array_merge( self::NAV_LOOKS, self::CARD_LOOKS ), true ) ? (string) $s['layout'] : 'chips';
		$items = $this->categories( $s, $look );

		if ( ! $items ) {
			$this->editor_hint( __( 'No categories with posts yet.', 'studiare-extensions' ) );
			return;
		}

		if ( in_array( $look, self::NAV_LOOKS, true ) ) {
			$this->render_nav( $items, $s, $look );
		} else {
			$this->render_cards( $items, $s, $look );
		}
	}

	/* ---------------------------------------------------------------------
	 * Which categories
	 * ------------------------------------------------------------------- */

	/**
	 * The categories to show, each with the picture and icon picked for it.
	 *
	 * @param array  $s    Settings.
	 * @param string $look Look.
	 * @return array<int, array{term: \WP_Term, image: int, icon: string}>
	 */
	private function categories( array $s, string $look ): array {
		$all   = self::all_terms();
		$items = array();

		if ( 'manual' === $s['source'] ) {
			foreach ( (array) $s['picks'] as $pick ) {
				$id = absint( $pick['term'] ?? 0 );
				if ( isset( $all[ $id ] ) ) {
					$items[] = array(
						'term'  => $all[ $id ],
						'image' => absint( $pick['image']['id'] ?? 0 ),
						'icon'  => (string) ( $pick['icon'] ?? '' ),
					);
				}
			}
		} else {
			$limit = max( 1, min( 40, (int) $s['count'] ) );
			foreach ( self::level( $all, $this->parent_id( $s, $look ), (string) $s['orderby'] ) as $term ) {
				$items[] = array(
					'term'  => $term,
					'image' => 0,
					'icon'  => '',
				);
			}
			$items = array_slice( $items, 0, $limit );
		}

		return in_array( $look, self::QUERY_LOOKS, true ) ? array_slice( $items, 0, self::MAX_QUERIED ) : $items;
	}

	/**
	 * Parent whose children are listed: 0 for the main categories, null for
	 * every level.
	 *
	 * @param array  $s    Settings.
	 * @param string $look Look.
	 */
	private function parent_id( array $s, string $look ): ?int {
		if ( 'current' !== $s['source'] ) {
			return 'yes' === $s['top_only'] || 'tree' === $look ? 0 : null;
		}

		$viewed = self::viewed();
		if ( ! $viewed ) {
			return 0;
		}

		// A category without subcategories lists its sisters, so the list still helps to move on.
		return self::level( self::all_terms(), $viewed->term_id, 'name' ) ? $viewed->term_id : (int) $viewed->parent;
	}

	/**
	 * Categories under a parent (or all of them), in the chosen order.
	 *
	 * @param array<int, \WP_Term> $all       All categories (sorted by name).
	 * @param int|null             $parent_id Parent ID, or null for every level.
	 * @param string               $orderby   `count` or `name`.
	 * @return \WP_Term[]
	 */
	private static function level( array $all, ?int $parent_id, string $orderby ): array {
		$terms = array_values(
			array_filter(
				$all,
				static function ( \WP_Term $term ) use ( $parent_id ): bool {
					return null === $parent_id || $parent_id === (int) $term->parent;
				}
			)
		);

		if ( 'count' === $orderby ) {
			// The database already sorted by name (its collation knows Persian letters); keep that order within equal counts.
			$position = array_flip( wp_list_pluck( $terms, 'term_id' ) );
			usort(
				$terms,
				static function ( \WP_Term $a, \WP_Term $b ) use ( $position ): int {
					return array( $b->count, $position[ $a->term_id ] ) <=> array( $a->count, $position[ $b->term_id ] );
				}
			);
		}

		return $terms;
	}

	/**
	 * Every blog category with posts, by ID. "Uncategorized" is left out: it
	 * says nothing about the posts in it.
	 *
	 * @return array<int, \WP_Term>
	 */
	private static function all_terms(): array {
		if ( null === self::$terms ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'category',
					'hide_empty' => true,
					// Counts include the posts of subcategories, like the category page lists them.
					'pad_counts' => true,
					'orderby'    => 'name',
					'exclude'    => array( (int) get_option( 'default_category' ) ),
				)
			);

			self::$terms = array();
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				self::$terms[ (int) $term->term_id ] = $term;
			}
		}

		return self::$terms;
	}

	/** The category whose page is being viewed. */
	private static function viewed(): ?\WP_Term {
		$term = is_category() ? get_queried_object() : null;

		return $term instanceof \WP_Term ? $term : null;
	}

	/**
	 * Category ID → name, for the picker of "My own list".
	 *
	 * @return array<int, string>
	 */
	private static function term_options(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		$options = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$options[ (int) $term->term_id ] = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
		}

		return $options;
	}

	/** Repeater row title: the picked category's name (Elementor only has its ID). */
	private static function pick_title_template(): string {
		$names = array_map(
			static function ( string $name ): string {
				// Braces would end Elementor's template tag early.
				return esc_html( str_replace( array( '{', '}' ), '', $name ) );
			},
			self::term_options()
		);

		return '{{{ ( ' . wp_json_encode( (object) $names ) . ' )[ term ] || "' . esc_js( __( 'Category', 'studiare-extensions' ) ) . '" }}}';
	}

	/* ---------------------------------------------------------------------
	 * Navigation looks
	 * ------------------------------------------------------------------- */

	/**
	 * @param array  $items Categories.
	 * @param array  $s     Settings.
	 * @param string $look  Look.
	 */
	private function render_nav( array $items, array $s, string $look ): void {
		$counts = 'yes' === $s['counts'];
		$colors = 'yes' === $s['theme_colors'];
		$links  = '';

		if ( in_array( $look, array( 'chips', 'tabs', 'dropdown' ), true ) && '' !== (string) $s['all_label'] ) {
			$links .= $this->link( (string) $s['all_label'], Resolver::blog_url(), is_home(), null, '' );
		}

		foreach ( $items as $item ) {
			$sub    = 'tree' === $look ? $this->sub_list( $item['term'], $s, $counts, $colors ) : '';
			$links .= $this->term_link( $item['term'], $counts, $colors, $sub );
		}

		$list = '<ul class="stx-terms__list">' . $links . '</ul>';

		printf(
			'<nav class="stx-terms stx-terms--%1$s" aria-label="%2$s">%3$s</nav>',
			esc_attr( $look ),
			esc_attr__( 'Blog categories', 'studiare-extensions' ),
			'dropdown' === $look ? $this->dropdown( $list, (string) $s['all_label'] ) : $list // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link() and dropdown().
		);
	}

	/**
	 * Subcategories of a category, for the tree look.
	 *
	 * @param \WP_Term $term   Category.
	 * @param array    $s      Settings.
	 * @param bool     $counts Whether post counts show.
	 * @param bool     $colors Whether category colours apply.
	 */
	private function sub_list( \WP_Term $term, array $s, bool $counts, bool $colors ): string {
		$links = '';
		foreach ( self::level( self::all_terms(), $term->term_id, (string) $s['orderby'] ) as $child ) {
			$links .= $this->term_link( $child, $counts, $colors, '' );
		}

		return '' !== $links ? '<ul class="stx-terms__sub">' . $links . '</ul>' : '';
	}

	/**
	 * The drop-down: a native <details>, so it opens without JavaScript;
	 * blog.js closes it on an outside tap and with Esc.
	 *
	 * @param string $links     List markup.
	 * @param string $all_label "All posts" label.
	 */
	private function dropdown( string $links, string $all_label ): string {
		$viewed = self::viewed();

		if ( $viewed ) {
			$current = html_entity_decode( $viewed->name, ENT_QUOTES, 'UTF-8' );
		} elseif ( is_home() && '' !== $all_label ) {
			$current = $all_label;
		} else {
			$current = __( 'Choose a category', 'studiare-extensions' );
		}

		return sprintf(
			'<details class="stx-terms__menu" data-stx-cat-menu><summary class="stx-terms__toggle"><span class="stx-terms__label">%1$s</span><span class="stx-terms__current">%2$s</span>%3$s</summary>%4$s</details>',
			esc_html__( 'Category:', 'studiare-extensions' ),
			esc_html( $current ),
			self::icon( 'chevron-down', 'stx-terms__chev' ),
			$links
		);
	}

	/**
	 * @param \WP_Term $term   Category.
	 * @param bool     $counts Whether the post count shows.
	 * @param bool     $colors Whether the category colour applies.
	 * @param string   $sub    Subcategory list markup.
	 */
	private function term_link( \WP_Term $term, bool $counts, bool $colors, string $sub ): string {
		return $this->link(
			html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
			(string) get_term_link( $term ),
			is_category( $term->term_id ),
			$counts ? (int) $term->count : null,
			$colors ? Theme_Bridge::category_color( $term ) : '',
			$sub
		);
	}

	/**
	 * @param string   $label   Label.
	 * @param string   $url     URL.
	 * @param bool     $current Whether it is the page being viewed.
	 * @param int|null $count   Post count (null to hide).
	 * @param string   $color   Category colour ('' for none).
	 * @param string   $sub     Subcategory list markup.
	 */
	private function link( string $label, string $url, bool $current, ?int $count, string $color, string $sub = '' ): string {
		return sprintf(
			'<li%1$s><a class="stx-terms__link%2$s" href="%3$s"%4$s><span class="stx-terms__name">%5$s</span>%6$s</a>%7$s</li>',
			'' !== $sub ? ' class="has-sub"' : '',
			( $current ? ' is-active' : '' ) . ( '' !== $color ? ' has-color' : '' ),
			esc_url( $url ),
			( $current ? ' aria-current="page"' : '' ) . self::color_style( $color ),
			esc_html( $label ),
			null !== $count ? '<span class="stx-terms__count">' . esc_html( self::num( $count ) ) . '</span>' : '',
			$sub // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- links built here.
		);
	}

	/* ---------------------------------------------------------------------
	 * Showcase looks
	 * ------------------------------------------------------------------- */

	/**
	 * @param array  $items Categories.
	 * @param array  $s     Settings.
	 * @param string $look  Look.
	 */
	private function render_cards( array $items, array $s, string $look ): void {
		$ratio = isset( Product_Grid::ratio_options()[ $s['ratio'] ] ) ? (string) $s['ratio'] : '4/3';
		$html  = '';

		foreach ( $items as $index => $item ) {
			$html .= 'posts' === $look ? $this->posts_card( $item, $index, $s ) : $this->card( $item, $index, $s, $look );
		}

		printf(
			'<div class="stx-bcats stx-bcats--%1$s%2$s" style="--stx-ratio:%3$s">%4$s</div>',
			esc_attr( $look ),
			'yes' === $s['mobile_scroll'] ? ' stx-bcats--swipe' : '',
			esc_attr( $ratio ),
			$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
		);
	}

	/**
	 * A category as one link: icon card, picture card or name over the picture.
	 *
	 * @param array  $item  Category.
	 * @param int    $index Position.
	 * @param array  $s     Settings.
	 * @param string $look  Look.
	 */
	private function card( array $item, int $index, array $s, string $look ): string {
		$term = $item['term'];
		$body = '<span class="stx-bcat__name">' . esc_html( html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) ) . '</span>';

		if ( 'yes' === $s['counts'] ) {
			$body .= '<span class="stx-bcat__count">' . esc_html( self::count_label( (int) $term->count ) ) . '</span>';
		}

		$text = 'overlay' !== $look && 'yes' === $s['show_description'] ? self::description( $term ) : '';
		if ( '' !== $text ) {
			$body .= '<span class="stx-bcat__desc">' . esc_html( $text ) . '</span>';
		}

		if ( 'cards' === $look ) {
			$inner = '<span class="stx-bcat__mark" aria-hidden="true">' . Category_Parts::mark( $term, $index, $item['icon'] ) . '</span>'
				. '<span class="stx-bcat__body">' . $body . '</span>'
				. '<span class="stx-bcat__go" aria-hidden="true">' . self::CHEVRON . '</span>';
		} else {
			$inner = '<span class="stx-bcat__media">' . $this->cover( $item, $index ) . '</span><span class="stx-bcat__body">' . $body . '</span>';
		}

		$current = is_category( $term->term_id );

		return sprintf(
			'<a class="stx-bcat%1$s" href="%2$s"%3$s>%4$s</a>',
			$current ? ' is-active' : '',
			esc_url( (string) get_term_link( $term ) ),
			( $current ? ' aria-current="page"' : '' ) . self::color_style( 'yes' === $s['theme_colors'] ? Theme_Bridge::category_color( $term ) : '' ),
			$inner
		);
	}

	/**
	 * A category with its newest posts and a link to the rest.
	 *
	 * @param array $item  Category.
	 * @param int   $index Position.
	 * @param array $s     Settings.
	 */
	private function posts_card( array $item, int $index, array $s ): string {
		$term = $item['term'];
		$name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
		$url  = (string) get_term_link( $term );
		$rows = '';

		$posts = get_posts(
			array(
				'cat'                 => $term->term_id,
				'numberposts'         => max( 1, min( 6, (int) $s['posts_count'] ) ),
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		foreach ( $posts as $post ) {
			$rows .= sprintf(
				'<li><a class="stx-bcat__post" href="%1$s"><span class="stx-bcat__thumb">%2$s</span><span class="stx-bcat__ptext"><span class="stx-bcat__ptitle">%3$s</span>%4$s</span></a></li>',
				esc_url( (string) get_permalink( $post ) ),
				$this->thumb( $post, $item, $index ),
				esc_html( get_the_title( $post ) ),
				Cards::post_time( $post )
			);
		}

		$count = 'yes' === $s['counts'] ? '<span class="stx-bcat__count">' . esc_html( self::count_label( (int) $term->count ) ) . '</span>' : '';
		$text  = 'yes' === $s['show_description'] ? self::description( $term ) : '';

		return sprintf(
			'<section class="stx-bcat%1$s"%2$s><header class="stx-bcat__head"><span class="stx-bcat__mark" aria-hidden="true">%3$s</span><span class="stx-bcat__titles"><h3 class="stx-bcat__name"><a href="%4$s">%5$s</a></h3>%6$s</span></header>%7$s<ul class="stx-bcat__posts">%8$s</ul><a class="stx-bcat__more" href="%4$s">%9$s<span class="stx-bcat__arrow" aria-hidden="true">%10$s</span></a></section>',
			is_category( $term->term_id ) ? ' is-active' : '',
			self::color_style( 'yes' === $s['theme_colors'] ? Theme_Bridge::category_color( $term ) : '' ),
			Category_Parts::mark( $term, $index, $item['icon'] ),
			esc_url( $url ),
			esc_html( $name ),
			$count,
			'' !== $text ? '<p class="stx-bcat__desc">' . esc_html( $text ) . '</p>' : '',
			$rows,
			/* translators: %s: category name. */
			esc_html( sprintf( __( 'All posts in %s', 'studiare-extensions' ), $name ) ),
			self::CHEVRON
		);
	}

	/**
	 * Cover of a picture card: the picked picture, else the newest post's
	 * picture, else a tile in the category's colour with its icon.
	 *
	 * @param array $item  Category.
	 * @param int   $index Position.
	 */
	private function cover( array $item, int $index ): string {
		$id = $item['image'] ? $item['image'] : Category_Parts::cover_id( $item['term'] );

		$picture = $id ? Picture::html(
			array( 'id' => $id ),
			array(),
			array(
				'loading'    => 'lazy',
				'sizes'      => '(max-width: 767px) 85vw, (max-width: 1024px) 45vw, 400px',
				'class'      => 'stx-bcat__img',
				'wrap_class' => 'stx-bcat__pic',
			)
		) : '';

		return '' !== $picture ? $picture : '<span class="stx-bcat__tile" aria-hidden="true">' . Category_Parts::mark( $item['term'], $index, $item['icon'] ) . '</span>';
	}

	/**
	 * Small picture of a post row, or the category's icon tile.
	 *
	 * @param \WP_Post $post  Post.
	 * @param array    $item  Category.
	 * @param int      $index Position.
	 */
	private function thumb( \WP_Post $post, array $item, int $index ): string {
		$id = (int) get_post_thumbnail_id( $post );

		$picture = $id ? Picture::html(
			array( 'id' => $id ),
			array(),
			array(
				'loading'    => 'lazy',
				'sizes'      => '64px',
				'class'      => 'stx-bcat__img',
				'wrap_class' => 'stx-bcat__pic',
			)
		) : '';

		return '' !== $picture ? $picture : '<span class="stx-bcat__tile" aria-hidden="true">' . Category_Parts::mark( $item['term'], $index, $item['icon'] ) . '</span>';
	}

	/**
	 * The category's description as plain text, shortened.
	 *
	 * @param \WP_Term $term Category.
	 */
	private static function description( \WP_Term $term ): string {
		return wp_trim_words( wp_strip_all_tags( (string) $term->description ), 18 );
	}

	/**
	 * "12 posts".
	 *
	 * @param int $count Number of posts.
	 */
	private static function count_label( int $count ): string {
		/* translators: %s: number of posts. */
		return sprintf( _n( '%s post', '%s posts', $count, 'studiare-extensions' ), self::num( $count ) );
	}

	/**
	 * Style attribute with the category colour, used by the CSS as `--stx-cat`.
	 *
	 * @param string $color Hex colour ('' for none).
	 */
	private static function color_style( string $color ): string {
		return '' !== $color ? ' style="--stx-cat:' . esc_attr( $color ) . '"' : '';
	}
}
