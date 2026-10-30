<?php
/**
 * Settings panel of the cart & checkout module: the cart page, the
 * checkout page and the billing fields. WooCommerce's fields are rows bound
 * by their keys (`fields.billing.<key>.*`); the admin's own fields are
 * cloned from the template at the bottom by checkout-admin.js, which also
 * keeps `fields.order` in step with the rows.
 *
 * @var \StudiareExt\Modules\Checkout\Module $module
 *
 * @package StudiareExt
 */

use StudiareExt\Admin\Fields;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Modules\Checkout\Module;
use StudiareExt\Modules\Checkout\Schema;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$ui_icon = static function ( string $key ): string {
	return Icon_Library::svg( 'phosphor-duotone', $key, false, 'stx-ico' );
};

$settings   = $module->settings();
$labels     = Module::default_labels();
$panel_tabs = array(
	'cart'     => array( 'cart', __( 'Cart page', 'studiare-extensions' ) ),
	'checkout' => array( 'wallet', __( 'Checkout page', 'studiare-extensions' ) ),
	'fields'   => array( 'list', __( 'Billing fields', 'studiare-extensions' ) ),
);
$designs    = array(
	'stx'   => __( 'Studiare+ design', 'studiare-extensions' ),
	'theme' => __( 'Theme design', 'studiare-extensions' ),
);
$required   = array(
	'auto' => __( 'As WooCommerce', 'studiare-extensions' ),
	'yes'  => __( 'Required', 'studiare-extensions' ),
	'no'   => __( 'Optional', 'studiare-extensions' ),
);
$widths     = array(
	'full' => __( 'Full width', 'studiare-extensions' ),
	'half' => __( 'Half width', 'studiare-extensions' ),
);
$types      = array(
	'text'          => __( 'Text', 'studiare-extensions' ),
	'textarea'      => __( 'Long text', 'studiare-extensions' ),
	'number'        => __( 'Number', 'studiare-extensions' ),
	'tel'           => __( 'Phone number', 'studiare-extensions' ),
	'email'         => __( 'Email', 'studiare-extensions' ),
	'select'        => __( 'Drop-down list', 'studiare-extensions' ),
	'checkbox'      => __( 'Checkbox', 'studiare-extensions' ),
	'national_code' => __( 'National code', 'studiare-extensions' ),
);
?>
<div class="stx-module" data-stx-module="checkout">

	<section class="stx-module-head">
		<span class="stx-module-head__icon" aria-hidden="true"><?php echo $ui_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<div class="stx-module-head__text">
			<h1><?php echo esc_html( $module->title() ); ?></h1>
			<p><?php echo esc_html( $module->description() ); ?></p>
		</div>
		<label class="stx-module-head__switch">
			<span data-stx-show-if="enabled"><?php esc_html_e( 'Active', 'studiare-extensions' ); ?></span>
			<span data-stx-show-if="!enabled"><?php esc_html_e( 'Inactive', 'studiare-extensions' ); ?></span>
			<span class="stx-switch stx-switch--lg">
				<input type="checkbox" data-stx-bind="enabled" aria-label="<?php esc_attr_e( 'Enable the cart & checkout designs', 'studiare-extensions' ); ?>">
				<span class="stx-switch__track" aria-hidden="true"></span>
			</span>
		</label>
	</section>

	<p class="stx-inline-note">
		<?php echo $ui_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
		<span>
			<?php esc_html_e( 'For WooCommerce\'s classic cart and checkout pages (the [woocommerce_cart] and [woocommerce_checkout] shortcodes), which Studiare uses. Gateways and other plugins keep working.', 'studiare-extensions' ); ?>
			<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View the cart', 'studiare-extensions' ); ?></a> ·
				<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View the checkout', 'studiare-extensions' ); ?></a>
			<?php endif; ?>
		</span>
	</p>

	<div class="stx-tabs" role="tablist" data-stx-tabs="checkout">
		<?php foreach ( $panel_tabs as $tab_id => $panel_tab ) : ?>
			<button type="button" class="stx-tab" role="tab" id="stx-tab-<?php echo esc_attr( $tab_id ); ?>" aria-controls="stx-panel-<?php echo esc_attr( $tab_id ); ?>" aria-selected="false" data-stx-tab="<?php echo esc_attr( $tab_id ); ?>">
				<?php echo $ui_icon( $panel_tab[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
				<span><?php echo esc_html( $panel_tab[1] ); ?></span>
			</button>
		<?php endforeach; ?>
	</div>

	<?php /* ------------------------------------------------------- Cart */ ?>
	<section class="stx-panel" role="tabpanel" id="stx-panel-cart" aria-labelledby="stx-tab-cart" data-stx-panel="cart">
		<div class="stx-card">
			<header class="stx-card__head">
				<h2><?php esc_html_e( 'Cart page', 'studiare-extensions' ); ?></h2>
				<p><?php esc_html_e( 'Items as cards with the total and the checkout button beside them, and a bar with the total at the bottom of phones.', 'studiare-extensions' ); ?></p>
			</header>
			<div class="stx-fields">
				<?php
				Fields::segmented( 'cart.design', __( 'Design', 'studiare-extensions' ), $designs );
				Fields::toggle(
					'cart.steps',
					__( 'Purchase steps', 'studiare-extensions' ),
					array(
						'help'    => __( 'Cart, details & payment, access: the three steps at the top of the cart, checkout and thank-you pages.', 'studiare-extensions' ),
						'show_if' => 'cart.design=stx',
					)
				);
				Fields::toggle(
					'cart.auto_update',
					__( 'Update the total right away', 'studiare-extensions' ),
					array(
						'help'    => __( 'Changing a quantity updates the cart without the "Update cart" button.', 'studiare-extensions' ),
						'show_if' => 'cart.design=stx',
					)
				);
				Fields::segmented(
					'cart.coupon',
					__( 'Coupon box', 'studiare-extensions' ),
					array(
						'toggle' => __( 'Behind a link', 'studiare-extensions' ),
						'open'   => __( 'Always open', 'studiare-extensions' ),
						'hidden' => __( 'Hidden', 'studiare-extensions' ),
					),
					array(
						'help'    => __( 'Coupon messages show right under the box.', 'studiare-extensions' ),
						'show_if' => 'cart.design=stx',
					)
				);
				Fields::toggle( 'cart.continue', __( '"Continue shopping" link', 'studiare-extensions' ), array( 'show_if' => 'cart.design=stx' ) );
				Fields::toggle(
					'cart.cross_sells',
					__( 'Suggested products under the cart', 'studiare-extensions' ),
					array(
						'help'    => __( 'The products set as cross-sells of the items in the cart.', 'studiare-extensions' ),
						'show_if' => 'cart.design=stx',
					)
				);
				Fields::toggle(
					'cart.mobile_bar',
					__( 'Total bar on phones', 'studiare-extensions' ),
					array(
						'help'    => __( 'Keeps the total and the checkout button in reach at the bottom of the screen until the summary scrolls into view.', 'studiare-extensions' ),
						'show_if' => 'cart.design=stx',
					)
				);
				Fields::text( 'cart.note', __( 'Note under the summary', 'studiare-extensions' ), array( 'show_if' => 'cart.design=stx' ) );
				?>
			</div>
		</div>

		<div class="stx-card">
			<header class="stx-card__head">
				<h2><?php esc_html_e( 'Mini cart', 'studiare-extensions' ); ?></h2>
				<p><?php esc_html_e( 'The cart that opens from the header and the bottom navigation: items as cards, the total and a checkout button that always stays in view.', 'studiare-extensions' ); ?></p>
			</header>
			<div class="stx-fields">
				<?php
				Fields::segmented( 'mini.design', __( 'Design', 'studiare-extensions' ), $designs );
				Fields::toggle(
					'mini.savings',
					__( '"You save" line', 'studiare-extensions' ),
					array(
						'show_if' => 'mini.design=stx',
						'help'    => __( 'What sale prices and coupons save the buyer, above the total.', 'studiare-extensions' ),
					)
				);
				?>
			</div>
		</div>

		<div class="stx-card">
			<header class="stx-card__head">
				<h2><?php esc_html_e( 'Colour', 'studiare-extensions' ); ?></h2>
				<p><?php esc_html_e( 'Buttons, the current step and highlights of both pages. Empty uses the theme\'s main colour; dark mode follows the theme.', 'studiare-extensions' ); ?></p>
			</header>
			<div class="stx-fields">
				<?php Fields::color( 'colors.accent', __( 'Accent colour', 'studiare-extensions' ), 'var(--primary_color)' ); ?>
			</div>
		</div>
	</section>

	<?php /* --------------------------------------------------- Checkout */ ?>
	<section class="stx-panel" role="tabpanel" id="stx-panel-checkout" aria-labelledby="stx-tab-checkout" data-stx-panel="checkout" hidden>
		<div class="stx-card">
			<header class="stx-card__head">
				<h2><?php esc_html_e( 'Checkout page', 'studiare-extensions' ); ?></h2>
				<p><?php esc_html_e( 'Two short steps, details and payment, beside a sticky order summary with the coupon box.', 'studiare-extensions' ); ?></p>
			</header>
			<div class="stx-fields">
				<?php
				Fields::segmented( 'checkout.design', __( 'Design', 'studiare-extensions' ), $designs );
				Fields::toggle( 'checkout.steps', __( 'Purchase steps', 'studiare-extensions' ), array( 'show_if' => 'checkout.design=stx' ) );
				Fields::segmented(
					'checkout.summary',
					__( 'Order summary on phones', 'studiare-extensions' ),
					array(
						'collapsed' => __( 'Folded, total only', 'studiare-extensions' ),
						'open'      => __( 'Open', 'studiare-extensions' ),
					),
					array( 'show_if' => 'checkout.design=stx' )
				);
				Fields::segmented(
					'checkout.coupon',
					__( 'Coupon box', 'studiare-extensions' ),
					array(
						'toggle' => __( 'In the summary', 'studiare-extensions' ),
						'hidden' => __( 'Hidden', 'studiare-extensions' ),
					),
					array( 'show_if' => 'checkout.design=stx' )
				);
				Fields::text( 'checkout.note', __( 'Note under the payment methods', 'studiare-extensions' ), array( 'show_if' => 'checkout.design=stx' ) );
				Fields::text(
					'checkout.button',
					__( 'Text of the order button', 'studiare-extensions' ),
					array(
						'placeholder' => __( 'e.g. Pay and start learning', 'studiare-extensions' ),
						'help'        => __( 'Empty keeps WooCommerce\'s text. Some gateways show their own text, such as "Proceed to PayPal".', 'studiare-extensions' ),
					)
				);
				?>
			</div>
		</div>

		<div class="stx-card">
			<header class="stx-card__head">
				<h2><?php esc_html_e( 'Thank-you page', 'studiare-extensions' ); ?></h2>
				<p><?php esc_html_e( 'The page after payment: the order\'s state with the buyer\'s name, buttons to their courses or order, the courses ready to start and what happens next.', 'studiare-extensions' ); ?></p>
			</header>
			<div class="stx-fields">
				<?php
				Fields::segmented( 'thankyou.design', __( 'Design', 'studiare-extensions' ), $designs );
				Fields::toggle( 'thankyou.steps', __( 'Purchase steps', 'studiare-extensions' ), array( 'show_if' => 'thankyou.design=stx' ) );
				Fields::toggle(
					'thankyou.next',
					__( '"What happens next" card', 'studiare-extensions' ),
					array(
						'show_if' => 'thankyou.design=stx',
						'help'    => __( 'Three short steps from payment to learning, or to delivery for shipped products.', 'studiare-extensions' ),
					)
				);
				Fields::toggle(
					'thankyou.celebrate',
					__( 'Celebration animation', 'studiare-extensions' ),
					array(
						'show_if' => 'thankyou.design=stx',
						'help'    => __( 'A little confetti around the tick after a successful payment. Visitors who turned motion off see none.', 'studiare-extensions' ),
					)
				);
				Fields::text(
					'thankyou.note',
					__( 'Message to buyers', 'studiare-extensions' ),
					array(
						'show_if'     => 'thankyou.design=stx',
						'placeholder' => __( 'e.g. Join our support group to ask the teacher your questions.', 'studiare-extensions' ),
						'help'        => __( 'Shown under the buttons, except when a payment failed. Empty shows nothing.', 'studiare-extensions' ),
					)
				);
				?>
			</div>
		</div>
	</section>

	<?php /* ----------------------------------------------------- Fields */ ?>
	<section class="stx-panel" role="tabpanel" id="stx-panel-fields" aria-labelledby="stx-tab-fields" data-stx-panel="fields" hidden>
		<div class="stx-card">
			<header class="stx-card__head">
				<h2><?php esc_html_e( 'Rules', 'studiare-extensions' ); ?></h2>
			</header>
			<div class="stx-fields">
				<?php
				Fields::toggle( 'fields.iran_phone', __( 'Iranian mobile number', 'studiare-extensions' ), array( 'help' => __( 'The phone must be a mobile number such as 09123456789. Persian digits, +98 and 0098 are accepted and saved as 09….', 'studiare-extensions' ) ) );
				?>
			</div>
		</div>

		<div class="stx-card">
			<header class="stx-card__head">
				<h2><?php esc_html_e( 'Billing fields', 'studiare-extensions' ); ?></h2>
				<p><?php esc_html_e( 'Switch fields on or off, rename them, make them required or optional and drag them into order. Empty texts keep WooCommerce\'s.', 'studiare-extensions' ); ?></p>
				<p><?php esc_html_e( 'A cart of only courses and downloads (virtual or downloadable products) needs no address: it asks only for the fields marked "Also for courses and downloads". By default these are the name, mobile and email.', 'studiare-extensions' ); ?></p>
			</header>

			<ul class="stx-items stx-co-fields" data-stx-co-fields>
				<?php foreach ( array_intersect( $settings['fields']['order'], Schema::CORE_FIELDS ) as $field_key ) : ?>
					<?php
					$bind  = 'fields.billing.' . $field_key . '.';
					$field = 'stx-co-' . $field_key . '-';
					?>
					<li class="stx-item" data-field="<?php echo esc_attr( $field_key ); ?>">
						<div class="stx-item__row">
							<span class="stx-item__handle" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<button type="button" class="stx-item__summary" data-item-toggle aria-expanded="false" aria-controls="<?php echo esc_attr( $field . 'editor' ); ?>">
								<span class="stx-item__label" data-field-label><?php echo esc_html( $labels[ $field_key ] ); ?></span>
								<span class="stx-item__chips" data-field-chips></span>
							</button>
							<span class="stx-item__tools">
								<button type="button" class="stx-move" data-field-move="up" aria-label="<?php esc_attr_e( 'Move up', 'studiare-extensions' ); ?>">▲</button>
								<button type="button" class="stx-move" data-field-move="down" aria-label="<?php esc_attr_e( 'Move down', 'studiare-extensions' ); ?>">▼</button>
								<label class="stx-switch stx-switch--sm" title="<?php esc_attr_e( 'Show this field', 'studiare-extensions' ); ?>">
									<input type="checkbox" data-stx-bind="<?php echo esc_attr( $bind . 'enabled' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: field label. */ __( 'Show %s', 'studiare-extensions' ), $labels[ $field_key ] ) ); ?>">
									<span class="stx-switch__track" aria-hidden="true"></span>
								</label>
								<button type="button" class="stx-icon-btn stx-item__chevron" data-item-toggle tabindex="-1" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
							</span>
						</div>
						<div class="stx-item__editor" id="<?php echo esc_attr( $field . 'editor' ); ?>" hidden>
							<div class="stx-fields stx-fields--2">
								<div class="stx-field">
									<label class="stx-field__label" for="<?php echo esc_attr( $field . 'label' ); ?>"><?php esc_html_e( 'Label', 'studiare-extensions' ); ?></label>
									<input type="text" class="stx-input" id="<?php echo esc_attr( $field . 'label' ); ?>" data-stx-bind="<?php echo esc_attr( $bind . 'label' ); ?>" maxlength="60" placeholder="<?php echo esc_attr( $labels[ $field_key ] ); ?>">
								</div>
								<div class="stx-field">
									<label class="stx-field__label" for="<?php echo esc_attr( $field . 'placeholder' ); ?>"><?php esc_html_e( 'Hint inside the field', 'studiare-extensions' ); ?></label>
									<input type="text" class="stx-input" id="<?php echo esc_attr( $field . 'placeholder' ); ?>" data-stx-bind="<?php echo esc_attr( $bind . 'placeholder' ); ?>" maxlength="80">
								</div>
								<?php
								Fields::segmented( $bind . 'required', __( 'Filling in', 'studiare-extensions' ), $required );
								Fields::segmented( $bind . 'width', __( 'Width', 'studiare-extensions' ), $widths );
								Fields::toggle( $bind . 'digital', __( 'Also for courses and downloads', 'studiare-extensions' ), array( 'help' => __( 'Off: asked only when the cart holds a product that is shipped.', 'studiare-extensions' ) ) );
								?>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="stx-co-fields-add">
				<button type="button" class="stx-btn stx-btn--soft" data-stx-co-add>
					<?php echo Icon_Library::svg( 'phosphor', 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Add a field', 'studiare-extensions' ); ?>
				</button>
				<p class="stx-field__help"><?php esc_html_e( 'Your own fields, such as a national code, field of study or "How did you hear about us?". Their answers are saved with the order and shown in the order screen and emails.', 'studiare-extensions' ); ?></p>
			</div>

			<template data-stx-co-template>
				<li class="stx-item stx-item--custom" data-field="__ID__">
					<div class="stx-item__row">
						<span class="stx-item__handle" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<button type="button" class="stx-item__summary" data-item-toggle aria-expanded="false" aria-controls="stx-co-__ID__-editor">
							<span class="stx-item__label" data-field-label></span>
							<span class="stx-item__chips" data-field-chips></span>
						</button>
						<span class="stx-item__tools">
							<button type="button" class="stx-move" data-field-move="up" aria-label="<?php esc_attr_e( 'Move up', 'studiare-extensions' ); ?>">▲</button>
							<button type="button" class="stx-move" data-field-move="down" aria-label="<?php esc_attr_e( 'Move down', 'studiare-extensions' ); ?>">▼</button>
							<label class="stx-switch stx-switch--sm" title="<?php esc_attr_e( 'Show this field', 'studiare-extensions' ); ?>">
								<input type="checkbox" data-custom-bind="enabled" aria-label="<?php esc_attr_e( 'Show this field', 'studiare-extensions' ); ?>">
								<span class="stx-switch__track" aria-hidden="true"></span>
							</label>
							<button type="button" class="stx-icon-btn stx-icon-btn--danger" data-custom-action="delete" aria-label="<?php esc_attr_e( 'Delete', 'studiare-extensions' ); ?>" title="<?php esc_attr_e( 'Delete', 'studiare-extensions' ); ?>"><?php echo Icon_Library::svg( 'phosphor', 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
							<button type="button" class="stx-icon-btn stx-item__chevron" data-item-toggle tabindex="-1" aria-hidden="true"><?php echo Icon_Library::svg( 'phosphor', 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						</span>
					</div>
					<div class="stx-item__editor" id="stx-co-__ID__-editor" hidden>
						<div class="stx-fields stx-fields--2">
							<div class="stx-field">
								<label class="stx-field__label" for="stx-co-__ID__-label"><?php esc_html_e( 'Label', 'studiare-extensions' ); ?></label>
								<input type="text" class="stx-input" id="stx-co-__ID__-label" data-custom-bind="label" maxlength="60" placeholder="<?php esc_attr_e( 'e.g. National code', 'studiare-extensions' ); ?>">
							</div>
							<div class="stx-field">
								<label class="stx-field__label" for="stx-co-__ID__-type"><?php esc_html_e( 'Type', 'studiare-extensions' ); ?></label>
								<div class="stx-select">
									<select id="stx-co-__ID__-type" data-custom-bind="type">
										<?php foreach ( $types as $type_key => $type_label ) : ?>
											<option value="<?php echo esc_attr( $type_key ); ?>"><?php echo esc_html( $type_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="stx-field">
								<label class="stx-field__label" for="stx-co-__ID__-placeholder"><?php esc_html_e( 'Hint inside the field', 'studiare-extensions' ); ?></label>
								<input type="text" class="stx-input" id="stx-co-__ID__-placeholder" data-custom-bind="placeholder" maxlength="80">
							</div>
							<div class="stx-field stx-field--segmented">
								<span class="stx-field__label"><?php esc_html_e( 'Width', 'studiare-extensions' ); ?></span>
								<div class="stx-segmented" role="radiogroup">
									<?php foreach ( $widths as $width_key => $width_label ) : ?>
										<label class="stx-segmented__option"><input type="radio" name="stx-co-__ID__-width" value="<?php echo esc_attr( $width_key ); ?>" data-custom-bind="width"><span><?php echo esc_html( $width_label ); ?></span></label>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="stx-field stx-field--full" data-custom-show-if="select">
								<label class="stx-field__label" for="stx-co-__ID__-options"><?php esc_html_e( 'Choices, one per line', 'studiare-extensions' ); ?></label>
								<textarea class="stx-input" id="stx-co-__ID__-options" rows="4" data-custom-bind="options"></textarea>
							</div>
							<label class="stx-switch-row stx-field--full" for="stx-co-__ID__-digital">
								<span class="stx-switch-row__text">
									<span class="stx-field__label"><?php esc_html_e( 'Also for courses and downloads', 'studiare-extensions' ); ?></span>
									<span class="stx-field__help"><?php esc_html_e( 'Off: asked only when the cart holds a product that is shipped.', 'studiare-extensions' ); ?></span>
								</span>
								<span class="stx-switch">
									<input type="checkbox" id="stx-co-__ID__-digital" data-custom-bind="digital">
									<span class="stx-switch__track" aria-hidden="true"></span>
								</span>
							</label>
							<label class="stx-switch-row stx-field--full" for="stx-co-__ID__-required">
								<span class="stx-switch-row__text">
									<span class="stx-field__label"><?php esc_html_e( 'Required', 'studiare-extensions' ); ?></span>
									<span class="stx-field__help" data-custom-show-if="national_code"><?php esc_html_e( 'Checked like a real national code: ten digits with a valid check digit.', 'studiare-extensions' ); ?></span>
								</span>
								<span class="stx-switch">
									<input type="checkbox" id="stx-co-__ID__-required" data-custom-bind="required">
									<span class="stx-switch__track" aria-hidden="true"></span>
								</span>
							</label>
						</div>
					</div>
				</li>
			</template>
		</div>

		<div class="stx-card">
			<header class="stx-card__head">
				<h2><?php esc_html_e( 'Order notes', 'studiare-extensions' ); ?></h2>
			</header>
			<div class="stx-fields">
				<?php
				Fields::toggle( 'fields.notes.enabled', __( 'Order notes field', 'studiare-extensions' ), array( 'help' => __( 'A free text box for a message with the order.', 'studiare-extensions' ) ) );
				Fields::text( 'fields.notes.label', __( 'Label', 'studiare-extensions' ), array( 'show_if' => 'fields.notes.enabled' ) );
				Fields::text( 'fields.notes.placeholder', __( 'Hint inside the field', 'studiare-extensions' ), array( 'show_if' => 'fields.notes.enabled' ) );
				?>
			</div>
		</div>
	</section>
</div>
