<?php
/**
 * Card markup for the listing widgets: product cards (Product grid) and post
 * cards (Post grid), plus the striped placeholder shown where a picture is
 * missing, as in the supplied designs.
 *
 * Every card keeps one real link for keyboard and screen reader users (the
 * title); the picture repeats it for pointers only.
 *
 * @package StudiareExt
 */

namespace StudiareExt\Modules\Builder\Elementor;

use StudiareExt\Core\Persian;
use StudiareExt\Modules\Builder\Context;
use StudiareExt\Modules\Builder\Elementor\Widgets\Base;

defined( 'ABSPATH' ) || exit;

final class Cards {

	/** Filled star (the icon packs only have outlined ones). */
	private const STAR = '<svg class="stx-hcard__star" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="m12 3 2.6 5.6 6 .8-4.4 4.2 1.1 6.1L12 16.9 6.7 19.7l1.1-6.1L3.4 9.4l6-.8z"/></svg>';

	/** Products younger than this get the "New" badge. */
	private const NEW_DAYS = 30;

	/**
	 * Striped stand-in for a missing picture.
	 *
	 * @param string $label Text in the middle ('' for none).
	 * @param string $icon  Bundled icon key ('' for none).
	 */
	public static function placeholder( string $label = '', string $icon = 'image' ): string {
		return sprintf(
			'<span class="stx-ph">%1$s%2$s</span>',
			'' !== $icon ? Base::icon( $icon, 'stx-ph__icon' ) : '',
			'' !== $label ? '<span class="stx-ph__label">' . esc_html( $label ) . '</span>' : ''
		);
	}

	/* ---------------------------------------------------------------------
	 * Products
	 * ------------------------------------------------------------------- */

	/**
	 * Product card.
	 *
	 * Styles: `shop` (cart button on the picture), `compact` (buy button
	 * beside the price), `course` (teacher and course facts), `overlay` (text
	 * over the picture), `list` (a row with a small picture), `minimal` (no
	 * box, cart button over the picture) and `classic` (centred, stars and a
	 * full-width buy button).
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $o       `style`, `badge`, `excerpt`, `rating` (bools),
	 *                             `meta` (info source key or ''), `cart` (bool), `more_text`, `buy_text`.
	 */
	public static function product( \WC_Product $product, array $o ): string {
		$o = array_merge(
			array(
				'style'     => 'shop',
				'badge'     => true,
				'excerpt'   => true,
				'rating'    => true,
				'meta'      => '',
				'cart'      => true,
				'more_text' => __( 'Details', 'studiare-extensions' ),
				'buy_text'  => __( 'Buy', 'studiare-extensions' ),
			),
			$o
		);

		$style = (string) $o['style'];
		$link  = esc_url( $product->get_permalink() );
		$badge = $o['badge'] ? self::product_badge( $product ) : '';
		$price = $product->get_price_html();
		$cart  = (bool) $o['cart'];
		$icons = Base::icon( 'cart', 'stx-hcard__cart-icon' ) . Base::icon( 'check', 'stx-hcard__done-icon' );

		$top = sprintf( '<a class="stx-hcard__media" href="%1$s" tabindex="-1" aria-hidden="true">%2$s</a>', $link, self::product_image( $product ) );
		if ( '' !== $badge ) {
			$top .= '<span class="stx-hcard__badge">' . esc_html( $badge ) . '</span>';
		}
		if ( $cart && in_array( $style, array( 'shop', 'overlay', 'minimal' ), true ) ) {
			$top .= self::cart_link( $product, 'stx-hcard__fab', $icons );
		}

		$body = '';
		$kind = in_array( $style, array( 'list', 'minimal', 'classic' ), true ) ? '' : self::product_kind( $product );
		if ( '' !== $kind ) {
			$body .= '<span class="stx-hcard__kind">' . esc_html( $kind ) . '</span>';
		}
		$body .= sprintf( '<h3 class="stx-hcard__title"><a href="%1$s">%2$s</a></h3>', $link, esc_html( $product->get_name() ) );

		switch ( $style ) {
			case 'shop':
				if ( $o['excerpt'] ) {
					$excerpt = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 14 );
					if ( '' !== $excerpt ) {
						$body .= '<p class="stx-hcard__excerpt">' . esc_html( $excerpt ) . '</p>';
					}
				}
				$body .= self::product_meta( $product, $o['rating'], (string) $o['meta'] );
				break;
			case 'course':
				$body .= self::product_teacher( $product ) . self::course_facts( $product );
				break;
			case 'classic':
				$body .= $o['rating'] ? Parts::rating(
					$product,
					array(
						'format' => '({count})',
						'link'   => false,
					)
				) : '';
				break;
			case 'overlay':
				break;
			default:
				$body .= self::product_meta( $product, $o['rating'], (string) $o['meta'] );
		}

