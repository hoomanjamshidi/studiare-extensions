<?php
/**
 * Table of contents: the post's H2 (and optionally H3) headings as links.
 *
 * A native <details>, so it opens and closes without JavaScript; blog.js
 * only marks the section being read. Designs usually place one open copy in
 * a sticky sidebar for computers and a closed copy above the text for
 * phones, using Elementor's device visibility, so nothing moves after load.
 *
 * "Follow the reader" adds a slim bar (hidden until blog.js shows it) that
 * slides in under the header once the list has scrolled away: it names the
 * section being read, shows the reading progress and opens the list. It is
 * fixed, so it never moves the article; the list itself stays where it is
 * (a sticky <details> would push the text down whenever it opens).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;

defined( 'ABSPATH' ) || exit;

final class Post_Toc extends Blog_Base {

	public function get_name(): string {
		return 'stx-post-toc';
	}

	public function get_title(): string {
		return __( 'Table of contents', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-table-of-contents';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Table of contents', 'studiare-extensions' ) );
		$this->add_post_note();

		$this->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'In this article', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'depth',
			array(
				'label'   => __( 'Headings', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '3',
				'options' => array(
					'2' => __( 'Main headings (H2)', 'studiare-extensions' ),
					'3' => __( 'Main headings and sub-headings (H2, H3)', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'open',
			array(
				'label'       => __( 'Open at first', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Visitors can open and close it. On phones a closed list takes little space above the text.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'follow',
			array(
				'label'       => __( 'Follow the reader', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'mobile',
				'options'     => array(
					''       => __( 'Off', 'studiare-extensions' ),
					'mobile' => __( 'Phones and tablets', 'studiare-extensions' ),
					'all'    => __( 'All devices', 'studiare-extensions' ),
				),
				'description' => __( 'Once the list scrolls away, a slim bar with the section being read stays under the header; tapping it opens the list. On computers the designs keep the list in a sticky sidebar instead.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'numbers',
			array(
				'label'   => __( 'Numbers', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'minimum',
			array(
				'label'       => __( 'Hide with fewer headings than', 'studiare-extensions' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 2,
				'min'         => 1,
				'max'         => 10,
				'description' => __( 'A short post without sections needs no table of contents.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Table of contents', 'studiare-extensions' ) );
		$this->add_box_style( 'box', '.stx-toc' );
		$this->add_text_style( 'title', '.stx-toc__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style(
			'link',
			'.stx-toc__link',
			array(
				'label' => __( 'Links', 'studiare-extensions' ),
				'hover' => '.stx-toc__link:hover',
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$this->with_post(
			function ( \WP_Post $post ) use ( $s ) {
				$depth    = (int) $s['depth'];
				$headings = array_values(
					array_filter(
						Post_Parts::content( $post )['headings'],
						static function ( $heading ) use ( $depth ) {
							return $heading['level'] <= $depth;
						}
					)
				);

				if ( count( $headings ) < max( 1, (int) $s['minimum'] ) ) {
					$this->editor_hint( __( 'The table of contents appears when the post has enough H2 headings.', 'studiare-extensions' ) );
					return;
				}

				$items = '';
				foreach ( $headings as $heading ) {
					$items .= sprintf(
						'<li class="stx-toc__item stx-toc__item--h%1$d"><a class="stx-toc__link" href="#%2$s">%3$s</a></li>',
						$heading['level'],
						esc_attr( $heading['id'] ),
						esc_html( $heading['text'] )
					);
				}

				$numbers = 'yes' === $s['numbers'] ? ' stx-toc--numbers' : '';

				printf(
					'<details class="stx-toc%1$s" data-stx-toc%2$s><summary class="stx-toc__head"><span class="stx-toc__title">%3$s%4$s</span>%5$s</summary><ol class="stx-toc__list">%6$s</ol></details>',
					esc_attr( $numbers ),
					'yes' === $s['open'] ? ' open' : '',
					self::icon( 'list', 'stx-toc__icon' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
					esc_html( $s['title'] ),
					'<span class="stx-toc__sign" aria-hidden="true"></span>',
					$items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				);

				if ( in_array( $s['follow'], array( 'mobile', 'all' ), true ) ) {
					echo $this->follow_bar( $s['follow'], $numbers, (string) $s['title'], $items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
				}
			}
		);
	}

	/**
	 * The bar that follows the reader (see the class comment). Hidden until
	 * blog.js moves it to <body> and shows it.
	 *
	 * @param string $devices `mobile` (phones and tablets) or `all`.
	 * @param string $numbers Numbering class, or ''.
	 * @param string $title   Title of the table of contents.
	 * @param string $items   Escaped list items.
	 */
	private function follow_bar( string $devices, string $numbers, string $title, string $items ): string {
		$panel = 'stx-tocbar-' . $this->get_id();

		return sprintf(
			'<div class="stx-tocbar stx-tocbar--%1$s%2$s" data-stx-tocbar hidden><button type="button" class="stx-tocbar__toggle" aria-expanded="false" aria-controls="%3$s">%4$s<span class="stx-tocbar__text"><span class="stx-tocbar__label">%5$s</span><span class="stx-tocbar__current" data-stx-tocbar-current></span></span><span class="stx-tocbar__chev" aria-hidden="true"></span></button><div class="stx-tocbar__panel" id="%3$s" hidden><ol class="stx-toc__list">%6$s</ol></div><span class="stx-tocbar__progress" aria-hidden="true"><i data-stx-tocbar-progress></i></span></div>',
			esc_attr( $devices ),
			esc_attr( $numbers ),
			esc_attr( $panel ),
			self::icon( 'list', 'stx-toc__icon' ),
			esc_html( '' !== $title ? $title : __( 'In this article', 'studiare-extensions' ) ),
			$items
		);
	}
}
