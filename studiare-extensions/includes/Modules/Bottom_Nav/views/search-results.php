<?php
/**
 * Live search results, returned by Live_Search and placed under the search
 * field of the search sheet.
 *
 * @var \StudiareExt\Modules\Bottom_Nav\Renderer $renderer
 * @var array                                    $view View data:
 *      `results` (title_html, url, thumb, icon, type, price, date),
 *      `term`, `count` (localized) and `all_url`.
 *
 * @package StudiareExt
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$title_tags = array( 'mark' => array( 'class' => true ) );
?>
<?php if ( ! $view['results'] ) : ?>
	<div class="stx-results-state">
		<span class="stx-results-state__icon" aria-hidden="true"><?php echo $renderer->icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<p class="stx-results-state__title">
			<?php
			/* translators: %s: search term. */
			echo esc_html( sprintf( __( 'Nothing found for “%s”.', 'studiare-extensions' ), $view['term'] ) );
			?>
		</p>
		<p class="stx-results-state__text"><?php esc_html_e( 'Check the spelling or try a shorter word.', 'studiare-extensions' ); ?></p>
	</div>
<?php else : ?>
	<div class="stx-results__head">
		<span class="stx-results__heading"><?php esc_html_e( 'Results', 'studiare-extensions' ); ?></span>
		<span class="stx-results__count"><?php echo esc_html( $view['count'] ); ?></span>
	</div>
	<ul class="stx-results">
		<?php foreach ( $view['results'] as $index => $result ) : ?>
			<li class="stx-results__item" style="--stx-i:<?php echo (int) $index; ?>">
				<a class="stx-results__link" href="<?php echo esc_url( $result['url'] ); ?>">
					<span class="stx-results__media<?php echo '' === $result['thumb'] ? ' is-placeholder' : ''; ?>" aria-hidden="true">
						<?php echo '' !== $result['thumb'] ? $result['thumb'] : $renderer->icon( $result['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core thumbnail markup or bundled SVG. ?>
					</span>
					<span class="stx-results__text">
						<span class="stx-results__title"><?php echo wp_kses( $result['title_html'], $title_tags ); ?></span>
						<?php if ( '' !== $result['type'] || '' !== $result['price'] || '' !== $result['date'] ) : ?>
							<span class="stx-results__meta">
								<?php if ( '' !== $result['type'] ) : ?>
									<span class="stx-results__type"><?php echo esc_html( $result['type'] ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $result['date'] ) : ?>
									<span class="stx-results__date"><?php echo esc_html( $result['date'] ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $result['price'] ) : ?>
									<span class="stx-results__price"><?php echo wp_kses_post( $result['price'] ); ?></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<a class="stx-results__all" href="<?php echo esc_url( $view['all_url'] ); ?>"><?php esc_html_e( 'See all results', 'studiare-extensions' ); ?></a>
<?php endif; ?>
