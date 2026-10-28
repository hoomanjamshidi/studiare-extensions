<?php
/**
 * Fields of the "Studiare+ audio" box on the product edit screen.
 * edit-box.js adds, removes and moves tracks and opens the media library;
 * without it the saved tracks can still be edited and links pasted.
 *
 * @var \WP_Post $post        Product being edited.
 * @var array    $album       Saved audio (Album::stored()).
 * @var bool     $has_lessons Whether the product has Studiare lessons.
 * @var array    $blank       A new track with the default preview length.
 *
 * @package StudiareExt
 */

use StudiareExt\Modules\Article_Tools\Podcast;
use StudiareExt\Modules\Audio_Products\Schema;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a class method, so variables are local.

$previews = array(
	'clip' => __( 'The first seconds (MP3 files)', 'studiare-extensions' ),
	'file' => __( 'A separate preview file', 'studiare-extensions' ),
	'free' => __( 'The whole track is free', 'studiare-extensions' ),
	'none' => __( 'No preview: buyers only', 'studiare-extensions' ),
);

$file_name = static function ( int $attachment_id ): string {
	$path = $attachment_id ? (string) get_attached_file( $attachment_id ) : '';

	return '' !== $path ? wp_basename( $path ) : '';
};

/**
 * One track row. `$index` is `__i__` in the template that edit-box.js copies.
 *
 * @param array  $track Track (Album::track_defaults() shape).
 * @param string $index Position in the posted list.
 */
