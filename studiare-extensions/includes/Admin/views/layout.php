<?php
/**
 * Admin shell: top bar, sidebar with every module, and the current panel.
 *
 * @var \StudiareExt\Admin\Admin                 $admin
 * @var array<string, \StudiareExt\Core\Module> $modules
 * @var \StudiareExt\Core\Module|null            $module  Current module (null on the dashboard).
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Theme_Bridge;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.
?>
<div class="stx-admin" id="stx-admin">
	<header class="stx-topbar">
		<a class="stx-brand" href="<?php echo esc_url( $admin->page_url() ); ?>">
			<span class="stx-brand__mark" aria-hidden="true"><?php echo $admin->icon( 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
			<span class="stx-brand__text">
				<strong><?php esc_html_e( 'Studiare Extensions', 'studiare-extensions' ); ?></strong>
				<span><?php esc_html_e( 'Extra features for the Studiare theme', 'studiare-extensions' ); ?> · v<?php echo esc_html( STUDIARE_EXT_VERSION ); ?></span>
			</span>
		</a>

		<?php if ( $module ) : ?>
			<div class="stx-topbar__actions">
				<span class="stx-dirty" data-stx-dirty hidden><?php esc_html_e( 'Unsaved changes', 'studiare-extensions' ); ?></span>
				<button type="button" class="stx-btn stx-btn--ghost" data-stx-reset>
					<?php esc_html_e( 'Reset', 'studiare-extensions' ); ?>
				</button>
				<button type="button" class="stx-btn stx-btn--primary" data-stx-save disabled>
					<span class="stx-btn__spinner" aria-hidden="true"></span>
					<span data-stx-save-label><?php esc_html_e( 'Save changes', 'studiare-extensions' ); ?></span>
				</button>
			</div>
		<?php endif; ?>
	</header>

	<div class="stx-shell">
		<aside class="stx-sidebar" aria-label="<?php esc_attr_e( 'Features', 'studiare-extensions' ); ?>">
			<nav class="stx-sidenav">
				<a class="stx-sidenav__link<?php echo $module ? '' : ' is-current'; ?>" href="<?php echo esc_url( $admin->page_url() ); ?>"<?php echo $module ? '' : ' aria-current="page"'; ?>>
					<?php echo $admin->icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Dashboard', 'studiare-extensions' ); ?></span>
				</a>

				<span class="stx-sidenav__heading"><?php esc_html_e( 'Features', 'studiare-extensions' ); ?></span>

				<?php foreach ( $modules as $item ) : ?>
					<?php $is_current = $module && $module->id() === $item->id(); ?>
					<a class="stx-sidenav__link<?php echo $is_current ? ' is-current' : ''; ?>" href="<?php echo esc_url( $admin->page_url( $item ) ); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>>
						<?php echo $admin->icon( $item->icon() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $item->title() ); ?></span>
						<i class="stx-status-dot<?php echo $item->is_enabled() ? ' is-on' : ''; ?>" data-stx-status="<?php echo esc_attr( $item->id() ); ?>" aria-hidden="true"></i>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="stx-sidebar__foot">
				<span class="stx-theme-chip<?php echo Theme_Bridge::is_active() ? ' is-ok' : ''; ?>">
					<i aria-hidden="true"></i>
					<?php
					echo Theme_Bridge::is_active()
						? esc_html__( 'Studiare theme detected', 'studiare-extensions' )
						: esc_html__( 'Studiare theme not active', 'studiare-extensions' );
					?>
				</span>
			</div>
		</aside>

		<main class="stx-main">
			<?php
			if ( $module ) {
				$module->render_admin();
			} else {
				require __DIR__ . '/dashboard.php';
			}
			?>
		</main>
	</div>

	<div class="stx-toasts" aria-live="polite" aria-atomic="false"></div>
</div>
