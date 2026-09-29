<?php
/**
 * Contact us page designs: contact details, the contact form (messages land
 * in Studiare+ → Contact messages), messenger buttons, a map with
 * directions, and common questions. Numbers, usernames and the address are
 * samples to replace in Elementor.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

defined( 'ABSPATH' ) || exit;

final class Contact {

	/** Cards: centred title, four contact cards, form beside messengers, map, questions. */
	public static function cards(): array {
		return array(
			Blocks::band(
				'soft',
				array(
					self::title(
						__( 'We are here to help', 'studiare-extensions' ),
						__( 'Questions about a course, an order or working together? Pick the way that suits you; we answer every message.', 'studiare-extensions' ),
						true
					),
				),
				array(
					'boxed'      => 860,
					'pad'        => array( 60, 20, 48 ),
					'pad_mobile' => array( 36, 16, 30 ),
				)
			),
			Blocks::band(
				'page',
				array( self::details( 'cards', array( 'phone', 'email', 'address', 'hours' ), 4 ) ),
				array(
					'pad'        => array( 32, 20, 12 ),
					'pad_mobile' => array( 20, 16, 8 ),
				)
			),
			Blocks::band(
				'page',
				array(
					El::box(
						array(
							'dir'          => 'row',
							'dir_tablet'   => 'column',
							'gap'          => 24,
							'align'        => 'flex-start',
							'align_tablet' => 'stretch',
						),
						array(
							El::box(
								array(
									'width'        => 62,
									'width_tablet' => 100,
								),
								array( self::form( 'card', true ) )
							),
							El::box(
								array(
									'width'        => 38,
									'width_tablet' => 100,
									'surface'      => 'card',
									'gap'          => 18,
									'pad'          => 26,
									'pad_mobile'   => 20,
								),
								array(
									Blocks::heading(
										__( 'Prefer messengers?', 'studiare-extensions' ),
										'sm',
										array( 'subtitle' => __( 'Message us in the app you use every day. We usually reply within an hour during working hours.', 'studiare-extensions' ) )
									),
									self::details( 'buttons', array( 'telegram', 'whatsapp', 'bale', 'eitaa' ) ),
									El::w(
										'stx-icon-list',
										array(
											'items'        => array(
												self::line( 'clock', __( 'Saturday to Wednesday, 9 to 17', 'studiare-extensions' ) ),
												self::line( 'check', __( 'Thursdays until 13', 'studiare-extensions' ) ),
											),
											'_css_classes' => 'stx-rule-top',
										)
									),
								)
							),
						)
					),
				)
			),
			Blocks::band( 'plain', array( Blocks::section_title( __( 'Find us', 'studiare-extensions' ) ), self::map( 'lazy', 420 ) ), Blocks::white_band() ),
			Blocks::band(
				'page',
				array(
					Blocks::heading( __( 'Maybe your answer is here', 'studiare-extensions' ), 'lg', array( 'align' => 'center' ) ),
					self::faq(),
				),
				array_merge(
					Blocks::last_band(),
					array(
						'boxed' => 860,
						'gap'   => 22,
					)
				)
			),
		);
	}

	/** Split: dark panel with every way to reach us beside the form, then the map. */
	public static function split(): array {
		return array(
			Blocks::band(
				'page',
				array(
					self::title(
						__( 'Contact us', 'studiare-extensions' ),
						__( 'Send a message and we will get back to you within one working day.', 'studiare-extensions' ),
						false
					),
					El::box(
						array(
							'dir'          => 'row',
							'dir_tablet'   => 'column',
							'gap'          => 24,
							'align'        => 'stretch',
							'align_tablet' => 'stretch',
						),
						array(
							El::box(
								array(
									'width'        => 38,
									'width_tablet' => 100,
									'surface'      => 'dark',
									'gap'          => 24,
									'pad'          => 30,
									'pad_mobile'   => 22,
								),
								array(
									Blocks::heading(
										__( 'Other ways to reach us', 'studiare-extensions' ),
										'sm',
										array( 'subtitle' => __( 'Call during working hours or write any time.', 'studiare-extensions' ) )
									),
									self::details( 'list', array( 'phone', 'email', 'address', 'hours' ) ),
									self::details( 'buttons', array( 'telegram', 'whatsapp', 'bale', 'eitaa' ), 0, array( '_css_classes' => 'stx-rule-top' ) ),
								)
							),
							El::box(
								array(
									'width'        => 62,
									'width_tablet' => 100,
								),
								array( self::form( 'card', true ) )
							),
						)
					),
				),
				array(
					'gap'        => 28,
					'pad'        => array( 56, 20, 32 ),
					'pad_mobile' => array( 32, 16, 20 ),
				)
			),
			Blocks::band( 'page', array( self::map( 'click', 380 ) ), Blocks::last_band() ),
		);
	}

	/** Support centre: accent hero with messenger buttons, departments, questions beside the form, map. */
	public static function support(): array {
		return array(
			El::box(
				array(
					'boxed'      => 860,
					'surface'    => 'gradient',
					'tag'        => 'section',
					'align'      => 'center',
					'gap'        => 26,
					'pad'        => array( 60, 20, 96 ),
					'pad_mobile' => array( 34, 16, 68 ),
					'set'        => array( 'css_classes' => 'stx-band stx-curve' ),
				),
				array(
					Blocks::heading(
						__( 'How can we help you?', 'studiare-extensions' ),
						'xl',
						array(
							'tag'           => 'h1',
							'eyebrow'       => __( 'Support centre', 'studiare-extensions' ),
							'eyebrow_style' => 'pill',
							'subtitle'      => __( 'The fastest answer is one message away. Choose a messenger, call the right team, or send us the form below.', 'studiare-extensions' ),
							'align'         => 'center',
							'spacing'       => El::px( 16 ),
						)
					),
					self::details( 'buttons', array( 'telegram', 'whatsapp', 'bale', 'eitaa' ), 0, array( 'align' => 'center' ) ),
				)
			),
			Blocks::band(
				'page',
				array(
					Blocks::section_title( __( 'Talk to the right team', 'studiare-extensions' ) ),
					self::departments(),
				),
				array( 'pad' => array( 40, 20, 24 ) )
			),
			Blocks::band(
				'page',
				array(
					El::box(
						array(
							'dir'          => 'row',
							'dir_tablet'   => 'column',
							'gap'          => 28,
							'align'        => 'flex-start',
							'align_tablet' => 'stretch',
						),
						array(
							El::box(
								array(
									'width'        => 46,
									'width_tablet' => 100,
									'gap'          => 20,
								),
								array(
									Blocks::section_title( __( 'Quick answers', 'studiare-extensions' ) ),
									self::faq(),
								)
							),
							El::box(
								array(
									'width'        => 54,
									'width_tablet' => 100,
									'gap'          => 20,
								),
								array(
									Blocks::section_title( __( 'Still need help?', 'studiare-extensions' ) ),
									self::form( 'card', false ),
								)
							),
						)
					),
				)
			),
			Blocks::band( 'plain', array( Blocks::section_title( __( 'Visit us', 'studiare-extensions' ) ), self::map( 'lazy', 360 ) ), array_merge( Blocks::white_band(), array( 'pad' => array( 44, 20, 60 ) ) ) ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Sections
	 * ------------------------------------------------------------------- */

	/**
	 * Page title (h1) with a short introduction.
	 *
	 * @param string $title    Title.
	 * @param string $subtitle Introduction.
	 * @param bool   $center   Centred.
	 */
	private static function title( string $title, string $subtitle, bool $center ): array {
		return Blocks::heading(
			$title,
			'xl',
			array_merge(
				array(
					'tag'           => 'h1',
					'eyebrow'       => __( 'Contact us', 'studiare-extensions' ),
					'eyebrow_style' => 'outline',
					'eyebrow_icon'  => 'chat',
					'subtitle'      => $subtitle,
					'spacing'       => El::px( 16 ),
				),
				$center ? array( 'align' => 'center' ) : array()
			)
		);
	}

	/**
	 * Contact details widget with sample items.
	 *
	 * @param string   $look    `cards`, `list` or `buttons`.
	 * @param string[] $kinds   Which sample items, in order.
	 * @param int      $columns Card columns on wide screens (0 = the widget's default).
	 * @param array    $more    Extra settings.
	 */
	private static function details( string $look, array $kinds, int $columns = 0, array $more = array() ): array {
		$settings = array(
			'source' => 'manual',
			'look'   => $look,
			'items'  => array_map( array( self::class, 'sample_item' ), $kinds ),
		);

		if ( $columns ) {
			$settings += array(
				'columns'        => (string) $columns,
				'columns_tablet' => '2',
				'columns_mobile' => '1',
			);
		}

		return El::w( 'stx-contact-details', array_merge( $settings, $more ) );
	}

	/**
	 * One sample contact item.
	 *
	 * @param string $kind Kind.
	 */
	private static function sample_item( string $kind ): array {
		$samples = array(
			'phone'    => array( '021-00000000', __( 'Saturday to Wednesday, 9 to 17', 'studiare-extensions' ) ),
			'email'    => array( 'info@example.com', __( 'We answer within one working day', 'studiare-extensions' ) ),
			'address'  => array( __( 'Your address goes here', 'studiare-extensions' ), __( 'Visits by appointment', 'studiare-extensions' ) ),
			'hours'    => array( __( 'Saturday to Wednesday, 9 to 17', 'studiare-extensions' ), __( 'Closed on public holidays', 'studiare-extensions' ) ),
			'telegram' => array( '@your_username', '' ),
			'whatsapp' => array( '09120000000', '' ),
			'bale'     => array( '@your_username', '' ),
			'eitaa'    => array( '@your_username', '' ),
		);

		return array(
			'_id'   => El::id(),
			'kind'  => $kind,
			'title' => '',
			'value' => $samples[ $kind ][0],
			'note'  => $samples[ $kind ][1],
		);
	}

	/** Three teams as contact cards: advice by phone, support by email, cooperation on Telegram. */
	private static function departments(): array {
		$team = static function ( string $kind, string $title, string $value, string $note ): array {
			return array(
				'_id'   => El::id(),
				'kind'  => $kind,
				'title' => $title,
				'value' => $value,
				'note'  => $note,
			);
		};

		return El::w(
			'stx-contact-details',
			array(
				'source'         => 'manual',
				'look'           => 'cards',
				'columns'        => '3',
				'columns_tablet' => '1',
				'columns_mobile' => '1',
				'items'          => array(
					$team( 'phone', __( 'Course advice', 'studiare-extensions' ), '021-00000000', __( 'Not sure which course fits? Call us, Saturday to Wednesday, 9 to 17.', 'studiare-extensions' ) ),
					$team( 'email', __( 'Technical support', 'studiare-extensions' ), 'support@example.com', __( 'Trouble with a video, a download or your account.', 'studiare-extensions' ) ),
					$team( 'telegram', __( 'Working with us', 'studiare-extensions' ), '@your_username', __( 'Teaching, partnerships and training for organisations.', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/**
	 * Contact form widget.
	 *
	 * @param string $look    `card` or `plain`.
	 * @param bool   $heading Whether the form starts with its own heading.
	 */
	private static function form( string $look, bool $heading ): array {
		$form = El::w(
			'stx-contact-form',
			array(
				'look'        => $look,
				'two_columns' => 'yes',
			)
		);

		if ( ! $heading ) {
			return $form;
		}

		return El::box(
			array( 'gap' => 18 ),
			array(
				Blocks::heading( __( 'Send us a message', 'studiare-extensions' ), 'sm', array( 'subtitle' => __( 'Fields marked with * are required.', 'studiare-extensions' ) ) ),
				$form,
			)
		);
	}

	/**
	 * Map with the address card and directions.
	 *
	 * @param string $load   `lazy` or `click`.
	 * @param int    $height Height on wide screens.
	 */
	private static function map( string $load, int $height ): array {
		return El::w(
			'stx-map',
			array(
				'provider' => 'osm',
				'load'     => $load,
				'height'   => El::px( $height ),
			)
		);
	}

	/** Four questions people ask before writing. */
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
					$qa( __( 'How soon will I get an answer?', 'studiare-extensions' ), __( 'Messages sent on working days are answered the same day; others on the next working day.', 'studiare-extensions' ) ),
					$qa( __( 'I paid but cannot see my course. What should I do?', 'studiare-extensions' ), __( 'Log in with the email or mobile number you used to pay and open "My courses". If it is not there, send us your order number.', 'studiare-extensions' ) ),
					$qa( __( 'Can I get a refund?', 'studiare-extensions' ), __( 'Within seven days of buying, if you have watched less than a fifth of the course, you get a full refund.', 'studiare-extensions' ) ),
					$qa( __( 'Do you offer group or company training?', 'studiare-extensions' ), __( 'Yes. Choose "Working with us" as the topic in the form and tell us a little about your team.', 'studiare-extensions' ) ),
				),
			)
		);
	}

	/**
	 * Icon list item.
	 *
	 * @param string $icon Icon key.
	 * @param string $text Text.
	 */
	private static function line( string $icon, string $text ): array {
		return array(
			'_id'  => El::id(),
			'text' => $text,
			'icon' => $icon,
		);
	}
}
