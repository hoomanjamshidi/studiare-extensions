<?php
/**
 * Page templates module: Elementor-built course pages, product pages,
 * headers, footers and the blog, with ready-made styles and per-category
 * rules, plus ready-made home, about us and contact us pages that are created
 * as regular pages.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder;

use StudiareExt\Admin\Admin;
use StudiareExt\Core\Module as Base_Module;
use StudiareExt\Core\Sanitizer;
use StudiareExt\Core\Theme_Bridge;
use StudiareExt\Modules\Builder\Elementor\Integration;

defined( 'ABSPATH' ) || exit;

final class Module extends Base_Module {

	/** @var Resolver|null */
	private $resolver = null;

	/** @var Single_Product|null */
	private $single = null;

	/** @var Blog_Pages|null */
	private $blog = null;

	public function id(): string {
		return 'builder';
	}

	public function title(): string {
		return __( 'Page templates', 'studiare-extensions' );
	}

	public function description(): string {
		return __( 'Ready-made Elementor designs for course pages, product pages, headers, footers, home, about us and contact us pages, and the blog — per category and per device.', 'studiare-extensions' );
	}

	public function icon(): string {
		return 'palette';
	}

	/** @return array<string, string> Kind → translated label. */
	public static function type_labels(): array {
		return array(
			'course'  => __( 'Course page', 'studiare-extensions' ),
			'product' => __( 'Product page', 'studiare-extensions' ),
			'header'  => __( 'Header', 'studiare-extensions' ),
			'footer'  => __( 'Footer', 'studiare-extensions' ),
			'home'    => __( 'Home page', 'studiare-extensions' ),
			'archive' => __( 'Blog archive', 'studiare-extensions' ),
			'post'    => __( 'Blog post', 'studiare-extensions' ),
			'about'   => __( 'About us page', 'studiare-extensions' ),
			'contact' => __( 'Contact us page', 'studiare-extensions' ),
		);
	}

	public function defaults(): array {
		return Schema::defaults();
	}

	/**
	 * Everything is registered even while the module is off: templates stay
	 * editable, widgets keep working on normal Elementor pages and admins can
	 * preview designs before switching them on. The resolver applies the
	 * `enabled` flag to what visitors see.
	 */
	public function register(): void {
		Context::init( $this );

		( new Template_Post_Type( $this ) )->register();
		( new Newsletter() )->register();
		( new Contact_Messages() )->register();
		( new Assets( $this ) )->register();

		$this->single = new Single_Product( $this );
		$this->single->register();
		$this->blog = new Blog_Pages( $this );
		$this->blog->register();
		( new Header_Footer( $this ) )->register();

		// The live search serves the Search widget and renders with widget helpers, so it needs Elementor too.
		if ( did_action( 'elementor/loaded' ) ) {
			( new Integration( $this ) )->register();
			( new Live_Search( $this ) )->register();
		}

		if ( is_admin() ) {
			add_action( 'admin_init', array( Library::class, 'maybe_upgrade' ) );
			( new Product_Meta_Box( $this ) )->register();
			( new Library_Ajax( $this ) )->register();
			( new Contact_Inbox() )->register();
		}

		parent::register();
	}

	/** Nothing extra: see register(). */
	protected function boot(): void {}

	public function resolver(): Resolver {
		if ( null === $this->resolver ) {
			$this->resolver = new Resolver( $this );
		}

		return $this->resolver;
	}

	public function single(): Single_Product {
		if ( null === $this->single ) {
			$this->single = new Single_Product( $this );
		}

		return $this->single;
	}

	public function blog(): Blog_Pages {
		if ( null === $this->blog ) {
			$this->blog = new Blog_Pages( $this );
		}

		return $this->blog;
	}

	public function sanitize( array $input ): array {
		$clean = Sanitizer::apply( Schema::fields(), $input, Schema::defaults() );

		foreach ( Schema::SINGLE_TYPES as $type ) {
			$clean[ $type ]['default'] = $this->valid_ref( $clean[ $type ]['default'], Schema::SINGLE_TYPES, array( 'theme' ), 'theme' );

			$seen  = array();
			$rules = array();
			foreach ( $clean[ $type ]['rules'] as $rule ) {
				$rule['template'] = $this->valid_ref( $rule['template'], Schema::SINGLE_TYPES, array( 'theme' ), 'theme' );
				if ( '' === $rule['id'] || isset( $seen[ $rule['id'] ] ) ) {
					$rule['id'] = 'r' . strtolower( wp_generate_password( 8, false, false ) );
				}
				$seen[ $rule['id'] ] = true;
				$rules[]             = $rule;
			}
			$clean[ $type ]['rules'] = $rules;

			$preview = (int) $clean[ $type ]['preview_id'];
			if ( $preview && 'product' !== get_post_type( $preview ) ) {
				$clean[ $type ]['preview_id'] = 0;
			}
		}

		foreach ( array( 'header', 'footer' ) as $area ) {
			$clean[ $area ]['desktop'] = $this->valid_ref( $clean[ $area ]['desktop'], array( $area ), array( 'theme', 'none' ), 'theme' );
			$clean[ $area ]['mobile']  = $this->valid_ref( $clean[ $area ]['mobile'], array( $area ), array( 'same', 'theme', 'none' ), 'same' );
		}

		foreach ( Schema::BLOG_TYPES as $kind ) {
			$clean['blog'][ $kind ] = $this->valid_ref( $clean['blog'][ $kind ], array( $kind ), array( 'theme' ), 'theme' );
		}

		return $clean;
	}

	/**
	 * Fills keys added in newer versions into stored rules.
	 *
	 * @param array $settings Settings merged with defaults.
	 */
	protected function normalize( array $settings ): array {
		$rule_defaults = Schema::rule_defaults();

		foreach ( Schema::SINGLE_TYPES as $type ) {
			$rules = is_array( $settings[ $type ]['rules'] ?? null ) ? $settings[ $type ]['rules'] : array();

			$settings[ $type ]['rules'] = array_values(
				array_map(
					static function ( $rule ) use ( $rule_defaults ) {
						$rule = is_array( $rule ) ? $rule : array();
						return array_merge( $rule_defaults, array_intersect_key( $rule, $rule_defaults ) );
					},
					$rules
				)
			);
		}

		return $settings;
	}

	public function render_admin(): void {
		$module = $this;
		require __DIR__ . '/views/admin.php';
	}

	public function enqueue_admin_assets(): void {
		wp_enqueue_style( 'stx-builder-admin', STUDIARE_EXT_URL . 'assets/modules/builder/css/builder-admin.css', array( 'stx-admin' ), STUDIARE_EXT_VERSION );
		wp_enqueue_script( 'stx-builder-admin', STUDIARE_EXT_URL . 'assets/modules/builder/js/builder-admin.js', array( 'stx-admin' ), STUDIARE_EXT_VERSION, true );
	}

	public function admin_script_data(): array {
		// First visit: install the ready-made designs so they can be picked right away.
		if ( Library::elementor_active() && false === get_option( Library::OPTION, false ) ) {
			Library::install_missing();
		}

		return array(
			'elementor'     => Library::elementor_status(),
			'typeLabels'    => self::type_labels(),
			'keywordThumbs' => array(
				'theme' => Thumbs::svg( 'theme' ),
				'none'  => Thumbs::svg( 'none' ),
				'same'  => Thumbs::svg( 'same' ),
			),
			'hasWoo'        => Context::has_woo(),
			'templates'     => Library::all( $this ),
			'pages'         => Design_Pages::all(),
			'pageKinds'     => self::page_kinds(),
			'presets'       => Library::catalog_for_js(),
			'terms'         => $this->term_choices(),
			'samples'       => array(
				'course'  => $this->sample_choices( 'course' ),
				'product' => $this->sample_choices( 'product' ),
			),
			'themePreview'  => array(
				'course'  => $this->resolver()->preview_url( 'course', 'theme' ),
				'product' => $this->resolver()->preview_url( 'product', 'theme' ),
				'header'  => $this->resolver()->preview_url( 'header', 'theme' ),
				'footer'  => $this->resolver()->preview_url( 'footer', 'theme' ),
				'archive' => $this->resolver()->preview_url( 'archive', 'theme' ),
				'post'    => $this->resolver()->preview_url( 'post', 'theme' ),
			),
			'themeActive'   => Theme_Bridge::is_active(),
			'palette'       => Theme_Bridge::palette(),
			'brandDefaults' => Schema::BRAND_DEFAULTS,
			'maxRules'      => Schema::MAX_RULES,
			'i18n'          => $this->admin_strings(),
		);
	}

	/**
	 * Page design kinds for the Pages tab, in order: the title of their group
	 * and the name suggested for a new page.
	 *
	 * @return array<int, array{kind:string, title:string, name:string}>
	 */
	private static function page_kinds(): array {
		$titles = array(
			'home'    => __( 'Home pages', 'studiare-extensions' ),
			'about'   => __( 'About us pages', 'studiare-extensions' ),
			'contact' => __( 'Contact us pages', 'studiare-extensions' ),
		);

		$kinds = array();
		foreach ( Schema::PAGE_TYPES as $kind ) {
			$kinds[] = array(
				'kind'  => $kind,
				'title' => $titles[ $kind ],
				'name'  => Design_Pages::default_title( $kind ),
			);
		}

		return $kinds;
	}

	/** Admin URL of the template library tab. */
	public function library_url(): string {
		return add_query_arg(
			array(
				'page'    => $this->admin_slug(),
				'stx_tab' => 'library',
			),
			admin_url( 'admin.php' )
		);
	}

	/** Whether the current user may manage templates. */
	public static function user_can_manage(): bool {
		return current_user_can( Admin::CAPABILITY );
	}

	/**
	 * Validates a stored template reference.
	 *
	 * @param string   $ref      Reference.
	 * @param string[] $types    Accepted template kinds.
	 * @param string[] $keywords Accepted keywords.
	 * @param string   $fallback Returned when invalid.
	 */
	private function valid_ref( string $ref, array $types, array $keywords, string $fallback ): string {
		if ( in_array( $ref, $keywords, true ) ) {
			return $ref;
		}

		if ( ctype_digit( $ref ) && in_array( Template_Post_Type::type_of( (int) $ref ), $types, true ) ) {
			return $ref;
		}

		return $fallback;
	}

	/** @return array<int, array{id:int, name:string, parent:int, count:int}> */
	private function term_choices(): array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'number'     => 500,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return array_map(
			static function ( $term ) {
				return array(
					'id'     => (int) $term->term_id,
					'name'   => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
					'parent' => (int) $term->parent,
					'count'  => (int) $term->count,
				);
			},
			$terms
		);
	}

	/**
	 * Recent courses (or non-course products) for the preview picker.
	 *
	 * @param string $type `course` or `product`.
	 * @return array<int, array{id:int, title:string}>
	 */
	public function sample_choices( string $type ): array {
		if ( ! Context::has_woo() ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 60,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$choices = array();
		foreach ( $ids as $id ) {
			if ( ( 'course' === $type ) === Context::is_course( (int) $id ) ) {
				$choices[] = array(
					'id'    => (int) $id,
					'title' => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
				);
			}
		}

		return $choices;
	}

	/** Strings used by builder-admin.js. */
	private function admin_strings(): array {
		return array(
			'theme'           => __( 'Theme layout', 'studiare-extensions' ),
			'themeHeader'     => __( 'Theme header', 'studiare-extensions' ),
			'themeFooter'     => __( 'Theme footer', 'studiare-extensions' ),
			'themeDesc'       => __( 'Keep Studiare\'s own design.', 'studiare-extensions' ),
			'none'            => __( 'Nothing', 'studiare-extensions' ),
			'noneDesc'        => __( 'Hide it on this device.', 'studiare-extensions' ),
			'same'            => __( 'Same as desktop', 'studiare-extensions' ),
			'sameDesc'        => __( 'Use the desktop choice on phones too.', 'studiare-extensions' ),
			'custom'          => __( 'Custom template', 'studiare-extensions' ),
			'preview'         => __( 'Preview', 'studiare-extensions' ),
			'edit'            => __( 'Edit with Elementor', 'studiare-extensions' ),
			'duplicate'       => __( 'Duplicate', 'studiare-extensions' ),
			'restore'         => __( 'Restore original', 'studiare-extensions' ),
			'delete'          => __( 'Delete', 'studiare-extensions' ),
			'rename'          => __( 'Rename', 'studiare-extensions' ),
			'deleteTitle'     => __( 'Delete this template?', 'studiare-extensions' ),
			/* translators: %s: template name. */
			'deleteMessage'   => __( '"%s" goes to the trash. Pages using it fall back to the theme layout.', 'studiare-extensions' ),
			'restoreTitle'    => __( 'Restore the original design?', 'studiare-extensions' ),
			'restoreMessage'  => __( 'Your Elementor changes to this template will be replaced by the original design. Duplicate it first if you want to keep your version.', 'studiare-extensions' ),
			'restoreConfirm'  => __( 'Restore', 'studiare-extensions' ),
			'created'         => __( 'Template created.', 'studiare-extensions' ),
			'duplicated'      => __( 'Template duplicated.', 'studiare-extensions' ),
			'restored'        => __( 'Original design restored.', 'studiare-extensions' ),
			'deleted'         => __( 'Template deleted.', 'studiare-extensions' ),
			'installed'       => __( 'Ready-made designs installed.', 'studiare-extensions' ),
			'renamed'         => __( 'Template renamed.', 'studiare-extensions' ),
			'addRule'         => __( 'Add rule', 'studiare-extensions' ),
			'pickCategories'  => __( 'Choose categories', 'studiare-extensions' ),
			'searchCats'      => __( 'Search categories…', 'studiare-extensions' ),
			'noCats'          => __( 'No categories found.', 'studiare-extensions' ),
			/* translators: %d: number of categories. */
			'catsSelected'    => __( '%d categories', 'studiare-extensions' ),
			'includeChildren' => __( 'Include subcategories', 'studiare-extensions' ),
			'useTemplate'     => __( 'Use', 'studiare-extensions' ),
			'removeRule'      => __( 'Remove rule', 'studiare-extensions' ),
			'moveUp'          => __( 'Move up', 'studiare-extensions' ),
			'moveDown'        => __( 'Move down', 'studiare-extensions' ),
			'maxRules'        => __( 'You reached the maximum number of rules.', 'studiare-extensions' ),
			'noRules'         => __( 'No rules yet. Every item uses the layout above.', 'studiare-extensions' ),
			'ruleIncomplete'  => __( 'Pick at least one category — this rule is ignored until then.', 'studiare-extensions' ),
			'autoSample'      => __( 'Newest item (automatic)', 'studiare-extensions' ),
			'desktop'         => __( 'Desktop', 'studiare-extensions' ),
			'tablet'          => __( 'Tablet', 'studiare-extensions' ),
			'mobile'          => __( 'Mobile', 'studiare-extensions' ),
			'openTab'         => __( 'Open in new tab', 'studiare-extensions' ),
			'close'           => __( 'Close', 'studiare-extensions' ),
			'previewOf'       => __( 'Preview', 'studiare-extensions' ),
			'unsavedPreview'  => __( 'Previews show a design right away — nothing changes for visitors until you save.', 'studiare-extensions' ),
			'noSample'        => __( 'Add a published product (or blog post) first to preview it with real data.', 'studiare-extensions' ),
			'newTitle'        => __( 'New template', 'studiare-extensions' ),
			'newName'         => __( 'Name', 'studiare-extensions' ),
			'newType'         => __( 'Kind', 'studiare-extensions' ),
			'newStart'        => __( 'Start from', 'studiare-extensions' ),
			'blank'           => __( 'Blank canvas', 'studiare-extensions' ),
			'create'          => __( 'Create & open Elementor', 'studiare-extensions' ),
			'createOnly'      => __( 'Create', 'studiare-extensions' ),
			'renamePrompt'    => __( 'New name', 'studiare-extensions' ),
			'save'            => __( 'Save', 'studiare-extensions' ),
			'cancel'          => __( 'Cancel', 'studiare-extensions' ),
			'inUse'           => __( 'In use', 'studiare-extensions' ),
			'preset'          => __( 'Ready-made', 'studiare-extensions' ),
			'modified'        => __( 'Edited', 'studiare-extensions' ),
			'empty'           => __( 'No templates of this kind yet.', 'studiare-extensions' ),
			'needsElementor'  => __( 'Elementor is required to edit templates.', 'studiare-extensions' ),
			'colorsApplied'   => __( 'Studiare colours applied — save to keep them.', 'studiare-extensions' ),
			'synced'          => __( 'Brand colours added to Elementor\'s global colours.', 'studiare-extensions' ),
			'containerOn'     => __( 'Flexbox containers activated.', 'studiare-extensions' ),
			'createPage'      => __( 'Create page', 'studiare-extensions' ),
			/* translators: %s: design name. */
			'createPageTitle' => __( 'New page from "%s"', 'studiare-extensions' ),
			'pageName'        => __( 'Page name', 'studiare-extensions' ),
			'publishNow'      => __( 'Publish now', 'studiare-extensions' ),
			'publishHelp'     => __( 'Off: the page is saved as a draft that only you can see.', 'studiare-extensions' ),
			'makeFront'       => __( 'Use it as the site\'s home page', 'studiare-extensions' ),
			'makeFrontHelp'   => __( 'Visitors see it at the site address. The page is published.', 'studiare-extensions' ),
			'pageCreated'     => __( 'Page created.', 'studiare-extensions' ),
			'noDesigns'       => __( 'The ready-made designs are not installed. Use "Reinstall missing designs" in the Templates tab.', 'studiare-extensions' ),
			'noPages'         => __( 'No pages yet. Create one from a design above.', 'studiare-extensions' ),
			'frontPage'       => __( 'Home page', 'studiare-extensions' ),
			'draft'           => __( 'Draft', 'studiare-extensions' ),
			'published'       => __( 'Published', 'studiare-extensions' ),
			/* translators: %s: design name. */
			'madeFrom'        => __( 'From "%s"', 'studiare-extensions' ),
			'view'            => __( 'View', 'studiare-extensions' ),
			'setFront'        => __( 'Make it the home page', 'studiare-extensions' ),
			'frontSet'        => __( 'This page is now the site\'s home page.', 'studiare-extensions' ),
			'editDesign'      => __( 'Edit the design', 'studiare-extensions' ),
		);
	}
}
