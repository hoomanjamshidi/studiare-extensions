<?php
/**
 * Settings panel for the article tools: the PDF button, the podcast player
 * and their colour. The phone preview prints the real front-end views
 * (podcast.php, pdf.php) with a made-up episode; article-tools-admin.js
 * keeps it in step with the settings.
 *
 * @var \StudiareExt\Modules\Article_Tools\Module $module
 *
 * @package StudiareExt
 */

use StudiareExt\Admin\Fields;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Site;
use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Article_Tools\Podcast;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$ui_icon = static function ( string $key ): string {
	return Icon_Library::svg( 'phosphor-duotone', $key, false, 'stx-ico' );
};

$settings = $module->settings();
$palette  = Theme_Bridge::palette();
$style    = $module->color_vars();

// Studiare's CSS variables, recreated so the preview resolves the same defaults as the site.
$preview_vars = sprintf(
	'--primary_color:%1$s;--secondary_color:%2$s;--font_body-color:%3$s;--dark_primary_color:%4$s;--dark_secondary_color:%5$s;--dark_light_color:%6$s',
	$palette['primary'],
	$palette['secondary'],
	$palette['text'],
	$palette['dark_surface'],
	$palette['dark_bg'],
	$palette['dark_text']
);
?>
<div class="stx-module" data-stx-module="article_tools" style="<?php echo esc_attr( $preview_vars ); ?>">

	<section class="stx-module-head">
		<span class="stx-module-head__icon" aria-hidden="true"><?php echo $ui_icon( 'headphones' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<div class="stx-module-head__text">
			<h1><?php echo esc_html( $module->title() ); ?></h1>
			<p><?php echo esc_html( $module->description() ); ?></p>
		</div>
		<label class="stx-module-head__switch">
			<span data-stx-show-if="enabled"><?php esc_html_e( 'Active', 'studiare-extensions' ); ?></span>
			<span data-stx-show-if="!enabled"><?php esc_html_e( 'Inactive', 'studiare-extensions' ); ?></span>
			<span class="stx-switch stx-switch--lg">
				<input type="checkbox" data-stx-bind="enabled" aria-label="<?php esc_attr_e( 'Enable the article tools', 'studiare-extensions' ); ?>">
				<span class="stx-switch__track" aria-hidden="true"></span>
			</span>
		</label>
	</section>

	<div class="stx-module-body">
		<div class="stx-settings">

			<div class="stx-card">
				<header class="stx-card__head">
					<h2><?php esc_html_e( 'Save as PDF', 'studiare-extensions' ); ?></h2>
					<p><?php esc_html_e( 'The button opens the browser\'s print window, where visitors choose "Save as PDF". Only the article goes on paper: no header, sidebar, comments or footer. Persian text, fonts and pictures come out just as on the site, and the browser\'s own Print command gives the same clean copy.', 'studiare-extensions' ); ?></p>
				</header>
				<div class="stx-fields">
					<?php
					Fields::toggle( 'pdf.enabled', __( 'Show the button on blog posts', 'studiare-extensions' ), array( 'help' => __( 'Authors can hide it on a single post from the "Studiare+ article tools" box of the post editor.', 'studiare-extensions' ) ) );
					Fields::segmented(
						'pdf.position',
						__( 'Place', 'studiare-extensions' ),
						array(
							'top'    => __( 'Above the text', 'studiare-extensions' ),
							'bottom' => __( 'After the text', 'studiare-extensions' ),
							'both'   => __( 'Both', 'studiare-extensions' ),
						),
						array( 'show_if' => 'pdf.enabled' )
					);
					Fields::text(
						'pdf.label',
						__( 'Button text', 'studiare-extensions' ),
						array(
							'placeholder' => __( 'Save as PDF', 'studiare-extensions' ),
							'show_if'     => 'pdf.enabled',
						)
					);
					Fields::toggle(
						'pdf.picture',
						__( 'Featured picture in the PDF', 'studiare-extensions' ),
						array(
							'help'    => __( 'Printed under the title. Visitors who never print do not download it.', 'studiare-extensions' ),
							'show_if' => 'pdf.enabled',
						)
					);
					Fields::toggle(
						'pdf.source',
						__( 'Link to the article at the end', 'studiare-extensions' ),
						array(
							'help'    => __( 'Readers of a shared PDF can find the original post.', 'studiare-extensions' ),
							'show_if' => 'pdf.enabled',
						)
					);
					?>
				</div>
			</div>

			<div class="stx-card">
				<header class="stx-card__head">
					<h2><?php esc_html_e( 'Podcast', 'studiare-extensions' ); ?></h2>
					<p><?php esc_html_e( 'To add the audio version of a post, open the post in the editor and choose the file in the "Studiare+ article tools" box. The player appears at the top of the article; posts without audio stay as they are.', 'studiare-extensions' ); ?></p>
				</header>
				<div class="stx-fields">
					<?php
					Fields::toggle( 'podcast.enabled', __( 'Show the podcast player', 'studiare-extensions' ) );
					Fields::text(
						'podcast.label',
						__( 'Text above the title', 'studiare-extensions' ),
						array(
							'placeholder' => __( 'Listen to this article', 'studiare-extensions' ),
							'show_if'     => 'podcast.enabled',
						)
					);
					Fields::segmented(
						'podcast.look',
						__( 'Look', 'studiare-extensions' ),
						array(
							'soft'   => __( 'Soft (follows dark mode)', 'studiare-extensions' ),
							'studio' => __( 'Studio (always dark)', 'studiare-extensions' ),
						),
						array( 'show_if' => 'podcast.enabled' )
					);
					Fields::toggle( 'podcast.download', __( 'Download button', 'studiare-extensions' ), array( 'show_if' => 'podcast.enabled' ) );
					Fields::toggle( 'podcast.speed', __( 'Playback speed button', 'studiare-extensions' ), array( 'show_if' => 'podcast.enabled' ) );
					Fields::toggle(
						'podcast.resume',
						__( 'Continue where the listener stopped', 'studiare-extensions' ),
						array(
							'help'    => __( 'Remembered in the visitor\'s own browser; nothing is sent to the site.', 'studiare-extensions' ),
							'show_if' => 'podcast.enabled',
						)
					);
					?>
				</div>
			</div>

			<div class="stx-card">
				<header class="stx-card__head">
					<h2><?php esc_html_e( 'Colours', 'studiare-extensions' ); ?></h2>
					<p><?php esc_html_e( 'Leave it empty to follow your Studiare primary colour. The text on the buttons switches between white and black for contrast.', 'studiare-extensions' ); ?></p>
				</header>
				<div class="stx-color-grid">
					<?php Fields::color( 'colors.accent', __( 'Accent', 'studiare-extensions' ), $palette['primary'] ); ?>
				</div>
			</div>
		</div>

		<aside class="stx-preview-col" aria-label="<?php esc_attr_e( 'Live preview', 'studiare-extensions' ); ?>">
			<div class="stx-preview">
				<div class="stx-preview__toolbar">
					<div class="stx-segmented stx-segmented--sm" role="radiogroup" aria-label="<?php esc_attr_e( 'Preview colour scheme', 'studiare-extensions' ); ?>">
						<label class="stx-segmented__option"><input type="radio" name="stx-at-preview-scheme" value="light" data-stx-preview-scheme checked><span><?php esc_html_e( 'Light', 'studiare-extensions' ); ?></span></label>
						<label class="stx-segmented__option"><input type="radio" name="stx-at-preview-scheme" value="dark" data-stx-preview-scheme><span><?php esc_html_e( 'Dark', 'studiare-extensions' ); ?></span></label>
					</div>
				</div>

				<div class="stx-phone">
					<div class="stx-phone__notch" aria-hidden="true"></div>
					<div class="stx-phone__screen" data-stx-preview-screen dir="<?php echo esc_attr( Site::direction() ); ?>">
						<div class="stx-at-stage" inert>
							<span class="stx-at-stage__title" aria-hidden="true"></span>
							<span class="stx-at-stage__line stx-at-stage__line--short" aria-hidden="true"></span>
							<div data-stx-preview-part="bar">
								<?php
								$place = 'top';
								$label = '' !== $settings['pdf']['label'] ? $settings['pdf']['label'] : __( 'Save as PDF', 'studiare-extensions' );
								require __DIR__ . '/pdf.php';
								?>
							</div>
							<div data-stx-preview-part="podcast">
								<?php
								$pod     = Podcast::sample();
								$options = $settings['podcast'];
								require __DIR__ . '/podcast.php';
								?>
							</div>
							<?php for ( $line = 0; $line < 6; $line++ ) : ?>
								<span class="stx-at-stage__line" aria-hidden="true"></span>
							<?php endfor; ?>
							<div data-stx-preview-part="cta">
								<?php
								$place = 'bottom';
								require __DIR__ . '/pdf.php';
								?>
							</div>
						</div>
					</div>
				</div>

				<p class="stx-preview__caption"><?php esc_html_e( 'The preview player does not play. On your site the Studiare font is used.', 'studiare-extensions' ); ?></p>
			</div>
		</aside>
	</div>
</div>
