<?php
/**
 * Cart & checkout module: Studiare+ designs for WooCommerce's cart,
 * checkout and thank-you pages (classic, shortcode based, as Studiare uses
 * them) and the mini cart, and the billing field settings.
 *
 * Off by default, so an update never changes a live store's checkout; each
 * page can keep the theme's design while the field settings still apply.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Checkout;

use StudiareExt\Core\Asset;
use StudiareExt\Core\Color;
use StudiareExt\Core\Module as Base_Module;
use StudiareExt\Core\Sanitizer;

defined( 'ABSPATH' ) || exit;

final class Module extends Base_Module {

	/** Colour tokens shared by the pages' and the mini cart's stylesheets. */
	public const TOKENS_HANDLE = 'stx-co-tokens';

	public function id(): string {
		return 'checkout';
	}

	public function title(): string {
		return __( 'Cart & checkout', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'A clear, quick cart and checkout: steps, a sticky order summary, coupon and payment in one place, and your own billing fields.', 'studiare-extensions' );
	}

	public function icon(): string {
		return 'cart';
	}

	public function defaults(): array {
		return Schema::defaults();
	}

	public function sanitize( array $input ): array {
		$clean                     = Sanitizer::apply( Schema::fields(), $input, Schema::defaults() );
		$clean['fields']['custom'] = Schema::valid_custom( $clean['fields']['custom'] );
		$clean['fields']['order']  = Schema::complete_order( $clean['fields']['order'], $clean['fields']['custom'] );

		return $clean;
	}

	protected function boot(): void {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		$settings = $this->settings();
		( new Billing_Fields( $settings['fields'] ) )->register();
		( new Pages( $settings ) )->register();
		( new Mini_Cart( $settings ) )->register();
		( new Woo_Texts() )->register();
		add_action( 'wp_enqueue_scripts', array( $this, 'register_tokens' ), 5 );
	}

	/**
	 * Registers tokens.css (enqueued as a dependency by Pages and Mini_Cart)
	 * with the admin's accent colour.
	 */
	public function register_tokens(): void {
		wp_register_style( self::TOKENS_HANDLE, Asset::url( 'assets/modules/checkout/css/tokens.css' ), array(), STUDIARE_EXT_VERSION );
		wp_add_inline_style( self::TOKENS_HANDLE, $this->color_css() );
	}

	/**
	 * The admin's accent colour and a readable text colour on it; empty
	 * keeps the theme's primary colour (white text, as the theme's buttons).
	 */
	private function color_css(): string {
		$accent = (string) $this->settings()['colors']['accent'];
		if ( '' === $accent ) {
			return '';
		}

		return sprintf( '.stx-co-page,.stx-mc,.stx-mc-on .sc-cart-offcanvas{--stx-co-accent:%1$s;--stx-co-on-accent:%2$s}', $accent, Color::ink( $accent ) );
	}

	/**
	 * Fields added in newer versions join the saved order; the admin's own
	 * fields get keys added to them since they were saved.
	 *
	 * @param array $settings Settings merged with defaults.
	 */
	protected function normalize( array $settings ): array {
		$custom = is_array( $settings['fields']['custom'] ) ? $settings['fields']['custom'] : array();

		$settings['fields']['custom'] = array_map(
			static function ( $field ): array {
				return array_merge( Schema::custom_defaults(), is_array( $field ) ? $field : array() );
			},
			$custom
		);
		$settings['fields']['order']  = Schema::complete_order( is_array( $settings['fields']['order'] ) ? $settings['fields']['order'] : array(), $settings['fields']['custom'] );

		return $settings;
	}

	public function render_admin(): void {
		$module = $this;
		require __DIR__ . '/views/admin.php';
	}

	public function enqueue_admin_assets(): void {
		wp_enqueue_style( 'stx-checkout-admin', STUDIARE_EXT_URL . 'assets/modules/checkout/css/checkout-admin.css', array(), STUDIARE_EXT_VERSION );
		wp_enqueue_script(
			'stx-checkout-admin',
			STUDIARE_EXT_URL . 'assets/modules/checkout/js/checkout-admin.js',
			array( 'stx-admin', 'jquery-ui-sortable' ),
			STUDIARE_EXT_VERSION,
			true
		);
	}

	public function admin_script_data(): array {
		return array(
			'customDefaults' => Schema::custom_defaults(),
			'customPrefix'   => Schema::CUSTOM_PREFIX,
			'maxCustom'      => Schema::MAX_CUSTOM,
			'labels'         => self::default_labels(),
			'i18n'           => array(
				'required'      => __( 'Required', 'studiare-extensions' ),
				'optional'      => __( 'Optional', 'studiare-extensions' ),
				'hidden'        => __( 'Hidden', 'studiare-extensions' ),
				'half'          => __( 'Half width', 'studiare-extensions' ),
				'shippedOnly'   => __( 'Shipped products only', 'studiare-extensions' ),
				'needsName'     => __( 'Add a label', 'studiare-extensions' ),
				'newField'      => __( 'New field', 'studiare-extensions' ),
				'needsChoices'  => __( 'Add choices', 'studiare-extensions' ),
				'deleteTitle'   => __( 'Delete this field?', 'studiare-extensions' ),
				/* translators: %s: field label. */
				'deleteMessage' => __( '"%s" will be removed from the checkout. Orders keep the values they already have.', 'studiare-extensions' ),
				'delete'        => __( 'Delete', 'studiare-extensions' ),
				/* translators: %d: maximum number of fields. */
				'maxReached'    => sprintf( __( 'You can add up to %d fields of your own.', 'studiare-extensions' ), Schema::MAX_CUSTOM ),
			),
		);
	}

	/**
	 * WooCommerce's own labels of the billing fields, shown where the panel
	 * leaves a label empty.
	 *
	 * @return array<string, string>
	 */
	public static function default_labels(): array {
		$labels = array();
		$fields = function_exists( 'WC' ) && WC()->countries ? WC()->countries->get_address_fields( '', 'billing_' ) : array();

		$own = self::own_labels();

		foreach ( Schema::CORE_FIELDS as $key ) {
			$label          = isset( $fields[ $key ]['label'] ) ? trim( wp_strip_all_tags( (string) $fields[ $key ]['label'] ) ) : '';
			$labels[ $key ] = '' !== $label ? $label : $own[ $key ];
		}

		return $labels;
	}

	/**
	 * Labels for fields WooCommerce leaves out (its "Company name" switch set
	 * to hidden) or labels only for screen readers (the second address line).
	 *
	 * @return array<string, string>
	 */
	private static function own_labels(): array {
		return array(
			'billing_first_name' => __( 'First name', 'studiare-extensions' ),
			'billing_last_name'  => __( 'Last name', 'studiare-extensions' ),
			'billing_phone'      => __( 'Phone', 'studiare-extensions' ),
			'billing_email'      => __( 'Email address', 'studiare-extensions' ),
			'billing_company'    => __( 'Company name', 'studiare-extensions' ),
			'billing_country'    => __( 'Country / Region', 'studiare-extensions' ),
			'billing_state'      => __( 'State / County', 'studiare-extensions' ),
			'billing_city'       => __( 'Town / City', 'studiare-extensions' ),
			'billing_address_1'  => __( 'Street address', 'studiare-extensions' ),
			'billing_address_2'  => __( 'Apartment, suite, unit, etc. (optional)', 'studiare-extensions' ),
			'billing_postcode'   => __( 'Postcode / ZIP', 'studiare-extensions' ),
		);
	}
}
