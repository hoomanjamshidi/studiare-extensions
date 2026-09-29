<?php
/**
 * Bottom sheet (search, cart, menu or custom content).
 *
 * @var \StudiareExt\Modules\Bottom_Nav\Renderer $renderer
 * @var array                                    $item  Item view model.
 * @var array                                    $sheet Sheet definition.
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$sheet_id = $renderer->sheet_id( $item );
?>
<div class="<?php echo esc_attr( $renderer->sheet_classes( $sheet ) ); ?>" id="<?php echo esc_attr( $sheet_id ); ?>"<?php echo $renderer->sheet_uses_history( $sheet ) ? ' data-stx-history' : ''; ?> hidden>
	<div class="stx-sheet__backdrop" data-stx-close></div>
	<div class="stx-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $sheet_id ); ?>-title" tabindex="-1">
		<div class="stx-sheet__grip" data-stx-drag aria-hidden="true"><span></span></div>
		<div class="stx-sheet__header" data-stx-drag>
			<h2 class="stx-sheet__title" id="<?php echo esc_attr( $sheet_id ); ?>-title"><?php echo esc_html( $sheet['title'] ); ?></h2>
			<button type="button" class="stx-sheet__close" data-stx-close aria-label="<?php esc_attr_e( 'Close', 'studiare-extensions' ); ?>">
				<?php echo $renderer->close_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
			</button>
		</div>
		<div class="stx-sheet__body">
			<?php $renderer->sheet_body( $sheet ); ?>
		</div>
	</div>
</div>
