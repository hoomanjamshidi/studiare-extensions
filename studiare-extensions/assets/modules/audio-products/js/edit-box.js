/**
 * Studiare Extensions — "Studiare+ audio" box on the product edit screen.
 *
 * Adds, removes and reorders tracks (rows are copied from the
 * `[data-stx-track-template]`, with a fresh index in their field names),
 * shows the preview fields that fit the chosen preview, and opens the media
 * library. Uploads use WooCommerce's `downloadable_product` type, so the
 * files land in its protected folder like WooCommerce's own downloads.
 */
( function () {
	'use strict';

	const box = document.querySelector( '[data-stx-audio-box]' );
	if ( ! box ) {
		return;
	}

	const config = window.stxAudioEdit || {};
	const i18n = config.i18n || {};
	const list = box.querySelector( '[data-stx-tracks]' );
	const template = box.querySelector( '[data-stx-track-template]' );
	let next = list.children.length;

	function renumber() {
		Array.from( list.children ).forEach( ( row, index ) => {
			row.querySelector( '[data-stx-number]' ).textContent = String( index + 1 );
		} );
	}

	function showPreviewFields( row ) {
		const preview = row.querySelector( '[data-stx-preview]' ).value;
		row.querySelectorAll( '[data-stx-when]' ).forEach( ( field ) => {
			field.hidden = field.dataset.stxWhen !== preview;
		} );
	}

	function addRow() {
		const html = template.innerHTML.replace( /__i__/g, 'n' + next++ );
		list.insertAdjacentHTML( 'beforeend', html );
		const row = list.lastElementChild;
		showPreviewFields( row );
		renumber();
		row.querySelector( '[data-stx-title]' ).focus();
	}

	function pick( row, kind ) {
		const isAudio = kind === 'audio';
		const frame = window.wp.media( {
			title: isAudio ? i18n.audioTitle : i18n.previewTitle,
			button: { text: i18n.choose },
			library: { type: 'audio' },
			multiple: false,
		} );

		if ( config.protect ) {
			frame.on( 'ready', () => {
				if ( frame.uploader && frame.uploader.options.uploader ) {
					frame.uploader.options.uploader.params = Object.assign( {}, frame.uploader.options.uploader.params, { type: 'downloadable_product' } );
				}
			} );
		}

		frame.on( 'select', () => {
			const file = frame.state().get( 'selection' ).first().toJSON();
			if ( isAudio ) {
				row.querySelector( '[data-stx-url]' ).value = file.url;
				row.querySelector( '[data-stx-audio-id]' ).value = String( file.id );
				const title = row.querySelector( '[data-stx-title]' );
				if ( title.value.trim() === '' ) {
					title.value = file.title || '';
				}
				// WordPress reads the length of uploaded audio ("3:25"); keep a duration the admin typed.
				const duration = row.querySelector( '[data-stx-duration]' );
				if ( duration.value.trim() === '' && file.fileLength ) {
					duration.value = file.fileLength;
				}
			} else {
				row.querySelector( '[data-stx-preview-id]' ).value = String( file.id );
				row.querySelector( '[data-stx-preview-name]' ).textContent = file.filename || file.title || '';
			}
		} );

		frame.open();
	}

	box.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( 'button' );
		const row = button && button.closest( '[data-stx-track]' );

		if ( button && button.hasAttribute( 'data-stx-add' ) ) {
			addRow();
			return;
		}
		if ( ! row ) {
			return;
		}

		if ( button.hasAttribute( 'data-stx-pick' ) && window.wp && window.wp.media ) {
			pick( row, button.dataset.stxPick );
		} else if ( button.hasAttribute( 'data-stx-remove' ) ) {
			const title = row.querySelector( '[data-stx-title]' ).value.trim();
			// eslint-disable-next-line no-alert
			if ( title === '' || window.confirm( i18n.remove ) ) {
				row.remove();
				renumber();
			}
		} else if ( button.hasAttribute( 'data-stx-move' ) ) {
			const sibling = button.dataset.stxMove === '-1' ? row.previousElementSibling : row.nextElementSibling;
			if ( sibling ) {
				list.insertBefore( row, button.dataset.stxMove === '-1' ? sibling : sibling.nextElementSibling );
				renumber();
				button.focus();
			}
		}
	} );

	box.addEventListener( 'change', ( event ) => {
		const row = event.target.closest( '[data-stx-track]' );
		if ( row && event.target.matches( '[data-stx-preview]' ) ) {
			showPreviewFields( row );
		}
	} );

	// A typed or pasted link is no longer the media library file.
	box.addEventListener( 'input', ( event ) => {
		if ( event.target.matches( '[data-stx-url]' ) ) {
			event.target.closest( '[data-stx-track]' ).querySelector( '[data-stx-audio-id]' ).value = '0';
		}
	} );

	Array.from( list.children ).forEach( showPreviewFields );
	renumber();
}() );
