<?php
/**
 * Product spotlight ("deal of the day"): one course or product large, with
 * its picture, price and discount, a countdown to the end of its sale (from
 * the sale dates in WooCommerce), a sold/left bar for products with stock,
 * and buy and details buttons.
 *
 * The product is picked by hand, or automatically: the newest one on sale,
 * else the newest featured one, else the newest one, so the section is never
 * empty on a new site.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Cards;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Spotlight extends Home_Base {

	public function get_name(): string {
		return 'stx-product-spotlight';
	}

	public function get_title(): string {
		return __( 'Product spotlight', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-single-product';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'offer', 'deal', 'sale', 'product', 'course', 'پیشنهاد' ) );
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Product', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Which product', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'   => __( 'Automatic (on sale, else featured, else newest)', 'studiare-extensions' ),
					'manual' => __( 'Hand-picked', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'studiare-extensions' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => '',
				'description' => __( 'The ID is shown when you hover a product in Products → All products.', 'studiare-extensions' ),
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'label',
			array(
				'label'   => __( 'Label', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Special offer', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'excerpt',
			array(
				'label'   => __( 'Short description', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'countdown',
			array(
				'label'       => __( 'Countdown to the end of the sale', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Shown when the sale price has an end date (Product data → General → Schedule).', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'countdown_title',
			array(
				'label'     => __( 'Text above the countdown', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'The offer ends in', 'studiare-extensions' ),
				'condition' => array( 'countdown' => 'yes' ),
			)
		);

		$this->add_control(
			'stock',
			array(
				'label'       => __( 'Sold / left bar', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'For products whose stock WooCommerce manages.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'buy_text',
			array(
				'label'   => __( 'Buy button', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Add to cart', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'more_text',
			array(
				'label'       => __( 'Details button', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'View details', 'studiare-extensions' ),
				'description' => __( 'Leave empty to hide.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'picture_end',
			array(
				'label'   => __( 'Picture at the end side', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'   => __( 'Picture shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4/3',
				'options' => Product_Grid::ratio_options(),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Box', 'studiare-extensions' ) );
		$this->add_box_style( 'box', '.stx-spot', array( 'shadow' => true ) );
		$this->add_text_style( 'title', '.stx-spot__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'price', '.stx-spot__price', array( 'label' => __( 'Price', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		if ( ! Context::has_woo() ) {
			$this->editor_hint( __( 'WooCommerce is not active.', 'studiare-extensions' ) );
			return;
		}

		$s       = $this->get_settings_for_display();
		$product = $this->pick( $s );

		if ( ! $product ) {
			$this->editor_hint( __( 'No product to show yet.', 'studiare-extensions' ) );
			return;
		}

		$link  = esc_url( $product->get_permalink() );
		$badge = Cards::product_badge( $product );
		$media = $product->get_image_id()
			? $product->get_image(
				'woocommerce_single',
				array(
					'class'   => 'stx-spot__img',
					'loading' => 'lazy',
				)
			)
			: Cards::placeholder( '', 'image' );

		printf(
			'<div class="stx-spot%1$s" style="--stx-ratio:%2$s">',
			'yes' === $s['picture_end'] ? ' stx-spot--end' : '',
			esc_attr( $s['ratio'] )
		);

		printf(
			'<div class="stx-spot__top"><a class="stx-spot__media" href="%1$s" tabindex="-1" aria-hidden="true">%2$s</a>%3$s</div>',
			$link, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			$media, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup or escaped placeholder.
			'' !== $badge ? '<span class="stx-spot__badge">' . esc_html( $badge ) . '</span>' : ''
		);

		echo '<div class="stx-spot__body">';

		if ( '' !== (string) $s['label'] ) {
			echo '<span class="stx-spot__label">' . self::icon( 'fire' ) . esc_html( $s['label'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
		}

		$kind = Cards::product_kind( $product );
		if ( '' !== $kind ) {
			echo '<span class="stx-spot__kind">' . esc_html( $kind ) . '</span>';
		}

		printf( '<h3 class="stx-spot__title"><a href="%1$s">%2$s</a></h3>', $link, esc_html( $product->get_name() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.

		echo Parts::rating( $product, array( 'link' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.

		if ( 'yes' === $s['excerpt'] ) {
			$excerpt = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 30 );
			if ( '' !== $excerpt ) {
				echo '<p class="stx-spot__excerpt">' . esc_html( $excerpt ) . '</p>';
			}
		}

		echo Cards::course_facts( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Cards.

		$price = $product->get_price_html();
		if ( '' !== $price ) {
			echo '<div class="stx-spot__price">' . self::digits_html( $price ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price markup.
		}

		if ( 'yes' === $s['countdown'] ) {
			$this->render_countdown( $product, (string) $s['countdown_title'] );
		}

		if ( 'yes' === $s['stock'] ) {
			$this->render_stock( $product );
		}

		echo '<div class="stx-spot__actions">';
		echo Cards::cart_link( $product, 'stx-btn stx-btn--accent stx-btn--lg stx-spot__buy', self::icon( 'cart' ) . '<span>' . esc_html( $s['buy_text'] ) . '</span>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Cards.
		if ( '' !== (string) $s['more_text'] ) {
			printf( '<a class="stx-btn stx-btn--outline stx-btn--lg stx-spot__more" href="%1$s">%2$s</a>', $link, esc_html( $s['more_text'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
		echo '</div></div></div>';
	}

	/**
	 * Countdown to the end of the product's sale, when it has one.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $title   Text above it.
	 */
	private function render_countdown( \WC_Product $product, string $title ): void {
		$end  = $product->is_on_sale() ? $product->get_date_on_sale_to() : null;
		$left = $end ? $end->getTimestamp() - time() : 0;

		if ( $left <= 0 ) {
			$this->editor_hint( __( 'The countdown appears when the product\'s sale price has an end date.', 'studiare-extensions' ) );
			return;
		}

		echo '<div class="stx-spot__timer">';
		if ( '' !== $title ) {
			echo '<span class="stx-spot__timer-title">' . esc_html( $title ) . '</span>';
		}
		echo Countdown::markup( $end->getTimestamp(), $left, 0, Countdown::units() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in markup().
		echo '</div>';
	}

	/**
	 * How much of the stock is sold, for products WooCommerce counts.
	 *
	 * @param \WC_Product $product Product.
	 */
	private function render_stock( \WC_Product $product ): void {
		$left = $product->managing_stock() ? (int) $product->get_stock_quantity() : 0;
		$sold = (int) $product->get_total_sales();

		if ( $left <= 0 || ! $sold ) {
			return;
		}

		printf(
			'<div class="stx-spot__stock"><div class="stx-spot__stock-text"><span>%1$s</span><span>%2$s</span></div><span class="stx-spot__bar" style="--stx-sold:%3$s"><span></span></span></div>',
			/* translators: %s: number of items sold. */
			esc_html( sprintf( __( 'Sold: %s', 'studiare-extensions' ), self::num( $sold ) ) ),
			/* translators: %s: number of items left. */
			esc_html( sprintf( __( 'Only %s left', 'studiare-extensions' ), self::num( $left ) ) ),
			esc_attr( (string) round( $sold / ( $sold + $left ), 3 ) )
		);
	}

	/**
	 * The product to show.
	 *
	 * @param array $s Settings.
	 */
	private function pick( array $s ): ?\WC_Product {
		if ( 'manual' === $s['source'] ) {
			$product = wc_get_product( (int) $s['product_id'] );
			return $product && 'publish' === $product->get_status() ? $product : null;
		}

		$base = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		$on_sale = wc_get_product_ids_on_sale();
		$queries = array(
			$on_sale ? array( 'post__in' => $on_sale ) : null,
			array(
				'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one indexed lookup.
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'name',
						'terms'    => 'featured',
					),
				),
			),
			array(),
		);

		foreach ( array_filter( $queries, 'is_array' ) as $query ) {
			$ids = get_posts( array_merge( $base, $query ) );
			if ( $ids ) {
				$product = wc_get_product( (int) $ids[0] );
				if ( $product ) {
					return $product;
				}
			}
		}

		return null;
	}
}
