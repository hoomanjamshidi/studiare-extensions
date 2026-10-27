/**
 * Studiare Extensions — audio products settings panel.
 *
 * Fields bind to the store through admin.js (`data-stx-bind`); this keeps
 * the phone preview (the real player.php markup with the front-end
 * stylesheet) in step with them.
 *
 * Contract: ink() mirrors Core\Color::ink(). Change them together.
 */
( function () {
	'use strict';

	const STX = window.STX;
	const root = document.querySelector( '[data-stx-module="audio_products"]' );
	if ( ! STX || ! STX.store || ! root ) {
		return;
	}

	const store = STX.store;
	const moduleData = STX.data.moduleData || {};
	const i18n = moduleData.i18n || {};
	const screen = root.querySelector( '[data-stx-preview-screen]' );
	const player = root.querySelector( '.stx-ap' );

	/** White or near-black text for a solid background, whichever contrasts more. */
	function ink( color ) {
		const match = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.exec( color || '' );
		if ( ! match ) {
			return '#ffffff';
		}

		const hex = match[ 1 ].length === 3 ? match[ 1 ].replace( /./g, '$&$&' ) : match[ 1 ];
		const channel = ( offset ) => {
			const value = parseInt( hex.substr( offset, 2 ), 16 ) / 255;
			return value <= 0.03928 ? value / 12.92 : Math.pow( ( value + 0.055 ) / 1.055, 2.4 );
		};
		const luminance = 0.2126 * channel( 0 ) + 0.7152 * channel( 2 ) + 0.0722 * channel( 4 );

		return 1.05 / ( luminance + 0.05 ) >= ( luminance + 0.05 ) / 0.0556 ? '#ffffff' : '#111111';
	}

	function render() {
		const settings = store.get();
		const options = settings.player;
		const accent = settings.colors.accent;

		player.classList.toggle( 'stx-ap--soft', options.look !== 'studio' );
		player.classList.toggle( 'stx-ap--studio', options.look === 'studio' );
		player.querySelector( '.stx-ap__eyebrow > span' ).textContent = options.label || i18n.label || '';

		// Their display is set by the stylesheet, which the `hidden` attribute cannot beat.
		player.querySelectorAll( '.stx-ap__speed' ).forEach( ( node ) => {
			node.style.display = options.speed ? '' : 'none';
		} );
		player.querySelectorAll( '.stx-ap__buy' ).forEach( ( node ) => {
			node.style.display = settings.preview.cta ? '' : 'none';
		} );
		const foot = player.querySelector( '.stx-ap__foot' );
		if ( foot ) {
			foot.style.display = settings.preview.cta ? '' : 'none';
		}

		if ( accent ) {
			player.style.setProperty( '--stx-ap-accent', accent );
		} else {
			player.style.removeProperty( '--stx-ap-accent' );
		}
		player.style.setProperty( '--stx-ap-on-accent', ink( accent || moduleData.themeAccent ) );
	}

	root.querySelectorAll( '[data-stx-preview-scheme]' ).forEach( ( input ) => {
		input.addEventListener( 'change', () => screen.classList.toggle( 'scdarkcolors', input.value === 'dark' ) );
	} );

	if ( player ) {
		store.subscribe( render );
		render();
	}
}() );
