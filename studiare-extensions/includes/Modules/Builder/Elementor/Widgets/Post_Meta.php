<?php
/**
 * Post details: author, date, last update, reading time, comments and
 * category, as one row of icons or as an author line with a photo.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Cards;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;

defined( 'ABSPATH' ) || exit;

final class Post_Meta extends Blog_Base {

	/** A post counts as updated when it changed at least this long after it was published. */
	private const UPDATE_GRACE = DAY_IN_SECONDS;

	public function get_name(): string {
		return 'stx-post-meta';
	}

	public function get_title(): string {
		return __( 'Post details', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-post-info';
	}

	/** @return array<string, string> Detail key → label, in display order. */
	private static function details(): array {
		return array(
			'author'   => __( 'Author', 'studiare-extensions' ),
			'date'     => __( 'Date', 'studiare-extensions' ),
			'modified' => __( 'Last update', 'studiare-extensions' ),
			'reading'  => __( 'Reading time', 'studiare-extensions' ),
			'comments' => __( 'Comments', 'studiare-extensions' ),
			'category' => __( 'Category', 'studiare-extensions' ),
		);
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Details', 'studiare-extensions' ) );
		$this->add_post_note();

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Look', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'inline',
				'options' => array(
					'inline' => __( 'One row with icons', 'studiare-extensions' ),
					'author' => __( 'Author photo and name, details below', 'studiare-extensions' ),
				),
			)
		);

		$defaults = array(
			'author'   => 'yes',
			'date'     => 'yes',
			'modified' => '',
			'reading'  => 'yes',
			'comments' => 'yes',
			'category' => '',
		);
		foreach ( self::details() as $key => $label ) {
			$this->add_control(
				'show_' . $key,
				array(
					'label'   => $label,
					'type'    => Controls_Manager::SWITCHER,
					'default' => $defaults[ $key ],
				)
			);
		}

		$this->add_control(
			'author_label',
			array(
				'label'     => __( 'Text before the author', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'layout' => 'author' ),
			)
		);

		$this->add_align_control( 'align', '.stx-pmeta' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Details', 'studiare-extensions' ) );
		$this->add_text_style( 'text', '.stx-pmeta' );
		$this->add_text_style(
			'name',
			'.stx-pmeta__name',
			array(
				'label' => __( 'Author name', 'studiare-extensions' ),
				'hover' => '.stx-pmeta__name:hover',
			)
		);
		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-pmeta .stx-ico' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_post(
			function ( \WP_Post $post ) use ( $s ) {
				$items = array();
				foreach ( array_keys( self::details() ) as $key ) {
					if ( 'author' !== $key && 'yes' === $s[ 'show_' . $key ] ) {
						$items[ $key ] = $this->item( $key, $post );
					}
				}
				$items = array_filter( $items );

				$author_id = (int) $post->post_author;
				$name      = sprintf(
					'<a class="stx-pmeta__name" href="%1$s">%2$s</a>',
					esc_url( get_author_posts_url( $author_id ) ),
					esc_html( get_the_author_meta( 'display_name', $author_id ) )
				);

				if ( 'author' === $s['layout'] ) {
					$this->render_author_line( $s, $author_id, $name, $items );
					return;
				}

				$html = '';
				if ( 'yes' === $s['show_author'] ) {
					$html .= '<span class="stx-pmeta__item stx-pmeta__author">' . Post_Parts::avatar( $author_id, 56, 'stx-pmeta__avatar' ) . $name . '</span>';
				}
				foreach ( $items as $item ) {
					$html .= '<span class="stx-pmeta__item">' . $item . '</span>';
				}

				echo '<div class="stx-pmeta stx-pmeta--inline">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			}
		);
	}

	/**
	 * Photo, name and a second line with the other details.
	 *
	 * @param array    $s         Settings.
	 * @param int      $author_id Author ID.
	 * @param string   $name      Linked name markup.
	 * @param string[] $items     Other details markup.
	 */
	private function render_author_line( array $s, int $author_id, string $name, array $items ): void {
		$show_author = 'yes' === $s['show_author'];
		$label       = '' !== (string) $s['author_label'] ? '<span class="stx-pmeta__label">' . esc_html( $s['author_label'] ) . '</span>' : '';

		echo '<div class="stx-pmeta stx-pmeta--author">';
		if ( $show_author ) {
			echo Post_Parts::avatar( $author_id, 96, 'stx-pmeta__avatar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core avatar markup.
		}
		echo '<div class="stx-pmeta__body">';
		if ( $show_author ) {
			echo '<div class="stx-pmeta__who">' . $label . $name . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
		if ( $items ) {
			echo '<div class="stx-pmeta__line">' . implode( '<span class="stx-pmeta__dot" aria-hidden="true">•</span>', $items ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in item().
		}
		echo '</div></div>';
	}

	/**
	 * One detail with its icon ('' when it does not apply).
	 *
	 * @param string   $key  Detail key.
	 * @param \WP_Post $post Post.
	 */
	private function item( string $key, \WP_Post $post ): string {
		switch ( $key ) {
			case 'date':
				return self::icon( 'calendar' ) . Cards::post_time( $post );

			case 'modified':
				if ( strtotime( $post->post_modified_gmt ) - strtotime( $post->post_date_gmt ) < self::UPDATE_GRACE ) {
					return '';
				}
				/* translators: %s: date of the last update. */
				return self::icon( 'clock' ) . sprintf( esc_html__( 'Updated %s', 'studiare-extensions' ), Cards::post_time( $post, 'modified' ) );

			case 'reading':
				return self::icon( 'book-open' ) . esc_html( Post_Parts::reading_label( $post ) );

			case 'comments':
				if ( ! comments_open( $post ) && ! get_comments_number( $post ) ) {
					return '';
				}
				return sprintf( '<a href="%1$s#comments">%2$s%3$s</a>', esc_url( (string) get_permalink( $post ) ), self::icon( 'chat' ), esc_html( Post_Parts::comments_label( $post ) ) );

			case 'category':
				$terms = Post_Parts::terms( $post, 'category' );
				if ( ! $terms ) {
					return '';
				}
				return sprintf( '<a href="%1$s">%2$s%3$s</a>', esc_url( (string) get_term_link( $terms[0] ) ), self::icon( 'category' ), esc_html( html_entity_decode( $terms[0]->name, ENT_QUOTES, 'UTF-8' ) ) );
		}

		return '';
	}
}
