<?php
/**
 * Schema-driven settings sanitizer.
 *
 * Modules describe their settings as a schema:
 *
 *     array(
 *         'enabled' => array( 'type' => 'bool' ),
 *         'height'  => array( 'type' => 'int', 'min' => 48, 'max' => 96 ),
 *         'style'   => array( 'type' => 'enum', 'options' => array( 'a', 'b' ) ),
 *         'colors'  => array( 'type' => 'group', 'fields' => array( ... ) ),
 *         'items'   => array( 'type' => 'list', 'max' => 8, 'fields' => array( ... ), 'defaults' => array( ... ) ),
 *         'types'   => array( 'type' => 'key_list' ),
 *     )
 *
 * Unknown keys are dropped and invalid values fall back to the default, so
 * whatever reaches the database always matches the schema.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Core;

defined( 'ABSPATH' ) || exit;

final class Sanitizer {

	/**
	 * @param array $schema   Field definitions keyed by setting key.
	 * @param array $input    Untrusted input.
	 * @param array $defaults Defaults with the same shape as the schema.
	 */
	public static function apply( array $schema, array $input, array $defaults ): array {
		$clean = array();

		foreach ( $schema as $key => $field ) {
			$default = $defaults[ $key ] ?? null;

			if ( ! array_key_exists( $key, $input ) ) {
				$clean[ $key ] = $default;
				continue;
			}

			$clean[ $key ] = self::field( $field, $input[ $key ], $default );
		}

		return $clean;
	}

	/**
	 * Sanitizes a single value according to its field definition.
	 *
	 * @param array $field   Field definition.
	 * @param mixed $value   Raw value.
	 * @param mixed $fallback Fallback when the value is invalid.
	 * @return mixed
	 */
	public static function field( array $field, $value, $fallback ) {
		switch ( $field['type'] ) {
			case 'bool':
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );

			case 'int':
				return is_numeric( $value )
					? (int) self::clamp( (int) round( (float) $value ), $field )
					: $fallback;

			case 'float':
				return is_numeric( $value )
					? round( (float) self::clamp( (float) $value, $field ), 2 )
					: $fallback;

			case 'enum':
				return in_array( $value, $field['options'], true ) ? $value : $fallback;

			case 'color':
				return self::color( $value );

			case 'text':
				return self::text( $value, $field['max_length'] ?? 120 );

			case 'textarea':
				return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : $fallback;

			case 'html':
				return is_scalar( $value ) ? wp_kses_post( (string) $value ) : $fallback;

			case 'url':
				return is_scalar( $value ) ? esc_url_raw( trim( (string) $value ) ) : $fallback;

			case 'key':
				return is_scalar( $value ) ? sanitize_key( (string) $value ) : $fallback;

			case 'css_class':
				return self::css_class( $value );

			case 'css_selector':
				return self::css_selector( $value );

			case 'font_family':
				return self::font_family( $value );

			case 'id_list':
				return self::id_list( $value );

			case 'key_list':
				return self::key_list( $value );

			case 'svg':
				return is_scalar( $value ) ? Icon_Library::sanitize_svg( (string) $value ) : $fallback;

			case 'group':
				return self::apply( $field['fields'], is_array( $value ) ? $value : array(), is_array( $fallback ) ? $fallback : array() );

			case 'list':
				return self::items( $field, $value );
		}

		return $fallback;
	}

	/**
	 * Sanitizes a list of structured items (e.g. navigation buttons).
	 *
	 * @param array $field List definition: `fields`, `defaults` (per item) and optional `max`.
	 * @param mixed $value Raw list.
	 */
	private static function items( array $field, $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$items = array();
		foreach ( array_values( $value ) as $item ) {
			if ( is_array( $item ) ) {
				$items[] = self::apply( $field['fields'], $item, $field['defaults'] );
			}
		}

		return isset( $field['max'] ) ? array_slice( $items, 0, (int) $field['max'] ) : $items;
	}

	/**
	 * @param int|float $value Number to clamp.
	 * @param array     $field Field with optional `min` / `max`.
	 * @return int|float
	 */
	private static function clamp( $value, array $field ) {
		if ( isset( $field['min'] ) ) {
			$value = max( $field['min'], $value );
		}
		if ( isset( $field['max'] ) ) {
			$value = min( $field['max'], $value );
		}

		return $value;
	}

	/**
	 * Accepts hex (#rgb, #rrggbb, #rrggbbaa), rgb()/rgba()/hsl()/hsla() and
	 * `transparent`. An empty string means "inherit from the theme".
	 *
	 * @param mixed $value Raw color.
	 */
	public static function color( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = strtolower( trim( (string) $value ) );

		if ( '' === $value || 'transparent' === $value ) {
			return $value;
		}

		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9.%,\s\/deg]+\)$/', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * @param mixed $value      Raw text.
	 * @param int   $max_length Maximum characters kept.
	 */
	private static function text( $value, int $max_length ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$text = sanitize_text_field( (string) $value );

		return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max_length ) : substr( $text, 0, $max_length );
	}

	/**
	 * Keeps a space separated list of safe class names (e.g. `fal fa-home`).
	 *
	 * @param mixed $value Raw class list.
	 */
	private static function css_class( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$classes = preg_split( '/\s+/', trim( (string) $value ) );
		$classes = array_filter( array_map( 'sanitize_html_class', (array) $classes ) );

		return implode( ' ', array_slice( $classes, 0, 6 ) );
	}

	/**
	 * CSS selectors are only ever passed to `document.querySelector`, so we
	 * strip markup/control characters and cap the length.
	 *
	 * @param mixed $value Raw selector.
	 */
	private static function css_selector( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$selector = preg_replace( '/[<>{};\\\\\r\n\t]/', '', (string) $value );

		return substr( trim( $selector ), 0, 200 );
	}

	/**
	 * @param mixed $value Raw font-family list, e.g. `IRANSansX, Tahoma`.
	 */
	private static function font_family( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$family = preg_replace( '/[^\p{L}\p{N}\s,\'"_\-]/u', '', (string) $value );

		return substr( trim( (string) $family ), 0, 120 );
	}

	/**
	 * Normalizes a comma separated list of post IDs to `12,34`.
	 *
	 * @param mixed $value Raw list.
	 */
	private static function id_list( $value ): string {
		if ( is_array( $value ) ) {
			$value = implode( ',', $value );
		}
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );

		return implode( ',', array_unique( $ids ) );
	}

	/**
	 * Keeps a list of unique machine keys, e.g. post type names.
	 *
	 * @param mixed $value Raw list.
	 * @return string[]
	 */
	private static function key_list( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$keys = array();
		foreach ( $value as $key ) {
			if ( is_scalar( $key ) ) {
				$keys[] = sanitize_key( (string) $key );
			}
		}

		return array_values( array_unique( array_filter( $keys ) ) );
	}
}
