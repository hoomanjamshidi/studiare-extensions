<?php
/**
 * The three purchase steps over the cart, the checkout and the thank-you
 * page. Done steps link back (only the cart can be revisited).
 *
 * @var int  $current Current step, 1 to 3.
 * @var bool $digital Whether the purchase holds only courses and downloads.
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Persian;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$steps = array(
	1 => __( 'Cart', 'studiare-extensions' ),
	2 => __( 'Details & payment', 'studiare-extensions' ),
	3 => $digital ? __( 'Access to your courses', 'studiare-extensions' ) : __( 'Order tracking', 'studiare-extensions' ),
);
?>
<nav class="stx-co stx-steps" aria-label="<?php esc_attr_e( 'Purchase steps', 'studiare-extensions' ); ?>">
	<ol class="stx-steps__list">
		<?php foreach ( $steps as $number => $label ) : ?>
			<?php
			$state = $number < $current ? 'is-done' : ( $number === $current ? 'is-current' : '' );
			$mark  = $number < $current ? Icon_Library::svg( 'phosphor', 'check' ) : esc_html( Persian::number( $number ) );
			?>
			<li class="stx-steps__step <?php echo esc_attr( $state ); ?>"<?php echo $number === $current ? ' aria-current="step"' : ''; ?>>
				<?php if ( 1 === $number && $current > 1 && $current < 3 ) : ?>
					<a class="stx-steps__link" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
						<span class="stx-steps__mark" aria-hidden="true"><?php echo $mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG or escaped number. ?></span>
						<span class="stx-steps__label"><?php echo esc_html( $label ); ?></span>
					</a>
				<?php else : ?>
					<span class="stx-steps__mark" aria-hidden="true"><?php echo $mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG or escaped number. ?></span>
					<span class="stx-steps__label"><?php echo esc_html( $label ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
