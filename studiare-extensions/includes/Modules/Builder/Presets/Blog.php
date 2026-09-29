<?php
/**
 * Blog designs: three layouts for post lists (the blog page, categories,
 * tags, authors, searches) and three for single posts, in the look of the
 * home designs. Every post list shows the posts of the page being viewed
 * with page numbers; every post uses the post's own title, picture and text.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Presets;

defined( 'ABSPATH' ) || exit;

final class Blog {

	/* ---------------------------------------------------------------------
	 * Post lists
	 * ------------------------------------------------------------------- */

	/** Magazine: soft title band with category buttons, the newest post highlighted over a card grid. */
	public static function archive_magazine(): array {
		return array(
			Blocks::band(
				'soft',
				array(
					El::w( 'stx-blog-breadcrumb', array( 'align' => 'center' ) ),
					El::w(
						'stx-archive-title',
						array(
							'size'  => 'xl',
							'align' => 'center',
						)
					),
					El::w(
						'stx-post-categories',
						array(
							'layout' => 'chips',
							'counts' => '',
							'align'  => 'center',
						),
						array( 'fill' => true )
					),
				),
				array(
					'align'      => 'center',
					'gap'        => 16,
					'pad'        => array( 44, 20, 36 ),
					'pad_mobile' => array( 28, 16, 24 ),
				)
			),
			Blocks::band(
				'page',
				array(
					self::list_grid(
						array(
							'card'           => 'card',
							'lead'           => 'yes',
							'excerpt'        => 'yes',
							'reading'        => 'yes',
							'columns'        => '3',
							'columns_tablet' => '2',
							'columns_mobile' => '1',
						)
					),
				),
				array(
					'pad'        => array( 36, 20, 24 ),
					'pad_mobile' => array( 22, 16, 18 ),
				)
			),
			Blocks::band( 'page', array( self::newsletter() ), Blocks::last_band() ),
		);
	}

	/** Classic: title row, post rows with a sticky sidebar (search, categories, most read, tags). */
	public static function archive_sidebar(): array {
		return array(
			Blocks::band(
				'page',
				array(
					El::w( 'stx-blog-breadcrumb' ),
					El::w( 'stx-archive-title', array( 'size' => 'lg' ) ),
				),
				array(
					'gap'        => 10,
					'pad'        => array( 36, 20, 8 ),
					'pad_mobile' => array( 22, 16, 4 ),
				)
			),
			Blocks::band(
				'page',
				array(
					El::box(
						array(
							'dir'        => 'row',
							'dir_tablet' => 'column',
							'gap'        => 28,
							'align'      => 'flex-start',
						),
						array(
							El::box(
								array(
									'width'        => 68,
									'width_tablet' => 100,
								),
								array(
									self::list_grid(
										array(
											'card'    => 'list',
											'excerpt' => 'yes',
											'reading' => 'yes',
											'ratio'   => '4/3',
											'columns' => '1',
											'columns_tablet' => '1',
											'columns_mobile' => '1',
										)
									),
								)
							),
							El::box(
								array(
									'width'        => 32,
									'width_tablet' => 100,
									'gap'          => 18,
									'sticky'       => 'desktop',
								),
								array(
									self::side_card(
										__( 'Search the blog', 'studiare-extensions' ),
										El::w(
											'stx-search',
											array(
												'post_type'   => 'post',
												'placeholder' => __( 'Search articles…', 'studiare-extensions' ),
											)
										)
									),
									self::side_card( __( 'Categories', 'studiare-extensions' ), El::w( 'stx-post-categories', array( 'layout' => 'list' ) ) ),
									self::side_card( __( 'Most discussed', 'studiare-extensions' ), self::mini_list( 'popular', true ) ),
									self::side_card(
										__( 'Tags', 'studiare-extensions' ),
										El::w(
											'stx-post-tags',
											array(
												'source' => 'popular',
												'label'  => '',
												'count'  => 14,
											)
										)
									),
								)
							),
						)
					),
				),
				Blocks::last_band()
			),
		);
	}

	/** Minimal: white page, centred title and categories, editorial cards without boxes. */
	public static function archive_minimal(): array {
		return array(
			Blocks::band(
				'plain',
				array(
					El::w(
						'stx-archive-title',
						array(
							'size'    => 'xl',
							'align'   => 'center',
							'eyebrow' => __( 'Articles', 'studiare-extensions' ),
						)
					),
					El::w(
						'stx-post-categories',
						array(
							'counts' => '',
							'align'  => 'center',
						),
						array( 'fill' => true )
					),
				),
				array(
					'align'      => 'center',
					'gap'        => 22,
					'pad'        => array( 56, 20, 28 ),
					'pad_mobile' => array( 32, 16, 18 ),
				)
			),
			Blocks::band(
				'plain',
				array(
					self::list_grid(
						array(
							'card'           => 'minimal',
							'excerpt'        => 'yes',
							'author'         => 'yes',
							'button_text'    => '',
							'ratio'          => '4/3',
							'columns'        => '3',
							'columns_tablet' => '2',
							'columns_mobile' => '1',
							'gap'            => El::px( 32 ),
						)
					),
				),
				array(
					'pad'        => array( 16, 20, 72 ),
					'pad_mobile' => array( 12, 16, 44 ),
				)
			),
		);
	}

	/* ---------------------------------------------------------------------
	 * Single posts
	 * ------------------------------------------------------------------- */

	/** Classic: the post in a white card beside a sticky sidebar, related posts below. */
	public static function post_classic(): array {
		$article = El::box(
			array(
				'surface'    => 'card',
				'gap'        => 22,
				'pad'        => array( 32, 32, 36 ),
				'pad_mobile' => array( 20, 16, 24 ),
			),
			array(
				El::w( 'stx-blog-breadcrumb' ),
				El::w( 'stx-post-title', array( 'size' => 'lg' ) ),
				El::w( 'stx-post-meta' ),
				El::w( 'stx-post-image', array( 'ratio' => '16/9' ) ),
				self::phone_toc(),
				El::w( 'stx-post-content' ),
				self::tags_and_share(),
			)
		);

		return array(
			El::w( 'stx-reading-progress' ),
			Blocks::band(
				'page',
				array(
					El::box(
						array(
							'dir'        => 'row',
							'dir_tablet' => 'column',
							'gap'        => 28,
							'align'      => 'flex-start',
						),
						array(
							El::box(
								array(
									'width'        => 70,
									'width_tablet' => 100,
									'gap'          => 22,
								),
								array(
									$article,
									El::w( 'stx-post-author' ),
									El::w( 'stx-post-nav' ),
									El::w( 'stx-post-comments' ),
								)
							),
							El::box(
								array(
									'width'        => 30,
									'width_tablet' => 100,
									'gap'          => 18,
									'sticky'       => 'desktop',
								),
								array(
									El::w( 'stx-post-toc', array(), array( 'hide' => array( 'tablet', 'mobile' ) ) ),
									self::side_card( __( 'Latest posts', 'studiare-extensions' ), self::mini_list( 'latest', false ) ),
									self::side_card( __( 'A short letter every week', 'studiare-extensions' ), El::w( 'stx-newsletter' ) ),
								)
							),
						)
					),
				),
				array(
					'pad'        => array( 28, 20, 24 ),
					'pad_mobile' => array( 16, 16, 18 ),
				)
			),
			Blocks::band( 'page', array( Blocks::section_title( __( 'Related posts', 'studiare-extensions' ), __( 'All posts', 'studiare-extensions' ), Blocks::blog_url() ), self::related( 'card' ) ), Blocks::last_band() ),
		);
	}

	/** Focus: one narrow, centred reading column on white, with a wide picture. */
	public static function post_focus(): array {
		$column = static function ( array $children, array $o = array() ): array {
			return Blocks::band( 'plain', $children, array_merge( array( 'boxed' => 760 ), $o ) );
		};

		return array(
			El::w( 'stx-reading-progress' ),
			$column(
				array(
					El::w(
						'stx-post-title',
						array(
							'size'  => 'xl',
							'lead'  => 'yes',
							'align' => 'center',
						)
					),
					El::w(
						'stx-post-meta',
						array(
							'layout'        => 'author',
							'align'         => 'center',
							'show_comments' => '',
						),
						array( 'width' => 'auto' )
					),
				),
				array(
					'align'      => 'center',
					'gap'        => 18,
					'pad'        => array( 52, 20, 28 ),
					'pad_mobile' => array( 28, 16, 18 ),
				)
			),
			Blocks::band(
				'plain',
				array(
					El::w(
						'stx-post-image',
						array(
							'ratio'        => '21/9',
							'ratio_mobile' => '4/3',
						)
					),
				),
				array(
					'boxed'      => 1120,
					'pad'        => array( 0, 20 ),
					'pad_mobile' => array( 0, 16 ),
				)
			),
			$column(
				array(
					El::w(
						'stx-post-toc',
						array(
							'open'    => '',
							'numbers' => 'yes',
						)
					),
					El::w( 'stx-post-content', array( 'size' => 'lg' ) ),
					El::w( 'stx-post-tags', array( 'align' => 'center' ) ),
					El::w(
						'stx-post-share',
						array(
							'look'   => 'buttons',
							'colors' => 'brand',
							'label'  => '',
							'align'  => 'center',
						)
					),
					El::w( 'stx-post-author', array( 'layout' => 'center' ) ),
					El::w( 'stx-post-nav' ),
				),
				array(
					'gap'        => 26,
					'pad'        => array( 32, 20, 48 ),
					'pad_mobile' => array( 22, 16, 32 ),
				)
			),
			Blocks::band( 'page', array( Blocks::section_title( __( 'More to read', 'studiare-extensions' ) ), self::related( 'minimal' ) ), Blocks::white_band() ),
			Blocks::band( 'page', array( El::w( 'stx-post-comments' ) ), array_merge( Blocks::last_band(), array( 'boxed' => 760 ) ) ),
		);
	}

	/** Cover: dark title band beside the picture, then the text between a share rail and a sticky contents list. */
	public static function post_cover(): array {
		return array(
			El::w( 'stx-reading-progress' ),
			Blocks::band(
				'dark',
				array(
					El::box(
						array(
							'dir'          => 'row',
							'dir_tablet'   => 'column',
							'gap'          => 40,
							'align'        => 'center',
							'align_tablet' => 'stretch',
						),
						array(
							El::box(
								array(
									'width'        => 55,
									'width_tablet' => 100,
									'gap'          => 18,
								),
								array(
									El::w( 'stx-blog-breadcrumb' ),
									El::w(
										'stx-post-title',
										array(
											'size' => 'xl',
											'lead' => 'yes',
										)
									),
									El::w(
										'stx-post-meta',
										array(
											'layout' => 'author',
											'show_comments' => '',
										)
									),
								)
							),
							El::box(
								array(
									'width'        => 45,
									'width_tablet' => 100,
								),
								array( El::w( 'stx-post-image', array( 'ratio' => '4/3' ) ) )
							),
						)
					),
				),
				array(
					'pad'        => array( 52, 20, 52 ),
					'pad_mobile' => array( 26, 16, 28 ),
				)
			),
			Blocks::band(
				'page',
				array(
					El::box(
						array(
							'dir'        => 'row',
							'dir_tablet' => 'column',
							'gap'        => 26,
							'align'      => 'flex-start',
						),
						array(
							El::box(
								array(
									'width'  => '64px',
									'sticky' => 'desktop',
									'hide'   => array( 'tablet', 'mobile' ),
								),
								array(
									El::w(
										'stx-post-share',
										array(
											'label' => '',
											'stack' => 'yes',
										)
									),
								)
							),
							El::box(
								array(
									'surface'      => 'card',
									'width'        => 60,
									'fill'         => true,
									'width_tablet' => 100,
									'gap'          => 24,
									'pad'          => array( 36, 36, 40 ),
									'pad_mobile'   => array( 20, 16, 26 ),
								),
								array(
									self::phone_toc(),
									El::w( 'stx-post-content' ),
									El::w( 'stx-post-tags' ),
									El::w( 'stx-post-share', array(), array( 'hide' => array( 'desktop' ) ) ),
								)
							),
							El::box(
								array(
									'width'  => 28,
									'sticky' => 'desktop',
									'hide'   => array( 'tablet', 'mobile' ),
								),
								array( El::w( 'stx-post-toc' ) )
							),
						)
					),
				),
				array(
					'pad'        => array( 32, 20, 16 ),
					'pad_mobile' => array( 18, 16, 10 ),
				)
			),
			Blocks::band(
				'page',
				array(
					El::w( 'stx-post-author' ),
					El::w( 'stx-post-nav' ),
					Blocks::section_title( __( 'Related posts', 'studiare-extensions' ) ),
					self::related( 'list' ),
					El::w( 'stx-post-comments' ),
				),
				Blocks::last_band()
			),
		);
	}

	/* ---------------------------------------------------------------------
	 * Pieces
	 * ------------------------------------------------------------------- */

	/**
	 * Posts of the page being viewed, with page numbers.
	 *
	 * @param array $settings Post grid settings.
	 */
	private static function list_grid( array $settings ): array {
		return El::w(
			'stx-post-grid',
			array_merge(
				array(
					'source'      => 'current',
					'pagination'  => 'yes',
					'date'        => 'yes',
					'author'      => '',
					'ratio'       => '16/9',
					'button_text' => '',
				),
				$settings
			)
		);
	}

	/**
	 * Compact list for a sidebar.
	 *
	 * @param string $source  Post grid source.
	 * @param bool   $numbers Numbered.
	 */
	private static function mini_list( string $source, bool $numbers ): array {
		return El::w(
			'stx-post-grid',
			array(
				'source'         => $source,
				'count'          => 4,
				'card'           => 'mini',
				'numbers'        => $numbers ? 'yes' : '',
				'columns'        => '1',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
			)
		);
	}

	/**
	 * Related posts row (a swipe row on phones).
	 *
	 * @param string $card Card style.
	 */
	private static function related( string $card ): array {
		$list = 'list' === $card;

		return El::w(
			'stx-post-grid',
			array(
				'source'         => 'related',
				'count'          => $list ? 4 : 3,
				'card'           => $card,
				'ratio'          => $list ? '4/3' : '16/10',
				'reading'        => 'yes',
				'author'         => '',
				'button_text'    => '',
				'mobile_scroll'  => $list ? '' : 'yes',
				'columns'        => $list ? '2' : '3',
				'columns_tablet' => '2',
				'columns_mobile' => '1',
			)
		);
	}

	/**
	 * White sidebar card with a small title.
	 *
	 * @param string $title  Title.
	 * @param array  $widget Content.
	 */
	private static function side_card( string $title, array $widget ): array {
		return El::box(
			array(
				'surface' => 'card',
				'gap'     => 14,
				'pad'     => array( 20, 20 ),
			),
			array(
				Blocks::heading(
					$title,
					'xs',
					array( 'decor' => 'rule' )
				),
				$widget,
			)
		);
	}

	/** Closed table of contents above the text, for phones and tablets. */
	private static function phone_toc(): array {
		return El::w( 'stx-post-toc', array( 'open' => '' ), array( 'hide' => array( 'desktop' ) ) );
	}

	/** Tags at the start and share buttons at the end of one row (stacked on phones). */
	private static function tags_and_share(): array {
		return El::box(
			array(
				'dir'          => 'row',
				'dir_mobile'   => 'column',
				'justify'      => 'space-between',
				'align'        => 'center',
				'align_mobile' => 'flex-start',
				'wrap'         => 'wrap',
				'gap'          => 16,
				'pad'          => array( 20, 0, 0 ),
				'set'          => array( 'css_classes' => 'stx-post-foot' ),
			),
			array(
				El::w( 'stx-post-tags', array(), array( 'fill' => true ) ),
				El::w( 'stx-post-share', array(), array( 'width' => 'auto' ) ),
			)
		);
	}

	/** Newsletter card closing a post list. */
	private static function newsletter(): array {
		return El::box(
			array(
				'surface'      => 'card',
				'dir'          => 'row',
				'dir_tablet'   => 'column',
				'align'        => 'center',
				'align_tablet' => 'stretch',
				'gap'          => 22,
				'pad'          => array( 30, 34 ),
				'pad_mobile'   => array( 24, 20 ),
			),
			array(
				Blocks::heading(
					__( 'New posts in your inbox', 'studiare-extensions' ),
					'sm',
					array( 'subtitle' => __( 'One short letter a week with the newest articles. No ads.', 'studiare-extensions' ) ),
					array( 'fill' => true )
				),
				El::box(
					array(
						'width'        => 50,
						'width_tablet' => 100,
					),
					array( El::w( 'stx-newsletter' ) )
				),
			)
		);
	}
}
