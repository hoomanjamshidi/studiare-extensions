<?php
/**
 * Navigation menu with dropdowns and a slide-in drawer on small screens.
 * Source: a WordPress menu or the product categories. The drawer can show a
 * second list in its own tab (e.g. the main menu and the course categories).
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Nav_Menu extends Base {

	public function get_name(): string {
		return 'stx-nav-menu';
	}

	public function get_title(): string {
		return __( 'Navigation menu', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-nav-menu';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'menu', 'nav', 'منو' ) );
	}

	/**
	 * @param string $empty_label Label of the '' choice.
	 * @return array<string, string>
	 */
	private function menu_options( string $empty_label ): array {
		$options = array( '' => $empty_label );
		foreach ( wp_get_nav_menus() as $menu ) {
			$options[ (string) $menu->term_id ] = $menu->name;
		}

		return $options;
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Menu', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Items from', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'menu',
				'options' => array(
					'menu'        => __( 'A WordPress menu', 'studiare-extensions' ),
					'product_cat' => __( 'Product categories', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'menu',
			array(
				'label'       => __( 'Menu', 'studiare-extensions' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $this->menu_options( __( 'Automatic (main menu)', 'studiare-extensions' ) ),
				'description' => sprintf(
					/* translators: %s: link to the menus screen. */
					__( 'Edit menus in %s.', 'studiare-extensions' ),
					'<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" target="_blank">' . esc_html__( 'Appearance → Menus', 'studiare-extensions' ) . '</a>'
				),
				'condition'   => array( 'source' => 'menu' ),
			)
		);

		$this->add_control(
			'depth',
			array(
				'label'   => __( 'Levels', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '2',
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
				),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'horizontal',
				'options' => array(
					'horizontal' => __( 'Horizontal', 'studiare-extensions' ),
					'vertical'   => __( 'Vertical (e.g. footer links)', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'pointer',
			array(
				'label'     => __( 'Hover & current effect', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'underline',
				'options'   => array(
					'underline' => __( 'Underline', 'studiare-extensions' ),
					'pill'      => __( 'Pill', 'studiare-extensions' ),
					'none'      => __( 'Colour only', 'studiare-extensions' ),
				),
				'condition' => array( 'layout' => 'horizontal' ),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'studiare-extensions' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Start', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::start_icon(),
					),
					'center'     => array(
						'title' => __( 'Center', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'End', 'studiare-extensions' ),
						'icon'  => 'eicon-text-align-' . self::end_icon(),
					),
				),
				'selectors' => array( '{{WRAPPER}} .stx-nav' => '--stx-justify: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_content_section( 'section_mobile', __( 'Mobile drawer', 'studiare-extensions' ) );

		$this->add_control(
			'toggle',
			array(
				'label'   => __( 'Switch to a menu button', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'tablet',
				'options' => array(
					'tablet' => __( 'On tablets and phones', 'studiare-extensions' ),
					'mobile' => __( 'On phones only', 'studiare-extensions' ),
					'always' => __( 'Always (button only)', 'studiare-extensions' ),
					'never'  => __( 'Never', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'toggle_label',
			array(
				'label'   => __( 'Button text', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'drawer_side',
			array(
				'label'   => __( 'Drawer opens from', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'start',
				'options' => array(
					'start' => __( 'Start side', 'studiare-extensions' ),
					'end'   => __( 'End side', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'drawer_title',
			array(
				'label'       => __( 'Drawer title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => get_bloginfo( 'name' ),
			)
		);

		$this->add_control(
			'drawer_tabs',
			array(
				'label'       => __( 'Second menu (two tabs)', 'studiare-extensions' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'The drawer shows two tabs, e.g. the main menu and the course categories.', 'studiare-extensions' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'tab_label',
			array(
				'label'       => __( 'First tab title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Main menu', 'studiare-extensions' ),
				'condition'   => array( 'drawer_tabs' => 'yes' ),
			)
		);

		$this->add_control(
			'second_source',
			array(
				'label'     => __( 'Second tab items from', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'product_cat',
				'options'   => array(
					'product_cat' => __( 'Product categories', 'studiare-extensions' ),
					'menu'        => __( 'A WordPress menu', 'studiare-extensions' ),
				),
				'condition' => array( 'drawer_tabs' => 'yes' ),
			)
		);

		$this->add_control(
			'second_menu',
			array(
				'label'     => __( 'Second menu', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => $this->menu_options( __( '— Choose a menu —', 'studiare-extensions' ) ),
				'condition' => array(
					'drawer_tabs'   => 'yes',
					'second_source' => 'menu',
				),
			)
		);

		$this->add_control(
			'second_label',
			array(
				'label'       => __( 'Second tab title', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Categories', 'studiare-extensions' ),
				'description' => __( 'Leave empty to use "Categories" or the chosen menu\'s name.', 'studiare-extensions' ),
				'condition'   => array( 'drawer_tabs' => 'yes' ),
			)
		);

		$this->add_control(
			'drawer_cta',
			array(
				'label'     => __( 'Button in drawer', 'studiare-extensions' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'drawer_cta_link',
			array(
				'label'     => __( 'Button link', 'studiare-extensions' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'drawer_cta!' => '' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Menu', 'studiare-extensions' ) );
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'link_typography',
				'selector' => '{{WRAPPER}} .stx-nav > .stx-menu > .stx-menu__item > .stx-menu__row > .stx-menu__link',
			)
		);
		$this->add_control(
			'link_color',
			array(
				'label'     => __( 'Link colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-nav' => '--stx-nav-link: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'link_active_color',
			array(
				'label'     => __( 'Hover & current colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-nav, .stx-drawer[data-owner="{{ID}}"]' => '--stx-nav-active: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'pointer_color',
			array(
				'label'     => __( 'Underline / pill colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-nav' => '--stx-nav-pointer: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'item_gap',
			array(
				'label'      => __( 'Space between items', 'studiare-extensions' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .stx-nav' => '--stx-nav-gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'dropdown_heading',
			array(
				'label'     => __( 'Dropdown', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'dropdown_bg',
			array(
				'label'     => __( 'Background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-nav' => '--stx-dd-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'dropdown_color',
			array(
				'label'     => __( 'Link colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-nav' => '--stx-dd-link: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'toggle_heading',
			array(
				'label'     => __( 'Menu button & drawer', 'studiare-extensions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'toggle_color',
			array(
				'label'     => __( 'Button colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-nav__toggle' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'toggle_bg',
			array(
				'label'     => __( 'Button background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-nav__toggle' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'drawer_bg',
			array(
				'label'     => __( 'Drawer background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '.stx-drawer[data-owner="{{ID}}"] .stx-drawer__panel' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$depth = max( 1, min( 3, (int) $s['depth'] ) );
		$tree  = 'product_cat' === $s['source'] ? $this->category_tree( $depth ) : $this->menu_tree( (string) $s['menu'] );

		if ( ! $tree ) {
			$this->editor_hint( __( 'No menu found. Create one in Appearance → Menus.', 'studiare-extensions' ) );
			return;
		}

		$drawer_id = 'stx-drawer-' . $this->get_id();
		$toggle    = in_array( $s['toggle'], array( 'tablet', 'mobile', 'always', 'never' ), true ) ? $s['toggle'] : 'tablet';

		printf(
			'<nav class="stx-nav stx-nav--%1$s stx-nav--pointer-%2$s stx-nav--toggle-%3$s" aria-label="%4$s">',
			esc_attr( $s['layout'] ),
			esc_attr( 'horizontal' === $s['layout'] ? $s['pointer'] : 'none' ),
			esc_attr( $toggle ),
			esc_attr__( 'Menu', 'studiare-extensions' )
		);

		echo '<ul class="stx-menu">' . $this->items_html( $tree, 0, $depth ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.

		if ( 'never' !== $toggle ) {
			printf(
				'<button type="button" class="stx-nav__toggle" aria-expanded="false" aria-controls="%1$s" data-stx-open="%1$s">%2$s%3$s<span class="screen-reader-text">%4$s</span></button>',
				esc_attr( $drawer_id ),
				self::icon( 'menu' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
				'' !== (string) $s['toggle_label'] ? '<span class="stx-nav__toggle-text">' . esc_html( $s['toggle_label'] ) . '</span>' : '',
				esc_html__( 'Open menu', 'studiare-extensions' )
			);
		}

		echo '</nav>';

		if ( 'never' !== $toggle ) {
			$this->render_drawer( $drawer_id, $tree, $depth, $s );
		}
	}

	/**
	 * @param string $drawer_id Drawer element id.
	 * @param array  $tree      Menu tree.
	 * @param int    $depth     Levels.
	 * @param array  $s         Settings.
	 */
	private function render_drawer( string $drawer_id, array $tree, int $depth, array $s ): void {
		$title = '' !== (string) $s['drawer_title'] ? $s['drawer_title'] : get_bloginfo( 'name' );

		printf(
			'<div class="stx-drawer stx-drawer--%1$s" id="%2$s" data-owner="%3$s" data-stx-layer hidden>',
			esc_attr( $s['drawer_side'] ),
			esc_attr( $drawer_id ),
			esc_attr( $this->get_id() )
		);
		echo '<div class="stx-drawer__backdrop" data-stx-close></div>';
		printf( '<div class="stx-drawer__panel" role="dialog" aria-modal="true" aria-label="%s">', esc_attr( $title ) );
		printf(
			'<div class="stx-drawer__head"><span class="stx-drawer__title">%1$s</span><button type="button" class="stx-drawer__close" data-stx-close aria-label="%2$s">%3$s</button></div>',
			esc_html( $title ),
			esc_attr__( 'Close', 'studiare-extensions' ),
			self::icon( 'close' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
		);
		$second = $this->second_tab( $s, $depth );
		if ( $second ) {
			$this->render_tabs(
				array(
					array(
						'id'    => $drawer_id . '-main',
						'title' => '' !== (string) $s['tab_label'] ? $s['tab_label'] : __( 'Main menu', 'studiare-extensions' ),
						'tree'  => $tree,
					),
					array( 'id' => $drawer_id . '-second' ) + $second,
				),
				$depth
			);
		} else {
			echo '<ul class="stx-menu stx-menu--drawer">' . $this->items_html( $tree, 0, $depth ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.
		}

		if ( '' !== (string) $s['drawer_cta'] && ! empty( $s['drawer_cta_link']['url'] ) ) {
			printf( '<a class="stx-btn stx-btn--accent stx-btn--md stx-drawer__cta" href="%1$s">%2$s</a>', esc_url( $s['drawer_cta_link']['url'] ), esc_html( $s['drawer_cta'] ) );
		}

		echo '</div></div>';
	}

	/**
	 * Title and items of the drawer's second tab.
	 *
	 * @param array $s     Settings.
	 * @param int   $depth Levels.
	 * @return array|null `title` and `tree`, or null when the tab is off or has nothing to list
	 *                    (the drawer then shows the main list alone).
	 */
	private function second_tab( array $s, int $depth ): ?array {
		if ( 'yes' !== $s['drawer_tabs'] ) {
			return null;
		}

		if ( 'menu' === $s['second_source'] ) {
			// No automatic choice here: it would repeat the main menu.
			$menu  = wp_get_nav_menu_object( (int) $s['second_menu'] );
			$tree  = $menu ? $this->menu_items_tree( $menu ) : array();
			$title = $menu ? $menu->name : '';
		} else {
			$tree  = $this->category_tree( $depth );
			$title = __( 'Categories', 'studiare-extensions' );
		}

		if ( ! $tree ) {
			return null;
		}

		return array(
			'title' => '' !== (string) $s['second_label'] ? $s['second_label'] : $title,
			'tree'  => $tree,
		);
	}

	/**
	 * Tabs in the drawer, one list each. builder.js switches them like the
	 * product tabs (click and arrow keys).
	 *
	 * @param array $tabs  Tabs (`id`, `title`, `tree`).
	 * @param int   $depth Levels.
	 */
	private function render_tabs( array $tabs, int $depth ): void {
		// Open on the tab that lists the page being viewed (e.g. a category page).
		$active = 0;
		foreach ( $tabs as $index => $tab ) {
			if ( self::has_current( $tab['tree'] ) ) {
				$active = $index;
				break;
			}
		}

		echo '<div class="stx-tabs stx-tabs--tabs stx-drawer__tabs"><div class="stx-tabs__nav" role="tablist">';
		foreach ( $tabs as $index => $tab ) {
			printf(
				'<button type="button" class="stx-tabs__tab%1$s" role="tab" id="%2$s-tab" aria-controls="%2$s" aria-selected="%3$s" tabindex="%4$s">%5$s</button>',
				$active === $index ? ' is-active' : '',
				esc_attr( $tab['id'] ),
				$active === $index ? 'true' : 'false',
				$active === $index ? '0' : '-1',
				esc_html( $tab['title'] )
			);
		}
		echo '</div>';

		foreach ( $tabs as $index => $tab ) {
			printf(
				'<div class="stx-tabs__panel" role="tabpanel" id="%1$s" aria-labelledby="%1$s-tab"%2$s><ul class="stx-menu stx-menu--drawer">%3$s</ul></div>',
				esc_attr( $tab['id'] ),
				$active === $index ? '' : ' hidden',
				$this->items_html( $tab['tree'], 0, $depth ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.
			);
		}
		echo '</div>';
	}

	/**
	 * @param array $nodes Nodes of a menu tree.
	 */
	private static function has_current( array $nodes ): bool {
		foreach ( $nodes as $node ) {
			if ( $node['current'] || $node['ancestor'] || self::has_current( $node['children'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $nodes Nodes (`title`, `url`, `current`, `children`).
	 * @param int   $level Current level (0-based).
	 * @param int   $depth Max levels.
	 */
	private function items_html( array $nodes, int $level, int $depth ): string {
		$html = '';

		foreach ( $nodes as $node ) {
			$children = $level + 1 < $depth ? $node['children'] : array();
			$classes  = array( 'stx-menu__item' );
			if ( $children ) {
				$classes[] = 'has-children';
			}
			if ( $node['current'] ) {
				$classes[] = 'is-current';
			}
			if ( $node['ancestor'] ) {
				$classes[] = 'is-ancestor';
			}

			$html .= '<li class="' . esc_attr( implode( ' ', $classes ) ) . '"><div class="stx-menu__row">';
			$html .= sprintf(
				'<a class="stx-menu__link" href="%1$s"%2$s%3$s>%4$s</a>',
				esc_url( $node['url'] ),
				$node['current'] ? ' aria-current="page"' : '',
				$node['target'] ? ' target="_blank" rel="noopener"' : '',
				esc_html( $node['title'] )
			);

			if ( $children ) {
				$html .= sprintf(
					'<button type="button" class="stx-menu__caret" aria-expanded="false" aria-label="%1$s">%2$s</button>',
					/* translators: %s: menu item title. */
					esc_attr( sprintf( __( 'Show submenu of %s', 'studiare-extensions' ), $node['title'] ) ),
					self::icon( 'chevron-down' )
				);
			}

			$html .= '</div>';

			if ( $children ) {
				$html .= '<ul class="stx-menu__sub">' . $this->items_html( $children, $level + 1, $depth ) . '</ul>';
			}

			$html .= '</li>';
		}

		return $html;
	}

	/**
	 * @param string $menu_id Chosen menu ('' = automatic).
	 */
	private function menu_tree( string $menu_id ): array {
		$menu = $menu_id ? wp_get_nav_menu_object( (int) $menu_id ) : null;

		if ( ! $menu ) {
			$locations = get_nav_menu_locations();
			foreach ( array( 'main-menu', 'primary', 'main', 'header', 'top' ) as $location ) {
				if ( ! empty( $locations[ $location ] ) ) {
					$menu = wp_get_nav_menu_object( $locations[ $location ] );
					break;
				}
			}
		}

		if ( ! $menu ) {
			$menus = wp_get_nav_menus();
			$menu  = $menus ? $menus[0] : null;
		}

		return $menu ? $this->menu_items_tree( $menu ) : array();
	}

	/**
	 * @param \WP_Term $menu Menu.
	 */
	private function menu_items_tree( \WP_Term $menu ): array {
		$items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );
		if ( ! $items ) {
			return array();
		}

		// Adds current/ancestor flags exactly like wp_nav_menu() does.
		_wp_menu_item_classes_by_context( $items );

		$by_parent = array();
		foreach ( $items as $item ) {
			$by_parent[ (int) $item->menu_item_parent ][] = $item;
		}

		$build = static function ( int $parent_id ) use ( &$build, $by_parent ): array {
			$nodes = array();
			foreach ( $by_parent[ $parent_id ] ?? array() as $item ) {
				$nodes[] = array(
					'title'    => wp_strip_all_tags( (string) apply_filters( 'the_title', $item->title, $item->ID ) ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
					'url'      => (string) $item->url,
					'target'   => '_blank' === $item->target,
					'current'  => ! empty( $item->current ),
					'ancestor' => ! empty( $item->current_item_ancestor ),
					'children' => $build( (int) $item->ID ),
				);
			}
			return $nodes;
		};

		return $build( 0 );
	}

	/**
	 * @param int $depth Levels.
	 */
	private function category_tree( int $depth ): array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$current = is_tax( 'product_cat' ) ? (int) get_queried_object_id() : 0;

		$build = static function ( int $parent_id, int $level ) use ( &$build, $depth, $current ): array {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'parent'     => $parent_id,
					'hide_empty' => true,
					'number'     => 40,
				)
			);

			if ( is_wp_error( $terms ) ) {
				return array();
			}

			$nodes = array();
			foreach ( $terms as $term ) {
				if ( 'uncategorized' === $term->slug ) {
					continue;
				}
				$nodes[] = array(
					'title'    => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
					'url'      => (string) get_term_link( $term ),
					'target'   => false,
					'current'  => $current === (int) $term->term_id,
					'ancestor' => $current && term_is_ancestor_of( $term, $current, 'product_cat' ),
					'children' => $level + 1 < $depth ? $build( (int) $term->term_id, $level + 1 ) : array(),
				);
			}
			return $nodes;
		};

		return $build( 0, 0 );
	}
}
