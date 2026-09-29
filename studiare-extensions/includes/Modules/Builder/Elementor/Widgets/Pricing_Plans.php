<?php
/**
 * Pricing plans: subscription or package cards with a price, a period, a
 * list of what is (and is not) included and a button; one plan can be
 * highlighted with a label ("Most popular").
 *
 * Features are typed one per line. A line starting with "-" is shown as not
 * included, so plans are easy to compare.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Pricing_Plans extends Home_Base {

	public function get_name(): string {
		return 'stx-pricing-plans';
	}

	public function get_title(): string {
		return __( 'Pricing plans', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-price-table';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'pricing', 'plans', 'subscription', 'packages', 'اشتراک' ) );
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Plans', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'name',
			array(
				'label'   => __( 'Name', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Plan', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label' => __( 'Short description', 'studiare-extensions' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
		$repeater->add_control(
			'price',
			array(
				'label'       => __( 'Price', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '490,000',
			)
		);
		$repeater->add_control(
			'old_price',
			array(
				'label'       => __( 'Price before discount', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Shown crossed out. Leave empty for none.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'unit',
			array(
				'label'   => __( 'Currency and period', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Toman / month', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'features',
			array(
				'label'       => __( 'Features', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 6,
				'description' => __( 'One per line. Start a line with "-" to show it as not included.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'button_text',
			array(
				'label'   => __( 'Button text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Choose this plan', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'button_link',
			array(
				'label'       => __( 'Button link', 'studiare-extensions' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
			)
		);
		$repeater->add_control(
			'featured',
			array(
				'label'   => __( 'Highlight this plan', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);
		$repeater->add_control(
			'badge',
			array(
				'label'     => __( 'Label on the plan', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Most popular', 'studiare-extensions' ),
				'condition' => array( 'featured' => 'yes' ),
			)
		);

		$this->add_control(
			'plans',
			array(
				'label'       => __( 'Plans', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => self::sample_plans(),
				'title_field' => '{{{ name }}}',
			)
		);

		$this->add_columns_control( '.stx-plans', array( 3, 3, 1 ), 4 );

		$this->add_control(
			'mobile_scroll',
			array(
				'label'       => __( 'Swipe row on phones', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Plans sit side by side and scroll sideways instead of stacking.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Plans', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-plans' );
		$this->add_box_style( 'plan', '.stx-plan', array( 'shadow' => true ) );
		$this->add_text_style( 'name', '.stx-plan__name', array( 'label' => __( 'Name', 'studiare-extensions' ) ) );
		$this->add_text_style( 'price', '.stx-plan__amount', array( 'label' => __( 'Price', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$plans = (array) $s['plans'];

		if ( ! $plans ) {
			$this->editor_hint( __( 'Add plans in the widget settings.', 'studiare-extensions' ) );
			return;
		}

		printf( '<div class="stx-plans%s">', 'yes' === $s['mobile_scroll'] ? ' stx-swipe' : '' );
		foreach ( $plans as $plan ) {
			echo $this->plan( $plan ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in plan().
		}
		echo '</div>';
	}

	/**
	 * One plan card.
	 *
	 * @param array $plan Repeater item.
	 */
	private function plan( array $plan ): string {
		$featured = 'yes' === ( $plan['featured'] ?? '' );
		$html     = '<article class="stx-plan' . ( $featured ? ' stx-plan--featured' : '' ) . '">';

		if ( $featured && '' !== (string) ( $plan['badge'] ?? '' ) ) {
			$html .= '<span class="stx-plan__badge">' . esc_html( $plan['badge'] ) . '</span>';
		}

		$html .= '<h3 class="stx-plan__name">' . esc_html( (string) ( $plan['name'] ?? '' ) ) . '</h3>';
		if ( '' !== (string) ( $plan['text'] ?? '' ) ) {
			$html .= '<p class="stx-plan__text">' . esc_html( $plan['text'] ) . '</p>';
		}

		$price = trim( (string) ( $plan['price'] ?? '' ) );
		if ( '' !== $price ) {
			$old   = trim( (string) ( $plan['old_price'] ?? '' ) );
			$html .= '<div class="stx-plan__price">'
				. ( '' !== $old ? '<del class="stx-plan__old">' . esc_html( self::digits( $old ) ) . '</del>' : '' )
				. '<span class="stx-plan__amount">' . esc_html( self::digits( $price ) ) . '</span>'
				. '<span class="stx-plan__unit">' . esc_html( (string) ( $plan['unit'] ?? '' ) ) . '</span>'
				. '</div>';
		}

		$features = array_filter( array_map( 'trim', preg_split( '/\R/', (string) ( $plan['features'] ?? '' ) ) ) );
		if ( $features ) {
			$html .= '<ul class="stx-plan__list">';
			foreach ( $features as $feature ) {
				$missing = 0 === strpos( $feature, '-' );
				$html   .= sprintf(
					'<li class="%1$s">%2$s<span>%3$s</span>%4$s</li>',
					$missing ? 'is-missing' : 'is-included',
					self::icon( $missing ? 'close' : 'check' ),
					esc_html( self::digits( $missing ? ltrim( substr( $feature, 1 ) ) : $feature ) ),
					$missing ? '<span class="screen-reader-text"> ' . esc_html__( '(not included)', 'studiare-extensions' ) . '</span>' : ''
				);
			}
			$html .= '</ul>';
		}

		$text = (string) ( $plan['button_text'] ?? '' );
		if ( '' !== $text ) {
			$url   = (string) ( $plan['button_link']['url'] ?? '' );
			$html .= sprintf(
				'<a class="stx-btn stx-btn--lg %1$s stx-plan__btn" href="%2$s"%3$s>%4$s</a>',
				$featured ? 'stx-btn--accent' : 'stx-btn--outline',
				esc_url( '' !== $url ? $url : '#' ),
				! empty( $plan['button_link']['is_external'] ) ? ' target="_blank" rel="noopener"' : '',
				esc_html( $text )
			);
		}

		return $html . '</article>';
	}

	/** Three sample plans, so the widget looks finished when dropped in. */
	private static function sample_plans(): array {
		return array(
			array(
				'name'        => __( 'Starter', 'studiare-extensions' ),
				'text'        => __( 'For trying the first courses.', 'studiare-extensions' ),
				'price'       => '290,000',
				'unit'        => __( 'Toman / month', 'studiare-extensions' ),
				'features'    => implode( "\n", array( __( 'All beginner courses', 'studiare-extensions' ), __( 'Exercise files', 'studiare-extensions' ), __( '- Support from teachers', 'studiare-extensions' ), __( '- Certificate', 'studiare-extensions' ) ) ),
				'button_text' => __( 'Start now', 'studiare-extensions' ),
			),
			array(
				'name'        => __( 'Professional', 'studiare-extensions' ),
				'text'        => __( 'Everything you need to grow faster.', 'studiare-extensions' ),
				'price'       => '590,000',
				'old_price'   => '790,000',
				'unit'        => __( 'Toman / month', 'studiare-extensions' ),
				'features'    => implode( "\n", array( __( 'All courses', 'studiare-extensions' ), __( 'Exercise files', 'studiare-extensions' ), __( 'Support from teachers', 'studiare-extensions' ), __( 'Certificate', 'studiare-extensions' ) ) ),
				'button_text' => __( 'Choose this plan', 'studiare-extensions' ),
				'featured'    => 'yes',
				'badge'       => __( 'Most popular', 'studiare-extensions' ),
			),
			array(
				'name'        => __( 'Team', 'studiare-extensions' ),
				'text'        => __( 'For companies and study groups.', 'studiare-extensions' ),
				'price'       => '1,490,000',
				'unit'        => __( 'Toman / month', 'studiare-extensions' ),
				'features'    => implode( "\n", array( __( 'Everything in Professional', 'studiare-extensions' ), __( 'Up to 5 people', 'studiare-extensions' ), __( 'Progress reports', 'studiare-extensions' ), __( 'Live workshops', 'studiare-extensions' ) ) ),
				'button_text' => __( 'Contact us', 'studiare-extensions' ),
			),
		);
	}
}
