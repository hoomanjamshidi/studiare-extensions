<?php
/**
 * "Save as PDF" button: a small button above the text (`top`) or a box
 * after it (`bottom`). article-tools.js opens the browser's print window,
 * where the visitor picks "Save as PDF"; without JavaScript it is hidden.
 *
 * @var string $place `top` or `bottom`.
 * @var string $label Button text.
 * @var string $style Inline colour tokens.
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$button = sprintf(
	'<button type="button" class="stx-at-pdf%1$s" data-stx-pdf title="%2$s">%3$s<span>%4$s</span></button>',
	'bottom' === $place ? ' stx-at-pdf--solid' : '',
	esc_attr__( 'Opens the print window. Choose "Save as PDF" as the printer.', 'studiare-extensions' ),
	Icon_Library::svg( 'phosphor', 'notes' ),
	esc_html( $label )
);
?>
<?php if ( 'top' === $place ) : ?>
	<div class="stx-at-bar" style="<?php echo esc_attr( $style ); ?>"><?php echo $button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></div>
<?php else : ?>
	<div class="stx-at-cta" style="<?php echo esc_attr( $style ); ?>">
		<div class="stx-at-cta__box">
			<span class="stx-at-cta__icon" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'notes', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
			<span class="stx-at-cta__text">
				<span class="stx-at-cta__title"><?php esc_html_e( 'Keep a copy of this article', 'studiare-extensions' ); ?></span>
				<span class="stx-at-cta__desc"><?php esc_html_e( 'Save it as a PDF to read offline, share or print.', 'studiare-extensions' ); ?></span>
			</span>
			<?php echo $button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
		</div>
	</div>
<?php endif; ?>
<noscript><style>.stx-at-bar,.stx-at-cta{display:none!important}</style></noscript>