		$body .= sprintf(
			'<div class="stx-hcard__foot">%1$s%2$s</div>',
			'' !== $price ? '<span class="stx-hcard__price">' . Base::digits_html( $price ) . '</span>' : '<span></span>',
			self::product_foot_end( $product, $o, $link, $icons )
		);

		if ( 'classic' === $style && $cart ) {
			$body .= self::cart_link( $product, 'stx-hcard__buy stx-hcard__buy--block', $icons . '<span>' . esc_html( $o['buy_text'] ) . '</span>' );
		}

		return sprintf(
			'<article class="stx-hcard stx-hcard--%1$s"><div class="stx-hcard__top">%2$s</div><div class="stx-hcard__body">%3$s</div></article>',
			esc_attr( $style ),
			$top,
			$body
		);
	}

	/**
	 * What sits at the end of the price row: a details link, a buy button,
	 * a small cart button or the rating, depending on the style.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $o       Card options.
	 * @param string      $link    Escaped product URL.
	 * @param string      $icons   Cart and "added" icons.
	 */
	private static function product_foot_end( \WC_Product $product, array $o, string $link, string $icons ): string {
		switch ( $o['style'] ) {
			case 'shop':
				return sprintf( '<a class="stx-hcard__more" href="%1$s" aria-hidden="true" tabindex="-1">%2$s %3$s</a>', $link, esc_html( $o['more_text'] ), is_rtl() ? '←' : '→' );
			case 'compact':
				return $o['cart'] ? self::cart_link( $product, 'stx-hcard__buy', Base::icon( 'plus', 'stx-hcard__cart-icon' ) . Base::icon( 'check', 'stx-hcard__done-icon' ) . '<span>' . esc_html( $o['buy_text'] ) . '</span>' ) : '';
			case 'list':
				return $o['cart'] ? self::cart_link( $product, 'stx-hcard__buy stx-hcard__buy--icon', $icons ) : '';
			case 'course':
				if ( $o['rating'] && $product->get_review_count() ) {
					return sprintf(
						'<span class="stx-hcard__score">%1$s<span>%2$s</span></span>',
						self::STAR,
						esc_html( Base::num( (float) $product->get_average_rating(), 1 ) )
					);
				}
				return '';
		}

		return '';
	}

	/**
	 * First teacher of a course: photo and name.
	 *
	 * @param \WC_Product $product Product.
	 */
	private static function product_teacher( \WC_Product $product ): string {
		$ids = Parts::teacher_ids( $product->get_id() );
		if ( ! $ids ) {
			return '';
		}

		$photo = get_the_post_thumbnail(
			$ids[0],
			'thumbnail',
			array(
				'class'   => 'stx-hcard__teacher-img',
				'alt'     => '',
				'loading' => 'lazy',
			)
		);

		return sprintf(
			'<span class="stx-hcard__teacher">%1$s<span>%2$s</span></span>',
			'' !== $photo ? $photo : '<span class="stx-hcard__teacher-img stx-avatar--empty">' . Base::icon( 'user' ) . '</span>',
			esc_html( get_the_title( $ids[0] ) )
		);
	}

	/**
	 * Course facts with icons: lessons, duration and students (those that are set).
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function course_facts( \WC_Product $product ): string {
		$facts = array();

		$lessons = (int) Persian::latin_digits( Parts::info_value( $product, 'lessons' ) );
		if ( $lessons ) {
			/* translators: %s: number of lessons. */
			$facts[] = array( 'book-open', sprintf( _n( '%s lesson', '%s lessons', $lessons, 'studiare-extensions' ), Base::num( $lessons ) ) );
		}

		$duration = Parts::info_value( $product, 'duration' );
		if ( '' !== $duration ) {
			$facts[] = array( 'clock', Base::digits( $duration ) );
		}

		$students = (int) $product->get_total_sales();
		if ( $students ) {
			/* translators: %s: number of students. */
			$facts[] = array( 'users', sprintf( _n( '%s student', '%s students', $students, 'studiare-extensions' ), Base::num( $students ) ) );
		}

		if ( ! $facts ) {
			return '';
		}

		$items = '';
		foreach ( array_slice( $facts, 0, 3 ) as $fact ) {
			$items .= '<li>' . Base::icon( $fact[0] ) . '<span>' . esc_html( $fact[1] ) . '</span></li>';
		}

		return '<ul class="stx-hcard__facts">' . $items . '</ul>';
	}

	/**
	 * Small label above the title: the first real category, else "Course".
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function product_kind( \WC_Product $product ): string {
		$default = (int) get_option( 'default_product_cat' );
		$terms   = get_the_terms( $product->get_id(), 'product_cat' );

		foreach ( ( $terms && ! is_wp_error( $terms ) ) ? $terms : array() as $term ) {
			if ( (int) $term->term_id !== $default ) {
				return html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
			}
		}

		return Context::is_course( $product->get_id() ) ? __( 'Course', 'studiare-extensions' ) : '';
	}

	/**
	 * Automatic badge: discount, then featured, then new.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function product_badge( \WC_Product $product ): string {
		if ( $product->is_on_sale() ) {
			$regular = (float) $product->get_regular_price();
			$sale    = (float) $product->get_sale_price();

			if ( $product->is_type( 'simple' ) && $regular > 0 && $sale < $regular ) {
				/* translators: {percent} is replaced with the discount percentage. */
				return Base::digits( str_replace( '{percent}', (string) round( ( $regular - $sale ) / $regular * 100 ), __( '{percent}% off', 'studiare-extensions' ) ) );
			}

			return __( 'Sale', 'studiare-extensions' );
		}

		if ( $product->is_featured() ) {
			return __( 'Featured', 'studiare-extensions' );
		}

		$created = $product->get_date_created();
		if ( $created && $created->getTimestamp() > time() - self::NEW_DAYS * DAY_IN_SECONDS ) {
			return __( 'New', 'studiare-extensions' );
		}

		return '';
	}

	/**
	 * @param \WC_Product $product Product.
	 */
	private static function product_image( \WC_Product $product ): string {
		if ( ! $product->get_image_id() ) {
			return self::placeholder( '', 'image' );
		}

		return $product->get_image(
			'woocommerce_thumbnail',
			array(
				'class'   => 'stx-hcard__img',
				'loading' => 'lazy',
			)
		);
	}

	/**
	 * Rating and one fact (e.g. the course duration) on one line.
	 *
	 * @param \WC_Product $product Product.
	 * @param bool        $rating  Show the rating.
	 * @param string      $source  Info source key ('' for none).
	 */
	private static function product_meta( \WC_Product $product, bool $rating, string $source ): string {
		$parts = array();

		if ( $rating && $product->get_review_count() ) {
			$parts[] = sprintf(
				'<span class="stx-hcard__rating">%1$s<span>%2$s (%3$s)</span></span>',
				self::STAR,
				esc_html( Base::num( (float) $product->get_average_rating(), 1 ) ),
				esc_html( Base::num( (int) $product->get_review_count() ) )
			);
		}

		if ( '' !== $source ) {
			$value = Parts::info_value( $product, $source );
			if ( '' !== $value ) {
				$parts[] = '<span>' . esc_html( Base::digits( $value ) ) . '</span>';
			}
		}

		return $parts ? '<div class="stx-hcard__meta">' . implode( '<span class="stx-hcard__dot" aria-hidden="true">•</span>', $parts ) . '</div>' : '';
	}

	/**
	 * Add-to-cart link: WooCommerce's AJAX button for simple products that
	 * can be bought right away, a link to the product for everything else
	 * (variations to choose, out of stock…).
	 *
	 * The label names the product, as every card has the same short button text.
	 *
	 * @param \WC_Product $product   Product.
	 * @param string      $css_class Class for the link.
	 * @param string      $inner     Trusted inner markup.
	 */
	public static function cart_link( \WC_Product $product, string $css_class, string $inner ): string {
		$ajax = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' );

		if ( $ajax && wp_script_is( 'wc-add-to-cart', 'registered' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}

		$label = $ajax ? $product->add_to_cart_description() : $product->add_to_cart_text();

		return sprintf(
			'<a href="%1$s" class="%2$s" data-quantity="1" data-product_id="%3$d" data-product_sku="%4$s" rel="nofollow" aria-label="%5$s">%6$s</a>',
			esc_url( $ajax ? $product->add_to_cart_url() : $product->get_permalink() ),
			esc_attr( $css_class . ( $ajax ? ' add_to_cart_button ajax_add_to_cart' : '' ) ),
			$product->get_id(),
			esc_attr( $product->get_sku() ),
			esc_attr( wp_strip_all_tags( $label ) ),
			$inner
		);
	}

	/* ---------------------------------------------------------------------
	 * Posts
	 * ------------------------------------------------------------------- */

	/**
	 * Post card.
	 *
	 * @param \WP_Post $post Post.
	 * @param array    $o    `style` (card|simple|minimal|list|mini|media), `lead` (the highlighted
	 *                       first post), `badge` (category), `date`, `author`, `reading`, `excerpt`
	 *                       (bools), `button_text`, `media_icon`, `meta_key`, `heading` (h2|h3).
	 */
	public static function post( \WP_Post $post, array $o ): string {
		$o = array_merge(
			array(
				'style'       => 'card',
				'lead'        => false,
				'badge'       => true,
				'date'        => true,
				'author'      => true,
				'reading'     => false,
				'excerpt'     => false,
				'button_text' => __( 'Read more', 'studiare-extensions' ),
				'media_icon'  => 'play',
				'meta_key'    => '',
				'heading'     => 'h3',
			),
			$o
		);

		$style = (string) $o['style'];
		$tag   = 'h2' === $o['heading'] ? 'h2' : 'h3';
		$link  = esc_url( get_permalink( $post ) );
		$title = esc_html( get_the_title( $post ) );
		$image = self::post_image( $post, 'mini' === $style ? 'thumbnail' : 'medium_large' );

		if ( 'media' === $style ) {
			$meta = array_filter( array( self::post_category( $post ), self::post_extra( $post, (string) $o['meta_key'] ) ) );

			return sprintf(
				'<article class="stx-post stx-post--media"><a class="stx-post__cover" href="%1$s">%2$s%3$s<span class="stx-post__overlay"><%6$s class="stx-post__title">%4$s</%6$s>%5$s</span></a></article>',
				$link,
				$image,
				'' !== $o['media_icon'] ? '<span class="stx-post__play" aria-hidden="true">' . Base::icon( $o['media_icon'] ) . '</span>' : '',
				$title,
				$meta ? '<span class="stx-post__meta">' . esc_html( implode( ' • ', $meta ) ) . '</span>' : '',
				$tag
			);
		}

		// Blog and simple cards carry the category on the picture; the others above the title.
		$category   = $o['badge'] ? self::post_category( $post ) : '';
		$on_picture = in_array( $style, array( 'card', 'simple' ), true );

		$top = sprintf( '<a class="stx-post__media" href="%1$s" tabindex="-1" aria-hidden="true">%2$s</a>', $link, $image );
		if ( $on_picture && '' !== $category ) {
			$top .= '<span class="stx-post__badge">' . esc_html( $category ) . '</span>';
		}

		$body = '';
		if ( ! $on_picture && '' !== $category ) {
			$body .= '<span class="stx-post__cat">' . esc_html( $category ) . '</span>';
		}
		$body .= sprintf( '<%1$s class="stx-post__title"><a href="%2$s">%3$s</a></%1$s>', $tag, $link, $title );

		if ( $o['excerpt'] ) {
			$excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), $o['lead'] ? 34 : 20 );
			if ( '' !== $excerpt ) {
				$body .= '<p class="stx-post__excerpt">' . esc_html( $excerpt ) . '</p>';
			}
		}

		$meta = array();
		if ( $o['author'] ) {
			$meta[] = '<span>' . esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) ) . '</span>';
		}
		if ( $o['date'] ) {
			$meta[] = self::post_time( $post );
		}
		if ( $o['reading'] ) {
			$meta[] = '<span>' . esc_html( Post_Parts::reading_label( $post ) ) . '</span>';
		}
		if ( $meta ) {
			$body .= '<div class="stx-post__meta">' . implode( '', $meta ) . '</div>';
		}

		if ( '' !== $o['button_text'] ) {
			$body .= sprintf(
				'<a class="stx-post__more" href="%1$s">%2$s<span class="screen-reader-text">: %3$s</span></a>',
				$link,
				esc_html( $o['button_text'] ),
				$title
			);
		}

		return sprintf(
			'<article class="stx-post stx-post--%1$s%2$s"><div class="stx-post__top">%3$s</div><div class="stx-post__body">%4$s</div></article>',
			esc_attr( $style ),
			$o['lead'] ? ' stx-post--lead' : '',
			$top,
			$body
		);
	}

	/**
	 * Publish (or last update) date: Jalali on Persian sites, the site's date
	 * format elsewhere.
	 *
	 * @param \WP_Post $post  Post.
	 * @param string   $field `date` (published) or `modified`.
	 */
	public static function post_date( \WP_Post $post, string $field = 'date' ): string {
		$date = get_post_datetime( $post, $field );

		if ( $date && Persian::is_site_persian() ) {
			return Base::digits( Persian::jalali_date( $date ) );
		}

		return 'modified' === $field ? (string) get_the_modified_date( '', $post ) : (string) get_the_date( '', $post );
	}

	/**
	 * `<time>` element for a post date.
	 *
	 * @param \WP_Post $post  Post.
	 * @param string   $field `date` (published) or `modified`.
	 */
	public static function post_time( \WP_Post $post, string $field = 'date' ): string {
		$date = get_post_datetime( $post, $field );

		return '<time datetime="' . esc_attr( $date ? $date->format( DATE_W3C ) : '' ) . '">' . esc_html( self::post_date( $post, $field ) ) . '</time>';
	}

	/**
	 * First category (or first term of the type's first taxonomy), leaving
	 * out the default "Uncategorized", which says nothing about the post.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function post_category( \WP_Post $post ): string {
		$taxonomy = 'post' === $post->post_type ? 'category' : '';

		if ( '' === $taxonomy ) {
			$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
			foreach ( $taxonomies as $object ) {
				if ( $object->hierarchical && $object->public ) {
					$taxonomy = $object->name;
					break;
				}
			}
		}

		$terms   = '' !== $taxonomy ? get_the_terms( $post, $taxonomy ) : false;
		$default = 'category' === $taxonomy ? (int) get_option( 'default_category' ) : 0;

		foreach ( ( $terms && ! is_wp_error( $terms ) ) ? $terms : array() as $term ) {
			if ( (int) $term->term_id !== $default ) {
				return html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
			}
		}

		return '';
	}

	/**
	 * @param \WP_Post $post Post.
	 * @param string   $size Image size.
	 */
	private static function post_image( \WP_Post $post, string $size ): string {
		if ( ! has_post_thumbnail( $post ) ) {
			return self::placeholder( '', 'image' );
		}

		return get_the_post_thumbnail(
			$post,
			$size,
			array(
				'class'   => 'stx-post__img',
				'loading' => 'lazy',
				'alt'     => '',
			)
		);
	}

	/**
	 * A custom field shown in the media card (e.g. an episode's length);
	 * falls back to the date.
	 *
	 * @param \WP_Post $post     Post.
	 * @param string   $meta_key Custom field name ('' for the date).
	 */
	private static function post_extra( \WP_Post $post, string $meta_key ): string {
		if ( '' !== $meta_key && '_' !== $meta_key[0] ) {
			$value = get_post_meta( $post->ID, $meta_key, true );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return Base::digits( trim( (string) $value ) );
			}
		}

		return self::post_date( $post );
	}
}
