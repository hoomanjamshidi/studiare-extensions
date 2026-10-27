<?php
/**
 * Settings panel for the floating support button.
 *
 * Every control binds to the settings by path (`data-stx-bind`), including
 * the built-in channel rows, which are printed here in the saved order.
 * Rows of the channels the admin makes are cloned from
 * `[data-stx-custom-template]` by support-button-admin.js (bound with
 * `data-custom-bind`), which also adds the live preview, row summaries and
 * drag-to-reorder.
 *
 * @var \StudiareExt\Modules\Support_Button\Module $module
 *
 * @package StudiareExt
 */

use StudiareExt\Admin\Fields;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Site;
use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Support_Button\Channels;
use StudiareExt\Modules\Support_Button\Schema;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$ui_icon = static function ( string $key ): string {
	return Icon_Library::svg( 'phosphor-duotone', $key, false, 'stx-ico' );
};

$settings = $module->settings();
$palette  = Theme_Bridge::palette();
$channels = Channels::all();

$icon_labels = array(
	'chat'    => __( 'Chat', 'studiare-extensions' ),
	'support' => __( 'Headset', 'studiare-extensions' ),
	'help'    => __( 'Question', 'studiare-extensions' ),
	'phone'   => __( 'Phone', 'studiare-extensions' ),
	'mail'    => __( 'Email', 'studiare-extensions' ),
	'ticket'  => __( 'Ticket', 'studiare-extensions' ),
);

// "Start" and "end" follow the site direction, so name the physical side the admin sees.
$sides = Site::is_rtl()
	? array(
		'start' => __( 'Right', 'studiare-extensions' ),
		'end'   => __( 'Left', 'studiare-extensions' ),
	)
	: array(
		'start' => __( 'Left', 'studiare-extensions' ),
		'end'   => __( 'Right', 'studiare-extensions' ),
	);

// Studiare's CSS variables, recreated so the preview resolves the same defaults as the site.
$preview_vars = sprintf(
	'--primary_color:%1$s;--secondary_color:%2$s;--font_body-color:%3$s;--dark_primary_color:%4$s;--dark_secondary_color:%5$s;--dark_light_color:%6$s;--font_body-font-family:Vazirmatn,Tahoma,sans-serif',
	$palette['primary'],
	$palette['secondary'],
	$palette['text'],
	$palette['dark_surface'],
	$palette['dark_bg'],
	$palette['dark_text']
);

$panel_tabs = array(
	'channels'   => array( 'chat', __( 'Channels', 'studiare-extensions' ) ),
	'appearance' => array( 'palette', __( 'Appearance', 'studiare-extensions' ) ),
	'display'    => array( 'settings', __( 'Position & display', 'studiare-extensions' ) ),
);

