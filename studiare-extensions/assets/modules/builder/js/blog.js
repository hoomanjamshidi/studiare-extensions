/**
 * Studiare Extensions — blog widgets.
 *
 * Everything here enhances markup that already works on its own: share
 * links open the networks, the table of contents is a native <details> with
 * plain anchor links, the category drop-down is a native <details>. This
 * adds "Copy link" and the phone's share sheet (hidden until now), marks the
 * section being read, fills the reading progress bar, brings the current
 * category into view in rows that scroll sideways, and closes the category
 * drop-down on an outside tap, with Esc or when focus leaves it. Scroll work is batched into one animation frame and only
 * writes transforms and classes.
 */
( function () {
	'use strict';

	const config = window.stxBlog || {};
	const i18n = config.i18n || {};
	const doc = document;

	/* ---------------------------------------------------------------------
	 * Shared scroll loop (table of contents, progress bar)
	 * ------------------------------------------------------------------- */

	const onScroll = [];
	let scheduled = false;

	function runScroll() {
		scheduled = false;
		onScroll.forEach( ( callback ) => callback() );
	}

	function schedule() {
		if ( ! scheduled ) {
			scheduled = true;
			window.requestAnimationFrame( runScroll );
		}
	}

	function watchScroll( callback ) {
		if ( ! onScroll.length ) {
			window.addEventListener( 'scroll', schedule, { passive: true } );
			window.addEventListener( 'resize', schedule, { passive: true } );
		}
		onScroll.push( callback );
		schedule();
	}

	/** Height the sticky header and admin bar cover at the top of the screen. */
	function topOffset() {
		const style = getComputedStyle( doc.documentElement );
		const header = parseFloat( style.getPropertyValue( '--stx-header-offset' ) ) || 0;
		const bar = parseFloat( style.getPropertyValue( '--stx-admin-bar' ) ) || 0;

		return header + bar;
	}

	/* ---------------------------------------------------------------------
	 * Share: copy link and the phone's share sheet
	 * ------------------------------------------------------------------- */

	let live = null;
	function announce( message ) {
		if ( ! live ) {
			live = doc.createElement( 'span' );
			live.className = 'screen-reader-text';
			live.setAttribute( 'aria-live', 'polite' );
			doc.body.appendChild( live );
		}
		live.textContent = '';
		window.setTimeout( () => {
			live.textContent = message;
		}, 50 );
	}

	function copyText( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}

		// Plain http sites have no clipboard API: fall back to a selected field.
		return new Promise( ( resolve, reject ) => {
			const field = doc.createElement( 'textarea' );
			field.value = text;
			field.setAttribute( 'readonly', '' );
			field.style.position = 'fixed';
			field.style.opacity = '0';
			doc.body.appendChild( field );
			field.select();
			const ok = doc.execCommand( 'copy' );
			field.remove();
			( ok ? resolve : reject )();
		} );
	}

	function initShare( root ) {
		root.querySelectorAll( '[data-stx-needs-js]' ).forEach( ( item ) => {
			item.hidden = false;
		} );

		if ( typeof navigator.share === 'function' ) {
			root.querySelectorAll( '[data-stx-needs-share]' ).forEach( ( item ) => {
				item.hidden = false;
			} );
		}

		root.addEventListener( 'click', ( event ) => {
			const copy = event.target.closest( '[data-stx-copy]' );
			if ( copy ) {
				copyText( copy.dataset.stxCopy ).then( () => {
					copy.classList.add( 'is-done' );
					announce( i18n.copied || '' );
					window.setTimeout( () => copy.classList.remove( 'is-done' ), 2000 );
				} ).catch( () => {} );
				return;
			}

			const share = event.target.closest( '[data-stx-share]' );
			if ( share ) {
				// Closing the share sheet rejects the promise; nothing to report.
				navigator.share( { title: share.dataset.title, url: share.dataset.url } ).catch( () => {} );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Table of contents: mark the section being read
	 * ------------------------------------------------------------------- */

	function initToc( root ) {
		const entries = Array.from( root.querySelectorAll( '.stx-toc__link' ) ).map( ( link ) => {
			let id = link.getAttribute( 'href' ).slice( 1 );
			try {
				id = decodeURIComponent( id );
			} catch ( error ) {
				// Keep the raw id.
			}
			return { link, heading: doc.getElementById( id ) };
		} ).filter( ( entry ) => entry.heading );

		if ( ! entries.length ) {
			return;
		}

		let active = null;
		watchScroll( () => {
			const line = topOffset() + window.innerHeight * 0.25;
			let current = null;
			entries.forEach( ( entry ) => {
				if ( entry.heading.getBoundingClientRect().top <= line ) {
					current = entry;
				}
			} );

			if ( current !== active ) {
				if ( active ) {
					active.link.classList.remove( 'is-active' );
					active.link.removeAttribute( 'aria-current' );
				}
				if ( current ) {
					current.link.classList.add( 'is-active' );
					current.link.setAttribute( 'aria-current', 'location' );
				}
				active = current;
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Reading progress
	 * ------------------------------------------------------------------- */

	function initProgress( root ) {
		const bar = root.querySelector( '.stx-progress__bar' );
		const content = doc.querySelector( '.stx-prose' );

		watchScroll( () => {
			let done;
			if ( content ) {
				const box = content.getBoundingClientRect();
				const span = box.height - window.innerHeight * 0.6;
				done = span > 0 ? ( window.innerHeight * 0.4 - box.top ) / span : 1;
			} else {
				const span = doc.documentElement.scrollHeight - window.innerHeight;
				done = span > 0 ? window.scrollY / span : 1;
			}
			bar.style.setProperty( '--stx-read', Math.min( 1, Math.max( 0, done ) ).toFixed( 4 ) );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Blog categories
	 * ------------------------------------------------------------------- */

	/**
	 * Rows of buttons or tabs scroll sideways on phones, so on a category page
	 * the current one can start off screen: centre it, instantly (the visitor
	 * did not ask for motion).
	 */
	function initCategoryRow( list ) {
		const current = list.querySelector( '.is-active' );
		if ( ! current || list.scrollWidth <= list.clientWidth ) {
			return;
		}

		const box = list.getBoundingClientRect();
		const item = current.getBoundingClientRect();
		// A visual distance, so it scrolls the right way in RTL and LTR alike.
		list.scrollBy( { left: item.left + ( item.width / 2 ) - ( box.left + ( box.width / 2 ) ), behavior: 'instant' } );
	}

	function initCategoryMenu( menu ) {
		const toggle = menu.querySelector( 'summary' );

		doc.addEventListener( 'click', ( event ) => {
			if ( menu.open && ! menu.contains( event.target ) ) {
				menu.open = false;
			}
		} );

		menu.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key && menu.open ) {
				menu.open = false;
				toggle.focus();
			}
		} );

		menu.addEventListener( 'focusout', ( event ) => {
			if ( menu.open && event.relatedTarget && ! menu.contains( event.relatedTarget ) ) {
				menu.open = false;
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Boot (page load and widgets added in the Elementor editor)
	 * ------------------------------------------------------------------- */

	function initScope( scope ) {
		const once = ( selector, init ) => {
			scope.querySelectorAll( selector ).forEach( ( element ) => {
				if ( ! element.hasAttribute( 'data-stx-ready' ) ) {
					element.setAttribute( 'data-stx-ready', '' );
					init( element );
				}
			} );
		};

		once( '.stx-share', initShare );
		once( '[data-stx-toc]', initToc );
		once( '[data-stx-progress]', initProgress );
		once( '.stx-terms--chips .stx-terms__list, .stx-terms--tabs .stx-terms__list', initCategoryRow );
		once( '[data-stx-cat-menu]', initCategoryMenu );
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
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', ( $scope ) => {
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
