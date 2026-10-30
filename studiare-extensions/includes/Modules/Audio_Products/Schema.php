<?php
/**
 * Settings schema and defaults of the audio products module: where the
 * player goes on the product page, the free preview, downloads after
 * purchase and the "My audio" tab of the account page.
 *
 * `fields()` feeds Core\Sanitizer; `defaults()` mirrors its shape.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Audio_Products;

defined( 'ABSPATH' ) || exit;

final class Schema {

	/** Where the player goes in the product description: before or after the text. */
	public const POSITIONS = array( 'top', 'bottom' );

	/** Player looks: tinted with the accent colour (follows dark mode), or always dark. */
	public const LOOKS = array( 'soft', 'studio' );

	/** Lowest place the "My audio" tab can take in the account menu (0 = automatic). */
	public const MAX_MENU_POSITION = 12;

	/** Shortest and longest "first N seconds" preview. */
	public const MIN_CLIP = 10;
	public const MAX_CLIP = 600;

	public static function defaults(): array {
		return array(
			'enabled' => true,
			'player'  => array(
				'position' => 'top',
				'label'    => __( 'Audio files of this product', 'studiare-extensions' ),
				'look'     => 'soft',
				'speed'    => true,
				'resume'   => true,
			),
			'preview' => array(
				'seconds' => 60,
				'lessons' => true,
				'cta'     => true,
			),
			'account' => array(
				'enabled'  => true,
				'label'    => __( 'My audio', 'studiare-extensions' ),
				'position' => 0,
				'courses'  => true,
			),
			'colors'  => array(
				'accent' => '',
			),
		);
	}

	public static function fields(): array {
		return array(
			'enabled' => array( 'type' => 'bool' ),
			'player'  => array(
				'type'   => 'group',
				'fields' => array(
					'position' => array(
						'type'    => 'enum',
						'options' => self::POSITIONS,
					),
					'label'    => array(
						'type'       => 'text',
						'max_length' => 60,
					),
					'look'     => array(
						'type'    => 'enum',
						'options' => self::LOOKS,
					),
					'speed'    => array( 'type' => 'bool' ),
					'resume'   => array( 'type' => 'bool' ),
				),
			),
			'preview' => array(
				'type'   => 'group',
				'fields' => array(
					'seconds' => array(
						'type' => 'int',
						'min'  => self::MIN_CLIP,
						'max'  => self::MAX_CLIP,
					),
					'lessons' => array( 'type' => 'bool' ),
					'cta'     => array( 'type' => 'bool' ),
				),
			),
			'account' => array(
				'type'   => 'group',
				'fields' => array(
					'enabled'  => array( 'type' => 'bool' ),
					'label'    => array(
						'type'       => 'text',
						'max_length' => 30,
					),
					'position' => array(
						'type' => 'int',
						'min'  => 0,
						'max'  => self::MAX_MENU_POSITION,
					),
					'courses'  => array( 'type' => 'bool' ),
				),
			),
			'colors'  => array(
				'type'   => 'group',
				'fields' => array(
					'accent' => array( 'type' => 'color' ),
				),
			),
		);
	}
}
