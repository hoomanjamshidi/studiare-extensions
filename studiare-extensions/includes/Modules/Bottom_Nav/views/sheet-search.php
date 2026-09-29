<?php
/**
 * Search form inside the search sheet, plus the live results area when the
 * item has live results turned on (filled by bottom-nav.js).
 *
 * @var \StudiareExt\Modules\Bottom_Nav\Renderer $renderer
 * @var array                                    $sheet    Sheet definition (`item_id`, `live`, `post_type`).
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$field_id   = 'stx-search-' . wp_unique_id();
$results_id = $field_id . '-results';
?>
<form class="stx-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo $sheet['live'] ? ' data-stx-live-search="' . esc_attr( $sheet['item_id'] ) . '"' : ''; ?>>
	<label class="screen-reader-text" for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Search for:', 'studiare-extensions' ); ?></label>
	<div class="stx-search__field">
		<span class="stx-search__icon" aria-hidden="true"><?php echo $renderer->search_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<input
			type="search"
			class="stx-search__input"
			id="<?php echo esc_attr( $field_id ); ?>"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php esc_attr_e( 'What are you looking for?', 'studiare-extensions' ); ?>"
			autocomplete="off"
			enterkeyhint="search"
			<?php echo $sheet['live'] ? 'aria-controls="' . esc_attr( $results_id ) . '"' : ''; ?>
			data-stx-autofocus
		>
	</div>
	<?php if ( '' !== $sheet['post_type'] ) : ?>
		<input type="hidden" name="post_type" value="<?php echo esc_attr( $sheet['post_type'] ); ?>">
	<?php endif; ?>
	<button type="submit" class="stx-search__submit"><?php esc_html_e( 'Search', 'studiare-extensions' ); ?></button>
</form>
<?php if ( $sheet['live'] ) : ?>
	<div class="stx-results-area" id="<?php echo esc_attr( $results_id ); ?>" data-stx-results>
		<div class="stx-results-state">
			<span class="stx-results-state__icon" aria-hidden="true"><?php echo $renderer->icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
			<p class="stx-results-state__text"><?php esc_html_e( 'Results appear as you type.', 'studiare-extensions' ); ?></p>
		</div>
	</div>
	<p class="screen-reader-text" role="status" data-stx-results-status></p>
	<?php // Shown by bottom-nav.js while the first results load. ?>
	<template data-stx-results-skeleton>
		<ul class="stx-results stx-results--skeleton" aria-hidden="true">
			<?php for ( $row = 0; $row < 3; $row++ ) : ?>
				<li class="stx-results__item">
					<span class="stx-results__link">
						<span class="stx-results__media"></span>
						<span class="stx-results__text"><span class="stx-skeleton"></span><span class="stx-skeleton stx-skeleton--short"></span></span>
					</span>
				</li>
			<?php endfor; ?>
		</ul>
	</template>
<?php endif; ?>
