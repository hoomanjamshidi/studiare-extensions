<?php
/**
 * Map: an OpenStreetMap or Google Maps view of a point (no API key), a map
 * embed pasted from Neshan, Balad or Google, or a picture of the map. Route
 * buttons open the point in Neshan, Balad, Google Maps or Waze, and an
 * optional card shows the address and opening hours.
 *
 * The map keeps its height before it loads (no layout shift). "Load on
 * click" shows a light placeholder instead of the iframe, which keeps map
 * scripts off the page until a visitor asks for them; without JavaScript
 * its button opens the map in a new tab.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Core\Persian;
use StudiareExt\Modules\Builder\Assets;

defined( 'ABSPATH' ) || exit;

final class Map extends Page_Base {

	/** Hosts a pasted embed may point at: map services only, never an arbitrary page. */
	private const EMBED_HOSTS = array( 'google.com', 'neshan.org', 'balad.ir', 'openstreetmap.org', 'map.ir' );

	/** Route apps: key => link with {lat} and {lng} (Neshan's and Balad's taken from their web apps' routes). */
	private const ROUTE_URLS = array(
		'neshan' => 'https://neshan.org/maps/routing/car/destination/{lat},{lng}',
		'balad'  => 'https://balad.ir/location?latitude={lat}&longitude={lng}',
		'google' => 'https://www.google.com/maps/dir/?api=1&destination={lat},{lng}',
		'waze'   => 'https://waze.com/ul?ll={lat},{lng}&navigate=yes',
	);

	public function get_name(): string {
		return 'stx-map';
	}

	public function get_title(): string {
		return __( 'Map', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-google-maps';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'map', 'location', 'address', 'neshan', 'balad', 'نقشه', 'آدرس', 'نشان', 'بلد' ) );
	}

	public function get_script_depends(): array {
		return array( Assets::PAGES_HANDLE );
	}

	/** @return array<string, string> Route app key => label. */
	private static function route_labels(): array {
		return array(
			'neshan' => __( 'Neshan', 'studiare-extensions' ),
			'balad'  => __( 'Balad', 'studiare-extensions' ),
			'google' => __( 'Google Maps', 'studiare-extensions' ),
			'waze'   => __( 'Waze', 'studiare-extensions' ),
		);
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_map', __( 'Map', 'studiare-extensions' ) );

		$this->add_control(
			'provider',
			array(
				'label'   => __( 'Map', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'osm',
				'options' => array(
					'osm'    => __( 'OpenStreetMap', 'studiare-extensions' ),
					'google' => __( 'Google Maps', 'studiare-extensions' ),
					'embed'  => __( 'Embed code (Neshan, Balad…)', 'studiare-extensions' ),
					'image'  => __( 'A picture of the map', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'coords_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'To find the numbers, right-click your place in Google Maps or Neshan and copy its coordinates, e.g. 35.6997, 51.3380.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'lat',
			array(
				'label'   => __( 'Latitude', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '35.6997',
			)
		);

		$this->add_control(
			'lng',
			array(
				'label'   => __( 'Longitude', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '51.3380',
			)
		);

		$this->add_control(
			'zoom',
			array(
				'label'     => __( 'Zoom', 'studiare-extensions' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 5,
						'max' => 19,
					),
				),
				'default'   => array( 'size' => 16 ),
				'condition' => array( 'provider' => array( 'osm', 'google' ) ),
			)
		);

		$this->add_control(
			'embed',
			array(
				'label'       => __( 'Embed code or link', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'rows'        => 4,
				'placeholder' => '<iframe src="https://…"></iframe>',
				'description' => __( 'Paste the "embed" code from Neshan, Balad, Google Maps, OpenStreetMap or Map.ir.', 'studiare-extensions' ),
				'condition'   => array( 'provider' => 'embed' ),
			)
		);

		$this->add_control(
			'image',
			array(
				'label'     => __( 'Picture', 'studiare-extensions' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array( 'provider' => 'image' ),
			)
		);

		$this->add_control(
			'load',
			array(
				'label'       => __( 'Loading', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'lazy',
				'options'     => array(
					'lazy'  => __( 'When scrolled into view', 'studiare-extensions' ),
					'click' => __( 'When the visitor asks for it (fastest page)', 'studiare-extensions' ),
				),
				'description' => __( 'On click, the map shows a light placeholder with a button until the visitor opens it.', 'studiare-extensions' ),
				'condition'   => array( 'provider!' => 'image' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => __( 'Title for screen readers', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Our location on the map', 'studiare-extensions' ),
				'label_block' => true,
			)
		);

		$this->end_controls_section();

		$this->start_content_section( 'section_card', __( 'Address and directions', 'studiare-extensions' ) );

		$this->add_control(
			'card',
			array(
				'label'       => __( 'Address card', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Sits on the map on wide screens and under it on phones.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'card_title',
			array(
				'label'     => __( 'Card title', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Our office', 'studiare-extensions' ),
				'condition' => array( 'card' => 'yes' ),
			)
		);

		$this->add_control(
			'address',
			array(
				'label'     => __( 'Address', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'default'   => __( 'Your full address goes here, with the building, floor and unit.', 'studiare-extensions' ),
				'condition' => array( 'card' => 'yes' ),
			)
		);

		$this->add_control(
			'hours',
			array(
				'label'     => __( 'Opening hours', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Saturday to Wednesday, 9 to 17', 'studiare-extensions' ),
				'condition' => array( 'card' => 'yes' ),
			)
		);

		$this->add_control(
			'routes',
			array(
				'label'       => __( 'Directions buttons', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => array( 'neshan', 'balad', 'google' ),
				'options'     => self::route_labels(),
				'separator'   => 'before',
				'description' => __( 'Open your location in the visitor\'s navigation app. Uses the latitude and longitude above.', 'studiare-extensions' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Map', 'studiare-extensions' ) );

		$this->add_responsive_control(
			'height',
			array(
				'label'          => __( 'Height', 'studiare-extensions' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array(
					'px' => array(
						'min' => 180,
						'max' => 720,
					),
				),
				'default'        => array(
					'unit' => 'px',
					'size' => 400,
				),
				'mobile_default' => array(
					'unit' => 'px',
					'size' => 280,
				),
				'selectors'      => array( '{{WRAPPER}} .stx-map' => '--stx-map-h: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_box_style( 'frame', '.stx-map__frame', array( 'padding' => false ) );
		$this->add_box_style( 'card', '.stx-map__card', array( 'label' => __( 'Address card', 'studiare-extensions' ) ) );
		$this->add_text_style( 'card_title', '.stx-map__title', array( 'label' => __( 'Card title', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s      = $this->get_settings_for_display();
		$point  = self::point( (string) $s['lat'], (string) $s['lng'] );
		$routes = $point ? array_values( array_intersect( array_keys( self::ROUTE_URLS ), (array) $s['routes'] ) ) : array();
		$card   = 'yes' === $s['card'] && ( '' !== trim( (string) $s['address'] ) || '' !== trim( (string) $s['hours'] ) || $routes );

		printf( '<div class="stx-map%s">', $card ? ' stx-map--card' : '' );
		echo '<div class="stx-map__frame">';
		$this->render_map( $s, $point );
		echo '</div>';

		if ( $card ) {
			$this->render_card( $s, $point, $routes );
		} elseif ( $routes ) {
			self::render_routes( $point, $routes );
		}

		echo '</div>';
	}

	/**
	 * The map itself: iframe, click-to-load placeholder or picture.
	 *
	 * @param array        $s     Settings.
	 * @param float[]|null $point Latitude and longitude.
	 */
	private function render_map( array $s, ?array $point ): void {
		$title = (string) $s['title'];

		if ( 'image' === $s['provider'] ) {
			$image_id = (int) ( $s['image']['id'] ?? 0 );
			if ( $image_id ) {
				echo wp_get_attachment_image(
					$image_id,
					'large',
					false,
					array(
						'class'   => 'stx-map__img',
						'alt'     => $title,
						'loading' => 'lazy',
					)
				);
			} else {
				self::placeholder( $title, '' );
			}
			return;
		}

		$src = $this->embed_url( $s, $point );
		if ( '' === $src ) {
			$this->editor_hint(
				'embed' === $s['provider']
					? __( 'Paste the embed code of a map from Neshan, Balad, Google Maps, OpenStreetMap or Map.ir.', 'studiare-extensions' )
					: __( 'Enter the latitude and longitude of your place.', 'studiare-extensions' )
			);
			self::placeholder( $title, '' );
			return;
		}

		if ( 'click' === $s['load'] ) {
			self::placeholder( $title, $src, self::open_url( (string) $s['provider'], $point, $src ) );
			return;
		}

		printf(
			'<iframe class="stx-map__iframe" src="%1$s" title="%2$s" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>',
			esc_url( $src ),
			esc_attr( $title )
		);
	}

	/**
	 * Light stand-in for the map: a pin on a soft grid, and a button that
	 * loads the map (pages.js) or, without JavaScript, opens it in a new tab.
	 *
	 * @param string $title Map title.
	 * @param string $src   Embed URL ('' when there is nothing to load).
	 * @param string $open  Page that shows the map.
	 */
	private static function placeholder( string $title, string $src, string $open = '' ): void {
		echo '<div class="stx-map__facade"' . ( '' !== $src ? ' data-stx-map="' . esc_url( $src ) . '" data-title="' . esc_attr( $title ) . '"' : '' ) . '>';
		echo '<span class="stx-map__pin">' . self::icon( 'map-pin' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.

		if ( '' !== $src ) {
			printf(
				'<a class="stx-btn stx-btn--dark stx-map__load" href="%1$s" target="_blank" rel="noopener" data-stx-map-load>%2$s</a>',
				esc_url( '' !== $open ? $open : $src ),
				esc_html__( 'Show the map', 'studiare-extensions' )
			);
		}

		echo '</div>';
	}

	/**
	 * Address, hours and directions in a card.
	 *
	 * @param array        $s      Settings.
	 * @param float[]|null $point  Latitude and longitude.
	 * @param string[]     $routes Route apps.
	 */
	private function render_card( array $s, ?array $point, array $routes ): void {
		echo '<div class="stx-map__card">';

		if ( '' !== trim( (string) $s['card_title'] ) ) {
			printf( '<h3 class="stx-map__title">%s</h3>', esc_html( $s['card_title'] ) );
		}
		if ( '' !== trim( (string) $s['address'] ) ) {
			printf( '<p class="stx-map__line">%1$s<span>%2$s</span></p>', self::icon( 'map-pin' ), nl2br( esc_html( self::digits( $s['address'] ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG, escaped text.
		}
		if ( '' !== trim( (string) $s['hours'] ) ) {
			printf( '<p class="stx-map__line">%1$s<span>%2$s</span></p>', self::icon( 'clock' ), esc_html( self::digits( $s['hours'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
		}
		if ( $routes ) {
			self::render_routes( $point, $routes );
		}

		echo '</div>';
	}

	/**
	 * Directions buttons.
	 *
	 * @param float[]  $point  Latitude and longitude.
	 * @param string[] $routes Route apps.
	 */
	private static function render_routes( array $point, array $routes ): void {
		$labels = self::route_labels();

		printf( '<div class="stx-map__routes"><span class="stx-map__routes-label">%s</span>', esc_html__( 'Directions with', 'studiare-extensions' ) );
		foreach ( $routes as $app ) {
			$url = strtr(
				self::ROUTE_URLS[ $app ],
				array(
					'{lat}' => self::coord( $point[0] ),
					'{lng}' => self::coord( $point[1] ),
				)
			);
			printf( '<a class="stx-map__route stx-map__route--%1$s" href="%2$s" target="_blank" rel="noopener">%3$s</a>', esc_attr( $app ), esc_url( $url ), esc_html( $labels[ $app ] ) );
		}
		echo '</div>';
	}

	/**
	 * Address the iframe loads, or '' when the settings cannot make one.
	 *
	 * @param array        $s     Settings.
	 * @param float[]|null $point Latitude and longitude.
	 */
	private function embed_url( array $s, ?array $point ): string {
		if ( 'embed' === $s['provider'] ) {
			return self::pasted_url( (string) $s['embed'] );
		}

		if ( ! $point ) {
			return '';
		}

		$zoom = max( 5, min( 19, (int) ( $s['zoom']['size'] ?? 16 ) ) );
		$lat  = self::coord( $point[0] );
		$lng  = self::coord( $point[1] );

		if ( 'google' === $s['provider'] ) {
			return 'https://maps.google.com/maps?q=' . $lat . ',' . $lng . '&z=' . $zoom . '&hl=' . rawurlencode( substr( get_locale(), 0, 2 ) ) . '&output=embed';
		}

		// OpenStreetMap frames a box, not a zoom level: about three map tiles wide around the pin.
		$span = 360 / ( 2 ** $zoom );
		$dlng = 1.5 * $span;
		$dlat = 0.75 * $span * cos( deg2rad( $point[0] ) );

		return 'https://www.openstreetmap.org/export/embed.html?' . http_build_query(
			array(
				'bbox'   => implode( ',', array_map( array( self::class, 'coord' ), array( $point[1] - $dlng, $point[0] - $dlat, $point[1] + $dlng, $point[0] + $dlat ) ) ),
				'layer'  => 'mapnik',
				'marker' => $lat . ',' . $lng,
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);
	}

	/**
	 * Page that shows the map in a new tab (the click-to-load button without JavaScript).
	 *
	 * @param string       $provider Provider.
	 * @param float[]|null $point    Latitude and longitude.
	 * @param string       $src      Embed URL.
	 */
	private static function open_url( string $provider, ?array $point, string $src ): string {
		if ( ! $point || 'embed' === $provider ) {
			return $src;
		}

		$lat = self::coord( $point[0] );
		$lng = self::coord( $point[1] );

		return 'google' === $provider
			? 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng
			: 'https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lng . '#map=16/' . $lat . '/' . $lng;
	}

	/**
	 * The iframe address in a pasted embed code (or a pasted link), when it
	 * points at a known map service over HTTPS.
	 *
	 * @param string $pasted Embed code or URL.
	 */
	private static function pasted_url( string $pasted ): string {
		$url = preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $pasted, $m ) ? $m[1] : trim( $pasted );
		$url = html_entity_decode( $url, ENT_QUOTES, 'UTF-8' );

		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return '';
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		foreach ( self::EMBED_HOSTS as $allowed ) {
			if ( $host === $allowed || substr( $host, -strlen( '.' . $allowed ) ) === '.' . $allowed ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Latitude and longitude from the settings (Persian digits allowed), or
	 * null when they are not a valid point.
	 *
	 * @param string $lat Latitude.
	 * @param string $lng Longitude.
	 * @return float[]|null
	 */
	private static function point( string $lat, string $lng ): ?array {
		$lat = str_replace( array( '٫', '/' ), '.', Persian::latin_digits( trim( $lat ) ) );
		$lng = str_replace( array( '٫', '/' ), '.', Persian::latin_digits( trim( $lng ) ) );

		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) || abs( (float) $lat ) > 90 || abs( (float) $lng ) > 180 ) {
			return null;
		}

		return array( (float) $lat, (float) $lng );
	}

	/**
	 * Coordinate for a URL: six decimals (about 10 cm), always with a dot.
	 *
	 * @param float $value Degrees.
	 */
	private static function coord( float $value ): string {
		return number_format( $value, 6, '.', '' );
	}
}
