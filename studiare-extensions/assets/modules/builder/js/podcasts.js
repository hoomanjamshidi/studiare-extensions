/**
 * Studiare Extensions — Podcast list widget.
 *
 * Plays episodes in place. Every list on the page shares one audio element,
 * created on the first tap (nothing is downloaded before), so only one
 * episode plays at a time; the post player (article-tools.js) pauses it and
 * is paused by it through the `stx:podcast-play` event. The playlist look
 * has a player (the deck) that shows the chosen episode, with seeking,
 * 15/30 second jumps, previous/next and speed, and goes on to the next
 * episode at the end. Positions are kept per episode under the same key as
 * the post player (`stxPodcast:<post id>`), so listening continues across
 * the two, and the speed under `stxPodcastSpeed`. Progress only changes a
 * transform. Without JavaScript the titles are plain links to the posts.
 */
( function () {
	'use strict';

	const config = window.stxPodcasts || {};
	const i18n = config.i18n || {};
	const doc = document;

	const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
	const SPEEDS = [ 1, 1.25, 1.5, 1.75, 2, 0.75 ];
	const SPEED_KEY = 'stxPodcastSpeed';
	const SAVE_EVERY = 5;
	const SOURCE = 'list';

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

	/* ---------------------------------------------------------------------
	 * Episodes
	 * ------------------------------------------------------------------- */

	const durationOf = ( ep ) => parseFloat( ep.dataset.duration ) || 0;
	const keyOf = ( ep ) => 'stxPodcast:' + ep.dataset.id;
	const listOf = ( ep ) => ep.closest( '[data-stx-pods]' );
	const deckOf = ( ep ) => {
		const list = listOf( ep );
		return list && list.stxDeck ? list.stxDeck : null;
	};

	/** Saved position; a visit that stopped near the end starts over. */
	function savedTime( ep ) {
		const time = parseFloat( store.get( keyOf( ep ) ) ) || 0;
		const duration = durationOf( ep );

		return duration && time > duration - 10 ? 0 : time;
	}

	let audio = null;
	let current = null;
	let lastSaved = 0;
	let startAt = 0;
	let rate = parseFloat( store.get( SPEED_KEY ) ) || 1;

	if ( SPEEDS.indexOf( rate ) === -1 ) {
		rate = 1;
	}

	function save( force ) {
		if ( ! audio || ! current ) {
			return;
		}
		const time = audio.currentTime;
		if ( ! force && Math.abs( time - lastSaved ) < SAVE_EVERY ) {
			return;
		}
		lastSaved = time;
		store.set( keyOf( current ), time > 1 ? String( Math.floor( time ) ) : null );
	}

	/** Buttons that play or pause this episode: its own and the deck's while it shows it. */
	function buttonsOf( ep ) {
		const buttons = Array.from( ep.querySelectorAll( '[data-ep-play]' ) );
		const deck = deckOf( ep );
		if ( deck && deck.ep === ep ) {
			buttons.push( deck.ui.play );
		}
		return buttons;
	}

	function setState( ep, name, on ) {
		ep.classList.toggle( 'is-' + name, on );
		const deck = deckOf( ep );
		if ( deck && deck.ep === ep ) {
			deck.root.classList.toggle( 'is-' + name, on );
		}
		if ( name === 'playing' ) {
			const label = format( on ? i18n.pause : i18n.play, [ ep.dataset.title ] );
			buttonsOf( ep ).forEach( ( button ) => button.setAttribute( 'aria-label', label ) );
		}
	}

	/** Moves the progress line of the episode (and of the deck showing it). */
	function showTime( ep, time ) {
		const duration = durationOf( ep );
		const ratio = duration ? Math.min( 1, time / duration ) : 0;
		ep.style.setProperty( '--stx-ep-p', ratio.toFixed( 4 ) );

		const deck = deckOf( ep );
		if ( deck && deck.ep === ep ) {
			deck.showTime( time );
		}
	}

	function setDuration( ep, value ) {
		if ( ! value || ! isFinite( value ) || Math.floor( value ) === durationOf( ep ) ) {
			return;
		}
		ep.dataset.duration = String( Math.floor( value ) );
		ep.dataset.time = clock( value );
		const deck = deckOf( ep );
		if ( deck && deck.ep === ep ) {
			deck.showEpisode( ep );
		}
	}

	function failed( ep ) {
		setState( ep, 'loading', false );
		setState( ep, 'playing', false );
		setState( ep, 'error', true );
		const deck = deckOf( ep );
		if ( deck && deck.ep === ep && deck.ui.error ) {
			deck.ui.error.hidden = false;
		}
	}

	function mediaSession( ep ) {
		if ( ! ( 'mediaSession' in navigator ) || typeof window.MediaMetadata !== 'function' ) {
			return;
		}

		navigator.mediaSession.metadata = new window.MediaMetadata( {
			title: ep.dataset.title || '',
			artist: ep.dataset.artist || '',
			artwork: ep.dataset.artwork ? [ { src: ep.dataset.artwork } ] : [],
		} );

		const deck = deckOf( ep );
		const actions = {
			play: () => audio.play(),
			pause: () => audio.pause(),
			seekbackward: () => seekBy( -15 ),
			seekforward: () => seekBy( 30 ),
			seekto: ( details ) => seekTo( ep, details.seekTime ),
			previoustrack: deck ? () => deck.step( -1 ) : null,
			nexttrack: deck ? () => deck.step( 1 ) : null,
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
			setDuration( current, audio.duration );
			// Some browsers drop a start time set before the file was read.
			if ( startAt && audio.currentTime < 1 ) {
				audio.currentTime = startAt;
			}
			startAt = 0;
		} );
		audio.addEventListener( 'durationchange', () => setDuration( current, audio.duration ) );
		audio.addEventListener( 'timeupdate', () => {
			showTime( current, audio.currentTime );
			save( false );
		} );
		audio.addEventListener( 'play', () => {
			setState( current, 'playing', true );
			setState( current, 'error', false );
			const deck = deckOf( current );
			if ( deck && deck.ui.error ) {
				deck.ui.error.hidden = true;
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
			const ep = current;
			store.set( keyOf( ep ), null );
			lastSaved = 0;
			showTime( ep, durationOf( ep ) );
			const deck = deckOf( ep );
			if ( deck ) {
				deck.step( 1, true );
			}
		} );
		audio.addEventListener( 'error', () => current && failed( current ) );

		return audio;
	}

	/** Puts an episode in the audio element, from where the visitor stopped. */
	function load( ep ) {
		if ( current ) {
			save( true );
			setState( current, 'playing', false );
			setState( current, 'loading', false );
		}

		const player = media();
		current = ep;
		startAt = savedTime( ep );
		lastSaved = startAt;
		player.src = ep.dataset.audio;
		player.playbackRate = rate;
		if ( startAt ) {
			player.currentTime = startAt;
		}
	}

	function play( ep ) {
		if ( current !== ep ) {
			load( ep );
		}
		if ( audio.readyState < 3 ) {
			setState( ep, 'loading', true );
		}

		const started = audio.play();
		if ( started && started.catch ) {
			started.catch( ( error ) => {
				setState( ep, 'loading', false );
				// Pausing before playback starts rejects with AbortError; that is no failure.
				if ( error && error.name !== 'AbortError' && error.name !== 'NotAllowedError' ) {
					failed( ep );
				}
			} );
		}
	}

	function toggle( ep ) {
		if ( current === ep && audio && ! audio.paused ) {
			audio.pause();
			return;
		}
		play( ep );
	}

	function seekTo( ep, time ) {
		const duration = durationOf( ep );
		const target = Math.max( 0, duration ? Math.min( duration, time ) : time );

		if ( current === ep && audio ) {
			audio.currentTime = target;
		} else {
			store.set( keyOf( ep ), target > 1 ? String( Math.floor( target ) ) : null );
		}
		showTime( ep, target );
	}

	function seekBy( seconds ) {
		if ( current && audio ) {
			seekTo( current, audio.currentTime + seconds );
		}
	}

	// The post player started: this list stops.
	doc.addEventListener( 'stx:podcast-play', ( event ) => {
		if ( event.detail && event.detail.source !== SOURCE && audio && ! audio.paused ) {
			audio.pause();
		}
	} );
	window.addEventListener( 'pagehide', () => save( true ) );

	/* ---------------------------------------------------------------------
	 * Deck (the player of the playlist look)
	 * ------------------------------------------------------------------- */

	function initDeck( root, list, episodes ) {
		const ui = {
			play: root.querySelector( '[data-deck-play]' ),
			seek: root.querySelector( '[data-deck-seek]' ),
			current: root.querySelector( '[data-deck-current]' ),
			total: root.querySelector( '[data-deck-total]' ),
			speed: root.querySelector( '[data-deck-speed]' ),
			error: root.querySelector( '[data-deck-error]' ),
			art: root.querySelector( '[data-deck-art]' ),
			label: root.querySelector( '[data-deck-label]' ),
			title: root.querySelector( '[data-deck-title]' ),
			sub: root.querySelector( '[data-deck-sub]' ),
		};
		const steps = Array.from( root.querySelectorAll( '[data-deck-step]' ) );
		let dragging = false;

		const deck = {
			root,
			ui,
			ep: episodes[ 0 ],

			showTime( time ) {
				const duration = durationOf( deck.ep );
				root.style.setProperty( '--stx-ep-p', duration ? Math.min( 1, time / duration ).toFixed( 4 ) : '0' );
				ui.current.textContent = clock( time );
				if ( ! dragging ) {
					ui.seek.value = String( Math.floor( time ) );
				}
				ui.seek.setAttribute( 'aria-valuetext', format( i18n.position, [ clock( time ), duration ? clock( duration ) : '--:--' ] ) );
			},

			/** Shows an episode's title, picture and length (without playing it). */
			showEpisode( ep ) {
				deck.ep = ep;
				ui.title.textContent = ep.dataset.title;
				ui.title.href = ep.dataset.url;
				ui.label.textContent = ep.dataset.label || '';
				ui.sub.textContent = ep.dataset.sub || '';
				ui.sub.hidden = ! ep.dataset.sub;
				ui.seek.max = String( durationOf( ep ) );
				ui.total.textContent = ep.dataset.time || '--:--';
				ui.error.hidden = true;
				deck.showArt( ep );

				const playing = current === ep && audio && ! audio.paused;
				[ 'playing', 'loading', 'error' ].forEach( ( name ) => root.classList.toggle( 'is-' + name, ep.classList.contains( 'is-' + name ) ) );
				ui.play.setAttribute( 'aria-label', format( playing ? i18n.pause : i18n.play, [ ep.dataset.title ] ) );
				episodes.forEach( ( item ) => item.classList.toggle( 'is-current', item === ep ) );
				const index = episodes.indexOf( ep );
				steps.forEach( ( button ) => {
					button.disabled = ! episodes[ index + parseInt( button.dataset.deckStep, 10 ) ];
				} );
				deck.showTime( current === ep && audio ? audio.currentTime : savedTime( ep ) );
			},

			showArt( ep ) {
				const art = ep.dataset.artwork;
				let img = ui.art.querySelector( 'img' );
				if ( ! art ) {
					ui.art.classList.add( 'is-empty' );
					return;
				}
				ui.art.classList.remove( 'is-empty' );
				if ( ! img ) {
					img = doc.createElement( 'img' );
					img.className = 'stx-pdeck__img';
					img.alt = '';
					ui.art.prepend( img );
				}
				if ( img.getAttribute( 'src' ) !== art ) {
					img.removeAttribute( 'srcset' );
					img.removeAttribute( 'sizes' );
					img.src = art;
				}
			},

			/** Previous or next episode; it plays if one was playing, or when `autoplay`. */
			step( direction, autoplay ) {
				const next = episodes[ episodes.indexOf( deck.ep ) + direction ];
				if ( ! next ) {
					return;
				}
				const playing = autoplay || ( audio && ! audio.paused );
				if ( playing ) {
					audio.pause();
				}
				deck.showEpisode( next );
				if ( playing ) {
					play( next );
				}
			},
		};

		list.stxDeck = deck;

		ui.play.addEventListener( 'click', () => toggle( deck.ep ) );

		root.querySelectorAll( '[data-deck-skip]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const jump = parseFloat( button.dataset.deckSkip ) || 0;
				const from = current === deck.ep && audio ? audio.currentTime : savedTime( deck.ep );
				seekTo( deck.ep, from + jump );
			} );
		} );

		steps.forEach( ( button ) => {
			button.addEventListener( 'click', () => deck.step( parseInt( button.dataset.deckStep, 10 ) ) );
		} );

		ui.seek.addEventListener( 'input', () => {
			dragging = true;
			deck.showTime( parseFloat( ui.seek.value ) || 0 );
		} );
		ui.seek.addEventListener( 'change', () => {
			dragging = false;
			seekTo( deck.ep, parseFloat( ui.seek.value ) || 0 );
		} );

		function showSpeed() {
			ui.speed.textContent = speedLabel( rate );
			ui.speed.setAttribute( 'aria-label', format( i18n.speed, [ speedLabel( rate ) ] ) );
		}
		ui.speed.addEventListener( 'click', () => {
			rate = SPEEDS[ ( SPEEDS.indexOf( rate ) + 1 ) % SPEEDS.length ];
			if ( audio ) {
				audio.playbackRate = rate;
			}
			store.set( SPEED_KEY, rate === 1 ? null : String( rate ) );
			doc.querySelectorAll( '[data-deck-speed]' ).forEach( ( button ) => {
				button.textContent = speedLabel( rate );
			} );
			showSpeed();
		} );

		showSpeed();
		deck.showEpisode( deck.ep );
	}

	/* ---------------------------------------------------------------------
	 * Boot (page load and widgets added in the Elementor editor)
	 * ------------------------------------------------------------------- */

	function initList( list ) {
		const episodes = Array.from( list.querySelectorAll( '[data-stx-ep]' ) );
		if ( ! episodes.length ) {
			return;
		}

		const deck = list.querySelector( '[data-pod-deck]' );
		if ( deck ) {
			initDeck( deck, list, episodes );
		}

		episodes.forEach( ( ep ) => {
			showTime( ep, savedTime( ep ) );
			ep.querySelectorAll( '[data-ep-play]' ).forEach( ( button ) => {
				button.addEventListener( 'click', () => {
					const listDeck = deckOf( ep );
					if ( listDeck && listDeck.ep !== ep ) {
						listDeck.showEpisode( ep );
					}
					toggle( ep );
				} );
			} );
		} );

		list.classList.add( 'is-ready' );
	}

	function initScope( scope ) {
		scope.querySelectorAll( '[data-stx-pods]' ).forEach( ( list ) => {
			if ( ! list.hasAttribute( 'data-stx-ready' ) ) {
				list.setAttribute( 'data-stx-ready', '' );
				initList( list );
			}
		} );
	}

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', () => initScope( doc ) );
	} else {
		initScope( doc );
	}

	let hooked = false;
	function hookElementor() {
		if ( hooked || ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}
		hooked = true;
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/stx-podcast-list.default', ( $scope ) => {
			if ( $scope && $scope[ 0 ] ) {
				initScope( $scope[ 0 ] );
			}
		} );
	}

	// Elementor announces itself with a jQuery event (native listeners do not see it).
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', hookElementor );
	}
	window.addEventListener( 'elementor/frontend/init', hookElementor );
	hookElementor();
}() );
