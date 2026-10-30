/**
 * Studiare Extensions — player of audio products.
 *
 * Every player on the page shares one audio element, created on the first
 * tap (nothing is downloaded before), so only one track plays at a time.
 * The deck shows the chosen track with seeking, 15/30 second jumps,
 * previous/next and speed; the list plays a track in place. A preview
 * stops at its limit (the server sends no more than that anyway) and shows
 * the buy prompt. Full tracks continue where the listener stopped
 * (`stxAudio:<product>:<track>`), and the speed is shared with the podcast
 * players (`stxPodcastSpeed`), which pause this one and are paused by it
 * through the `stx:podcast-play` event. Progress only changes a transform.
 */
( function () {
	'use strict';

	/**
	 * Options for tap, click and key listeners. WP Rocket's "Delay JavaScript"
	 * (3.17+) holds such listeners back, also in scripts it does not delay like
	 * this one, until the scripts it delays have loaded, and then replays the
	 * first tap as an untrusted copy (iOS will not start audio from one). Its
	 * own `isRocket` option lets a listener through; browsers ignore it.
	 */
	const UNDELAYED = { isRocket: true };

	const config = window.stxAudioProducts || {};
	const i18n = config.i18n || {};
	const doc = document;

	const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
	const SPEEDS = [ 1, 1.25, 1.5, 1.75, 2, 0.75 ];
	const SPEED_KEY = 'stxPodcastSpeed';
	const SAVE_EVERY = 5;
	const SOURCE = 'audio-product';

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

	function clock( seconds ) {
		const total = Math.max( 0, Math.floor( seconds || 0 ) );
		const hours = Math.floor( total / 3600 );
		const minutes = Math.floor( ( total % 3600 ) / 60 );
		const pad = ( value ) => String( value ).padStart( 2, '0' );
		const rest = pad( total % 60 );

		return digits( hours ? hours + ':' + pad( minutes ) + ':' + rest : minutes + ':' + rest );
	}

	function speedLabel( value ) {
		return digits( String( value ).replace( '.', config.persian ? '٫' : '.' ) ) + '×';
	}

	let rate = parseFloat( store.get( SPEED_KEY ) ) || 1;
	if ( SPEEDS.indexOf( rate ) === -1 ) {
		rate = 1;
	}

	/* ---------------------------------------------------------------------
	 * Tracks
	 * ------------------------------------------------------------------- */

	const limitOf = ( track ) => parseFloat( track.dataset.limit ) || 0;
	const durationOf = ( track ) => parseFloat( track.dataset.duration ) || 0;
	/** Seconds the visitor may hear: the preview limit, else the whole track. */
	const spanOf = ( track ) => limitOf( track ) || durationOf( track );
	const playerOf = ( track ) => track.closest( '[data-stx-ap]' ).stxPlayer;
	const remembers = ( track ) => track.dataset.state === 'full' && track.closest( '[data-stx-ap]' ).dataset.resume === '1';
	const keyOf = ( track ) => 'stxAudio:' + track.closest( '[data-stx-ap]' ).dataset.id + ':' + track.dataset.key;

	/** Saved position of a full track; a visit that stopped near the end starts over. */
	function savedTime( track ) {
		if ( ! remembers( track ) ) {
			return 0;
		}
		const time = parseFloat( store.get( keyOf( track ) ) ) || 0;
		const duration = durationOf( track );

		return duration && time > duration - 10 ? 0 : time;
	}

	let audio = null;
	let current = null;
	let lastSaved = 0;
	let startAt = 0;

	function save( force ) {
		if ( ! audio || ! current || ! remembers( current ) ) {
			return;
		}
		const time = audio.currentTime;
		if ( ! force && Math.abs( time - lastSaved ) < SAVE_EVERY ) {
			return;
		}
		lastSaved = time;
		store.set( keyOf( current ), time > 1 ? String( Math.floor( time ) ) : null );
	}

	function setState( track, name, on ) {
		track.classList.toggle( 'is-' + name, on );
		const player = playerOf( track );
		if ( player.track === track ) {
			player.root.classList.toggle( 'is-' + name, on );
		}
		if ( name === 'playing' ) {
			const label = format( on ? i18n.pause : i18n.play, [ track.dataset.title ] );
			const row = track.querySelector( '[data-ap-play]' );
			if ( row ) {
				row.setAttribute( 'aria-label', label );
			}
			if ( player.track === track && player.ui.main ) {
				player.ui.main.setAttribute( 'aria-label', label );
			}
		}
	}

	function failed( track ) {
		setState( track, 'loading', false );
		setState( track, 'playing', false );
		const player = playerOf( track );
		if ( player.track === track ) {
			player.ui.error.hidden = false;
		}
	}

	/** The preview reached its end: stop there and offer the full version. */
	function previewEnded( track ) {
		audio.pause();
		const player = playerOf( track );
		if ( player.track === track ) {
			player.showTime( spanOf( track ) );
			player.ui.gate.hidden = false;
		}
	}

	function mediaSession( track ) {
		if ( ! ( 'mediaSession' in navigator ) || typeof window.MediaMetadata !== 'function' ) {
			return;
		}

		const root = track.closest( '[data-stx-ap]' );
		const player = playerOf( track );
		navigator.mediaSession.metadata = new window.MediaMetadata( {
			title: track.dataset.title || '',
			artist: root.dataset.artist || '',
			artwork: root.dataset.artwork ? [ { src: root.dataset.artwork } ] : [],
		} );

		const actions = {
			play: () => play( current ),
			pause: () => audio.pause(),
			seekbackward: () => seekTo( current, audio.currentTime - 15 ),
			seekforward: () => seekTo( current, audio.currentTime + 30 ),
			seekto: ( details ) => seekTo( current, details.seekTime ),
			previoustrack: () => player.step( -1 ),
			nexttrack: () => player.step( 1 ),
		};
		Object.keys( actions ).forEach( ( action ) => {
			try {
				navigator.mediaSession.setActionHandler( action, actions[ action ] );
			} catch ( error ) {
				// An action this browser does not know.
			}
		} );
	}

	/** The shared audio element, created on the first play. */
	function media() {
		if ( audio ) {
			return audio;
		}

		audio = new window.Audio();
		audio.preload = 'auto';

		audio.addEventListener( 'loadedmetadata', () => {
			// Some browsers drop a start time set before the file was read.
			if ( startAt && audio.currentTime < 1 ) {
				audio.currentTime = startAt;
			}
			startAt = 0;
			// A track whose length the admin did not type learns it from the file
			// (a cut preview reports its own, shorter length, so it is left alone).
			if ( ! durationOf( current ) && ! limitOf( current ) && isFinite( audio.duration ) ) {
				current.dataset.duration = String( Math.floor( audio.duration ) );
				playerOf( current ).showTrack( current );
			}
		} );
		audio.addEventListener( 'timeupdate', () => {
			const limit = limitOf( current );
			if ( limit && audio.currentTime >= limit ) {
				previewEnded( current );
				return;
			}
			const player = playerOf( current );
			if ( player.track === current ) {
				player.showTime( audio.currentTime );
			}
			save( false );
		} );
		audio.addEventListener( 'play', () => {
			setState( current, 'playing', true );
			const player = playerOf( current );
			player.ui.error.hidden = true;
			if ( player.ui.gate ) {
				player.ui.gate.hidden = true;
			}
			mediaSession( current );
			doc.dispatchEvent( new window.CustomEvent( 'stx:podcast-play', { detail: { source: SOURCE } } ) );
		} );
		audio.addEventListener( 'pause', () => {
			setState( current, 'playing', false );
			save( true );
		} );
		audio.addEventListener( 'waiting', () => setState( current, 'loading', true ) );
		audio.addEventListener( 'playing', () => setState( current, 'loading', false ) );
		audio.addEventListener( 'canplay', () => setState( current, 'loading', false ) );
		audio.addEventListener( 'ended', () => {
			const track = current;
			if ( track.dataset.state === 'preview' ) {
				previewEnded( track );
				return;
			}
			store.set( keyOf( track ), null );
			lastSaved = 0;
			playerOf( track ).step( 1, true );
		} );
		audio.addEventListener( 'error', () => current && failed( current ) );

		return audio;
	}

	/** Puts a track in the audio element, from where the visitor stopped. */
	function load( track ) {
		if ( current ) {
			save( true );
			setState( current, 'playing', false );
			setState( current, 'loading', false );
		}

		const player = media();
		current = track;
		startAt = savedTime( track );
		lastSaved = startAt;
		player.src = track.dataset.src;
		player.playbackRate = rate;
		if ( startAt ) {
			player.currentTime = startAt;
		}
	}

	function play( track ) {
		if ( current !== track ) {
			load( track );
		}
		// A finished preview starts again from the beginning.
		const limit = limitOf( track );
		if ( limit && audio.currentTime >= limit - 0.5 ) {
			audio.currentTime = 0;
		}
		if ( audio.readyState < 3 ) {
			setState( track, 'loading', true );
		}

		const started = audio.play();
		if ( started && started.catch ) {
			started.catch( ( error ) => {
				setState( track, 'loading', false );
				// Pausing before playback starts rejects with AbortError; that is no failure.
				if ( error && error.name !== 'AbortError' && error.name !== 'NotAllowedError' ) {
					failed( track );
				}
			} );
		}
	}

	function toggle( track ) {
		if ( current === track && audio && ! audio.paused ) {
			audio.pause();
			return;
		}
		play( track );
	}

	/** Moves the position, never past what the visitor may hear. */
	function seekTo( track, time ) {
		const span = spanOf( track );
		const target = Math.max( 0, span ? Math.min( span - ( limitOf( track ) ? 0.5 : 0 ), time ) : time );

		if ( current === track && audio ) {
			audio.currentTime = target;
		} else if ( remembers( track ) ) {
			store.set( keyOf( track ), target > 1 ? String( Math.floor( target ) ) : null );
		}
		playerOf( track ).showTime( target );
	}

	// A podcast player started: this one stops.
	doc.addEventListener( 'stx:podcast-play', ( event ) => {
		if ( event.detail && event.detail.source !== SOURCE && audio && ! audio.paused ) {
			audio.pause();
		}
	} );
	window.addEventListener( 'pagehide', () => save( true ) );

	/* ---------------------------------------------------------------------
	 * Player (deck + list)
	 * ------------------------------------------------------------------- */

	function initPlayer( root ) {
		const tracks = Array.from( root.querySelectorAll( '[data-ap-track]' ) ).filter( ( track ) => track.dataset.state !== 'locked' );
		const deck = root.querySelector( '[data-ap-deck]' );
		if ( ! tracks.length || ! deck ) {
			return;
		}

		const ui = {
			main: deck.querySelector( '[data-ap-main]' ),
			seek: deck.querySelector( '[data-ap-seek]' ),
			current: deck.querySelector( '[data-ap-current]' ),
			total: deck.querySelector( '[data-ap-total]' ),
			speed: deck.querySelector( '[data-ap-speed]' ),
			gate: deck.querySelector( '[data-ap-gate]' ),
			error: deck.querySelector( '[data-ap-error]' ),
			label: deck.querySelector( '[data-ap-now-label]' ),
			title: deck.querySelector( '[data-ap-now-title]' ),
		};
		const steps = Array.from( deck.querySelectorAll( '[data-ap-step]' ) );
		let dragging = false;

		const player = {
			root,
			ui,
			track: tracks[ 0 ],

			showTime( time ) {
				const span = spanOf( player.track );
				root.style.setProperty( '--stx-ap-p', span ? Math.min( 1, time / span ).toFixed( 4 ) : '0' );
				ui.current.textContent = clock( time );
				if ( ! dragging ) {
					ui.seek.value = String( Math.floor( time ) );
				}
				ui.seek.setAttribute( 'aria-valuetext', format( i18n.position, [ clock( time ), span ? clock( span ) : '--:--' ] ) );
			},

			/** Shows a track in the deck (without playing it). */
			showTrack( track ) {
				player.track = track;
				const span = spanOf( track );
				ui.title.textContent = track.dataset.title;
				ui.label.textContent = track.dataset.note || '';
				ui.seek.max = String( span );
				ui.total.textContent = span ? clock( span ) : '--:--';
				ui.error.hidden = true;
				if ( ui.gate ) {
					ui.gate.hidden = true;
				}

				const playing = current === track && audio && ! audio.paused;
				[ 'playing', 'loading' ].forEach( ( name ) => root.classList.toggle( 'is-' + name, track.classList.contains( 'is-' + name ) ) );
				ui.main.setAttribute( 'aria-label', format( playing ? i18n.pause : i18n.play, [ track.dataset.title ] ) );
				tracks.forEach( ( item ) => item.classList.toggle( 'is-current', item === track ) );
				const index = tracks.indexOf( track );
				steps.forEach( ( button ) => {
					button.disabled = ! tracks[ index + parseInt( button.dataset.apStep, 10 ) ];
				} );
				player.showTime( current === track && audio ? audio.currentTime : savedTime( track ) );
			},

			/** Previous or next track; it plays if one was playing, or when `autoplay`. */
			step( direction, autoplay ) {
				const next = tracks[ tracks.indexOf( player.track ) + direction ];
				if ( ! next ) {
					return;
				}
				const playing = autoplay || ( audio && ! audio.paused );
				if ( playing && audio ) {
					audio.pause();
				}
				player.showTrack( next );
				if ( playing ) {
					play( next );
				}
			},
		};

		root.stxPlayer = player;

		ui.main.addEventListener( 'click', () => toggle( player.track ), UNDELAYED );

		deck.querySelectorAll( '[data-ap-skip]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const jump = parseFloat( button.dataset.apSkip ) || 0;
				const from = current === player.track && audio ? audio.currentTime : savedTime( player.track );
				seekTo( player.track, from + jump );
			}, UNDELAYED );
		} );

		steps.forEach( ( button ) => {
			button.addEventListener( 'click', () => player.step( parseInt( button.dataset.apStep, 10 ) ), UNDELAYED );
		} );

		ui.seek.addEventListener( 'input', () => {
			dragging = true;
			player.showTime( parseFloat( ui.seek.value ) || 0 );
		}, UNDELAYED );
		ui.seek.addEventListener( 'change', () => {
			dragging = false;
			seekTo( player.track, parseFloat( ui.seek.value ) || 0 );
		} );

		if ( ui.speed ) {
			const showSpeed = () => {
				doc.querySelectorAll( '[data-ap-speed]' ).forEach( ( button ) => {
					button.textContent = speedLabel( rate );
					button.setAttribute( 'aria-label', format( i18n.speed, [ speedLabel( rate ) ] ) );
				} );
			};
			ui.speed.addEventListener( 'click', () => {
				rate = SPEEDS[ ( SPEEDS.indexOf( rate ) + 1 ) % SPEEDS.length ];
				if ( audio ) {
					audio.playbackRate = rate;
				}
				store.set( SPEED_KEY, rate === 1 ? null : String( rate ) );
				showSpeed();
			}, UNDELAYED );
			showSpeed();
		}

		tracks.forEach( ( track ) => {
			track.querySelector( '[data-ap-play]' ).addEventListener( 'click', () => {
				if ( player.track !== track ) {
					player.showTrack( track );
				}
				toggle( track );
			}, UNDELAYED );
		} );

		// Start on the track the listener was in the middle of, else the first one.
		player.showTrack( tracks.find( ( track ) => savedTime( track ) > 0 ) || tracks[ 0 ] );
		root.classList.add( 'is-ready' );
	}

	function boot() {
		doc.querySelectorAll( '[data-stx-ap]' ).forEach( initPlayer );
	}

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
