<?php
/**
 * Home page designs (from the supplied "style 6" and "style 1" designs,
 * without their header and footer, which the site's own ones replace).
 *
 * Sections are full-width bands (`stx-band`) whose content lines up with the
 * site's container width. Products, categories, posts and teachers come from
 * the site; the rest is sample text to replace in Elementor.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

use StudiareExt\Core\Persian;
use StudiareExt\Core\Site;

defined( 'ABSPATH' ) || exit;

final class Home {

	/** Complete home page: every section of the full design. */
	public static function complete(): array {
		return array(
			Blocks::band(
				'page',
				array( self::hero_slider(), self::trust_strip() ),
				array(
					'gap'        => 22,
					'pad_mobile' => array( 16, 16, 18 ),
				)
			),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Browse by topic', 'studiare-extensions' ), __( 'All categories', 'studiare-extensions' ), Blocks::shop_url() ), self::categories( 'numbered' ) ) ),
			Blocks::band( 'plain', array( self::products( true ) ), Blocks::white_band() ),
			Blocks::band( 'page', array( self::weekly_offer() ) ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Learning paths', 'studiare-extensions' ), '', '', __( 'Each path is a suggested order of courses, books and workshops.', 'studiare-extensions' ) ), self::paths() ) ),
			Blocks::band( 'page', array( self::podcasts_title(), self::podcasts() ) ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Teachers and authors', 'studiare-extensions' ) ), self::people() ) ),
			Blocks::band( 'plain', array( Blocks::section_title( __( 'What learners say', 'studiare-extensions' ) ), self::testimonials() ), Blocks::white_band() ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Upcoming workshops', 'studiare-extensions' ), __( 'Full calendar', 'studiare-extensions' ), '#' ), self::events() ) ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'From the blog', 'studiare-extensions' ), __( 'See all', 'studiare-extensions' ), Blocks::blog_url() ), self::blog( 'card' ) ) ),
			Blocks::band( 'page', array( self::faq_and_cta() ) ),
			Blocks::band( 'page', array( self::newsletter() ), Blocks::last_band() ),
		);
	}

	/** Studiare-style home page: curved accent hero with an intro video. */
	public static function studiare(): array {
		return array(
			self::curved_hero(),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Browse by topic', 'studiare-extensions' ), __( 'All categories', 'studiare-extensions' ), Blocks::shop_url() ), self::categories( 'numbered' ) ), array( 'pad' => array( 48, 20, 24 ) ) ),
			Blocks::band( 'page', array( self::podcasts_title(), self::podcasts() ) ),
			Blocks::band( 'plain', array( self::products( true ) ), Blocks::white_band() ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Teachers and authors', 'studiare-extensions' ) ), self::people() ) ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'From the blog', 'studiare-extensions' ), __( 'See all', 'studiare-extensions' ), Blocks::blog_url() ), self::blog( 'card' ) ) ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Upcoming workshops', 'studiare-extensions' ) ), self::events() ) ),
			Blocks::band( 'page', array( self::closing_cta() ), Blocks::last_band() ),
		);
	}

	/** Light shop front: banner hero, category tiles, best sellers, bundle, perks, blog. */
	public static function shop(): array {
		$white = array(
			'pad'        => array( 26, 20 ),
			'pad_mobile' => array( 18, 16 ),
		);

		return array(
			self::shop_hero(),
			Blocks::band( 'plain', array( self::categories( 'icon' ) ), array( 'pad' => array( 36, 20, 12 ) ) ),
			Blocks::band( 'plain', array( Blocks::row_title( __( 'Best-selling products', 'studiare-extensions' ), __( 'Chosen by thousands of learners', 'studiare-extensions' ), __( 'All products', 'studiare-extensions' ), Blocks::shop_url() ), self::products( false ) ), $white ),
			Blocks::band( 'plain', array( self::bundle_offer() ), $white ),
			Blocks::band(
				'soft',
				array( self::perks() ),
				array(
					'pad' => array( 52, 20 ),
					'set' => array( 'css_classes' => 'stx-band stx-bar--line stx-bar--line-top' ),
				)
			),
			Blocks::band( 'plain', array( Blocks::row_title( __( 'News from the blog', 'studiare-extensions' ), '', __( 'See all', 'studiare-extensions' ), Blocks::blog_url() ), self::blog( 'simple' ) ), Blocks::last_band( 56 ) ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Heroes
	 * ------------------------------------------------------------------- */

	/** Slider with two promo cards beside it (a row under it on tablets). */
	private static function hero_slider(): array {
		$slide = static function ( string $badge, string $title, string $text, string $button, string $url, string $button2 = '', string $url2 = '#' ): array {
			return array(
				'_id'          => El::id(),
				'badge'        => $badge,
				'title'        => $title,
				'text'         => $text,
				'button_text'  => $button,
				'button_link'  => Blocks::link( $url ),
				'button2_text' => $button2,
				'button2_link' => Blocks::link( $url2 ),
			);
		};

		return El::box(
			array(
				'dir'        => 'row',
				'dir_tablet' => 'column',
				'gap'        => 18,
			),
			array(
				El::box(
					array(
						'width'        => 66,
						'width_tablet' => 100,
					),
					array(
						El::w(
							'stx-slides',
							array(
								'slides'     => array(
									$slide(
										__( 'Courses, books and workshops', 'studiare-extensions' ),
										__( "Build your own\ngrowth path", 'studiare-extensions' ),
										__( 'Video courses, digital books and live workshops, with practical lessons you can use the same day.', 'studiare-extensions' ),
										__( 'Browse the shop', 'studiare-extensions' ),
										Blocks::shop_url(),
										__( 'How to choose', 'studiare-extensions' )
									),
									$slide(
										__( 'New courses every month', 'studiare-extensions' ),
										__( "Learn from\nexperienced teachers", 'studiare-extensions' ),
										__( 'Short lessons, real examples and support after the course.', 'studiare-extensions' ),
										__( 'See the courses', 'studiare-extensions' ),
										Blocks::shop_url(),
										__( 'Meet the teachers', 'studiare-extensions' )
									),
									$slide(
										__( 'Special offer', 'studiare-extensions' ),
										__( "Buy a bundle,\nsave more", 'studiare-extensions' ),
										__( 'Related courses and books together, at a better price.', 'studiare-extensions' ),
										__( 'See the bundles', 'studiare-extensions' ),
										Blocks::shop_url()
									),
								),
								'look'       => 'gradient',
								'min_height' => El::px( 360 ),
							)
						),
					)
				),
				El::box(
					array(
						'width'        => 34,
						'width_tablet' => 100,
						'gap'          => 18,
						'dir_tablet'   => 'row',
						'dir_mobile'   => 'column',
					),
					array(
						self::promo( __( 'Newest course', 'studiare-extensions' ), __( "Start the newest course\nthis week", 'studiare-extensions' ), __( 'See the course', 'studiare-extensions' ), 'dark' ),
						self::promo( __( 'Digital library', 'studiare-extensions' ), __( "E-books with\na seasonal discount", 'studiare-extensions' ), __( 'See the books', 'studiare-extensions' ), 'card' ),
					)
				),
			)
		);
	}

	/**
	 * @param string $eyebrow Small label.
	 * @param string $title   Title.
	 * @param string $cta     Link text.
	 * @param string $look    Look.
	 */
	private static function promo( string $eyebrow, string $title, string $cta, string $look ): array {
		return El::w(
			'stx-promo-card',
			array(
				'eyebrow'   => $eyebrow,
				'title'     => $title,
				'link_text' => $cta,
				'link'      => Blocks::link( Blocks::shop_url() ),
				'look'      => $look,
			),
			array( 'fill' => true )
		);
	}

	/** Full-width accent band with a curved bottom edge, text beside an intro video. */
	private static function curved_hero(): array {
		return El::box(
			array(
				'boxed'        => true,
				'surface'      => 'gradient',
				'tag'          => 'section',
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'gap'          => 40,
				'align'        => 'center',
				'align_tablet' => 'stretch',
				'pad'          => array( 60, 20, 104 ),
				'pad_tablet'   => array( 44, 20, 88 ),
				'pad_mobile'   => array( 28, 16, 72 ),
				'set'          => array( 'css_classes' => 'stx-band stx-curve' ),
			),
			array(
				El::box(
					array(
						'width'        => 50,
						'width_tablet' => 100,
						'gap'          => 28,
					),
					array(
						Blocks::heading(
							__( "Build your own\ngrowth path", 'studiare-extensions' ),
							'xl',
							array(
								'tag'           => 'h1',
								'eyebrow'       => __( 'Learn anytime, anywhere', 'studiare-extensions' ),
								'eyebrow_style' => 'pill',
								'subtitle'      => __( 'Video courses, digital books and live workshops, with practical lessons you can use the same day.', 'studiare-extensions' ),
								'spacing'       => El::px( 16 ),
							)
						),
						Blocks::buttons(
							array(
								array( __( 'Browse the shop', 'studiare-extensions' ), Blocks::shop_url(), 'white' ),
								array( __( 'Get advice', 'studiare-extensions' ), '#', 'outline' ),
							),
							true
						),
					)
				),
				El::box(
					array(
						'width'        => 50,
						'width_tablet' => 100,
					),
					array(
						El::w(
							'stx-media',
							array(
								'frame'       => 'device',
								'ratio'       => '16/10',
								'placeholder' => __( 'Intro video', 'studiare-extensions' ),
							)
						),
						El::w(
							'stx-stats',
							array(
								'look'             => 'card',
								'items'            => self::stat_items( 2 ),
								'_element_width'   => 'auto',
								'_flex_align_self' => 'flex-start',
								'_css_classes'     => 'stx-overlap',
							)
						),
					)
				),
			)
		);
	}

	/** Soft band with a big title, a highlighted word and a banner picture. */
	private static function shop_hero(): array {
		return El::box(
			array(
				'boxed'        => true,
				'surface'      => 'soft',
				'tag'          => 'section',
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'gap'          => 44,
				'gap_mobile'   => 28,
				'align'        => 'center',
				'align_tablet' => 'stretch',
				'pad'          => array( 56, 20 ),
				'pad_mobile'   => array( 32, 16 ),
				'set'          => array( 'css_classes' => 'stx-band stx-bar--line' ),
			),
			array(
				El::box(
					array(
						'width'        => 50,
						'width_tablet' => 100,
						'gap'          => 28,
					),
					array(
						Blocks::heading(
							/* translators: keep <mark> and </mark> around the highlighted words. */
							__( 'Courses and books for <mark>teachers</mark> and personal growth', 'studiare-extensions' ),
							'xl',
							array(
								'tag'           => 'h1',
								'eyebrow'       => __( 'Courses and books ready to download', 'studiare-extensions' ),
								'eyebrow_style' => 'outline',
								'eyebrow_icon'  => 'download',
								'subtitle'      => __( 'Practical, well-researched lessons. Buy in one step and keep them forever.', 'studiare-extensions' ),
								'spacing'       => El::px( 16 ),
							)
						),
						Blocks::buttons(
							array(
								array( __( 'Visit the shop', 'studiare-extensions' ), Blocks::shop_url(), 'accent' ),
								array( __( 'Free courses', 'studiare-extensions' ), Blocks::shop_url(), 'outline' ),
							),
							false
						),
					)
				),
				El::box(
					array(
						'width'        => 50,
						'width_tablet' => 100,
					),
					array(
						El::w(
							'stx-media',
							array(
								'frame'       => 'card',
								'ratio'       => '16/11',
								'placeholder' => __( 'Main shop banner', 'studiare-extensions' ),
							)
						),
					)
				),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Sections
	 * ------------------------------------------------------------------- */

	/** Four promises in one bar, marked with characters as in the design. */
	private static function trust_strip(): array {
		$item = static function ( string $mark, string $title, string $text ): array {
			return array(
				'_id'   => El::id(),
				'icon'  => '',
				'mark'  => $mark,
				'title' => $title,
				'text'  => $text,
			);
		};

		return El::w(
			'stx-features',
			array(
				'layout'         => 'strip',
				'icon_style'     => 'soft',
				'columns'        => '4',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
				'items'          => array(
					$item( Site::is_rtl() ? '٪' : '%', __( 'Money-back guarantee', 'studiare-extensions' ), __( 'Within 7 days of buying', 'studiare-extensions' ) ),
					$item( '∞', __( 'Lifetime access', 'studiare-extensions' ), __( 'No time limit', 'studiare-extensions' ) ),
					// U+FE0E asks for the plain symbol, not a coloured emoji.
					$item( "☎\u{FE0E}", __( 'Advice when you need it', 'studiare-extensions' ), __( 'Every day, 9 to 20', 'studiare-extensions' ) ),
					$item( '✓', __( 'Secure payment', 'studiare-extensions' ), __( 'Trusted bank gateway', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/**
	 * The site's product categories.
	 *
	 * @param string $style `numbered` or `icon`.
	 */
	private static function categories( string $style ): array {
		$icon = 'icon' === $style;

		return El::w(
			'stx-category-grid',
			array(
				'source'         => 'auto',
				'taxonomy'       => 'product_cat',
				'limit'          => 6,
				'style'          => $style,
				'show_count'     => $icon ? '' : 'yes',
				'columns'        => $icon ? '6' : '3',
				'columns_tablet' => '3',
				'columns_mobile' => '2',
			)
		);
	}

	/**
	 * @param bool $shop Shop cards with category buttons in a slide row (style 6), else compact best sellers (style 1).
	 */
	private static function products( bool $shop ): array {
		return El::w(
			'stx-product-grid',
			array(
				'source'         => $shop ? 'latest' : 'best_selling',
				'count'          => $shop ? 8 : 4,
				'slider'         => $shop ? 'yes' : '',
				'filter'         => $shop ? 'yes' : '',
				'title'          => $shop ? __( 'From the shop', 'studiare-extensions' ) : '',
				'card'           => $shop ? 'shop' : 'compact',
				'ratio'          => $shop ? '16/10' : '4/3',
				'meta'           => $shop ? 'duration' : '',
				'mobile_scroll'  => 'yes',
				'columns'        => '4',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
			)
		);
	}

	/** Dark band: this week's offer with a countdown to the end of the week. */
	private static function weekly_offer(): array {
		return El::box(
			array(
				'surface'      => 'dark',
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'align'        => 'center',
				'align_tablet' => 'flex-start',
				'justify'      => 'space-between',
				'gap'          => 26,
				'pad'          => array( 30, 34 ),
				'pad_mobile'   => array( 24, 20 ),
			),
			array(
				Blocks::heading(
					__( '30% off this week\'s bundle', 'studiare-extensions' ),
					'md',
					array(
						'eyebrow'       => __( 'This week\'s offer', 'studiare-extensions' ),
						'eyebrow_style' => 'pill',
						'subtitle'      => __( 'Two video courses, a digital book and a live Q&A session.', 'studiare-extensions' ),
						'spacing'       => El::px( 10 ),
					),
					array( 'fill' => true )
				),
				El::box(
					array(
						'dir'     => 'row',
						'wrap'    => 'wrap',
						'align'   => 'center',
						'gap'     => 18,
						'width'   => 'auto',
						'justify' => 'flex-end',
					),
					array(
						El::w( 'stx-countdown', array( 'mode' => 'weekly' ), array( 'width' => 'auto' ) ),
						Blocks::button( __( 'Buy the bundle', 'studiare-extensions' ), Blocks::shop_url(), 'accent', array( 'size' => 'lg' ) + Blocks::pill() ),
					)
				),
			)
		);
	}

	/** Three learning path cards. */
	private static function paths(): array {
		return El::box(
			array(
				'dir'        => 'row',
				'dir_tablet' => 'column',
				'gap'        => 18,
			),
			array(
				self::path_card(
					__( 'Starter path', 'studiare-extensions' ),
					__( 'Beginner', 'studiare-extensions' ),
					__( 'For anyone taking their first steps: the basics, one at a time.', 'studiare-extensions' ),
					array( __( 'An introductory book', 'studiare-extensions' ), __( 'The fundamentals course', 'studiare-extensions' ), __( 'A live Q&A workshop', 'studiare-extensions' ) ),
					__( 'About 4 weeks', 'studiare-extensions' )
				),
				self::path_card(
					__( 'Skills path', 'studiare-extensions' ),
					__( 'Beginner to intermediate', 'studiare-extensions' ),
					__( 'For learners who want to turn knowledge into everyday skills.', 'studiare-extensions' ),
					array( __( 'The core skills course', 'studiare-extensions' ), __( 'An eight-week workbook', 'studiare-extensions' ), __( 'Companion podcasts', 'studiare-extensions' ) ),
					__( 'About 6 weeks', 'studiare-extensions' )
				),
				self::path_card(
					__( 'Mastery path', 'studiare-extensions' ),
					__( 'Advanced', 'studiare-extensions' ),
					__( 'For those ready to go deeper and work on real projects.', 'studiare-extensions' ),
					array( __( 'The advanced course', 'studiare-extensions' ), __( 'A project workshop', 'studiare-extensions' ), __( 'A one-to-one consultation', 'studiare-extensions' ) ),
					__( 'About 8 weeks', 'studiare-extensions' )
				),
			)
		);
	}

	/**
	 * @param string   $title    Path name.
	 * @param string   $level    Level.
	 * @param string   $text     Who it is for.
	 * @param string[] $steps    Steps.
	 * @param string   $duration Duration.
	 */
	private static function path_card( string $title, string $level, string $text, array $steps, string $duration ): array {
		$items = array();
		foreach ( $steps as $step ) {
			$items[] = array(
				'_id'  => El::id(),
				'text' => $step,
				'icon' => '',
			);
		}

		return El::box(
			array(
				'surface'    => 'card',
				'pad'        => 24,
				'pad_mobile' => 20,
				'gap'        => 14,
				'fill'       => true,
				'set'        => array( 'css_classes' => 'stx-lift' ),
			),
			array(
				El::box(
					array(
						'dir'     => 'row',
						'justify' => 'space-between',
						'align'   => 'center',
						'wrap'    => 'wrap',
						'gap'     => 10,
					),
					array(
						Blocks::heading( $title, 'sm', array( 'tag' => 'h3' ), array( 'width' => 'auto' ) ),
						Blocks::text( '<p>' . esc_html( $level ) . '</p>', 'muted', array( '_element_width' => 'auto' ) ),
					)
				),
				Blocks::text( '<p>' . esc_html( $text ) . '</p>', 'muted' ),
				El::w(
					'stx-icon-list',
					array(
						'items'        => $items,
						'icon_style'   => 'number',
						'_css_classes' => 'stx-dashed-y',
					)
				),
				El::box(
					array(
						'dir'     => 'row',
						'justify' => 'space-between',
						'align'   => 'center',
						'wrap'    => 'wrap',
						'gap'     => 10,
					),
					array(
						Blocks::text( '<p>' . esc_html( $duration ) . '</p>', 'muted', array( '_element_width' => 'auto' ) ),
						Blocks::button( __( 'Start the path', 'studiare-extensions' ) . Blocks::arrow(), '#', 'link', array( 'size' => 'sm' ) ),
					)
				),
			)
		);
	}

	private static function podcasts_title(): array {
		return Blocks::section_title( __( 'Podcasts', 'studiare-extensions' ), __( 'All episodes', 'studiare-extensions' ), Blocks::blog_url(), __( 'Short, free episodes for the commute.', 'studiare-extensions' ) );
	}

	/** Picture cards with a play button (pick the podcast category in Elementor). */
	private static function podcasts(): array {
		return El::w(
			'stx-post-grid',
			array(
				'card'           => 'media',
				'source'         => 'latest',
				'count'          => 4,
				'ratio'          => '4/3',
				'media_icon'     => 'play',
				'mobile_scroll'  => 'yes',
				'columns'        => '4',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
			)
		);
	}

	/** Studiare's teachers; the sample people show until teachers are added. */
	private static function people(): array {
		$roles = array(
			__( 'Counsellor and author', 'studiare-extensions' ),
			__( 'Child psychologist', 'studiare-extensions' ),
			__( 'Communication skills teacher', 'studiare-extensions' ),
			__( 'Researcher', 'studiare-extensions' ),
		);

		$items = array();
		foreach ( $roles as $role ) {
			$items[] = array(
				'_id'  => El::id(),
				'name' => __( 'Teacher name', 'studiare-extensions' ),
				'role' => $role,
			);
		}

		return El::w(
			'stx-people',
			array(
				'source'         => 'teachers',
				'count'          => 4,
				'items'          => $items,
				'ratio'          => '3/4',
				'columns'        => '4',
				'columns_tablet' => '4',
				'columns_mobile' => '2',
			)
		);
	}

	private static function testimonials(): array {
		$quote = static function ( string $text, string $name, string $role ): array {
			return array(
				'_id'    => El::id(),
				'text'   => $text,
				'name'   => $name,
				'role'   => $role,
				'rating' => 5,
			);
		};

		return El::w(
			'stx-testimonials',
			array(
				'look'           => 'soft',
				'mobile_scroll'  => 'yes',
				'columns'        => '3',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
				'items'          => array(
					$quote( __( 'The exercises were short, and in the very first week I could use them at home.', 'studiare-extensions' ), __( 'Maryam R.', 'studiare-extensions' ), __( 'Communication skills course', 'studiare-extensions' ) ),
					$quote( __( 'Most courses stay general; here every session gave me something concrete to do that day.', 'studiare-extensions' ), __( 'Hossein N.', 'studiare-extensions' ), __( 'Habits and focus course', 'studiare-extensions' ) ),
					$quote( __( 'Support went on after the workshop, and I got answers to my questions in the group.', 'studiare-extensions' ), __( 'Somayeh K.', 'studiare-extensions' ), __( 'Talent discovery workshop', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/** Three sample workshops, dated a few weeks ahead in the site's calendar. */
	private static function events(): array {
		$event = static function ( int $days, string $title, string $time, string $place ): array {
			list( $day, $month ) = self::sample_date( $days );

			return array(
				'_id'       => El::id(),
				'day'       => $day,
				'month'     => $month,
				'title'     => $title,
				'time'      => $time,
				'place'     => $place,
				'link_text' => __( 'Sign up', 'studiare-extensions' ),
				'link'      => Blocks::link( '#' ),
			);
		};

		return El::w(
			'stx-events',
			array(
				'mobile_scroll'  => 'yes',
				'columns'        => '3',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
				'items'          => array(
					$event( 12, __( 'Effective conversation workshop', 'studiare-extensions' ), __( '18:00 to 20:00', 'studiare-extensions' ), __( 'Online, in Skyroom', 'studiare-extensions' ) ),
					$event( 25, __( 'Live Q&A session', 'studiare-extensions' ), __( '17:00 to 19:00', 'studiare-extensions' ), __( 'Online, in Skyroom', 'studiare-extensions' ) ),
					$event( 40, __( 'In-person skills workshop', 'studiare-extensions' ), __( '9:00 to 13:00', 'studiare-extensions' ), __( 'In person, limited seats', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/**
	 * Newest posts.
	 *
	 * @param string $card `card` (style 6) or `simple` (style 1).
	 */
	private static function blog( string $card ): array {
		$simple = 'simple' === $card;

		return El::w(
			'stx-post-grid',
			array(
				'card'           => $card,
				'count'          => 4,
				'ratio'          => $simple ? '16/10' : '16/9',
				'button_text'    => $simple ? __( 'View', 'studiare-extensions' ) : __( 'Read more', 'studiare-extensions' ),
				'mobile_scroll'  => 'yes',
				'columns'        => '4',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
			)
		);
	}

	/** FAQ beside an accent call-to-action box with key numbers. */
	private static function faq_and_cta(): array {
		$qa = static function ( string $question, string $answer ): array {
			return array(
				'_id'      => El::id(),
				'question' => $question,
				'answer'   => '<p>' . esc_html( $answer ) . '</p>',
			);
		};

		return El::box(
			array(
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'gap'          => 22,
				'align'        => 'flex-start',
				'align_tablet' => 'stretch',
			),
			array(
				El::box(
					array(
						'width'        => 50,
						'width_tablet' => 100,
						'gap'          => 20,
					),
					array(
						Blocks::section_title( __( 'Frequently asked questions', 'studiare-extensions' ) ),
						El::w(
							'stx-faq',
							array(
								'items' => array(
									$qa( __( 'How long can I access a course?', 'studiare-extensions' ), __( 'Once you buy, the videos and files are yours to keep, with no time limit.', 'studiare-extensions' ) ),
									$qa( __( 'Can I get a refund?', 'studiare-extensions' ), __( 'Within seven days of buying, if you have watched less than a fifth of the course, you get a full refund.', 'studiare-extensions' ) ),
									$qa( __( 'Are the workshops held in person?', 'studiare-extensions' ), __( 'Most workshops are live online; in-person ones are announced with limited seats.', 'studiare-extensions' ) ),
									$qa( __( 'How do I choose the right course?', 'studiare-extensions' ), __( 'Take the short guide or call support, and we will suggest two or three options that fit you.', 'studiare-extensions' ) ),
								),
							)
						),
					)
				),
				El::box(
					array(
						'width'        => 50,
						'width_tablet' => 100,
						'surface'      => 'gradient',
						'pad'          => 32,
						'pad_mobile'   => 24,
						'gap'          => 24,
					),
					array(
						Blocks::heading( __( 'Not sure where to start?', 'studiare-extensions' ), 'md', array( 'subtitle' => __( 'Answer a few short questions and we will suggest two or three products that fit your needs.', 'studiare-extensions' ) ) ),
						Blocks::buttons(
							array(
								array( __( 'Start the guide', 'studiare-extensions' ), '#', 'white' ),
								array( __( 'Talk to an adviser', 'studiare-extensions' ), '#', 'outline' ),
							),
							true
						),
						El::w(
							'stx-stats',
							array(
								'look'         => 'plain',
								'items'        => self::stat_items( 3 ),
								'_css_classes' => 'stx-rule-top',
							)
						),
					)
				),
			)
		);
	}

	/** Newsletter card: text beside the form. */
	private static function newsletter(): array {
		return El::box(
			array(
				'surface'      => 'card',
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'align'        => 'center',
				'align_tablet' => 'stretch',
				'gap'          => 22,
				'pad'          => array( 30, 34 ),
				'pad_mobile'   => array( 24, 20 ),
			),
			array(
				Blocks::heading(
					__( 'A short letter every week', 'studiare-extensions' ),
					'sm',
					array( 'subtitle' => __( 'One practical exercise and the newest blog posts, with no ads.', 'studiare-extensions' ) ),
					array( 'fill' => true )
				),
				El::box(
					array(
						'width'        => 50,
						'width_tablet' => 100,
					),
					array( El::w( 'stx-newsletter' ) )
				),
			)
		);
	}

	/** Accent box closing the Studiare-style page. */
	private static function closing_cta(): array {
		return El::box(
			array(
				'surface'      => 'gradient',
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'align'        => 'center',
				'align_tablet' => 'flex-start',
				'justify'      => 'space-between',
				'gap'          => 24,
				'pad'          => 38,
				'pad_mobile'   => 24,
			),
			array(
				Blocks::heading( __( 'Not sure where to start?', 'studiare-extensions' ), 'md', array( 'subtitle' => __( 'Answer a few short questions and we will suggest two or three products that fit your needs.', 'studiare-extensions' ) ), array( 'fill' => true ) ),
				Blocks::buttons(
					array(
						array( __( 'Start the guide', 'studiare-extensions' ), '#', 'white' ),
						array( __( 'Contact us', 'studiare-extensions' ), '#', 'outline' ),
					),
					true
				),
			)
		);
	}

	/** Dark band promoting a bundle, with its price and a buy button. */
	private static function bundle_offer(): array {
		return El::box(
			array(
				'surface'      => 'dark',
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'align'        => 'center',
				'align_tablet' => 'flex-start',
				'gap'          => 32,
				'pad'          => array( 44, 40 ),
				'pad_mobile'   => array( 28, 22 ),
			),
			array(
				Blocks::heading(
					__( 'Three classroom and communication courses in one', 'studiare-extensions' ),
					'lg',
					array(
						'eyebrow'      => __( 'Teachers\' bundle', 'studiare-extensions' ),
						'eyebrow_icon' => 'graduation',
						'subtitle'     => __( 'Classroom management, talking with students and parents, and motivation skills, 30% off.', 'studiare-extensions' ),
						'spacing'      => El::px( 12 ),
					),
					array( 'fill' => true )
				),
				El::w(
					'stx-offer-price',
					array(
						'source'      => 'manual',
						'price'       => self::money( 490000 ),
						'old_price'   => self::money( 700000 ),
						'button_text' => __( 'Add the bundle to the cart', 'studiare-extensions' ),
						'button_link' => Blocks::link( Blocks::shop_url() ),
					),
					array( 'width' => 'auto' )
				),
			)
		);
	}

	/** Four perks as open tiles with outlined icons. */
	private static function perks(): array {
		$item = static function ( string $icon, string $title, string $text ): array {
			return array(
				'_id'   => El::id(),
				'icon'  => $icon,
				'mark'  => '',
				'title' => $title,
				'text'  => $text,
			);
		};

		return El::w(
			'stx-features',
			array(
				'layout'         => 'tiles',
				'icon_style'     => 'outline',
				'columns'        => '4',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
				'items'          => array(
					$item( 'lock', __( 'Secure payment', 'studiare-extensions' ), __( 'Instant access right after paying.', 'studiare-extensions' ) ),
					$item( 'download', __( 'Download anytime', 'studiare-extensions' ), __( 'Your files stay in your account for good.', 'studiare-extensions' ) ),
					$item( 'check', __( 'Quality guaranteed', 'studiare-extensions' ), __( 'Specialist content that fits your needs.', 'studiare-extensions' ) ),
					$item( 'chat', __( 'Support every day', 'studiare-extensions' ), __( 'Every day from 9 to 20.', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Sample data
	 * ------------------------------------------------------------------- */

	/**
	 * Sample key numbers.
	 *
	 * @param int $count How many (2 or 3).
	 */
	private static function stat_items( int $count ): array {
		$items = array(
			array( '+71', __( 'Articles published', 'studiare-extensions' ) ),
			array( '+15', __( 'Years of experience', 'studiare-extensions' ) ),
			array( '+3,200', __( 'Learners', 'studiare-extensions' ) ),
		);

		$out = array();
		foreach ( array_slice( $items, 0, $count ) as $item ) {
			$out[] = array(
				'_id'   => El::id(),
				'value' => $item[0],
				'label' => $item[1],
			);
		}

		return $out;
	}

	/**
	 * Day and month a number of days from now, in the site's calendar
	 * (Jalali on Persian sites).
	 *
	 * @param int $days Days ahead.
	 * @return string[] Day (two digits) and month name.
	 */
	private static function sample_date( int $days ): array {
		$date = current_datetime()->modify( '+' . $days . ' days' );

		if ( Persian::is_site_persian() ) {
			list( , $month, $day ) = Persian::gregorian_to_jalali( (int) $date->format( 'Y' ), (int) $date->format( 'n' ), (int) $date->format( 'j' ) );

			return array( str_pad( (string) $day, 2, '0', STR_PAD_LEFT ), Persian::jalali_month( $month ) );
		}

		return array( $date->format( 'd' ), wp_date( 'M', $date->getTimestamp() ) );
	}

	/**
	 * Amount in the shop's currency, as plain text (sample prices).
	 *
	 * @param int $amount Amount.
	 */
	private static function money( int $amount ): string {
		if ( function_exists( 'wc_price' ) ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
		}

		return number_format_i18n( $amount );
	}
}
