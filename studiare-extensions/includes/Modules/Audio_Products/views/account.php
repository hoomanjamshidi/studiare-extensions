<?php
/**
 * "My audio" tab of the account page: a card per audio product the
 * customer owns, each a link to that product's player, or a link to the
 * shop when there is none.
 *
 * @var array[] $albums My_Account::album() of each owned product.
 * @var string  $style  Inline colour tokens.
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.
?>
<div class="stx-ap-account" style="<?php echo esc_attr( $style ); ?>">
	<?php if ( $albums ) : ?>
		<ul class="stx-ap-lib">
			<?php foreach ( $albums as $album ) : ?>
				<li>
					<a class="stx-ap-lib__item" href="<?php echo esc_url( $album['url'] ); ?>">
						<span class="stx-ap__art" aria-hidden="true">
							<?php
							if ( $album['cover_id'] ) {
								echo wp_get_attachment_image(
									$album['cover_id'],
									'thumbnail',
									false,
									array(
										'class'   => 'stx-ap__cover',
										'alt'     => '',
										'sizes'   => '64px',
										'loading' => 'lazy',
									)
								);
							} else {
								echo '<span class="stx-ap__cover stx-ap__cover--empty">' . Icon_Library::svg( 'phosphor', 'headphones', true ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
							}
							?>
						</span>
						<span class="stx-ap-lib__body">
							<span class="stx-ap-lib__title"><?php echo esc_html( $album['title'] ); ?></span>
							<span class="stx-ap__meta">
								<?php foreach ( $album['facts'] as $fact_text ) : ?>
									<span><?php echo esc_html( $fact_text ); ?></span>
								<?php endforeach; ?>
							</span>
						</span>
						<span class="stx-ap-lib__go">
							<span><?php esc_html_e( 'Listen', 'studiare-extensions' ); ?></span>
							<?php echo Icon_Library::svg( 'phosphor', 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<div class="woocommerce-info">
			<?php esc_html_e( 'You have no audio products yet.', 'studiare-extensions' ); ?>
			<a class="button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'studiare-extensions' ); ?></a>
		</div>
	<?php endif; ?>
</div>
