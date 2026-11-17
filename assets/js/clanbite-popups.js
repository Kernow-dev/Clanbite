/**
 * Clanbite popups client: the one implementation of the popup lifecycle hooks.
 *
 * Every Clanbite popup (user menu, notification bell, challenge dialog, reaction picker and tooltips…) tells the world when it opens
 * or closes and lets other code veto an opening, through the same two hooks. Components call this instead of keeping their own copy.
 *
 *   clanbite.popups.announce( 'user-nav', isOpen, rootElement ); // from a data-wp-watch callback
 *   if ( ! clanbite.popups.allowOpen( 'user-nav', rootElement ) ) return;
 *
 * @param {Window} window Browser window.
 */
( function ( window ) {
	'use strict';

	const hooks = window.wp && window.wp.hooks;
	const last = new WeakMap();

	/**
	 * Report a popup's open state. Fires `clanbite.popup.toggled` when it changed, however it was triggered.
	 *
	 * The first call for an element only records the server-rendered state; it is not a toggle.
	 *
	 * @param {string}  name Popup identifier (`user-nav`, `notification-bell`, `team-challenge`, `feed-reaction-picker`…).
	 * @param {boolean} open Whether it is open now.
	 * @param {Element} root The popup's root element.
	 */
	function announce( name, open, root ) {
		if ( ! root ) {
			return;
		}
		const seen = last.has( root );
		const previous = last.get( root );
		last.set( root, open );
		if ( seen && previous !== open && hooks ) {
			/**
			 * Fires whenever a Clanbite popup opens or closes, however it was triggered.
			 *
			 * @param {{ name: string, open: boolean, root: Element }} detail `name` identifies the popup; `root` is its element.
			 */
			hooks.doAction( 'clanbite.popup.toggled', { name, open, root } );
		}
	}

	/**
	 * Whether a popup may open. Other code can return `false` from the `clanbite.popup.shouldOpen` filter to keep it closed.
	 *
	 * @param {string}  name Popup identifier.
	 * @param {Element} root The popup's root element.
	 * @return {boolean} True unless a filter vetoed it.
	 */
	function allowOpen( name, root ) {
		if ( ! hooks ) {
			return true;
		}
		return (
			false !==
			/**
			 * Filters whether a Clanbite popup may open. Return `false` to keep it closed.
			 *
			 * @param {boolean}                         allow  Default `true`.
			 * @param {{ name: string, root: Element }} detail `name` identifies the popup; `root` is its element.
			 */
			hooks.applyFilters( 'clanbite.popup.shouldOpen', true, {
				name,
				root,
			} )
		);
	}

	/**
	 * Popovers that are shown and hidden by the Interactivity `hidden` binding (reaction pickers, tooltips) announce themselves too:
	 * mark the element `data-cp-popup="name"` and this watches its `hidden` attribute, so the lifecycle hooks fire for every way it
	 * can open or close (click, hover, outside click, Escape) without each store calling `announce()` in every close path.
	 * The event's `root` is the marked element.
	 *
	 * @param {Element} el A marked element.
	 */
	function track( el ) {
		const name = el.getAttribute( 'data-cp-popup' );
		if ( name ) {
			announce( name, ! el.hidden, el );
		}
	}

	function scan( node ) {
		if ( node.nodeType !== 1 ) {
			return;
		}
		if ( node.hasAttribute( 'data-cp-popup' ) ) {
			track( node );
		}
		node.querySelectorAll( '[data-cp-popup]' ).forEach( track );
	}

	function watchMarkedPopups() {
		scan( window.document.documentElement );
		new window.MutationObserver( ( records ) => {
			for ( const record of records ) {
				if ( record.type === 'attributes' ) {
					if ( record.target.hasAttribute( 'data-cp-popup' ) ) {
						track( record.target );
					}
				} else {
					record.addedNodes.forEach( scan );
				}
			}
		} ).observe( window.document.documentElement, {
			subtree: true,
			childList: true,
			attributes: true,
			attributeFilter: [ 'hidden' ],
		} );
	}

	if ( window.document.readyState === 'loading' ) {
		window.document.addEventListener(
			'DOMContentLoaded',
			watchMarkedPopups
		);
	} else {
		watchMarkedPopups();
	}

	const api = { announce, allowOpen };
	window.clanbite = window.clanbite || {};
	window.clanbite.popups = api;
	window.clanbite.components?.register( 'popups', api );
} )( window );
