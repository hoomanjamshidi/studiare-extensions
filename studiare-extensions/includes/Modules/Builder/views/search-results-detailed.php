<?php
/**
 * Detailed live results of the Search widget (Search_Results::detailed()),
 * placed in the dropdown under the search field. The footer is a submit
 * button, so "see all" keeps the form's post type filter.
 *
 * @var array $view View data: `results` (url, thumb, title_html, kind,
 *                  kind_label, icon, facts, price), `term`, `count`
 *                  (localized) and `topics` (name, url; only when empty).
 *
 * @package StudiareExt
 */

use StudiareExt\Modules\Builder\Elementor\Widgets\Base;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$title_tags = array( 'mark' => array( 'class' => true ) );
?>
<?php if ( ! $view['results'] ) : ?>
	<div class="stx-live__empty">
		<span class="stx-live__empty-icon" aria-hidden="true"><?php echo Base::icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
		<p class="stx-live__empty-title">
			<?php
			/* translators: %s: search term. */
			echo esc_html( sprintf( __( 'Nothing found for “%s”.', 'studiare-extensions' ), $view['term'] ) );
			?>
		</p>
		<?php if ( $view['topics'] ) : ?>
			<p class="stx-live__empty-text"><?php esc_html_e( 'Try one of these topics:', 'studiare-extensions' ); ?></p>
			<ul class="stx-live__topics">
				<?php foreach ( $view['topics'] as $topic ) : ?>
					<li><a class="stx-live__topic" href="<?php echo esc_url( $topic['url'] ); ?>"><?php echo esc_html( $topic['name'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="stx-live__empty-text"><?php esc_html_e( 'Check the spelling or try a shorter word.', 'studiare-extensions' ); ?></p>
		<?php endif; ?>
	</div>
<?php else : ?>
	<div class="stx-live__head">
		<span class="stx-live__count"><?php echo esc_html( $view['count'] ); ?></span>
		<?php // Keyboard hint; hidden on touch screens and redundant for screen readers (the field is a combobox). ?>
		<span class="stx-live__keys" aria-hidden="true"><?php esc_html_e( '↑ ↓ to move', 'studiare-extensions' ); ?></span>
	</div>
	<ul class="stx-live__list">
		<?php foreach ( $view['results'] as $result ) : ?>
			<li class="stx-live__item">
				<a class="stx-live__link" href="<?php echo esc_url( $result['url'] ); ?>">
					<span class="stx-live__media<?php echo '' === $result['thumb'] ? ' is-placeholder' : ''; ?>" aria-hidden="true">
						<?php echo '' !== $result['thumb'] ? $result['thumb'] : Base::icon( $result['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core thumbnail markup or bundled SVG. ?>
					</span>
					<span class="stx-live__text">
						<span class="stx-live__title"><?php echo wp_kses( $result['title_html'], $title_tags ); ?></span>
						<span class="stx-live__meta">
							<span class="stx-live__tag stx-live__tag--<?php echo esc_attr( sanitize_html_class( $result['kind'] ) ); ?>"><?php echo esc_html( $result['kind_label'] ); ?></span>
							<?php foreach ( $result['facts'] as $fact ) : ?>
								<span class="stx-live__fact"><?php echo esc_html( $fact ); ?></span>
							<?php endforeach; ?>
							<?php if ( '' !== $result['price'] ) : ?>
								<span class="stx-live__price"><?php echo wp_kses_post( $result['price'] ); ?></span>
							<?php endif; ?>
						</span>
					</span>
					<span class="stx-live__go" aria-hidden="true"><?php echo Base::icon( 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<button type="submit" class="stx-live__all">
		<?php
		/* translators: %s: search term. */
		echo esc_html( sprintf( __( 'See all results for “%s”', 'studiare-extensions' ), $view['term'] ) );
		echo Base::icon( 'chevron-down', 'stx-live__all-go' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
		?>
	</button>
<?php endif; ?>
