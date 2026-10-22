/**
 * Studiare Extensions — article tools settings panel.
 *
 * Fields bind to the store through admin.js (`data-stx-bind`); this keeps
 * the phone preview (the real podcast.php / pdf.php markup with the
 * front-end stylesheet) in step with them.
 *
 * Contract: ink() mirrors Core\Color::ink(). Change them together.
 */
( function () {
	'use strict';

	const STX = window.STX;
	const root = document.querySelector( '[data-stx-module="article_tools"]' );
	if ( ! STX || ! STX.store || ! root ) {
		return;
	}

	const store = STX.store;
	const moduleData = STX.data.moduleData || {};
	const i18n = moduleData.i18n || {};
	const screen = root.querySelector( '[data-stx-preview-screen]' );
	const part = ( name ) => root.querySelector( '[data-stx-preview-part="' + name + '"]' );
	const podcast = root.querySelector( '.stx-pod' );

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
		const pdf = settings.pdf;
		const pod = settings.podcast;
		const accent = settings.colors.accent;

		part( 'bar' ).hidden = ! pdf.enabled || pdf.position === 'bottom';
		part( 'cta' ).hidden = ! pdf.enabled || pdf.position === 'top';
		part( 'podcast' ).hidden = ! pod.enabled;

		root.querySelectorAll( '[data-stx-pdf] > span' ).forEach( ( label ) => {
			label.textContent = pdf.label || i18n.pdfLabel || '';
		} );

		if ( podcast ) {
			podcast.classList.toggle( 'stx-pod--soft', pod.look !== 'studio' );
			podcast.classList.toggle( 'stx-pod--studio', pod.look === 'studio' );
			podcast.querySelector( '.stx-pod__eyebrow > span' ).textContent = pod.label || i18n.podcastLabel || '';
			// Their display is set by the stylesheet, which the `hidden` attribute cannot beat.
			podcast.querySelectorAll( '.stx-pod__download' ).forEach( ( node ) => {
				node.style.display = pod.download ? '' : 'none';
			} );
			podcast.querySelectorAll( '.stx-pod__speed' ).forEach( ( node ) => {
				node.style.display = pod.speed ? '' : 'none';
			} );
		}

		root.querySelectorAll( '.stx-pod, .stx-at-bar, .stx-at-cta' ).forEach( ( node ) => {
			if ( accent ) {
				node.style.setProperty( '--stx-at-accent', accent );
			} else {
				node.style.removeProperty( '--stx-at-accent' );
			}
			node.style.setProperty( '--stx-at-on-accent', ink( accent || moduleData.themeAccent ) );
		} );
	}

	root.querySelectorAll( '[data-stx-preview-scheme]' ).forEach( ( input ) => {
		input.addEventListener( 'change', () => screen.classList.toggle( 'scdarkcolors', input.value === 'dark' ) );
	} );

	store.subscribe( render );
	render();
}() );
