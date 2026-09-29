<?php
/**
 * Breadcrumb for blog pages: Home › Blog › Category › Post. On a post list
 * it ends with the category, tag, author or search being viewed.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Post_Parts;
use StudiareExt\Modules\Builder\Resolver;

defined( 'ABSPATH' ) || exit;

final class Blog_Breadcrumb extends Blog_Base {

	public function get_name(): string {
		return 'stx-blog-breadcrumb';
	}

	public function get_title(): string {
		return __( 'Blog breadcrumb', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-product-breadcrumbs';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Breadcrumb', 'studiare-extensions' ) );

		$this->add_control(
			'home_label',
			array(
				'label'       => __( 'Home label', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Home', 'studiare-extensions' ),
				'description' => __( 'Leave empty to hide.', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'blog_label',
			array(
				'label'       => __( 'Blog label', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Title of the blog page', 'studiare-extensions' ),
			)
		);

		$this->add_control(
			'show_current',
			array(
				'label'   => __( 'Current page', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'   => __( 'Separator', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '/',
			)
		);

		$this->add_align_control( 'align', '.stx-crumbs' );
		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Breadcrumb', 'studiare-extensions' ) );
		$this->add_text_style( 'link', '.stx-crumbs a', array( 'hover' => '.stx-crumbs a:hover' ) );
		$this->add_text_style( 'current', '.stx-crumbs__current', array( 'label' => __( 'Current item', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$trail   = array();
		$current = '';

		if ( '' !== (string) $s['home_label'] ) {
			$trail[] = array( (string) $s['home_label'], home_url( '/' ) );
		}

		$page       = (int) get_option( 'page_for_posts' );
		$blog_label = '' !== (string) $s['blog_label'] ? (string) $s['blog_label'] : ( $page ? html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ) : __( 'Blog', 'studiare-extensions' ) );
		$post       = is_home() || is_archive() || is_search() ? null : Context::post();

		if ( $post ) {
			$trail[] = array( $blog_label, Resolver::blog_url() );
			$terms   = Post_Parts::terms( $post, 'category' );
			if ( $terms ) {
				$trail[] = array( html_entity_decode( $terms[0]->name, ENT_QUOTES, 'UTF-8' ), (string) get_term_link( $terms[0] ) );
			}
			$current = get_the_title( $post );
		} elseif ( is_home() ) {
			$current = $blog_label;
		} elseif ( is_archive() || is_search() ) {
			$trail[] = array( $blog_label, Resolver::blog_url() );
			$current = Post_Parts::archive( '', '' )['title'];
		} else {
			// Editing a post list template: it shows the blog page.
			$current = $blog_label;
		}

		echo '<nav class="stx-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'studiare-extensions' ) . '"><ol>';
		foreach ( $trail as $crumb ) {
			printf( '<li><a href="%1$s">%2$s</a></li><li class="stx-crumbs__sep" aria-hidden="true">%3$s</li>', esc_url( $crumb[1] ), esc_html( $crumb[0] ), esc_html( $s['separator'] ) );
		}
		if ( 'yes' === $s['show_current'] && '' !== $current ) {
			printf( '<li class="stx-crumbs__current" aria-current="page">%s</li>', esc_html( $current ) );
		}
		echo '</ol></nav>';
	}
}
