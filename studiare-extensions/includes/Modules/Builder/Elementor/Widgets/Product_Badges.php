<?php
/**
 * Small chips: kind (course/product), category, discount, level, custom text…
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use StudiareExt\Modules\Builder\Elementor\Parts;

defined( 'ABSPATH' ) || exit;

final class Product_Badges extends Base {

	public function get_name(): string {
		return 'stx-product-badges';
	}

	public function get_title(): string {
		return __( 'Product badges', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-tags';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Badges', 'studiare-extensions' ) );
		$this->add_sample_note();

		$repeater = new Repeater();
		$repeater->add_control(
			'source',
			array(
				'label'   => __( 'Show', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'kind',
				'options' => array(
					'kind'     => __( 'Kind (course / category)', 'studiare-extensions' ),
					'category' => __( 'Category', 'studiare-extensions' ),
					'sale'     => __( 'Discount', 'studiare-extensions' ),
					'level'    => __( 'Course level', 'studiare-extensions' ),
					'featured' => __( 'Featured', 'studiare-extensions' ),
					'new'      => __( 'New (last 30 days)', 'studiare-extensions' ),
					'custom'   => __( 'Custom text', 'studiare-extensions' ),
				),
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'       => __( 'Text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Leave empty for the automatic text. For discounts, {percent} is replaced with the percentage.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'variant',
			array(
				'label'   => __( 'Style', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'soft',
				'options' => array(
					'soft'    => __( 'Accent (light)', 'studiare-extensions' ),
					'outline' => __( 'Outline', 'studiare-extensions' ),
					'dark'    => __( 'Dark', 'studiare-extensions' ),
					'accent'  => __( 'Accent', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'badges',
			array(
				'label'       => __( 'Badges', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'source'  => 'kind',
						'variant' => 'soft',
					),
					array(
						'source'  => 'category',
						'variant' => 'outline',
					),
				),
				'title_field' => '{{{ source }}}',
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'studiare-extensions' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Start', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::start_icon(),
					),
					'center'     => array(
						'title' => __( 'Center', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'End', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::end_icon(),
					),
				),
				'selectors' => array( '{{WRAPPER}} .stx-badges' => 'justify-content: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Badges', 'studiare-extensions' ) );
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'selector' => '{{WRAPPER}} .stx-badge',
			)
		);
		$this->add_responsive_control(
			'badge_radius',
			array(
				'label'      => __( 'Border radius', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-badge' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_product(
			function ( \WC_Product $product ) use ( $s ) {
				$chips = '';
				$seen  = array();
				foreach ( (array) $s['badges'] as $badge ) {
					$text = $this->badge_text( $product, $badge );
					// A product with one category would otherwise show it twice (kind + category).
					if ( '' !== $text && ! isset( $seen[ $text ] ) ) {
						$seen[ $text ] = true;
						$chips        .= '<span class="stx-badge stx-badge--' . esc_attr( $badge['variant'] ) . '">' . esc_html( self::digits( $text ) ) . '</span>';
					}
				}

				if ( '' !== $chips ) {
					echo '<div class="stx-badges">' . $chips . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				}
			}
		);
	}

	/**
	 * @param \WC_Product $product Product.
	 * @param array       $badge   Repeater item.
	 */
	private function badge_text( \WC_Product $product, array $badge ): string {
		$custom = trim( (string) $badge['text'] );

		switch ( $badge['source'] ) {
			case 'kind':
				return '' !== $custom ? $custom : Parts::kind_label( $product );

			case 'category':
				$terms = get_the_terms( $product->get_id(), 'product_cat' );
				if ( ! $terms || is_wp_error( $terms ) ) {
					return '';
				}
				// Skip the category already shown as "kind" for regular products.
				$pick = count( $terms ) > 1 && ! \StudiareExt\Modules\Builder\Context::is_course( $product->get_id() ) ? $terms[1] : $terms[0];
				return html_entity_decode( $pick->name, ENT_QUOTES, 'UTF-8' );

			case 'sale':
				if ( ! $product->is_on_sale() || ! $product->is_type( 'simple' ) || ! (float) $product->get_regular_price() ) {
					return '';
				}
				$percent = (int) round( ( 1 - (float) $product->get_price() / (float) $product->get_regular_price() ) * 100 );
				/* translators: {percent} is replaced with the discount percentage. */
				$format = '' !== $custom ? $custom : __( '{percent}% off', 'studiare-extensions' );
				return $percent > 0 ? str_replace( '{percent}', (string) $percent, $format ) : '';

			case 'level':
				return Parts::info_value( $product, 'level' );

			case 'featured':
				return $product->is_featured() ? ( '' !== $custom ? $custom : __( 'Featured', 'studiare-extensions' ) ) : '';

			case 'new':
				$created = $product->get_date_created();
				return $created && $created->getTimestamp() > time() - 30 * DAY_IN_SECONDS ? ( '' !== $custom ? $custom : __( 'New', 'studiare-extensions' ) ) : '';

			case 'custom':
				return $custom;
		}

		return '';
	}
}
