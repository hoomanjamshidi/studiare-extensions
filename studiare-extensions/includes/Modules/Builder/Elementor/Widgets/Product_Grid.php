<?php
/**
 * Product grid for home pages: newest, best-selling, featured, discounted,
 * top-rated or hand-picked products and courses, with optional category
 * buttons above it.
 *
 * Each category button has its own panel rendered on the server, so the
 * buttons switch instantly and cached pages need no AJAX. The buttons are
 * links to the category pages, which is where they lead without JavaScript.
 *
 * "Slide on every screen" puts the cards in one scrolling row on computers
 * too, with arrow buttons that home.js reveals only when there is more to
 * see; without JavaScript the row still scrolls with a trackpad or wheel.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Cards;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Grid extends Home_Base {

	/** Automatic category buttons when none are picked. */
	private const AUTO_CHIPS = 4;

	public function get_name(): string {
		return 'stx-product-grid';
	}

	public function get_title(): string {
		return __( 'Product grid', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-products';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'products', 'courses', 'shop', 'woocommerce' ) );
	}

	protected function register_controls(): void {
		$categories = self::category_options();

		$this->start_content_section( 'section_query', __( 'Products', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Which products', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'latest',
				'options' => array(
					'latest'       => __( 'Newest', 'studiare-extensions' ),
					'best_selling' => __( 'Best-selling', 'studiare-extensions' ),
					'featured'     => __( 'Featured', 'studiare-extensions' ),
					'on_sale'      => __( 'On sale', 'studiare-extensions' ),
					'top_rated'    => __( 'Top rated', 'studiare-extensions' ),
					'category'     => __( 'From categories', 'studiare-extensions' ),
					'manual'       => __( 'Hand-picked', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'categories',
			array(
				'label'       => __( 'Categories', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $categories,
				'label_block' => true,
				'condition'   => array( 'source' => 'category' ),
			)
		);

		$this->add_control(
			'ids',
			array(
				'label'       => __( 'Product IDs', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '12, 34, 56',
				'description' => __( 'Comma-separated, in the order to show them. The ID is shown when you hover a product in Products → All products.', 'studiare-extensions' ),
				'label_block' => true,
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'kind',
			array(
				'label'   => __( 'Show', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => array(
					'all'      => __( 'Courses and products', 'studiare-extensions' ),
					'courses'  => __( 'Courses only', 'studiare-extensions' ),
					'products' => __( 'Products only (no courses)', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => __( 'Number of products', 'studiare-extensions' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 12,
			)
		);

		$this->add_columns_control( '.stx-products__grid', array( 4, 2, 1 ) );

		$this->add_control(
			'slider',
			array(
				'label'       => __( 'Slide on every screen', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Cards sit in one row that slides sideways, with arrow buttons on computers. "Columns" sets how many are in view, so show more products than columns.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'mobile_scroll',
			array(
				'label'       => __( 'Swipe row on phones', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Cards sit side by side and scroll sideways instead of stacking.', 'studiare-extensions' ),
				'condition'   => array( 'slider!' => 'yes' ),
			)
		);

		$this->add_control(
			'empty_text',
			array(
				'label'       => __( 'Text when nothing matches', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Leave empty to hide the grid.', 'studiare-extensions' ),
				'label_block' => true,
			)
		);

		$this->end_controls_section();

		$this->start_content_section( 'section_filter', __( 'Category buttons', 'studiare-extensions' ) );

		$this->add_control(
			'filter',
			array(
				'label'       => __( 'Show category buttons', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Visitors switch between the products of each category without leaving the page.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'filter_terms',
			array(
				'label'       => __( 'Categories for the buttons', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $categories,
				'label_block' => true,
				'description' => __( 'Leave empty to use the biggest categories.', 'studiare-extensions' ),
				'condition'   => array( 'filter' => 'yes' ),
			)
		);

		$this->add_control(
			'all_label',
			array(
				'label'     => __( '"All" button', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'All', 'studiare-extensions' ),
				'condition' => array( 'filter' => 'yes' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => __( 'Section title beside the buttons', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'description' => __( 'Puts a title with a marker and a line in the same row as the buttons.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_content_section( 'section_card', __( 'Cards', 'studiare-extensions' ) );

		$this->add_control(
			'card',
			array(
				'label'   => __( 'Card style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'shop',
				'options' => array(
					'shop'    => __( 'Shop (cart button on the picture)', 'studiare-extensions' ),
					'compact' => __( 'Compact (buy button beside the price)', 'studiare-extensions' ),
					'course'  => __( 'Course (teacher, lessons, duration)', 'studiare-extensions' ),
					'overlay' => __( 'Picture (text over the picture)', 'studiare-extensions' ),
					'minimal' => __( 'Minimal (no box, cart on the picture)', 'studiare-extensions' ),
					'classic' => __( 'Classic shop (centred, full-width buy button)', 'studiare-extensions' ),
					'list'    => __( 'Row (small picture beside the text)', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'numbers',
			array(
				'label'       => __( 'Numbers (1, 2, 3…)', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'For lists such as "Top 5 best sellers".', 'studiare-extensions' ),
				'condition'   => array( 'card' => 'list' ),
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'   => __( 'Picture shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '16/10',
				'options' => self::ratio_options(),
			)
		);

		$this->add_control(
			'badge',
			array(
				'label'       => __( 'Badge', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Discount, "Featured" or "New", whichever applies first.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'excerpt',
			array(
				'label'     => __( 'Short description', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'card' => 'shop' ),
			)
		);

		$this->add_control(
			'rating',
			array(
				'label'     => __( 'Rating', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'card!' => 'overlay' ),
			)
		);

		$meta = array( '' => __( 'None', 'studiare-extensions' ) );
		foreach ( Parts::info_sources() as $key => $source ) {
			if ( ! in_array( $key, array( 'meta', 'text' ), true ) ) {
				$meta[ $key ] = $source['label'];
			}
		}

		$this->add_control(
			'meta',
			array(
				'label'     => __( 'Fact beside the rating', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'duration',
				'options'   => $meta,
				'condition' => array( 'card' => array( 'shop', 'compact', 'minimal', 'list' ) ),
			)
		);

		$this->add_control(
			'cart',
			array(
				'label'   => __( 'Add-to-cart button', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'more_text',
			array(
				'label'     => __( 'Details link', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Details', 'studiare-extensions' ),
				'condition' => array( 'card' => 'shop' ),
			)
		);

		$this->add_control(
			'buy_text',
			array(
				'label'     => __( 'Buy button', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Buy', 'studiare-extensions' ),
				'condition' => array(
					'card' => array( 'compact', 'classic' ),
					'cart' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Cards', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-products__grid' );
		$this->add_box_style( 'card', '.stx-hcard', array( 'shadow' => true ) );
		$this->add_text_style(
			'title',
			'.stx-hcard__title',
			array(
				'label' => __( 'Title', 'studiare-extensions' ),
				'hover' => '.stx-hcard__title a:hover',
			)
		);
		$this->add_text_style( 'kind', '.stx-hcard__kind', array( 'label' => __( 'Category label', 'studiare-extensions' ) ) );
		$this->add_text_style( 'price', '.stx-hcard__price', array( 'label' => __( 'Price', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		if ( ! Context::has_woo() ) {
			$this->editor_hint( __( 'WooCommerce is not active.', 'studiare-extensions' ) );
			return;
		}

		$s     = $this->get_settings_for_display();
		$count = max( 1, min( 12, (int) $s['count'] ) );
		$id    = 'stx-products-' . $this->get_id();

		$panels = array(
			array(
				'id'    => $id . '-all',
				'label' => (string) $s['all_label'],
				'url'   => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
				'ids'   => $this->query_ids( $s, $count ),
			),
		);

		if ( 'yes' === $s['filter'] ) {
			foreach ( $this->chip_terms( $s ) as $term ) {
				$ids = $this->query_ids( $s, $count, (int) $term->term_id );
				// A button that would show an empty grid is left out.
				if ( $ids ) {
					$panels[] = array(
						'id'    => $id . '-' . $term->term_id,
						'label' => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
						'url'   => (string) get_term_link( $term ),
						'ids'   => $ids,
					);
				}
			}
		}

		if ( ! $panels[0]['ids'] && '' === (string) $s['empty_text'] ) {
			$this->editor_hint( __( 'No products match these settings yet.', 'studiare-extensions' ) );
			return;
		}

		$card = array(
			'style'     => $s['card'],
			'badge'     => 'yes' === $s['badge'],
			'excerpt'   => 'yes' === $s['excerpt'],
			'rating'    => 'yes' === $s['rating'],
			'meta'      => (string) $s['meta'],
			'cart'      => 'yes' === $s['cart'],
			'more_text' => (string) $s['more_text'],
			'buy_text'  => (string) $s['buy_text'],
		);

		printf( '<div class="stx-products" data-stx-filter style="--stx-ratio:%s">', esc_attr( $s['ratio'] ) );
		$this->render_header( $s, $panels );

		foreach ( $panels as $index => $panel ) {
			printf( '<div class="stx-products__panel" id="%1$s"%2$s>', esc_attr( $panel['id'] ), $index ? ' hidden' : '' );

			if ( ! $panel['ids'] ) {
				echo '<p class="stx-empty-note">' . esc_html( $s['empty_text'] ) . '</p>';
			} else {
				$this->render_cards( $panel, $card, $s );
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * One panel's cards: a grid, or a slide row with its arrow buttons.
	 *
	 * @param array $panel Panel (id and product IDs).
	 * @param array $card  Card options.
	 * @param array $s     Settings.
	 */
	private function render_cards( array $panel, array $card, array $s ): void {
		$slider = 'yes' === $s['slider'] && 'list' !== $s['card'];
		$track  = $panel['id'] . '-cards';

		if ( $slider ) {
			echo '<div class="stx-rail" data-stx-rail>';
		}

		$rows = 'list' === $s['card'];

		printf(
			'<div class="stx-products__grid%1$s%2$s" id="%3$s">',
			// On phones a slide row looks like the swipe row; rows stack instead.
			$slider ? ' stx-rail__track stx-swipe' : ( 'yes' === $s['mobile_scroll'] && ! $rows ? ' stx-swipe' : '' ),
			$rows && 'yes' === $s['numbers'] ? ' stx-products__grid--numbers' : '',
			esc_attr( $track )
		);
		foreach ( $panel['ids'] as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				echo Cards::product( $product, $card ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Cards.
			}
		}
		echo '</div>';

		if ( $slider ) {
			$arrows = array(
				'prev' => __( 'Previous products', 'studiare-extensions' ),
				'next' => __( 'Next products', 'studiare-extensions' ),
			);
			foreach ( $arrows as $dir => $label ) {
				// Hidden until home.js finds more cards than fit.
				printf(
					'<button type="button" class="stx-rail__arrow stx-rail__arrow--%1$s" data-stx-step="%2$s" aria-controls="%3$s" aria-label="%4$s" hidden>%5$s</button>',
					esc_attr( $dir ),
					'prev' === $dir ? '-1' : '1',
					esc_attr( $track ),
					esc_attr( $label ),
					self::CHEVRON // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG.
				);
			}
			echo '</div>';
		}
	}

	/**
	 * Section title and category buttons, in one row when both are set.
	 *
	 * @param array $s      Settings.
	 * @param array $panels Panels (the first is "All").
	 */
	private function render_header( array $s, array $panels ): void {
		$chips = '';

		if ( count( $panels ) > 1 ) {
			foreach ( $panels as $index => $panel ) {
				$chips .= sprintf(
					'<a class="stx-filter__chip%1$s" href="%2$s" data-stx-panel="%3$s" aria-controls="%3$s"%4$s>%5$s</a>',
					$index ? '' : ' is-active',
					esc_url( $panel['url'] ),
					esc_attr( $panel['id'] ),
					$index ? '' : ' aria-current="true"',
					esc_html( $panel['label'] )
				);
			}
			$chips = '<nav class="stx-filter" aria-label="' . esc_attr__( 'Product categories', 'studiare-extensions' ) . '">' . $chips . '</nav>';
		}

		if ( '' !== (string) $s['title'] ) {
			$title = '<h2 class="stx-heading__title">' . esc_html( $s['title'] ) . '</h2>';
			echo '<div class="stx-heading stx-heading--md stx-heading--rule stx-products__head">' . Heading::row( $title, $chips, true ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		} elseif ( '' !== $chips ) {
			echo '<div class="stx-products__head">' . $chips . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
	}

	/**
	 * Visible product IDs for the settings, optionally within one category.
	 *
	 * @param array $s     Settings.
	 * @param int   $count Number of products.
	 * @param int   $term  Category to narrow to (0 for none).
	 * @return int[]
	 */
	private function query_ids( array $s, int $count, int $term = 0 ): array {
		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $count,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'tax_query'           => self::visibility_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- WooCommerce's own catalog visibility.
		);

		switch ( $s['source'] ) {
			case 'best_selling':
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WooCommerce's sales counter.
				$args['orderby']  = array(
					'meta_value_num' => 'DESC',
					'date'           => 'DESC',
				);
				break;
			case 'top_rated':
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WooCommerce's rating cache.
				$args['orderby']  = array(
					'meta_value_num' => 'DESC',
					'date'           => 'DESC',
				);
				break;
			case 'featured':
				$args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
				);
				break;
			case 'on_sale':
				$on_sale          = wc_get_product_ids_on_sale();
				$args['post__in'] = $on_sale ? $on_sale : array( 0 );
				break;
			case 'category':
				$cats = array_filter( array_map( 'intval', (array) $s['categories'] ) );
				if ( $cats ) {
					$args['tax_query'][] = self::category_clause( $cats );
				}
				break;
			case 'manual':
				$ids                    = array_filter( array_map( 'absint', explode( ',', (string) $s['ids'] ) ) );
				$args['post__in']       = $ids ? $ids : array( 0 );
				$args['orderby']        = 'post__in';
				$args['posts_per_page'] = max( $count, count( $ids ) );
				break;
		}

		if ( $term ) {
			$args['tax_query'][] = self::category_clause( array( $term ) );
		}

		if ( 'all' !== $s['kind'] ) {
			$args['meta_query'] = self::kind_query( 'courses' === $s['kind'] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one indexed flag.
		}

		return array_map( 'intval', get_posts( $args ) );
	}

	/**
	 * Categories for the buttons: the chosen ones, else the biggest top-level ones.
	 *
	 * @param array $s Settings.
	 * @return \WP_Term[]
	 */
	private function chip_terms( array $s ): array {
		$chosen = array_filter( array_map( 'intval', (array) $s['filter_terms'] ) );

		$args = $chosen
			? array(
				'include' => $chosen,
				'orderby' => 'include',
			)
			: array(
				'parent'     => 0,
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => self::AUTO_CHIPS,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			);

		$terms = get_terms( array_merge( array( 'taxonomy' => 'product_cat' ), $args ) );

		return is_array( $terms ) ? $terms : array();
	}

	/** Leaves out products hidden from the catalogue (and out-of-stock ones when the shop hides them). */
	private static function visibility_query(): array {
		$terms  = wc_get_product_visibility_term_ids();
		$hidden = array( $terms['exclude-from-catalog'] ?? 0 );

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$hidden[] = $terms['outofstock'] ?? 0;
		}

		return array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array_filter( $hidden ),
				'operator' => 'NOT IN',
			),
		);
	}

	/**
	 * @param int[] $term_ids Category IDs (children included).
	 */
	private static function category_clause( array $term_ids ): array {
		return array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => $term_ids,
			'include_children' => true,
		);
	}

	/**
	 * Studiare marks courses with `_studiare_course = yes`.
	 *
	 * @param bool $courses Courses (true) or everything else (false).
	 */
	private static function kind_query( bool $courses ): array {
		if ( $courses ) {
			return array(
				array(
					'key'   => '_studiare_course',
					'value' => 'yes',
				),
			);
		}

		return array(
			'relation' => 'OR',
			array(
				'key'     => '_studiare_course',
				'value'   => 'yes',
				'compare' => '!=',
			),
			array(
				'key'     => '_studiare_course',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/** @return array<string, string> Category ID → name, for the pickers. */
	private static function category_options(): array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
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

	/** @return array<string, string> Aspect ratio → label. */
	public static function ratio_options(): array {
		return array(
			'1/1'   => __( 'Square', 'studiare-extensions' ),
			'4/3'   => '4:3',
			'16/10' => '16:10',
			'16/9'  => '16:9',
			'3/4'   => __( 'Portrait 3:4', 'studiare-extensions' ),
		);
	}
}
