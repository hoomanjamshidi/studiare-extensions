<?php
/**
 * Fixed buy bar for phones: price and a buy button, shown once the main
 * add-to-cart form scrolls out of view. Sits above the Studiare+ bottom
 * navigation when both are on.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Mobile_Buy_Bar extends Base {

	public function get_name(): string {
		return 'stx-mobile-buy-bar';
	}

	public function get_title(): string {
		return __( 'Mobile buy bar', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-cart-solid';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Buy bar', 'studiare-extensions' ) );

		$this->add_control(
			'info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Fixed to the bottom of the screen on phones (and tablets if chosen). In the editor it is shown in place so you can style it.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'devices',
			array(
				'label'   => __( 'Show on', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'mobile',
				'options' => array(
					'mobile' => __( 'Phones', 'studiare-extensions' ),
					'tablet' => __( 'Phones and tablets', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'reveal',
			array(
				'label'   => __( 'Appears', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'scroll',
				'options' => array(
					'scroll' => __( 'After the main buy button scrolls away', 'studiare-extensions' ),
					'always' => __( 'Always', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'   => __( 'Button text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Add to cart', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'in_cart_text',
			array(
				'label'   => __( 'Text when in cart', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'In your cart · Checkout', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'owned_text',
			array(
				'label'   => __( 'Text for enrolled students', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Go to lessons', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Buy bar', 'studiare-extensions' ) );
		$this->add_control(
			'bar_bg',
			array(
				'label'     => __( 'Background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-buybar' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'price', '.stx-buybar .stx-price__now', array( 'label' => __( 'Price', 'studiare-extensions' ) ) );
		$this->add_control(
			'button_heading',
			array(
				'label'     => __( 'Button', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_button_style( 'btn', '.stx-buybar__btn' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$id     = $product->get_id();
				$action = $this->action( $product, $s );

				if ( ! $action ) {
					return;
				}

				printf(
					'<div class="stx-buybar stx-buybar--%1$s%2$s" data-stx-buybar="%3$s">',
					esc_attr( $s['devices'] ),
					$this->in_editor() ? ' is-editor' : '',
					esc_attr( $s['reveal'] )
				);

				echo '<div class="stx-buybar__price">' . Parts::price( $product, array( 'badge' => false ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Parts.

				if ( 'form' === $action['type'] ) {
					printf(
						'<form class="stx-buybar__form" method="post" action="%1$s"><input type="hidden" name="add-to-cart" value="%2$d"><button type="submit" class="stx-buybar__btn">%3$s</button></form>',
						esc_url( $product->get_permalink() ),
						(int) $id,
						esc_html( $action['label'] )
					);
				} else {
					printf( '<a class="stx-buybar__btn" href="%1$s">%2$s</a>', esc_url( $action['url'] ), esc_html( $action['label'] ) );
				}

				echo '</div>';
			}
		);
	}

	/**
	 * What the bar's button does for this product and visitor.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $s       Settings.
	 * @return array{type:string, label:string, url?:string}|null
	 */
	private function action( \WC_Product $product, array $s ): ?array {
		$id = $product->get_id();

		if ( Context::is_course( $id ) && Parts::user_bought( $id ) ) {
			return array(
				'type'  => 'link',
				'label' => (string) $s['owned_text'],
				'url'   => '#stx-curriculum',
			);
		}

		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( (int) ( $item['product_id'] ?? 0 ) === $id && $product->is_sold_individually() ) {
					return array(
						'type'  => 'link',
						'label' => (string) $s['in_cart_text'],
						'url'   => wc_get_cart_url(),
					);
				}
			}
		}

		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return null;
		}

		if ( $product->is_type( 'simple' ) ) {
			return array(
				'type'  => 'form',
				'label' => (string) $s['button_text'],
			);
		}

		// Variable/grouped products need the full form: jump to it.
		return array(
			'type'  => 'link',
			'label' => (string) $s['button_text'],
			'url'   => '#stx-buy',
		);
	}
}
