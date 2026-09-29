<?php
/**
 * Blog page (post list or single post) rendered with a Studiare+ Elementor
 * template, between the theme's header and footer.
 *
 * Loaded through `template_include`, so it runs in the global scope. A single
 * post runs the main loop, so the template's widgets and the comment form see
 * the post as the current one. A post list leaves the loop to the Post grid
 * widget ("Posts of the page being viewed").
 *
 * @package StudiareExt
 */

use StudiareExt\Modules\Builder\Module;
use StudiareExt\Modules\Builder\Renderer;
use StudiareExt\Plugin;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file.

$stx_module      = Plugin::instance()->module( 'builder' );
$stx_template_id = $stx_module instanceof Module ? $stx_module->blog()->template_id() : 0;

get_header();

if ( is_singular() ) {
	while ( have_posts() ) :
		the_post();

		if ( post_password_required() ) {
			echo '<div class="stx-blog stx-blog--locked">' . get_the_password_form() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup.
			continue;
		}

		// No post_class(): the theme styles `.post` and `.hentry` boxes, which would restyle the design.
		printf(
			'<div id="post-%1$d" class="stx-blog stx-blog--post">%2$s</div>',
			(int) get_the_ID(),
			Renderer::render( $stx_template_id ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor output.
		);
	endwhile;
} else {
	echo '<div class="stx-blog stx-blog--archive">' . Renderer::render( $stx_template_id ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor output.
}

get_footer();
