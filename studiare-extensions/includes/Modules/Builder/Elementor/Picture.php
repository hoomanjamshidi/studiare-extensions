<?php
/**
 * Responsive `<picture>` markup for the slider and the post's featured
 * image: WordPress's own srcset, an optional phone picture (art direction)
 * and the loading hints that PageSpeed looks at.
 *
 * - `high`: the picture most likely to be the Largest Contentful Paint.
 *   Loads right away with `fetchpriority="high"`, and carries the usual
 *   "do not lazy-load" markers, because optimisation plugins (WP Rocket,
 *   LiteSpeed, Smush, Jetpack…) would otherwise lazy-load it and PageSpeed
 *   would report a lazily loaded LCP image.
 * - `eager`: loads right away at normal priority, also skipped by those plugins.
 * - `lazy`: native `loading="lazy"`; slider.js fetches the next slides in
 *   the background once the page has loaded.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor;

defined( 'ABSPATH' ) || exit;

final class Picture {

	/** Widest screen that gets the phone picture (Elementor's default mobile breakpoint). */
	public const PHONE_MAX = 767;

	/**
	 * @param array $media Elementor MEDIA value (`id`, `url`).
	 * @param array $phone Phone MEDIA value, or an empty array.
	 * @param array $o     `loading` (high|eager|lazy), `sizes`, `phone_sizes`,
	 *                     `alt` (used when the picture has no alt text), `class`
	 *                     (on the image), `wrap_class` (on the picture).
	 */
	public static function html( array $media, array $phone, array $o ): string {
		$id   = (int) ( $media['id'] ?? 0 );
		$url  = (string) ( $media['url'] ?? '' );
		$wrap = '<picture class="' . esc_attr( (string) ( $o['wrap_class'] ?? 'stx-sl__pic' ) ) . '">';

		if ( ! $id && '' === $url ) {
			return '';
		}

		$attr = self::loading_attributes( (string) ( $o['loading'] ?? 'lazy' ), (string) ( $o['class'] ?? '' ) );
		$alt  = (string) ( $o['alt'] ?? '' );

		// Placeholder pictures (no attachment), e.g. Elementor's sample image in new widgets.
		if ( ! $id ) {
			$html = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"';
			foreach ( $attr as $name => $value ) {
				$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
			}

			return $wrap . $html . '></picture>';
		}

		$attr['sizes'] = (string) ( $o['sizes'] ?? '100vw' );
		if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
			$attr['alt'] = $alt;
		}

		// Studiare sets `loading="eager"` on every attachment image (its
		// `disable_lazy_load_featured_images` filter), which would download every
		// slide at once, and WordPress prefixes lazy images' sizes with `auto`
		// (see clean_img_tag()). This runs after both and puts back the slider's choices.
		$keep = static function ( array $image_attr ) use ( $attr ): array {
			return array_merge( $image_attr, array_intersect_key( $attr, array_flip( array( 'loading', 'fetchpriority', 'sizes' ) ) ) );
		};
		add_filter( 'wp_get_attachment_image_attributes', $keep, PHP_INT_MAX );
		$img = wp_get_attachment_image( $id, 'full', false, $attr );
		remove_filter( 'wp_get_attachment_image_attributes', $keep, PHP_INT_MAX );

		if ( '' === $img ) {
			return '';
		}

		return $wrap . self::phone_source( $phone, (string) ( $o['phone_sizes'] ?? '100vw' ) ) . $img . '</picture>';
	}

	/**
	 * Undoes what content filters do to our images, which run on the page
	 * content after the widget: Elementor's "Optimized image loading" prepends
	 * `loading` and `fetchpriority` to images that already have them (each
	 * then appears twice, invalid HTML), and WordPress prefixes lazy images'
	 * sizes with `auto`, which makes a picture that was hidden while the page
	 * loaded pick a larger copy than the slider's own `sizes` asks for.
	 * Runs on `wp_content_img_tag` after both.
	 *
	 * @param string $image `<img>` tag.
	 */
	public static function clean_img_tag( $image ) {
		if ( ! is_string( $image ) || ! preg_match( '/stx-(sl|pimage)__img/', $image ) ) {
			return $image;
		}

		foreach ( array( 'loading', 'fetchpriority' ) as $name ) {
			if ( substr_count( $image, ' ' . $name . '=' ) > 1 ) {
				$image = (string) preg_replace( '/\s' . $name . '="[a-z]+"/', '', $image, 1 );
			}
		}

		return str_replace( ' sizes="auto, ', ' sizes="', $image );
	}

	/**
	 * Width / height of a picture as a CSS aspect ratio ("1920 / 600"), or ''.
	 *
	 * @param array $media Elementor MEDIA value.
	 */
	public static function ratio( array $media ): string {
		$id  = (int) ( $media['id'] ?? 0 );
		$src = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;

		return $src && $src[1] && $src[2] ? (int) $src[1] . ' / ' . (int) $src[2] : '';
	}

	/**
	 * `<source>` for phones, when a phone picture is set.
	 *
	 * @param array  $phone Phone MEDIA value.
	 * @param string $sizes Sizes on phones.
	 */
	private static function phone_source( array $phone, string $sizes ): string {
		$id  = (int) ( $phone['id'] ?? 0 );
		$src = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;

		if ( ! $src ) {
			return '';
		}

		$srcset = wp_get_attachment_image_srcset( $id, 'full' );

		return sprintf(
			'<source media="(max-width: %1$dpx)" srcset="%2$s" sizes="%3$s" width="%4$d" height="%5$d">',
			self::PHONE_MAX,
			esc_attr( $srcset ? $srcset : $src[0] ),
			esc_attr( $sizes ),
			(int) $src[1],
			(int) $src[2]
		);
	}

	/**
	 * @param string $loading    high|eager|lazy.
	 * @param string $class_name Image class.
	 * @return array<string, string>
	 */
	private static function loading_attributes( string $loading, string $class_name ): array {
		if ( 'lazy' === $loading ) {
			return array(
				'class'    => $class_name,
				'loading'  => 'lazy',
				'decoding' => 'async',
			);
		}

		$attr = array(
			'class'          => trim( $class_name . ' skip-lazy' ),
			'loading'        => 'eager',
			'data-skip-lazy' => '1',
			'data-no-lazy'   => '1',
		);

		if ( 'high' === $loading ) {
			$attr['fetchpriority'] = 'high';
		}

		return $attr;
	}
}
