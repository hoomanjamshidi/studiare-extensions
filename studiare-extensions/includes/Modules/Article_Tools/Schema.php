<?php
/**
 * Settings schema and defaults for the article tools: the "Save as PDF"
 * button and the podcast player of blog posts.
 *
 * `fields()` feeds Core\Sanitizer; `defaults()` mirrors its shape.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Article_Tools;

defined( 'ABSPATH' ) || exit;

final class Schema {

	/** Where the PDF button goes: a small button above the text, a box after it, or both. */
	public const PDF_POSITIONS = array( 'top', 'bottom', 'both' );

	/** Player looks: tinted with the accent colour (follows dark mode), or always dark. */
	public const PODCAST_LOOKS = array( 'soft', 'studio' );

	public static function defaults(): array {
		return array(
			'enabled' => true,
			'pdf'     => array(
				'enabled'  => true,
				'position' => 'top',
				'label'    => __( 'Save as PDF', 'studiare-extensions' ),
				'picture'  => true,
				'source'   => true,
			),
			'podcast' => array(
				'enabled'  => true,
				'label'    => __( 'Listen to this article', 'studiare-extensions' ),
				'look'     => 'soft',
				'download' => true,
				'speed'    => true,
				'resume'   => true,
			),
			'colors'  => array(
				'accent' => '',
			),
		);
	}

	public static function fields(): array {
		return array(
			'enabled' => array( 'type' => 'bool' ),
			'pdf'     => array(
				'type'   => 'group',
				'fields' => array(
					'enabled'  => array( 'type' => 'bool' ),
					'position' => array(
						'type'    => 'enum',
						'options' => self::PDF_POSITIONS,
					),
					'label'    => array(
						'type'       => 'text',
						'max_length' => 30,
					),
					'picture'  => array( 'type' => 'bool' ),
					'source'   => array( 'type' => 'bool' ),
				),
			),
			'podcast' => array(
				'type'   => 'group',
				'fields' => array(
					'enabled'  => array( 'type' => 'bool' ),
					'label'    => array(
						'type'       => 'text',
						'max_length' => 40,
					),
					'look'     => array(
						'type'    => 'enum',
						'options' => self::PODCAST_LOOKS,
					),
					'download' => array( 'type' => 'bool' ),
					'speed'    => array( 'type' => 'bool' ),
					'resume'   => array( 'type' => 'bool' ),
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
