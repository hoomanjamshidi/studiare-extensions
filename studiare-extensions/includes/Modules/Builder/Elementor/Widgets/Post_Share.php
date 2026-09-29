<?php
/**
 * Share buttons: Telegram, WhatsApp, X, LinkedIn and email links, plus
 * "Copy link" and the phone's own share sheet. The links work without
 * JavaScript; the two buttons need it, so they stay hidden until blog.js
 * shows them (the share sheet only where the browser has one).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;

defined( 'ABSPATH' ) || exit;

final class Post_Share extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-share';
	}

	public function get_title(): string {
		return __( 'Share buttons', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-share';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Share buttons', 'studiare-extensions' ) );
		$this->add_post_note();

		$this->add_control(
			'label',
			array(
				'label'   => __( 'Text before the buttons', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Share:', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'networks',
			array(
				'label'       => __( 'Networks', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => Post_Parts::share_networks(),
				'default'     => array_keys( Post_Parts::share_networks() ),
			)
		);

		$this->add_control(
			'copy',
			array(
				'label'   => __( '"Copy link" button', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'native',
			array(
				'label'       => __( 'Phone\'s share button', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Opens the phone\'s own share sheet (every installed app). Shown only where the browser supports it.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'look',
			array(
				'label'   => __( 'Look', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'icons',
				'options' => array(
					'icons'   => __( 'Round icons', 'studiare-extensions' ),
					'buttons' => __( 'Buttons with names', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'colors',
			array(
				'label'   => __( 'Colours', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'neutral',
				'options' => array(
					'neutral' => __( 'Site colours (brand colour on hover)', 'studiare-extensions' ),
					'brand'   => __( 'Each network\'s colour', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'stack',
			array(
				'label'       => __( 'One under another', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'For a narrow sticky column beside the text.', 'studiare-extensions' ),
			)
		);

		$this->add_align_control( 'align', '.stx-share' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Share buttons', 'studiare-extensions' ) );
		$this->add_text_style( 'label', '.stx-share__label', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_post(
			static function ( \WP_Post $post ) use ( $s ) {
				$networks = array_intersect_key( Post_Parts::share_networks(), array_flip( (array) $s['networks'] ) );
				$title    = html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );
				$items    = '';

				foreach ( $networks as $network => $name ) {
					$items .= sprintf(
						'<li><a class="stx-share__btn stx-share__btn--%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s">%4$s<span class="stx-share__name">%5$s</span></a></li>',
						esc_attr( $network ),
						esc_url( Post_Parts::share_url( $network, $post ) ),
						/* translators: %s: network name. */
						esc_attr( sprintf( __( 'Share on %s', 'studiare-extensions' ), $name ) ),
						self::glyph( $network ),
						esc_html( $name )
					);
				}

				if ( 'yes' === $s['copy'] ) {
					$items .= sprintf(
						'<li hidden data-stx-needs-js><button type="button" class="stx-share__btn stx-share__btn--copy" data-stx-copy="%1$s">%2$s<span class="stx-share__name">%3$s</span></button></li>',
						esc_url( (string) get_permalink( $post ) ),
						self::icon( 'copy' ),
						esc_html__( 'Copy link', 'studiare-extensions' )
					);
				}

				if ( 'yes' === $s['native'] ) {
					$items .= sprintf(
						'<li hidden data-stx-needs-share><button type="button" class="stx-share__btn stx-share__btn--native" data-stx-share data-url="%1$s" data-title="%2$s">%3$s<span class="stx-share__name">%4$s</span></button></li>',
						esc_url( (string) get_permalink( $post ) ),
						esc_attr( $title ),
						self::icon( 'share' ),
						esc_html__( 'More…', 'studiare-extensions' )
					);
				}

				if ( '' === $items ) {
					return;
				}

				printf(
					'<div class="stx-share stx-share--%1$s stx-share--%2$s%3$s">%4$s<ul class="stx-share__list">%5$s</ul></div>',
					esc_attr( $s['look'] ),
					esc_attr( $s['colors'] ),
					'yes' === $s['stack'] ? ' stx-share--stack' : '',
					'' !== (string) $s['label'] ? '<span class="stx-share__label">' . esc_html( $s['label'] ) . '</span>' : '',
					$items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				);
			}
		);
	}

	/**
	 * Brand glyph of a network (the same glyphs as the support button), or
	 * the chosen icon pack's envelope for email.
	 *
	 * @param string $network Network key.
	 */
	private static function glyph( string $network ): string {
		switch ( $network ) {
			case 'telegram':
				return Icon_Library::svg( 'phosphor', 'telegram', true, 'stx-ico' );
			case 'whatsapp':
			case 'x':
			case 'linkedin':
				return Icon_Library::svg( 'bootstrap', $network, false, 'stx-ico' );
		}

		return self::icon( 'mail' );
	}
}
