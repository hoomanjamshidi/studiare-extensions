<?php
/**
 * Settings panel for the page templates module.
 *
 * Static structure lives here; builder-admin.js renders the template pickers,
 * category rules, library and preview dialog from `stxAdmin.moduleData`.
 *
 * @var \StudiareExt\Modules\Builder\Module $module
 *
 * @package StudiareExt
 */

use StudiareExt\Admin\Fields;
use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Builder\Contact_Messages;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Library;
use StudiareExt\Modules\Builder\Newsletter;
use StudiareExt\Modules\Builder\Schema;
use StudiareExt\Modules\Builder\Thumbs;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$ui_icon = static function ( string $key ): string {
	return Icon_Library::svg( 'phosphor-duotone', $key, false, 'stx-ico' );
};

$elementor = Library::elementor_status();
$has_woo   = Context::has_woo();

$panel_tabs = array(
	'course'  => array( 'graduation', __( 'Course pages', 'studiare-extensions' ) ),
	'product' => array( 'bag', __( 'Product pages', 'studiare-extensions' ) ),
	'header'  => array( 'arrow-up', __( 'Header', 'studiare-extensions' ) ),
	'footer'  => array( 'chevron-down', __( 'Footer', 'studiare-extensions' ) ),
	'home'    => array( 'home', __( 'Pages', 'studiare-extensions' ) ),
	'blog'    => array( 'news', __( 'Blog', 'studiare-extensions' ) ),
	'library' => array( 'grid', __( 'Templates', 'studiare-extensions' ) ),
	'brand'   => array( 'palette', __( 'Colours & options', 'studiare-extensions' ) ),
);

$sample_select = static function ( string $type ) use ( $module ): void {
	$choices = array( '0' => __( 'Newest item (automatic)', 'studiare-extensions' ) );
	foreach ( $module->sample_choices( $type ) as $sample ) {
		$choices[ (string) $sample['id'] ] = $sample['title'];
	}

	Fields::select(
		$type . '.preview_id',
		'course' === $type ? __( 'Sample course for previews and the Elementor editor', 'studiare-extensions' ) : __( 'Sample product for previews and the Elementor editor', 'studiare-extensions' ),
		$choices,
		array( 'help' => __( 'Product widgets show this item\'s real data while you design.', 'studiare-extensions' ) )
	);
};

// Keys follow Schema::SEARCH_STYLES.
$search_styles = array(
	'detailed' => array( __( 'Detailed', 'studiare-extensions' ), __( 'Large images, highlighted matches, type, category and one detail per item, plus popular topics when nothing is found.', 'studiare-extensions' ) ),
	'compact'  => array( __( 'Compact', 'studiare-extensions' ), __( 'Small images with the title and price in a short list.', 'studiare-extensions' ) ),
);

