<?php
/**
 * Settings panel of the audio products module: the player on the product
 * page, the free preview, the account tab and the colour. The phone preview
 * prints the real front-end view (player.php) with a made-up album;
 * audio-products-admin.js keeps it in step with the settings.
 *
 * @var \StudiareExt\Modules\Audio_Products\Module $module
 *
 * @package StudiareExt
 */

use StudiareExt\Admin\Fields;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Persian;
use StudiareExt\Core\Site;
use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Audio_Products\Player;
use StudiareExt\Modules\Audio_Products\Schema;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$settings = $module->settings();
$palette  = Theme_Bridge::palette();

// Places of the "My audio" tab: automatic, or counted from the top of the account menu.
$menu_positions = array( 0 => __( 'Automatic (after Downloads)', 'studiare-extensions' ) );
for ( $place = 1; $place <= Schema::MAX_MENU_POSITION; $place++ ) {
	/* translators: %s: place in the menu, 1 = top. */
	$menu_positions[ $place ] = sprintf( __( 'Item %s', 'studiare-extensions' ), Persian::is_site_persian() ? Persian::number( $place ) : $place );
}

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
<div class="stx-module" data-stx-module="audio_products" style="<?php echo esc_attr( $preview_vars ); ?>">

	<section class="stx-module-head">
		<span class="stx-module-head__icon" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor-duotone', 'headphones', false, 'stx-ico' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<div class="stx-module-head__text">
			<h1><?php echo esc_html( $module->title() ); ?></h1>
			<p><?php echo esc_html( $module->description() ); ?></p>
		</div>
		<label class="stx-module-head__switch">
			<span data-stx-show-if="enabled"><?php esc_html_e( 'Active', 'studiare-extensions' ); ?></span>
			<span data-stx-show-if="!enabled"><?php esc_html_e( 'Inactive', 'studiare-extensions' ); ?></span>
			<span class="stx-switch stx-switch--lg">
				<input type="checkbox" data-stx-bind="enabled" aria-label="<?php esc_attr_e( 'Enable audio products', 'studiare-extensions' ); ?>">
				<span class="stx-switch__track" aria-hidden="true"></span>
			</span>
		</label>
	</section>

	<div class="stx-module-body">
		<div class="stx-settings">

			<?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
				<div class="stx-notice"><?php esc_html_e( 'Audio products are WooCommerce products. Activate WooCommerce to use them.', 'studiare-extensions' ); ?></div>
			<?php endif; ?>

			<div class="stx-card">
				<header class="stx-card__head">
					<h2><?php esc_html_e( 'Player on the product page', 'studiare-extensions' ); ?></h2>
					<p><?php esc_html_e( 'Open a product in the editor and add its tracks in the "Studiare+ audio" box. The player shows in the product description; products without audio stay as they are. Files are sent by the site only to visitors allowed to hear them, so their address never appears on the page.', 'studiare-extensions' ); ?></p>
				</header>
				<div class="stx-fields">
					<?php
					Fields::segmented(
						'player.position',
						__( 'Place', 'studiare-extensions' ),
						array(
							'top'    => __( 'Above the description', 'studiare-extensions' ),
							'bottom' => __( 'After the description', 'studiare-extensions' ),
						)
					);
					Fields::text( 'player.label', __( 'Text above the title', 'studiare-extensions' ), array( 'placeholder' => Schema::defaults()['player']['label'] ) );
					Fields::segmented(
						'player.look',
						__( 'Look', 'studiare-extensions' ),
						array(
							'soft'   => __( 'Soft (follows dark mode)', 'studiare-extensions' ),
							'studio' => __( 'Studio (always dark)', 'studiare-extensions' ),
						)
					);
					Fields::toggle( 'player.speed', __( 'Playback speed button', 'studiare-extensions' ) );
					Fields::toggle( 'player.resume', __( 'Continue where the listener stopped', 'studiare-extensions' ), array( 'help' => __( 'Remembered in the visitor\'s own browser; nothing is sent to the site.', 'studiare-extensions' ) ) );
					?>
				</div>
			</div>

			<div class="stx-card">
				<header class="stx-card__head">
					<h2><?php esc_html_e( 'Free preview', 'studiare-extensions' ); ?></h2>
					<p><?php esc_html_e( 'Each track chooses its own preview in the product box: its first seconds, a separate short file, the whole track, or none. Visitors who have not bought the product never receive more than the preview.', 'studiare-extensions' ); ?></p>
				</header>
				<div class="stx-fields">
					<?php
					Fields::range( 'preview.seconds', __( 'Default preview length', 'studiare-extensions' ), Schema::MIN_CLIP, Schema::MAX_CLIP, 5, __( 's', 'studiare-extensions' ), array( 'help' => __( 'Used for new tracks and for the private audio of Studiare lessons.', 'studiare-extensions' ) ) );
					Fields::toggle( 'preview.lessons', __( 'Preview of private Studiare lessons', 'studiare-extensions' ), array( 'help' => __( 'Lesson audio added to a player plays its first seconds for visitors who have not bought the course (MP3 files on this site only).', 'studiare-extensions' ) ) );
					Fields::toggle( 'preview.cta', __( 'Buy button in the player', 'studiare-extensions' ), array( 'help' => __( 'Shown under the list and when a preview ends.', 'studiare-extensions' ) ) );
					?>
				</div>
			</div>

			<div class="stx-card">
				<header class="stx-card__head">
					<h2><?php esc_html_e( 'Customer account', 'studiare-extensions' ); ?></h2>
					<p><?php esc_html_e( 'A tab in the WooCommerce account page with the player of every audio product the customer bought. It only shows to customers who own one.', 'studiare-extensions' ); ?></p>
				</header>
				<div class="stx-fields">
					<?php
					Fields::toggle( 'account.enabled', __( 'Show the tab', 'studiare-extensions' ) );
					Fields::text(
						'account.label',
						__( 'Tab name', 'studiare-extensions' ),
						array(
							'placeholder' => Schema::defaults()['account']['label'],
							'show_if'     => 'account.enabled',
						)
					);
					Fields::select(
						'account.position',
						__( 'Place in the account menu', 'studiare-extensions' ),
						$menu_positions,
						array(
							'show_if' => 'account.enabled',
							'help'    => __( 'Counted from the top of the account menu (all of Studiare\'s account menus follow it). "Log out" always stays last.', 'studiare-extensions' ),
						)
					);
					Fields::toggle(
						'account.courses',
						__( 'Open audio courses here from "My courses"', 'studiare-extensions' ),
						array(
							'show_if' => 'account.enabled',
							'help'    => __( 'The "Study and learning" button of Studiare\'s "My courses" tab opens the course\'s player in this tab instead of an empty lesson list. Only for courses with audio and no lessons; courses with lessons open as before.', 'studiare-extensions' ),
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
						<label class="stx-segmented__option"><input type="radio" name="stx-ap-preview-scheme" value="light" data-stx-preview-scheme checked><span><?php esc_html_e( 'Light', 'studiare-extensions' ); ?></span></label>
						<label class="stx-segmented__option"><input type="radio" name="stx-ap-preview-scheme" value="dark" data-stx-preview-scheme><span><?php esc_html_e( 'Dark', 'studiare-extensions' ); ?></span></label>
					</div>
				</div>

				<div class="stx-phone">
					<div class="stx-phone__notch" aria-hidden="true"></div>
					<div class="stx-phone__screen" data-stx-preview-screen dir="<?php echo esc_attr( Site::direction() ); ?>">
						<div class="stx-ap-stage" inert>
							<?php
							$player = Player::sample();
							$style  = $module->color_vars();
							$place  = 'preview';
							require __DIR__ . '/player.php';
							?>
						</div>
					</div>
				</div>

				<p class="stx-preview__caption"><?php esc_html_e( 'The preview player does not play. On your site the Studiare font is used.', 'studiare-extensions' ); ?></p>
			</div>
		</aside>
	</div>
</div>
