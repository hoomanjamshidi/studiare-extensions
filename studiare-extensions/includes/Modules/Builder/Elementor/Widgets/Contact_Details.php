<?php
/**
 * Contact details: phone, email, address, opening hours and messengers
 * (Telegram, WhatsApp, Bale, Eitaa, Instagram) as cards, a list or brand
 * buttons. The ways to reach you come from the floating support button's
 * settings (typed once, shown everywhere) or from a list made here.
 *
 * Numbers, usernames and links are turned into tel:, mailto: and app links
 * by Support_Button\Channels, exactly like the floating button does.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Modules\Support_Button\Channels;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

final class Contact_Details extends Page_Base {

	/** Kinds that are text only (no link built from the value). */
	private const TEXT_KINDS = array( 'address', 'hours' );

	/** Kinds shown left-to-right (numbers, addresses, usernames). */
	private const LTR_KINDS = array( 'phone', 'email', 'telegram', 'whatsapp', 'bale', 'eitaa', 'instagram' );

	public function get_name(): string {
		return 'stx-contact-details';
	}

	public function get_title(): string {
		return __( 'Contact details', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-call-to-action';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'phone', 'email', 'address', 'telegram', 'whatsapp', 'bale', 'eitaa', 'تلفن', 'آدرس', 'تلگرام', 'واتساپ', 'بله', 'ایتا' ) );
	}

	/** @return array<string, string> Kind => label. */
	private static function kinds(): array {
		$channels = Channels::all();

		return array(
			'phone'     => __( 'Phone', 'studiare-extensions' ),
			'email'     => __( 'Email', 'studiare-extensions' ),
			'address'   => __( 'Address', 'studiare-extensions' ),
			'hours'     => __( 'Opening hours', 'studiare-extensions' ),
			'telegram'  => $channels['telegram']['label'],
			'whatsapp'  => $channels['whatsapp']['label'],
			'bale'      => $channels['bale']['label'],
			'eitaa'     => $channels['eitaa']['label'],
			'instagram' => $channels['instagram']['label'],
		);
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Contact details', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'       => __( 'Show', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'manual',
				'options'     => array(
					'manual'  => __( 'The list below', 'studiare-extensions' ),
					'support' => __( 'The floating support button\'s ways to reach us', 'studiare-extensions' ),
				),
				'description' => __( 'The second choice shows what you set in Studiare+ → Floating support button, so you type your numbers and usernames once.', 'studiare-extensions' ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'kind',
			array(
				'label'   => __( 'Type', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'phone',
				'options' => self::kinds(),
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Empty: the type\'s name', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'value',
			array(
				'label'       => __( 'Number, address or username', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => '',
				'description' => __( 'Phones and messengers become links: a number (Persian digits work), @username or a link.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'note',
			array(
				'label'   => __( 'Small text below', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => __( 'Link', 'studiare-extensions' ),
				'type'        => Controls_Manager::URL,
				'description' => __( 'For an address: a link to your place on a map.', 'studiare-extensions' ),
				'condition'   => array( 'kind' => self::TEXT_KINDS ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Items', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'kind'  => 'phone',
						'value' => '021-00000000',
						'note'  => __( 'Saturday to Wednesday, 9 to 17', 'studiare-extensions' ),
					),
					array(
						'kind'  => 'email',
						'value' => 'info@example.com',
						'note'  => __( 'We answer within one working day', 'studiare-extensions' ),
					),
					array(
						'kind'  => 'address',
						'value' => __( 'Your address goes here', 'studiare-extensions' ),
					),
				),
				'title_field' => '{{{ title || kind }}}',
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'look',
			array(
				'label'     => __( 'Style', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cards',
				'separator' => 'before',
				'options'   => array(
					'cards'   => __( 'Cards', 'studiare-extensions' ),
					'list'    => __( 'List', 'studiare-extensions' ),
					'buttons' => __( 'Brand buttons', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'brand',
			array(
				'label'       => __( 'Brand colours', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Messengers in their own colours; off uses the accent colour for all.', 'studiare-extensions' ),
			)
		);

		$this->add_columns_control( '.stx-cdetails', array( 3, 2, 1 ) );
		$this->add_align_control( 'align', '.stx-cdetails' );

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Items', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-cdetails' );
		$this->add_box_style( 'item', '.stx-cdetails__item', array( 'shadow' => true ) );
		$this->add_text_style( 'title', '.stx-cdetails__title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'value', '.stx-cdetails__value', array( 'label' => __( 'Value', 'studiare-extensions' ) ) );
		$this->add_text_style( 'note', '.stx-cdetails__note', array( 'label' => __( 'Small text', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$items = 'support' === $s['source'] ? self::support_items() : self::manual_items( (array) $s['items'] );

		if ( 'buttons' === $s['look'] ) {
			$items = array_values(
				array_filter(
					$items,
					static function ( array $item ): bool {
						return '' !== $item['url'];
					}
				)
			);
		}

		if ( ! $items ) {
			$this->editor_hint(
				'support' === $s['source']
					? __( 'Switch on and fill in some ways to reach you in Studiare+ → Floating support button.', 'studiare-extensions' )
					: __( 'Add a phone number, email or messenger.', 'studiare-extensions' )
			);
			return;
		}

		printf( '<ul class="stx-cdetails stx-cdetails--%s" role="list">', esc_attr( $s['look'] ) );
		foreach ( $items as $item ) {
			$brand = 'yes' === $s['brand'] ? $item['brand'] : '';
			printf(
				'<li class="stx-cdetails__item%1$s"%2$s>',
				'' !== $brand ? ' has-brand' : '',
				'' !== $brand ? ' style="--stx-brand:' . esc_attr( $brand ) . '"' : ''
			);
			'buttons' === $s['look'] ? self::render_button( $item ) : self::render_entry( $item );
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Card or list row: icon, title, value and note. The value (or the title,
	 * when there is no value to show) carries the link.
	 *
	 * @param array $item Item.
	 */
	private static function render_entry( array $item ): void {
		$title = esc_html( $item['label'] );
		$value = nl2br( esc_html( $item['value'] ) );
		if ( $item['ltr'] ) {
			// Numbers and usernames read left to right but still line up with the page.
			$value = '<bdi dir="ltr">' . $value . '</bdi>';
		}

		printf( '<span class="stx-cdetails__icon" aria-hidden="true">%s</span><span class="stx-cdetails__body">', $item['glyph'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
		if ( '' !== $item['value'] ) {
			printf( '<span class="stx-cdetails__title">%1$s</span><span class="stx-cdetails__value">%2$s</span>', $title, self::linked( $item, $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		} else {
			printf( '<span class="stx-cdetails__title">%s</span>', self::linked( $item, $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
		if ( '' !== $item['note'] ) {
			printf( '<span class="stx-cdetails__note">%s</span>', esc_html( self::digits( $item['note'] ) ) );
		}
		echo '</span>';
	}

	/**
	 * Brand button: glyph and the channel's name.
	 *
	 * @param array $item Item (with a link).
	 */
	private static function render_button( array $item ): void {
		printf(
			'<a class="stx-cdetails__btn" href="%1$s"%2$s>%3$s<span>%4$s</span></a>',
			esc_url( $item['url'] ),
			$item['external'] ? ' target="_blank" rel="noopener"' : '',
			$item['glyph'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
			esc_html( $item['label'] )
		);
	}

	/**
	 * Markup, linked when the item has a link.
	 *
	 * @param array  $item Item.
	 * @param string $html Escaped markup.
	 */
	private static function linked( array $item, string $html ): string {
		if ( '' === $item['url'] ) {
			return $html;
		}

		return sprintf(
			'<a class="stx-cdetails__link" href="%1$s"%2$s>%3$s</a>',
			esc_url( $item['url'] ),
			$item['external'] ? ' target="_blank" rel="noopener"' : '',
			$html
		);
	}

	/**
	 * The floating support button's ready channels.
	 *
	 * @return array<int, array>
	 */
	private static function support_items(): array {
		$module = Plugin::instance()->module( 'support_button' );
		if ( ! $module ) {
			return array();
		}

		$items = array();
		foreach ( Channels::ready( $module->settings() ) as $channel ) {
			$items[] = array(
				'label'    => $channel['label'],
				'value'    => self::shown_value( $channel['id'], $channel['value'] ),
				'note'     => $channel['note'],
				'url'      => $channel['url'],
				'external' => $channel['external'],
				'glyph'    => $channel['glyph'],
				'brand'    => $channel['brand'],
				'ltr'      => in_array( $channel['id'], self::LTR_KINDS, true ),
			);
		}

		return $items;
	}

	/**
	 * Items typed in the widget.
	 *
	 * @param array $rows Repeater rows.
	 * @return array<int, array>
	 */
	private static function manual_items( array $rows ): array {
		$kinds    = self::kinds();
		$channels = Channels::all();
		$items    = array();

		foreach ( $rows as $row ) {
			$kind  = isset( $kinds[ $row['kind'] ?? '' ] ) ? $row['kind'] : 'phone';
			$value = trim( (string) ( $row['value'] ?? '' ) );
			$url   = in_array( $kind, self::TEXT_KINDS, true )
				? (string) ( $row['link']['url'] ?? '' )
				: Channels::url(
					$kind,
					array(
						'value'   => $value,
						'message' => '',
					)
				);

			if ( '' === $value && '' === $url ) {
				continue;
			}

			$items[] = array(
				'label'    => '' !== trim( (string) ( $row['title'] ?? '' ) ) ? (string) $row['title'] : $kinds[ $kind ],
				'value'    => self::shown_value( $kind, $value ),
				'note'     => (string) ( $row['note'] ?? '' ),
				'url'      => $url,
				'external' => '' !== $url && Channels::is_external( $url ),
				'glyph'    => self::glyph( $kind ),
				// Phone and email have no brand colour, so they use the accent.
				'brand'    => $channels[ $kind ]['color'] ?? '',
				'ltr'      => in_array( $kind, self::LTR_KINDS, true ),
			);
		}

		return $items;
	}

	/**
	 * The value as shown. A pasted link reads badly, so it is left out and the
	 * linked title is enough; usernames and numbers never contain a slash,
	 * addresses may.
	 *
	 * @param string $kind  Kind.
	 * @param string $value Value as typed.
	 */
	private static function shown_value( string $kind, string $value ): string {
		if ( ! in_array( $kind, self::TEXT_KINDS, true ) && false !== strpos( $value, '/' ) ) {
			return '';
		}

		return self::digits( $value );
	}

	/**
	 * Glyph of a kind, in the same filled style as the messenger logos.
	 *
	 * @param string $kind Kind.
	 */
	private static function glyph( string $kind ): string {
		if ( 'address' === $kind ) {
			return Icon_Library::svg( 'phosphor', 'map-pin', true );
		}
		if ( 'hours' === $kind ) {
			return Icon_Library::svg( 'phosphor', 'clock', true );
		}

		return Channels::glyph( $kind );
	}
}
