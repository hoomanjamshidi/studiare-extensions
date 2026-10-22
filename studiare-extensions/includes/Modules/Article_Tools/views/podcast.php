<?php
/**
 * Podcast player at the top of a post (and in the admin preview).
 *
 * The markup only uses <div>/<span>: it is part of the post content, where
 * the theme spaces every <p>, and headings would join the table of contents.
 * article-tools.js creates the audio element and wires the controls; without
 * JavaScript the controls are hidden and the browser's own player shows.
 * The timeline reads left to right in every language, like a tape.
 *
 * @var array  $pod     Podcast::view().
 * @var array  $options Podcast settings (label, look, download, speed, resume).
 * @var string $style   Inline colour tokens.
 *
 * @package StudiareExt
 */

use StudiareExt\Core\Icon_Library;
use StudiareExt\Core\Persian;
use StudiareExt\Modules\Article_Tools\Podcast;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$clock = static function ( int $seconds ): string {
	$time = Podcast::clock( $seconds );
	return Persian::is_site_persian() ? Persian::digits( $time ) : $time;
};

$bars = '';
foreach ( $pod['wave'] as $height ) {
	$bars .= '<i style="--h:' . (int) $height . '%"></i>';
}

$label   = '' !== $options['label'] ? $options['label'] : __( 'Listen to this article', 'studiare-extensions' );
$minutes = $pod['duration'] ? max( 1, (int) round( $pod['duration'] / 60 ) ) : 0;
?>
<section id="stx-podcast" class="stx-pod stx-pod--<?php echo esc_attr( $options['look'] ); ?>" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( $label . ': ' . $pod['title'] ); ?>"
	data-stx-pod
	data-id="<?php echo esc_attr( (string) $pod['id'] ); ?>"
	data-audio="<?php echo esc_url( $pod['src'] ); ?>"
	data-duration="<?php echo esc_attr( (string) $pod['duration'] ); ?>"
	data-title="<?php echo esc_attr( $pod['title'] ); ?>"
	data-artist="<?php echo esc_attr( '' !== $pod['speaker'] ? $pod['speaker'] : get_bloginfo( 'name' ) ); ?>"
	data-artwork="<?php echo esc_url( $pod['artwork'] ); ?>"
	data-resume="<?php echo $options['resume'] ? '1' : '0'; ?>">
	<div class="stx-pod__card">
		<div class="stx-pod__art" aria-hidden="true">
			<?php
			if ( $pod['cover_id'] ) {
				echo wp_get_attachment_image(
					$pod['cover_id'],
					'medium',
					false,
					array(
						'class' => 'stx-pod__cover',
						'alt'   => '',
						'sizes' => '120px',
					)
				);
			} else {
				echo '<span class="stx-pod__cover stx-pod__cover--empty">' . Icon_Library::svg( 'phosphor', 'headphones', true ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG.
			}
			?>
			<span class="stx-pod__eq"><i></i><i></i><i></i><i></i></span>
		</div>

		<div class="stx-pod__head">
			<span class="stx-pod__eyebrow"><?php echo Icon_Library::svg( 'phosphor', 'headphones' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?><span><?php echo esc_html( $label ); ?></span></span>
			<span class="stx-pod__title"><?php echo esc_html( $pod['title'] ); ?></span>
			<?php if ( '' !== $pod['speaker'] || $minutes ) : ?>
				<span class="stx-pod__meta">
					<?php if ( '' !== $pod['speaker'] ) : ?>
						<span><?php echo esc_html( $pod['speaker'] ); ?></span>
					<?php endif; ?>
					<?php if ( $minutes ) : ?>
						<?php /* translators: %s: length of the episode in minutes. */ ?>
						<span><?php echo esc_html( sprintf( _n( '%s minute', '%s minutes', $minutes, 'studiare-extensions' ), Persian::number( $minutes ) ) ); ?></span>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</div>

		<div class="stx-pod__player">
			<button type="button" class="stx-pod__play" data-pod-play aria-label="<?php esc_attr_e( 'Play', 'studiare-extensions' ); ?>">
				<?php echo Podcast::glyph( 'play', 'stx-pod__glyph-play' ) . Podcast::glyph( 'pause', 'stx-pod__glyph-pause' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?>
				<span class="stx-pod__spinner" aria-hidden="true"></span>
			</button>

			<div class="stx-pod__track" dir="ltr">
				<span class="stx-pod__wave" aria-hidden="true"><?php echo $bars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integers only. ?></span>
				<span class="stx-pod__wave stx-pod__wave--played" aria-hidden="true"><?php echo $bars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integers only. ?></span>
				<input type="range" class="stx-pod__seek" min="0" max="<?php echo esc_attr( (string) $pod['duration'] ); ?>" step="1" value="0" aria-label="<?php esc_attr_e( 'Playback position', 'studiare-extensions' ); ?>" data-pod-seek>
			</div>

			<span class="stx-pod__times" dir="ltr">
				<span data-pod-current><?php echo esc_html( $clock( 0 ) ); ?></span>
				<span data-pod-total><?php echo esc_html( $pod['duration'] ? $clock( $pod['duration'] ) : '--:--' ); ?></span>
			</span>

			<span class="stx-pod__tools">
				<?php /* translators: %s: number of seconds. */ ?>
				<button type="button" class="stx-pod__tool" data-pod-skip="<?php echo esc_attr( (string) -Podcast::SKIP_BACK ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Back %s seconds', 'studiare-extensions' ), Persian::number( Podcast::SKIP_BACK ) ) ); ?>">
					<?php echo Podcast::glyph( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?>
					<span class="stx-pod__skip"><?php echo esc_html( Persian::number( Podcast::SKIP_BACK ) ); ?></span>
				</button>
				<?php /* translators: %s: number of seconds. */ ?>
				<button type="button" class="stx-pod__tool" data-pod-skip="<?php echo esc_attr( (string) Podcast::SKIP_FORWARD ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Forward %s seconds', 'studiare-extensions' ), Persian::number( Podcast::SKIP_FORWARD ) ) ); ?>">
					<?php echo Podcast::glyph( 'forward' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant SVG. ?>
					<span class="stx-pod__skip"><?php echo esc_html( Persian::number( Podcast::SKIP_FORWARD ) ); ?></span>
				</button>
				<?php if ( $options['speed'] ) : ?>
					<?php /* translators: %s: playback speed, e.g. 1.5×. */ ?>
					<button type="button" class="stx-pod__tool stx-pod__speed" data-pod-speed aria-label="<?php echo esc_attr( sprintf( __( 'Playback speed: %s', 'studiare-extensions' ), Persian::number( 1 ) . '×' ) ); ?>"><?php echo esc_html( Persian::number( 1 ) . '×' ); ?></button>
				<?php endif; ?>
				<?php if ( $options['download'] ) : ?>
					<a class="stx-pod__download" href="<?php echo esc_url( $pod['src'] ); ?>" download="<?php echo esc_attr( $pod['file_name'] ); ?>">
						<?php echo Icon_Library::svg( 'phosphor', 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled SVG. ?>
						<span><?php esc_html_e( 'Download', 'studiare-extensions' ); ?></span>
					</a>
				<?php endif; ?>
			</span>

			<span class="stx-pod__error" role="status" data-pod-error hidden><?php esc_html_e( 'The audio could not be played. Check your connection, or download the file.', 'studiare-extensions' ); ?></span>
		</div>
	</div>
	<noscript><style>.stx-pod__player{display:none!important}</style><audio class="stx-pod__native" controls preload="none" src="<?php echo esc_url( $pod['src'] ); ?>"></audio></noscript>
</section>
