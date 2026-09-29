<?php
/**
 * Settings schema and defaults for the floating support button.
 *
 * `fields()` feeds Core\Sanitizer; `defaults()` mirrors its shape. Channels
 * are a group keyed by channel id (so admin fields bind by path, e.g.
 * `channels.telegram.value`) and `order` holds their display order.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Support_Button;

defined( 'ABSPATH' ) || exit;

final class Schema {

	/** Icons offered for the main button and the custom link channel. */
	public const ICONS = array( 'chat', 'support', 'help', 'phone', 'mail', 'ticket' );

	/** Channels switched on for new installs: the four messengers Iranian visitors use most. */
	private const DEFAULT_CHANNELS = array( 'telegram', 'whatsapp', 'bale', 'eitaa' );

	public static function defaults(): array {
		return array(
			'enabled'  => true,
			'design'   => 'card',
			'order'    => Channels::ids(),
			'channels' => self::channel_defaults(),
			'button'   => array(
				'icon'       => 'chat',
				'label'      => __( 'Support', 'studiare-extensions' ),
				'label_mode' => 'desktop',
				'size'       => 56,
				'pulse'      => true,
			),
			'header'   => array(
				'title'    => __( 'Online support', 'studiare-extensions' ),
				'subtitle' => __( 'Message us in the app you prefer.', 'studiare-extensions' ),
			),
			'greeting' => array(
				'enabled' => false,
				'text'    => __( 'Hi! Have a question about a course? We are here to help.', 'studiare-extensions' ),
				'delay'   => 6,
			),
			'colors'   => array(
				'brand'       => true,
				'button_bg'   => '',
				'button_icon' => '',
			),
			'position' => array(
				'side'     => 'start',
				'offset_x' => 20,
				'offset_y' => 24,
			),
			'display'  => array(
				'devices'          => 'all',
				'hide_on_checkout' => true,
				'hide_on_cart'     => false,
				'hide_for_ids'     => '',
				'z_index'          => 9985,
			),
		);
	}

	public static function fields(): array {
		return array(
			'enabled'  => array( 'type' => 'bool' ),
			'design'   => array(
				'type'    => 'enum',
				'options' => array( 'card', 'bubbles' ),
			),
			'order'    => array( 'type' => 'key_list' ),
			'channels' => array(
				'type'   => 'group',
				'fields' => array_fill_keys(
					Channels::ids(),
					array(
						'type'   => 'group',
						'fields' => self::channel_fields(),
					)
				),
			),
			'button'   => array(
				'type'   => 'group',
				'fields' => array(
					'icon'       => array(
						'type'    => 'enum',
						'options' => self::ICONS,
					),
					'label'      => array(
						'type'       => 'text',
						'max_length' => 30,
					),
					'label_mode' => array(
						'type'    => 'enum',
						'options' => array( 'always', 'desktop', 'never' ),
					),
					'size'       => array(
						'type' => 'int',
						'min'  => 48,
						'max'  => 72,
					),
					'pulse'      => array( 'type' => 'bool' ),
				),
			),
			'header'   => array(
				'type'   => 'group',
				'fields' => array(
					'title'    => array(
						'type'       => 'text',
						'max_length' => 60,
					),
					'subtitle' => array(
						'type'       => 'text',
						'max_length' => 140,
					),
				),
			),
			'greeting' => array(
				'type'   => 'group',
				'fields' => array(
					'enabled' => array( 'type' => 'bool' ),
					'text'    => array(
						'type'       => 'text',
						'max_length' => 140,
					),
					'delay'   => array(
						'type' => 'int',
						'min'  => 0,
						'max'  => 60,
					),
				),
			),
			'colors'   => array(
				'type'   => 'group',
				'fields' => array(
					'brand'       => array( 'type' => 'bool' ),
					'button_bg'   => array( 'type' => 'color' ),
					'button_icon' => array( 'type' => 'color' ),
				),
			),
			'position' => array(
				'type'   => 'group',
				'fields' => array(
					'side'     => array(
						'type'    => 'enum',
						'options' => array( 'start', 'end' ),
					),
					'offset_x' => array(
						'type' => 'int',
						'min'  => 8,
						'max'  => 80,
					),
					'offset_y' => array(
						'type' => 'int',
						'min'  => 8,
						'max'  => 160,
					),
				),
			),
			'display'  => array(
				'type'   => 'group',
				'fields' => array(
					'devices'          => array(
						'type'    => 'enum',
						'options' => array( 'all', 'desktop', 'mobile' ),
					),
					'hide_on_checkout' => array( 'type' => 'bool' ),
					'hide_on_cart'     => array( 'type' => 'bool' ),
					'hide_for_ids'     => array( 'type' => 'id_list' ),
					'z_index'          => array(
						'type' => 'int',
						'min'  => 1,
						'max'  => 2147483000,
					),
				),
			),
		);
	}

	/**
	 * Settings of one channel. `message` is used by WhatsApp and `icon` by
	 * the custom link; other channels ignore them.
	 */
	public static function channel_fields(): array {
		return array(
			'enabled' => array( 'type' => 'bool' ),
			'value'   => array(
				'type'       => 'text',
				'max_length' => 200,
			),
			'label'   => array(
				'type'       => 'text',
				'max_length' => 30,
			),
			'note'    => array(
				'type'       => 'text',
				'max_length' => 60,
			),
			'message' => array(
				'type'       => 'text',
				'max_length' => 200,
			),
			'icon'    => array(
				'type'    => 'enum',
				'options' => self::ICONS,
			),
		);
	}

	/** @return array<string, array> Channel id => defaults. */
	private static function channel_defaults(): array {
		$defaults = array();
		foreach ( Channels::ids() as $id ) {
			$defaults[ $id ] = array(
				'enabled' => in_array( $id, self::DEFAULT_CHANNELS, true ),
				'value'   => '',
				'label'   => '',
				'note'    => '',
				'message' => '',
				'icon'    => 'ticket',
			);
		}

		return $defaults;
	}

	/**
	 * Keeps every known channel exactly once: saved order first, then any
	 * channel added in a newer version.
	 *
	 * @param string[] $order Saved order.
	 * @return string[]
	 */
	public static function complete_order( array $order ): array {
		return array_values( array_unique( array_merge( array_intersect( $order, Channels::ids() ), Channels::ids() ) ) );
	}
}
