<?php
/**
 * Bottom navigation bar.
 *
 * @var \StudiareExt\Modules\Bottom_Nav\Renderer $renderer
 * @var array<int, array>                        $items View models.
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.
?>
<nav id="stx-bottom-nav" class="<?php echo esc_attr( $renderer->nav_classes() ); ?>" style="<?php echo esc_attr( $renderer->nav_style() ); ?>" data-count="<?php echo count( $items ); ?>" aria-label="<?php esc_attr_e( 'Mobile navigation', 'studiare-extensions' ); ?>">
	<div class="stx-bn__bar" aria-hidden="true"><span class="stx-bn__indicator"></span></div>
	<ul class="stx-bn__list">
		<?php foreach ( $items as $index => $item ) : ?>
			<?php $item_tag = $renderer->item_tag( $item ); ?>
			<li class="<?php echo esc_attr( $renderer->item_classes( $item ) ); ?>" style="--stx-bn-i:<?php echo (int) $index; ?>">
				<<?php echo tag_escape( $item_tag ); ?><?php echo $renderer->item_attributes( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per attribute. ?>>
					<span class="stx-bn__icon">
						<span class="stx-bn__glyph stx-bn__glyph--base"><?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted/sanitized icon markup. ?></span>
						<?php if ( '' !== $item['icon_active'] ) : ?>
							<span class="stx-bn__glyph stx-bn__glyph--active"><?php echo $item['icon_active']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<?php if ( '' !== $item['icon_alt'] ) : ?>
							<span class="stx-bn__glyph stx-bn__glyph--alt"><?php echo $item['icon_alt']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<?php echo $item['badge']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html(). ?>
					</span>
					<span class="stx-bn__label"><?php echo esc_html( $item['label'] ); ?></span>
				</<?php echo tag_escape( $item_tag ); ?>>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
<div class="stx-bn-spacer" aria-hidden="true"></div>
