<?php
/**
 * Add to cart: WooCommerce's own form (simple, variable, grouped, external),
 * so stock, variations and quantity rules keep working. Adds course-aware
 * states: "already enrolled" and "already in the cart".
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Add_To_Cart extends Base {

	public function get_name(): string {
		return 'stx-add-to-cart';
	}

	public function get_title(): string {
		return __( 'Add to cart', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-add-to-cart';
	}

	public function get_script_depends(): array {
		return array( 'stx-builder', 'wc-add-to-cart-variation' );
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Add to cart', 'studiare-extensions' ) );
		$this->add_sample_note();

		$this->add_control(
			'button_text',
			array(
				'label'       => __( 'Button text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'WooCommerce default', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'button_icon',
			array(
				'label'   => __( 'Button icon', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => self::icon_options(),
			)
		);

		$this->add_control(
			'full_width',
			array(
				'label'   => __( 'Full-width button', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'quantity',
			array(
				'label'   => __( 'Quantity field', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto' => __( 'When WooCommerce allows it', 'studiare-extensions' ),
					'hide' => __( 'Hidden', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'variation_style',
			array(
				'label'   => __( 'Variation choices', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'buttons',
				'options' => array(
					'buttons' => __( 'Buttons', 'studiare-extensions' ),
					'select'  => __( 'Dropdowns', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'in_cart',
			array(
				'label'       => __( 'Already in cart', 'studiare-extensions' ),
				'description' => __( 'For items sold one per order (like courses), show a checkout link instead of the form.', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'in_cart_text',
			array(
				'label'     => __( 'Checkout link text', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'In your cart · Checkout', 'studiare-extensions' ),
				'condition' => array( 'in_cart' => 'yes' ),
			)
		);

		$this->add_control(
			'owned',
			array(
				'label'       => __( 'Enrolled students', 'studiare-extensions' ),
				'description' => __( 'Students who bought the course see a link to their lessons instead of the form.', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'owned_text',
			array(
				'label'     => __( 'Enrolled message', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'You are enrolled in this course', 'studiare-extensions' ),
				'condition' => array( 'owned' => 'yes' ),
			)
		);

		$this->add_control(
			'owned_button',
			array(
				'label'     => __( 'Enrolled button text', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Go to lessons', 'studiare-extensions' ),
				'condition' => array( 'owned' => 'yes' ),
			)
		);

		$this->add_control(
			'owned_target',
			array(
				'label'     => __( 'Enrolled button opens', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'curriculum',
				'options'   => array(
					'curriculum' => __( 'The lessons on this page', 'studiare-extensions' ),
					'account'    => __( 'My courses (account)', 'studiare-extensions' ),
				),
				'condition' => array( 'owned' => 'yes' ),
			)
		);

		$this->add_control(
			'note',
			array(
				'label'       => __( 'Note under the button', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'e.g. Pay in 4 instalments', 'studiare-extensions' ),
				'label_block' => true,
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'note_icon',
			array(
				'label'     => __( 'Note icon', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'wallet',
				'options'   => self::icon_options(),
				'condition' => array( 'note!' => '' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_button', __( 'Button', 'studiare-extensions' ) );
		$this->add_button_style( 'btn', '.stx-atc__btn' );
		$this->end_controls_section();

		$this->start_style_section( 'section_fields', __( 'Quantity & variations', 'studiare-extensions' ) );
		$this->add_control(
			'field_bg',
			array(
				'label'     => __( 'Field background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-atc' => '--stx-field-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'field_border',
			array(
				'label'     => __( 'Field border', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-atc' => '--stx-field-line: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'choice_active',
			array(
				'label'     => __( 'Selected choice', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-atc' => '--stx-choice-active: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'note', '.stx-atc__note', array( 'label' => __( 'Note', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$classes = array(
					'stx-atc',
					'yes' === $s['full_width'] ? 'stx-atc--full' : 'stx-atc--auto',
					'stx-atc--vars-' . $s['variation_style'],
				);
				if ( 'hide' === $s['quantity'] ) {
					$classes[] = 'stx-atc--no-qty';
				}

				// `#stx-buy` is the jump target of the mobile buy bar.
				echo '<div id="stx-buy" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-stx-atc>';
				$this->render_action( $product, $s );

				if ( '' !== (string) $s['note'] ) {
					printf(
						'<p class="stx-atc__note">%1$s<span>%2$s</span></p>',
						'' !== $s['note_icon'] ? self::icon( $s['note_icon'] ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
						esc_html( self::digits( $s['note'] ) )
					);
				}

				echo '</div>';
			}
		);
	}

	/**
	 * @param \WC_Product $product Product.
	 * @param array       $s       Settings.
	 */
	private function render_action( \WC_Product $product, array $s ): void {
		$id = $product->get_id();

		if ( 'yes' === $s['owned'] && Context::is_course( $id ) && Parts::user_bought( $id ) ) {
			$url = 'account' === $s['owned_target'] && function_exists( 'wc_get_account_endpoint_url' )
				? add_query_arg( 'courseid', $id, wc_get_account_endpoint_url( 'my-courses' ) )
				: '#stx-curriculum';

			printf(
				'<div class="stx-atc__owned">%1$s<span>%2$s</span></div><a class="stx-atc__btn stx-atc__cta" href="%3$s">%4$s</a>',
				self::icon( 'check' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
				esc_html( $s['owned_text'] ),
				esc_url( $url ),
				esc_html( $s['owned_button'] )
			);
			return;
		}

		if ( 'yes' === $s['in_cart'] && $product->is_sold_individually() && $this->in_cart( $id ) ) {
			printf(
				'<a class="stx-atc__btn stx-atc__cta is-in-cart" href="%1$s">%2$s<span>%3$s</span></a>',
				esc_url( wc_get_cart_url() ),
				self::icon( 'check' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
				esc_html( $s['in_cart_text'] )
			);
			return;
		}

		$text = trim( (string) $s['button_text'] );
		$icon = '' !== $s['button_icon'] ? self::icon( $s['button_icon'] ) : '';

		$label_filter = static function ( $label ) use ( $text ) {
			return '' !== $text ? $text : $label;
		};

		add_filter( 'woocommerce_product_single_add_to_cart_text', $label_filter, 99 );
		ob_start();
		woocommerce_template_single_add_to_cart();
		$form = (string) ob_get_clean();
		remove_filter( 'woocommerce_product_single_add_to_cart_text', $label_filter, 99 );

		// Our button class (and optional icon) on WooCommerce's submit button
		// (a link for external/affiliate products).
		$form = (string) preg_replace_callback(
			'/<(button|a)(\s[^>]*class="[^"]*single_add_to_cart_button[^"]*"[^>]*)>(.*?)<\/\1>/s',
			static function ( $tag ) use ( $icon ) {
				$attrs = preg_replace( '/class="/', 'class="stx-atc__btn ', $tag[2], 1 );
				return '<' . $tag[1] . $attrs . '>' . $icon . '<span>' . $tag[3] . '</span></' . $tag[1] . '>';
			},
			$form,
			1
		);

		if ( '' === trim( $form ) ) {
			$this->editor_hint( __( 'This product cannot be bought right now (out of stock or no price).', 'studiare-extensions' ) );
		}

		echo $form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce template output.
	}

	/**
	 * @param int $product_id Product ID.
	 */
	private function in_cart( int $product_id ): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( (int) ( $item['product_id'] ?? 0 ) === $product_id ) {
				return true;
			}
		}

		return false;
	}
}
