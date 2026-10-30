<?php
/**
 * One product of the "My audio" tab: a link back to the library above the
 * product's player.
 *
 * @var string $player     Player markup (Module::player()).
 * @var string $back       Address of the library.
 * @var string $back_label The tab's name.
 * @var string $style      Inline colour tokens.
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.
?>
<div class="stx-ap-account" style="<?php echo esc_attr( $style ); ?>">
	<a class="stx-ap-back" href="<?php echo esc_url( $back ); ?>">
		<?php echo Icon_Library::svg( 'phosphor', 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
		<span><?php echo esc_html( $back_label ); ?></span>
	</a>
	<?php echo $player; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in views/player.php. ?>
</div>
