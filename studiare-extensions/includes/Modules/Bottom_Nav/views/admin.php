<?php
/**
 * Settings panel for the bottom navigation module.
 *
 * Static structure and every translatable label live here; bottom-nav-admin.js
 * adds behaviour: live previews, the sortable item list (cloned from
 * #stx-item-template) and the icon picker.
 *
 * @var \StudiareExt\Modules\Bottom_Nav\Module $module
 *
 * @package StudiareExt
 */

use StudiareExt\Admin\Fields;
use StudiareExt\Core\Color;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Site;
use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Bottom_Nav\Item_Types;
use StudiareExt\Modules\Bottom_Nav\Schema;
use StudiareExt\Modules\Bottom_Nav\Styles;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$ui_icon = static function ( string $key ): string {
	return Icon_Library::svg( 'phosphor-duotone', $key, false, 'stx-ico' );
};

$palette   = Theme_Bridge::palette();
$catalog   = Icon_Library::catalog();
$theme_on  = Theme_Bridge::is_active();
$pack_demo = array( 'home', 'graduation', 'search', 'bag', 'user' );

$color_labels = array(
	'bg'         => __( 'Background', 'studiare-extensions' ),
	'icon'       => __( 'Icons', 'studiare-extensions' ),
	'label'      => __( 'Labels', 'studiare-extensions' ),
	'active'     => __( 'Active item', 'studiare-extensions' ),
	'active-bg'  => __( 'Active highlight', 'studiare-extensions' ),
	'badge-bg'   => __( 'Badge', 'studiare-extensions' ),
	'badge-text' => __( 'Badge text', 'studiare-extensions' ),
	'border'     => __( 'Border', 'studiare-extensions' ),
	'fab-bg'     => __( 'Center button', 'studiare-extensions' ),
	'fab-icon'   => __( 'Center button icon', 'studiare-extensions' ),
);

// What each slot shows when left empty (mirrors the defaults in bottom-nav.css).
$light_fallbacks = array(
	'bg'         => '#ffffff',
	'icon'       => $palette['text'],
	'label'      => $palette['text'],
	'active'     => $palette['primary'],
	'active-bg'  => Color::alpha( $palette['primary'], 0.14 ),
	'badge-bg'   => $palette['secondary'],
	'badge-text' => '#ffffff',
	'border'     => 'rgba(15, 23, 42, 0.07)',
	'fab-bg'     => $palette['primary'],
	'fab-icon'   => '#ffffff',
);

$dark_fallbacks = array(
	'bg'         => $palette['dark_bg'],
	'icon'       => Color::alpha( $palette['dark_text'], 0.6 ),
	'label'      => Color::alpha( $palette['dark_text'], 0.6 ),
	'active'     => $palette['primary'],
	'active-bg'  => Color::alpha( $palette['primary'], 0.24 ),
	'badge-bg'   => $palette['secondary'],
	'badge-text' => '#ffffff',
	'border'     => 'rgba(255, 255, 255, 0.08)',
	'fab-bg'     => $palette['primary'],
	'fab-icon'   => '#ffffff',
);

// Studiare's CSS variables, recreated so previews resolve the same defaults as the site.
$preview_vars = sprintf(
	'--primary_color:%1$s;--secondary_color:%2$s;--font_body-color:%3$s;--dark_primary_color:%4$s;--dark_secondary_color:%5$s;--dark_light_color:%6$s;--font_body-font-family:Vazirmatn,Tahoma,sans-serif;--menu_heading-font-family:Vazirmatn,Tahoma,sans-serif',
	$palette['primary'],
	$palette['secondary'],
	$palette['text'],
	$palette['dark_surface'],
	$palette['dark_bg'],
	$palette['dark_text']
);