$row = static function ( array $track, string $index ) use ( $previews, $file_name ): void {
	$name    = 'stx_audio[tracks][' . $index . ']';
	$id      = 'stx-audio-' . $index;
	$url     = $track['audio_id'] ? (string) wp_get_attachment_url( (int) $track['audio_id'] ) : $track['url'];
	$preview = $file_name( (int) $track['preview_id'] );
	?>
	<li class="stx-apb__track" data-stx-track>
		<input type="hidden" name="<?php echo esc_attr( $name . '[id]' ); ?>" value="<?php echo esc_attr( $track['id'] ); ?>">
		<div class="stx-apb__order">
			<button type="button" class="button-link" data-stx-move="-1" aria-label="<?php esc_attr_e( 'Move up', 'studiare-extensions' ); ?>">▲</button>
			<span class="stx-apb__number" data-stx-number></span>
			<button type="button" class="button-link" data-stx-move="1" aria-label="<?php esc_attr_e( 'Move down', 'studiare-extensions' ); ?>">▼</button>
		</div>
		<div class="stx-apb__fields">
			<div class="stx-apb__field stx-apb__field--wide">
				<label for="<?php echo esc_attr( $id . '-title' ); ?>"><?php esc_html_e( 'Title', 'studiare-extensions' ); ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( $id . '-title' ); ?>" name="<?php echo esc_attr( $name . '[title]' ); ?>" value="<?php echo esc_attr( $track['title'] ); ?>" maxlength="160" data-stx-title>
			</div>
			<div class="stx-apb__field stx-apb__field--wide">
				<label for="<?php echo esc_attr( $id . '-url' ); ?>"><?php esc_html_e( 'Audio file', 'studiare-extensions' ); ?></label>
				<span class="stx-apb__file">
					<input type="url" class="widefat" id="<?php echo esc_attr( $id . '-url' ); ?>" name="<?php echo esc_attr( $name . '[url]' ); ?>" value="<?php echo esc_attr( $url ); ?>" dir="ltr" placeholder="https://" data-stx-url>
					<input type="hidden" name="<?php echo esc_attr( $name . '[audio_id]' ); ?>" value="<?php echo esc_attr( (string) $track['audio_id'] ); ?>" data-stx-audio-id>
					<button type="button" class="button" data-stx-pick="audio"><?php esc_html_e( 'Upload or choose', 'studiare-extensions' ); ?></button>
				</span>
			</div>
			<div class="stx-apb__field">
				<label for="<?php echo esc_attr( $id . '-duration' ); ?>"><?php esc_html_e( 'Duration', 'studiare-extensions' ); ?></label>
				<input type="text" class="stx-apb__short" id="<?php echo esc_attr( $id . '-duration' ); ?>" name="<?php echo esc_attr( $name . '[duration]' ); ?>" value="<?php echo esc_attr( $track['duration'] ? Podcast::clock( (int) $track['duration'] ) : '' ); ?>" dir="ltr" placeholder="12:34" inputmode="numeric" data-stx-duration>
			</div>
			<div class="stx-apb__field">
				<label for="<?php echo esc_attr( $id . '-preview' ); ?>"><?php esc_html_e( 'Free preview', 'studiare-extensions' ); ?></label>
				<select id="<?php echo esc_attr( $id . '-preview' ); ?>" name="<?php echo esc_attr( $name . '[preview]' ); ?>" data-stx-preview>
					<?php foreach ( $previews as $value => $text ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $track['preview'], $value ); ?>><?php echo esc_html( $text ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="stx-apb__field" data-stx-when="clip">
				<label for="<?php echo esc_attr( $id . '-clip' ); ?>"><?php esc_html_e( 'Preview length (seconds)', 'studiare-extensions' ); ?></label>
				<input type="number" class="stx-apb__short" id="<?php echo esc_attr( $id . '-clip' ); ?>" name="<?php echo esc_attr( $name . '[clip]' ); ?>" value="<?php echo esc_attr( (string) $track['clip'] ); ?>" min="<?php echo esc_attr( (string) Schema::MIN_CLIP ); ?>" max="<?php echo esc_attr( (string) Schema::MAX_CLIP ); ?>" step="5">
			</div>
			<div class="stx-apb__field" data-stx-when="file">
				<span class="stx-apb__label"><?php esc_html_e( 'Preview file', 'studiare-extensions' ); ?></span>
				<span class="stx-apb__file">
					<input type="hidden" name="<?php echo esc_attr( $name . '[preview_id]' ); ?>" value="<?php echo esc_attr( (string) $track['preview_id'] ); ?>" data-stx-preview-id>
					<span class="stx-apb__name" data-stx-preview-name><?php echo esc_html( '' !== $preview ? $preview : __( 'None chosen', 'studiare-extensions' ) ); ?></span>
					<button type="button" class="button" data-stx-pick="preview"><?php esc_html_e( 'Upload or choose', 'studiare-extensions' ); ?></button>
				</span>
			</div>
		</div>
		<button type="button" class="button-link button-link-delete stx-apb__remove" data-stx-remove><?php esc_html_e( 'Remove', 'studiare-extensions' ); ?></button>
	</li>
	<?php
};
?>
<div class="stx-apb" data-stx-audio-box>
	<p class="stx-apb__intro"><?php esc_html_e( 'Add the audio of this product. Buyers listen to it on the product page and in "My audio" of their account; other visitors hear the free preview you choose for each track. Files uploaded here go to WooCommerce\'s protected folder, so their address never reaches the page.', 'studiare-extensions' ); ?></p>

	<ol class="stx-apb__tracks" data-stx-tracks>
		<?php
		foreach ( $album['tracks'] as $index => $track ) {
			$row( $track, (string) $index );
		}
		?>
	</ol>
	<template data-stx-track-template><?php $row( $blank, '__i__' ); ?></template>

	<p class="stx-apb__add"><button type="button" class="button button-secondary" data-stx-add><?php esc_html_e( 'Add a track', 'studiare-extensions' ); ?></button></p>

	<p class="description"><?php esc_html_e( '"The first seconds" only works with MP3 files on this site: the server sends just that part of the file. For M4A files or links to another host, upload a short preview file instead.', 'studiare-extensions' ); ?></p>

	<div class="stx-apb__options">
		<label>
			<input type="checkbox" name="stx_audio[download]" value="1" <?php checked( $album['download'] ); ?>>
			<?php esc_html_e( 'Buyers may also download the files', 'studiare-extensions' ); ?>
		</label>
		<?php if ( $has_lessons || $album['lessons'] ) : ?>
			<label>
				<input type="checkbox" name="stx_audio[lessons]" value="1" <?php checked( $album['lessons'] ); ?>>
				<?php esc_html_e( 'Add the audio of this course\'s Studiare lessons to the player', 'studiare-extensions' ); ?>
			</label>
			<span class="description"><?php esc_html_e( 'Lessons open to everyone in Studiare play for everyone; all other lessons (including "logged-in users" lessons) play in full only for buyers of this product, with the free preview from the Studiare+ settings. Release dates set in Studiare are kept.', 'studiare-extensions' ); ?></span>
		<?php endif; ?>
	</div>
</div>
