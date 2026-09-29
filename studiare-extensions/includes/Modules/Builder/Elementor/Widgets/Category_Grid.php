<?php
/**
 * Category cards: the site's product (or post) categories as numbered cards
 * or icon tiles, or a hand-made list of links.
 *
 * Automatic tiles use the icon picked for the category in Studiare (the
 * category's "Featured icon" image) and otherwise cycle through a set of
 * bundled icons (Category_Parts), so a fresh site already looks finished.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use StudiareExt\Modules\Builder\Elementor\Category_Parts;

defined( 'ABSPATH' ) || exit;

final class Category_Grid extends Home_Base {

	public function get_name(): string {
		return 'stx-category-grid';
	}

	public function get_title(): string {
		return __( 'Category grid', 'studiare-extensions' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	protected function register_controls(): void {
		$this->start_content_section( 'section_content', __( 'Categories', 'studiare-extensions' ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Categories', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'   => __( 'From the site (top-level categories)', 'studiare-extensions' ),
					'custom' => __( 'My own list', 'studiare-extensions' ),
				),
			)
		);

		$this->add_control(
			'taxonomy',
			array(
				'label'     => __( 'Of', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'product_cat',
				'options'   => array(
					'product_cat' => __( 'Products and courses', 'studiare-extensions' ),
					'category'    => __( 'Blog posts', 'studiare-extensions' ),
				),
				'condition' => array( 'source' => 'auto' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'     => __( 'Number of categories', 'studiare-extensions' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 6,
				'min'       => 1,
				'max'       => 24,
				'condition' => array( 'source' => 'auto' ),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'     => __( 'Order', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'count',
				'options'   => array(
					'count'      => __( 'Most items first', 'studiare-extensions' ),
					'name'       => __( 'By name', 'studiare-extensions' ),
					'menu_order' => __( 'Custom order (drag & drop in the category list)', 'studiare-extensions' ),
				),
				'condition' => array( 'source' => 'auto' ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'studiare-extensions' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Category', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'note',
			array(
				'label'       => __( 'Small text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'e.g. 12 courses', 'studiare-extensions' ),
			)
		);
		$repeater->add_control(
			'icon',
			array(
				'label'   => __( 'Icon (tile style)', 'studiare-extensions' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'book',
				'options' => self::icon_options( false ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'studiare-extensions' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Items', 'studiare-extensions' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'title' => __( 'Category', 'studiare-extensions' ) ),
					array( 'title' => __( 'Category', 'studiare-extensions' ) ),
					array( 'title' => __( 'Category', 'studiare-extensions' ) ),
				),
				'title_field' => '{{{ title }}}',
				'condition'   => array( 'source' => 'custom' ),
			)
		);

		$this->add_control(
			'style',
			array(
				'label'     => __( 'Style', 'studiare-extensions' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'numbered',
				'options'   => array(
					'numbered' => __( 'Numbered cards', 'studiare-extensions' ),
					'icon'     => __( 'Icon tiles', 'studiare-extensions' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'   => __( 'Number of items', 'studiare-extensions' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'count_label',
			array(
				'label'       => __( 'Count text', 'studiare-extensions' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				/* translators: keep {count}: it is replaced with a number. */
				'placeholder' => __( '{count} items', 'studiare-extensions' ),
				'description' => __( 'Keep {count} where the number goes.', 'studiare-extensions' ),
				'condition'   => array(
					'show_count' => 'yes',
					'source'     => 'auto',
				),
			)
		);

		$this->add_columns_control( '.stx-cats', array( 3, 2, 1 ) );

		$this->end_controls_section();

		$this->start_style_section( 'section_style', __( 'Cards', 'studiare-extensions' ) );
		$this->add_gap_control( '.stx-cats' );
		$this->add_box_style( 'card', '.stx-cat', array( 'shadow' => true ) );
		$this->add_control(
			'mark_color',
			array(
				'label'     => __( 'Number / icon colour', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .stx-cat__mark' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'mark_bg',
			array(
				'label'     => __( 'Number / icon background', 'studiare-extensions' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .stx-cat__mark' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'name', '.stx-cat__name', array( 'label' => __( 'Title', 'studiare-extensions' ) ) );
		$this->add_text_style( 'count', '.stx-cat__count', array( 'label' => __( 'Small text', 'studiare-extensions' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$items = 'custom' === $s['source'] ? $this->custom_items( $s ) : $this->term_items( $s );

		if ( ! $items ) {
			$this->editor_hint( __( 'No categories with items yet.', 'studiare-extensions' ) );
			return;
		}

		printf( '<div class="stx-cats stx-cats--%s">', esc_attr( $s['style'] ) );

		foreach ( $items as $index => $item ) {
			if ( 'icon' === $s['style'] ) {
				$mark = '' !== $item['image'] ? $item['image'] : self::icon( $item['icon'] );
			} else {
				$mark = esc_html( self::digits( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) );
			}

			$body = '<span class="stx-cat__name">' . esc_html( $item['title'] ) . '</span>';
			if ( 'yes' === $s['show_count'] && '' !== $item['note'] ) {
				$body .= '<span class="stx-cat__count">' . esc_html( $item['note'] ) . '</span>';
			}

			$tag   = '' !== $item['url'] ? 'a' : 'div';
			$attrs = '' !== $item['url'] ? ' href="' . esc_url( $item['url'] ) . '"' : '';

			printf(
				'<%1$s class="stx-cat"%2$s><span class="stx-cat__mark" aria-hidden="true">%3$s</span><span class="stx-cat__body">%4$s</span></%1$s>',
				esc_html( $tag ),
				$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				$mark, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped digits, bundled SVG or core image markup.
				$body // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
		}

		echo '</div>';
	}

	/**
	 * Top-level categories of the chosen taxonomy.
	 *
	 * @param array $s Settings.
	 * @return array<int, array{title:string, note:string, url:string, icon:string, image:string}>
	 */
	private function term_items( array $s ): array {
		$taxonomy = 'category' === $s['taxonomy'] ? 'category' : 'product_cat';
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$orderby = in_array( $s['orderby'], array( 'count', 'name', 'menu_order' ), true ) ? $s['orderby'] : 'count';
		$default = (int) get_option( 'product_cat' === $taxonomy ? 'default_product_cat' : 'default_category' );

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'parent'     => 0,
				'hide_empty' => true,
				'number'     => max( 1, min( 24, (int) $s['limit'] ) ),
				'orderby'    => $orderby,
				'order'      => 'count' === $orderby ? 'DESC' : 'ASC',
				// "Uncategorized" is not a topic worth a card.
				'exclude'    => $default ? array( $default ) : array(),
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$format = '' !== trim( (string) $s['count_label'] ) ? (string) $s['count_label'] : $this->default_count_label( $taxonomy );
		$items  = array();

		foreach ( $terms as $index => $term ) {
			$items[] = array(
				'title' => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
				'note'  => str_replace( '{count}', self::num( (int) $term->count ), $format ),
				'url'   => (string) get_term_link( $term ),
				'icon'  => Category_Parts::fallback_icon( $index ),
				'image' => Category_Parts::theme_icon( $term ),
			);
		}

		return $items;
	}

	/**
	 * Items typed in the widget.
	 *
	 * @param array $s Settings.
	 * @return array<int, array{title:string, note:string, url:string, icon:string, image:string}>
	 */
	private function custom_items( array $s ): array {
		$items = array();

		foreach ( (array) $s['items'] as $item ) {
			$items[] = array(
				'title' => (string) $item['title'],
				'note'  => (string) $item['note'],
				'url'   => (string) ( $item['link']['url'] ?? '' ),
				'icon'  => '' !== (string) $item['icon'] ? (string) $item['icon'] : 'book',
				'image' => '',
			);
		}

		return $items;
	}

	/**
	 * @param string $taxonomy Taxonomy.
	 */
	private function default_count_label( string $taxonomy ): string {
		/* translators: keep {count}: it is replaced with a number. */
		return 'category' === $taxonomy ? __( '{count} posts', 'studiare-extensions' ) : __( '{count} products', 'studiare-extensions' );
	}
}
