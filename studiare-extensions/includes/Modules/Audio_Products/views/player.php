<?php
/**
 * Player of an audio product: the deck (the chosen track, seeking, jumps,
 * previous/next, speed) above the track list, and the buy prompt.
 *
 * Inside the product description, so only <div>/<span>/<ol> are used: the
 * theme spaces every <p> there and headings would join tables of contents.
 * audio-products.js plays the tracks; without JavaScript the deck is hidden
 * and every track the visitor may hear gets the browser's own player. The
 * timeline reads left to right in every language, like a tape.
 *
 * @var array  $player Player::view().
 * @var array  $settings Module settings.
 * @var string $style  Inline colour tokens.
 * @var string $place  `product`, `account` or `preview`.
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Persian;
use StudiareExt\Modules\Article_Tools\Podcast;
use StudiareExt\Modules\Audio_Products\Player;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$options = $settings['player'];
$label   = '' !== $options['label'] ? $options['label'] : __( 'Audio files of this product', 'studiare-extensions' );
$count   = count( $player['tracks'] );
$minutes = $player['total'] ? max( 1, (int) round( $player['total'] / 60 ) ) : 0;
$first   = null;
$section = '';
$icon    = static function ( string $key ): string {
	return Icon_Library::svg( 'phosphor', $key );
};
foreach ( $player['tracks'] as $track ) {
	if ( 'locked' !== $track['state'] ) {
		$first = $track;
		break;
	}
}
?>
<div class="stx-ap stx-ap--<?php echo esc_attr( $options['look'] ); ?><?php echo $player['owned'] ? ' is-owned' : ''; ?>" id="<?php echo 'product' === $place ? 'stx-audio' : esc_attr( 'stx-audio-' . $player['id'] ); ?>" role="region" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( $label . ': ' . $player['title'] ); ?>"
	data-stx-ap
	data-id="<?php echo esc_attr( (string) $player['id'] ); ?>"
	data-artist="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
	data-artwork="<?php echo esc_url( $player['artwork'] ); ?>"
	data-resume="<?php echo $options['resume'] ? '1' : '0'; ?>">
	<div class="stx-ap__card">
		<div class="stx-ap__head">
			<span class="stx-ap__art" aria-hidden="true">
				<?php
				if ( $player['cover_id'] ) {
					echo wp_get_attachment_image(
						$player['cover_id'],
						'thumbnail',
						false,
						array(
							'class'   => 'stx-ap__cover',
							'alt'     => '',
							'sizes'   => '72px',
							'loading' => 'lazy',
						)
					);
				} else {
					echo '<span class="stx-ap__cover stx-ap__cover--empty">' . Icon_Library::svg( 'phosphor', 'headphones', true ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
				}
				?>
				<span class="stx-ap__eq"><i></i><i></i><i></i><i></i></span>
			</span>
			<span class="stx-ap__intro">
				<span class="stx-ap__eyebrow"><?php echo $icon( 'headphones' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php echo esc_html( $label ); ?></span></span>
				<span class="stx-ap__title"><?php echo esc_html( $player['title'] ); ?></span>
				<span class="stx-ap__meta">
					<?php /* translators: %s: number of audio tracks. */ ?>
					<span><?php echo esc_html( sprintf( _n( '%s track', '%s tracks', $count, 'studiare-extensions' ), Persian::number( $count ) ) ); ?></span>
					<?php if ( $minutes ) : ?>
						<?php /* translators: %s: total length in minutes. */ ?>
						<span><?php echo esc_html( sprintf( _n( '%s minute', '%s minutes', $minutes, 'studiare-extensions' ), Persian::number( $minutes ) ) ); ?></span>
					<?php endif; ?>
				</span>
			</span>
			<?php if ( $player['owned'] ) : ?>
				<span class="stx-ap__status stx-ap__status--owned"><?php echo $icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php esc_html_e( 'Full access', 'studiare-extensions' ); ?></span></span>
			<?php elseif ( $player['manager'] ) : ?>
				<?php // Store managers hear everything to check the files; say so, so it is not taken for a leak. ?>
				<span class="stx-ap__status"><?php echo $icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php esc_html_e( 'Store manager view: every track is open to you', 'studiare-extensions' ); ?></span></span>
			<?php elseif ( $first ) : ?>
				<span class="stx-ap__status"><?php echo $icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php esc_html_e( 'Free preview', 'studiare-extensions' ); ?></span></span>
			<?php endif; ?>
		</div>

		<?php if ( $first ) : ?>
			<div class="stx-ap__deck" data-ap-deck>
				<div class="stx-ap__top">
					<span class="stx-ap__now">
						<span class="stx-ap__now-label" data-ap-now-label><?php echo esc_html( $first['note'] ); ?></span>
						<span class="stx-ap__now-title" data-ap-now-title><?php echo esc_html( $first['title'] ); ?></span>
					</span>
					<?php if ( $options['speed'] ) : ?>
						<?php /* translators: %s: playback speed, e.g. 1.5×. */ ?>
						<button type="button" class="stx-ap__tool stx-ap__speed" data-ap-speed aria-label="<?php echo esc_attr( sprintf( __( 'Playback speed: %s', 'studiare-extensions' ), Persian::number( 1 ) . '×' ) ); ?>"><?php echo esc_html( Persian::number( 1 ) . '×' ); ?></button>
					<?php endif; ?>
				</div>

				<div class="stx-ap__bar" dir="ltr">
					<span class="stx-ap__rail" aria-hidden="true"><span class="stx-ap__fill"></span></span>
					<input type="range" class="stx-ap__seek" min="0" max="<?php echo esc_attr( (string) ( $first['limit'] ? $first['limit'] : $first['duration'] ) ); ?>" step="1" value="0" aria-label="<?php esc_attr_e( 'Playback position', 'studiare-extensions' ); ?>" data-ap-seek>
				</div>
				<span class="stx-ap__times" dir="ltr">
					<span data-ap-current><?php echo esc_html( Player::clock( 0 ) ); ?></span>
					<span data-ap-total><?php echo esc_html( $first['duration'] ? Player::clock( $first['limit'] ? $first['limit'] : $first['duration'] ) : '--:--' ); ?></span>
				</span>

				<div class="stx-ap__controls" dir="ltr">
					<button type="button" class="stx-ap__tool" data-ap-step="-1" aria-label="<?php esc_attr_e( 'Previous track', 'studiare-extensions' ); ?>"><?php echo Podcast::glyph( 'previous' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?></button>
					<?php /* translators: %s: number of seconds. */ ?>
					<button type="button" class="stx-ap__tool stx-ap__tool--skip" data-ap-skip="<?php echo esc_attr( (string) -Podcast::SKIP_BACK ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Back %s seconds', 'studiare-extensions' ), Persian::number( Podcast::SKIP_BACK ) ) ); ?>"><?php echo Podcast::glyph( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?><span><?php echo esc_html( Persian::number( Podcast::SKIP_BACK ) ); ?></span></button>
					<button type="button" class="stx-ap__play" data-ap-main aria-label="<?php esc_attr_e( 'Play', 'studiare-extensions' ); ?>">
						<?php echo Podcast::glyph( 'play', 'stx-ap__glyph-play' ) . Podcast::glyph( 'pause', 'stx-ap__glyph-pause' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?>
						<span class="stx-ap__spinner" aria-hidden="true"></span>
					</button>
					<?php /* translators: %s: number of seconds. */ ?>
					<button type="button" class="stx-ap__tool stx-ap__tool--skip" data-ap-skip="<?php echo esc_attr( (string) Podcast::SKIP_FORWARD ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Forward %s seconds', 'studiare-extensions' ), Persian::number( Podcast::SKIP_FORWARD ) ) ); ?>"><?php echo Podcast::glyph( 'forward' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?><span><?php echo esc_html( Persian::number( Podcast::SKIP_FORWARD ) ); ?></span></button>
					<button type="button" class="stx-ap__tool" data-ap-step="1" aria-label="<?php esc_attr_e( 'Next track', 'studiare-extensions' ); ?>"><?php echo Podcast::glyph( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?></button>
				</div>

				<div class="stx-ap__gate" role="status" data-ap-gate hidden>
					<span><?php esc_html_e( 'The free preview of this track has ended.', 'studiare-extensions' ); ?></span>
					<?php if ( $player['buy'] ) : ?>
						<a class="stx-ap__buy" href="<?php echo esc_url( $player['buy']['url'] ); ?>"><?php echo $icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php echo esc_html( $player['buy']['text'] ); ?></span></a>
					<?php endif; ?>
				</div>
				<span class="stx-ap__error" role="status" data-ap-error hidden><?php esc_html_e( 'The audio could not be played. Check your connection and try again.', 'studiare-extensions' ); ?></span>
			</div>
		<?php endif; ?>

		<ol class="stx-ap__list">
			<?php foreach ( $player['tracks'] as $index => $track ) : ?>
				<?php
				$time = $track['duration'] ? Player::clock( $track['duration'] ) : '';
				$name = $track['section'] !== $section ? $track['section'] : '';

				$section = $track['section'];
				?>
				<li class="stx-ap__track is-<?php echo esc_attr( $track['state'] ); ?>"
					data-ap-track
					data-key="<?php echo esc_attr( $track['key'] ); ?>"
					data-state="<?php echo esc_attr( $track['state'] ); ?>"
					data-src="<?php echo esc_url( $track['src'] ); ?>"
					data-limit="<?php echo esc_attr( (string) $track['limit'] ); ?>"
					data-duration="<?php echo esc_attr( (string) $track['duration'] ); ?>"
					data-title="<?php echo esc_attr( $track['title'] ); ?>"
					data-note="<?php echo esc_attr( $track['note'] ); ?>">
					<?php if ( '' !== $name ) : ?>
						<span class="stx-ap__section"><?php echo esc_html( $name ); ?></span>
					<?php endif; ?>
					<?php if ( 'locked' === $track['state'] ) : ?>
						<span class="stx-ap__row">
					<?php else : ?>
						<?php /* translators: %s: track title. */ ?>
						<button type="button" class="stx-ap__row" data-ap-play aria-label="<?php echo esc_attr( sprintf( __( 'Play: %s', 'studiare-extensions' ), $track['title'] ) ); ?>">
					<?php endif; ?>
						<span class="stx-ap__num">
							<?php if ( 'locked' === $track['state'] ) : ?>
								<?php echo $icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
							<?php else : ?>
								<span class="stx-ap__digit"><?php echo esc_html( Persian::number( $index + 1 ) ); ?></span>
								<?php echo Podcast::glyph( 'play', 'stx-ap__glyph-play' ) . Podcast::glyph( 'pause', 'stx-ap__glyph-pause' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?>
							<?php endif; ?>
						</span>
						<span class="stx-ap__name"><?php echo esc_html( $track['title'] ); ?></span>
						<?php if ( '' !== $track['note'] ) : ?>
							<span class="stx-ap__note"><?php echo esc_html( $track['note'] ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $time ) : ?>
							<span class="stx-ap__time" dir="ltr"><?php echo esc_html( $time ); ?></span>
						<?php endif; ?>
					<?php echo 'locked' === $track['state'] ? '</span>' : '</button>'; ?>
					<?php if ( '' !== $track['download'] ) : ?>
						<?php /* translators: %s: track title. */ ?>
						<a class="stx-ap__dl" href="<?php echo esc_url( $track['download'] ); ?>" download aria-label="<?php echo esc_attr( sprintf( __( 'Download: %s', 'studiare-extensions' ), $track['title'] ) ); ?>"><?php echo $icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?></a>
					<?php endif; ?>
					<?php if ( '' !== $track['src'] && 'preview' !== $place ) : ?>
						<noscript><audio class="stx-ap__native" controls preload="none" src="<?php echo esc_url( $track['src'] ); ?>"></audio></noscript>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>

		<?php if ( $player['buy'] || ( '' !== $player['login'] && ! $player['owned'] && ! $player['manager'] ) ) : ?>
			<div class="stx-ap__foot">
				<span class="stx-ap__foot-text">
					<?php
					echo esc_html(
						$player['buy']
							? __( 'Buy this product to hear every track in full, here on the site.', 'studiare-extensions' )
							: __( 'Already bought it? Log in to listen.', 'studiare-extensions' )
					);
					?>
				</span>
				<?php if ( $player['buy'] ) : ?>
					<a class="stx-ap__buy" href="<?php echo esc_url( $player['buy']['url'] ); ?>"><?php echo $icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php echo esc_html( $player['buy']['text'] ); ?></span></a>
				<?php endif; ?>
				<?php if ( '' !== $player['login'] ) : ?>
					<?php // `register-modal-opener` opens Studiare's login popup; without it the link goes to the account page. ?>
					<a class="stx-ap__login register-modal-opener" href="<?php echo esc_url( $player['login'] ); ?>"><?php echo $icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php esc_html_e( 'Log in', 'studiare-extensions' ); ?></span></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( 'preview' !== $place ) : ?>
		<noscript><style>.stx-ap__deck,.stx-ap__row .stx-ap__glyph-play{display:none!important}</style></noscript>
	<?php endif; ?>
</div>
