/**
 * Studiare Extensions — article tools on blog posts.
 *
 * "Save as PDF": marks the post content and the elements around it, so the
 * print stylesheet keeps only the article on paper (for the button and for
 * the browser's own Print command alike), then opens the print window, where
 * "Save as PDF" is one of the printers. Browsers lay out Persian text and
 * the site's fonts themselves there, which no in-page PDF library matches.
 *
 * Podcast: the player markup is printed by PHP with its controls visible, so
 * nothing moves when this runs; this creates the audio element on the first
 * tap (nothing is downloaded before), wires the controls, remembers where the
 * visitor stopped and the chosen speed, and feeds the phone's lock screen
 * controls (Media Session). Progress only changes a clip-path. Playing
 * announces `stx:podcast-play`, so a Podcast list widget on the page stops,
 * and the player stops when a list starts.
 */
( function () {
	'use strict';

	const config = window.stxArticleTools || {};
	const i18n = config.i18n || {};
	const doc = document;

	const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
	const SPEEDS = [ 1, 1.25, 1.5, 1.75, 2, 0.75 ];
	const SPEED_KEY = 'stxPodcastSpeed';
	const SAVE_EVERY = 5;
	const PRINT_WAIT = 4000;

	const digits = ( text ) => ( config.persian ? String( text ).replace( /\d/g, ( digit ) => PERSIAN_DIGITS[ digit ] ) : String( text ) );

	/** sprintf for "%s" and "%1$s" placeholders. */
	function format( template, values ) {
		let next = 0;
		return String( template || '' ).replace( /%(?:(\d+)\$)?s/g, ( match, position ) => values[ position ? position - 1 : next++ ] );
	}

	/** localStorage that never throws (private windows, blocked storage). */
	const store = {
		get( key ) {
			try {
				return window.localStorage.getItem( key );
			} catch ( error ) {
				return null;
			}
		},
		set( key, value ) {
			try {
				if ( value === null ) {
					window.localStorage.removeItem( key );
				} else {
					window.localStorage.setItem( key, value );
				}
			} catch ( error ) {
				// Nothing to remember then.
			}
		},
	};

	/* ---------------------------------------------------------------------
	 * Save as PDF
	 * ------------------------------------------------------------------- */

	/** Loads pictures that lazy loading (native or a plugin's) has held back. */
	function revealImages( scope ) {
		scope.querySelectorAll( 'img' ).forEach( ( img ) => {
			const src = img.dataset.lazySrc || img.dataset.src;
			const srcset = img.dataset.lazySrcset || img.dataset.srcset;
			const current = img.getAttribute( 'src' ) || '';

			if ( src && ( current === '' || current.indexOf( 'data:' ) === 0 ) ) {
				img.src = src;
			}
			if ( srcset && ! img.getAttribute( 'srcset' ) ) {
				img.srcset = srcset;
			}
			if ( img.loading === 'lazy' ) {
				img.loading = 'eager';
			}
		} );
	}

	/** Resolves once pictures and fonts are in, or after a few seconds at most. */
	function readyToPrint( scope ) {
		revealImages( scope );

		const waits = Array.from( scope.querySelectorAll( 'img' ) )
			.filter( ( img ) => ! img.complete )
			.map( ( img ) => new Promise( ( resolve ) => {
				img.addEventListener( 'load', resolve, { once: true } );
				img.addEventListener( 'error', resolve, { once: true } );
			} ) );

		if ( doc.fonts && doc.fonts.ready ) {
			waits.push( doc.fonts.ready );
		}

		return Promise.race( [
			Promise.all( waits ),
			new Promise( ( resolve ) => window.setTimeout( resolve, PRINT_WAIT ) ),
		] );
	}

	function initPrint() {
		const head = doc.querySelector( '[data-stx-print-root]' );
		const content = head && head.parentElement;
		if ( ! content ) {
			return;
		}

		// The marks only matter to the print stylesheet, so they stay on.
		content.classList.add( 'stx-print-content' );
		for ( let node = content.parentElement; node && node !== doc.body; node = node.parentElement ) {
			node.classList.add( 'stx-print-path' );
		}
		doc.documentElement.classList.add( 'stx-printing' );

		// The page title becomes the PDF's file name, so it is the article's own title while printing.
		let pageTitle = null;
		window.addEventListener( 'beforeprint', () => {
			revealImages( content );
			if ( pageTitle === null && head.dataset.title ) {
				pageTitle = doc.title;
				doc.title = head.dataset.title;
			}
		} );
		window.addEventListener( 'afterprint', () => {
			if ( pageTitle !== null ) {
				doc.title = pageTitle;
				pageTitle = null;
			}
		} );

		doc.querySelectorAll( '[data-stx-pdf]' ).forEach( ( button ) => {
			const label = button.querySelector( 'span' );
			const text = label ? label.textContent : '';

			button.addEventListener( 'click', () => {
				if ( button.getAttribute( 'aria-busy' ) === 'true' ) {
					return;
				}

				button.setAttribute( 'aria-busy', 'true' );
				if ( label && i18n.preparing ) {
					label.textContent = i18n.preparing;
				}

				readyToPrint( content ).then( () => {
					button.removeAttribute( 'aria-busy' );
					if ( label ) {
						label.textContent = text;
					}
					window.print();
				} );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Podcast player
	 * ------------------------------------------------------------------- */

	const players = [];

	function clock( seconds ) {
		const total = Math.max( 0, Math.floor( seconds || 0 ) );
		const hours = Math.floor( total / 3600 );
		const minutes = Math.floor( ( total % 3600 ) / 60 );
		const pad = ( value ) => String( value ).padStart( 2, '0' );
		const rest = pad( total % 60 );

		return digits( hours ? hours + ':' + pad( minutes ) + ':' + rest : minutes + ':' + rest );
	}

	function speedLabel( rate ) {
		return digits( String( rate ).replace( '.', config.persian ? '٫' : '.' ) ) + '×';
	}

	function initPlayer( root ) {
		const ui = {
			play: root.querySelector( '[data-pod-play]' ),
			seek: root.querySelector( '[data-pod-seek]' ),
			current: root.querySelector( '[data-pod-current]' ),
			total: root.querySelector( '[data-pod-total]' ),
			speed: root.querySelector( '[data-pod-speed]' ),
			error: root.querySelector( '[data-pod-error]' ),
		};
		if ( ! ui.play || ! ui.seek || ! root.dataset.audio ) {
			return;
		}

		const skipButtons = Array.from( root.querySelectorAll( '[data-pod-skip]' ) );
		const jumps = skipButtons.map( ( button ) => parseFloat( button.dataset.podSkip ) || 0 );
		const resumeKey = 'stxPodcast:' + root.dataset.id;
		const resume = root.dataset.resume === '1';
		let audio = null;
		let duration = parseFloat( root.dataset.duration ) || 0;
		let position = resume ? parseFloat( store.get( resumeKey ) ) || 0 : 0;
		let lastSaved = position;
		let dragging = false;
		let rate = parseFloat( store.get( SPEED_KEY ) ) || 1;

		if ( SPEEDS.indexOf( rate ) === -1 ) {
			rate = 1;
		}

		function show( time ) {
			const ratio = duration ? Math.min( 1, time / duration ) : 0;
			root.style.setProperty( '--stx-pod-p', ( ratio * 100 ).toFixed( 2 ) + '%' );
			ui.current.textContent = clock( time );
			if ( ! dragging ) {
				ui.seek.value = String( Math.floor( time ) );
			}
			ui.seek.setAttribute( 'aria-valuetext', format( i18n.position, [ clock( time ), duration ? clock( duration ) : '--:--' ] ) );
		}

		function setDuration( value ) {
			if ( ! value || ! isFinite( value ) ) {
				return;
			}
			duration = value;
			ui.seek.max = String( Math.floor( value ) );
			ui.total.textContent = clock( value );
			show( audio ? audio.currentTime : position );
		}

		/** A visit that stopped near the end starts the episode over. */
		function freshStart() {
			if ( duration && position > duration - 10 ) {
				position = 0;
			}
		}

		function save( time, force ) {
			if ( ! resume || ( ! force && Math.abs( time - lastSaved ) < SAVE_EVERY ) ) {
				return;
			}
			lastSaved = time;
			store.set( resumeKey, time > 1 ? String( Math.floor( time ) ) : null );
		}

		function setState( name, on ) {
			root.classList.toggle( 'is-' + name, on );
		}

		function failed() {
			setState( 'loading', false );
			setState( 'playing', false );
			setState( 'error', true );
			if ( ui.error ) {
				ui.error.hidden = false;
			}
		}

		function showSpeed() {
			if ( ui.speed ) {
				ui.speed.textContent = speedLabel( rate );
				ui.speed.setAttribute( 'aria-label', format( i18n.speed, [ speedLabel( rate ) ] ) );
			}
		}

		function mediaSession() {
			if ( ! ( 'mediaSession' in navigator ) || typeof window.MediaMetadata !== 'function' ) {
				return;
			}

			navigator.mediaSession.metadata = new window.MediaMetadata( {
				title: root.dataset.title || '',
				artist: root.dataset.artist || '',
				artwork: root.dataset.artwork ? [ { src: root.dataset.artwork } ] : [],
			} );

			const actions = {
				play: () => audio.play(),
				pause: () => audio.pause(),
				seekbackward: () => skip( Math.min( ...jumps ) ),
				seekforward: () => skip( Math.max( ...jumps ) ),
				seekto: ( details ) => seekTo( details.seekTime ),
			};
			Object.keys( actions ).forEach( ( action ) => {
				try {
					navigator.mediaSession.setActionHandler( action, actions[ action ] );
				} catch ( error ) {
					// An action this browser does not know.
				}
			} );
		}

		/** Creates the audio on the first use, so visitors who never press play download nothing. */
		function media() {
			if ( audio ) {
				return audio;
			}

			audio = new window.Audio();
			audio.preload = 'auto';
			audio.src = root.dataset.audio;
			audio.playbackRate = rate;
			if ( position ) {
				audio.currentTime = position;
			}

			audio.addEventListener( 'loadedmetadata', () => {
				const saved = position;
				setDuration( audio.duration );
				freshStart();
				// Some browsers drop a start time set before the file was read.
				if ( saved !== position || ( position && audio.currentTime < 1 ) ) {
					audio.currentTime = position;
				}
			} );
			audio.addEventListener( 'durationchange', () => setDuration( audio.duration ) );
			audio.addEventListener( 'timeupdate', () => {
				if ( ! dragging ) {
					show( audio.currentTime );
				}
				save( audio.currentTime, false );
			} );
			audio.addEventListener( 'play', () => {
				setState( 'playing', true );
				setState( 'error', false );
				if ( ui.error ) {
					ui.error.hidden = true;
				}
				ui.play.setAttribute( 'aria-label', i18n.pause || '' );
				players.forEach( ( player ) => player !== controller && player.pause() );
				doc.dispatchEvent( new window.CustomEvent( 'stx:podcast-play', { detail: { source: 'article' } } ) );
				mediaSession();
			} );
			audio.addEventListener( 'pause', () => {
				setState( 'playing', false );
				ui.play.setAttribute( 'aria-label', i18n.play || '' );
				save( audio.currentTime, true );
			} );
			audio.addEventListener( 'waiting', () => setState( 'loading', true ) );
			audio.addEventListener( 'playing', () => setState( 'loading', false ) );
			audio.addEventListener( 'canplay', () => setState( 'loading', false ) );
			audio.addEventListener( 'ended', () => {
				position = 0;
				save( 0, true );
				show( duration );
			} );
			audio.addEventListener( 'error', failed );

			return audio;
		}

		function seekTo( time ) {
			const target = Math.max( 0, duration ? Math.min( duration, time ) : time );
			if ( audio ) {
				audio.currentTime = target;
			} else {
				position = target;
			}
			show( target );
			save( target, true );
		}

		function skip( seconds ) {
			seekTo( ( audio ? audio.currentTime : position ) + seconds );
		}

		const controller = {
			pause: () => audio && audio.pause(),
		};
		players.push( controller );

		ui.play.addEventListener( 'click', () => {
			const player = media();
			if ( ! player.paused ) {
				player.pause();
				return;
			}

			if ( player.readyState < 3 ) {
				setState( 'loading', true );
			}
			const started = player.play();
			if ( started && started.catch ) {
				started.catch( ( error ) => {
					setState( 'loading', false );
					// Pausing before playback starts rejects with AbortError; that is no failure.
					if ( error && error.name !== 'AbortError' && error.name !== 'NotAllowedError' ) {
						failed();
					}
				} );
			}
		} );

		ui.seek.addEventListener( 'input', () => {
			dragging = true;
			show( parseFloat( ui.seek.value ) || 0 );
		} );
		ui.seek.addEventListener( 'change', () => {
			dragging = false;
			seekTo( parseFloat( ui.seek.value ) || 0 );
		} );

		skipButtons.forEach( ( button, index ) => {
			button.addEventListener( 'click', () => skip( jumps[ index ] ) );
		} );

		if ( ui.speed ) {
			ui.speed.addEventListener( 'click', () => {
				rate = SPEEDS[ ( SPEEDS.indexOf( rate ) + 1 ) % SPEEDS.length ];
				if ( audio ) {
					audio.playbackRate = rate;
				}
				store.set( SPEED_KEY, rate === 1 ? null : String( rate ) );
				showSpeed();
			} );
		}

		window.addEventListener( 'pagehide', () => audio && save( audio.currentTime, true ) );

		freshStart();
		showSpeed();
		show( position );
	}

	// A Podcast list widget started playing: the post players stop.
	doc.addEventListener( 'stx:podcast-play', ( event ) => {
		if ( event.detail && event.detail.source !== 'article' ) {
			players.forEach( ( player ) => player.pause() );
		}
	} );

	initPrint();
	doc.querySelectorAll( '[data-stx-pod]' ).forEach( initPlayer );
}() );
