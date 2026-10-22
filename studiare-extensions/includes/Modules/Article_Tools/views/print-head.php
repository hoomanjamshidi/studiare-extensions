<?php
/**
 * Heading of the printed (PDF) copy: site, title, author, date and the
 * featured picture. Hidden on screen; the print stylesheet shows it, and
 * article-tools.js finds the article through it (its parent element holds
 * the post content).
 *
 * The picture is lazy and hidden, so visitors who never print do not load
 * it; article-tools.js loads it before opening the print window.
 *
 * @var \WP_Post $post    Post.
 * @var string   $date    Publish date, as shown on Persian or other sites.
 * @var bool     $picture Whether to include the featured picture.
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$post_title = wp_strip_all_tags( get_the_title( $post ) );
$author     = get_the_author_meta( 'display_name', (int) $post->post_author );
$categories = wp_list_pluck( (array) get_the_category( $post->ID ), 'name' );
$meta       = array_filter( array( $author, $date, wp_sprintf( '%l', $categories ) ) );
$image      = $picture && has_post_thumbnail( $post ) ? wp_get_attachment_image_src( (int) get_post_thumbnail_id( $post ), 'large' ) : false;
?>
<div class="stx-print-head" data-stx-print-root data-title="<?php echo esc_attr( $post_title ); ?>" hidden>
	<span class="stx-print-head__site"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
	<span class="stx-print-head__title"><?php echo esc_html( $post_title ); ?></span>
	<?php if ( $meta ) : ?>
		<span class="stx-print-head__meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></span>
	<?php endif; ?>
	<?php if ( $image ) : ?>
		<?php // Built by hand: Studiare forces loading="eager" on attachment images, and this one should only load for printing. ?>
		<img class="stx-print-head__img skip-lazy" data-no-lazy="1" src="<?php echo esc_url( $image[0] ); ?>" width="<?php echo esc_attr( (string) $image[1] ); ?>" height="<?php echo esc_attr( (string) $image[2] ); ?>" loading="lazy" decoding="async" alt="">
	<?php endif; ?>
</div>