$brand_labels = array(
	'accent'        => __( 'Accent (buttons, highlights)', 'studiare-extensions' ),
	'accent_strong' => __( 'Accent — hover & links', 'studiare-extensions' ),
	'accent_soft'   => __( 'Accent — light background', 'studiare-extensions' ),
	'on_accent'     => __( 'Text on accent', 'studiare-extensions' ),
	'ink'           => __( 'Headings', 'studiare-extensions' ),
	'text'          => __( 'Body text', 'studiare-extensions' ),
	'muted'         => __( 'Muted text', 'studiare-extensions' ),
	'bg'            => __( 'Page background', 'studiare-extensions' ),
	'card'          => __( 'Cards', 'studiare-extensions' ),
	'line'          => __( 'Borders', 'studiare-extensions' ),
	'dark'          => __( 'Dark areas', 'studiare-extensions' ),
	'success'       => __( 'Success / in stock', 'studiare-extensions' ),
	'danger'        => __( 'Warning / out of stock', 'studiare-extensions' ),
);
?>
<div class="stx-module stx-builder" data-stx-module="builder">

	<section class="stx-module-head">
		<span class="stx-module-head__icon" aria-hidden="true"><?php echo $ui_icon( 'palette' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<div class="stx-module-head__text">
			<h1><?php echo esc_html( $module->title() ); ?></h1>
			<p><?php echo esc_html( $module->description() ); ?></p>
		</div>
		<label class="stx-module-head__switch">
			<span data-stx-show-if="enabled"><?php esc_html_e( 'Active', 'studiare-extensions' ); ?></span>
			<span data-stx-show-if="!enabled"><?php esc_html_e( 'Inactive', 'studiare-extensions' ); ?></span>
			<span class="stx-switch stx-switch--lg">
				<input type="checkbox" data-stx-bind="enabled" aria-label="<?php esc_attr_e( 'Enable page templates', 'studiare-extensions' ); ?>">
				<span class="stx-switch__track" aria-hidden="true"></span>
			</span>
		</label>
	</section>

	<?php if ( ! $elementor['active'] ) : ?>
		<div class="stx-notice">
			<?php echo $ui_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p>
				<?php esc_html_e( 'Elementor is not active. The designs are Elementor templates, so install and activate the free Elementor plugin first.', 'studiare-extensions' ); ?>
				<a href="<?php echo esc_url( $elementor['installUrl'] ); ?>"><?php esc_html_e( 'Install Elementor', 'studiare-extensions' ); ?></a>
			</p>
		</div>
	<?php elseif ( ! $elementor['container'] ) : ?>
		<div class="stx-notice" data-stx-container-notice>
			<?php echo $ui_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p><?php esc_html_e( 'The designs use Elementor\'s Flexbox Containers, which are switched off on this site. Turn them on to display and edit the templates.', 'studiare-extensions' ); ?></p>
			<button type="button" class="stx-btn stx-btn--primary" data-stx-activate-container><?php esc_html_e( 'Turn on containers', 'studiare-extensions' ); ?></button>
		</div>
	<?php endif; ?>

	<?php if ( ! $has_woo ) : ?>
		<div class="stx-notice">
			<?php echo $ui_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p><?php esc_html_e( 'WooCommerce is not active, so course and product pages are unavailable. Headers and footers still work.', 'studiare-extensions' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="stx-module-body stx-module-body--wide">
		<div class="stx-settings">

			<div class="stx-tabs" role="tablist" data-stx-tabs="builder">
				<?php foreach ( $panel_tabs as $tab_id => $panel_tab ) : ?>
					<button type="button" class="stx-tab" role="tab" id="stx-tab-<?php echo esc_attr( $tab_id ); ?>" aria-controls="stx-panel-<?php echo esc_attr( $tab_id ); ?>" aria-selected="false" data-stx-tab="<?php echo esc_attr( $tab_id ); ?>">
						<?php echo $ui_icon( $panel_tab[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $panel_tab[1] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( Schema::SINGLE_TYPES as $single_type ) : ?>
				<?php $is_course = 'course' === $single_type; ?>
				<section class="stx-panel" role="tabpanel" id="stx-panel-<?php echo esc_attr( $single_type ); ?>" aria-labelledby="stx-tab-<?php echo esc_attr( $single_type ); ?>">
					<div class="stx-card">
						<header class="stx-card__head">
							<h2><?php echo esc_html( $is_course ? __( 'Layout for course pages', 'studiare-extensions' ) : __( 'Layout for product pages', 'studiare-extensions' ) ); ?></h2>
							<p>
								<?php
								echo esc_html(
									$is_course
										? __( 'Used by every course unless a category rule below or the course itself says otherwise. Courses are products marked as a course in Studiare.', 'studiare-extensions' )
										: __( 'Used by every product that is not a course, unless a category rule below or the product itself says otherwise.', 'studiare-extensions' )
								);
								?>
							</p>
						</header>
						<div class="stx-tpl-grid" data-stx-picker="<?php echo esc_attr( $single_type ); ?>" data-path="<?php echo esc_attr( $single_type ); ?>.default" data-keywords="theme"></div>
					</div>

					<div class="stx-card">
						<header class="stx-card__head stx-card__head--split">
							<div>
								<h2><?php esc_html_e( 'Rules by category', 'studiare-extensions' ); ?></h2>
								<p><?php esc_html_e( 'Give some categories a different layout. Rules are checked from top to bottom and the first match wins. A layout chosen on the product edit screen always comes first.', 'studiare-extensions' ); ?></p>
							</div>
							<button type="button" class="stx-btn stx-btn--soft" data-stx-add-rule="<?php echo esc_attr( $single_type ); ?>">
								<?php echo $ui_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Add rule', 'studiare-extensions' ); ?></span>
							</button>
						</header>
						<div class="stx-rules" data-stx-rules="<?php echo esc_attr( $single_type ); ?>"></div>
					</div>

					<div class="stx-card">
						<div class="stx-fields">
							<?php $sample_select( $single_type ); ?>
						</div>
					</div>
				</section>
			<?php endforeach; ?>

			<?php foreach ( array( 'header', 'footer' ) as $area ) : ?>
				<?php $is_header = 'header' === $area; ?>
				<section class="stx-panel" role="tabpanel" id="stx-panel-<?php echo esc_attr( $area ); ?>" aria-labelledby="stx-tab-<?php echo esc_attr( $area ); ?>">
					<?php if ( $is_header ) : ?>
						<div class="stx-card">
							<header class="stx-card__head">
								<h2><?php esc_html_e( 'Sticky header', 'studiare-extensions' ); ?></h2>
								<p><?php esc_html_e( 'Keeps the Studiare+ header at the top of the screen. Smart hides it while visitors scroll down and brings it back as soon as they scroll up. The theme\'s own header follows Studiare\'s settings (Header → Sticky Header).', 'studiare-extensions' ); ?></p>
							</header>
							<div class="stx-fields stx-fields--2">
								<?php
								$sticky_options = array(
									'none'      => __( 'Off', 'studiare-extensions' ),
									'always'    => __( 'Always', 'studiare-extensions' ),
									'scroll_up' => __( 'Smart', 'studiare-extensions' ),
								);
								Fields::segmented( 'header.sticky_desktop', __( 'Desktop', 'studiare-extensions' ), $sticky_options );
								Fields::segmented( 'header.sticky_mobile', __( 'Phones & tablets', 'studiare-extensions' ), $sticky_options );
								?>
							</div>
						</div>
					<?php endif; ?>

					<div class="stx-card">
						<header class="stx-card__head">
							<h2 class="stx-device-title"><?php echo $ui_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $is_header ? __( 'Header on desktop', 'studiare-extensions' ) : __( 'Footer on desktop', 'studiare-extensions' ) ); ?></h2>
							<p><?php esc_html_e( 'Shown on screens wider than the breakpoint (Colours & options tab).', 'studiare-extensions' ); ?></p>
						</header>
						<div class="stx-tpl-grid" data-stx-picker="<?php echo esc_attr( $area ); ?>" data-path="<?php echo esc_attr( $area ); ?>.desktop" data-keywords="theme,none"></div>
					</div>

					<div class="stx-card">
						<header class="stx-card__head">
							<h2 class="stx-device-title"><?php echo $ui_icon( 'mobile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $is_header ? __( 'Header on phones & tablets', 'studiare-extensions' ) : __( 'Footer on phones & tablets', 'studiare-extensions' ) ); ?></h2>
							<p><?php esc_html_e( 'Each device gets only its own version: pick the same design, a different one, the theme\'s, or nothing.', 'studiare-extensions' ); ?></p>
						</header>
						<div class="stx-tpl-grid" data-stx-picker="<?php echo esc_attr( $area ); ?>" data-path="<?php echo esc_attr( $area ); ?>.mobile" data-keywords="same,theme,none"></div>
					</div>

					<?php if ( $is_header ) : ?>
						<div class="stx-card">
							<header class="stx-card__head">
								<h2><?php esc_html_e( 'Live search results', 'studiare-extensions' ); ?></h2>
								<p><?php esc_html_e( 'How the Search widget lists matches while visitors type. Enter always opens the full results page.', 'studiare-extensions' ); ?></p>
							</header>
							<div class="stx-style-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Live search results', 'studiare-extensions' ); ?>">
								<?php foreach ( $search_styles as $style_id => $search_style ) : ?>
									<label class="stx-style-card">
										<input type="radio" name="stx-search-results" value="<?php echo esc_attr( $style_id ); ?>" data-stx-bind="options.search_results">
										<span class="stx-style-card__stage stx-style-card__stage--thumb" aria-hidden="true"><?php echo Thumbs::svg( 'search-' . $style_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
										<span class="stx-style-card__body">
											<span class="stx-style-card__title"><?php echo esc_html( $search_style[0] ); ?> <i class="stx-style-card__check" aria-hidden="true"><?php echo Icon_Library::svg( 'tabler', 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></i></span>
											<span class="stx-style-card__desc"><?php echo esc_html( $search_style[1] ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>

			<section class="stx-panel" role="tabpanel" id="stx-panel-home" aria-labelledby="stx-tab-home">
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Ready-made pages', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Home, about us and contact us pages. Pick a design and create a new page from it. The page is a normal Elementor page: change the texts and pictures, move or remove sections, add your own. Products, categories, posts and teachers come from your site automatically.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-page-designs" data-stx-home-designs></div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Pages made from these designs', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Editing a page never changes the design it came from, so you can make as many pages as you like.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-home-pages" data-stx-home-pages></div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head stx-card__head--split">
						<div>
							<h2><?php esc_html_e( 'Newsletter subscribers', 'studiare-extensions' ); ?> <span class="stx-counter"><?php echo esc_html( number_format_i18n( Newsletter::count() ) ); ?></span></h2>
							<p><?php esc_html_e( 'Emails sent through the Newsletter widget\'s built-in form. Download them to import into your email service.', 'studiare-extensions' ); ?></p>
						</div>
						<div class="stx-inline-actions">
							<a class="stx-btn stx-btn--soft" href="<?php echo esc_url( Newsletter::export_url() ); ?>">
								<?php echo $ui_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Download CSV', 'studiare-extensions' ); ?></span>
							</a>
						</div>
					</header>
				</div>

				<div class="stx-card">
					<header class="stx-card__head stx-card__head--split">
						<div>
							<h2><?php esc_html_e( 'Contact messages', 'studiare-extensions' ); ?> <span class="stx-counter"><?php echo esc_html( number_format_i18n( Contact_Messages::count() ) ); ?></span></h2>
							<p><?php esc_html_e( 'Messages sent through the Contact form widget. They are kept here even when email does not work on your host, and each one can also be emailed to you (set the address on the widget).', 'studiare-extensions' ); ?></p>
						</div>
						<div class="stx-inline-actions">
							<a class="stx-btn stx-btn--primary" href="<?php echo esc_url( Contact_Messages::inbox_url() ); ?>">
								<?php echo $ui_icon( 'chat' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Read the messages', 'studiare-extensions' ); ?></span>
							</a>
							<a class="stx-btn stx-btn--soft" href="<?php echo esc_url( Contact_Messages::export_url() ); ?>">
								<?php echo $ui_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Download CSV', 'studiare-extensions' ); ?></span>
							</a>
						</div>
					</header>
				</div>
			</section>

			<section class="stx-panel" role="tabpanel" id="stx-panel-blog" aria-labelledby="stx-tab-blog">
				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Layout for post lists', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Used on the blog page, categories, tags, author pages and date archives. The list shows the posts of the page being viewed, with page numbers.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-tpl-grid" data-stx-picker="archive" data-path="blog.archive" data-keywords="theme"></div>
					<div class="stx-fields stx-fields--after-grid">
						<?php
						Fields::toggle(
							'blog.search',
							__( 'Use it for blog searches too', 'studiare-extensions' ),
							array( 'help' => __( 'Searches limited to blog posts (for example the Search widget set to "Blog posts") then look like the blog. Other searches keep the theme\'s results page.', 'studiare-extensions' ) )
						);
						?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Layout for blog posts', 'studiare-extensions' ); ?></h2>
						<p><?php esc_html_e( 'Used by every blog post. The comments keep your theme\'s own design.', 'studiare-extensions' ); ?></p>
					</header>
					<div class="stx-tpl-grid" data-stx-picker="post" data-path="blog.post" data-keywords="theme"></div>
				</div>
			</section>

			<section class="stx-panel" role="tabpanel" id="stx-panel-library" aria-labelledby="stx-tab-library">
				<div class="stx-card">
					<header class="stx-card__head stx-card__head--split">
						<div>
							<h2><?php esc_html_e( 'Template library', 'studiare-extensions' ); ?></h2>
							<p><?php esc_html_e( 'Every design is a normal Elementor template: edit it, duplicate it to try variations, or restore a ready-made design to its original state.', 'studiare-extensions' ); ?></p>
						</div>
						<div class="stx-inline-actions">
							<button type="button" class="stx-btn stx-btn--ghost" data-stx-install-presets>
								<?php echo $ui_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Reinstall missing designs', 'studiare-extensions' ); ?></span>
							</button>
							<button type="button" class="stx-btn stx-btn--primary" data-stx-new-template>
								<?php echo $ui_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'New template', 'studiare-extensions' ); ?></span>
							</button>
						</div>
					</header>
					<div class="stx-library" data-stx-library></div>
				</div>
			</section>

			<section class="stx-panel" role="tabpanel" id="stx-panel-brand" aria-labelledby="stx-tab-brand">
				<div class="stx-card">
					<header class="stx-card__head stx-card__head--split">
						<div>
							<h2><?php esc_html_e( 'Brand colours', 'studiare-extensions' ); ?></h2>
							<p><?php esc_html_e( 'Every design uses these colours, including Studiare\'s dark mode. Anything you set on a widget in Elementor overrides them.', 'studiare-extensions' ); ?></p>
						</div>
						<div class="stx-inline-actions">
							<?php if ( Theme_Bridge::is_active() ) : ?>
								<button type="button" class="stx-btn stx-btn--ghost" data-stx-theme-colors><?php esc_html_e( 'Use Studiare\'s colours', 'studiare-extensions' ); ?></button>
							<?php endif; ?>
							<?php if ( $elementor['active'] ) : ?>
								<button type="button" class="stx-btn stx-btn--soft" data-stx-sync-colors><?php esc_html_e( 'Add to Elementor global colours', 'studiare-extensions' ); ?></button>
							<?php endif; ?>
						</div>
					</header>
					<div class="stx-color-grid">
						<?php foreach ( Schema::BRAND_DEFAULTS as $key => $fallback ) : ?>
							<?php Fields::color( 'brand.' . $key, $brand_labels[ $key ], $fallback ); ?>
						<?php endforeach; ?>
					</div>
					<div class="stx-fields" style="margin-top:22px">
						<?php Fields::range( 'brand.radius', __( 'Corner roundness', 'studiare-extensions' ), 0, 40, 1, 'px' ); ?>
					</div>
				</div>

				<div class="stx-card">
					<header class="stx-card__head">
						<h2><?php esc_html_e( 'Options', 'studiare-extensions' ); ?></h2>
					</header>
					<div class="stx-fields">
						<?php
						Fields::range(
							'breakpoint',
							__( 'Phone & tablet breakpoint', 'studiare-extensions' ),
							480,
							1600,
							1,
							'px',
							array( 'help' => __( 'At this width and below, the phone header and footer are shown. 1024 matches Elementor\'s tablet breakpoint.', 'studiare-extensions' ) )
						);

						$packs = array();
						foreach ( Icon_Library::catalog()['packs'] as $pack_id => $pack ) {
							if ( Icon_Library::FONT_AWESOME !== $pack_id ) {
								$packs[ $pack_id ] = $pack['label'];
							}
						}
						Fields::select( 'options.icon_pack', __( 'Icon style in the designs', 'studiare-extensions' ), $packs );

						Fields::toggle(
							'options.persian_digits',
							__( 'Persian digits', 'studiare-extensions' ),
							array( 'help' => __( 'Show prices, counts and years as ۱۲۳ with Persian separators on Persian sites.', 'studiare-extensions' ) )
						);
						Fields::toggle(
							'options.hide_theme_title',
							__( 'Hide Studiare\'s title bar on template pages', 'studiare-extensions' ),
							array( 'help' => __( 'Course and product templates have their own title and breadcrumb.', 'studiare-extensions' ) )
						);
						?>
					</div>
				</div>
			</section>

		</div>
	</div>
</div>
