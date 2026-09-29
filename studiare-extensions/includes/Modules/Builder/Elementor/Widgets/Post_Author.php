<?php
/**
 * Author box: photo, name, the biography from the user profile and a link
 * to the author's other posts.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;

defined( 'ABSPATH' ) || exit;

final class Post_Author extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-author';
	}

	public function get_title(): string {
		return __( 'Author box', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Author', 'studiare-extensions' ) );
		$this->add_post_note();

		$this->add_control(
			'label',
			array(
				'label'   => __( 'Small title', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Written by', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'bio',
			array(
				'label'       => __( 'Biography', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'From "Biographical Info" in the author\'s profile.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'link_text',
			array(
				'label'       => __( 'Link to their posts', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'All posts', 'studiare-extensions' ),
				'description' => __( 'Leave empty to hide.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Look', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'row',
				'options' => array(
					'row'    => __( 'Photo beside the text', 'studiare-extensions' ),
					'center' => __( 'Centred', 'studiare-extensions' ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Author box', 'studiare-extensions' ) );
		$this->add_box_style( 'box', '.stx-author' );
		$this->add_text_style( 'name', '.stx-author__name', array( 'label' => __( 'Name', 'studiare-extensions' ) ) );
		$this->add_text_style( 'bio', '.stx-author__bio', array( 'label' => __( 'Biography', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_post(
			static function ( \WP_Post $post ) use ( $s ) {
				$author_id = (int) $post->post_author;
				$url       = get_author_posts_url( $author_id );
				$bio       = 'yes' === $s['bio'] ? (string) get_the_author_meta( 'description', $author_id ) : '';

				echo '<div class="stx-author stx-author--' . esc_attr( $s['layout'] ) . '">';
				echo Post_Parts::avatar( $author_id, 160, 'stx-author__avatar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core avatar markup.
				echo '<div class="stx-author__body">';
				if ( '' !== (string) $s['label'] ) {
					echo '<span class="stx-author__label">' . esc_html( $s['label'] ) . '</span>';
				}
				printf( '<a class="stx-author__name" href="%1$s">%2$s</a>', esc_url( $url ), esc_html( get_the_author_meta( 'display_name', $author_id ) ) );
				if ( '' !== $bio ) {
					echo '<p class="stx-author__bio">' . esc_html( $bio ) . '</p>';
				}
				if ( '' !== (string) $s['link_text'] ) {
					printf(
						'<a class="stx-author__more" href="%1$s">%2$s <span class="stx-author__count">(%3$s)</span></a>',
						esc_url( $url ),
						esc_html( $s['link_text'] ),
						esc_html( self::num( (int) count_user_posts( $author_id, 'post', true ) ) )
					);
				}
				echo '</div></div>';
			}
		);
	}
}
