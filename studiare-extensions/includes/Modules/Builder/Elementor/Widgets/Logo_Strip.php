<?php
/**
 * Logo strip: partners, clients, universities or media logos, in a grid or
 * in a row that scrolls by itself.
 *
 * The moving row is pure CSS: the logos are printed twice (the copy hidden
 * from screen readers) and the track slides by half its width, so it loops
 * without a jump. It pauses on hover and focus, and stands still as a
 * wrapping grid for visitors who prefer reduced motion. A logo without a
 * picture shows its name, so a new site never shows empty boxes.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Logo_Strip extends Home_Base {

	public function get_name(): string {
		return 'stx-logo-strip';
	}

	public function get_title(): string {
		return __( 'Logo strip', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-logo';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'logos', 'brands', 'partners', 'clients', 'carousel', 'لوگو' ) );
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Logos', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'name',
			array(
				'label'   => __( 'Name', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Partner', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'logo',
			array(
				'label' => __( 'Logo', 'studiare-extensions' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => __( 'Link', 'studiare-extensions' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
			)
		);

		$names = array(
			__( 'Tehran University', 'studiare-extensions' ),
			__( 'Digital Academy', 'studiare-extensions' ),
			__( 'Code Studio', 'studiare-extensions' ),
			__( 'Design House', 'studiare-extensions' ),
			__( 'Startup Hub', 'studiare-extensions' ),
			__( 'Media Group', 'studiare-extensions' ),
		);

		$this->add_control(
			'logos',
			array(
				'label'       => __( 'Logos', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array_map(
					static function ( $name ) {
						return array( 'name' => $name );
					},
					$names
				),
				'title_field' => '{{{ name }}}',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'marquee',
				'options' => array(
					'marquee' => __( 'Moving row', 'studiare-extensions' ),
					'grid'    => __( 'Grid', 'studiare-extensions' ),
				),
			)
		);

		$this->add_columns_control( '.stx-logos--grid .stx-logos__list', array( 6, 3, 2 ) );

		$this->add_control(
			'speed',
			array(
				'label'       => __( 'Seconds for one round', 'studiare-extensions' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 30,
				'min'         => 8,
				'max'         => 120,
				'condition'   => array( 'layout' => 'marquee' ),
				'description' => __( 'More seconds is slower.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'gray',
			array(
				'label'       => __( 'Grey until hovered', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Logos in different colours look calmer side by side.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'boxed',
			array(
				'label'   => __( 'Logos in boxes', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Logos', 'studiare-extensions' ) );
		$this->add_responsive_control(
			'logo_height',
			array(
				'label'      => __( 'Logo height', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 20,
						'max' => 120,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-logos' => '--stx-logo-h: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_box_style( 'item', '.stx-logos__item' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$logos = array_filter(
			(array) $s['logos'],
			static function ( $logo ) {
				return '' !== trim( (string) ( $logo['name'] ?? '' ) ) || ! empty( $logo['logo']['url'] );
			}
		);

		if ( ! $logos ) {
			$this->editor_hint( __( 'Add logos in the widget settings.', 'studiare-extensions' ) );
			return;
		}

		$items = '';
		foreach ( $logos as $logo ) {
			$items .= $this->item( $logo );
		}

		$marquee = 'marquee' === $s['layout'];
		$classes = 'stx-logos stx-logos--' . ( $marquee ? 'marquee' : 'grid' )
			. ( 'yes' === $s['gray'] ? ' stx-logos--gray' : '' )
			. ( 'yes' === $s['boxed'] ? ' stx-logos--boxed' : '' );

		printf(
			'<div class="%1$s" style="--stx-logos-time:%2$ss">',
			esc_attr( $classes ),
			esc_attr( (string) max( 8, min( 120, (int) $s['speed'] ) ) )
		);

		echo '<ul class="stx-logos__list">' . $items . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in item().
		if ( $marquee ) {
			// The copy completes the loop; screen readers and the keyboard skip it.
			echo '<ul class="stx-logos__list stx-logos__list--copy" aria-hidden="true" inert>' . $items . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in item().
		}

		echo '</div>';
	}

	/**
	 * One logo: its picture (or its name as text), linked when a link is set.
	 *
	 * @param array $logo Repeater item.
	 */
	private function item( array $logo ): string {
		$name = trim( (string) ( $logo['name'] ?? '' ) );
		$id   = (int) ( $logo['logo']['id'] ?? 0 );
		$url  = (string) ( $logo['logo']['url'] ?? '' );

		if ( $id ) {
			$mark = wp_get_attachment_image(
				$id,
				'medium',
				false,
				array(
					'class'   => 'stx-logos__img',
					'alt'     => $name,
					'loading' => 'lazy',
				)
			);
		} elseif ( '' !== $url && false === strpos( $url, 'placeholder' ) ) {
			$mark = '<img class="stx-logos__img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $name ) . '" loading="lazy">';
		} else {
			$mark = '<span class="stx-logos__name">' . esc_html( $name ) . '</span>';
		}

		$href = (string) ( $logo['link']['url'] ?? '' );
		if ( '' !== $href ) {
			$mark = sprintf(
				'<a class="stx-logos__link" href="%1$s"%2$s>%3$s</a>',
				esc_url( $href ),
				! empty( $logo['link']['is_external'] ) ? ' target="_blank" rel="noopener"' : '',
				$mark
			);
		}

		return '<li class="stx-logos__item">' . $mark . '</li>';
	}
}