$designs = array(
	'card'    => array( __( 'Card', 'studiare-extensions' ), __( 'A small window with a heading and one row per channel.', 'studiare-extensions' ) ),
	'bubbles' => array( __( 'Bubbles', 'studiare-extensions' ), __( 'Round brand buttons that pop up above the main button.', 'studiare-extensions' ) ),
);
?>
<div class="stx-module" data-stx-module="support_button" style="<?php echo esc_attr( $preview_vars ); ?>">

	<section class="stx-module-head">
		<span class="stx-module-head__icon" aria-hidden="true"><?php echo $ui_icon( 'support' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<div class="stx-module-head__text">
			<h1><?php echo esc_html( $module->title() ); ?></h1>
			<p><?php echo esc_html( $module->description() ); ?></p>
		</div>
		<label class="stx-module-head__switch">
			<span data-stx-show-if="enabled"><?php esc_html_e( 'Active', 'studiare-extensions' ); ?></span>
			<span data-stx-show-if="!enabled"><?php esc_html_e( 'Inactive', 'studiare-extensions' ); ?></span>
			<span class="stx-switch stx-switch--lg">
				<input type="checkbox" data-stx-bind="enabled" aria-label="<?php esc_attr_e( 'Enable the support button', 'studiare-extensions' ); ?>">
				<span class="stx-switch__track" aria-hidden="true"></span>
			</span>
		</label>
	</section>

	<div class="stx-module-body">
		<div class="stx-settings">

			<div class="stx-tabs" role="tablist" data-stx-tabs="support_button">
				<?php foreach ( $panel_tabs as $tab_id => $panel_tab ) : ?>
					<button type="button" class="stx-tab" role="tab" id="stx-tab-<?php echo esc_attr( $tab_id ); ?>" aria-controls="stx-panel-<?php echo esc_attr( $tab_id ); ?>" aria-selected="false" data-stx-tab="<?php echo esc_attr( $tab_id ); ?>">
						<?php echo $ui_icon( $panel_tab[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $panel_tab[1] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<?php /* ------------------------------------------------------- Channels */ ?>
			<section class="stx-panel" role="tabpanel" id="stx-panel-channels" aria-labelledby="stx-tab-channels" data-stx-panel="channels">
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Channels', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Switch on the ways visitors can reach you, fill in your ID or number, and drag to reorder. A channel without an ID stays hidden.', 'studiare-extensions' ); ?></p>
					</header>

					<p class="stx-inline-note stx-inline-note--warn" data-stx-none-ready hidden>
						<?php echo $ui_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'No channel is ready yet, so the button is not shown on your site. Fill in at least one switched-on channel.', 'studiare-extensions' ); ?>
					</p>

					<ul class="stx-items" data-stx-channels>
						<?php
						// Custom channels in the order are added by the script, from the template below.
						foreach ( array_intersect( $settings['order'], array_keys( $channels ) ) as $channel_id ) :
							?>
							<?php
							$channel = $channels[ $channel_id ];
							$bind    = 'channels.' . $channel_id . '.';
							$field   = 'stx-sb-' . $channel_id . '-';
							?>
							<li class="stx-item" data-channel="<?php echo esc_attr( $channel_id ); ?>">
								<div class="stx-item__row">
									<span class="stx-item__handle" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="stx-item__icon stx-channel-icon" data-channel-icon aria-hidden="true" style="--stx-sb-brand:<?php echo esc_attr( '' !== $channel['color'] ? $channel['color'] : 'var(--primary_color)' ); ?>"></span>
									<button type="button" class="stx-item__summary" data-item-toggle aria-expanded="false" aria-controls="<?php echo esc_attr( $field . 'editor' ); ?>">
										<span class="stx-item__label" data-channel-label><?php echo esc_html( $channel['label'] ); ?></span>
										<span class="stx-item__chips">
											<span class="stx-chip" data-channel-value dir="ltr" hidden></span>
											<span class="stx-chip stx-chip--warn" data-channel-warning hidden></span>
										</span>
									</button>
									<span class="stx-item__tools">
										<button type="button" class="stx-move" data-channel-move="up" aria-label="<?php esc_attr_e( 'Move up', 'studiare-extensions' ); ?>">▲</button>
										<button type="button" class="stx-move" data-channel-move="down" aria-label="<?php esc_attr_e( 'Move down', 'studiare-extensions' ); ?>">▼</button>
										<label class="stx-switch stx-switch--sm" title="<?php esc_attr_e( 'Show this channel', 'studiare-extensions' ); ?>">
											<input type="checkbox" data-stx-bind="<?php echo esc_attr( $bind . 'enabled' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: channel name, e.g. Telegram. */ __( 'Show %s', 'studiare-extensions' ), $channel['label'] ) ); ?>">
											<span class="stx-switch__track" aria-hidden="true"></span>
										</label>
										<button type="button" class="stx-icon-btn stx-item__chevron" data-item-toggle tabindex="-1" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
									</span>
								</div>

								<div class="stx-item__editor" id="<?php echo esc_attr( $field . 'editor' ); ?>" hidden>
									<div class="stx-fields stx-fields--2">
										<div class="stx-field stx-field--full">
											<label class="stx-field__label" for="<?php echo esc_attr( $field . 'value' ); ?>">
												<?php echo esc_html( 'link' === $channel_id ? __( 'Link (URL)', 'studiare-extensions' ) : __( 'ID, number or link', 'studiare-extensions' ) ); ?>
											</label>
											<input type="text" class="stx-input" id="<?php echo esc_attr( $field . 'value' ); ?>" data-stx-bind="<?php echo esc_attr( $bind . 'value' ); ?>" dir="ltr" placeholder="<?php echo esc_attr( $channel['placeholder'] ); ?>" autocomplete="off" spellcheck="false">
											<p class="stx-field__help"><?php echo esc_html( $channel['help'] ); ?></p>
											<p class="stx-channel-url" data-channel-url hidden></p>
										</div>

										<div class="stx-field">
											<label class="stx-field__label" for="<?php echo esc_attr( $field . 'label' ); ?>"><?php esc_html_e( 'Label', 'studiare-extensions' ); ?></label>
											<input type="text" class="stx-input" id="<?php echo esc_attr( $field . 'label' ); ?>" data-stx-bind="<?php echo esc_attr( $bind . 'label' ); ?>" maxlength="30" placeholder="<?php echo esc_attr( $channel['label'] ); ?>">
										</div>

										<div class="stx-field">
											<label class="stx-field__label" for="<?php echo esc_attr( $field . 'note' ); ?>"><?php esc_html_e( 'Note under the label', 'studiare-extensions' ); ?></label>
											<input type="text" class="stx-input" id="<?php echo esc_attr( $field . 'note' ); ?>" data-stx-bind="<?php echo esc_attr( $bind . 'note' ); ?>" maxlength="60" placeholder="<?php esc_attr_e( 'e.g. Replies within an hour', 'studiare-extensions' ); ?>">
										</div>

										<?php if ( 'whatsapp' === $channel_id ) : ?>
											<div class="stx-field stx-field--full">
												<label class="stx-field__label" for="<?php echo esc_attr( $field . 'message' ); ?>"><?php esc_html_e( 'Ready-made first message', 'studiare-extensions' ); ?></label>
												<input type="text" class="stx-input" id="<?php echo esc_attr( $field . 'message' ); ?>" data-stx-bind="<?php echo esc_attr( $bind . 'message' ); ?>" maxlength="200" placeholder="<?php esc_attr_e( 'Hello, I have a question about…', 'studiare-extensions' ); ?>">
												<p class="stx-field__help"><?php esc_html_e( 'Optional. WhatsApp opens with this text typed in; the visitor can edit it before sending.', 'studiare-extensions' ); ?></p>
											</div>
										<?php endif; ?>

										<?php if ( 'link' === $channel_id ) : ?>
											<div class="stx-field">
												<label class="stx-field__label" for="<?php echo esc_attr( $field . 'icon' ); ?>"><?php esc_html_e( 'Icon', 'studiare-extensions' ); ?></label>
												<div class="stx-select">
													<select id="<?php echo esc_attr( $field . 'icon' ); ?>" data-stx-bind="<?php echo esc_attr( $bind . 'icon' ); ?>">
														<?php foreach ( $icon_labels as $icon_key => $icon_label ) : ?>
															<option value="<?php echo esc_attr( $icon_key ); ?>"><?php echo esc_html( $icon_label ); ?></option>
														<?php endforeach; ?>
													</select>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
					<div class="stx-sb-custom-add">
						<button type="button" class="stx-btn stx-btn--soft" data-stx-custom-add>
							<?php echo Icon_Library::svg( 'phosphor', 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php esc_html_e( 'Make your own channel', 'studiare-extensions' ); ?>
						</button>
						<p class="stx-field__help"><?php esc_html_e( 'Any other social network or page, with its own name, link, colour and icon (from the icon library, an image or SVG code).', 'studiare-extensions' ); ?></p>
					</div>
				</div>

				<template data-stx-custom-template>
					<li class="stx-item stx-item--custom" data-channel="__ID__">
						<div class="stx-item__row">
							<span class="stx-item__handle" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="stx-item__icon stx-channel-icon" data-channel-icon aria-hidden="true"></span>
							<button type="button" class="stx-item__summary" data-item-toggle aria-expanded="false" aria-controls="stx-sb-__ID__-editor">
								<span class="stx-item__label" data-channel-label></span>
								<span class="stx-item__chips">
									<span class="stx-chip" data-channel-value dir="ltr" hidden></span>
									<span class="stx-chip stx-chip--warn" data-channel-warning hidden></span>
								</span>
							</button>
							<span class="stx-item__tools">
								<button type="button" class="stx-move" data-channel-move="up" aria-label="<?php esc_attr_e( 'Move up', 'studiare-extensions' ); ?>">▲</button>
								<button type="button" class="stx-move" data-channel-move="down" aria-label="<?php esc_attr_e( 'Move down', 'studiare-extensions' ); ?>">▼</button>
								<label class="stx-switch stx-switch--sm" title="<?php esc_attr_e( 'Show this channel', 'studiare-extensions' ); ?>">
									<input type="checkbox" data-custom-bind="enabled" aria-label="<?php esc_attr_e( 'Show this channel', 'studiare-extensions' ); ?>">
									<span class="stx-switch__track" aria-hidden="true"></span>
								</label>
								<button type="button" class="stx-icon-btn stx-icon-btn--danger" data-custom-action="delete" aria-label="<?php esc_attr_e( 'Delete', 'studiare-extensions' ); ?>" title="<?php esc_attr_e( 'Delete', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
								<button type="button" class="stx-icon-btn stx-item__chevron" data-item-toggle tabindex="-1" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
							</span>
						</div>

						<div class="stx-item__editor" id="stx-sb-__ID__-editor" hidden>
							<div class="stx-fields stx-fields--2">
								<div class="stx-field">
									<label class="stx-field__label" for="stx-sb-__ID__-label"><?php esc_html_e( 'Name', 'studiare-extensions' ); ?></label>
									<input type="text" class="stx-input" id="stx-sb-__ID__-label" data-custom-bind="label" maxlength="30" placeholder="<?php esc_attr_e( 'e.g. Rubino', 'studiare-extensions' ); ?>">
								</div>

								<div class="stx-field">
									<label class="stx-field__label" for="stx-sb-__ID__-note"><?php esc_html_e( 'Note under the label', 'studiare-extensions' ); ?></label>
									<input type="text" class="stx-input" id="stx-sb-__ID__-note" data-custom-bind="note" maxlength="60" placeholder="<?php esc_attr_e( 'e.g. Replies within an hour', 'studiare-extensions' ); ?>">
								</div>

								<div class="stx-field stx-field--full">
									<label class="stx-field__label" for="stx-sb-__ID__-value"><?php esc_html_e( 'Link (URL)', 'studiare-extensions' ); ?></label>
									<input type="text" class="stx-input" id="stx-sb-__ID__-value" data-custom-bind="value" dir="ltr" placeholder="https://" autocomplete="off" spellcheck="false">
									<p class="stx-field__help"><?php esc_html_e( 'Your page on the network, any link on your site, or tel: / mailto:.', 'studiare-extensions' ); ?></p>
									<p class="stx-channel-url" data-channel-url hidden></p>
								</div>

								<div class="stx-field stx-field--color">
									<span class="stx-field__label"><?php esc_html_e( 'Colour', 'studiare-extensions' ); ?></span>
									<div class="stx-color" data-custom-color></div>
									<p class="stx-field__help"><?php esc_html_e( 'The circle behind the icon. Pick a dark enough colour for a white icon.', 'studiare-extensions' ); ?></p>
								</div>

								<div class="stx-field stx-field--full">
									<span class="stx-field__label"><?php esc_html_e( 'Icon', 'studiare-extensions' ); ?></span>
									<div class="stx-segmented">
										<label class="stx-segmented__option"><input type="radio" name="stx-sb-__ID__-icon-source" value="pack" data-custom-bind="icon_source"><span><?php esc_html_e( 'From pack', 'studiare-extensions' ); ?></span></label>
										<label class="stx-segmented__option"><input type="radio" name="stx-sb-__ID__-icon-source" value="image" data-custom-bind="icon_source"><span><?php esc_html_e( 'Image', 'studiare-extensions' ); ?></span></label>
										<label class="stx-segmented__option"><input type="radio" name="stx-sb-__ID__-icon-source" value="svg" data-custom-bind="icon_source"><span>SVG</span></label>
									</div>

									<div class="stx-icon-choice" data-custom-show-if="pack">
										<button type="button" class="stx-btn stx-btn--soft" data-custom-action="pick-icon">
											<span class="stx-icon-choice__preview" data-custom-icon-preview aria-hidden="true"></span>
											<?php esc_html_e( 'Choose icon', 'studiare-extensions' ); ?>
										</button>
									</div>
									<div class="stx-media-field" data-custom-show-if="image">
										<input type="url" class="stx-input" data-custom-bind="icon_image" dir="ltr" placeholder="https://" aria-label="<?php esc_attr_e( 'Image URL', 'studiare-extensions' ); ?>">
										<button type="button" class="stx-btn stx-btn--soft" data-custom-action="pick-image"><?php esc_html_e( 'Media library', 'studiare-extensions' ); ?></button>
										<p class="stx-field__help"><?php esc_html_e( 'The network\'s logo as a square PNG, WebP or SVG file. It fills the circle, so the colour above only shows around a transparent logo.', 'studiare-extensions' ); ?></p>
									</div>
									<div data-custom-show-if="svg">
										<textarea class="stx-input stx-input--code" rows="4" dir="ltr" data-custom-bind="icon_svg" aria-label="<?php esc_attr_e( 'SVG code', 'studiare-extensions' ); ?>" placeholder="<svg viewBox=&quot;0 0 24 24&quot;>…</svg>"></textarea>
										<p class="stx-field__help"><?php esc_html_e( 'Use fill="currentColor" so the icon turns white on the colour above. Scripts are removed on save.', 'studiare-extensions' ); ?></p>
									</div>
								</div>
							</div>
						</div>
					</li>
				</template>
			</section>

			<?php /* ----------------------------------------------------- Appearance */ ?>
			<section class="stx-panel" role="tabpanel" id="stx-panel-appearance" aria-labelledby="stx-tab-appearance" data-stx-panel="appearance" hidden>
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Design', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'How the channels appear when the button is tapped. The previews use your real channels.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-style-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Design', 'studiare-extensions' ); ?>">
						<?php foreach ( $designs as $design_id => $design ) : ?>
							<label class="stx-style-card">
								<input type="radio" name="stx-sb-design" value="<?php echo esc_attr( $design_id ); ?>" data-stx-bind="design">
								<span class="stx-style-card__stage stx-stage stx-sb-stage" data-stx-design-stage="<?php echo esc_attr( $design_id ); ?>" dir="<?php echo esc_attr( Site::direction() ); ?>" aria-hidden="true"></span>
								<span class="stx-style-card__body">
									<span class="stx-style-card__title"><?php echo esc_html( $design[0] ); ?> <i class="stx-style-card__check" aria-hidden="true"><?php echo Icon_Library::svg( 'tabler', 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></i></span>
									<span class="stx-style-card__desc"><?php echo esc_html( $design[1] ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Main button', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'With a single channel switched on, the button opens it directly and shows its logo.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-fields">
						<div class="stx-field stx-field--full">
							<span class="stx-field__label" id="stx-sb-icon-label"><?php esc_html_e( 'Icon', 'studiare-extensions' ); ?></span>
							<div class="stx-icon-radios" role="radiogroup" aria-labelledby="stx-sb-icon-label">
								<?php foreach ( $icon_labels as $icon_key => $icon_label ) : ?>
									<label class="stx-icon-radio" title="<?php echo esc_attr( $icon_label ); ?>">
										<input type="radio" name="stx-sb-icon" value="<?php echo esc_attr( $icon_key ); ?>" data-stx-bind="button.icon" aria-label="<?php echo esc_attr( $icon_label ); ?>">
										<span aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', $icon_key, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
					<div class="stx-fields stx-fields--2">
						<?php
						Fields::text(
							'button.label',
							__( 'Button text', 'studiare-extensions' ),
							array(
								'placeholder' => __( 'Support', 'studiare-extensions' ),
								'help'        => __( 'Also read out by screen readers when the text is hidden.', 'studiare-extensions' ),
							)
						);
						Fields::range( 'button.size', __( 'Button size', 'studiare-extensions' ), 48, 72 );
						?>
					</div>
					<div class="stx-fields">
						<?php
						Fields::segmented(
							'button.label_mode',
							__( 'Show the text', 'studiare-extensions' ),
							array(
								'always'  => __( 'Always', 'studiare-extensions' ),
								'desktop' => __( 'On computers only', 'studiare-extensions' ),
								'never'   => __( 'Icon only', 'studiare-extensions' ),
							),
							array( 'help' => __( 'On phones a round icon button takes the least room.', 'studiare-extensions' ) )
						);
						Fields::toggle( 'button.pulse', __( 'Attention pulse', 'studiare-extensions' ), array( 'help' => __( 'A soft ring pulses three times after the page loads. Visitors who ask for reduced motion never see it.', 'studiare-extensions' ) ) );
						?>
					</div>
				</div>

				<div class="stx-card" data-stx-show-if="design=card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Card heading', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Leave both empty to show the channels only.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-fields">
						<?php
						Fields::text( 'header.title', __( 'Title', 'studiare-extensions' ) );
						Fields::text( 'header.subtitle', __( 'Subtitle', 'studiare-extensions' ) );
						?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Colours', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Leave a colour empty to follow your Studiare theme colours automatically. Dark mode follows the theme too.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-fields">
						<?php Fields::toggle( 'colors.brand', __( 'Brand colours for channels', 'studiare-extensions' ), array( 'help' => __( 'Telegram blue, WhatsApp green and so on. Off: every channel uses the button colour.', 'studiare-extensions' ) ) ); ?>
					</div>
					<div class="stx-color-grid">
						<?php
						Fields::color( 'colors.button_bg', __( 'Button', 'studiare-extensions' ), $palette['primary'] );
						Fields::color( 'colors.button_icon', __( 'Button icon and text', 'studiare-extensions' ), '#ffffff' );
						?>
					</div>
				</div>
			</section>

			<?php /* ------------------------------------------------ Position & display */ ?>
			<section class="stx-panel" role="tabpanel" id="stx-panel-display" aria-labelledby="stx-tab-display" data-stx-panel="display" hidden>
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Position', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'The button rises automatically above the bottom navigation, sticky buy bars and the theme\'s "back to top" button.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-fields">
						<?php Fields::segmented( 'position.side', __( 'Corner', 'studiare-extensions' ), $sides ); ?>
					</div>
					<div class="stx-fields stx-fields--2">
						<?php
						Fields::range( 'position.offset_x', __( 'Distance from the side', 'studiare-extensions' ), 8, 80 );
						Fields::range( 'position.offset_y', __( 'Distance from the bottom', 'studiare-extensions' ), 8, 160 );
						?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Greeting', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'A short message beside the button, shown once per visit. Tapping it opens the channels.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-fields">
						<?php
						Fields::toggle( 'greeting.enabled', __( 'Show a greeting', 'studiare-extensions' ) );
						Fields::text( 'greeting.text', __( 'Message', 'studiare-extensions' ), array( 'show_if' => 'greeting.enabled' ) );
						Fields::range( 'greeting.delay', __( 'Appears after', 'studiare-extensions' ), 0, 60, 1, _x( 's', 'unit after a number of seconds', 'studiare-extensions' ), array( 'show_if' => 'greeting.enabled' ) );
						?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Where to show', 'studiare-extensions' ); ?></h2>
					</header>
					<div class="stx-fields">
						<?php
						Fields::segmented(
							'display.devices',
							__( 'Devices', 'studiare-extensions' ),
							array(
								'all'     => __( 'All', 'studiare-extensions' ),
								'desktop' => __( 'Computers and tablets', 'studiare-extensions' ),
								'mobile'  => __( 'Phones only', 'studiare-extensions' ),
							),
							array( 'help' => __( 'Phones are screens narrower than 768px.', 'studiare-extensions' ) )
						);
						Fields::toggle( 'display.hide_on_checkout', __( 'Hide on the checkout page', 'studiare-extensions' ), array( 'help' => __( 'Keeps buyers focused on completing the order.', 'studiare-extensions' ) ) );
						Fields::toggle( 'display.hide_on_cart', __( 'Hide on the cart page', 'studiare-extensions' ) );
						Fields::text(
							'display.hide_for_ids',
							__( 'Hide on these pages or posts (IDs)', 'studiare-extensions' ),
							array(
								'placeholder' => '12, 345',
								'dir'         => 'ltr',
								'help'        => __( 'Comma separated IDs, e.g. landing pages that have their own call to action.', 'studiare-extensions' ),
							)
						);
						Fields::text(
							'display.z_index',
							__( 'Stacking order (z-index)', 'studiare-extensions' ),
							array(
								'type' => 'number',
								'dir'  => 'ltr',
								'help' => __( 'Raise it if a popup or chat widget covers the button.', 'studiare-extensions' ),
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
						<label class="stx-segmented__option"><input type="radio" name="stx-sb-preview-scheme" value="light" data-stx-preview-scheme checked><span><?php esc_html_e( 'Light', 'studiare-extensions' ); ?></span></label>
						<label class="stx-segmented__option"><input type="radio" name="stx-sb-preview-scheme" value="dark" data-stx-preview-scheme><span><?php esc_html_e( 'Dark', 'studiare-extensions' ); ?></span></label>
					</div>
					<div class="stx-segmented stx-segmented--sm" role="radiogroup" aria-label="<?php esc_attr_e( 'Preview state', 'studiare-extensions' ); ?>">
						<label class="stx-segmented__option"><input type="radio" name="stx-sb-preview-state" value="open" data-stx-preview-state checked><span><?php esc_html_e( 'Open', 'studiare-extensions' ); ?></span></label>
						<label class="stx-segmented__option"><input type="radio" name="stx-sb-preview-state" value="closed" data-stx-preview-state><span><?php esc_html_e( 'Closed', 'studiare-extensions' ); ?></span></label>
					</div>
				</div>

				<div class="stx-phone">
					<div class="stx-phone__notch" aria-hidden="true"></div>
					<div class="stx-phone__screen stx-stage stx-sb-stage" data-stx-preview-screen dir="<?php echo esc_attr( Site::direction() ); ?>">
						<div class="stx-mock" aria-hidden="true">
							<div class="stx-mock__header"><span></span><i></i></div>
							<div class="stx-mock__hero"></div>
							<div class="stx-mock__row"><span></span><span></span></div>
							<div class="stx-mock__card"></div>
							<div class="stx-mock__card"></div>
						</div>
						<div data-stx-preview-host></div>
						<p class="stx-sb-stage__note" data-stx-preview-empty hidden><?php esc_html_e( 'Fill in a channel to see the button.', 'studiare-extensions' ); ?></p>
						<p class="stx-sb-stage__note" data-stx-preview-hidden hidden><?php esc_html_e( 'Hidden on phones (Position & display tab).', 'studiare-extensions' ); ?></p>
					</div>
				</div>

				<p class="stx-preview__caption">
					<?php esc_html_e( 'Links are not opened in the preview. On your site the Studiare font is used.', 'studiare-extensions' ); ?>
				</p>
			</div>
		</aside>
	</div>

	<?php require STUDIARE_EXT_DIR . 'includes/Admin/views/icon-picker.php'; ?>
</div>
