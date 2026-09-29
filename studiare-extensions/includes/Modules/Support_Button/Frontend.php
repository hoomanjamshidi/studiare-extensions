<?php
/**
 * Front-end integration of the support button: assets, visibility rules and
 * the view model printed in the footer.
 *
 * Device rules are CSS media queries (no `wp_is_mobile()`), so the button is
 * safe with full-page caching. It rises above the bottom navigation, sticky
 * buy bars and the theme's "back to top" button through CSS alone.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Support_Button;

use StudiareExt\Core\Asset;
use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;

final class Frontend {

	private const HANDLE = 'stx-support-button';

	/** @var Module */
	private $module;

	/** @var array<int, array>|null Memoized channel view models. */
	private $channels = null;

	/** @var bool|null Memoized render decision. */
	private $should_render = null;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render' ), 25 );
	}

	public function enqueue_assets(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_enqueue_style( self::HANDLE, Asset::url( 'assets/modules/support-button/css/support-button.css' ), array(), STUDIARE_EXT_VERSION );

		// A single channel is a plain link; the script only serves the menu and the greeting.
		if ( ! $this->is_single() || $this->settings()['greeting']['enabled'] ) {
			wp_enqueue_script(
				self::HANDLE,
				Asset::url( 'assets/modules/support-button/js/support-button.js' ),
				array(),
				STUDIARE_EXT_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}

	public function render(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		$view = $this->view();
		require __DIR__ . '/views/button.php';
	}

	/**
	 * Decides once per request whether the button is shown. Filterable
	 * through `studiare_ext_support_button_should_render`.
	 */
	private function should_render(): bool {
		if ( null === $this->should_render ) {
			$this->should_render = (bool) apply_filters(
				'studiare_ext_support_button_should_render',
				$this->passes_visibility_rules() && array() !== $this->channels()
			);
		}

		return $this->should_render;
	}

	private function passes_visibility_rules(): bool {
		if ( is_admin() || is_feed() || is_embed() || wp_doing_ajax() || $this->is_page_builder_preview() ) {
			return false;
		}

		$rules = $this->settings()['display'];

		// Buyers finishing an order should not be pulled into a chat.
		if ( $rules['hide_on_checkout'] && function_exists( 'is_checkout' ) && is_checkout() ) {
			return false;
		}

		if ( $rules['hide_on_cart'] && function_exists( 'is_cart' ) && is_cart() ) {
			return false;
		}

		if ( '' !== $rules['hide_for_ids'] && is_singular() ) {
			$hidden_ids = array_map( 'intval', explode( ',', $rules['hide_for_ids'] ) );
			if ( in_array( (int) get_queried_object_id(), $hidden_ids, true ) ) {
				return false;
			}
		}

		return true;
	}

	/** The button would sit on top of the editing canvas inside Elementor's preview. */
	private function is_page_builder_preview(): bool {
		return class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance->preview )
			&& \Elementor\Plugin::$instance->preview->is_preview_mode();
	}

	/**
	 * Switched-on channels that resolve to a link, in the saved order.
	 *
	 * @return array<int, array> See Channels::ready().
	 */
	private function channels(): array {
		if ( null !== $this->channels ) {
			return $this->channels;
		}

		$this->channels = Channels::ready( $this->settings() );

		return $this->channels;
	}

	/** With one channel the button links straight to it instead of opening a menu. */
	private function is_single(): bool {
		return 1 === count( $this->channels() );
	}

	/** Everything views/button.php prints. */
	private function view(): array {
		$settings = $this->settings();
		$button   = $settings['button'];
		$single   = $this->is_single();

		$classes = array(
			'stx-sb',
			'stx-sb--' . $settings['design'],
			'stx-sb--' . $settings['position']['side'],
			'stx-sb--label-' . ( '' === $button['label'] ? 'never' : $button['label_mode'] ),
		);
		if ( 'all' !== $settings['display']['devices'] ) {
			$classes[] = 'stx-sb--' . $settings['display']['devices'] . '-only';
		}
		if ( $button['pulse'] ) {
			$classes[] = 'stx-sb--pulse';
		}

		return array(
			'classes'  => implode( ' ', $classes ),
			'style'    => $this->style_vars(),
			'single'   => $single,
			'channels' => $this->channels(),
			// A single channel shows its own glyph and name, so the button says where it goes.
			'label'    => $single ? $this->channels()[0]['label'] : ( '' !== $button['label'] ? $button['label'] : __( 'Support', 'studiare-extensions' ) ),
			'icon'     => $single ? $this->channels()[0]['glyph'] : Icon_Library::svg( 'phosphor', $button['icon'], true ),
			// A lone WhatsApp button is WhatsApp green, unless the admin chose a button colour.
			'brand'    => $single && '' === $settings['colors']['button_bg'] ? $this->channels()[0]['brand'] : '',
			'close'    => Icon_Library::svg( 'phosphor', 'close' ),
			'header'   => 'card' === $settings['design'] ? $settings['header'] : array(
				'title'    => '',
				'subtitle' => '',
			),
			'greeting' => $settings['greeting']['enabled'] && '' !== $settings['greeting']['text'] ? $settings['greeting'] : null,
		);
	}

	/** Size, position and colour tokens, as an inline style on the wrapper. */
	private function style_vars(): string {
		$settings = $this->settings();

		$vars = array(
			'--stx-sb-size' => (int) $settings['button']['size'] . 'px',
			'--stx-sb-x'    => (int) $settings['position']['offset_x'] . 'px',
			'--stx-sb-y'    => (int) $settings['position']['offset_y'] . 'px',
			'--stx-sb-z'    => (string) (int) $settings['display']['z_index'],
		);

		// Empty colours mean "follow the theme", so they are left to the stylesheet.
		if ( '' !== $settings['colors']['button_bg'] ) {
			$vars['--stx-sb-button'] = $settings['colors']['button_bg'];
		}
		if ( '' !== $settings['colors']['button_icon'] ) {
			$vars['--stx-sb-button-icon'] = $settings['colors']['button_icon'];
		}

		$css = '';
		foreach ( $vars as $name => $value ) {
			$css .= $name . ':' . $value . ';';
		}

		return $css;
	}

	private function settings(): array {
		return $this->module->settings();
	}
}
