<?php
/**
 * Podcast list: blog posts that have a podcast (Article tools), in one of
 * four looks. Three play the episodes in place (podcasts.js), the fourth is
 * a list of links beside a picture:
 *
 * - playlist: a player for the chosen episode beside or above the episodes;
 * - cards:    a grid of episode covers with a play button;
 * - rows:     compact rows with a play button and a progress line;
 * - list:     numbered rows that open the post's player, beside a picture.
 *
 * Every published post with a podcast is listed, or only those in chosen
 * categories, or those in the category being viewed. The podcast is read
 * from the post meta, so the list works even while the Article tools module
 * is off. Titles are real links to the posts, so without JavaScript every
 * episode is still reachable (the play buttons are hidden then).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Core\Color;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Modules\Article_Tools\Podcast;
use StudiareExt\Modules\Builder\Assets;
use StudiareExt\Modules\Builder\Elementor\Integration;
use StudiareExt\Modules\Builder\Schema;

defined( 'ABSPATH' ) || exit;

final class Podcast_List extends Base {

	/** Most episodes a list may ask for. */
	private const MAX_ITEMS = 20;

	/** Two-tone arrow of the simple list. It points left; CSS mirrors it on LTR sites. */
	private const ARROW = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 4l-8 8" opacity=".45"/><path d="M7 12l8 8"/></svg>';

	public function get_name(): string {
		return 'stx-podcast-list';
	}

	public function get_title(): string {
		return __( 'Podcast list', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-headphones';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'podcast', 'audio', 'player', 'پادکست', 'صوت' ) );
	}

	/** Only its own inline styles and deferred script: no builder.css/js. */
	public function get_style_depends(): array {
		return array( Assets::PODCASTS_HANDLE );
	}

	public function get_script_depends(): array {
		return array( Assets::PODCASTS_HANDLE );
	}

	/** @return array<string, string> Look → label. */
	private static function looks(): array {
		return array(
			'playlist' => __( 'Player with episode list', 'studiare-extensions' ),
			'cards'    => __( 'Episode cards', 'studiare-extensions' ),
			'rows'     => __( 'Playable rows', 'studiare-extensions' ),
			'list'     => __( 'Simple list with picture', 'studiare-extensions' ),
		);
	}

	protected function register_controls(): void {
		$this->register_list_controls();
		$this->register_layout_controls();
		$this->register_picture_controls();
		$this->register_style_controls();
	}

	private function register_list_controls(): void {
		$this->start_content_section( 'section_list', __( 'Podcasts', 'studiare-extensions' ) );

		$this->add_control(
			'look',
			array(
				'label'       => __( 'Look', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'playlist',
				'options'     => self::looks(),
				'description' => __( 'The first three play the episodes right here; the simple list opens each post.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Lists blog posts that have a podcast (set in the "Podcast" box of the post editor).', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Posts', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => array(
					'all'      => __( 'All categories', 'studiare-extensions' ),
					'category' => __( 'From categories', 'studiare-extensions' ),
					'current'  => __( 'Category being viewed', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'categories',
			array(
				'label'       => __( 'Categories', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => Post_Grid::category_options(),
				'description' => __( 'Subcategories are included.', 'studiare-extensions' ),
				'condition'   => array( 'source' => 'category' ),
			)
		);

		$this->add_control(
			'current_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'On a category page, the podcasts of that category; on other pages, all podcasts.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array( 'source' => 'current' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => __( 'Number of podcasts', 'studiare-extensions' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => self::MAX_ITEMS,
				'default' => 4,
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => __( 'Newest first', 'studiare-extensions' ),
					'ASC'  => __( 'Oldest first', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'   => __( 'Text under the title', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'excerpt',
				'options' => array(
					'excerpt' => __( 'Post excerpt', 'studiare-extensions' ),
					'speaker' => __( 'Speaker', 'studiare-extensions' ),
					''        => __( 'None', 'studiare-extensions' ),
				),
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
			'duration',
			array(
				'label'   => __( 'Duration', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => __( 'Title tag', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'span' => __( 'Plain text', 'studiare-extensions' ),
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_layout_controls(): void {
		$this->start_content_section(
			'section_layout',
			__( 'Layout', 'studiare-extensions' ),
			array( 'condition' => array( 'look!' => 'list' ) )
		);

		$this->add_control(
			'covers',
			array(
				'label'     => __( 'Cover pictures', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'look' => array( 'playlist', 'rows' ) ),
			)
		);

		$this->add_control(
			'deck_side',
			array(
				'label'     => __( 'Player position', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'side',
				'options'   => array(
					'side' => __( 'Beside the list', 'studiare-extensions' ),
					'top'  => __( 'Above the list', 'studiare-extensions' ),
				),
				'condition' => array( 'look' => 'playlist' ),
			)
		);

		$this->add_columns_control( '.stx-pods__grid', array( 4, 2, 1 ), 6, array( 'condition' => array( 'look' => 'cards' ) ) );

		$this->add_control(
			'swipe',
			array(
				'label'       => __( 'Swipe on phones', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Cards sit in one row that scrolls sideways on narrow screens.', 'studiare-extensions' ),
				'condition'   => array( 'look' => 'cards' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_picture_controls(): void {
		$this->start_content_section(
			'section_picture',
			__( 'Picture', 'studiare-extensions' ),
			array( 'condition' => array( 'look' => 'list' ) )
		);

		$this->add_control(
			'image',
			array(
				'label'       => __( 'Picture', 'studiare-extensions' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array(
					'id'  => 0,
					'url' => '',
				),
				'description' => __( 'Optional. Leave empty for the list alone.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'image_shape',
			array(
				'label'   => __( 'Shape', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'blob',
				'options' => array(
					'blob'    => __( 'Free shape with decorations', 'studiare-extensions' ),
					'circle'  => __( 'Circle', 'studiare-extensions' ),
					'rounded' => __( 'Rounded', 'studiare-extensions' ),
					'plain'   => __( 'As uploaded', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'image_side',
			array(
				'label'   => __( 'Position', 'studiare-extensions' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'end',
				'toggle'  => false,
				'options' => array(
					'start' => array(
						'title' => __( 'Start', 'studiare-extensions' ),
						'icon'  => 'eicon-h-align-' . self::start_icon(),
					),
					'end'   => array(
						'title' => __( 'End', 'studiare-extensions' ),
						'icon'  => 'eicon-h-align-' . self::end_icon(),
					),
				),
			)
		);

		$this->add_control(
			'image_phone',
			array(
				'label'       => __( 'Show on phones', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'On narrow screens the picture sits above the list.', 'studiare-extensions' ),
			)
		);

		$this->add_responsive_control(
			'image_width',
			array(
				'label'      => __( 'Picture width', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min' => 20,
						'max' => 50,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-pods' => '--stx-pods-media: {{SIZE}}%;' ),
			)
		);

		$this->add_control(
			'dividers',
			array(
				'label' => __( 'Lines between rows', 'studiare-extensions' ),
				'type'  => Controls_Manager::SWITCHER,
			)
		);

		$this->end_controls_section();
	}

	private function register_style_controls(): void {
		$this->start_style_section( 'section_style_colors', __( 'Colours', 'studiare-extensions' ) );

		$this->add_control(
			'accent',
			array(
				'label'     => __( 'Play buttons and progress', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-pods' => '--stx-pods-accent: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'accent_ink',
			array(
				'label'       => __( 'Play icon', 'studiare-extensions' ),
				'type'        => Controls_Manager::COLOR,
				'description' => __( 'Empty: white or black, whichever reads better on the site\'s accent colour.', 'studiare-extensions' ),
				'selectors'   => array( '{{WRAPPER}} .stx-pods' => '--stx-pods-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'deck_bg',
			array(
				'label'     => __( 'Player background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-pdeck' => '--stx-pdeck-bg: {{VALUE}};' ),
				'condition' => array( 'look' => 'playlist' ),
			)
		);

		$this->add_control(
			'deck_text',
			array(
				'label'     => __( 'Player text', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-pdeck' => '--stx-pdeck-text: {{VALUE}};' ),
				'condition' => array( 'look' => 'playlist' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style_items', __( 'Episodes', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-pods__eps, .stx-pods__grid, .stx-pods__list' );
		$this->add_box_style( 'item', '.stx-ep, .stx-pods__link', array( 'shadow' => true ) );
		$this->add_text_style( 'title', '.stx-ep__title, .stx-pods__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'sub', '.stx-ep__sub, .stx-pods__sub', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );
		$this->add_text_style( 'num', '.stx-ep__num, .stx-ep__label, .stx-pods__num', array( 'label' => __( 'Number', 'studiare-extensions' ) ) );
		$this->end_controls_section();

		$this->start_style_section(
			'section_style_arrow',
			__( 'Arrow', 'studiare-extensions' ),
			array( 'condition' => array( 'look' => 'list' ) )
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => __( 'Colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-pods__go' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'      => __( 'Size', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 14,
						'max' => 56,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-pods__go' => '--stx-pods-arrow: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$rows = $this->query( $s );

		if ( ! $rows ) {
			$this->editor_hint( __( 'No post has a podcast yet. Add one in the "Podcast" box of the post editor.', 'studiare-extensions' ) );
			return;
		}

		$look = array_key_exists( (string) $s['look'], self::looks() ) ? (string) $s['look'] : 'playlist';
		$tag  = in_array( $s['title_tag'], array( 'h2', 'h3', 'h4', 'span' ), true ) ? (string) $s['title_tag'] : 'h3';

		$episodes = array();
		foreach ( $rows as $index => $row ) {
			$episodes[] = $this->episode( $row['post'], $row['pod'], $index + 1, $s );
		}

		if ( 'list' === $look ) {
			$this->render_list( $episodes, $tag, $s );
			return;
		}

		$classes = array( 'stx-pods', 'stx-pods--' . $look );
		if ( 'playlist' === $look && 'side' === $s['deck_side'] ) {
			$classes[] = 'stx-pods--side';
		}
		if ( 'cards' === $look && 'yes' === $s['swipe'] ) {
			$classes[] = 'stx-pods--swipe';
		}

		// The play glyph's colour: white or black by contrast with the accent.
		printf( '<div class="%1$s" style="--stx-pods-ink-auto:%2$s" data-stx-pods><div class="stx-pods__inner">', esc_attr( implode( ' ', $classes ) ), esc_attr( self::accent_ink() ) );

		if ( 'playlist' === $look ) {
			$this->render_deck( $episodes[0] );
		}

		if ( 'cards' === $look ) {
			echo '<ul class="stx-pods__grid">';
			foreach ( $episodes as $episode ) {
				$this->render_card( $episode, $tag, $s );
			}
			echo '</ul>';
		} else {
			$covers = 'yes' === $s['covers'];
			echo '<ol class="stx-pods__eps">';
			foreach ( $episodes as $episode ) {
				$this->render_episode_row( $episode, $tag, $covers, $s );
			}
			echo '</ol>';
		}

		echo '</div></div>';
	}

	/**
	 * What every look shows of one episode.
	 *
	 * @param \WP_Post $post   Post with a podcast.
	 * @param array    $pod    Podcast::view().
	 * @param int      $number Position in the list.
	 * @param array    $s      Widget settings.
	 * @return array{pod:array, number:int, label:string, sub:string, url:string, time:string}
	 */
	private function episode( \WP_Post $post, array $pod, int $number, array $s ): array {
		/* translators: %s: number of the episode in the list. */
		$label = 'yes' === $s['numbers'] ? sprintf( __( 'Episode %s', 'studiare-extensions' ), self::num( $number ) ) : (string) $pod['speaker'];

		return array(
			'pod'    => $pod,
			'number' => $number,
			'label'  => $label,
			'sub'    => $this->subtitle( $post, $pod, (string) $s['subtitle'] ),
			// The post player carries id="stx-podcast", so the link lands on it.
			'url'    => get_permalink( $post ) . '#stx-podcast',
			'time'   => $pod['duration'] ? self::digits( Podcast::clock( (int) $pod['duration'] ) ) : '',
		);
	}

	/* ---------------------------------------------------------------------
	 * Playable looks
	 * ------------------------------------------------------------------- */

	/**
	 * The player of the playlist look. It shows the first episode; podcasts.js
	 * puts the chosen one in its place.
	 *
	 * @param array $episode episode().
	 */
	private function render_deck( array $episode ): void {
		$pod = $episode['pod'];
		?>
		<div class="stx-pdeck" data-pod-deck><div class="stx-pdeck__in">
			<span class="stx-pdeck__art" data-deck-art><?php echo $this->cover( (int) $pod['cover_id'], 'medium', '(max-width: 640px) 120px, 240px', 'stx-pdeck__img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by cover(). ?></span>
			<div class="stx-pdeck__main">
				<span class="stx-pdeck__label" data-deck-label><?php echo esc_html( $episode['label'] ); ?></span>
				<a class="stx-pdeck__title" href="<?php echo esc_url( $episode['url'] ); ?>" data-deck-title><?php echo esc_html( $pod['title'] ); ?></a>
				<span class="stx-pdeck__sub" data-deck-sub<?php echo '' === $episode['sub'] ? ' hidden' : ''; ?>><?php echo esc_html( $episode['sub'] ); ?></span>

				<div class="stx-pdeck__bar" dir="ltr">
					<input type="range" class="stx-pdeck__seek" min="0" max="<?php echo esc_attr( (string) (int) $pod['duration'] ); ?>" step="1" value="0" aria-label="<?php esc_attr_e( 'Playback position', 'studiare-extensions' ); ?>" data-deck-seek>
					<span class="stx-pdeck__times">
						<span data-deck-current><?php echo esc_html( self::digits( '0:00' ) ); ?></span>
						<span data-deck-total><?php echo esc_html( '' !== $episode['time'] ? $episode['time'] : '--:--' ); ?></span>
					</span>
				</div>

				<div class="stx-pdeck__controls" dir="ltr">
					<button type="button" class="stx-pdeck__tool" data-deck-step="-1" aria-label="<?php esc_attr_e( 'Previous episode', 'studiare-extensions' ); ?>"><?php echo Podcast::glyph( 'previous' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?></button>
					<?php /* translators: %s: number of seconds. */ ?>
					<button type="button" class="stx-pdeck__tool stx-pdeck__tool--skip" data-deck-skip="<?php echo esc_attr( (string) -Podcast::SKIP_BACK ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Back %s seconds', 'studiare-extensions' ), self::num( Podcast::SKIP_BACK ) ) ); ?>"><?php echo Podcast::glyph( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?><span><?php echo esc_html( self::num( Podcast::SKIP_BACK ) ); ?></span></button>
					<?php echo $this->play_button( $pod['title'], 'stx-pdeck__play', 'data-deck-play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by play_button(). ?>
					<?php /* translators: %s: number of seconds. */ ?>
					<button type="button" class="stx-pdeck__tool stx-pdeck__tool--skip" data-deck-skip="<?php echo esc_attr( (string) Podcast::SKIP_FORWARD ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Forward %s seconds', 'studiare-extensions' ), self::num( Podcast::SKIP_FORWARD ) ) ); ?>"><?php echo Podcast::glyph( 'forward' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?><span><?php echo esc_html( self::num( Podcast::SKIP_FORWARD ) ); ?></span></button>
					<button type="button" class="stx-pdeck__tool" data-deck-step="1" aria-label="<?php esc_attr_e( 'Next episode', 'studiare-extensions' ); ?>"><?php echo Podcast::glyph( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?></button>
					<?php /* translators: %s: playback speed, e.g. 1.5×. */ ?>
					<button type="button" class="stx-pdeck__tool stx-pdeck__speed" data-deck-speed aria-label="<?php echo esc_attr( sprintf( __( 'Playback speed: %s', 'studiare-extensions' ), self::num( 1 ) . '×' ) ); ?>"><?php echo esc_html( self::num( 1 ) . '×' ); ?></button>
				</div>

				<span class="stx-pdeck__error" role="status" data-deck-error hidden><?php esc_html_e( 'The audio could not be played. Check your connection, or download the file.', 'studiare-extensions' ); ?></span>
			</div>
		</div></div>
		<?php
	}

	/**
	 * One row of the playlist and rows looks.
	 *
	 * @param array  $episode episode().
	 * @param string $tag     Title tag.
	 * @param bool   $covers  Whether the cover picture shows behind the play button.
	 * @param array  $s       Widget settings.
	 */
	private function render_episode_row( array $episode, string $tag, bool $covers, array $s ): void {
		echo '<li class="stx-ep stx-ep--row" ' . $this->episode_attributes( $episode ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by episode_attributes().

		if ( 'yes' === $s['numbers'] ) {
			// The list is an <ol>: screen readers already say the number.
			echo '<span class="stx-ep__num" aria-hidden="true">' . esc_html( self::num( $episode['number'] ) ) . '</span>';
		}

		echo '<span class="stx-ep__thumb' . ( $covers ? ' has-cover' : '' ) . '">';
		if ( $covers ) {
			echo $this->cover( (int) $episode['pod']['cover_id'], 'thumbnail', '64px', 'stx-ep__img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by cover().
		}
		echo $this->play_button( $episode['pod']['title'], 'stx-ep__play', 'data-ep-play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by play_button().
		echo '</span>';

		echo '<span class="stx-ep__body">';
		$this->render_title( $episode, $tag );
		if ( '' !== $episode['sub'] ) {
			echo '<span class="stx-ep__sub">' . esc_html( $episode['sub'] ) . '</span>';
		}
		echo '</span>';

		if ( 'yes' === $s['duration'] && '' !== $episode['time'] ) {
			echo '<span class="stx-ep__time"><bdi dir="ltr">' . esc_html( $episode['time'] ) . '</bdi></span>';
		}

		echo '<span class="stx-ep__bar" aria-hidden="true"><i></i></span>';
		echo '</li>';
	}

	/**
	 * One card of the cards look: the title link covers the card, the play
	 * button sits above it.
	 *
	 * @param array  $episode episode().
	 * @param string $tag     Title tag.
	 * @param array  $s       Widget settings.
	 */
	private function render_card( array $episode, string $tag, array $s ): void {
		echo '<li class="stx-ep stx-ep--card" ' . $this->episode_attributes( $episode ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by episode_attributes().

		echo '<span class="stx-ep__media">';
		echo $this->cover( (int) $episode['pod']['cover_id'], 'medium', '(max-width: 640px) 78vw, 320px', 'stx-ep__img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by cover().
		echo $this->play_button( $episode['pod']['title'], 'stx-ep__play', 'data-ep-play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by play_button().
		if ( 'yes' === $s['duration'] && '' !== $episode['time'] ) {
			echo '<span class="stx-ep__time"><bdi dir="ltr">' . esc_html( $episode['time'] ) . '</bdi></span>';
		}
		echo '<span class="stx-ep__bar" aria-hidden="true"><i></i></span>';
		echo '</span>';

		echo '<span class="stx-ep__body">';
		if ( '' !== $episode['label'] ) {
			echo '<span class="stx-ep__label">' . esc_html( $episode['label'] ) . '</span>';
		}
		$this->render_title( $episode, $tag );
		if ( '' !== $episode['sub'] ) {
			echo '<span class="stx-ep__sub">' . esc_html( $episode['sub'] ) . '</span>';
		}
		echo '</span>';

		echo '</li>';
	}

	/**
	 * Episode title, a link to the post's player.
	 *
	 * @param array  $episode episode().
	 * @param string $tag     Title tag.
	 */
	private function render_title( array $episode, string $tag ): void {
		printf(
			'<%1$s class="stx-ep__title"><a href="%2$s">%3$s</a></%1$s>',
			esc_html( $tag ),
			esc_url( $episode['url'] ),
			esc_html( $episode['pod']['title'] )
		);
	}

	/**
	 * Data podcasts.js plays an episode from. Nothing is downloaded before a
	 * play button is pressed.
	 *
	 * @param array $episode episode().
	 */
	private function episode_attributes( array $episode ): string {
		$pod   = $episode['pod'];
		$attrs = array(
			'data-id'       => (string) $pod['id'],
			'data-audio'    => esc_url( $pod['src'] ),
			'data-duration' => (string) (int) $pod['duration'],
			'data-title'    => $pod['title'],
			'data-artist'   => '' !== $pod['speaker'] ? $pod['speaker'] : get_bloginfo( 'name' ),
			'data-artwork'  => esc_url( $pod['artwork'] ),
			'data-label'    => $episode['label'],
			'data-sub'      => $episode['sub'],
			'data-url'      => esc_url( $episode['url'] ),
			'data-time'     => $episode['time'],
		);

		$html = 'data-stx-ep';
		foreach ( $attrs as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}

		return $html;
	}

	/**
	 * Play/pause button; podcasts.js switches its glyph and label.
	 *
	 * @param string $title      Episode title, for the label.
	 * @param string $class_name Button class.
	 * @param string $attribute  Data attribute podcasts.js finds it by.
	 */
	private function play_button( string $title, string $class_name, string $attribute ): string {
		return sprintf(
			'<button type="button" class="stx-pods__play %1$s" %2$s aria-label="%3$s">%4$s<span class="stx-pods__spinner" aria-hidden="true"></span></button>',
			esc_attr( $class_name ),
			esc_attr( $attribute ),
			/* translators: %s: episode title. */
			esc_attr( sprintf( __( 'Play: %s', 'studiare-extensions' ), $title ) ),
			Podcast::glyph( 'play', 'stx-pods__glyph-play' ) . Podcast::glyph( 'pause', 'stx-pods__glyph-pause' )
		);
	}

	/**
	 * Cover picture, or a headphones mark when the episode has none.
	 *
	 * @param int    $id         Attachment ID.
	 * @param string $size       Image size.
	 * @param string $sizes      `sizes` attribute.
	 * @param string $class_name Class of the picture.
	 */
	private function cover( int $id, string $size, string $sizes, string $class_name ): string {
		$img = $id ? wp_get_attachment_image(
			$id,
			$size,
			false,
			array(
				'class'   => $class_name,
				'alt'     => '',
				'loading' => 'lazy',
				'sizes'   => $sizes,
			)
		) : '';

		return '' !== $img ? $img : '<span class="' . esc_attr( $class_name ) . ' stx-pods__noart">' . Icon_Library::svg( 'phosphor', 'headphones', true ) . '</span>';
	}

	/** White or black, whichever reads better on the site's accent colour. */
	private static function accent_ink(): string {
		$module = Integration::module();
		$accent = $module ? (string) $module->settings()['brand']['accent'] : '';

		return Color::ink( '' !== $accent ? $accent : Schema::BRAND_DEFAULTS['accent'] );
	}

	/* ---------------------------------------------------------------------
	 * Simple list (links to the posts)
	 * ------------------------------------------------------------------- */

	/**
	 * Numbered rows that open each post, beside an optional picture.
	 *
	 * @param array  $episodes episode() of each row.
	 * @param string $tag      Title tag.
	 * @param array  $s        Widget settings.
	 */
	private function render_list( array $episodes, string $tag, array $s ): void {
		$image_id = isset( $s['image']['id'] ) ? (int) $s['image']['id'] : 0;
		$classes  = array( 'stx-pods', 'stx-pods--list' );
		if ( $image_id ) {
			$classes[] = 'stx-pods--media';
			$classes[] = 'stx-pods--media-' . ( 'start' === $s['image_side'] ? 'start' : 'end' );
			$classes[] = 'stx-pods--' . sanitize_html_class( (string) $s['image_shape'] );
			if ( 'yes' !== $s['image_phone'] ) {
				$classes[] = 'stx-pods--media-no-phone';
			}
		}
		if ( 'yes' === $s['dividers'] ) {
			$classes[] = 'stx-pods--dividers';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"><div class="stx-pods__inner">';
		echo '<ol class="stx-pods__list">';
		foreach ( $episodes as $episode ) {
			$this->render_link_row( $episode, $tag, $s );
		}
		echo '</ol>';

		if ( $image_id ) {
			echo '<div class="stx-pods__media">' . wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
				$image_id,
				'large',
				false,
				array(
					'class'   => 'stx-pods__img',
					'loading' => 'lazy',
					'sizes'   => '(max-width: 767px) 80vw, 420px',
				)
			) . '</div>';
		}

		echo '</div></div>';
	}

	/**
	 * One row of the simple list: number, title, text and duration.
	 *
	 * @param array  $episode episode().
	 * @param string $tag     Title tag.
	 * @param array  $s       Widget settings.
	 */
	private function render_link_row( array $episode, string $tag, array $s ): void {
		echo '<li class="stx-pods__item">';
		echo '<a class="stx-pods__link" href="' . esc_url( $episode['url'] ) . '">';
		echo '<span class="stx-pods__body">';
		printf( '<%s class="stx-pods__title">', esc_html( $tag ) );
		if ( 'yes' === $s['numbers'] ) {
			// The list is an <ol>: screen readers already say the number.
			echo '<span class="stx-pods__num" aria-hidden="true">' . esc_html( self::num( $episode['number'] ) ) . ':</span> ';
		}
		echo esc_html( $episode['pod']['title'] ) . sprintf( '</%s>', esc_html( $tag ) );
		if ( '' !== $episode['sub'] ) {
			echo '<span class="stx-pods__sub">' . esc_html( $episode['sub'] ) . '</span>';
		}
		echo '</span>';

		if ( 'yes' === $s['duration'] && '' !== $episode['time'] ) {
			echo '<span class="stx-pods__time"><bdi dir="ltr">' . esc_html( $episode['time'] ) . '</bdi></span>';
		}

		echo '<span class="stx-pods__go" aria-hidden="true">' . self::ARROW . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG.
		echo '</a></li>';
	}

	/* ---------------------------------------------------------------------
	 * Data
	 * ------------------------------------------------------------------- */

	/**
	 * Text under a title: the post's own excerpt (else the start of its text)
	 * or the podcast's speaker.
	 *
	 * @param \WP_Post $post   Post.
	 * @param array    $pod    Podcast::view().
	 * @param string   $source `excerpt`, `speaker` or ''.
	 */
	private function subtitle( \WP_Post $post, array $pod, string $source ): string {
		if ( 'speaker' === $source ) {
			return (string) $pod['speaker'];
		}
		if ( 'excerpt' !== $source ) {
			return '';
		}

		// Not get_the_excerpt(): without a written excerpt it runs the content
		// filters (Elementor renders the whole post) once per row.
		$text = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content );

		return wp_trim_words( wp_strip_all_tags( $text ), 14 );
	}

	/**
	 * Published posts with a podcast, newest (or oldest) first. A post whose
	 * audio file was deleted is skipped, so the query asks for a few more.
	 *
	 * @param array $s Widget settings.
	 * @return array<int, array{post:\WP_Post, pod:array}>
	 */
	private function query( array $s ): array {
		$count = max( 1, min( self::MAX_ITEMS, (int) $s['count'] ) );
		$args  = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'has_password'        => false,
			'posts_per_page'      => $count + 5,
			'order'               => 'ASC' === $s['order'] ? 'ASC' : 'DESC',
			'orderby'             => 'date',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one indexed key, a few rows.
				array(
					'key'     => Podcast::META,
					'compare' => 'EXISTS',
				),
			),
		);

		$cats = $this->category_ids( $s );
		if ( $cats ) {
			// category__in leaves out subcategories; a tax query includes them.
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the point of the filter.
				array(
					'taxonomy'         => 'category',
					'terms'            => $cats,
					'include_children' => true,
				),
			);
		}

		$posts = get_posts( $args );
		self::prime_attachments( $posts );

		$rows = array();
		foreach ( $posts as $post ) {
			$pod = Podcast::view( $post );
			if ( $pod ) {
				$rows[] = array(
					'post' => $post,
					'pod'  => $pod,
				);
			}
		}

		return array_slice( $rows, 0, $count );
	}

	/**
	 * Loads the covers, featured pictures and audio files of all the posts in
	 * one query, instead of one or two per episode.
	 *
	 * @param \WP_Post[] $posts Posts with a podcast (their meta is already cached).
	 */
	private static function prime_attachments( array $posts ): void {
		$ids = array();
		foreach ( $posts as $post ) {
			$data  = Podcast::stored( (int) $post->ID );
			$ids[] = (int) $data['audio_id'];
			$ids[] = (int) $data['cover_id'];
			$ids[] = (int) get_post_thumbnail_id( $post );
		}

		$ids = array_values( array_filter( array_unique( $ids ) ) );
		if ( $ids ) {
			_prime_post_caches( $ids, false, true );
		}
	}

	/**
	 * Categories to filter by, none for all.
	 *
	 * @param array $s Widget settings.
	 * @return int[]
	 */
	private function category_ids( array $s ): array {
		if ( 'category' === $s['source'] ) {
			return array_values( array_filter( array_map( 'intval', (array) $s['categories'] ) ) );
		}

		if ( 'current' === $s['source'] && is_category() ) {
			return array( (int) get_queried_object_id() );
		}

		return array();
	}
}
