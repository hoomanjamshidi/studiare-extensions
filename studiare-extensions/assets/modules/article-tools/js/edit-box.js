/**
 * Studiare Extensions — "article tools" box on the post edit screen.
 *
 * Adds the media library pickers for the podcast audio and cover, keeps the
 * audio preview in step with the link field, and fills in the duration of
 * media library files. The fields themselves are plain inputs, so a pasted
 * link saves without this script.
 */
( function () {
	'use strict';

	const box = document.querySelector( '[data-stx-article-box]' );
	if ( ! box || ! window.wp || ! window.wp.media ) {
		return;
	}

	const i18n = ( window.stxArticleEdit || {} ).i18n || {};
	const find = ( selector ) => box.querySelector( selector );

	const audio = {
		url: find( '[data-stx-audio-url]' ),
		id: find( '[data-stx-audio-id]' ),
		preview: find( '[data-stx-audio-preview]' ),
		duration: find( '[data-stx-audio-duration]' ),
		clear: find( '[data-stx-clear="audio"]' ),
	};

	const cover = {
		id: find( '[data-stx-cover-id]' ),
		preview: find( '[data-stx-cover-preview]' ),
		clear: find( '[data-stx-clear="cover"]' ),
	};

	if ( ! audio.url || ! cover.id ) {
		return;
	}

	const frames = {};

	function frame( kind ) {
		if ( ! frames[ kind ] ) {
			const isAudio = kind === 'audio';
			frames[ kind ] = window.wp.media( {
				title: isAudio ? i18n.audioTitle : i18n.coverTitle,
				button: { text: isAudio ? i18n.choose : i18n.chooseImage },
				library: { type: isAudio ? 'audio' : 'image' },
				multiple: false,
			} );
			frames[ kind ].on( 'select', () => {
				const file = frames[ kind ].state().get( 'selection' ).first().toJSON();
				if ( isAudio ) {
					setAudio( file );
				} else {
					setCover( file );
				}
			} );
		}

		return frames[ kind ];
	}

	function refreshAudio() {
		const url = audio.url.value.trim();
		audio.preview.hidden = url === '';
		audio.clear.hidden = url === '';
		if ( url !== '' && audio.preview.getAttribute( 'src' ) !== url ) {
			audio.preview.setAttribute( 'src', url );
		}
	}

	function setAudio( file ) {
		audio.url.value = file.url;
		audio.id.value = String( file.id );
		// WordPress reads the length of uploaded audio ("3:25"); keep a duration the author typed.
		if ( audio.duration && audio.duration.value.trim() === '' && file.fileLength ) {
			audio.duration.value = file.fileLength;
		}
		refreshAudio();
	}

	function setCover( file ) {
		const size = file.sizes && ( file.sizes.thumbnail || file.sizes.medium || file.sizes.full );
		const img = document.createElement( 'img' );
		img.src = size ? size.url : file.url;
		img.alt = '';
		img.className = 'stx-atb__cover-img';

		cover.id.value = String( file.id );
		cover.preview.replaceChildren( img );
		cover.preview.hidden = false;
		cover.clear.hidden = false;
	}

	box.querySelectorAll( '[data-stx-pick]' ).forEach( ( button ) => {
		button.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			frame( button.dataset.stxPick ).open();
		} );
	} );

	// A typed or pasted link is no longer the media library file.
	audio.url.addEventListener( 'input', () => {
		audio.id.value = '0';
	} );
	audio.url.addEventListener( 'change', refreshAudio );

	audio.clear.addEventListener( 'click', () => {
		audio.url.value = '';
		audio.id.value = '0';
		audio.preview.pause();
		audio.preview.removeAttribute( 'src' );
		refreshAudio();
	} );

	cover.clear.addEventListener( 'click', () => {
		cover.id.value = '0';
		cover.preview.replaceChildren();
		cover.preview.hidden = true;
		cover.clear.hidden = true;
	} );
}() );
