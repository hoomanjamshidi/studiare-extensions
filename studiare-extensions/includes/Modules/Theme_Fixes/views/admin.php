<?php
/**
 * Settings panel for the theme fixes: one switch per fix, with a note on
 * whether the theme feature it repairs is in use on this site.
 *
 * @var \StudiareExt\Modules\Theme_Fixes\Module $module
 *
 * @package StudiareExt
 */

use StudiareExt\Admin\Fields;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Modules\Theme_Fixes\Fixes;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$ui_icon = static function ( string $key ): string {
	return Icon_Library::svg( 'phosphor-duotone', $key, false, 'stx-ico' );
};
?>
<div class="stx-module" data-stx-module="theme_fixes">

	<section class="stx-module-head">
		<span class="stx-module-head__icon" aria-hidden="true"><?php echo $ui_icon( 'wrench' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<div class="stx-module-head__text">
			<h1><?php echo esc_html( $module->title() ); ?></h1>
			<p><?php echo esc_html( $module->description() ); ?></p>
		</div>
		<label class="stx-module-head__switch">
			<span data-stx-show-if="enabled"><?php esc_html_e( 'Active', 'studiare-extensions' ); ?></span>
			<span data-stx-show-if="!enabled"><?php esc_html_e( 'Inactive', 'studiare-extensions' ); ?></span>
			<span class="stx-switch stx-switch--lg">
				<input type="checkbox" data-stx-bind="enabled" aria-label="<?php esc_attr_e( 'Enable the theme fixes', 'studiare-extensions' ); ?>">
				<span class="stx-switch__track" aria-hidden="true"></span>
			</span>
		</label>
	</section>

	<div class="stx-card">
		<header class="stx-card__head">
			<h2><?php esc_html_e( 'Fixes', 'studiare-extensions' ); ?></h2>
			<p><?php esc_html_e( 'The theme\'s files stay untouched, so the fixes keep working after theme updates. If an update repairs a problem itself, switch its fix off.', 'studiare-extensions' ); ?></p>
		</header>

		<div class="stx-fields">
			<?php
			foreach ( Fixes::all() as $fix_id => $fix ) {
				Fields::toggle(
					'fixes.' . $fix_id,
					$fix->title(),
					array(
						'help' => $fix->description(),
						'chip' => $fix->in_use()
							? array( __( 'Used on this site', 'studiare-extensions' ), 'accent' )
							: array( __( 'The theme feature is off', 'studiare-extensions' ), '' ),
					)
				);
			}
			?>
		</div>
	</div>
</div>
