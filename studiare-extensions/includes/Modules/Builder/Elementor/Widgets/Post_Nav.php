<?php
/**
 * Previous and next post, with their pictures and titles.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Post_Nav extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-nav';
	}

	public function get_title(): string {
		return __( 'Previous / next post', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-post-navigation';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Previous / next post', 'studiare-extensions' ) );
		$this->add_post_note();

		$this->add_control(
			'prev_label',
			array(
				'label'   => __( 'Previous label', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Previous post', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'next_label',
			array(
				'label'   => __( 'Next label', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Next post', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'same_term',
			array(
				'label'   => __( 'Same category only', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'thumbs',
			array(
				'label'   => __( 'Pictures', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Previous / next post', 'studiare-extensions' ) );
		$this->add_box_style( 'box', '.stx-pnav__link' );
		$this->add_text_style( 'title', '.stx-pnav__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_post(
			// get_adjacent_post() reads the global post, which with_post() sets.
			function () use ( $s ) {
				$same  = 'yes' === $s['same_term'];
				$links = array(
					'prev' => get_adjacent_post( $same, '', true ),
					'next' => get_adjacent_post( $same, '', false ),
				);

				if ( ! array_filter( $links ) ) {
					$this->editor_hint( __( 'This post has no previous or next post yet.', 'studiare-extensions' ) );
					return;
				}

				echo '<nav class="stx-pnav" aria-label="' . esc_attr__( 'More posts', 'studiare-extensions' ) . '">';
				foreach ( $links as $dir => $adjacent ) {
					if ( ! $adjacent instanceof \WP_Post ) {
						// Keeps "next" at the end side when there is no previous post.
						echo '<span class="stx-pnav__gap" aria-hidden="true"></span>';
						continue;
					}

					$thumb = 'yes' === $s['thumbs'] && has_post_thumbnail( $adjacent )
						? get_the_post_thumbnail(
							$adjacent,
							'thumbnail',
							array(
								'class'   => 'stx-pnav__img',
								'alt'     => '',
								'loading' => 'lazy',
							)
						)
						: '';

					printf(
						'<a class="stx-pnav__link stx-pnav__link--%1$s" href="%2$s" rel="%1$s">%3$s<span class="stx-pnav__text"><span class="stx-pnav__dir">%4$s%5$s</span><span class="stx-pnav__title">%6$s</span></span></a>',
						esc_attr( $dir ),
						esc_url( (string) get_permalink( $adjacent ) ),
						$thumb, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
						'<span class="stx-pnav__arrow" aria-hidden="true">' . self::CHEVRON . '</span>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG.
						esc_html( 'prev' === $dir ? $s['prev_label'] : $s['next_label'] ),
						esc_html( get_the_title( $adjacent ) )
					);
				}
				echo '</nav>';
			}
		);
	}
}
