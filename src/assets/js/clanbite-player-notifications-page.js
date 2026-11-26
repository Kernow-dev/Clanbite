/**
 * The player notifications subpage: runs a notification's buttons (such as accepting a team invitation), and marks a
 * notification read before following its link. The notification bell handles its own dropdown.
 *
 * @package
 */

/* global clanbitePlayerNotificationsPageI18n */
( function () {
	'use strict';

	const i18n =
		typeof clanbitePlayerNotificationsPageI18n === 'object' &&
		clanbitePlayerNotificationsPageI18n !== null
			? clanbitePlayerNotificationsPageI18n
			: {};

	const root = document.querySelector(
		'.clanbite-notifications-page[data-clanbite-notifications-rest]'
	);
	if ( ! root ) {
		return;
	}
	const restBase = root.getAttribute( 'data-clanbite-notifications-rest' );
	const nonce = root.getAttribute( 'data-clanbite-notifications-nonce' );
	if ( ! restBase || ! nonce ) {
		return;
	}

	function isPlainLeftClick( ev ) {
		return (
			ev.button === 0 &&
			! ev.metaKey &&
			! ev.ctrlKey &&
			! ev.shiftKey &&
			! ev.altKey
		);
	}

	/**
	 * A web address that is safe to send the visitor to.
	 *
	 * @param {unknown} value Address from the server.
	 * @return {string} The address, or an empty string.
	 */
	function safeHttpUrl( value ) {
		try {
			const url = new URL( String( value ), window.location.href );
			return url.protocol === 'http:' || url.protocol === 'https:'
				? url.href
				: '';
		} catch {
			return '';
		}
	}

	/**
	 * Say what happened, in the place of the buttons.
	 *
	 * @param {HTMLElement} actions The notification's button row.
	 * @param {string}      message What happened.
	 * @param {boolean}     failed  Whether it failed, in which case the buttons stay.
	 */
	function report( actions, message, failed ) {
		let note = actions.parentNode.querySelector(
			'.clanbite-notification__status'
		);
		if ( ! note ) {
			note = document.createElement( 'div' );
			note.className = 'clanbite-notification__status';
			// Focusable, so keyboard users are not left where a button used to be.
			note.tabIndex = -1;
			actions.parentNode.appendChild( note );
		}
		note.setAttribute( 'role', failed ? 'alert' : 'status' );
		note.textContent = message;
		if ( ! failed ) {
			actions.remove();
			note.focus();
		}
	}

	root.addEventListener( 'click', function ( ev ) {
		const button = ev.target.closest(
			'button[data-action][data-notification-id]'
		);
		if ( ! button || ! root.contains( button ) ) {
			return;
		}
		ev.preventDefault();
		const question = button.getAttribute( 'data-confirm' );
		// eslint-disable-next-line no-alert -- the site's own wording, asked before something that can not be undone.
		if ( question && ! window.confirm( question ) ) {
			return;
		}
		const actions = button.closest( '.clanbite-notification__actions' );
		const buttons = actions.querySelectorAll( 'button' );
		buttons.forEach( ( b ) => ( b.disabled = true ) );
		fetch(
			restBase.replace( /\/?$/, '/' ) +
				'notifications/' +
				encodeURIComponent(
					button.getAttribute( 'data-notification-id' )
				) +
				'/action',
			{
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( {
					action: button.getAttribute( 'data-action' ),
				} ),
			}
		)
			.then( async function ( res ) {
				const data = await res.json().catch( () => ( {} ) );
				if ( ! res.ok ) {
					throw new Error( data.message || '' );
				}
				const row = actions.closest( '.clanbite-notification' );
				row?.classList.add( 'is-read' );
				report(
					actions,
					String( data.message || i18n.actionDone || '' ),
					false
				);
				const next = data.redirect ? safeHttpUrl( data.redirect ) : '';
				if ( next ) {
					window.location.assign( next );
				}
			} )
			.catch( function ( err ) {
				buttons.forEach( ( b ) => ( b.disabled = false ) );
				// A network failure carries the browser's own wording: show the site's message instead.
				const own = err instanceof TypeError ? '' : err.message;
				report( actions, own || i18n.actionFailed || '', true );
			} );
	} );

	root.addEventListener( 'click', function ( ev ) {
		const a = ev.target.closest(
			'a.clanbite-notification__link[data-notification-id]'
		);
		if ( ! a || ! root.contains( a ) ) {
			return;
		}
		if ( ! isPlainLeftClick( ev ) ) {
			return;
		}
		const id = a.getAttribute( 'data-notification-id' );
		if ( ! id ) {
			return;
		}
		const row = a.closest( '.clanbite-notification' );
		if ( row && row.classList.contains( 'is-read' ) ) {
			return;
		}
		ev.preventDefault();
		const href = a.getAttribute( 'href' );
		const url =
			restBase.replace( /\/?$/, '/' ) +
			'notifications/' +
			encodeURIComponent( id ) +
			'/read';
		fetch( url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce,
			},
		} )
			.then( function ( res ) {
				if ( ! res.ok ) {
					return res.text().then( function ( body ) {
						const msg = body ? body.slice( 0, 200 ) : '';
						throw new Error( msg || 'HTTP ' + res.status );
					} );
				}
			} )
			.then( function () {
				if ( href ) {
					window.location.assign( href );
				}
			} )
			.catch( function ( err ) {
				// eslint-disable-next-line no-console
				console.error(
					'Clanbite: failed to mark notification as read',
					err
				);
				const note = document.createElement( 'p' );
				note.className = 'clanbite-notifications-page__read-error';
				note.setAttribute( 'role', 'alert' );
				note.textContent =
					i18n.markReadError ||
					'Could not mark this notification as read. Opening the link anyway — see the browser console for details.';
				const header = root.querySelector(
					'.clanbite-notifications-page__header'
				);
				if ( header && header.nextSibling ) {
					root.insertBefore( note, header.nextSibling );
				} else {
					root.insertBefore( note, root.firstChild );
				}
				setTimeout( function () {
					if ( href ) {
						window.location.assign( href );
					}
				}, 2000 );
			} );
	} );
} )();
