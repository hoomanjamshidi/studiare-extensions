<?php
/**
 * Registry of visual styles for the bottom navigation.
 *
 * Each style is pure CSS (a `stx-bn--style-<id>` modifier in bottom-nav.css);
 * the metadata here drives the admin style picker and a few layout decisions.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Bottom_Nav;

defined( 'ABSPATH' ) || exit;

final class Styles {

	public const DEFAULT_STYLE = 'floating';

	/**
	 * @return array<string, array{label:string, description:string, floating:bool, label_modes:bool, featured:bool}>
	 *   - floating:    the bar is detached from the screen edges (uses the offset setting).
	 *   - label_modes: honours the "labels" setting; other styles manage labels themselves.
	 *   - featured:    renders the featured item as a raised action button.
	 */
	public static function all(): array {
		return array(
			'classic'  => array(
				'label'       => __( 'Classic', 'studiare-extensions' ),
				'description' => __( 'Edge-to-edge bar with a soft pill behind the active icon.', 'studiare-extensions' ),
				'floating'    => false,
				'label_modes' => true,
				'featured'    => false,
			),
			'floating' => array(
				'label'       => __( 'Floating glass', 'studiare-extensions' ),
				'description' => __( 'A floating frosted capsule with a sliding highlight.', 'studiare-extensions' ),
				'floating'    => true,
				'label_modes' => true,
				'featured'    => false,
			),
			'notch'    => array(
				'label'       => __( 'Center button', 'studiare-extensions' ),
				'description' => __( 'Curved notch with a raised round button for your featured item.', 'studiare-extensions' ),
				'floating'    => false,
				'label_modes' => true,
				'featured'    => true,
			),
			'bubble'   => array(
				'label'       => __( 'Bubble', 'studiare-extensions' ),
				'description' => __( 'The active icon pops up into a bubble that glides between items.', 'studiare-extensions' ),
				'floating'    => false,
				'label_modes' => false,
				'featured'    => false,
			),
			'pill'     => array(
				'label'       => __( 'Expanding pill', 'studiare-extensions' ),
				'description' => __( 'Icons only; the active item expands into a pill with its label.', 'studiare-extensions' ),
				'floating'    => true,
				'label_modes' => false,
				'featured'    => false,
			),
		);
	}

	/** @return string[] */
	public static function ids(): array {
		return array_keys( self::all() );
	}

	/**
	 * @param string $style Style id.
	 */
	public static function is_floating( string $style ): bool {
		return ! empty( self::all()[ $style ]['floating'] );
	}

	/**
	 * @param string $style Style id.
	 */
	public static function supports_label_modes( string $style ): bool {
		return ! empty( self::all()[ $style ]['label_modes'] );
	}

	/**
	 * @param string $style Style id.
	 */
	public static function has_featured_button( string $style ): bool {
		return ! empty( self::all()[ $style ]['featured'] );
	}
}
