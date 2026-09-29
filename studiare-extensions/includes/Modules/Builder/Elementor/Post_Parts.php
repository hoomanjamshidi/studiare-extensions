<?php
/**
 * Shared pieces of the blog widgets: the post content with anchors on its
 * headings (shared by the content and the table of contents), reading time,
 * share links, author details and what a post list page is about.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor;

use StudiareExt\Core\Site;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Widgets\Base;

defined( 'ABSPATH' ) || exit;

final class Post_Parts {

	/** Reading speed used for the reading time (words per minute). */
	private const WORDS_PER_MINUTE = 200;

	/** @var array<int, array{html:string, headings:array}> Filtered content per post, for this request. */
	private static $content = array();

	/**
	 * The post's content after WordPress's content filters, with an `id` on
	 * every H2 and H3 and tables in scrolling boxes, and the list of those
	 * headings. Filtered once per
	 * request, so the table of contents and the content never disagree and
	 * content filters (shortcodes, embeds) do not run twice.
	 *
	 * @param \WP_Post $post Post.
	 * @return array{html:string, headings: array<int, array{level:int, id:string, text:string}>}
	 */
	public static function content( \WP_Post $post ): array {
		if ( isset( self::$content[ $post->ID ] ) ) {
			return self::$content[ $post->ID ];
		}

		$html = (string) Context::run_post(
			$post,
			static function () {
				// get_the_content() reads the current page of a post split with <!--nextpage-->.
				return str_replace( ']]>', ']]&gt;', (string) apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
			}
		);

		self::$content[ $post->ID ] = self::anchor_headings( self::wrap_tables( $html ) );

		return self::$content[ $post->ID ];
	}

	/**
	 * Puts each table in a box that scrolls sideways, so a wide table never
	 * widens the page on phones while narrow ones still fill the column.
	 *
	 * @param string $html Content markup.
	 */
	private static function wrap_tables( string $html ): string {
		$html = (string) preg_replace( '#<table\b#i', '<div class="stx-prose__table"><table', $html );

		return (string) preg_replace( '#</table>#i', '</table></div>', $html );
	}

	/**
	 * Adds an id to headings that have none and collects H2/H3 for the table
	 * of contents. IDs keep the heading's own words (Persian included), so
	 * shared links such as #why-it-matters stay readable.
	 *
	 * @param string $html Content markup.
	 * @return array{html:string, headings: array<int, array{level:int, id:string, text:string}>}
	 */
	private static function anchor_headings( string $html ): array {
		$headings = array();
		$used     = array();

		$html = (string) preg_replace_callback(
			'#<h([23])(\s[^>]*)?>(.*?)</h\1>#is',
			static function ( array $m ) use ( &$headings, &$used ): string {
				$attrs = $m[2] ?? '';
				$text  = trim( html_entity_decode( wp_strip_all_tags( $m[3] ), ENT_QUOTES, 'UTF-8' ) );

				if ( preg_match( '/\sid=(["\'])(.*?)\1/i', $attrs, $found ) ) {
					$id = $found[2];
				} else {
					$id     = self::unique_slug( $text, $used );
					$attrs .= ' id="' . esc_attr( $id ) . '"';
				}
				$used[ $id ] = true;

				if ( '' !== $text ) {
					$headings[] = array(
						'level' => (int) $m[1],
						'id'    => $id,
						'text'  => $text,
					);
				}

				return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
			},
			$html
		);

		return array(
			'html'     => $html,
			'headings' => $headings,
		);
	}

	/**
	 * @param string              $text Heading text.
	 * @param array<string, bool> $used IDs already taken.
	 */
	private static function unique_slug( string $text, array $used ): string {
		$slug = trim( (string) preg_replace( '/[^\p{L}\p{N}]+/u', '-', $text ), '-' );
		$slug = function_exists( 'mb_strtolower' ) ? mb_strtolower( mb_substr( $slug, 0, 60 ) ) : strtolower( substr( $slug, 0, 60 ) );
		$slug = '' !== $slug ? $slug : 'section';

		$id = $slug;
		for ( $i = 2; isset( $used[ $id ] ); $i++ ) {
			$id = $slug . '-' . $i;
		}

		return $id;
	}

	/**
	 * Estimated reading time in minutes (at least 1).
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function reading_minutes( \WP_Post $post ): int {
		$text  = wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );
		$words = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );

		return max( 1, (int) ceil( count( (array) $words ) / self::WORDS_PER_MINUTE ) );
	}

	/**
	 * "5 min read", with the site's digits.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function reading_label( \WP_Post $post ): string {
		$minutes = self::reading_minutes( $post );

		/* translators: %s: number of minutes. */
		return sprintf( __( '%s min read', 'studiare-extensions' ), Base::num( $minutes ) );
	}

	/**
	 * "3 comments", with the site's digits.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function comments_label( \WP_Post $post ): string {
		$count = (int) get_comments_number( $post );

		if ( ! $count ) {
			return __( 'No comments yet', 'studiare-extensions' );
		}

		/* translators: %s: number of comments. */
		return sprintf( _n( '%s comment', '%s comments', $count, 'studiare-extensions' ), Base::num( $count ) );
	}

	/**
	 * Author avatar, or an icon when avatars are switched off.
	 *
	 * @param int    $user_id    Author ID.
	 * @param int    $size       Size in pixels.
	 * @param string $class_name Class for the image.
	 */
	public static function avatar( int $user_id, int $size, string $class_name ): string {
		$avatar = get_option( 'show_avatars' ) ? get_avatar(
			$user_id,
			$size,
			'',
			'',
			array(
				'class'         => $class_name,
				'loading'       => 'lazy',
				'force_display' => false,
			)
		) : '';

		return $avatar ? $avatar : '<span class="' . esc_attr( $class_name ) . ' stx-avatar--empty" aria-hidden="true">' . Base::icon( 'user' ) . '</span>';
	}

	/**
	 * Terms of a post in one taxonomy, the default "Uncategorized" left out.
	 *
	 * @param \WP_Post $post     Post.
	 * @param string   $taxonomy Taxonomy.
	 * @return \WP_Term[]
	 */
	public static function terms( \WP_Post $post, string $taxonomy ): array {
		$terms   = get_the_terms( $post, $taxonomy );
		$default = 'category' === $taxonomy ? (int) get_option( 'default_category' ) : 0;

		return array_values(
			array_filter(
				( $terms && ! is_wp_error( $terms ) ) ? $terms : array(),
				static function ( $term ) use ( $default ) {
					return (int) $term->term_id !== $default;
				}
			)
		);
	}

	/** @return array<string, string> Share network → label, in their default order. */
	public static function share_networks(): array {
		return array(
			'telegram' => __( 'Telegram', 'studiare-extensions' ),
			'whatsapp' => __( 'WhatsApp', 'studiare-extensions' ),
			'x'        => __( 'X (Twitter)', 'studiare-extensions' ),
			'linkedin' => __( 'LinkedIn', 'studiare-extensions' ),
			'email'    => __( 'Email', 'studiare-extensions' ),
		);
	}

	/**
	 * Share link for a network.
	 *
	 * @param string   $network Network key.
	 * @param \WP_Post $post    Post.
	 */
	public static function share_url( string $network, \WP_Post $post ): string {
		$url   = rawurlencode( (string) get_permalink( $post ) );
		$title = rawurlencode( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) );

		switch ( $network ) {
			case 'telegram':
				return 'https://t.me/share/url?url=' . $url . '&text=' . $title;
			case 'whatsapp':
				return 'https://wa.me/?text=' . $title . '%20' . $url;
			case 'x':
				return 'https://x.com/intent/post?url=' . $url . '&text=' . $title;
			case 'linkedin':
				return 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url;
			case 'email':
				return 'mailto:?subject=' . $title . '&body=' . $url;
		}

		return '';
	}

	/**
	 * What the post list being viewed is about: the posts page, a category or
	 * tag, an author, a date or a search. In a template's editor it describes
	 * the posts page with sample numbers.
	 *
	 * @param string $blog_title Title for the posts page ('' for the page's own title).
	 * @param string $blog_text  Text under it on the posts page.
	 * @return array{kind:string, eyebrow:string, title:string, text:string, count:int, author:int}
	 */
	public static function archive( string $blog_title, string $blog_text ): array {
		global $wp_query;

		$info = array(
			'kind'    => 'blog',
			'eyebrow' => '',
			'title'   => '' !== $blog_title ? $blog_title : self::posts_page_title(),
			'text'    => $blog_text,
			'count'   => (int) ( $wp_query->found_posts ?? 0 ),
			'author'  => 0,
		);

		if ( is_category() || is_tag() || is_tax() ) {
			$term     = get_queried_object();
			$taxonomy = $term instanceof \WP_Term ? get_taxonomy( $term->taxonomy ) : null;

			$info['kind']    = 'term';
			$info['eyebrow'] = $taxonomy ? $taxonomy->labels->singular_name : '';
			$info['title']   = single_term_title( '', false );
			$info['text']    = wp_strip_all_tags( term_description() );
		} elseif ( is_author() ) {
			$author = get_queried_object();

			$info['kind']    = 'author';
			$info['eyebrow'] = __( 'Author', 'studiare-extensions' );
			$info['title']   = $author instanceof \WP_User ? $author->display_name : '';
			$info['text']    = $author instanceof \WP_User ? (string) get_the_author_meta( 'description', $author->ID ) : '';
			$info['author']  = $author instanceof \WP_User ? (int) $author->ID : 0;
		} elseif ( is_date() ) {
			$info['kind']    = 'date';
			$info['eyebrow'] = __( 'Archive', 'studiare-extensions' );
			$info['title']   = self::date_title();
			$info['text']    = '';
		} elseif ( is_search() ) {
			$info['kind']    = 'search';
			$info['eyebrow'] = __( 'Search results', 'studiare-extensions' );
			// Persian guillemets on right-to-left sites, curly quotes elsewhere.
			$info['title'] = sprintf( Site::is_rtl() ? '«%s»' : '“%s”', get_search_query( false ) );
			$info['text']  = '';
		} elseif ( ! is_home() ) {
			// Editing or previewing the template: a posts page with the site's real post count.
			$info['count'] = (int) wp_count_posts( 'post' )->publish;
		}

		return $info;
	}

	/** Title of the posts page, else "Blog". */
	private static function posts_page_title(): string {
		$page = (int) get_option( 'page_for_posts' );

		return $page ? html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ) : __( 'Blog', 'studiare-extensions' );
	}

	/** Title of a date archive, in the Jalali calendar on Persian sites. */
	private static function date_title(): string {
		add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
		$title = wp_strip_all_tags( get_the_archive_title() );
		remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

		return Base::digits( $title );
	}
}