$panel_tabs = array(
	'style'    => array( 'palette', __( 'Style', 'studiare-extensions' ) ),
	'items'    => array( 'grid', __( 'Buttons', 'studiare-extensions' ) ),
	'colors'   => array( 'sun', __( 'Colours', 'studiare-extensions' ) ),
	'layout'   => array( 'sliders', __( 'Size & font', 'studiare-extensions' ) ),
	'behavior' => array( 'settings', __( 'Behaviour', 'studiare-extensions' ) ),
);
?>
<div class="stx-module" data-stx-module="bottom_nav" style="<?php echo esc_attr( $preview_vars ); ?>">

	<section class="stx-module-head">
		<span class="stx-module-head__icon" aria-hidden="true"><?php echo $ui_icon( 'mobile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<div class="stx-module-head__text">
			<h1><?php echo esc_html( $module->title() ); ?></h1>
			<p><?php echo esc_html( $module->description() ); ?></p>
		</div>
		<label class="stx-module-head__switch">
			<span data-stx-show-if="enabled"><?php esc_html_e( 'Active', 'studiare-extensions' ); ?></span>
			<span data-stx-show-if="!enabled"><?php esc_html_e( 'Inactive', 'studiare-extensions' ); ?></span>
			<span class="stx-switch stx-switch--lg">
				<input type="checkbox" data-stx-bind="enabled" aria-label="<?php esc_attr_e( 'Enable the bottom navigation', 'studiare-extensions' ); ?>">
				<span class="stx-switch__track" aria-hidden="true"></span>
			</span>
		</label>
	</section>

	<div class="stx-module-body">
		<div class="stx-settings">

			<div class="stx-tabs" role="tablist" data-stx-tabs="bottom_nav">
				<?php foreach ( $panel_tabs as $tab_id => $panel_tab ) : ?>
					<button type="button" class="stx-tab" role="tab" id="stx-tab-<?php echo esc_attr( $tab_id ); ?>" aria-controls="stx-panel-<?php echo esc_attr( $tab_id ); ?>" aria-selected="false" data-stx-tab="<?php echo esc_attr( $tab_id ); ?>">
						<?php echo $ui_icon( $panel_tab[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $panel_tab[1] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<?php /* ---------------------------------------------------------- Style */ ?>
			<section class="stx-panel" role="tabpanel" id="stx-panel-style" aria-labelledby="stx-tab-style" data-stx-panel="style">
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Choose a style', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Every style works with any number of buttons. The previews use your real buttons.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-style-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Style', 'studiare-extensions' ); ?>">
						<?php foreach ( Styles::all() as $style_id => $style ) : ?>
							<label class="stx-style-card">
								<input type="radio" name="stx-style" value="<?php echo esc_attr( $style_id ); ?>" data-stx-bind="style">
								<span class="stx-style-card__stage stx-stage" data-stx-style-stage="<?php echo esc_attr( $style_id ); ?>" dir="<?php echo esc_attr( Site::direction() ); ?>" aria-hidden="true"></span>
								<span class="stx-style-card__body">
									<span class="stx-style-card__title"><?php echo esc_html( $style['label'] ); ?> <i class="stx-style-card__check" aria-hidden="true"><?php echo Icon_Library::svg( 'tabler', 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></i></span>
									<span class="stx-style-card__desc"><?php echo esc_html( $style['description'] ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Icon pack', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Switching packs re-draws every button. You can still give any button its own icon, image or SVG.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-pack-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Icon pack', 'studiare-extensions' ); ?>">
						<?php foreach ( $catalog['packs'] as $pack_id => $pack ) : ?>
							<?php $is_fa = Icon_Library::FONT_AWESOME === $pack_id; ?>
							<label class="stx-pack-card<?php echo ( $is_fa && ! $theme_on ) ? ' is-disabled' : ''; ?>">
								<input type="radio" name="stx-icon-pack" value="<?php echo esc_attr( $pack_id ); ?>" data-stx-bind="icon_pack" <?php disabled( $is_fa && ! $theme_on ); ?>>
								<span class="stx-pack-card__icons" aria-hidden="true">
									<?php foreach ( $pack_demo as $key ) : ?>
										<?php if ( $is_fa ) : ?>
											<i class="<?php echo esc_attr( Icon_Library::fa_class( $key, 'fal' ) ); ?>"></i>
										<?php else : ?>
											<?php echo Icon_Library::svg( $pack_id, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php endif; ?>
									<?php endforeach; ?>
								</span>
								<span class="stx-pack-card__name"><?php echo esc_html( $pack['label'] ); ?></span>
								<span class="stx-pack-card__meta"><?php echo esc_html( $is_fa && ! $theme_on ? __( 'Needs the Studiare theme', 'studiare-extensions' ) : $pack['license'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>

					<div class="stx-fields">
						<?php
						Fields::segmented(
							'fa_weight',
							__( 'Font Awesome weight', 'studiare-extensions' ),
							array(
								'fal' => __( 'Light', 'studiare-extensions' ),
								'far' => __( 'Regular', 'studiare-extensions' ),
								'fas' => __( 'Solid', 'studiare-extensions' ),
								'fad' => __( 'Duotone', 'studiare-extensions' ),
							),
							array( 'show_if' => 'icon_pack=fontawesome' )
						);
						Fields::range(
							'icon_stroke',
							__( 'Line thickness', 'studiare-extensions' ),
							1,
							2.5,
							0.25,
							'',
							array( 'show_if' => 'icon_pack=lucide|tabler|heroicons' )
						);
						Fields::toggle(
							'active_filled',
							__( 'Filled icon for the active button', 'studiare-extensions' ),
							array( 'help' => __( 'Packs with filled variants (Phosphor, Heroicons, Bootstrap, Tabler, Font Awesome) swap to the solid icon on the current page.', 'studiare-extensions' ) )
						);
						?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Labels', 'studiare-extensions' ); ?></h2>
					</header>
					<div class="stx-fields">
						<?php
						Fields::segmented(
							'label_mode',
							__( 'Show labels', 'studiare-extensions' ),
							array(
								'always' => __( 'Always', 'studiare-extensions' ),
								'active' => __( 'Active only', 'studiare-extensions' ),
								'never'  => __( 'Icons only', 'studiare-extensions' ),
							),
							array(
								'show_if' => 'style=classic|floating|notch',
								'help'    => __( 'Hidden labels stay available to screen readers.', 'studiare-extensions' ),
							)
						);
						?>
						<p class="stx-inline-note" data-stx-show-if="style=bubble|pill">
							<?php echo $ui_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php esc_html_e( 'This style shows the label of the active button only, by design.', 'studiare-extensions' ); ?>
						</p>
					</div>
				</div>
			</section>

			<?php /* -------------------------------------------------------- Buttons */ ?>
			<section class="stx-panel" role="tabpanel" id="stx-panel-items" aria-labelledby="stx-tab-items" data-stx-panel="items" hidden>
				<div class="stx-card">
					<header class="stx-card__head stx-card__head--split">
						<div>
							<h2>
								<?php esc_html_e( 'Buttons', 'studiare-extensions' ); ?>
								<span class="stx-counter" data-stx-item-count></span>
							</h2>
							<p><?php esc_html_e( 'Drag to reorder, tap a button to edit it. 3 to 5 buttons feel best on phones.', 'studiare-extensions' ); ?></p>
						</div>
						<div class="stx-add">
							<button type="button" class="stx-btn stx-btn--primary" data-stx-add-toggle aria-expanded="false" aria-controls="stx-add-menu">
								<?php echo Icon_Library::svg( 'tabler', 'plus', false, 'stx-ico' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Add button', 'studiare-extensions' ); ?>
							</button>
							<div class="stx-popover" id="stx-add-menu" role="menu" hidden>
								<?php foreach ( Item_Types::all() as $type_id => $item_type ) : ?>
									<?php $available = Item_Types::is_available( $type_id ); ?>
									<button type="button" class="stx-popover__item" role="menuitem" data-stx-add-type="<?php echo esc_attr( $type_id ); ?>" <?php disabled( ! $available ); ?>>
										<span class="stx-popover__icon" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', $item_type['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span>
											<strong><?php echo esc_html( $item_type['label'] ); ?></strong>
											<small><?php echo esc_html( $available ? $item_type['description'] : __( 'Requires WooCommerce', 'studiare-extensions' ) ); ?></small>
										</span>
									</button>
								<?php endforeach; ?>
							</div>
						</div>
					</header>

					<ul class="stx-items" data-stx-items></ul>

					<div class="stx-empty" data-stx-items-empty hidden>
						<?php echo $ui_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<p><?php esc_html_e( 'No buttons yet. Add your first one.', 'studiare-extensions' ); ?></p>
					</div>

					<p class="stx-inline-note stx-inline-note--warn" data-stx-items-many hidden>
						<?php echo $ui_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'More than 5 buttons still fit, but labels get smaller. Consider moving extras into a menu or content sheet.', 'studiare-extensions' ); ?>
					</p>
				</div>
			</section>

			<?php /* -------------------------------------------------------- Colours */ ?>
			<section class="stx-panel" role="tabpanel" id="stx-panel-colors" aria-labelledby="stx-tab-colors" data-stx-panel="colors" hidden>
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Light mode colours', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Leave a colour empty to follow your Studiare theme colours automatically.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-color-grid">
						<?php
						foreach ( Schema::COLOR_SLOTS as $slot ) {
							Fields::color( 'colors.' . $slot, $color_labels[ $slot ], $light_fallbacks[ $slot ] );
						}
						?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Dark mode', 'studiare-extensions' ); ?></h2>
						<p>
							<?php
							echo esc_html(
								Theme_Bridge::dark_mode_available()
									? __( 'Studiare dark mode is on. The bar switches palettes together with the site.', 'studiare-extensions' )
									: __( 'Studiare dark mode is currently off in the theme options, so these colours are only used once you enable it.', 'studiare-extensions' )
							);
							?>
						</p>
					</header>
					<div class="stx-fields">
						<?php
						Fields::segmented(
							'dark.mode',
							__( 'When the site is dark', 'studiare-extensions' ),
							array(
								'auto' => __( 'Use dark colours', 'studiare-extensions' ),
								'off'  => __( 'Keep light colours', 'studiare-extensions' ),
							)
						);
						?>
					</div>
					<div class="stx-color-grid" data-stx-show-if="dark.mode=auto">
						<?php
						foreach ( Schema::COLOR_SLOTS as $slot ) {
							Fields::color( 'dark.colors.' . $slot, $color_labels[ $slot ], $dark_fallbacks[ $slot ] );
						}
						?>
					</div>
				</div>
			</section>

			<?php /* ---------------------------------------------------- Size & font */ ?>
			<section class="stx-panel" role="tabpanel" id="stx-panel-layout" aria-labelledby="stx-tab-layout" data-stx-panel="layout" hidden>
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Size and shape', 'studiare-extensions' ); ?></h2>
					</header>
					<div class="stx-fields stx-fields--2">
						<?php
						Fields::range( 'layout.height', __( 'Bar height', 'studiare-extensions' ), 52, 88 );
						Fields::range( 'layout.icon_size', __( 'Icon size', 'studiare-extensions' ), 18, 32 );
						Fields::range( 'layout.radius', __( 'Corner radius', 'studiare-extensions' ), 0, 40, 1, 'px', array( 'show_if' => 'style=classic|floating|notch|pill' ) );
						Fields::range( 'layout.offset', __( 'Distance from screen edges', 'studiare-extensions' ), 0, 32, 1, 'px', array( 'show_if' => 'style=floating|pill' ) );
						Fields::range( 'layout.max_width', __( 'Maximum width (tablets)', 'studiare-extensions' ), 320, 1000, 10, 'px', array( 'show_if' => 'style=floating|pill' ) );
						Fields::range(
							'layout.breakpoint',
							__( 'Show on screens narrower than', 'studiare-extensions' ),
							360,
							1400,
							1,
							'px',
							array( 'help' => __( '768px covers phones; raise it to include tablets.', 'studiare-extensions' ) )
						);
						?>
					</div>
					<div class="stx-fields">
						<?php
						Fields::segmented(
							'layout.shadow',
							__( 'Shadow', 'studiare-extensions' ),
							array(
								'none'   => __( 'None', 'studiare-extensions' ),
								'soft'   => __( 'Soft', 'studiare-extensions' ),
								'medium' => __( 'Medium', 'studiare-extensions' ),
								'strong' => __( 'Strong', 'studiare-extensions' ),
							)
						);
						Fields::segmented(
							'layout.motion',
							__( 'Animation', 'studiare-extensions' ),
							array(
								'spring' => __( 'Springy', 'studiare-extensions' ),
								'smooth' => __( 'Smooth', 'studiare-extensions' ),
								'none'   => __( 'None', 'studiare-extensions' ),
							),
							array( 'help' => __( 'Visitors who ask their device for reduced motion never see animations.', 'studiare-extensions' ) )
						);
						Fields::toggle(
							'layout.glass',
							__( 'Frosted glass background', 'studiare-extensions' ),
							array(
								'show_if' => 'style=classic|floating|pill',
								'help'    => __( 'Slightly translucent with a blur of the page behind it.', 'studiare-extensions' ),
							)
						);
						?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Font', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'By default labels use the body font you picked in Studiare → Typography.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-fields stx-fields--2">
						<?php
						$fonts = Theme_Bridge::fonts();
						Fields::select(
							'typography.font_source',
							__( 'Font family', 'studiare-extensions' ),
							array(
								/* translators: %s: font family name. */
								'theme_body' => '' !== $fonts['body'] ? sprintf( __( 'Theme body font (%s)', 'studiare-extensions' ), $fonts['body'] ) : __( 'Theme body font', 'studiare-extensions' ),
								/* translators: %s: font family name. */
								'theme_menu' => '' !== $fonts['menu'] ? sprintf( __( 'Theme menu font (%s)', 'studiare-extensions' ), $fonts['menu'] ) : __( 'Theme menu font', 'studiare-extensions' ),
								'inherit'    => __( 'Inherit from the page', 'studiare-extensions' ),
								'custom'     => __( 'Custom…', 'studiare-extensions' ),
							)
						);
						Fields::text(
							'typography.font_family',
							__( 'Custom font family', 'studiare-extensions' ),
							array(
								'show_if'     => 'typography.font_source=custom',
								'placeholder' => 'IRANSansX, Tahoma',
								'dir'         => 'ltr',
								'help'        => __( 'The font must already be loaded on your site.', 'studiare-extensions' ),
							)
						);
						Fields::range( 'typography.font_size', __( 'Label size', 'studiare-extensions' ), 9, 15 );
						Fields::segmented(
							'typography.font_weight',
							__( 'Label weight', 'studiare-extensions' ),
							array(
								'400' => __( 'Regular', 'studiare-extensions' ),
								'500' => __( 'Medium', 'studiare-extensions' ),
								'600' => __( 'Semi-bold', 'studiare-extensions' ),
								'700' => __( 'Bold', 'studiare-extensions' ),
							)
						);
						?>
					</div>
				</div>
			</section>

			<?php /* ------------------------------------------------------- Behaviour */ ?>
			<section class="stx-panel" role="tabpanel" id="stx-panel-behavior" aria-labelledby="stx-tab-behavior" data-stx-panel="behavior" hidden>
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Behaviour', 'studiare-extensions' ); ?></h2>
					</header>
					<div class="stx-fields">
						<?php
						Fields::toggle( 'behavior.hide_on_scroll', __( 'Hide while scrolling down', 'studiare-extensions' ), array( 'help' => __( 'Gives more room to content; the bar returns as soon as the visitor scrolls up.', 'studiare-extensions' ) ) );
						Fields::toggle( 'behavior.haptic', __( 'Haptic feedback', 'studiare-extensions' ), array( 'help' => __( 'A tiny vibration on tap (Android devices that support it).', 'studiare-extensions' ) ) );
						Fields::toggle( 'behavior.replace_theme_nav', __( 'Replace the Studiare bottom bar', 'studiare-extensions' ), array( 'help' => __( 'Removes the theme\'s own mobile bottom bar so the two never overlap.', 'studiare-extensions' ) ) );
						Fields::toggle( 'behavior.hide_theme_back_to_top', __( 'Hide the theme "back to top" button on phones', 'studiare-extensions' ) );
						Fields::toggle( 'behavior.lift_fixed_elements', __( 'Lift floating theme elements above the bar', 'studiare-extensions' ), array( 'help' => __( 'Moves the sticky add-to-cart bar and floating contact buttons up so they stay visible.', 'studiare-extensions' ) ) );
						Fields::text(
							'behavior.z_index',
							__( 'Stacking order (z-index)', 'studiare-extensions' ),
							array(
								'type' => 'number',
								'dir'  => 'ltr',
								'help' => __( 'Raise it if a chat widget or popup covers the bar.', 'studiare-extensions' ),
							)
						);
						?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Where to show', 'studiare-extensions' ); ?></h2>
					</header>
					<div class="stx-fields">
						<?php
						Fields::toggle( 'visibility.hide_on_checkout', __( 'Hide on the checkout page', 'studiare-extensions' ), array( 'help' => __( 'Keeps buyers focused on completing the order.', 'studiare-extensions' ) ) );
						Fields::toggle( 'visibility.hide_on_cart', __( 'Hide on the cart page', 'studiare-extensions' ) );
						Fields::toggle( 'visibility.hide_on_course', __( 'Hide on single course pages', 'studiare-extensions' ), array( 'help' => __( 'Leaves the bottom of the screen to the course page\'s own buy bar.', 'studiare-extensions' ) ) );
						Fields::toggle( 'visibility.hide_on_product', __( 'Hide on single product pages (not courses)', 'studiare-extensions' ) );
						Fields::text(
							'visibility.hide_for_ids',
							__( 'Hide on these pages or posts (IDs)', 'studiare-extensions' ),
							array(
								'placeholder' => '12, 345',
								'dir'         => 'ltr',
								'help'        => __( 'Comma separated IDs, e.g. landing pages that have their own call to action.', 'studiare-extensions' ),
							)
						);
						?>
					</div>
				</div>
			</section>
		</div>

		<?php /* ----------------------------------------------------------- Preview */ ?>
		<aside class="stx-preview-col" aria-label="<?php esc_attr_e( 'Live preview', 'studiare-extensions' ); ?>">
			<div class="stx-preview">
				<div class="stx-preview__toolbar">
					<div class="stx-segmented stx-segmented--sm" role="radiogroup" aria-label="<?php esc_attr_e( 'Preview colour scheme', 'studiare-extensions' ); ?>">
						<label class="stx-segmented__option"><input type="radio" name="stx-preview-scheme" value="light" data-stx-preview-scheme checked><span><?php esc_html_e( 'Light', 'studiare-extensions' ); ?></span></label>
						<label class="stx-segmented__option"><input type="radio" name="stx-preview-scheme" value="dark" data-stx-preview-scheme><span><?php esc_html_e( 'Dark', 'studiare-extensions' ); ?></span></label>
					</div>
					<div class="stx-segmented stx-segmented--sm" role="radiogroup" aria-label="<?php esc_attr_e( 'Preview visitor', 'studiare-extensions' ); ?>">
						<label class="stx-segmented__option"><input type="radio" name="stx-preview-user" value="member" data-stx-preview-user checked><span><?php esc_html_e( 'Member', 'studiare-extensions' ); ?></span></label>
						<label class="stx-segmented__option"><input type="radio" name="stx-preview-user" value="guest" data-stx-preview-user><span><?php esc_html_e( 'Guest', 'studiare-extensions' ); ?></span></label>
					</div>
				</div>

				<div class="stx-phone">
					<div class="stx-phone__notch" aria-hidden="true"></div>
					<div class="stx-phone__screen stx-stage" data-stx-preview-screen dir="<?php echo esc_attr( Site::direction() ); ?>">
						<div class="stx-mock" aria-hidden="true">
							<div class="stx-mock__header"><span></span><i></i></div>
							<div class="stx-mock__hero"></div>
							<div class="stx-mock__row"><span></span><span></span></div>
							<div class="stx-mock__card"></div>
							<div class="stx-mock__card"></div>
							<div class="stx-mock__row"><span></span><span></span></div>
						</div>
						<div data-stx-preview-nav></div>
					</div>
				</div>

				<p class="stx-preview__caption">
					<?php esc_html_e( 'Tap the buttons to try the animation. On your site the Studiare font is used.', 'studiare-extensions' ); ?>
				</p>
			</div>
		</aside>
	</div>

	<?php /* ------------------------------------------------ Item row template */ ?>
	<template id="stx-item-template">
		<li class="stx-item">
			<div class="stx-item__row">
				<span class="stx-item__handle" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="stx-item__icon" data-item-icon aria-hidden="true"></span>
				<button type="button" class="stx-item__summary" data-item-toggle aria-expanded="false">
					<span class="stx-item__label" data-item-label></span>
					<span class="stx-item__chips">
						<span class="stx-chip" data-item-type></span>
						<span class="stx-chip stx-chip--accent" data-item-featured hidden><?php esc_html_e( 'Center', 'studiare-extensions' ); ?></span>
						<span class="stx-chip" data-item-audience hidden></span>
						<span class="stx-chip stx-chip--warn" data-item-warning hidden></span>
					</span>
				</button>
				<span class="stx-item__tools">
					<button type="button" class="stx-move" data-item-action="up" aria-label="<?php esc_attr_e( 'Move up', 'studiare-extensions' ); ?>">▲</button>
					<button type="button" class="stx-move" data-item-action="down" aria-label="<?php esc_attr_e( 'Move down', 'studiare-extensions' ); ?>">▼</button>
					<label class="stx-switch stx-switch--sm" title="<?php esc_attr_e( 'Show this button', 'studiare-extensions' ); ?>">
						<input type="checkbox" data-item-bind="enabled" aria-label="<?php esc_attr_e( 'Show this button', 'studiare-extensions' ); ?>">
						<span class="stx-switch__track" aria-hidden="true"></span>
					</label>
					<button type="button" class="stx-icon-btn" data-item-action="duplicate" title="<?php esc_attr_e( 'Duplicate', 'studiare-extensions' ); ?>" aria-label="<?php esc_attr_e( 'Duplicate', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<button type="button" class="stx-icon-btn stx-icon-btn--danger" data-item-action="delete" title="<?php esc_attr_e( 'Delete', 'studiare-extensions' ); ?>" aria-label="<?php esc_attr_e( 'Delete', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<button type="button" class="stx-icon-btn stx-item__chevron" data-item-toggle tabindex="-1" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</span>
			</div>

			<div class="stx-item__editor" hidden>
				<div class="stx-fields stx-fields--2">
					<div class="stx-field">
						<label class="stx-field__label"><?php esc_html_e( 'Button type', 'studiare-extensions' ); ?></label>
						<div class="stx-select">
							<select data-item-bind="type">
								<?php foreach ( Item_Types::all() as $type_id => $item_type ) : ?>
									<option value="<?php echo esc_attr( $type_id ); ?>" <?php disabled( ! Item_Types::is_available( $type_id ) ); ?>><?php echo esc_html( $item_type['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<p class="stx-field__help" data-item-type-help></p>
					</div>

					<div class="stx-field">
						<label class="stx-field__label"><?php esc_html_e( 'Label', 'studiare-extensions' ); ?></label>
						<input type="text" class="stx-input" data-item-bind="label" maxlength="40">
					</div>

					<div class="stx-field stx-field--full">
						<span class="stx-field__label"><?php esc_html_e( 'Icon', 'studiare-extensions' ); ?></span>
						<div class="stx-segmented">
							<label class="stx-segmented__option"><input type="radio" value="pack" data-item-bind="icon_source"><span><?php esc_html_e( 'From pack', 'studiare-extensions' ); ?></span></label>
							<label class="stx-segmented__option" <?php echo $theme_on ? '' : 'hidden'; ?>><input type="radio" value="fontawesome" data-item-bind="icon_source"><span>Font Awesome</span></label>
							<label class="stx-segmented__option"><input type="radio" value="image" data-item-bind="icon_source"><span><?php esc_html_e( 'Image', 'studiare-extensions' ); ?></span></label>
							<label class="stx-segmented__option"><input type="radio" value="svg" data-item-bind="icon_source"><span>SVG</span></label>
						</div>

						<div class="stx-icon-choice" data-item-show-if="icon_source=pack">
							<button type="button" class="stx-btn stx-btn--soft" data-item-action="pick-icon">
								<span class="stx-icon-choice__preview" data-item-icon-preview aria-hidden="true"></span>
								<?php esc_html_e( 'Choose icon', 'studiare-extensions' ); ?>
							</button>
						</div>
						<div data-item-show-if="icon_source=fontawesome">
							<input type="text" class="stx-input" data-item-bind="icon_fa" dir="ltr" placeholder="fal fa-graduation-cap">
							<p class="stx-field__help"><?php esc_html_e( 'Any Font Awesome 5 Pro class loaded by Studiare.', 'studiare-extensions' ); ?></p>
						</div>
						<div class="stx-media-field" data-item-show-if="icon_source=image">
							<input type="url" class="stx-input" data-item-bind="icon_image" dir="ltr" placeholder="https://">
							<button type="button" class="stx-btn stx-btn--soft" data-item-action="pick-image"><?php esc_html_e( 'Media library', 'studiare-extensions' ); ?></button>
							<p class="stx-field__help"><?php esc_html_e( 'Square PNG, WebP or SVG files look best.', 'studiare-extensions' ); ?></p>
						</div>
						<div data-item-show-if="icon_source=svg">
							<textarea class="stx-input stx-input--code" rows="4" dir="ltr" data-item-bind="icon_svg" placeholder="<svg viewBox=&quot;0 0 24 24&quot;>…</svg>"></textarea>
							<p class="stx-field__help"><?php esc_html_e( 'Use fill="currentColor" or stroke="currentColor" so the icon follows the bar colours. Scripts are removed on save.', 'studiare-extensions' ); ?></p>
						</div>
					</div>

					<div class="stx-field stx-field--full" data-item-show-if="type=link|account|selector">
						<label class="stx-field__label"><?php esc_html_e( 'Link (URL)', 'studiare-extensions' ); ?></label>
						<input type="text" class="stx-input" data-item-bind="url" dir="ltr" placeholder="https://" list="stx-url-suggestions">
						<p class="stx-field__help" data-item-show-if="type=link"><?php esc_html_e( 'Pages, categories, tel:+98…, https://t.me/… — anything goes.', 'studiare-extensions' ); ?></p>
						<p class="stx-field__help" data-item-show-if="type=account"><?php esc_html_e( 'Optional. Leave empty to use the WooCommerce "My account" page.', 'studiare-extensions' ); ?></p>
						<p class="stx-field__help" data-item-show-if="type=selector"><?php esc_html_e( 'Optional fallback, used when the element is not on the page.', 'studiare-extensions' ); ?></p>
					</div>

					<div class="stx-field stx-field--full" data-item-show-if="type=link">
						<label class="stx-switch-row">
							<span class="stx-field__label"><?php esc_html_e( 'Open in a new tab', 'studiare-extensions' ); ?></span>
							<span class="stx-switch"><input type="checkbox" data-item-bind="new_tab"><span class="stx-switch__track" aria-hidden="true"></span></span>
						</label>
					</div>

					<fieldset class="stx-field stx-field--full" data-item-show-if="type=search">
						<legend class="stx-field__label"><?php esc_html_e( 'Search in', 'studiare-extensions' ); ?></legend>
						<div class="stx-checks" data-item-bind-list="search_post_types" data-stx-options="postTypes"></div>
						<p class="stx-field__help"><?php esc_html_e( 'Leave all unchecked to search everything.', 'studiare-extensions' ); ?></p>
					</fieldset>

					<div class="stx-field stx-field--full" data-item-show-if="type=search">
						<label class="stx-switch-row">
							<span class="stx-switch-row__text">
								<span class="stx-field__label"><?php esc_html_e( 'Live results while typing (AJAX)', 'studiare-extensions' ); ?></span>
								<span class="stx-field__help"><?php esc_html_e( 'Matching results appear in the sheet without leaving the page. Pressing Search still opens the full results page.', 'studiare-extensions' ); ?></span>
							</span>
							<span class="stx-switch"><input type="checkbox" data-item-bind="search_live"><span class="stx-switch__track" aria-hidden="true"></span></span>
						</label>
					</div>

					<div class="stx-field" data-item-show-if="type=cart">
						<label class="stx-field__label"><?php esc_html_e( 'On tap', 'studiare-extensions' ); ?></label>
						<div class="stx-select">
							<select data-item-bind="cart_action">
								<option value="auto"><?php esc_html_e( 'Open the Studiare mini cart (recommended)', 'studiare-extensions' ); ?></option>
								<option value="sheet"><?php esc_html_e( 'Open the cart sheet', 'studiare-extensions' ); ?></option>
								<option value="page"><?php esc_html_e( 'Go to the cart page', 'studiare-extensions' ); ?></option>
								<option value="checkout"><?php esc_html_e( 'Go to checkout', 'studiare-extensions' ); ?></option>
							</select>
						</div>
					</div>

					<div class="stx-field" data-item-show-if="type=cart">
						<label class="stx-switch-row">
							<span class="stx-field__label"><?php esc_html_e( 'Hide the count when the cart is empty', 'studiare-extensions' ); ?></span>
							<span class="stx-switch"><input type="checkbox" data-item-bind="hide_empty_badge"><span class="stx-switch__track" aria-hidden="true"></span></span>
						</label>
					</div>

					<div class="stx-field" data-item-show-if="type=account">
						<label class="stx-field__label"><?php esc_html_e( 'Label for guests', 'studiare-extensions' ); ?></label>
						<input type="text" class="stx-input" data-item-bind="guest_label" maxlength="40" placeholder="<?php esc_attr_e( 'Login', 'studiare-extensions' ); ?>">
					</div>

					<div class="stx-field" data-item-show-if="type=account">
						<label class="stx-field__label"><?php esc_html_e( 'Guests tapping it', 'studiare-extensions' ); ?></label>
						<div class="stx-select">
							<select data-item-bind="guest_action">
								<option value="page"><?php esc_html_e( 'Go to the login page', 'studiare-extensions' ); ?></option>
								<option value="modal"><?php esc_html_e( 'Open the Studiare login popup', 'studiare-extensions' ); ?></option>
							</select>
						</div>
					</div>

					<div class="stx-field stx-field--full" data-item-show-if="type=account">
						<label class="stx-switch-row">
							<span class="stx-field__label"><?php esc_html_e( 'Show the user\'s avatar instead of the icon', 'studiare-extensions' ); ?></span>
							<span class="stx-switch"><input type="checkbox" data-item-bind="show_avatar"><span class="stx-switch__track" aria-hidden="true"></span></span>
						</label>
					</div>

					<div class="stx-field" data-item-show-if="type=menu">
						<label class="stx-field__label"><?php esc_html_e( 'Menu', 'studiare-extensions' ); ?></label>
						<div class="stx-select">
							<select data-item-bind="menu_source">
								<option value="theme"><?php esc_html_e( 'Studiare mobile menu', 'studiare-extensions' ); ?></option>
								<option value="wp_menu"><?php esc_html_e( 'A WordPress menu in a sheet', 'studiare-extensions' ); ?></option>
							</select>
						</div>
					</div>

					<div class="stx-field" data-item-show-if="type=menu;menu_source=wp_menu">
						<label class="stx-field__label"><?php esc_html_e( 'Which menu', 'studiare-extensions' ); ?></label>
						<div class="stx-select"><select data-item-bind="menu_id" data-stx-type="number" data-stx-options="menus"></select></div>
					</div>

					<div class="stx-field stx-field--full" data-item-show-if="type=content">
						<span class="stx-field__label"><?php esc_html_e( 'Content', 'studiare-extensions' ); ?></span>
						<div class="stx-segmented">
							<label class="stx-segmented__option"><input type="radio" value="shortcode" data-item-bind="content_source"><span><?php esc_html_e( 'Text / shortcode', 'studiare-extensions' ); ?></span></label>
							<label class="stx-segmented__option"><input type="radio" value="elementor" data-item-bind="content_source"><span><?php esc_html_e( 'Elementor template', 'studiare-extensions' ); ?></span></label>
						</div>
					</div>

					<div class="stx-field stx-field--full" data-item-show-if="type=content;content_source=elementor">
						<label class="stx-field__label"><?php esc_html_e( 'Template or page', 'studiare-extensions' ); ?></label>
						<div class="stx-select"><select data-item-bind="content_id" data-stx-type="number" data-stx-options="templates"></select></div>
					</div>

					<div class="stx-field stx-field--full" data-item-show-if="type=content;content_source=shortcode">
						<label class="stx-field__label"><?php esc_html_e( 'Text, HTML or shortcode', 'studiare-extensions' ); ?></label>
						<textarea class="stx-input" rows="4" data-item-bind="content_html"></textarea>
					</div>

					<div class="stx-field stx-field--full" data-item-show-if="type=selector">
						<label class="stx-field__label"><?php esc_html_e( 'CSS selector of the element to click', 'studiare-extensions' ); ?></label>
						<input type="text" class="stx-input" data-item-bind="selector" dir="ltr" placeholder="#sc_notif_trigger">
						<p class="stx-field__help"><?php esc_html_e( 'For example #sc_notif_trigger opens Studiare notifications.', 'studiare-extensions' ); ?></p>
					</div>

					<div class="stx-field" data-item-show-if="type=search|cart|menu|content">
						<label class="stx-field__label"><?php esc_html_e( 'Sheet title', 'studiare-extensions' ); ?></label>
						<input type="text" class="stx-input" data-item-bind="sheet_title" maxlength="60" data-item-placeholder="label">
					</div>

					<div class="stx-field">
						<label class="stx-field__label"><?php esc_html_e( 'Show to', 'studiare-extensions' ); ?></label>
						<div class="stx-select">
							<select data-item-bind="visibility">
								<option value="all"><?php esc_html_e( 'Everyone', 'studiare-extensions' ); ?></option>
								<option value="guests"><?php esc_html_e( 'Guests only', 'studiare-extensions' ); ?></option>
								<option value="members"><?php esc_html_e( 'Logged-in users only', 'studiare-extensions' ); ?></option>
							</select>
						</div>
					</div>

					<div class="stx-field">
						<label class="stx-field__label"><?php esc_html_e( 'Badge text', 'studiare-extensions' ); ?></label>
						<input type="text" class="stx-input" data-item-bind="badge" maxlength="12" placeholder="<?php esc_attr_e( 'e.g. New', 'studiare-extensions' ); ?>">
					</div>

					<div class="stx-field stx-field--full">
						<label class="stx-switch-row">
							<span class="stx-switch-row__text">
								<span class="stx-field__label"><?php esc_html_e( 'Make it the center button', 'studiare-extensions' ); ?></span>
								<span class="stx-field__help"><?php esc_html_e( 'Used by the "Center button" style. Without a choice, the middle button is used.', 'studiare-extensions' ); ?></span>
							</span>
							<span class="stx-switch"><input type="checkbox" data-item-bind="featured"><span class="stx-switch__track" aria-hidden="true"></span></span>
						</label>
					</div>
				</div>
			</div>
		</li>
	</template>

	<datalist id="stx-url-suggestions">
		<option value="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'studiare-extensions' ); ?></option>
		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<option value="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Shop / courses', 'studiare-extensions' ); ?></option>
			<option value="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'My account', 'studiare-extensions' ); ?></option>
			<option value="<?php echo esc_url( wc_get_page_permalink( 'cart' ) ); ?>"><?php esc_html_e( 'Cart', 'studiare-extensions' ); ?></option>
		<?php endif; ?>
		<?php
		foreach ( get_pages(
			array(
				'number'      => 60,
				'sort_column' => 'menu_order,post_title',
			)
		) as $page_post ) :
			?>
			<option value="<?php echo esc_url( get_permalink( $page_post ) ); ?>"><?php echo esc_html( get_the_title( $page_post ) ); ?></option>
		<?php endforeach; ?>
	</datalist>

	<?php /* ------------------------------------------------------ Icon picker */ ?>
	<dialog class="stx-dialog stx-icon-picker" id="stx-icon-picker" aria-labelledby="stx-icon-picker-title">
		<div class="stx-dialog__head">
			<h2 id="stx-icon-picker-title"><?php esc_html_e( 'Choose an icon', 'studiare-extensions' ); ?></h2>
			<button type="button" class="stx-icon-btn" data-stx-dialog-close aria-label="<?php esc_attr_e( 'Close', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</div>
		<div class="stx-icon-picker__search">
			<?php echo Icon_Library::svg( 'phosphor', 'search', false, 'stx-ico' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<input type="search" class="stx-input" data-stx-icon-search placeholder="<?php esc_attr_e( 'Search icons… (e.g. cart, سبد)', 'studiare-extensions' ); ?>">
		</div>
		<div class="stx-icon-picker__grid" data-stx-icon-grid role="listbox" aria-label="<?php esc_attr_e( 'Icons', 'studiare-extensions' ); ?>"></div>
	</dialog>
</div>
