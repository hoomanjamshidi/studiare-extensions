<?php
/**
 * Cart & checkout settings: defaults and the sanitizer schema (same shape).
 *
 * Billing fields are WooCommerce's own, keyed by their names (`billing_phone`),
 * so the panel binds them directly; `fields.order` holds those keys and the
 * keys of the admin's own fields (`billing_stx_<id>`, saved by WooCommerce as
 * order meta `_billing_stx_<id>`).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Checkout;

defined( 'ABSPATH' ) || exit;

final class Schema {

	/** WooCommerce's billing fields, in the default order of the panel. */
	public const CORE_FIELDS = array(
		'billing_first_name',
		'billing_last_name',
		'billing_phone',
		'billing_email',
		'billing_company',
		'billing_country',
		'billing_state',
		'billing_city',
		'billing_address_1',
		'billing_address_2',
		'billing_postcode',
	);

	/** Fields asked for courses and downloads too, until the admin decides otherwise. */
	private const DIGITAL_FIELDS = array( 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email' );

	/** Key prefix of the admin's own fields. */
	public const CUSTOM_PREFIX = 'billing_stx_';

	/** Most fields an admin can add. */
	public const MAX_CUSTOM = 10;

	/** Types of the admin's own fields. */
	public const CUSTOM_TYPES = array( 'text', 'textarea', 'number', 'tel', 'email', 'select', 'checkbox', 'national_code' );

	public static function defaults(): array {
		return array(
			'enabled'  => false,
			'cart'     => array(
				'design'      => 'stx',
				'steps'       => true,
				'auto_update' => true,
				'coupon'      => 'toggle',
				'continue'    => true,
				'cross_sells' => true,
				'mobile_bar'  => true,
				'note'        => __( 'Secure payment, instant access to your courses after purchase.', 'studiare-extensions' ),
			),
			'checkout' => array(
				'design'  => 'stx',
				'steps'   => true,
				'summary' => 'collapsed',
				'coupon'  => 'toggle',
				'button'  => '',
				'note'    => __( 'Your details stay private and are only used for this order.', 'studiare-extensions' ),
			),
			'thankyou' => array(
				'design'    => 'stx',
				'steps'     => true,
				'next'      => true,
				'celebrate' => true,
				'note'      => '',
			),
			'mini'     => array(
				'design'  => 'stx',
				'savings' => true,
			),
			'fields'   => array(
				'iran_phone' => true,
				'order'      => self::CORE_FIELDS,
				'billing'    => self::core_defaults(),
				'notes'      => array(
					'enabled'     => true,
					'label'       => '',
					'placeholder' => '',
				),
				'custom'     => array(),
			),
			'colors'   => array(
				'accent' => '',
			),
		);
	}

	public static function fields(): array {
		$design = array(
			'type'    => 'enum',
			'options' => array( 'stx', 'theme' ),
		);

		return array(
			'enabled'  => array( 'type' => 'bool' ),
			'cart'     => array(
				'type'   => 'group',
				'fields' => array(
					'design'      => $design,
					'steps'       => array( 'type' => 'bool' ),
					'auto_update' => array( 'type' => 'bool' ),
					'coupon'      => array(
						'type'    => 'enum',
						'options' => array( 'toggle', 'open', 'hidden' ),
					),
					'continue'    => array( 'type' => 'bool' ),
					'cross_sells' => array( 'type' => 'bool' ),
					'mobile_bar'  => array( 'type' => 'bool' ),
					'note'        => array(
						'type'       => 'text',
						'max_length' => 160,
					),
				),
			),
			'checkout' => array(
				'type'   => 'group',
				'fields' => array(
					'design'  => $design,
					'steps'   => array( 'type' => 'bool' ),
					'summary' => array(
						'type'    => 'enum',
						'options' => array( 'collapsed', 'open' ),
					),
					'coupon'  => array(
						'type'    => 'enum',
						'options' => array( 'toggle', 'hidden' ),
					),
					'button'  => array(
						'type'       => 'text',
						'max_length' => 40,
					),
					'note'    => array(
						'type'       => 'text',
						'max_length' => 160,
					),
				),
			),
			'thankyou' => array(
				'type'   => 'group',
				'fields' => array(
					'design'    => $design,
					'steps'     => array( 'type' => 'bool' ),
					'next'      => array( 'type' => 'bool' ),
					'celebrate' => array( 'type' => 'bool' ),
					'note'      => array(
						'type'       => 'text',
						'max_length' => 200,
					),
				),
			),
			'mini'     => array(
				'type'   => 'group',
				'fields' => array(
					'design'  => $design,
					'savings' => array( 'type' => 'bool' ),
				),
			),
			'fields'   => array(
				'type'   => 'group',
				'fields' => array(
					'iran_phone' => array( 'type' => 'bool' ),
					'order'      => array( 'type' => 'key_list' ),
					'billing'    => array(
						'type'   => 'group',
						'fields' => array_fill_keys(
							self::CORE_FIELDS,
							array(
								'type'   => 'group',
								'fields' => self::field_fields(),
							)
						),
					),
					'notes'      => array(
						'type'   => 'group',
						'fields' => array(
							'enabled'     => array( 'type' => 'bool' ),
							'label'       => array(
								'type'       => 'text',
								'max_length' => 60,
							),
							'placeholder' => array(
								'type'       => 'text',
								'max_length' => 120,
							),
						),
					),
					'custom'     => array(
						'type'     => 'list',
						'max'      => self::MAX_CUSTOM,
						'fields'   => self::custom_fields(),
						'defaults' => self::custom_defaults(),
					),
				),
			),
			'colors'   => array(
				'type'   => 'group',
				'fields' => array(
					'accent' => array( 'type' => 'color' ),
				),
			),
		);
	}

	/**
	 * Settings of one WooCommerce field. Empty texts keep WooCommerce's;
	 * `required` "auto" keeps its rule, which for address fields depends on
	 * the country. `digital` off leaves the field out when the cart holds
	 * only courses and downloads (virtual or downloadable products).
	 */
	private static function field_fields(): array {
		return array(
			'enabled'     => array( 'type' => 'bool' ),
			'digital'     => array( 'type' => 'bool' ),
			'required'    => array(
				'type'    => 'enum',
				'options' => array( 'auto', 'yes', 'no' ),
			),
			'label'       => array(
				'type'       => 'text',
				'max_length' => 60,
			),
			'placeholder' => array(
				'type'       => 'text',
				'max_length' => 80,
			),
			'width'       => array(
				'type'    => 'enum',
				'options' => array( 'full', 'half' ),
			),
		);
	}

	/** Settings of a field the admin adds. */
	public static function custom_fields(): array {
		return array_merge(
			self::field_fields(),
			array(
				'id'       => array( 'type' => 'key' ),
				'type'     => array(
					'type'    => 'enum',
					'options' => self::CUSTOM_TYPES,
				),
				'required' => array( 'type' => 'bool' ),
				'options'  => array(
					'type'       => 'textarea',
					'max_length' => 1000,
				),
			)
		);
	}

	/** A new field of the admin's; the panel script gives it its id. */
	public static function custom_defaults(): array {
		return array(
			'id'          => '',
			'enabled'     => true,
			'digital'     => true,
			'type'        => 'text',
			'label'       => '',
			'placeholder' => '',
			'required'    => false,
			'width'       => 'full',
			'options'     => '',
		);
	}

	/**
	 * The admin's fields with a valid, unique id; others are dropped (a field
	 * without one could not be ordered or saved with the order).
	 *
	 * @param array $custom Sanitized fields.
	 */
	public static function valid_custom( array $custom ): array {
		$seen = array();

		return array_values(
			array_filter(
				$custom,
				static function ( array $field ) use ( &$seen ): bool {
					$id = $field['id'];
					if ( 0 !== strpos( $id, self::CUSTOM_PREFIX ) || strlen( $id ) <= strlen( self::CUSTOM_PREFIX ) || isset( $seen[ $id ] ) ) {
						return false;
					}
					$seen[ $id ] = true;

					return true;
				}
			)
		);
	}

	/**
	 * Every field exactly once: the saved order first, then fields missing
	 * from it. Keys of deleted fields are dropped.
	 *
	 * @param string[] $order  Saved order.
	 * @param array    $custom The admin's fields.
	 * @return string[]
	 */
	public static function complete_order( array $order, array $custom ): array {
		$known = array_merge( self::CORE_FIELDS, array_column( $custom, 'id' ) );

		return array_values( array_unique( array_merge( array_intersect( $order, $known ), $known ) ) );
	}

	/** @return array<string, array> Field key => defaults. */
	private static function core_defaults(): array {
		$half     = array( 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email', 'billing_state', 'billing_city' );
		$defaults = array();

		foreach ( self::CORE_FIELDS as $key ) {
			$defaults[ $key ] = array(
				// A company name is rarely asked for a course.
				'enabled'     => 'billing_company' !== $key,
				'digital'     => in_array( $key, self::DIGITAL_FIELDS, true ),
				// Iranian gateways and SMS notices need the mobile number.
				'required'    => 'billing_phone' === $key ? 'yes' : 'auto',
				'label'       => '',
				'placeholder' => '',
				'width'       => in_array( $key, $half, true ) ? 'half' : 'full',
			);
		}

		return $defaults;
	}
}
