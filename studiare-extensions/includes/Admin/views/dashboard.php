<?php
/**
 * Dashboard: one card per module with an on/off switch.
 *
 * @var \StudiareExt\Admin\Admin                 $admin
 * @var array<string, \StudiareExt\Core\Module> $modules
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.
?>
<section class="stx-hero">
	<div class="stx-hero__text">
		<h1><?php esc_html_e( 'Welcome to Studiare Extensions', 'studiare-extensions' ); ?></h1>
		<p><?php esc_html_e( 'Turn features on or off, then fine-tune each one. Everything follows your Studiare colours and fonts by default.', 'studiare-extensions' ); ?></p>
	</div>
	<div class="stx-hero__art" aria-hidden="true"><?php echo $admin->icon( 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
</section>

<?php if ( ! Theme_Bridge::is_active() ) : ?>
	<div class="stx-notice stx-notice--warning">
		<?php echo $admin->icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p><?php esc_html_e( 'The Studiare theme is not active. Features still work, but they fall back to neutral colours and cannot use Studiare-specific integrations (mobile menu, mini cart, dark mode).', 'studiare-extensions' ); ?></p>
	</div>
<?php endif; ?>

<div class="stx-module-grid">
	<?php foreach ( $modules as $item ) : ?>
		<article class="stx-module-card">
			<div class="stx-module-card__head">
				<span class="stx-module-card__icon" aria-hidden="true"><?php echo $admin->icon( $item->icon() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<label class="stx-switch" title="<?php esc_attr_e( 'Enable', 'studiare-extensions' ); ?>">
					<input type="checkbox" data-stx-module-toggle="<?php echo esc_attr( $item->id() ); ?>" <?php checked( $item->is_enabled() ); ?> aria-label="<?php echo esc_attr( sprintf( /* translators: %s: feature name. */ __( 'Enable %s', 'studiare-extensions' ), $item->title() ) ); ?>">
					<span class="stx-switch__track" aria-hidden="true"></span>
				</label>
			</div>
			<h2><?php echo esc_html( $item->title() ); ?></h2>
			<p><?php echo esc_html( $item->description() ); ?></p>
			<a class="stx-btn stx-btn--soft" href="<?php echo esc_url( $admin->page_url( $item ) ); ?>">
				<?php esc_html_e( 'Customize', 'studiare-extensions' ); ?>
				<?php echo $admin->icon( 'settings' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</article>
	<?php endforeach; ?>

	<article class="stx-module-card stx-module-card--soon" aria-disabled="true">
		<div class="stx-module-card__head">
			<span class="stx-module-card__icon" aria-hidden="true"><?php echo $admin->icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
		<h2><?php esc_html_e( 'More features are on the way', 'studiare-extensions' ); ?></h2>
		<p><?php esc_html_e( 'New tools will appear here as they are added to the plugin.', 'studiare-extensions' ); ?></p>
	</article>
</div>
