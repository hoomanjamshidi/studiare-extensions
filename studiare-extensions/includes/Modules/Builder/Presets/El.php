<?php
/**
 * Tiny builder for Elementor data (Flexbox Containers + widgets).
 *
 * Presets are written with this instead of hand-typed JSON, so every element
 * gets a unique ID and every setting has the exact shape Elementor stores
 * (sliders, dimensions, gaps, responsive keys). Anything not set falls back
 * to the control defaults at render time, exactly like elements built in
 * the editor.
 *
 * Container options (`$o`):
 *   boxed (int px, or true for the site's container width) · width / width_tablet / width_mobile (% or "360px")
 *   dir / dir_tablet / dir_mobile · gap / gap_mobile · wrap · align · justify
 *   pad / pad_tablet / pad_mobile (int or [t, r, b, l]) · margin / margin_mobile
 *   surface · sticky · sticky_offset · tag · min_h · grow · fill · bg · radius · border
 *   hide (array of devices: desktop|tablet|mobile) · set (raw settings merged last)
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

use StudiareExt\Core\Site;

defined( 'ABSPATH' ) || exit;

final class El {

	/** 7 hex characters, like Elementor's own element IDs. */
	public static function id(): string {
		return substr( bin2hex( random_bytes( 4 ) ), 0, 7 );
	}

	/**
	 * Container element.
	 *
	 * @param array $o        Options (see class doc).
	 * @param array $children Child elements.
	 */
	public static function box( array $o, array $children = array() ): array {
		$s = array();

		// A growing container must start from its content width: with Elementor's default
		// 100% it would not shrink and would push its siblings out of the row.
		if ( ! empty( $o['grow'] ) && ! isset( $o['width'] ) ) {
			$o['width']        = 'auto';
			$o['width_mobile'] = $o['width_mobile'] ?? 100;
		}

		// Elementor resets container widths to 100% on phones unless a phone width is set.
		if ( isset( $o['width'] ) && 'auto' === $o['width'] && ! isset( $o['width_mobile'] ) ) {
			$o['width_mobile'] = 'auto';
		}

		if ( isset( $o['boxed'] ) ) {
			$s['content_width'] = 'boxed';
			// `true` keeps Elementor's site-wide container width, so the template lines up with the page content.
			if ( true !== $o['boxed'] ) {
				$s['boxed_width'] = self::px( (int) $o['boxed'] );
			}
		} else {
			$s['content_width'] = 'full';
		}

		foreach ( array( '', '_tablet', '_mobile' ) as $device ) {
			if ( isset( $o[ 'width' . $device ] ) ) {
				$s[ 'width' . $device ] = self::size( $o[ 'width' . $device ] );
			}
			if ( isset( $o[ 'dir' . $device ] ) ) {
				$s[ 'flex_direction' . $device ] = $o[ 'dir' . $device ];
			}
			if ( isset( $o[ 'gap' . $device ] ) ) {
				$s[ 'flex_gap' . $device ] = self::gap( $o[ 'gap' . $device ] );
			}
			if ( isset( $o[ 'pad' . $device ] ) ) {
				$s[ 'padding' . $device ] = self::dims( $o[ 'pad' . $device ] );
			}
			if ( isset( $o[ 'margin' . $device ] ) ) {
				$s[ 'margin' . $device ] = self::dims( $o[ 'margin' . $device ] );
			}
			if ( isset( $o[ 'align' . $device ] ) ) {
				$s[ 'flex_align_items' . $device ] = $o[ 'align' . $device ];
			}
			if ( isset( $o[ 'justify' . $device ] ) ) {
				$s[ 'flex_justify_content' . $device ] = $o[ 'justify' . $device ];
			}
			if ( isset( $o[ 'wrap' . $device ] ) ) {
				$s[ 'flex_wrap' . $device ] = $o[ 'wrap' . $device ];
			}
		}

		// Containers default to a column with Elementor's kit spacing; be explicit.
		$s += array(
			'flex_direction' => 'column',
			'flex_gap'       => self::gap( 0 ),
			'padding'        => self::dims( 0 ),
		);

		if ( isset( $o['grow'] ) ) {
			$s['_flex_size'] = $o['grow'] ? 'grow' : 'none';
		}
		if ( ! empty( $o['fill'] ) ) {
			$s = array_merge( $s, self::fill() );
		}
		if ( isset( $o['min_h'] ) ) {
			$s['min_height'] = self::px( (int) $o['min_h'] );
		}
		if ( ! empty( $o['surface'] ) ) {
			$s['stx_surface'] = $o['surface'];
		}
		if ( ! empty( $o['sticky'] ) ) {
			$s['stx_sticky']        = $o['sticky'];
			$s['stx_sticky_offset'] = self::px( (int) ( $o['sticky_offset'] ?? 24 ) );
		}
		if ( ! empty( $o['tag'] ) ) {
			$s['html_tag'] = $o['tag'];
		}
		if ( ! empty( $o['bg'] ) ) {
			$s['background_background'] = 'classic';
			$s['background_color']      = $o['bg'];
		}
		if ( isset( $o['radius'] ) ) {
			$s['border_radius'] = self::dims( $o['radius'] );
		}
		if ( ! empty( $o['border'] ) ) {
			// [ widths, colour ] — widths as for dims().
			$s['border_border'] = 'solid';
			$s['border_width']  = self::dims( $o['border'][0] );
			$s['border_color']  = $o['border'][1];
		}
		foreach ( (array) ( $o['hide'] ?? array() ) as $device ) {
			$s[ 'hide_' . $device ] = 'hidden-' . $device;
		}

		if ( ! empty( $o['set'] ) ) {
			$s = array_merge( $s, $o['set'] );
		}

		return array(
			'id'       => self::id(),
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => $s,
			'elements' => array_values( array_filter( $children ) ),
		);
	}

	/**
	 * Widget element.
	 *
	 * @param string $type     Widget name.
	 * @param array  $settings Settings.
	 * @param array  $o        `hide` (devices), `width` (auto|full), `grow` (bool), `fill` (bool).
	 */
	public static function w( string $type, array $settings = array(), array $o = array() ): array {
		foreach ( (array) ( $o['hide'] ?? array() ) as $device ) {
			$settings[ 'hide_' . $device ] = 'hidden-' . $device;
		}

		if ( isset( $o['width'] ) && 'auto' === $o['width'] ) {
			$settings['_element_width'] = 'auto';
		}
		if ( ! empty( $o['grow'] ) ) {
			$settings['_flex_size'] = 'grow';
		}
		if ( ! empty( $o['fill'] ) ) {
			$settings = array_merge( $settings, self::fill() );
		}

		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => $type,
			'isInner'    => false,
			'settings'   => $settings,
			'elements'   => array(),
		);
	}

	/**
	 * Takes the free space in a row and gives it back when space runs out.
	 * Elementor's "Grow" also stops shrinking, so a long text would push its
	 * row wider than the screen.
	 */
	private static function fill(): array {
		return array(
			'_flex_size'   => 'custom',
			'_flex_grow'   => 1,
			'_flex_shrink' => 1,
		);
	}

	/**
	 * Marks nested containers as inner, as the editor does.
	 *
	 * @param array $elements Top-level elements.
	 */
	public static function finalize( array $elements ): array {
		$walk = static function ( array $nodes, int $depth ) use ( &$walk ): array {
			foreach ( $nodes as &$node ) {
				if ( 'container' === $node['elType'] ) {
					$node['isInner'] = $depth > 0;
				}
				$node['elements'] = $walk( $node['elements'], $depth + 1 );
			}
			unset( $node );

			return $nodes;
		};

		return $walk( array_values( $elements ), 0 );
	}

	/* ---------------------------------------------------------------------
	 * Value shapes
	 * ------------------------------------------------------------------- */

	/**
	 * @param int|float $size Pixels.
	 */
	public static function px( $size ): array {
		return array(
			'unit'  => 'px',
			'size'  => $size,
			'sizes' => array(),
		);
	}

	/**
	 * Slider value from `64` (percent) or `"360px"`.
	 *
	 * @param int|string $value Value (`auto` for content width).
	 */
	public static function size( $value ): array {
		if ( 'auto' === $value ) {
			// Elementor's "custom" unit: prints `auto` as-is and stays editable in the panel.
			return array(
				'unit'  => 'custom',
				'size'  => 'auto',
				'sizes' => array(),
			);
		}

		if ( is_string( $value ) && preg_match( '/^(\d+(?:\.\d+)?)(px|%|vw|ch|em|rem)$/', $value, $m ) ) {
			return array(
				'unit'  => $m[2],
				'size'  => (float) $m[1],
				'sizes' => array(),
			);
		}

		return array(
			'unit'  => '%',
			'size'  => (float) $value,
			'sizes' => array(),
		);
	}

	/**
	 * Dimensions from `12`, `[ 12, 20 ]` (block, inline) or `[ t, r, b, l ]`.
	 *
	 * @param int|array $v Value.
	 */
	public static function dims( $v ): array {
		if ( ! is_array( $v ) ) {
			$v = array( $v, $v, $v, $v );
		} elseif ( 2 === count( $v ) ) {
			$v = array( $v[0], $v[1], $v[0], $v[1] );
		} elseif ( 3 === count( $v ) ) {
			$v = array( $v[0], $v[1], $v[2], $v[1] );
		}

		return array(
			'unit'     => 'px',
			'top'      => (string) $v[0],
			'right'    => (string) $v[1],
			'bottom'   => (string) $v[2],
			'left'     => (string) $v[3],
			'isLinked' => count( array_unique( array_map( 'strval', $v ) ) ) === 1,
		);
	}

	/**
	 * Flexbox gap (row and column).
	 *
	 * @param int|array $v Gap or [ row, column ].
	 */
	public static function gap( $v ): array {
		$row    = is_array( $v ) ? $v[0] : $v;
		$column = is_array( $v ) ? $v[1] : $v;

		return array(
			'unit'     => 'px',
			'size'     => $column,
			'column'   => (string) $column,
			'row'      => (string) $row,
			'isLinked' => (string) $row === (string) $column,
		);
	}

	/** Physical "start" side for widgets with left/right alignment options. */
	public static function start(): string {
		return Site::is_rtl() ? 'right' : 'left';
	}
}
