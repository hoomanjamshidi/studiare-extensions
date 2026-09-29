<?php
/**
 * Front-end markup of the support button, printed in the footer.
 *
 * The menu is a native <details>, so it opens and its links work without
 * JavaScript. renderWidget() in support-button-admin.js mirrors this markup
 * for the admin preview; change them together.
 *
 * @var array $view View model from Frontend::view().
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$brand_style = static function ( string $brand ): string {
	return '' === $brand ? '' : ' style="--stx-sb-brand:' . esc_attr( $brand ) . '"';
};

$link_attrs = static function ( array $channel ): string {
	return $channel['external'] ? ' target="_blank" rel="noopener"' : '';
};
?>
<div class="<?php echo esc_attr( $view['classes'] ); ?>" id="stx-support" style="<?php echo esc_attr( $view['style'] ); ?>" data-stx-sb<?php echo $view['greeting'] ? ' data-greeting-delay="' . esc_attr( (string) $view['greeting']['delay'] ) . '"' : ''; ?>>
	<?php if ( $view['single'] ) : ?>
		<?php $channel = $view['channels'][0]; ?>
		<a class="stx-sb__toggle" href="<?php echo esc_url( $channel['url'], array( 'http', 'https', 'tel', 'mailto' ) ); ?>"<?php echo $link_attrs( $channel ) . $brand_style( $view['brand'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the closures. ?> data-channel="<?php echo esc_attr( $channel['id'] ); ?>">
			<span class="stx-sb__icons" aria-hidden="true"><span class="stx-sb__icon"><?php echo $view['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span></span>
			<span class="stx-sb__label"><?php echo esc_html( $view['label'] ); ?></span>
		</a>
	<?php else : ?>
		<details class="stx-sb__menu">
			<summary class="stx-sb__toggle" id="stx-support-toggle">
				<span class="stx-sb__icons" aria-hidden="true">
					<span class="stx-sb__icon"><?php echo $view['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
					<span class="stx-sb__icon stx-sb__icon--close"><?php echo $view['close']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
				</span>
				<span class="stx-sb__label"><?php echo esc_html( $view['label'] ); ?></span>
			</summary>

			<div class="stx-sb__panel">
				<?php if ( '' !== $view['header']['title'] || '' !== $view['header']['subtitle'] ) : ?>
					<div class="stx-sb__head">
						<?php if ( '' !== $view['header']['title'] ) : ?>
							<p class="stx-sb__title"><?php echo esc_html( $view['header']['title'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $view['header']['subtitle'] ) : ?>
							<p class="stx-sb__subtitle"><?php echo esc_html( $view['header']['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<ul class="stx-sb__list" style="--stx-sb-count:<?php echo count( $view['channels'] ); ?>">
					<?php foreach ( $view['channels'] as $index => $channel ) : ?>
						<li class="stx-sb__item" style="--stx-sb-i:<?php echo (int) $index; ?>">
							<a class="stx-sb__channel" href="<?php echo esc_url( $channel['url'], array( 'http', 'https', 'tel', 'mailto' ) ); ?>"<?php echo $link_attrs( $channel ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attributes. ?> data-channel="<?php echo esc_attr( $channel['id'] ); ?>">
								<span class="stx-sb__glyph" aria-hidden="true"<?php echo $brand_style( $channel['brand'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the closure. ?>><?php echo $channel['glyph']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
								<span class="stx-sb__text">
									<span class="stx-sb__name"><?php echo esc_html( $channel['label'] ); ?></span>
									<?php if ( '' !== $channel['note'] ) : ?>
										<span class="stx-sb__note"><?php echo esc_html( $channel['note'] ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</details>
	<?php endif; ?>

	<?php if ( $view['greeting'] ) : ?>
		<div class="stx-sb__greeting" role="status" hidden>
			<button type="button" class="stx-sb__greeting-text" data-stx-sb-greeting><?php echo esc_html( $view['greeting']['text'] ); ?></button>
			<button type="button" class="stx-sb__greeting-close" data-stx-sb-dismiss aria-label="<?php esc_attr_e( 'Close', 'studiare-extensions' ); ?>"><?php echo $view['close']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></button>
		</div>
	<?php endif; ?>
</div>
