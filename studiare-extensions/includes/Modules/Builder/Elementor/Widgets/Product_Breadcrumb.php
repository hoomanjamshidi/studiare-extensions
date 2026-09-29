<?php
/**
 * Breadcrumb trail: Home › Shop › Category › Product.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Product_Breadcrumb extends Base {

	public function get_name(): string {
		return 'stx-product-breadcrumb';
	}

	public function get_title(): string {
		return __( 'Product breadcrumb', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-breadcrumbs';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Breadcrumb', 'studiare-extensions' ) );

		$this->add_control(
			'home_label',
			array(
				'label'       => __( 'Home label', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Home', 'studiare-extensions' ),
				'description' => __( 'Leave empty to hide.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'show_shop',
			array(
				'label'   => __( 'Shop page', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_current',
			array(
				'label'   => __( 'Current product', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'   => __( 'Separator', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '/',
			)
		);

		$this->add_align_control( 'align', '.stx-crumbs' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Breadcrumb', 'studiare-extensions' ) );
		$this->add_text_style( 'link', '.stx-crumbs a', array( 'hover' => '.stx-crumbs a:hover' ) );
		$this->add_text_style( 'current', '.stx-crumbs__current', array( 'label' => __( 'Current item', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			static function ( \WC_Product $product ) use ( $s ) {
				$trail = array();

				if ( '' !== (string) $s['home_label'] ) {
					$trail[] = array( $s['home_label'], home_url( '/' ) );
				}

				if ( 'yes' === $s['show_shop'] && function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ) {
					$trail[] = array( get_the_title( wc_get_page_id( 'shop' ) ), get_permalink( wc_get_page_id( 'shop' ) ) );
				}

				$terms = get_the_terms( $product->get_id(), 'product_cat' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					// Deepest category first, then walk up to the root.
					usort(
						$terms,
						static function ( $a, $b ) {
							return count( get_ancestors( $b->term_id, 'product_cat' ) ) <=> count( get_ancestors( $a->term_id, 'product_cat' ) );
						}
					);
					$term  = $terms[0];
					$chain = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
					foreach ( array_merge( $chain, array( $term->term_id ) ) as $term_id ) {
						$item = get_term( $term_id, 'product_cat' );
						if ( $item && ! is_wp_error( $item ) ) {
							$trail[] = array( html_entity_decode( $item->name, ENT_QUOTES, 'UTF-8' ), get_term_link( $item ) );
						}
					}
				}

				echo '<nav class="stx-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'studiare-extensions' ) . '"><ol>';
				foreach ( $trail as $crumb ) {
					if ( is_string( $crumb[1] ) ) {
						printf( '<li><a href="%1$s">%2$s</a></li><li class="stx-crumbs__sep" aria-hidden="true">%3$s</li>', esc_url( $crumb[1] ), esc_html( $crumb[0] ), esc_html( $s['separator'] ) );
					}
				}
				if ( 'yes' === $s['show_current'] ) {
					printf( '<li class="stx-crumbs__current" aria-current="page">%s</li>', esc_html( $product->get_name() ) );
				}
				echo '</ol></nav>';
			}
		);
	}
}
