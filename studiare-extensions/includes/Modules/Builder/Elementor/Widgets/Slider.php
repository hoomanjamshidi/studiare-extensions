<?php
/**
 * Slider: pictures with an optional phone version, a link and text per
 * slide, in six designs (banner, hero, centre with peeks, cards, side
 * banners, title tabs).
 *
 * Built to pass PageSpeed and GTmetrix: the slides are plain HTML in a
 * scroll-snap track, so they swipe with no script at all; every slide box
 * gets its aspect ratio before anything loads, so nothing shifts; the first
 * picture loads with high priority and the rest lazily (see "Loading"); and
 * the only files are slider.css and a small deferred slider.js, with no
 * jQuery, carousel library or icon font.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use StudiareExt\Modules\Builder\Assets;
use StudiareExt\Modules\Builder\Elementor\Picture;

defined( 'ABSPATH' ) || exit;

final class Slider extends Base {

	/** Aspect ratio of each design when there is no picture to measure: desktop, phone. */
	private const RATIOS = array(
		'banner' => array( '3 / 1', '2 / 1' ),
		'hero'   => array( '12 / 5', '4 / 5' ),
		'peek'   => array( '16 / 7', '4 / 3' ),
		'cards'  => array( '4 / 3', '4 / 3' ),
		'side'   => array( '2 / 1', '2 / 1' ),
		'tabs'   => array( '3 / 1', '2 / 1' ),
	);

	/** Designs that show one slide at a time, so they can fade. */
	private const SINGLE = array( 'banner', 'hero', 'side', 'tabs' );

	/** Designs that can show the slide text on the picture (hero always does). */
	private const OVERLAY = array( 'banner', 'peek', 'side', 'tabs' );

	/** Choices for "Cards per view". */
	private const PER_VIEW = array( '1', '1.25', '1.5', '2', '2.5', '3', '3.5', '4', '5', '6' );

	/** Fixed shapes offered in "Picture shape", as width-height. */
	private const SHAPES = array( '21-9', '3-1', '5-2', '2-1', '16-9', '3-2', '4-3', '1-1', '4-5', '9-16' );

	private const PAUSE = '<svg class="stx-sl__ico-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7 5h3.5v14H7zm6.5 0H17v14h-3.5z"/></svg>';

	private const PLAY = '<svg class="stx-sl__ico-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M8 5.5v13a1 1 0 0 0 1.5.86l10.2-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>';

	public function get_name(): string {
		return 'stx-slider';
	}

	public function get_title(): string {
		return __( 'Slider', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-slider-push';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'slider', 'carousel', 'banner', 'اسلایدر', 'بنر' ) );
	}

	/** Only the tokens and the slider's own files: no builder.css or builder.js. */
	public function get_style_depends(): array {
		return array( Assets::SLIDER_HANDLE );
	}

	public function get_script_depends(): array {
		return array( Assets::SLIDER_HANDLE );
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------- */

	protected function register_controls(): void {
		$this->register_design_controls();
		$this->register_slide_controls();
		$this->register_side_controls();
		$this->register_behaviour_controls();
		$this->register_loading_controls();
		$this->register_frame_style_controls();
		$this->register_text_style_controls();
		$this->register_nav_style_controls();
	}

	private function register_design_controls(): void {
		$this->start_content_section( 'section_design', __( 'Design', 'studiare-extensions' ) );

		$this->add_control(
			'design',
			array(
				'label'   => __( 'Design', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'banner',
				'options' => array(
					'banner' => __( 'Banner', 'studiare-extensions' ),
					'hero'   => __( 'Hero with text', 'studiare-extensions' ),
					'peek'   => __( 'Centre with side peeks', 'studiare-extensions' ),
					'cards'  => __( 'Cards (several at once)', 'studiare-extensions' ),
					'side'   => __( 'With side banners', 'studiare-extensions' ),
					'tabs'   => __( 'With title tabs', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'design_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Banner: one picture at a time. Hero: full-width picture with a title and buttons on it. Centre: the current slide in the middle with the next ones peeking. Cards: several small banners side by side. Side banners: the slider with two fixed banners beside it. Title tabs: the slide titles as tabs under the slider.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	private function register_slide_controls(): void {
		$this->start_content_section( 'section_slides', __( 'Slides', 'studiare-extensions' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'image',
			array(
				'label'   => __( 'Picture', 'studiare-extensions' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => Utils::get_placeholder_image_src() ),
			)
		);
		$repeater->add_control(
			'image_mobile',
			array(
				'label'       => __( 'Phone picture (optional)', 'studiare-extensions' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => __( 'A taller crop for phones. Phones download only this one.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'studiare-extensions' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => '',
				'separator'   => 'before',
				'description' => __( 'Shown on the picture (hero design, or "Text on pictures"), under it (cards) or as the tab (title tabs). Put words between <mark> and </mark> to highlight them.', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'badge',
			array(
				'label'       => __( 'Small label', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => '',
			)
		);
		foreach ( self::button_suffixes() as $suffix => $label ) {
			$repeater->add_control(
				'button' . $suffix . '_text',
				array(
					'label'     => $label,
					'type'      => Controls_Manager::TEXT,
					'default'   => '',
					'separator' => 'before',
				)
			);
			$repeater->add_control(
				'button' . $suffix . '_link',
				array(
					'label'   => __( 'Button link', 'studiare-extensions' ),
					'type'    => Controls_Manager::URL,
					'dynamic' => array( 'active' => true ),
				)
			);
		}

		$sample = static function ( string $title ): array {
			return array(
				'image' => array( 'url' => Utils::get_placeholder_image_src() ),
				'title' => $title,
			);
		};

		$this->add_control(
			'slides',
			array(
				'label'       => __( 'Slides', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					$sample( __( 'New courses', 'studiare-extensions' ) ),
					$sample( __( 'Special offer', 'studiare-extensions' ) ),
					$sample( __( 'Free workshops', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ title || "' . esc_js( __( 'Slide', 'studiare-extensions' ) ) . '" }}}',
			)
		);

		$this->add_control(
			'size_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Use pictures of the same size, at the size they are shown (for example 1920×600 for a full-width banner), as WebP or compressed JPEG. WordPress makes the smaller copies phones download.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	private function register_side_controls(): void {
		$this->start_content_section(
			'section_side',
			__( 'Side banners', 'studiare-extensions' ),
			array( 'condition' => array( 'design' => 'side' ) )
		);

		foreach ( array( 1, 2 ) as $n ) {
			$this->add_control(
				'side_' . $n . '_image',
				array(
					/* translators: %s: banner number. */
					'label'     => sprintf( __( 'Banner %s', 'studiare-extensions' ), self::num( $n ) ),
					'type'      => Controls_Manager::MEDIA,
					'default'   => array( 'url' => Utils::get_placeholder_image_src() ),
					'separator' => 2 === $n ? 'before' : 'default',
				)
			);
			$this->add_control(
				'side_' . $n . '_link',
				array(
					'label'   => __( 'Link', 'studiare-extensions' ),
					'type'    => Controls_Manager::URL,
					'dynamic' => array( 'active' => true ),
				)
			);
		}

		$this->add_control(
			'side_position',
			array(
				'label'     => __( 'Banners side', 'studiare-extensions' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'end',
				'separator' => 'before',
				'options'   => array(
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
			'side_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'On computers the banners are cropped to fill the slider\'s height; on tablets and phones they sit side by side under it.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	private function register_behaviour_controls(): void {
		$this->start_content_section( 'section_behaviour', __( 'Behaviour', 'studiare-extensions' ) );

		$this->add_control(
			'effect',
			array(
				'label'     => __( 'Change effect', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'slide',
				'options'   => array(
					'slide' => __( 'Slide', 'studiare-extensions' ),
					'fade'  => __( 'Fade', 'studiare-extensions' ),
				),
				'condition' => array( 'design' => self::SINGLE ),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'   => __( 'Change slides automatically', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'interval',
			array(
				'label'     => __( 'Seconds per slide', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5,
				'min'       => 3,
				'max'       => 20,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'pause_button',
			array(
				'label'       => __( 'Pause button', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Lets visitors stop the motion (an accessibility requirement for moving content).', 'studiare-extensions' ),
				'condition'   => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'arrows',
			array(
				'label'     => __( 'Arrows', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'hover',
				'separator' => 'before',
				'options'   => array(
					'hover'  => __( 'On mouse hover (hidden on touch screens)', 'studiare-extensions' ),
					'always' => __( 'Always', 'studiare-extensions' ),
					'none'   => __( 'None', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'dots',
			array(
				'label'     => __( 'Dots', 'studiare-extensions' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'design!' => array( 'cards', 'tabs' ) ),
			)
		);

		$this->add_control(
			'overlay',
			array(
				'label'       => __( 'Text on pictures', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Shows each slide\'s label, title, text and buttons on its picture. Leave off for banners that already have their text in the picture.', 'studiare-extensions' ),
				'condition'   => array( 'design' => self::OVERLAY ),
			)
		);

		$this->add_control(
			'first_h1',
			array(
				'label'       => __( 'First title is the page\'s main heading (H1)', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Turn on only if the page has no other H1.', 'studiare-extensions' ),
				'conditions'  => $this->text_conditions(),
			)
		);

		$this->end_controls_section();
	}

	private function register_loading_controls(): void {
		$this->start_content_section( 'section_loading', __( 'Loading (speed)', 'studiare-extensions' ) );

		$this->add_control(
			'loading',
			array(
				'label'   => __( 'Picture loading', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'smart',
				'options' => array(
					'smart' => __( 'Top of the page: first picture at once, the rest lazily (recommended)', 'studiare-extensions' ),
					'lazy'  => __( 'Lower on the page: every picture lazily', 'studiare-extensions' ),
					'eager' => __( 'No lazy loading: every picture at once', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'loading_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'For a slider at the top of the page, keep the first option: its first picture is usually what PageSpeed measures as the Largest Contentful Paint, so it loads first with high priority, and caching plugins are told not to lazy-load it. Lazy pictures of the next slides are fetched quietly after the page has loaded. "No lazy loading" makes the page heavier.', 'studiare-extensions' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	private function register_frame_style_controls(): void {
		$this->start_style_section( 'section_style_frame', __( 'Slider', 'studiare-extensions' ) );

		$options    = array( 'auto' => __( 'Same as the pictures', 'studiare-extensions' ) );
		$dictionary = array( 'auto' => 'var(--stx-sl-auto)' );
		foreach ( self::SHAPES as $shape ) {
			$options[ $shape ]    = self::digits( str_replace( '-', ':', $shape ) );
			$dictionary[ $shape ] = str_replace( '-', ' / ', $shape );
		}

		$this->add_responsive_control(
			'ratio',
			array(
				'label'                => __( 'Picture shape', 'studiare-extensions' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'auto',
				'options'              => $options,
				'selectors_dictionary' => $dictionary,
				'selectors'            => array( '{{WRAPPER}} .stx-sl' => '--stx-sl-ar: {{VALUE}};' ),
				'description'          => __( '"Same as the pictures" reads the size of the first slide\'s picture (and phone picture), so the slider keeps its space while pictures load.', 'studiare-extensions' ),
			)
		);

		$this->add_responsive_control(
			'per_view',
			array(
				'label'          => __( 'Cards per view', 'studiare-extensions' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1.25',
				'options'        => array_combine( self::PER_VIEW, array_map( array( self::class, 'digits' ), self::PER_VIEW ) ),
				// Also read by render() (picture sizes, slides that wait), so Elementor keeps it on the front end.
				'render_type'    => 'template',
				'selectors'      => array( '{{WRAPPER}} .stx-sl' => '--stx-sl-per: {{VALUE}};' ),
				'condition'      => array( 'design' => 'cards' ),
			)
		);

		$this->add_responsive_control(
			'peek_width',
			array(
				'label'          => __( 'Slide width', 'studiare-extensions' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( '%' ),
				'range'          => array(
					'%' => array(
						'min' => 50,
						'max' => 95,
					),
				),
				'default'        => array(
					'unit' => '%',
					'size' => 76,
				),
				'mobile_default' => array(
					'unit' => '%',
					'size' => 80,
				),
				'render_type'    => 'template',
				'selectors'      => array( '{{WRAPPER}} .stx-sl' => '--stx-sl-w: {{SIZE}}%;' ),
				'condition'      => array( 'design' => 'peek' ),
			)
		);

		$this->add_control(
			'side_width',
			array(
				'label'       => __( 'Side banners width', 'studiare-extensions' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array(
					'%' => array(
						'min' => 20,
						'max' => 45,
					),
				),
				'default'     => array(
					'unit' => '%',
					'size' => 32,
				),
				'render_type' => 'template',
				'selectors'   => array( '{{WRAPPER}} .stx-sl' => '--stx-sl-side: {{SIZE}}%;' ),
				'condition'   => array( 'design' => 'side' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Space between', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-sl' => '--stx-sl-gap: {{SIZE}}px;' ),
				'condition'  => array( 'design' => array( 'peek', 'cards', 'side' ) ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => __( 'Border radius', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-sl' => '--stx-sl-radius: {{SIZE}}px;' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_text_style_controls(): void {
		$this->start_style_section(
			'section_style_text',
			__( 'Text', 'studiare-extensions' ),
			array(
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						$this->text_conditions(),
						array(
							'name'     => 'design',
							'operator' => '===',
							'value'    => 'cards',
						),
					),
				),
			)
		);

		$this->add_control(
			'tone',
			array(
				'label'      => __( 'Text colour on pictures', 'studiare-extensions' ),
				'type'       => Controls_Manager::SELECT,
				'default'    => 'light',
				'options'    => array(
					'light' => __( 'Light, on a dark shade', 'studiare-extensions' ),
					'dark'  => __( 'Dark, on a light shade', 'studiare-extensions' ),
				),
				'conditions' => $this->text_conditions(),
			)
		);

		$this->add_control(
			'text_position',
			array(
				'label'      => __( 'Text position', 'studiare-extensions' ),
				'type'       => Controls_Manager::CHOOSE,
				'default'    => 'start',
				'options'    => array(
					'start'  => array(
						'title' => __( 'Start', 'studiare-extensions' ),
						'icon'  => 'eicon-h-align-' . self::start_icon(),
					),
					'center' => array(
						'title' => __( 'Center', 'studiare-extensions' ),
						'icon'  => 'eicon-h-align-center',
					),
					'end'    => array(
						'title' => __( 'End', 'studiare-extensions' ),
						'icon'  => 'eicon-h-align-' . self::end_icon(),
					),
				),
				'conditions' => $this->text_conditions(),
			)
		);

		$this->add_text_style( 'title', '.stx-sl__title, .stx-sl__cap-title', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'text', '.stx-sl__text, .stx-sl__cap-text', array( 'label' => __( 'Text', 'studiare-extensions' ) ) );

		$this->end_controls_section();
	}

	private function register_nav_style_controls(): void {
		$this->start_style_section( 'section_style_nav', __( 'Arrows, dots and tabs', 'studiare-extensions' ) );

		$colors = array(
			'nav_accent'   => array( __( 'Current slide colour', 'studiare-extensions' ), '--stx-sl-accent' ),
			'nav_dot'      => array( __( 'Dot colour', 'studiare-extensions' ), '--stx-sl-dot' ),
			'nav_arrow_bg' => array( __( 'Arrow background', 'studiare-extensions' ), '--stx-sl-arrow-bg' ),
			'nav_arrow'    => array( __( 'Arrow colour', 'studiare-extensions' ), '--stx-sl-arrow-ink' ),
		);
		foreach ( $colors as $name => $spec ) {
			$this->add_control(
				$name,
				array(
					'label'     => $spec[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .stx-sl' => $spec[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->end_controls_section();
	}

	/** Conditions under which the slide text shows on the pictures. */
	private function text_conditions(): array {
		return array(
			'relation' => 'or',
			'terms'    => array(
				array(
					'name'     => 'design',
					'operator' => '===',
					'value'    => 'hero',
				),
				array(
					'relation' => 'and',
					'terms'    => array(
						array(
							'name'     => 'overlay',
							'operator' => '===',
							'value'    => 'yes',
						),
						array(
							'name'     => 'design',
							'operator' => 'in',
							'value'    => self::OVERLAY,
						),
					),
				),
			),
		);
	}

	/** Button setting suffix → label. */
	private static function button_suffixes(): array {
		return array(
			''  => __( 'Button', 'studiare-extensions' ),
			'2' => __( 'Second button', 'studiare-extensions' ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------- */

	protected function render(): void {
		// Like get_settings_for_display(), but keeps settings whose conditions fail (such as
		// `per_view` outside the cards design); each one is only read where it applies.
		$s      = $this->get_parsed_dynamic_settings();
		$design = isset( self::RATIOS[ $s['design'] ] ) ? $s['design'] : 'banner';
		$slides = array_values( array_filter( (array) $s['slides'], array( self::class, 'has_content' ) ) );
		$count  = count( $slides );

		if ( ! $count ) {
			$this->editor_hint( __( 'Add a slide with a picture.', 'studiare-extensions' ) );
			return;
		}

		$interval = 'yes' === $s['autoplay'] && $count > 1 ? min( 20, max( 3, (int) $s['interval'] ) ) * 1000 : 0;
		$base_id  = 'stx-sl-' . $this->get_id();
		$sizes    = $this->sizes( $design, $s );
		$loading  = in_array( $s['loading'], array( 'smart', 'lazy', 'eager' ), true ) ? $s['loading'] : 'smart';
		$ctx      = array(
			'design'  => $design,
			'count'   => $count,
			'base_id' => $base_id,
			'loading' => $loading,
			'later'   => 'eager' === $loading ? $count : $this->visible_count( $design, $s ),
			'text'    => 'hero' === $design || ( 'yes' === $s['overlay'] && in_array( $design, self::OVERLAY, true ) ),
			'h1'      => 'yes' === $s['first_h1'],
			'sizes'   => $sizes,
		);

		printf(
			'<div class="%1$s" data-stx-slider data-effect="%2$s" data-interval="%3$d" style="%4$s">',
			esc_attr( $this->root_classes( $design, $s ) ),
			esc_attr( 'fade' === $s['effect'] && in_array( $design, self::SINGLE, true ) ? 'fade' : 'slide' ),
			(int) $interval,
			esc_attr( $this->root_style( $design, $slides[0], $s, $interval ) )
		);

		printf(
			'<div class="stx-sl__main" role="region" aria-roledescription="%1$s" aria-label="%2$s">',
			esc_attr__( 'carousel', 'studiare-extensions' ),
			esc_attr__( 'Slider', 'studiare-extensions' )
		);
		echo '<div class="stx-sl__viewport">';
		printf( '<div class="stx-sl__track" id="%1$s-track" aria-live="%2$s">', esc_attr( $base_id ), $interval ? 'off' : 'polite' );
		foreach ( $slides as $index => $slide ) {
			$this->render_slide( $slide, $index, $ctx );
		}
		echo '</div>';

		if ( $count > 1 && 'none' !== $s['arrows'] ) {
			$this->render_arrows( $base_id );
		}
		echo '</div>';

		$this->render_nav( $slides, $ctx, $s, $interval > 0 );
		echo '</div>';

		if ( 'side' === $design ) {
			$this->render_side( $s, $ctx );
		}

		echo '</div>';
	}

	/**
	 * A slide is shown when it has a picture or some text.
	 *
	 * @param mixed $slide Slide settings.
	 */
	private static function has_content( $slide ): bool {
		return is_array( $slide ) && ( ! empty( $slide['image']['url'] ) || '' !== trim( (string) ( $slide['title'] ?? '' ) ) );
	}

	/**
	 * @param string $design Design.
	 * @param array  $s      Settings.
	 */
	private function root_classes( string $design, array $s ): string {
		$classes = array(
			'stx-sl',
			'stx-sl--' . $design,
			'stx-sl--arrows-' . ( 'always' === $s['arrows'] ? 'always' : 'hover' ),
			'stx-sl--text-' . ( in_array( $s['text_position'], array( 'center', 'end' ), true ) ? $s['text_position'] : 'start' ),
			'stx-sl--tone-' . ( 'dark' === $s['tone'] ? 'dark' : 'light' ),
		);

		if ( 'side' === $design && 'start' === $s['side_position'] ) {
			$classes[] = 'stx-sl--side-start';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Custom properties measured from the pictures, so the slider reserves
	 * exactly the right space before anything loads (no layout shift).
	 *
	 * @param string $design   Design.
	 * @param array  $first    First slide.
	 * @param array  $s        Settings.
	 * @param int    $interval Autoplay interval (ms).
	 */
	private function root_style( string $design, array $first, array $s, int $interval ): string {
		$desktop = Picture::ratio( (array) ( $first['image'] ?? array() ) );
		$phone   = Picture::ratio( (array) ( $first['image_mobile'] ?? array() ) );

		// A hero without a phone picture crops the wide picture to a tall one, leaving room for its text.
		if ( '' === $phone ) {
			$phone = 'hero' === $design || '' === $desktop ? self::RATIOS[ $design ][1] : $desktop;
		}

		$vars = array(
			'--stx-sl-auto-d:' . ( '' !== $desktop ? $desktop : self::RATIOS[ $design ][0] ),
			'--stx-sl-auto-m:' . $phone,
		);

		if ( $interval ) {
			$vars[] = '--stx-sl-interval:' . $interval . 'ms';
		}

		if ( 'side' === $design ) {
			$tile   = Picture::ratio( (array) $s['side_1_image'] );
			$vars[] = '--stx-sl-tile-ar:' . ( '' !== $tile ? $tile : '2 / 1' );
		}

		return implode( ';', $vars );
	}

	/**
	 * `sizes` for the pictures, from the design and the layout settings, so
	 * browsers download the smallest copy that is still sharp.
	 *
	 * @param string $design Design.
	 * @param array  $s      Settings.
	 * @return array{img:string, phone:string, tile:string}
	 */
	private function sizes( string $design, array $s ): array {
		$site = self::container_width();
		$per  = static function ( string $value, float $fallback ): float {
			return (float) $value > 0 ? (float) $value : $fallback;
		};

		$per_d  = $per( (string) $s['per_view'], 3 );
		$per_t  = $per( (string) ( $s['per_view_tablet'] ?? '' ), min( 2, $per_d ) );
		$per_m  = $per( (string) ( $s['per_view_mobile'] ?? '' ), 1.25 );
		$peek_d = (int) ( $s['peek_width']['size'] ?? 76 );
		$peek_m = (int) ( $s['peek_width_mobile']['size'] ?? 80 );
		$side   = (int) ( $s['side_width']['size'] ?? 32 );

		switch ( $design ) {
			case 'hero':
				$phone   = 100;
				$desktop = '100vw';
				break;
			case 'peek':
				$phone   = $peek_m ? $peek_m : 80;
				$desktop = sprintf( '(max-width: %1$dpx) %2$dvw, %3$dpx', $site, $peek_d, $site * $peek_d / 100 );
				break;
			case 'cards':
				$phone   = (int) ceil( 100 / $per_m );
				$desktop = sprintf( '(max-width: 1024px) %1$dvw, %2$dpx', ceil( 100 / $per_t ), ceil( $site / $per_d ) );
				break;
			case 'side':
				$phone   = 100;
				$desktop = sprintf( '(max-width: 1024px) 100vw, %dpx', $site * ( 100 - $side ) / 100 );
				break;
			default:
				$phone   = 100;
				$desktop = sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $site );
		}

		return array(
			'img'   => sprintf( '(max-width: %1$dpx) %2$dvw, %3$s', Picture::PHONE_MAX, $phone, $desktop ),
			'phone' => $phone . 'vw',
			'tile'  => sprintf( '(max-width: 1024px) 50vw, %dpx', $site * $side / 100 ),
		);
	}

	/** Elementor's content width (Site Settings → Layout), which boxed containers use. */
	private static function container_width(): int {
		$kit   = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
		$width = $kit ? $kit->get_settings_for_display( 'container_width' ) : null;

		return ! empty( $width['size'] ) ? (int) $width['size'] : 1140;
	}

	/**
	 * Most slides on screen at once, on any device. Pictures of the slides
	 * after them wait for the page to load (see `.is-later` in slider.css).
	 *
	 * @param string $design Design.
	 * @param array  $s      Settings.
	 */
	private function visible_count( string $design, array $s ): int {
		if ( 'cards' === $design ) {
			$most = max( (float) $s['per_view'], (float) ( $s['per_view_tablet'] ?? 0 ), (float) ( $s['per_view_mobile'] ?? 0 ), 1 );

			return (int) ceil( $most );
		}

		// The next slide peeks in beside the first.
		return 'peek' === $design ? 2 : 1;
	}

	/**
	 * How a slide's picture loads.
	 *
	 * @param string $mode  smart|lazy|eager.
	 * @param int    $index Slide position.
	 */
	private static function loading_for( string $mode, int $index ): string {
		if ( 'smart' === $mode ) {
			return 0 === $index ? 'high' : 'lazy';
		}

		return $mode;
	}

	/**
	 * @param array $slide Slide settings.
	 * @param int   $index Position.
	 * @param array $ctx   Render context.
	 */
	private function render_slide( array $slide, int $index, array $ctx ): void {
		$title   = (string) ( $slide['title'] ?? '' );
		$plain   = trim( wp_strip_all_tags( $title ) );
		$cards   = 'cards' === $ctx['design'];
		$caption = $cards ? $this->caption( $slide ) : '';
		$body    = $ctx['text'] ? $this->body( $slide, $index, $ctx ) : '';
		$label   = sprintf(
			/* translators: 1: slide number, 2: number of slides. */
			__( 'Slide %1$s of %2$s', 'studiare-extensions' ),
			self::num( $index + 1 ),
			self::num( $ctx['count'] )
		);

		// The title is the picture's description, unless it is already on screen as text.
		$alt     = '' === $body && '' === $caption ? $plain : '';
		$picture = Picture::html(
			(array) ( $slide['image'] ?? array() ),
			(array) ( $slide['image_mobile'] ?? array() ),
			array(
				'loading'     => self::loading_for( $ctx['loading'], $index ),
				'sizes'       => $ctx['sizes']['img'],
				'phone_sizes' => $ctx['sizes']['phone'],
				'alt'         => $alt,
				'class'       => 'stx-sl__img',
			)
		);

		$key = 'frame_' . $index;
		$this->add_render_attribute( $key, 'class', 'stx-sl__frame' );
		$tag = 'div';
		if ( ! empty( $slide['link']['url'] ) ) {
			$tag = 'a';
			$this->add_link_attributes( $key, $slide['link'] );
			// Named explicitly: a picture that waits (.is-later) is hidden, so its alt
			// text cannot name the link. A caption names it on its own.
			if ( '' === $caption ) {
				$name = self::alt_of( $picture );
				$this->add_render_attribute( $key, 'aria-label', '' !== $name ? $name : ( '' !== $plain ? $plain : $label ) );
			}
		}

		printf(
			'<div class="stx-sl__slide%1$s%2$s%3$s" id="%4$s" role="group" aria-roledescription="%5$s" aria-label="%6$s">',
			0 === $index ? ' is-active' : '',
			$index >= $ctx['later'] ? ' is-later' : '',
			'' !== $body ? ' has-body' : '',
			esc_attr( $ctx['base_id'] . '-' . $index ),
			esc_attr__( 'slide', 'studiare-extensions' ),
			esc_attr( $label )
		);
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag is a constant, attributes escaped by Elementor, picture/caption/body escaped where built.
		echo '<' . $tag . ' ' . $this->get_render_attribute_string( $key ) . '>' . $picture . $caption . '</' . $tag . '>' . $body;
		echo '</div>';
	}

	/**
	 * The alt text in a picture's markup, or ''.
	 *
	 * @param string $picture Picture markup.
	 */
	private static function alt_of( string $picture ): string {
		return preg_match( '/\salt="([^"]+)"/', $picture, $match ) ? trim( html_entity_decode( $match[1], ENT_QUOTES, 'UTF-8' ) ) : '';
	}

	/**
	 * Title and text under a card's picture.
	 *
	 * @param array $slide Slide settings.
	 */
	private function caption( array $slide ): string {
		$title = trim( (string) ( $slide['title'] ?? '' ) );
		$text  = trim( (string) ( $slide['text'] ?? '' ) );

		if ( '' === $title && '' === $text ) {
			return '';
		}

		$html = '<span class="stx-sl__cap">';
		if ( '' !== $title ) {
			$html .= '<span class="stx-sl__cap-title">' . wp_kses_post( $title ) . '</span>';
		}
		if ( '' !== $text ) {
			$html .= '<span class="stx-sl__cap-text">' . esc_html( $text ) . '</span>';
		}

		return $html . '</span>';
	}

	/**
	 * Label, title, text and buttons shown on the picture.
	 *
	 * @param array $slide Slide settings.
	 * @param int   $index Position.
	 * @param array $ctx   Render context.
	 */
	private function body( array $slide, int $index, array $ctx ): string {
		$html  = '';
		$title = trim( (string) ( $slide['title'] ?? '' ) );

		if ( '' !== trim( (string) $slide['badge'] ) ) {
			$html .= '<span class="stx-sl__badge">' . esc_html( $slide['badge'] ) . '</span>';
		}
		if ( '' !== $title ) {
			$tag   = 0 === $index && $ctx['h1'] ? 'h1' : 'h2';
			$html .= sprintf( '<%1$s class="stx-sl__title">%2$s</%1$s>', $tag, wp_kses_post( nl2br( $title ) ) );
		}
		if ( '' !== trim( (string) $slide['text'] ) ) {
			$html .= '<p class="stx-sl__text">' . wp_kses_post( nl2br( (string) $slide['text'] ) ) . '</p>';
		}

		$buttons = $this->buttons( $slide, $index );
		if ( '' !== $buttons ) {
			$html .= '<div class="stx-sl__actions">' . $buttons . '</div>';
		}

		return '' !== $html ? '<div class="stx-sl__body">' . $html . '</div>' : '';
	}

	/**
	 * @param array $slide Slide settings.
	 * @param int   $index Position.
	 */
	private function buttons( array $slide, int $index ): string {
		$html     = '';
		$variants = array_combine( array_keys( self::button_suffixes() ), array( 'primary', 'secondary' ) );

		foreach ( $variants as $suffix => $variant ) {
			$text = trim( (string) ( $slide[ 'button' . $suffix . '_text' ] ?? '' ) );
			if ( '' === $text ) {
				continue;
			}

			$key = 'button' . $suffix . '_' . $index;
			$this->add_render_attribute( $key, 'class', 'stx-sl__btn stx-sl__btn--' . $variant );
			if ( ! empty( $slide[ 'button' . $suffix . '_link' ]['url'] ) ) {
				$this->add_link_attributes( $key, $slide[ 'button' . $suffix . '_link' ] );
			}
			$html .= '<a ' . $this->get_render_attribute_string( $key ) . '>' . esc_html( $text ) . '</a>';
		}

		return $html;
	}

	/**
	 * @param string $base_id ID prefix.
	 */
	private function render_arrows( string $base_id ): void {
		$arrows = array(
			'prev' => __( 'Previous slide', 'studiare-extensions' ),
			'next' => __( 'Next slide', 'studiare-extensions' ),
		);

		foreach ( $arrows as $dir => $label ) {
			printf(
				'<button type="button" class="stx-sl__arrow stx-sl__arrow--%1$s" data-stx-step="%2$s" aria-controls="%3$s" aria-label="%4$s">%5$s</button>',
				esc_attr( $dir ),
				'prev' === $dir ? '-1' : '1',
				esc_attr( $base_id . '-track' ),
				esc_attr( $label ),
				self::CHEVRON // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG.
			);
		}
	}

	/**
	 * Dots or title tabs, and the pause button.
	 *
	 * @param array $slides   Slides.
	 * @param array $ctx      Render context.
	 * @param array $s        Settings.
	 * @param bool  $autoplay Whether slides change on their own.
	 */
	private function render_nav( array $slides, array $ctx, array $s, bool $autoplay ): void {
		$tabs  = 'tabs' === $ctx['design'];
		$picks = $ctx['count'] > 1 && ( $tabs || ( 'yes' === $s['dots'] && 'cards' !== $ctx['design'] ) );
		$pause = $autoplay && 'yes' === $s['pause_button'];

		if ( ! $picks && ! $pause ) {
			return;
		}

		echo '<div class="stx-sl__nav' . ( $tabs ? ' stx-sl__nav--tabs' : '' ) . '">';

		if ( $picks ) {
			foreach ( $slides as $index => $slide ) {
				$label = sprintf(
					/* translators: 1: slide number, 2: number of slides. */
					__( 'Slide %1$s of %2$s', 'studiare-extensions' ),
					self::num( $index + 1 ),
					self::num( $ctx['count'] )
				);
				$text = $tabs ? trim( wp_strip_all_tags( (string) $slide['title'] ) ) : '';

				printf(
					'<button type="button" class="stx-sl__pick stx-sl__%1$s%2$s" data-stx-go="%3$d" aria-controls="%4$s"%5$s%6$s>%7$s<span class="stx-sl__fill" aria-hidden="true"></span></button>',
					$tabs ? 'tab' : 'dot',
					0 === $index ? ' is-active' : '',
					(int) $index,
					esc_attr( $ctx['base_id'] . '-' . $index ),
					'' === $text ? ' aria-label="' . esc_attr( $label ) . '"' : '',
					0 === $index ? ' aria-current="true"' : '',
					'' !== $text ? '<span class="stx-sl__tab-text">' . esc_html( $text ) . '</span>' : ( $tabs ? '<span class="stx-sl__tab-text">' . esc_html( self::num( $index + 1 ) ) . '</span>' : '' )
				);
			}
		}

		if ( $pause ) {
			printf(
				'<button type="button" class="stx-sl__pause" aria-label="%1$s" data-label-pause="%1$s" data-label-play="%2$s">%3$s%4$s</button>',
				esc_attr__( 'Pause slides', 'studiare-extensions' ),
				esc_attr__( 'Play slides', 'studiare-extensions' ),
				self::PAUSE, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG.
				self::PLAY // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG.
			);
		}

		echo '</div>';
	}

	/**
	 * The two fixed banners of the "side banners" design.
	 *
	 * @param array $s   Settings.
	 * @param array $ctx Render context.
	 */
	private function render_side( array $s, array $ctx ): void {
		echo '<div class="stx-sl__side">';

		foreach ( array( 1, 2 ) as $n ) {
			$picture = Picture::html(
				(array) $s[ 'side_' . $n . '_image' ],
				array(),
				array(
					// Beside the slider, so they are on screen at once, but never the LCP.
					'loading' => 'smart' === $ctx['loading'] ? 'eager' : $ctx['loading'],
					'sizes'   => $ctx['sizes']['tile'],
					'class'   => 'stx-sl__img',
				)
			);

			$key = 'side_' . $n;
			$this->add_render_attribute( $key, 'class', 'stx-sl__tile' );
			$tag = 'div';
			if ( ! empty( $s[ $key . '_link' ]['url'] ) ) {
				$tag = 'a';
				$this->add_link_attributes( $key, $s[ $key . '_link' ] );
				$name = self::alt_of( $picture );
				/* translators: %s: banner number. */
				$this->add_render_attribute( $key, 'aria-label', '' !== $name ? $name : sprintf( __( 'Banner %s', 'studiare-extensions' ), self::num( $n ) ) );
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag is a constant, attributes escaped by Elementor, picture built with escaping.
			echo '<' . $tag . ' ' . $this->get_render_attribute_string( $key ) . '>' . $picture . '</' . $tag . '>';
		}

		echo '</div>';
	}
}
