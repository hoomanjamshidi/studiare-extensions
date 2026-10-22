<?php
/**
 * End of the printed (PDF) copy: where the article was published. Hidden
 * on screen.
 *
 * @var \WP_Post $post Post.
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="stx-print-foot" hidden>
	<span><?php esc_html_e( 'Source:', 'studiare-extensions' ); ?></span>
	<?php // Persian slugs are stored percent-encoded; print them readable. ?>
	<bdi dir="ltr"><?php echo esc_html( rawurldecode( (string) get_permalink( $post ) ) ); ?></bdi>
</div>
