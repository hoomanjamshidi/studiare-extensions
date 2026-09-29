<?php
/**
 * Grid of related products/courses.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Related_Products extends Base {

	public function get_name(): string {
		return 'stx-related-products';
	}

	public function get_title(): string {
		return __( 'Related products', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-related';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Products', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Which products', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'related',
				'options' => array(
					'related'   => __( 'Related (same categories/tags, else newest of the same kind)', 'studiare-extensions' ),
					'upsells'   => __( 'Upsells', 'studiare-extensions' ),
					'cross'     => __( 'Cross-sells', 'studiare-extensions' ),
					'same_kind' => __( 'Newest of the same kind (courses / products)', 'studiare-extensions' ),
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

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'studiare-extensions' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors'      => array( '{{WRAPPER}} .stx-pgrid' => '--stx-cols: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'kind',
			array(
				'label'   => __( 'Kind label above the title', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'rating',
			array(
				'label'   => __( 'Rating', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'   => __( 'Image shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '16/10',
				'options' => array(
					'1/1'   => __( 'Square', 'studiare-extensions' ),
					'4/3'   => '4:3',
					'16/10' => '16:10',
					'16/9'  => '16:9',
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_card', __( 'Cards', 'studiare-extensions' ) );
		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Space between', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-pgrid' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_box_style( 'card', '.stx-pcard', array( 'shadow' => true ) );
		$this->add_text_style(
			'title',
			'.stx-pcard__title',
			array(
				'label' => __( 'Title', 'studiare-extensions' ),
				'hover' => '.stx-pcard__title a:hover',
			)
		);
		$this->add_text_style( 'kind', '.stx-pcard__kind', array( 'label' => __( 'Kind label', 'studiare-extensions' ) ) );
		$this->add_text_style( 'price', '.stx-pcard__price', array( 'label' => __( 'Price', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$count = max( 1, min( 12, (int) $s['count'] ) );
				$ids   = $this->product_ids( $product, (string) $s['source'], $count );

				if ( ! $ids ) {
					$this->editor_hint( __( 'No related products for the sample product.', 'studiare-extensions' ) );
					return;
				}

				printf( '<div class="stx-pgrid" style="--stx-ratio:%s">', esc_attr( $s['ratio'] ) );
				foreach ( $ids as $id ) {
					$item = wc_get_product( $id );
					if ( $item && $item->is_visible() ) {
						$card = Parts::card(
							$item,
							array(
								'kind'   => 'yes' === $s['kind'],
								'rating' => 'yes' === $s['rating'],
							)
						);
						echo $card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.
					}
				}
				echo '</div>';
			}
		);
	}

	/**
	 * @param \WC_Product $product Current product.
	 * @param string      $source  Source.
	 * @param int         $count   Number of products.
	 * @return int[]
	 */
	private function product_ids( \WC_Product $product, string $source, int $count ): array {
		switch ( $source ) {
			case 'upsells':
				$ids = $product->get_upsell_ids();
				break;
			case 'cross':
				$ids = $product->get_cross_sell_ids();
				break;
			case 'same_kind':
				$is_course = Context::is_course( $product->get_id() );
				$ids       = array();
				foreach ( wc_get_products(
					array(
						'status'  => 'publish',
						'limit'   => $count * 3,
						'exclude' => array( $product->get_id() ),
						'return'  => 'ids',
					)
				) as $id ) {
					if ( Context::is_course( (int) $id ) === $is_course ) {
						$ids[] = (int) $id;
					}
				}
				break;
			default:
				$ids = wc_get_related_products( $product->get_id(), $count );
				if ( ! $ids ) {
					// Nothing shares a category or tag: show the newest of the same kind instead.
					return $this->product_ids( $product, 'same_kind', $count );
				}
		}

		return array_slice( array_map( 'intval', (array) $ids ), 0, $count );
	}
}
