<?php
/**
 * Registry of the ready-made designs.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

defined( 'ABSPATH' ) || exit;

final class Catalog {

	/**
	 * @return array<string, array{type:string, label:string, description:string, build:callable}>
	 */
	public static function all(): array {
		return array(
			'course-classic'    => array(
				'type'        => 'course',
				'label'       => __( 'Course · Classic', 'studiare-extensions' ),
				'description' => __( 'Cover, tabs (overview, curriculum, teacher, reviews) and a sticky buy box.', 'studiare-extensions' ),
				'build'       => array( Course::class, 'classic' ),
			),
			'course-spotlight'  => array(
				'type'        => 'course',
				'label'       => __( 'Course · Spotlight', 'studiare-extensions' ),
				'description' => __( 'Dark hero with the teacher, a buy card with the intro video, sections stacked below.', 'studiare-extensions' ),
				'build'       => array( Course::class, 'spotlight' ),
			),
			'course-landing'    => array(
				'type'        => 'course',
				'label'       => __( 'Course · Landing', 'studiare-extensions' ),
				'description' => __( 'Centred sales page: big title, video, fact tiles, curriculum and a closing call to action.', 'studiare-extensions' ),
				'build'       => array( Course::class, 'landing' ),
			),
			'product-shop'      => array(
				'type'        => 'product',
				'label'       => __( 'Product · Shop', 'studiare-extensions' ),
				'description' => __( 'Gallery and buy area in one card, variation buttons, perks, tabs and similar products.', 'studiare-extensions' ),
				'build'       => array( Product::class, 'shop' ),
			),
			'product-showcase'  => array(
				'type'        => 'product',
				'label'       => __( 'Product · Showcase', 'studiare-extensions' ),
				'description' => __( 'Large gallery with a sticky summary and a section jump bar below.', 'studiare-extensions' ),
				'build'       => array( Product::class, 'showcase' ),
			),
			'product-editorial' => array(
				'type'        => 'product',
				'label'       => __( 'Product · Editorial', 'studiare-extensions' ),
				'description' => __( 'Soft hero band, benefit tiles and details in an accordion.', 'studiare-extensions' ),
				'build'       => array( Product::class, 'editorial' ),
			),
			'header-classic'    => array(
				'type'        => 'header',
				'label'       => __( 'Header · Classic', 'studiare-extensions' ),
				'description' => __( 'Logo, menu, search, cart and login in one row.', 'studiare-extensions' ),
				'build'       => array( Header::class, 'classic' ),
			),
			'header-centered'   => array(
				'type'        => 'header',
				'label'       => __( 'Header · Centred logo', 'studiare-extensions' ),
				'description' => __( 'Logo in the middle, search and account around it, menu below.', 'studiare-extensions' ),
				'build'       => array( Header::class, 'centered' ),
			),
			'header-topbar'     => array(
				'type'        => 'header',
				'label'       => __( 'Header · Top bar', 'studiare-extensions' ),
				'description' => __( 'Contact strip on top, then logo, menu and a call-to-action button.', 'studiare-extensions' ),
				'build'       => array( Header::class, 'topbar' ),
			),
			'header-market'     => array(
				'type'        => 'header',
				'label'       => __( 'Header · Marketplace', 'studiare-extensions' ),
				'description' => __( 'Wide search field, a categories button and the menu — for large catalogues.', 'studiare-extensions' ),
				'build'       => array( Header::class, 'market' ),
			),
			'header-floating'   => array(
				'type'        => 'header',
				'label'       => __( 'Header · Floating', 'studiare-extensions' ),
				'description' => __( 'Rounded floating bar with dark mode switch. Also great as a phone header.', 'studiare-extensions' ),
				'build'       => array( Header::class, 'floating' ),
			),
			'header-dark'       => array(
				'type'        => 'header',
				'label'       => __( 'Header · Dark', 'studiare-extensions' ),
				'description' => __( 'Dark bar with a centred menu and an accent login button.', 'studiare-extensions' ),
				'build'       => array( Header::class, 'dark' ),
			),
			'header-minimal'    => array(
				'type'        => 'header',
				'label'       => __( 'Header · Minimal', 'studiare-extensions' ),
				'description' => __( 'Airy bar: logo, centred menu and icon-only buttons.', 'studiare-extensions' ),
				'build'       => array( Header::class, 'minimal' ),
			),
			'footer-columns'    => array(
				'type'        => 'footer',
				'label'       => __( 'Footer · Columns', 'studiare-extensions' ),
				'description' => __( 'About, two link columns, contact details and trust badges.', 'studiare-extensions' ),
				'build'       => array( Footer::class, 'columns' ),
			),
			'footer-dark'       => array(
				'type'        => 'footer',
				'label'       => __( 'Footer · Dark', 'studiare-extensions' ),
				'description' => __( 'Call-to-action band over a dark footer.', 'studiare-extensions' ),
				'build'       => array( Footer::class, 'dark' ),
			),
			'footer-minimal'    => array(
				'type'        => 'footer',
				'label'       => __( 'Footer · Minimal', 'studiare-extensions' ),
				'description' => __( 'One slim row: logo, links and copyright.', 'studiare-extensions' ),
				'build'       => array( Footer::class, 'minimal' ),
			),
			'footer-centered'   => array(
				'type'        => 'footer',
				'label'       => __( 'Footer · Centred', 'studiare-extensions' ),
				'description' => __( 'Logo, short text, social icons and links, all centred.', 'studiare-extensions' ),
				'build'       => array( Footer::class, 'centered' ),
			),
			'footer-trust'      => array(
				'type'        => 'footer',
				'label'       => __( 'Footer · Trust', 'studiare-extensions' ),
				'description' => __( 'Contact card, links and large e-Namad / Samandehi area.', 'studiare-extensions' ),
				'build'       => array( Footer::class, 'trust' ),
			),
			'footer-split'      => array(
				'type'        => 'footer',
				'label'       => __( 'Footer · Split', 'studiare-extensions' ),
				'description' => __( 'Dark brand panel with social icons and badges beside three link columns.', 'studiare-extensions' ),
				'build'       => array( Footer::class, 'split' ),
			),
			'footer-support'    => array(
				'type'        => 'footer',
				'label'       => __( 'Footer · Support', 'studiare-extensions' ),
				'description' => __( 'Phone, email and address cards on top, then menu and social icons.', 'studiare-extensions' ),
				'build'       => array( Footer::class, 'support' ),
			),
			'footer-soft'       => array(
				'type'        => 'footer',
				'label'       => __( 'Footer · Soft', 'studiare-extensions' ),
				'description' => __( 'Soft accent band, everything centred, with trust badges.', 'studiare-extensions' ),
				'build'       => array( Footer::class, 'soft' ),
			),
			'home-complete'     => array(
				'type'        => 'home',
				'label'       => __( 'Home · Complete', 'studiare-extensions' ),
				'description' => __( 'Slider with side cards, categories, products with category buttons, weekly offer with countdown, learning paths, podcasts, teachers, testimonials, workshops, blog, FAQ and newsletter.', 'studiare-extensions' ),
				'build'       => array( Home::class, 'complete' ),
			),
			'home-studiare'     => array(
				'type'        => 'home',
				'label'       => __( 'Home · Studiare', 'studiare-extensions' ),
				'description' => __( 'Curved accent hero with an intro video, categories, podcasts, products, teachers, blog, workshops and a closing call to action.', 'studiare-extensions' ),
				'build'       => array( Home::class, 'studiare' ),
			),
			'home-shop'         => array(
				'type'        => 'home',
				'label'       => __( 'Home · Bright shop', 'studiare-extensions' ),
				'description' => __( 'Light shop front: hero with a banner, category tiles, best sellers, a bundle offer, perks and the latest posts.', 'studiare-extensions' ),
				'build'       => array( Home::class, 'shop' ),
			),
			'archive-magazine'  => array(
				'type'        => 'archive',
				'label'       => __( 'Blog · Magazine', 'studiare-extensions' ),
				'description' => __( 'Soft title band with category buttons, the newest post highlighted over a card grid, page numbers and a newsletter box.', 'studiare-extensions' ),
				'build'       => array( Blog::class, 'archive_magazine' ),
			),
			'archive-sidebar'   => array(
				'type'        => 'archive',
				'label'       => __( 'Blog · With sidebar', 'studiare-extensions' ),
				'description' => __( 'Posts in rows beside a sticky sidebar: blog search, categories, the most discussed posts and tags.', 'studiare-extensions' ),
				'build'       => array( Blog::class, 'archive_sidebar' ),
			),
			'archive-minimal'   => array(
				'type'        => 'archive',
				'label'       => __( 'Blog · Minimal', 'studiare-extensions' ),
				'description' => __( 'Airy white page: centred title and categories over editorial cards without boxes.', 'studiare-extensions' ),
				'build'       => array( Blog::class, 'archive_minimal' ),
			),
			'post-classic'      => array(
				'type'        => 'post',
				'label'       => __( 'Post · Classic', 'studiare-extensions' ),
				'description' => __( 'The post in a white card beside a sticky sidebar with the table of contents, latest posts and newsletter.', 'studiare-extensions' ),
				'build'       => array( Blog::class, 'post_classic' ),
			),
			'post-focus'        => array(
				'type'        => 'post',
				'label'       => __( 'Post · Focus', 'studiare-extensions' ),
				'description' => __( 'One narrow, centred reading column with a wide picture, large text and share buttons with names.', 'studiare-extensions' ),
				'build'       => array( Blog::class, 'post_focus' ),
			),
			'post-cover'        => array(
				'type'        => 'post',
				'label'       => __( 'Post · Cover', 'studiare-extensions' ),
				'description' => __( 'Dark title band beside the picture, then the text between a share rail and a sticky table of contents.', 'studiare-extensions' ),
				'build'       => array( Blog::class, 'post_cover' ),
			),
			'about-story'       => array(
				'type'        => 'about',
				'label'       => __( 'About · Story', 'studiare-extensions' ),
				'description' => __( 'Hero with a team photo, number tiles that count up, a timeline, values, the team, reviews and a call to action.', 'studiare-extensions' ),
				'build'       => array( About::class, 'story' ),
			),
			'about-minimal'     => array(
				'type'        => 'about',
				'label'       => __( 'About · Minimal', 'studiare-extensions' ),
				'description' => __( 'Centred title, a wide photo, large numbers, what you believe, milestones and questions.', 'studiare-extensions' ),
				'build'       => array( About::class, 'minimal' ),
			),
			'about-academy'     => array(
				'type'        => 'about',
				'label'       => __( 'About · Academy', 'studiare-extensions' ),
				'description' => __( 'Curved accent hero with a video, a dark band of large numbers, six reasons, milestones in a row and the teachers.', 'studiare-extensions' ),
				'build'       => array( About::class, 'academy' ),
			),
			'contact-cards'     => array(
				'type'        => 'contact',
				'label'       => __( 'Contact · Cards', 'studiare-extensions' ),
				'description' => __( 'Four contact cards, the form beside messenger buttons, a map with directions and common questions.', 'studiare-extensions' ),
				'build'       => array( Contact::class, 'cards' ),
			),
			'contact-split'     => array(
				'type'        => 'contact',
				'label'       => __( 'Contact · Split', 'studiare-extensions' ),
				'description' => __( 'A dark panel with every way to reach you beside the form, then a map that loads on request.', 'studiare-extensions' ),
				'build'       => array( Contact::class, 'split' ),
			),
			'contact-support'   => array(
				'type'        => 'contact',
				'label'       => __( 'Contact · Support centre', 'studiare-extensions' ),
				'description' => __( 'Accent hero with messenger buttons, one card per team, questions beside the form, and a map.', 'studiare-extensions' ),
				'build'       => array( Contact::class, 'support' ),
			),
		);
	}

	/** @return string[] */
	public static function keys(): array {
		return array_keys( self::all() );
	}

	/**
	 * @param string $key Preset key.
	 */
	public static function get( string $key ): ?array {
		return self::all()[ $key ] ?? null;
	}

	/**
	 * Elementor data for a preset.
	 *
	 * @param string $key Preset key.
	 */
	public static function build( string $key ): array {
		$preset = self::get( $key );

		return $preset ? El::finalize( call_user_func( $preset['build'] ) ) : array();
	}
}
