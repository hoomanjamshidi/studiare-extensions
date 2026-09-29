<?php
/**
 * Compact live results of the Search widget (Search_Results::compact()),
 * placed in the dropdown under the search field.
 *
 * @var array<int, array> $results View models: title, url, thumb, type, price.
 * @var string            $term    Search term.
 *
 * @package StudiareExt
 */

use StudiareExt\Modules\Builder\Elementor\Widgets\Base;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.
?>
<?php if ( ! $results ) : ?>
	<p class="stx-live__note">
		<?php
		/* translators: %s: search term. */
		echo esc_html( sprintf( __( 'Nothing found for “%s”.', 'studiare-extensions' ), $term ) );
		?>
	</p>
<?php else : ?>
	<ul class="stx-live__list">
		<?php foreach ( $results as $result ) : ?>
			<li class="stx-live__item">
				<a class="stx-live__link" href="<?php echo esc_url( $result['url'] ); ?>">
					<span class="stx-live__media" aria-hidden="true">
						<?php echo '' !== $result['thumb'] ? $result['thumb'] : Base::icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core thumbnail markup or bundled SVG. ?>
					</span>
					<span class="stx-live__text">
						<span class="stx-live__title"><?php echo esc_html( $result['title'] ); ?></span>
						<?php if ( '' !== $result['type'] || '' !== $result['price'] ) : ?>
							<span class="stx-live__meta">
								<?php if ( '' !== $result['type'] ) : ?>
									<span class="stx-live__type"><?php echo esc_html( $result['type'] ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $result['price'] ) : ?>
									<span class="stx-live__price"><?php echo wp_kses_post( Base::digits_html( $result['price'] ) ); ?></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
