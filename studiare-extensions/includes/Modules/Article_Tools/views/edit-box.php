<?php
/**
 * Fields of the "Studiare+ article tools" box on the post edit screen.
 * edit-box.js adds the media library pickers and the audio preview; without
 * it the audio link can still be pasted.
 *
 * @var \WP_Post $post        Post being edited.
 * @var array    $podcast     Saved podcast (Podcast::stored()).
 * @var string   $audio_url   Address of the saved audio, or ''.
 * @var bool     $pdf_off     Whether the PDF button is hidden on this post.
 * @var bool     $has_podcast Whether the podcast player is switched on.
 * @var bool     $has_pdf     Whether the PDF button is switched on.
 *
 * @package StudiareExt
 */

use StudiareExt\Modules\Article_Tools\Podcast;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$cover = $podcast['cover_id'] ? wp_get_attachment_image( (int) $podcast['cover_id'], 'thumbnail', false, array( 'class' => 'stx-atb__cover-img' ) ) : '';
?>
<div class="stx-atb" data-stx-article-box>
	<?php if ( $has_podcast ) : ?>
		<p class="stx-atb__intro"><?php esc_html_e( 'Add an audio version of this post. It appears as a podcast player at the top of the article.', 'studiare-extensions' ); ?></p>

		<div class="stx-atb__field">
			<label class="stx-atb__label" for="stx-podcast-url"><?php esc_html_e( 'Podcast audio', 'studiare-extensions' ); ?></label>
			<input type="url" class="widefat" id="stx-podcast-url" name="stx_podcast[url]" value="<?php echo esc_attr( $audio_url ); ?>" dir="ltr" placeholder="https://example.com/episode.mp3" data-stx-audio-url>
			<input type="hidden" name="stx_podcast[audio_id]" value="<?php echo esc_attr( (string) $podcast['audio_id'] ); ?>" data-stx-audio-id>
			<span class="stx-atb__buttons">
				<button type="button" class="button" data-stx-pick="audio"><?php esc_html_e( 'Choose from the media library', 'studiare-extensions' ); ?></button>
				<button type="button" class="button-link button-link-delete" data-stx-clear="audio" <?php echo '' === $audio_url ? 'hidden' : ''; ?>><?php esc_html_e( 'Remove', 'studiare-extensions' ); ?></button>
			</span>
			<audio class="stx-atb__preview" controls preload="none" src="<?php echo esc_url( $audio_url ); ?>" data-stx-audio-preview <?php echo '' === $audio_url ? 'hidden' : ''; ?>></audio>
			<span class="description"><?php esc_html_e( 'Upload an MP3 or M4A file, or paste a direct link to one.', 'studiare-extensions' ); ?></span>
		</div>

		<div class="stx-atb__field">
			<label class="stx-atb__label" for="stx-podcast-title"><?php esc_html_e( 'Episode title', 'studiare-extensions' ); ?></label>
			<input type="text" class="widefat" id="stx-podcast-title" name="stx_podcast[title]" value="<?php echo esc_attr( $podcast['title'] ); ?>" maxlength="120" placeholder="<?php esc_attr_e( 'Empty: the post title', 'studiare-extensions' ); ?>">
		</div>

		<div class="stx-atb__field">
			<label class="stx-atb__label" for="stx-podcast-speaker"><?php esc_html_e( 'Narrator or guests', 'studiare-extensions' ); ?></label>
			<input type="text" class="widefat" id="stx-podcast-speaker" name="stx_podcast[speaker]" value="<?php echo esc_attr( $podcast['speaker'] ); ?>" maxlength="80">
		</div>

		<div class="stx-atb__field">
			<label class="stx-atb__label" for="stx-podcast-duration"><?php esc_html_e( 'Duration', 'studiare-extensions' ); ?></label>
			<input type="text" class="stx-atb__short" id="stx-podcast-duration" name="stx_podcast[duration]" value="<?php echo esc_attr( $podcast['duration'] ? Podcast::clock( (int) $podcast['duration'] ) : '' ); ?>" dir="ltr" placeholder="12:34" inputmode="numeric" data-stx-audio-duration>
			<span class="description"><?php esc_html_e( 'Minutes:seconds. Filled in for media library files; shown before the audio loads.', 'studiare-extensions' ); ?></span>
		</div>

		<div class="stx-atb__field">
			<span class="stx-atb__label"><?php esc_html_e( 'Cover', 'studiare-extensions' ); ?></span>
			<input type="hidden" name="stx_podcast[cover_id]" value="<?php echo esc_attr( (string) $podcast['cover_id'] ); ?>" data-stx-cover-id>
			<span class="stx-atb__cover" data-stx-cover-preview <?php echo '' === $cover ? 'hidden' : ''; ?>><?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output. ?></span>
			<span class="stx-atb__buttons">
				<button type="button" class="button" data-stx-pick="cover"><?php esc_html_e( 'Choose a picture', 'studiare-extensions' ); ?></button>
				<button type="button" class="button-link button-link-delete" data-stx-clear="cover" <?php echo '' === $cover ? 'hidden' : ''; ?>><?php esc_html_e( 'Remove', 'studiare-extensions' ); ?></button>
			</span>
			<span class="description"><?php esc_html_e( 'Empty: the featured image. A square picture looks best.', 'studiare-extensions' ); ?></span>
		</div>
	<?php endif; ?>

	<?php if ( $has_pdf ) : ?>
		<div class="stx-atb__field<?php echo $has_podcast ? ' stx-atb__field--split' : ''; ?>">
			<label>
				<input type="checkbox" name="stx_pdf_off" value="1" <?php checked( $pdf_off ); ?>>
				<?php esc_html_e( 'Hide the "Save as PDF" button on this post', 'studiare-extensions' ); ?>
			</label>
		</div>
	<?php endif; ?>
</div>
