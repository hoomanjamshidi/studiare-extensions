<?php
/**
 * About us page designs. Each one has a key numbers section (count-up
 * numbers), the site's story as a timeline, values, the team (Studiare's
 * teachers) and a closing call to action. Texts are samples to replace in
 * Elementor.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

use StudiareExt\Core\Persian;

defined( 'ABSPATH' ) || exit;

final class About {

	/** Story: hero with a team photo, number tiles, timeline, values, team, reviews. */
	public static function story(): array {
		return array(
			self::story_hero(),
			Blocks::band(
				'plain',
				array(
					Blocks::section_title( __( 'Our numbers', 'studiare-extensions' ), '', '', __( 'What years of teaching have added up to.', 'studiare-extensions' ) ),
					self::stats( 'tiles', 4 ),
				),
				Blocks::white_band()
			),
			Blocks::band(
				'page',
				array(
					Blocks::section_title( __( 'How we got here', 'studiare-extensions' ) ),
					self::timeline( 'alternate' ),
				),
				array( 'pad' => array( 48, 20 ) )
			),
			Blocks::band( 'page', array( Blocks::section_title( __( 'What we care about', 'studiare-extensions' ) ), self::values( 'cards', 'soft' ) ) ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'The people behind it', 'studiare-extensions' ) ), self::team() ) ),
			Blocks::band( 'plain', array( Blocks::section_title( __( 'What learners say', 'studiare-extensions' ) ), self::testimonials( 'soft' ) ), Blocks::white_band() ),
			Blocks::band( 'page', array( self::closing_cta( 'gradient' ) ), Blocks::last_band() ),
		);
	}

	/** Minimal: centred title, a wide photo, large numbers, beliefs, milestones and FAQ. */
	public static function minimal(): array {
		$narrow = array(
			'boxed'      => 860,
			'pad'        => array( 40, 20 ),
			'pad_mobile' => array( 28, 16 ),
		);

		return array(
			Blocks::band(
				'plain',
				array(
					Blocks::heading(
						__( 'We make learning simple, practical and within reach', 'studiare-extensions' ),
						'xl',
						array(
							'tag'      => 'h1',
							'eyebrow'  => __( 'About us', 'studiare-extensions' ),
							'subtitle' => __( 'A small team of teachers, designers and developers who believe a good course should change what you do on Monday morning.', 'studiare-extensions' ),
							'align'    => 'center',
							'spacing'  => El::px( 18 ),
						)
					),
					El::box(
						array(
							'dir'     => 'row',
							'justify' => 'center',
						),
						array(
							Blocks::buttons(
								array(
									array( __( 'See the courses', 'studiare-extensions' ), Blocks::shop_url(), 'accent' ),
									array( __( 'Contact us', 'studiare-extensions' ), '#', 'outline' ),
								),
								true
							),
						)
					),
				),
				array_merge(
					$narrow,
					array(
						'gap'        => 28,
						'pad'        => array( 64, 20, 36 ),
						'pad_mobile' => array( 36, 16, 24 ),
					)
				)
			),
			Blocks::band(
				'plain',
				array(
					El::w(
						'stx-media',
						array(
							'frame'       => 'card',
							'ratio'       => '21/9',
							'placeholder' => __( 'A photo of your team or classroom', 'studiare-extensions' ),
						)
					),
				),
				array(
					'pad'        => array( 0, 20, 12 ),
					'pad_mobile' => array( 0, 16, 8 ),
				)
			),
			Blocks::band( 'plain', array( self::stats( 'band', 4 ) ), Blocks::white_band() ),
			Blocks::band(
				'page',
				array(
					El::box(
						array(
							'dir'          => 'row',
							'dir_tablet'   => 'column',
							'gap'          => 40,
							'gap_mobile'   => 24,
							'align'        => 'flex-start',
							'align_tablet' => 'stretch',
						),
						array(
							El::box(
								array(
									'width'        => 34,
									'width_tablet' => 100,
									'sticky'       => 'desktop',
									'gap'          => 18,
								),
								array(
									Blocks::heading(
										__( 'What we believe', 'studiare-extensions' ),
										'lg',
										array( 'subtitle' => __( 'Three ideas behind every course, every answer to a message and every price we set.', 'studiare-extensions' ) )
									),
								)
							),
							El::box(
								array(
									'width'        => 66,
									'width_tablet' => 100,
								),
								array( self::beliefs() )
							),
						)
					),
				),
				array( 'pad' => array( 52, 20 ) )
			),
			Blocks::band(
				'plain',
				array(
					Blocks::heading( __( 'Milestones', 'studiare-extensions' ), 'lg', array( 'align' => 'center' ) ),
					self::timeline( 'line' ),
				),
				array_merge( $narrow, array( 'gap' => 26 ) )
			),
			Blocks::band(
				'page',
				array(
					Blocks::heading( __( 'Questions about us', 'studiare-extensions' ), 'lg', array( 'align' => 'center' ) ),
					self::faq(),
				),
				array_merge(
					$narrow,
					array(
						'surface' => 'page',
						'gap'     => 22,
					)
				)
			),
			Blocks::band( 'page', array( self::closing_cta( 'card' ) ), array_merge( Blocks::last_band(), array( 'boxed' => 860 ) ) ),
		);
	}

	/** Academy: curved accent hero with a video, dark band of large numbers, reasons, steps, team. */
	public static function academy(): array {
		return array(
			self::academy_hero(),
			Blocks::band(
				'page',
				array(
					El::box(
						array(
							'surface'    => 'dark',
							'gap'        => 26,
							'pad'        => array( 40, 32 ),
							'pad_mobile' => array( 30, 18 ),
						),
						array(
							Blocks::heading(
								__( 'Studiare+ in numbers', 'studiare-extensions' ),
								'md',
								array(
									'align'    => 'center',
									'subtitle' => __( 'Updated every season.', 'studiare-extensions' ),
								)
							),
							self::stats( 'band', 4 ),
						)
					),
				),
				array( 'pad' => array( 48, 20, 24 ) )
			),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Why learners choose us', 'studiare-extensions' ) ), self::reasons() ) ),
			Blocks::band( 'plain', array( Blocks::section_title( __( 'Our path so far', 'studiare-extensions' ) ), self::timeline( 'row' ) ), Blocks::white_band() ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Meet the teachers', 'studiare-extensions' ) ), self::team() ) ),
			Blocks::band( 'page', array( Blocks::section_title( __( 'In their words', 'studiare-extensions' ) ), self::testimonials( 'card' ) ) ),
			Blocks::band( 'page', array( self::closing_cta( 'dark' ) ), Blocks::last_band() ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Heroes
	 * ------------------------------------------------------------------- */

	/** Title and buttons beside a team photo, with a number card on its corner. */
	private static function story_hero(): array {
		return El::box(
			array(
				'boxed'        => true,
				'surface'      => 'soft',
				'tag'          => 'section',
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'gap'          => 48,
				'gap_mobile'   => 30,
				'align'        => 'center',
				'align_tablet' => 'stretch',
				'pad'          => array( 60, 20 ),
				'pad_mobile'   => array( 32, 16, 40 ),
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
							__( 'We help people learn skills that <mark>change their lives</mark>', 'studiare-extensions' ),
							'xl',
							array(
								'tag'           => 'h1',
								'eyebrow'       => __( 'About us', 'studiare-extensions' ),
								'eyebrow_style' => 'outline',
								'eyebrow_icon'  => 'info',
								'subtitle'      => __( 'Since our first class, we have kept one promise: short, practical lessons from people who do the work, and real answers when you get stuck.', 'studiare-extensions' ),
								'spacing'       => El::px( 16 ),
							)
						),
						Blocks::buttons(
							array(
								array( __( 'See the courses', 'studiare-extensions' ), Blocks::shop_url(), 'accent' ),
								array( __( 'Contact us', 'studiare-extensions' ), '#', 'outline' ),
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
								'ratio'       => '4/3',
								'placeholder' => __( 'A photo of your team', 'studiare-extensions' ),
							)
						),
						El::w(
							'stx-stats',
							array(
								'look'             => 'card',
								'count_up'         => 'yes',
								'items'            => self::stat_items( array( 'learners', 'years' ) ),
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

	/** Curved accent band: title and buttons beside the intro video. */
	private static function academy_hero(): array {
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
							__( "An online academy\nbuilt around you", 'studiare-extensions' ),
							'xl',
							array(
								'tag'           => 'h1',
								'eyebrow'       => __( 'About the academy', 'studiare-extensions' ),
								'eyebrow_style' => 'pill',
								'subtitle'      => __( 'Video courses, books and live workshops by experienced teachers, with support that stays after you buy.', 'studiare-extensions' ),
								'spacing'       => El::px( 16 ),
							)
						),
						Blocks::buttons(
							array(
								array( __( 'Browse the courses', 'studiare-extensions' ), Blocks::shop_url(), 'white' ),
								array( __( 'Talk to us', 'studiare-extensions' ), '#', 'outline' ),
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
								'placeholder' => __( 'A short video about the academy', 'studiare-extensions' ),
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

	/**
	 * Key numbers with icons, counting up.
	 *
	 * @param string $look  `tiles` or `band`.
	 * @param int    $count How many.
	 */
	private static function stats( string $look, int $count ): array {
		return El::w(
			'stx-stats',
			array(
				'look'           => $look,
				'count_up'       => 'yes',
				'columns'        => (string) $count,
				'columns_tablet' => '2',
				'columns_mobile' => '2',
				'items'          => array_slice( self::stat_items( array( 'learners', 'courses', 'satisfaction', 'years' ) ), 0, $count ),
			)
		);
	}

	/**
	 * Sample numbers.
	 *
	 * @param string[] $keys Which ones, in order.
	 */
	private static function stat_items( array $keys ): array {
		// Latin digits with the site's separators: the widget shows Persian digits when that option is on.
		$persian = Persian::is_site_persian();
		$all     = array(
			'learners'     => array( '+' . number_format( 12000, 0, '.', $persian ? '٬' : ',' ), __( 'Learners', 'studiare-extensions' ), 'users' ),
			'courses'      => array( '+85', __( 'Courses and workshops', 'studiare-extensions' ), 'graduation' ),
			'satisfaction' => array( $persian ? '98٪' : '98%', __( 'Say they would recommend us', 'studiare-extensions' ), 'star' ),
			'years'        => array( '+15', __( 'Years of teaching', 'studiare-extensions' ), 'award' ),
		);

		$items = array();
		foreach ( $keys as $key ) {
			$items[] = array(
				'_id'   => El::id(),
				'value' => $all[ $key ][0],
				'label' => $all[ $key ][1],
				'icon'  => $all[ $key ][2],
			);
		}

		return $items;
	}

	/**
	 * Four sample milestones, a year apart in the site's calendar.
	 *
	 * @param string $look Timeline look.
	 */
	private static function timeline( string $look ): array {
		$steps = array(
			array( __( 'The first class', 'studiare-extensions' ), __( 'Twelve learners in a rented room, and a promise to keep lessons short and useful.', 'studiare-extensions' ) ),
			array( __( 'Going online', 'studiare-extensions' ), __( 'Our first video course, so people in every city could learn with us.', 'studiare-extensions' ) ),
			array( __( 'A growing team', 'studiare-extensions' ), __( 'New teachers joined, and support became a team of its own.', 'studiare-extensions' ) ),
			array( __( 'Today', 'studiare-extensions' ), __( 'Thousands of learners, live workshops every month and new courses every season.', 'studiare-extensions' ) ),
		);

		$year  = self::year() - count( $steps ) * 2 + 2;
		$items = array();
		foreach ( $steps as $index => $step ) {
			$items[] = array(
				'_id'   => El::id(),
				'year'  => (string) ( $year + $index * 2 ),
				'title' => $step[0],
				'text'  => $step[1],
			);
		}

		return El::w(
			'stx-timeline',
			array(
				'look'  => $look,
				'items' => $items,
			)
		);
	}

	/**
	 * Four values with icons.
	 *
	 * @param string $layout     Features layout.
	 * @param string $icon_style Icon style.
	 */
	private static function values( string $layout, string $icon_style ): array {
		return El::w(
			'stx-features',
			array(
				'layout'         => $layout,
				'icon_style'     => $icon_style,
				'columns'        => '4',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
				'items'          => array(
					self::feature( 'check', __( 'Practical first', 'studiare-extensions' ), __( 'Every lesson ends with something you can do the same day.', 'studiare-extensions' ) ),
					self::feature( 'support', __( 'Real support', 'studiare-extensions' ), __( 'Questions get answers from people, not bots, every day.', 'studiare-extensions' ) ),
					self::feature( 'wallet', __( 'Fair prices', 'studiare-extensions' ), __( 'Clear prices, no surprises, and a refund if a course is not for you.', 'studiare-extensions' ) ),
					self::feature( 'sparkles', __( 'Always current', 'studiare-extensions' ), __( 'Courses are reviewed and updated as the field changes.', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/** Three beliefs, numbered, for the minimal design. */
	private static function beliefs(): array {
		return El::w(
			'stx-features',
			array(
				'layout'         => 'tiles',
				'icon_style'     => 'outline',
				'columns'        => '1',
				'columns_tablet' => '1',
				'columns_mobile' => '1',
				'items'          => array(
					self::feature( '', __( 'Less theory, more practice', 'studiare-extensions' ), __( 'We cut what you will never use and keep what you will use this week.', 'studiare-extensions' ), '01' ),
					self::feature( '', __( 'Teachers who do the work', 'studiare-extensions' ), __( 'Everyone who teaches here does the job they teach, every day.', 'studiare-extensions' ), '02' ),
					self::feature( '', __( 'Learning does not end at checkout', 'studiare-extensions' ), __( 'Updates, answers and a community stay with you after the course.', 'studiare-extensions' ), '03' ),
				),
			)
		);
	}

	/** Six reasons in open tiles, for the academy design. */
	private static function reasons(): array {
		return El::w(
			'stx-features',
			array(
				'layout'         => 'tiles',
				'icon_style'     => 'outline',
				'columns'        => '3',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
				'items'          => array(
					self::feature( 'graduation', __( 'Experienced teachers', 'studiare-extensions' ), __( 'People who practise what they teach.', 'studiare-extensions' ) ),
					self::feature( 'play', __( 'Learn at your pace', 'studiare-extensions' ), __( 'Short videos you can watch on any device.', 'studiare-extensions' ) ),
					self::feature( 'chat', __( 'Answers when you are stuck', 'studiare-extensions' ), __( 'Ask under any lesson and get a reply.', 'studiare-extensions' ) ),
					self::feature( 'award', __( 'Certificate of completion', 'studiare-extensions' ), __( 'Show what you have learned.', 'studiare-extensions' ) ),
					self::feature( 'download', __( 'Yours for good', 'studiare-extensions' ), __( 'Lifetime access, with every update.', 'studiare-extensions' ) ),
					self::feature( 'lock', __( 'Safe payment', 'studiare-extensions' ), __( 'A trusted gateway and a clear refund policy.', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/**
	 * Features item.
	 *
	 * @param string $icon  Icon key ('' for a mark).
	 * @param string $title Title.
	 * @param string $text  Text.
	 * @param string $mark  Character shown instead of the icon.
	 */
	private static function feature( string $icon, string $title, string $text, string $mark = '' ): array {
		return array(
			'_id'   => El::id(),
			'icon'  => $icon,
			'mark'  => $mark,
			'title' => $title,
			'text'  => $text,
		);
	}

	/** Studiare's teachers; the sample people show until teachers are added. */
	private static function team(): array {
		$roles = array(
			__( 'Founder and teacher', 'studiare-extensions' ),
			__( 'Head of education', 'studiare-extensions' ),
			__( 'Support lead', 'studiare-extensions' ),
			__( 'Course designer', 'studiare-extensions' ),
		);

		$items = array();
		foreach ( $roles as $role ) {
			$items[] = array(
				'_id'  => El::id(),
				'name' => __( 'Team member', 'studiare-extensions' ),
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

	/**
	 * Three sample reviews.
	 *
	 * @param string $look `soft` or `card`.
	 */
	private static function testimonials( string $look ): array {
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
				'look'           => $look,
				'mobile_scroll'  => 'yes',
				'columns'        => '3',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
				'items'          => array(
					$quote( __( 'I wrote to support at midnight and had an answer before class the next morning.', 'studiare-extensions' ), __( 'Neda A.', 'studiare-extensions' ), __( 'Learning with us for two years', 'studiare-extensions' ) ),
					$quote( __( 'The lessons are short and to the point. I used the first one at work the same week.', 'studiare-extensions' ), __( 'Reza M.', 'studiare-extensions' ), __( 'Project manager', 'studiare-extensions' ) ),
					$quote( __( 'It feels like a small school where the teachers know you, even online.', 'studiare-extensions' ), __( 'Sara K.', 'studiare-extensions' ), __( 'Workshop participant', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/** Four questions people ask about the site. */
	private static function faq(): array {
		$qa = static function ( string $question, string $answer ): array {
			return array(
				'_id'      => El::id(),
				'question' => $question,
				'answer'   => '<p>' . esc_html( $answer ) . '</p>',
			);
		};

		return El::w(
			'stx-faq',
			array(
				'items' => array(
					$qa( __( 'Who teaches the courses?', 'studiare-extensions' ), __( 'Teachers who work in the field they teach. Each course page introduces its teacher.', 'studiare-extensions' ) ),
					$qa( __( 'Do I get a certificate?', 'studiare-extensions' ), __( 'Yes. When you finish a course, you can download a certificate of completion from your account.', 'studiare-extensions' ) ),
					$qa( __( 'Can organisations train their teams with you?', 'studiare-extensions' ), __( 'Yes. Write to us and we will suggest a plan with group prices.', 'studiare-extensions' ) ),
					$qa( __( 'How can I teach here?', 'studiare-extensions' ), __( 'Send us a short introduction and a sample lesson through the contact page.', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/**
	 * Closing call to action.
	 *
	 * @param string $surface `gradient`, `card` or `dark`.
	 */
	private static function closing_cta( string $surface ): array {
		$on_accent = 'gradient' === $surface;
		$on_dark   = 'dark' === $surface;

		return El::box(
			array(
				'surface'      => $surface,
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
				Blocks::heading(
					__( 'Ready to learn something new?', 'studiare-extensions' ),
					'md',
					array( 'subtitle' => __( 'Start with a free lesson, or tell us what you need and we will suggest a course.', 'studiare-extensions' ) ),
					array( 'fill' => true )
				),
				Blocks::buttons(
					array(
						array( __( 'See the courses', 'studiare-extensions' ), Blocks::shop_url(), $on_accent ? 'white' : 'accent' ),
						array( __( 'Contact us', 'studiare-extensions' ), '#', $on_dark ? 'light' : 'outline' ),
					),
					true
				),
			)
		);
	}

	/** This year in the site's calendar (Solar Hijri on Persian sites). */
	private static function year(): int {
		return Persian::is_site_persian() ? Persian::jalali_year() : (int) current_time( 'Y' );
	}
}
