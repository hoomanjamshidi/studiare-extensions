<?php
/**
 * Applies the panel's billing field settings to WooCommerce's checkout.
 *
 * Runs on `woocommerce_checkout_fields` after the theme (Studiare sorts the
 * address fields at priority 999 and can drop them for course carts), so the
 * panel's order, labels and rules win. Address fields are also described in
 * WooCommerce's country locale, which its address script applies again in
 * the browser whenever the country changes; the same overrides go there, or
 * the page would show other labels and rules than the server checks.
 *
 * The admin's own fields are billing fields too (`billing_stx_<id>`):
 * WooCommerce saves them as order meta (`_billing_stx_<id>`) and on the
 * customer, who finds them filled in next time. This class shows them in
 * the order screen, the emails and the order details.
 *
 * Numbers typed with a Persian keyboard are converted before WooCommerce
 * validates them; its phone check rejects ۰۹۱۲…
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Checkout;

use StudiareExt\Core\Persian;

defined( 'ABSPATH' ) || exit;

final class Billing_Fields {

	/** Fields that hold numbers people type in Persian digits. */
	private const DIGIT_FIELDS = array( 'billing_phone', 'billing_postcode' );

	/** Custom field types that hold numbers. */
	private const DIGIT_TYPES = array( 'number', 'tel', 'national_code' );

	/** @var array The module's `fields` settings. */
	private $settings;

	/** @var array<string, int>|null Field key => priority. */
	private $priorities = null;

	/**
	 * @param array $settings The module's `fields` settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields' ), 1000 );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'country_locale' ), 1000 );
		add_filter( 'woocommerce_get_country_locale_default', array( $this, 'locale_fields' ), 1000 );
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'posted_data' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate' ), 10, 2 );
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'admin_fields' ) );
		add_filter( 'woocommerce_email_order_meta_fields', array( $this, 'email_fields' ), 10, 3 );
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'order_details' ) );

		if ( empty( $this->settings['notes']['enabled'] ) ) {
			add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 1000 );
		}
	}

	/**
	 * The billing fields in the panel's order, with its labels, rules and
	 * widths. Fields other plugins add stay beside the field they followed
	 * (Persian WooCommerce Shipping puts "District" after the city), and go
	 * with it when the panel hides that field.
	 *
	 * @param array $fields Checkout fields by group.
	 */
	public function checkout_fields( $fields ) {
		$fields  = is_array( $fields ) ? $fields : array();
		$billing = isset( $fields['billing'] ) && is_array( $fields['billing'] ) ? $fields['billing'] : array();
		$digital = self::cart_is_digital();
		$ours    = array();
		$half    = false;

		foreach ( $this->settings['order'] as $key ) {
			$field = $this->field( $key, $billing, $digital );
			if ( null === $field ) {
				continue;
			}

			// Half-width fields pair up as WooCommerce's first/last columns.
			$wide              = 'half' !== $field['stx_width'];
			$field['class']    = array_merge(
				array_diff( isset( $field['class'] ) ? (array) $field['class'] : array(), array( 'form-row-first', 'form-row-last', 'form-row-wide', 'stx-half' ) ),
				$wide ? array( 'form-row-wide' ) : array( $half ? 'form-row-last' : 'form-row-first', 'stx-half' )
			);
			$half              = ! $wide && ! $half;
			$field['priority'] = $this->priority( $key );
			unset( $field['stx_width'] );

			$ours[ $key ] = $field;
		}

		$fields['billing'] = $this->with_others( $ours, $billing );

		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments'] = $this->texts( $fields['order']['order_comments'], $this->settings['notes'] );
		}

		return $fields;
	}

	/**
	 * @param array $locales Country => address field overrides.
	 */
	public function country_locale( $locales ) {
		$locales = is_array( $locales ) ? $locales : array();
		foreach ( $locales as $country => $fields ) {
			$locales[ $country ] = $this->locale_fields( $fields, false );
		}

		return $locales;
	}

	/**
	 * The panel's settings in a country locale: everywhere in the default
	 * one, only over fields a country describes otherwise. WooCommerce's
	 * address script re-sorts and relabels the form with these whenever the
	 * country changes, and the default locale covers more than the address
	 * (the names, company and phone have priorities there too).
	 *
	 * @param array $fields   Field => overrides (keys without `billing_`).
	 * @param bool  $complete Whether every field gets an entry.
	 */
	public function locale_fields( $fields, $complete = true ) {
		$fields = is_array( $fields ) ? $fields : array();

		foreach ( Schema::CORE_FIELDS as $key ) {
			$short = substr( $key, strlen( 'billing_' ) );
			if ( ! $complete && ! isset( $fields[ $short ] ) ) {
				continue;
			}

			$setting  = $this->settings['billing'][ $key ];
			$override = array( 'priority' => $this->priority( $key ) );
			if ( 'auto' !== $setting['required'] ) {
				$override['required'] = 'yes' === $setting['required'];
			}

			$fields[ $short ] = $this->texts( array_merge( isset( $fields[ $short ] ) ? (array) $fields[ $short ] : array(), $override ), $setting );
		}

		return $fields;
	}

	/**
	 * Numbers in Latin digits before WooCommerce checks them; the phone as
	 * 09… when Iranian mobiles are expected.
	 *
	 * @param array $data Posted checkout data.
	 */
	public function posted_data( $data ) {
		$data = is_array( $data ) ? $data : array();

		foreach ( $this->digit_fields() as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_string( $data[ $key ] ) ) {
				continue;
			}

			$data[ $key ] = 'billing_phone' === $key && ! empty( $this->settings['iran_phone'] )
				? Persian::iran_mobile( $data[ $key ] )
				: trim( Persian::latin_digits( $data[ $key ] ) );
		}

		return $data;
	}

	/**
	 * Rules WooCommerce does not know: Iranian mobiles, national codes,
	 * numbers and choices from the list.
	 *
	 * @param array     $data   Posted checkout data (already cleaned).
	 * @param \WP_Error $errors Errors so far.
	 */
	public function validate( $data, $errors ): void {
		if ( ! $errors instanceof \WP_Error || ! is_array( $data ) ) {
			return;
		}

		$fields = WC()->checkout()->get_checkout_fields( 'billing' );

		if ( ! empty( $this->settings['iran_phone'] ) && isset( $fields['billing_phone'] ) && '' !== (string) ( $data['billing_phone'] ?? '' ) && ! preg_match( '/^09\d{9}$/', (string) $data['billing_phone'] ) && ! self::persian_woocommerce_checks_phone() ) {
			/* translators: %s: field label, e.g. Phone. */
			$this->error( $errors, 'billing_phone', $fields['billing_phone'], __( '%s is not a valid mobile number. Example: 09123456789', 'studiare-extensions' ) );
		}

		foreach ( $this->custom() as $custom ) {
			$key   = $custom['id'];
			$value = (string) ( $data[ $key ] ?? '' );
			if ( ! isset( $fields[ $key ] ) || '' === $value ) {
				continue;
			}

			if ( 'national_code' === $custom['type'] && ! Persian::is_national_code( $value ) ) {
				/* translators: %s: field label. */
				$this->error( $errors, $key, $fields[ $key ], __( '%s is not a valid national code.', 'studiare-extensions' ) );
			} elseif ( 'number' === $custom['type'] && ! is_numeric( $value ) ) {
				/* translators: %s: field label. */
				$this->error( $errors, $key, $fields[ $key ], __( '%s must be a number.', 'studiare-extensions' ) );
			} elseif ( 'select' === $custom['type'] && ! in_array( $value, self::choices( $custom ), true ) ) {
				/* translators: %s: field label. */
				$this->error( $errors, $key, $fields[ $key ], __( 'Please choose %s from the list.', 'studiare-extensions' ) );
			}
		}
	}

	/**
	 * The admin's fields in the order screen's billing column, where they
	 * can be read and edited (keys without `billing_`, as WooCommerce wants).
	 *
	 * @param array $fields Billing fields of the order screen.
	 */
	public function admin_fields( $fields ) {
		$fields = is_array( $fields ) ? $fields : array();
		foreach ( $this->custom( false ) as $custom ) {
			$fields[ substr( $custom['id'], strlen( 'billing_' ) ) ] = array(
				'label' => $custom['label'],
				'show'  => true,
			);
		}

		return $fields;
	}

	/**
	 * @param array     $fields        Fields WooCommerce lists under the order.
	 * @param bool      $sent_to_admin Whether the email goes to the store.
	 * @param \WC_Order $order         Order.
	 */
	public function email_fields( $fields, $sent_to_admin, $order ) {
		$fields = is_array( $fields ) ? $fields : array();
		if ( ! $order instanceof \WC_Order ) {
			return $fields;
		}

		foreach ( $this->values( $order ) as $key => $row ) {
			$fields[ $key ] = $row;
		}

		return $fields;
	}

	/**
	 * Under the addresses on the thank-you page and in "My account".
	 *
	 * @param \WC_Order $order Order.
	 */
	public function order_details( $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$rows = $this->values( $order );
		if ( ! $rows ) {
			return;
		}

		echo '<section class="stx-co-extra"><dl>';
		foreach ( $rows as $row ) {
			printf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html( $row['label'] ), esc_html( $row['value'] ) );
		}
		echo '</dl></section>';
	}

	/**
	 * Whether Persian WooCommerce already rejects a phone that is not an
	 * Iranian mobile (its "phone_validation" tool, on by default, which it
	 * only registers while its "fix_postcode_persian_number" tool is on); a
	 * second message for the same field would only repeat it.
	 */
	private static function persian_woocommerce_checks_phone(): bool {
		return function_exists( 'PW' ) && 'no' !== PW()->get_options( 'fix_postcode_persian_number', 'yes' ) && 'yes' === PW()->get_options( 'phone_validation', 'yes' );
	}

	/** Whether the cart holds only courses and downloads (virtual or downloadable products). */
	public static function cart_is_digital(): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			$product = $item['data'] ?? null;
			if ( ! $product instanceof \WC_Product || ! ( $product->is_virtual() || $product->is_downloadable() ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The admin's field's choices, one per line.
	 *
	 * @param array $custom Field settings.
	 * @return string[]
	 */
	public static function choices( array $custom ): array {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) $custom['options'] ) ) ) );
	}

	/**
	 * Adds the fields other plugins put in the billing group. Each one is
	 * anchored to the WooCommerce field just before it (by their original
	 * priorities) and follows it: placed right after it, or left out when
	 * the panel hides it. A field before every known one keeps the top.
	 *
	 * @param array $ours    Our fields with their new priorities.
	 * @param array $billing The billing fields as they came in.
	 */
	private function with_others( array $ours, array $billing ): array {
		$before = array();
		foreach ( $billing as $key => $field ) {
			if ( in_array( $key, Schema::CORE_FIELDS, true ) ) {
				$before[ $key ] = isset( $field['priority'] ) ? (int) $field['priority'] : 0;
			}
		}

		$others = array_filter(
			$billing,
			static function ( $key ) {
				return ! in_array( $key, Schema::CORE_FIELDS, true ) && 0 !== strpos( (string) $key, Schema::CUSTOM_PREFIX );
			},
			ARRAY_FILTER_USE_KEY
		);
		uasort(
			$others,
			static function ( $a, $b ) {
				return ( $a['priority'] ?? 0 ) <=> ( $b['priority'] ?? 0 );
			}
		);

		$steps = array();
		foreach ( $others as $key => $field ) {
			$priority = isset( $field['priority'] ) ? (int) $field['priority'] : 0;
			$anchor   = '';
			foreach ( $before as $core => $core_priority ) {
				if ( $core_priority <= $priority && ( '' === $anchor || $core_priority >= $before[ $anchor ] ) ) {
					$anchor = $core;
				}
			}

			if ( '' === $anchor ) {
				$field['priority'] = min( $priority, 5 );
			} elseif ( isset( $ours[ $anchor ] ) ) {
				$steps[ $anchor ]  = ( $steps[ $anchor ] ?? 0 ) + 1;
				$field['priority'] = $ours[ $anchor ]['priority'] + $steps[ $anchor ];
			} else {
				continue;
			}

			$ours[ $key ] = $field;
		}

		return $ours;
	}

	/**
	 * One field as WooCommerce describes it, or null when it is not shown.
	 *
	 * @param string $key     Field key.
	 * @param array  $billing WooCommerce's billing fields.
	 * @param bool   $digital Whether the cart holds only courses and downloads.
	 */
	private function field( string $key, array $billing, bool $digital ): ?array {
		if ( isset( $this->settings['billing'][ $key ] ) ) {
			$setting = $this->settings['billing'][ $key ];
			// Missing: WooCommerce's own settings or the theme took it out.
			if ( empty( $setting['enabled'] ) || ! isset( $billing[ $key ] ) || ( $digital && empty( $setting['digital'] ) ) ) {
				return null;
			}

			$field = $this->texts( $billing[ $key ], $setting );
			if ( 'auto' !== $setting['required'] ) {
				$field['required'] = 'yes' === $setting['required'];
			}
			if ( in_array( $key, self::DIGIT_FIELDS, true ) ) {
				$field['custom_attributes'] = array_merge( isset( $field['custom_attributes'] ) ? (array) $field['custom_attributes'] : array(), self::digit_attributes() );
			}

			return $field + array( 'stx_width' => $setting['width'] );
		}

		foreach ( $this->custom() as $custom ) {
			if ( $custom['id'] === $key ) {
				return $digital && empty( $custom['digital'] ) ? null : $this->custom_field( $custom );
			}
		}

		return null;
	}

	/**
	 * A field of the admin's in WooCommerce's terms. Numbers are text inputs
	 * (`inputmode` brings up the number pad): a number input refuses digits
	 * typed with a Persian keyboard.
	 *
	 * @param array $custom Field settings.
	 */
	private function custom_field( array $custom ): array {
		$types = array(
			'text'          => 'text',
			'textarea'      => 'textarea',
			'number'        => 'text',
			'tel'           => 'tel',
			'email'         => 'email',
			'select'        => 'select',
			'checkbox'      => 'checkbox',
			'national_code' => 'text',
		);

		$field = array(
			'type'        => $types[ $custom['type'] ],
			'label'       => $custom['label'],
			'placeholder' => $custom['placeholder'],
			'required'    => (bool) $custom['required'],
			'stx_width'   => $custom['width'],
		);

		if ( in_array( $custom['type'], self::DIGIT_TYPES, true ) ) {
			$field['custom_attributes'] = self::digit_attributes();
		}
		if ( 'national_code' === $custom['type'] ) {
			$field['custom_attributes']['maxlength'] = '10';
		}
		if ( 'select' === $custom['type'] ) {
			$choices          = self::choices( $custom );
			$field['options'] = array( '' => '' !== $custom['placeholder'] ? $custom['placeholder'] : __( 'Choose an option', 'studiare-extensions' ) ) + array_combine( $choices, $choices );
		}

		return $field;
	}

	/**
	 * Label and placeholder from the panel when it gives them.
	 *
	 * @param array $field   WooCommerce field.
	 * @param array $setting Panel settings with `label` and `placeholder`.
	 */
	private function texts( array $field, array $setting ): array {
		foreach ( array( 'label', 'placeholder' ) as $text ) {
			if ( '' !== (string) $setting[ $text ] ) {
				$field[ $text ] = $setting[ $text ];
			}
		}

		return $field;
	}

	/** Left to right, Latin digits as typed, the number pad on phones. */
	private static function digit_attributes(): array {
		return array(
			'dir'             => 'ltr',
			'inputmode'       => 'numeric',
			'data-stx-digits' => '1',
		);
	}

	/**
	 * Priority from the panel's order: 10, 20, …
	 *
	 * @param string $key Field key.
	 */
	private function priority( string $key ): int {
		if ( null === $this->priorities ) {
			$this->priorities = array();
			foreach ( array_values( $this->settings['order'] ) as $index => $order_key ) {
				$this->priorities[ $order_key ] = ( $index + 1 ) * 10;
			}
		}

		return $this->priorities[ $key ] ?? 1000;
	}

	/** @return string[] Keys whose values are numbers. */
	private function digit_fields(): array {
		$keys = self::DIGIT_FIELDS;
		foreach ( $this->custom() as $custom ) {
			if ( in_array( $custom['type'], self::DIGIT_TYPES, true ) ) {
				$keys[] = $custom['id'];
			}
		}

		return $keys;
	}

	/**
	 * The admin's fields that can be filled in (switched on, with a name).
	 *
	 * @param bool $only_enabled Leave out switched-off fields; old orders may still hold their values.
	 */
	private function custom( bool $only_enabled = true ): array {
		return array_values(
			array_filter(
				(array) $this->settings['custom'],
				static function ( array $custom ) use ( $only_enabled ): bool {
					return '' !== $custom['label'] && ( ! $only_enabled || ! empty( $custom['enabled'] ) );
				}
			)
		);
	}

	/**
	 * The admin's fields an order holds, as label/value rows.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<string, array{label:string, value:string}>
	 */
	private function values( \WC_Order $order ): array {
		$rows = array();
		foreach ( $this->custom( false ) as $custom ) {
			$value = (string) $order->get_meta( '_' . $custom['id'] );
			if ( '' === $value ) {
				continue;
			}
			if ( 'checkbox' === $custom['type'] ) {
				$value = $value ? __( 'Yes', 'studiare-extensions' ) : __( 'No', 'studiare-extensions' );
			}
			$rows[ $custom['id'] ] = array(
				'label' => $custom['label'],
				'value' => $value,
			);
		}

		return $rows;
	}

	/**
	 * Adds an error that names the field and points WooCommerce's script at it.
	 *
	 * @param \WP_Error $errors  Errors.
	 * @param string    $key     Field key.
	 * @param array     $field   Field.
	 * @param string    $message Message with %s for the field's label.
	 */
	private function error( \WP_Error $errors, string $key, array $field, string $message ): void {
		$label = isset( $field['label'] ) ? (string) $field['label'] : $key;
		$errors->add( $key . '_validation', sprintf( $message, '<strong>' . esc_html( wp_strip_all_tags( $label ) ) . '</strong>' ), array( 'id' => $key ) );
	}
}
