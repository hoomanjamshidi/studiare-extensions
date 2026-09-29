<?php
/**
 * Renders single product/course pages with an Elementor template instead of
 * Studiare's layout, keeping the theme header/footer and WooCommerce hooks.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

defined( 'ABSPATH' ) || exit;

final class Single_Product {

	/** @var Module */
	private $module;

	/** @var int Template for this request (0 = theme layout). */
	private $template_id = 0;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp', array( $this, 'setup' ) );
		add_filter( 'template_include', array( $this, 'template_include' ), 99 );
	}

	/** Resolves the template once the main query is known. */
	public function setup(): void {
		if ( is_admin() || ! Renderer::elementor_ready() || ! Context::has_woo() ) {
			return;
		}

		$this->template_id = $this->module->resolver()->single_template();

		if ( ! $this->template_id ) {
			return;
		}

		add_action(
			'wp_enqueue_scripts',
			function () {
				Renderer::enqueue( $this->template_id );
			},
			20
		);

		add_filter( 'body_class', array( $this, 'body_class' ) );

		if ( $this->module->settings()['options']['persian_digits'] ) {
			// Variation prices are printed by WooCommerce's script from this data.
			add_filter( 'woocommerce_available_variation', array( $this, 'variation_digits' ) );
		}

		if ( $this->module->settings()['options']['hide_theme_title'] ) {
			// The template brings its own title and breadcrumb.
			add_filter( 'load_studipage_title', '__return_empty_string', PHP_INT_MAX );
		}

		if ( $this->module->resolver()->is_preview() ) {
			nocache_headers();
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
		}
	}

	/**
	 * @param string $template Template file chosen by WordPress/WooCommerce.
	 */
	public function template_include( $template ) {
		if ( $this->template_id && is_singular( 'product' ) ) {
			return __DIR__ . '/views/single-product.php';
		}

		return $template;
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		$classes[] = 'stx-tpl-page';
		$classes[] = 'stx-tpl-page--' . Template_Post_Type::type_of( $this->template_id );

		return $classes;
	}

	/**
	 * @param array $data Variation data sent to the variation form script.
	 */
	public function variation_digits( $data ) {
		if ( is_array( $data ) && ! empty( $data['price_html'] ) ) {
			$data['price_html'] = Elementor\Widgets\Base::digits_html( (string) $data['price_html'] );
		}

		return $data;
	}

	public function template_id(): int {
		return $this->template_id;
	}
}
