<?php
/**
 * Builds the inline CSS that turns settings into CSS custom properties.
 *
 * Default design tokens live in bottom-nav.css and already point at the
 * Studiare theme variables; this class only emits what the admin changed
 * (non-empty colors, sizes, fonts) plus the breakpoint media queries.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Bottom_Nav;

defined( 'ABSPATH' ) || exit;

final class Style_Vars {

	/**
	 * @param array $settings Module settings.
	 */
	public static function inline_css( array $settings ): string {
		$layout     = $settings['layout'];
		$breakpoint = (int) $layout['breakpoint'];

		$vars = self::base_vars( $settings ) + self::color_vars( $settings['colors'], 'l' );
		if ( 'auto' === $settings['dark']['mode'] ) {
			$vars += self::color_vars( $settings['dark']['colors'], 'd' );
		}

		// Sheets are siblings of the bar, so they receive the same tokens.
		$css = '.stx-bn,.stx-sheet{' . self::declarations( $vars ) . '}';

		// Reserved space below the page content, also used to lift theme elements.
		$css .= sprintf(
			'@media (max-width:%1$.2fpx){:root{--stx-bn-space:calc(%2$dpx + %3$dpx + env(safe-area-inset-bottom, 0px))}}',
			$breakpoint - 0.02,
			(int) $layout['height'],
			Styles::is_floating( $settings['style'] ) ? (int) $layout['offset'] : 0
		);

		$css .= self::theme_integration_css( $settings, $breakpoint );
		$css .= sprintf( '@media (min-width:%dpx){.stx-bn,.stx-bn-spacer,.stx-sheet{display:none!important}}', $breakpoint );

		return $css;
	}

	/**
	 * Size, typography and motion tokens.
	 *
	 * @param array $settings Module settings.
	 * @return array<string, string>
	 */
	public static function base_vars( array $settings ): array {
		$layout     = $settings['layout'];
		$typography = $settings['typography'];

		return array(
			'--stx-bn-h'           => (int) $layout['height'] . 'px',
			'--stx-bn-icon'        => (int) $layout['icon_size'] . 'px',
			'--stx-bn-radius'      => (int) $layout['radius'] . 'px',
			'--stx-bn-offset'      => (int) $layout['offset'] . 'px',
			'--stx-bn-max-w'       => (int) $layout['max_width'] . 'px',
			'--stx-bn-stroke'      => (string) (float) $settings['icon_stroke'],
			'--stx-bn-font'        => self::font_stack( $typography ),
			'--stx-bn-font-size'   => (int) $typography['font_size'] . 'px',
			'--stx-bn-font-weight' => (string) $typography['font_weight'],
			'--stx-bn-z'           => (string) (int) $settings['behavior']['z_index'],
		);
	}

	/**
	 * Only colors the admin actually set; empty means "use the theme default".
	 *
	 * @param array  $colors Color slot => value.
	 * @param string $scheme `l` (light) or `d` (dark) — see Schema::COLOR_SLOTS.
	 * @return array<string, string>
	 */
	public static function color_vars( array $colors, string $scheme ): array {
		$vars = array();
		foreach ( Schema::COLOR_SLOTS as $slot ) {
			if ( ! empty( $colors[ $slot ] ) ) {
				$vars[ "--stx-bn-{$scheme}-{$slot}" ] = $colors[ $slot ];
			}
		}

		return $vars;
	}

	/**
	 * Font stack for labels. Theme stacks end with Studiare's own fallback
	 * (`--fallback-font`); if the theme variable is missing the whole value is
	 * invalid and the bar simply inherits the page font.
	 *
	 * @param array $typography Typography settings.
	 */
	private static function font_stack( array $typography ): string {
		$fallback = 'var(--fallback-font, Tahoma, Arial, sans-serif)';

		switch ( $typography['font_source'] ) {
			case 'theme_menu':
				return 'var(--menu_heading-font-family, var(--font_body-font-family)), ' . $fallback;
			case 'inherit':
				return 'inherit';
			case 'custom':
				if ( '' !== $typography['font_family'] ) {
					return $typography['font_family'] . ', Tahoma, Arial, sans-serif';
				}
		}

		return 'var(--font_body-font-family), ' . $fallback;
	}

	/**
	 * Keeps Studiare's own fixed elements clear of the bar.
	 *
	 * @param array $settings   Module settings.
	 * @param int   $breakpoint Breakpoint in px.
	 */
	private static function theme_integration_css( array $settings, int $breakpoint ): string {
		$behavior = $settings['behavior'];
		$rules    = '';

		if ( $behavior['lift_fixed_elements'] ) {
			$rules .= 'body.stx-bn-on .sc_studi_btm_addtocart_fixed_btn_holder_container.sc_add_to_cart_fixed_active{bottom:var(--stx-bn-space)!important;transition:bottom .3s ease}';
			$rules .= 'body.stx-bn-on.stx-bn-is-hidden .sc_studi_btm_addtocart_fixed_btn_holder_container.sc_add_to_cart_fixed_active{bottom:0!important}';
			$rules .= 'body.stx-bn-on .studi_custom_floating_btn,body.stx-bn-on a.swss_floting_ticket{margin-bottom:var(--stx-bn-space)}';
		}

		if ( $behavior['hide_theme_back_to_top'] ) {
			$rules .= 'body.stx-bn-on #back-to-top{display:none!important}';
		}

		return '' === $rules ? '' : sprintf( '@media (max-width:%.2fpx){%s}', $breakpoint - 0.02, $rules );
	}

	/**
	 * @param array<string, string> $vars Custom property => value.
	 */
	private static function declarations( array $vars ): string {
		$css = '';
		foreach ( $vars as $name => $value ) {
			$css .= $name . ':' . $value . ';';
		}

		return $css;
	}
}
