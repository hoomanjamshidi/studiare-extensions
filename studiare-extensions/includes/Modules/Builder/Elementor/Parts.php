<?php
/**
 * Markup and data for product/course parts, shared by the single widgets
 * and the Tabs widget (which can show any of them in a tab).
 *
 * Course data comes from Studiare's own meta: `lessons_group` (sections →
 * lesson/quiz posts with `lesson_data`), `_studiare_course_teachers` and
 * the `_studiare_course_*` info fields. Inside the editor, parts fall back
 * to realistic demo content so an empty sample never hides the design.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor;

use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Widgets\Base;
use StudiareExt\Modules\Builder\Product_Meta_Box;

defined( 'ABSPATH' ) || exit;

final class Parts {

	/** @var bool Guards against the_content recursion. */
	private static $in_content = false;

	/* ---------------------------------------------------------------------
	 * Formatting
	 * ------------------------------------------------------------------- */

	/**
	 * Price block: old price, discount badge and current price.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $o       `layout` (stack|inline), `badge` (bool), `badge_format`, `free_label`.
	 */
	public static function price( \WC_Product $product, array $o = array() ): string {
		$o = array_merge(
			array(
				'layout'       => 'stack',
				'badge'        => true,
				/* translators: {percent} is replaced with the discount percentage. */
				'badge_format' => __( '{percent}% off', 'studiare-extensions' ),
				'free_label'   => __( 'Free', 'studiare-extensions' ),
			),
			$o
		);

		$classes = 'stx-price stx-price--' . sanitize_html_class( $o['layout'] );

		if ( $product->is_type( array( 'variable', 'grouped' ) ) ) {
			return sprintf( '<div class="%s stx-price--range"><div class="stx-price__now">%s</div></div>', esc_attr( $classes ), Base::digits_html( $product->get_price_html() ) );
		}

		if ( '' === $product->get_price() ) {
			return '';
		}

		$current = (float) wc_get_price_to_display( $product );

		if ( $current <= 0 ) {
			return sprintf( '<div class="%1$s"><div class="stx-price__now stx-price__free">%2$s</div></div>', esc_attr( $classes ), esc_html( $o['free_label'] ) );
		}

		$top = '';
		if ( $product->is_on_sale() && '' !== $product->get_regular_price() ) {
			$regular = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
			$top    .= '<del class="stx-price__old">' . Base::digits_html( wc_price( $regular ) ) . '</del>';

			if ( $o['badge'] && $regular > 0 ) {
				$percent = (int) round( ( ( $regular - $current ) / $regular ) * 100 );
				if ( $percent > 0 ) {
					$top .= '<span class="stx-price__badge">' . esc_html( Base::digits( str_replace( '{percent}', (string) $percent, $o['badge_format'] ) ) ) . '</span>';
				}
			}
		}

		return sprintf(
			'<div class="%1$s">%2$s<div class="stx-price__now">%3$s</div></div>',
			esc_attr( $classes ),
			'' !== $top ? '<div class="stx-price__top">' . $top . '</div>' : '',
			Base::digits_html( wc_price( $current ) )
		);
	}

	/**
	 * Star rating with average and count.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $o       `format` (with {average} and {count}), `link` (bool), `hide_empty` (bool).
	 */
	public static function rating( \WC_Product $product, array $o = array() ): string {
		$o = array_merge(
			array(
				/* translators: keep {average} and {count}: they are replaced with the rating and the number of reviews. */
				'format'     => __( '{average} from {count} reviews', 'studiare-extensions' ),
				'link'       => true,
				'hide_empty' => true,
			),
			$o
		);

		$count   = (int) $product->get_review_count();
		$average = (float) $product->get_average_rating();

		if ( ! $count && $o['hide_empty'] ) {
			return '';
		}

		$text = strtr(
			esc_html( $o['format'] ),
			array(
				'{average}' => '<b>' . esc_html( Base::num( $average, 1 ) ) . '</b>',
				'{count}'   => esc_html( Base::num( $count ) ),
			)
		);
		$html = sprintf(
			'<span class="stx-stars" style="--stx-rating:%1$s" role="img" aria-label="%2$s"><span class="stx-stars__fill"></span></span><span class="stx-rating__text">%3$s</span>',
			esc_attr( (string) round( $average / 5 * 100, 1 ) . '%' ),
			/* translators: %s: average rating out of 5. */
			esc_attr( sprintf( __( 'Rated %s out of 5', 'studiare-extensions' ), number_format_i18n( $average, 1 ) ) ),
			$text
		);

		if ( $o['link'] ) {
			return '<a class="stx-rating" href="#reviews">' . $html . '</a>';
		}

		return '<span class="stx-rating">' . $html . '</span>';
	}

	/* ---------------------------------------------------------------------
	 * Description
	 * ------------------------------------------------------------------- */

	/**
	 * Product description (`the_content`), optionally collapsed.
	 *
	 * @param \WC_Product $product Product (already the global post).
	 * @param array       $o       `collapse` (bool), `height` (int px).
	 */
	public static function description( \WC_Product $product, array $o = array() ): string {
		if ( self::$in_content ) {
			return '';
		}

		self::$in_content = true;
		$content          = apply_filters( 'the_content', get_post_field( 'post_content', $product->get_id() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
		self::$in_content = false;

		if ( '' === trim( wp_strip_all_tags( (string) $content ) ) && false === strpos( (string) $content, '<img' ) ) {
			return Context::is_editor() ? self::demo_description() : '';
		}

		if ( empty( $o['collapse'] ) ) {
			return '<div class="stx-desc">' . $content . '</div>';
		}

		return sprintf(
			'<div class="stx-desc is-collapsible" style="--stx-desc-h:%1$dpx" data-stx-collapse><div class="stx-desc__body">%2$s</div><button type="button" class="stx-desc__more" aria-expanded="false">%3$s</button></div>',
			max( 120, (int) ( $o['height'] ?? 420 ) ),
			$content,
			esc_html__( 'Show more', 'studiare-extensions' )
		);
	}

	private static function demo_description(): string {
		return '<div class="stx-desc"><p>' . esc_html__( 'This is where the product description appears. Write it in the product editor — text, images and videos all show up here with the template\'s typography.', 'studiare-extensions' ) . '</p></div>';
	}

	/* ---------------------------------------------------------------------
	 * Highlights ("what you will learn")
	 * ------------------------------------------------------------------- */

	/**
	 * @param \WC_Product $product Product.
	 * @return string[]
	 */
	public static function highlight_items( \WC_Product $product ): array {
		$raw   = (string) get_post_meta( $product->get_id(), Product_Meta_Box::META_HIGHLIGHTS, true );
		$items = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) ) );

		if ( ! $items && Context::is_editor() ) {
			$items = array(
				__( 'Hands-on techniques you can use the same week', 'studiare-extensions' ),
				__( 'Real examples from real classrooms', 'studiare-extensions' ),
				__( 'Short exercises after every lesson', 'studiare-extensions' ),
				__( 'Downloadable notes and checklists', 'studiare-extensions' ),
			);
		}

		return $items;
	}

	/**
	 * @param \WC_Product $product Product.
	 * @param array       $o       `title`.
	 */
	public static function highlights( \WC_Product $product, array $o = array() ): string {
		$items = self::highlight_items( $product );
		if ( ! $items ) {
			return '';
		}

		$html = '';
		foreach ( $items as $item ) {
			$html .= '<li class="stx-hl__item">' . Base::icon( 'check', 'stx-hl__icon' ) . '<span>' . esc_html( $item ) . '</span></li>';
		}

		$title = '' !== ( $o['title'] ?? '' ) ? '<h3 class="stx-hl__title">' . esc_html( $o['title'] ) . '</h3>' : '';

		return '<div class="stx-hl">' . $title . '<ul class="stx-hl__list">' . $html . '</ul></div>';
	}

	/* ---------------------------------------------------------------------
	 * Curriculum
	 * ------------------------------------------------------------------- */

	/**
	 * Sections and lessons from Studiare's `lessons_group` meta.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array{title:string, label:string, open:bool, items:array}>
	 */
	public static function sections( int $product_id ): array {
		$groups = get_post_meta( $product_id, 'lessons_group', true );
		if ( ! is_array( $groups ) ) {
			return array();
		}

		$sections = array();
		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$items = array();
			foreach ( (array) ( $group['lessons_list'] ?? array() ) as $lesson_id ) {
				if ( ! is_numeric( $lesson_id ) || ! get_post( (int) $lesson_id ) ) {
					continue;
				}
				$items[] = self::lesson( (int) $lesson_id );
			}

			$label = trim( (string) ( $group['section_num_title'] ?? '' ) . ' ' . (string) ( $group['section_number'] ?? '' ) );

			$sections[] = array(
				'title' => (string) ( $group['title'] ?? '' ),
				'label' => $label,
				'open'  => 'on' === ( $group['list_open_situ'] ?? '' ),
				'items' => $items,
			);
		}

		return $sections;
	}

	/**
	 * @param int $lesson_id Lesson or quiz post.
	 */
	private static function lesson( int $lesson_id ): array {
		if ( 'sc-quiz' === get_post_type( $lesson_id ) ) {
			return array(
				'kind'     => 'quiz',
				'title'    => get_the_title( $lesson_id ),
				'subtitle' => '',
				'badge'    => __( 'Quiz', 'studiare-extensions' ),
				'preview'  => '',
				'video'    => '',
				'locked'   => false,
			);
		}

		$data    = get_post_meta( $lesson_id, 'lesson_data', true );
		$data    = ( is_array( $data ) && isset( $data[0] ) && is_array( $data[0] ) ) ? $data[0] : array();
		$private = (string) ( $data['private_lesson'] ?? '' );
		$locked  = '' !== $private && 'off' !== $private;
		$title   = trim( (string) ( $data['lesson_title'] ?? '' ) );

		$badges = array(
			'free'   => __( 'Free', 'studiare-extensions' ),
			'new'    => __( 'New', 'studiare-extensions' ),
			'update' => __( 'Updated', 'studiare-extensions' ),
			'hot'    => __( 'Popular', 'studiare-extensions' ),
		);
		$badge  = (string) ( $data['badge'] ?? '' );

		return array(
			'kind'     => 'lesson',
			'title'    => '' !== $title ? $title : get_the_title( $lesson_id ),
			'subtitle' => (string) ( $data['lesson_subtitle'] ?? '' ),
			'badge'    => $badges[ $badge ] ?? '',
			'preview'  => esc_url_raw( (string) ( $data['preview_video'] ?? '' ) ),
			// Public lessons can be watched by anyone; private URLs never reach the page.
			'video'    => $locked ? '' : esc_url_raw( (string) ( $data['lesson_video'] ?? '' ) ),
			'locked'   => $locked,
		);
	}

	private static function demo_sections(): array {
		$lesson = static function ( string $title, string $time, bool $free = false ): array {
			return array(
				'kind'     => 'lesson',
				'title'    => $title,
				'subtitle' => $time,
				'badge'    => '',
				'preview'  => $free ? '#' : '',
				'video'    => '',
				'locked'   => ! $free,
			);
		};

		return array(
			array(
				'title' => __( 'Getting started', 'studiare-extensions' ),
				'label' => '',
				'open'  => true,
				'items' => array( $lesson( __( 'Why the first session matters', 'studiare-extensions' ), '12:40', true ), $lesson( __( 'Setting the rules together', 'studiare-extensions' ), '24:10' ), $lesson( __( 'Arranging the room', 'studiare-extensions' ), '18:30' ) ),
			),
			array(
				'title' => __( 'Core techniques', 'studiare-extensions' ),
				'label' => '',
				'open'  => false,
				'items' => array( $lesson( __( 'Finding the cause', 'studiare-extensions' ), '22:00', true ), $lesson( __( 'One-to-one conversations', 'studiare-extensions' ), '26:20' ) ),
			),
			array(
				'title' => __( 'Putting it into practice', 'studiare-extensions' ),
				'label' => '',
				'open'  => false,
				'items' => array( $lesson( __( 'Short group activities', 'studiare-extensions' ), '21:15' ), $lesson( __( 'Giving useful feedback', 'studiare-extensions' ), '23:40' ) ),
			),
		);
	}

	/**
	 * Whether the current visitor has access to the course (bought it or
	 * has a Studiare subscription).
	 *
	 * @param int $product_id Product ID.
	 */
	public static function user_bought( int $product_id ): bool {
		return Theme_Bridge::user_has_course( get_current_user_id(), $product_id );
	}

	/**
	 * Course curriculum.
	 *
	 * Modes: `theme` (Studiare's own lesson list with player, downloads and
	 * quizzes, the same for visitors and students; the default, so a course
	 * looks the same before and after buying), `styled` (our accordion;
	 * public lessons and previews play in a lightbox) and `auto` (styled for
	 * visitors, theme list for students). Without Studiare, `styled` is used.
	 * A course with nothing to list gets no curriculum at all, so an empty
	 * tab is left out.
	 *
	 * @param \WC_Product $product Product (already the global post).
	 * @param array       $o       `mode`, `open_first`, `summary`, `title`.
	 */
	public static function curriculum( \WC_Product $product, array $o = array() ): string {
		$o = array_merge(
			array(
				'mode'       => 'theme',
				'open_first' => true,
				'summary'    => true,
				'title'      => '',
			),
			$o
		);

		$id       = $product->get_id();
		$sections = self::sections( $id );

		if ( ! self::lists_anything( $sections ) ) {
			if ( ! Context::is_editor() ) {
				return '';
			}
			$sections = self::demo_sections();
		}

		$has_theme = function_exists( 'sc_get_lessons_for_product' );
		$use_theme = $has_theme && ( 'theme' === $o['mode'] || ( 'auto' === $o['mode'] && self::user_bought( $id ) ) );

		if ( $use_theme && ! Context::is_editor() ) {
			return '<div class="stx-curr stx-curr--theme" id="stx-curriculum">' . sc_get_lessons_for_product( $id ) . '</div>';
		}

		$lesson_count = 0;
		foreach ( $sections as $section ) {
			$lesson_count += count( $section['items'] );
		}

		$html = '<div class="stx-curr" id="stx-curriculum">';

		if ( '' !== $o['title'] || $o['summary'] ) {
			$html .= '<div class="stx-curr__head">';
			if ( '' !== $o['title'] ) {
				$html .= '<h3 class="stx-curr__heading">' . esc_html( $o['title'] ) . '</h3>';
			}
			if ( $o['summary'] ) {
				$html .= sprintf(
					'<span class="stx-curr__summary">%1$s</span><button type="button" class="stx-curr__all" data-stx-expand-all aria-expanded="false" data-open="%2$s" data-close="%3$s">%2$s</button>',
					esc_html(
						Base::digits(
							sprintf(
								/* translators: 1: number of sections, 2: number of lessons. */
								__( '%1$s sections · %2$s lessons', 'studiare-extensions' ),
								Base::num( count( $sections ) ),
								Base::num( $lesson_count )
							)
						)
					),
					esc_attr__( 'Open all', 'studiare-extensions' ),
					esc_attr__( 'Close all', 'studiare-extensions' )
				);
			}
			$html .= '</div>';
		}

		foreach ( $sections as $index => $section ) {
			$open  = $section['open'] || ( $o['open_first'] && 0 === $index );
			$num   = '' !== $section['label'] ? $section['label'] : str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT );
			$count = count( $section['items'] );

			$html .= '<details class="stx-curr__section"' . ( $open ? ' open' : '' ) . '>';
			$html .= sprintf(
				'<summary class="stx-curr__toggle"><span class="stx-curr__num">%1$s</span><span class="stx-curr__title">%2$s</span><span class="stx-curr__meta">%3$s</span><span class="stx-curr__sign" aria-hidden="true"></span></summary>',
				esc_html( Base::digits( $num ) ),
				esc_html( $section['title'] ),
				/* translators: %s: number of lessons. */
				esc_html( Base::digits( sprintf( _n( '%s lesson', '%s lessons', $count, 'studiare-extensions' ), Base::num( $count ) ) ) )
			);
			$html .= '<ul class="stx-curr__lessons">';

			foreach ( $section['items'] as $item ) {
				$html .= self::lesson_row( $item );
			}

			$html .= '</ul></details>';
		}

		return $html . '</div>';
	}

	/**
	 * Whether the sections show anything: a section title or a lesson.
	 *
	 * Studiare prints a section bar only when it has a title, so for a course
	 * whose sections are empty rows (no title, no lessons) its list is just
	 * the "Course content" heading and "1 section" with nothing under them.
	 *
	 * @param array $sections From sections().
	 */
	private static function lists_anything( array $sections ): bool {
		foreach ( $sections as $section ) {
			if ( '' !== trim( $section['title'] ) || $section['items'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $item Lesson data.
	 */
	private static function lesson_row( array $item ): string {
		if ( 'quiz' === $item['kind'] ) {
			$icon = 'help';
		} elseif ( $item['locked'] ) {
			$icon = 'lock';
		} else {
			$icon = 'play';
		}

		$action = '';
		if ( '' !== $item['preview'] ) {
			$action = sprintf( '<a class="stx-curr__preview" href="%1$s" data-stx-video="%1$s">%2$s</a>', esc_url( $item['preview'] ), esc_html__( 'Free preview', 'studiare-extensions' ) );
		} elseif ( '' !== $item['video'] ) {
			$action = sprintf( '<a class="stx-curr__preview" href="%1$s" data-stx-video="%1$s">%2$s</a>', esc_url( $item['video'] ), esc_html__( 'Watch', 'studiare-extensions' ) );
		}

		return sprintf(
			'<li class="stx-curr__lesson%1$s">%2$s<span class="stx-curr__lesson-title">%3$s%4$s</span>%5$s%6$s</li>',
			$item['locked'] ? ' is-locked' : '',
			Base::icon( $icon, 'stx-curr__lesson-icon' ),
			esc_html( $item['title'] ),
			'' !== $item['badge'] ? ' <span class="stx-curr__badge">' . esc_html( $item['badge'] ) . '</span>' : '',
			$action,
			'' !== $item['subtitle'] ? '<span class="stx-curr__time">' . esc_html( Base::digits( $item['subtitle'] ) ) . '</span>' : ''
		);
	}

	/* ---------------------------------------------------------------------
	 * Teachers
	 * ------------------------------------------------------------------- */

	/**
	 * Teacher post IDs of a course (new field, then Studiare's legacy fields).
	 *
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	public static function teacher_ids( int $product_id ): array {
		$ids = get_post_meta( $product_id, '_studiare_course_teachers', true );

		if ( ! is_array( $ids ) || ! $ids ) {
			$ids = array();
			foreach ( array( '', '_2', '_3', '_4' ) as $suffix ) {
				$ids[] = get_post_meta( $product_id, '_studiare_course_teacher' . $suffix, true );
			}
		}

		$ids = array_filter(
			array_map( 'intval', $ids ),
			static function ( $id ) {
				return $id > 0 && 'publish' === get_post_status( $id );
			}
		);

		return array_values( array_unique( $ids ) );
	}

	/**
	 * @param \WC_Product $product Product.
	 * @param array       $o       `layout` (card|compact), `bio` (bool), `link` (bool), `label`.
	 */
	public static function teachers( \WC_Product $product, array $o = array() ): string {
		$o = array_merge(
			array(
				'layout' => 'card',
				'bio'    => true,
				'link'   => true,
				'label'  => '',
			),
			$o
		);

		$people = array();
		foreach ( self::teacher_ids( $product->get_id() ) as $id ) {
			$bio      = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $id ) ) ), 38 );
			$people[] = array(
				'name'  => get_the_title( $id ),
				'role'  => (string) get_post_meta( $id, '_studiare_teacher_job_title', true ),
				'photo' => (string) get_the_post_thumbnail_url( $id, 'medium' ),
				'bio'   => $bio,
				'url'   => (string) get_permalink( $id ),
			);
		}

		if ( ! $people && Context::is_editor() ) {
			$people[] = array(
				'name'  => __( 'Teacher name', 'studiare-extensions' ),
				'role'  => __( 'Job title', 'studiare-extensions' ),
				'photo' => '',
				'bio'   => __( 'Teachers are picked in the course\'s Studiare settings. Their photo, title and bio appear here.', 'studiare-extensions' ),
				'url'   => '',
			);
		}

		if ( ! $people ) {
			return '';
		}

		$html = '<div class="stx-teachers stx-teachers--' . esc_attr( $o['layout'] ) . '">';
		foreach ( $people as $person ) {
			$photo = '' !== $person['photo']
				? '<img class="stx-teacher__photo" src="' . esc_url( $person['photo'] ) . '" alt="' . esc_attr( $person['name'] ) . '" loading="lazy" decoding="async">'
				: '<span class="stx-teacher__photo stx-teacher__photo--empty">' . Base::icon( 'user' ) . '</span>';

			$name = esc_html( $person['name'] );
			if ( $o['link'] && '' !== $person['url'] ) {
				$name = '<a href="' . esc_url( $person['url'] ) . '">' . $name . '</a>';
			}

			$html .= '<div class="stx-teacher">' . $photo . '<div class="stx-teacher__body">';
			if ( '' !== $o['label'] ) {
				$html .= '<span class="stx-teacher__label">' . esc_html( $o['label'] ) . '</span>';
			}
			$html .= '<span class="stx-teacher__name">' . $name . '</span>';
			if ( '' !== $person['role'] ) {
				$html .= '<span class="stx-teacher__role">' . esc_html( $person['role'] ) . '</span>';
			}
			if ( $o['bio'] && 'card' === $o['layout'] && '' !== $person['bio'] ) {
				$html .= '<p class="stx-teacher__bio">' . esc_html( $person['bio'] ) . '</p>';
			}
			$html .= '</div></div>';
		}

		return $html . '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Info items (course facts / product meta)
	 * ------------------------------------------------------------------- */

	/** @return array<string, array{label:string, icon:string}> Source → defaults. */
	public static function info_sources(): array {
		return array(
			'duration'    => array(
				'label' => __( 'Duration', 'studiare-extensions' ),
				'icon'  => 'clock',
			),
			'lessons'     => array(
				'label' => __( 'Lessons', 'studiare-extensions' ),
				'icon'  => 'book-open',
			),
			'sessions'    => array(
				'label' => __( 'Sessions', 'studiare-extensions' ),
				'icon'  => 'video',
			),
			'level'       => array(
				'label' => __( 'Level', 'studiare-extensions' ),
				'icon'  => 'chart',
			),
			'language'    => array(
				'label' => __( 'Language', 'studiare-extensions' ),
				'icon'  => 'chat',
			),
			'certificate' => array(
				'label' => __( 'Certificate', 'studiare-extensions' ),
				'icon'  => 'award',
			),
			'students'    => array(
				'label' => __( 'Students', 'studiare-extensions' ),
				'icon'  => 'users',
			),
			'rating'      => array(
				'label' => __( 'Rating', 'studiare-extensions' ),
				'icon'  => 'star',
			),
			'reviews'     => array(
				'label' => __( 'Reviews', 'studiare-extensions' ),
				'icon'  => 'chat',
			),
			'teacher'     => array(
				'label' => __( 'Teacher', 'studiare-extensions' ),
				'icon'  => 'user',
			),
			'updated'     => array(
				'label' => __( 'Last update', 'studiare-extensions' ),
				'icon'  => 'calendar',
			),
			'file_type'   => array(
				'label' => __( 'File type', 'studiare-extensions' ),
				'icon'  => 'notes',
			),
			'file_size'   => array(
				'label' => __( 'File size', 'studiare-extensions' ),
				'icon'  => 'download',
			),
			'categories'  => array(
				'label' => __( 'Category', 'studiare-extensions' ),
				'icon'  => 'category',
			),
			'sku'         => array(
				'label' => __( 'SKU', 'studiare-extensions' ),
				'icon'  => 'tag',
			),
			'stock'       => array(
				'label' => __( 'Availability', 'studiare-extensions' ),
				'icon'  => 'store',
			),
			'weight'      => array(
				'label' => __( 'Weight', 'studiare-extensions' ),
				'icon'  => 'bag',
			),
			'dimensions'  => array(
				'label' => __( 'Dimensions', 'studiare-extensions' ),
				'icon'  => 'grid',
			),
			'meta'        => array(
				'label' => __( 'Custom field', 'studiare-extensions' ),
				'icon'  => 'info',
			),
			'text'        => array(
				'label' => __( 'Fixed text', 'studiare-extensions' ),
				'icon'  => 'info',
			),
		);
	}

	/** Studiare's own label overrides for the course fields. */
	private const THEME_HINTS = array(
		'duration'    => '_studiare_course_duration_hint',
		'lessons'     => '_studiare_course_lesseons_hint',
		'sessions'    => '_studiare_course_sessions_hint',
		'level'       => '_studiare_course_level_hint',
		'language'    => '_studiare_course_language_hint',
		'certificate' => '_studiare_course_certificate_hint',
		'students'    => '_studiare_course_buyers_text_hint',
		'file_type'   => '_studiare_sc_file_type_hint',
		'file_size'   => '_studiare_sc_file_size_hint',
	);

	/**
	 * Value of an info source for a product ('' when unknown).
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $source  Source key.
	 * @param string      $extra   Meta key (`meta`) or fixed text (`text`).
	 */
	public static function info_value( \WC_Product $product, string $source, string $extra = '' ): string {
		$id   = $product->get_id();
		$meta = static function ( string $key ) use ( $id ): string {
			$value = get_post_meta( $id, $key, true );
			return is_scalar( $value ) ? trim( (string) $value ) : '';
		};

		switch ( $source ) {
			case 'duration':
				return $meta( '_studiare_course_duration' );
			case 'lessons':
				$value = $meta( '_studiare_course_lesseons' );
				if ( '' === $value ) {
					$count = 0;
					foreach ( self::sections( $id ) as $section ) {
						$count += count( $section['items'] );
					}
					$value = $count ? Base::num( $count ) : '';
				}
				return $value;
			case 'sessions':
				return $meta( '_studiare_course_sessions' );
			case 'level':
				return $meta( '_studiare_course_level' );
			case 'language':
				return $meta( '_studiare_course_language' );
			case 'certificate':
				return $meta( '_studiare_course_certificate' );
			case 'students':
				$sales = (int) $product->get_total_sales();
				return $sales ? Base::num( $sales ) : '';
			case 'rating':
				return $product->get_review_count() ? Base::num( (float) $product->get_average_rating(), 1 ) : '';
			case 'reviews':
				return $product->get_review_count() ? Base::num( (int) $product->get_review_count() ) : '';
			case 'teacher':
				return implode( '، ', array_map( 'get_the_title', self::teacher_ids( $id ) ) );
			case 'updated':
				$value = $meta( '_studiare_woo_course_date_update' );
				if ( '' === $value && $product->get_date_modified() ) {
					$value = date_i18n( get_option( 'date_format' ), $product->get_date_modified()->getTimestamp() );
				}
				return $value;
			case 'file_type':
				return $meta( '_studiare_sc_file_type' );
			case 'file_size':
				return $meta( '_studiare_sc_file_size' );
			case 'categories':
				return wp_strip_all_tags( (string) wc_get_product_category_list( $id, '، ' ) );
			case 'sku':
				return (string) $product->get_sku();
			case 'stock':
				$availability = $product->get_availability();
				return (string) ( $availability['availability'] ?? '' );
			case 'weight':
				return $product->has_weight() ? wc_format_weight( $product->get_weight() ) : '';
			case 'dimensions':
				return $product->has_dimensions() ? wc_format_dimensions( $product->get_dimensions( false ) ) : '';
			case 'meta':
				return '' !== $extra && '_' !== $extra[0] ? $meta( $extra ) : '';
			case 'text':
				return $extra;
		}

		return '';
	}

	/**
	 * Label for an info source: widget label → Studiare's hint → default.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $source  Source key.
	 * @param string      $label   Label typed in the widget.
	 */
	public static function info_label( \WC_Product $product, string $source, string $label ): string {
		if ( '' !== $label ) {
			return $label;
		}

		if ( isset( self::THEME_HINTS[ $source ] ) ) {
			$hint = get_post_meta( $product->get_id(), self::THEME_HINTS[ $source ], true );
			if ( is_string( $hint ) && '' !== trim( $hint ) ) {
				return trim( $hint );
			}
		}

		return self::info_sources()[ $source ]['label'] ?? '';
	}

	/* ---------------------------------------------------------------------
	 * Specifications
	 * ------------------------------------------------------------------- */

	/**
	 * Visible attributes, weight and dimensions as label/value rows.
	 *
	 * @param \WC_Product $product Product.
	 * @return array<int, array{label:string, value:string}>
	 */
	public static function spec_rows( \WC_Product $product ): array {
		$rows = array();

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible() ) {
				continue;
			}

			if ( $attribute->is_taxonomy() ) {
				$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
			} else {
				$values = $attribute->get_options();
			}

			$rows[] = array(
				'label' => wc_attribute_label( $attribute->get_name() ),
				'value' => implode( '، ', array_map( 'strval', (array) $values ) ),
			);
		}

		if ( $product->has_weight() ) {
			$rows[] = array(
				'label' => __( 'Weight', 'studiare-extensions' ),
				'value' => wc_format_weight( $product->get_weight() ),
			);
		}

		if ( $product->has_dimensions() ) {
			$rows[] = array(
				'label' => __( 'Dimensions', 'studiare-extensions' ),
				'value' => wc_format_dimensions( $product->get_dimensions( false ) ),
			);
		}

		return $rows;
	}

	/**
	 * @param \WC_Product $product Product.
	 * @param array       $extra   Extra rows (label/value).
	 */
	public static function specs( \WC_Product $product, array $extra = array() ): string {
		$rows = array_merge( self::spec_rows( $product ), $extra );

		if ( ! $rows && Context::is_editor() ) {
			$rows = array(
				array(
					'label' => __( 'Attribute', 'studiare-extensions' ),
					'value' => __( 'Value from the product\'s attributes', 'studiare-extensions' ),
				),
			);
		}

		if ( ! $rows ) {
			return '';
		}

		$html = '<dl class="stx-specs">';
		foreach ( $rows as $row ) {
			$html .= '<div class="stx-specs__row"><dt>' . esc_html( $row['label'] ) . '</dt><dd>' . esc_html( Base::digits( $row['value'] ) ) . '</dd></div>';
		}

		return $html . '</dl>';
	}

	/* ---------------------------------------------------------------------
	 * Reviews
	 * ------------------------------------------------------------------- */

	/**
	 * WooCommerce reviews and review form (Studiare's template when active).
	 *
	 * @param \WC_Product $product Product (already the global post).
	 */
	public static function reviews( \WC_Product $product ): string {
		if ( ! comments_open( $product->get_id() ) && ! $product->get_review_count() ) {
			return Context::is_editor() ? '<div class="stx-w-hint">' . esc_html__( 'Reviews are closed for the sample product.', 'studiare-extensions' ) . '</div>' : '';
		}

		global $withcomments;
		$previous     = $withcomments;
		$withcomments = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- lets comments_template() run inside Elementor.

		ob_start();
		comments_template();
		$html = (string) ob_get_clean();

		$withcomments = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return '<div class="stx-reviews woocommerce">' . $html . '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Product cards
	 * ------------------------------------------------------------------- */

	/**
	 * Small label above a card title: "Course" or the first category.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function kind_label( \WC_Product $product ): string {
		if ( Context::is_course( $product->get_id() ) ) {
			return __( 'Course', 'studiare-extensions' );
		}

		$terms = get_the_terms( $product->get_id(), 'product_cat' );

		return ( $terms && ! is_wp_error( $terms ) ) ? html_entity_decode( $terms[0]->name, ENT_QUOTES, 'UTF-8' ) : '';
	}

	/**
	 * @param \WC_Product $product Product.
	 * @param array       $o       `kind` (bool), `rating` (bool), `image_size`.
	 */
	public static function card( \WC_Product $product, array $o = array() ): string {
		$o    = array_merge(
			array(
				'kind'       => true,
				'rating'     => false,
				'image_size' => 'woocommerce_thumbnail',
			),
			$o
		);
		$link = $product->get_permalink();

		$image = $product->get_image(
			$o['image_size'],
			array(
				'class'   => 'stx-pcard__img',
				'loading' => 'lazy',
			)
		);
		$kind  = $o['kind'] ? self::kind_label( $product ) : '';
		$price = $product->get_price_html();

		return sprintf(
			'<article class="stx-pcard"><a class="stx-pcard__media" href="%1$s" tabindex="-1" aria-hidden="true">%2$s</a><div class="stx-pcard__body">%3$s<h3 class="stx-pcard__title"><a href="%1$s">%4$s</a></h3>%5$s%6$s</div></article>',
			esc_url( $link ),
			$image,
			'' !== $kind ? '<span class="stx-pcard__kind">' . esc_html( $kind ) . '</span>' : '',
			esc_html( $product->get_name() ),
			$o['rating'] ? self::rating( $product, array( 'link' => false ) ) : '',
			'' !== $price ? '<div class="stx-pcard__price">' . Base::digits_html( $price ) . '</div>' : ''
		);
	}
}
