<?php
/**
 * Elementor integration: widget category and widgets, extra container
 * controls (brand surfaces, sticky) and WooCommerce cart fragments.
 *
 * Widgets are classic (v3) Elementor widgets and layouts use Flexbox
 * Containers, which work from Elementor 3.16 through 4.x.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Assets;
use StudiareExt\Modules\Builder\Module;

defined( 'ABSPATH' ) || exit;

final class Integration {

	public const CATEGORY = 'studiare-plus';

	/** Widget classes (short names inside Elementor\Widgets). */
	private const WIDGETS = array(
		// Basics.
		'Heading',
		'Text',
		'Button',
		'Icon_List',
		// Product & course.
		'Product_Title',
		'Product_Breadcrumb',
		'Product_Badges',
		'Product_Excerpt',
		'Product_Price',
		'Add_To_Cart',
		'Product_Gallery',
		'Product_Info',
		'Product_Rating',
		'Product_Stock',
		'Product_Content',
		'Product_Highlights',
		'Course_Curriculum',
		'Course_Teacher',
		'Product_Attributes',
		'Product_Reviews',
		'Product_Tabs',
		'Related_Products',
		'Mobile_Buy_Bar',
		// Header & footer.
		'Site_Logo',
		'Nav_Menu',
		'Search',
		'Cart',
		'Account',
		'Dark_Toggle',
		'Copyright',
		'Trust_Badges',
		// Home pages.
		'Slides',
		'Slider',
		'Promo_Card',
		'Product_Grid',
		'Category_Grid',
		'Post_Grid',
		'Features',
		'Countdown',
		'Offer_Price',
		'Testimonials',
		'People',
		'Events',
		'Faq',
		'Stats',
		'Media',
		'Newsletter_Form',
		'Product_Spotlight',
		'Logo_Strip',
		'Pricing_Plans',
		// Blog.
		'Archive_Title',
		'Blog_Breadcrumb',
		'Post_Categories',
		'Post_Title',
		'Post_Meta',
		'Post_Image',
		'Post_Content',
		'Post_Toc',
		'Post_Share',
		'Post_Tags',
		'Post_Author',
		'Post_Nav',
		'Post_Comments',
		'Reading_Progress',
		// About & contact.
		'Contact_Form',
		'Contact_Details',
		'Map',
		'Timeline',
	);

	/** @var Module|null */
	private static $module = null;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		self::$module = $module;
	}

	/** Module instance, for widgets that read settings (icon pack, digits…). */
	public static function module(): ?Module {
		return self::$module;
	}

	public function register(): void {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/element/container/section_layout_additional_options/after_section_end', array( $this, 'container_controls' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'preview_styles' ) );
		add_filter( 'woocommerce_add_to_cart_fragments', array( Widgets\Cart::class, 'fragments' ) );
		add_filter( 'wp_content_img_tag', array( Picture::class, 'clean_img_tag' ), 20 );
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 */
	public function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Studiare+', 'studiare-extensions' ),
				'icon'  => 'eicon-apps',
			)
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public function register_widgets( $widgets_manager ): void {
		foreach ( self::WIDGETS as $name ) {
			$class = __NAMESPACE__ . '\\Widgets\\' . $name;
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}

	/** Container surfaces and sticky helpers are styled by the module stylesheets. */
	public function preview_styles(): void {
		wp_enqueue_style( Assets::HANDLE );
		wp_enqueue_style( Assets::HOME_HANDLE );
		wp_enqueue_style( Assets::BLOG_HANDLE );
		wp_enqueue_style( Assets::PAGES_HANDLE );
	}

	/**
	 * "Studiare+" section in the container's Layout tab.
	 *
	 * @param \Elementor\Element_Base $element Container.
	 */
	public function container_controls( $element ): void {
		$element->start_controls_section(
			'stx_section',
			array(
				'label' => __( 'Studiare+', 'studiare-extensions' ),
				'tab'   => Controls_Manager::TAB_LAYOUT,
			)
		);

		$element->add_control(
			'stx_surface',
			array(
				'label'        => __( 'Brand surface', 'studiare-extensions' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => '',
				'options'      => array(
					''            => __( 'None', 'studiare-extensions' ),
					'card'        => __( 'Card (with border)', 'studiare-extensions' ),
					'plain'       => __( 'Card (no border)', 'studiare-extensions' ),
					'soft'        => __( 'Soft background', 'studiare-extensions' ),
					'page'        => __( 'Page background', 'studiare-extensions' ),
					'dark'        => __( 'Dark', 'studiare-extensions' ),
					'accent'      => __( 'Accent', 'studiare-extensions' ),
					'gradient'    => __( 'Accent gradient', 'studiare-extensions' ),
					'accent-soft' => __( 'Accent (light)', 'studiare-extensions' ),
				),
				'prefix_class' => 'stx-surface-',
				'description'  => __( 'Uses the brand colours from Studiare+ → Page templates, including dark mode. A background set in the Style tab still wins.', 'studiare-extensions' ),
			)
		);

		$element->add_control(
			'stx_sticky',
			array(
				'label'        => __( 'Sticky while scrolling', 'studiare-extensions' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => '',
				'options'      => array(
					''        => __( 'Off', 'studiare-extensions' ),
					'desktop' => __( 'Desktop only', 'studiare-extensions' ),
					'all'     => __( 'All devices', 'studiare-extensions' ),
				),
				'prefix_class' => 'stx-sticky-',
				'separator'    => 'before',
				'description'  => __( 'Keeps a column (e.g. the buy box) in view inside its parent. The parent must be taller than this container.', 'studiare-extensions' ),
			)
		);

		$element->add_control(
			'stx_sticky_offset',
			array(
				'label'      => __( 'Distance from top', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array(
					'{{WRAPPER}}' => '--stx-sticky-offset: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'stx_sticky!' => '' ),
			)
		);

		$element->end_controls_section();
	}
}
