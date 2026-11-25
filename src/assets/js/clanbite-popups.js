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
	 * mark the element `data-clanbite-popup="name"` and this watches its `hidden` attribute, so the lifecycle hooks fire for every way it
	 * can open or close (click, hover, outside click, Escape) without each store calling `announce()` in every close path.
	 * The event's `root` is the marked element.
	 *
	 * @param {Element} el A marked element.
	 */
	function track( el ) {
		const name = el.getAttribute( 'data-clanbite-popup' );
		if ( name ) {
			announce( name, ! el.hidden, el );
			float( el );
		}
	}

	const floating = new WeakMap();

	/**
	 * Lift an open popup into the browser's top layer so no post, card or avatar can ever cover it.
	 *
	 * A popup that is `position: absolute` inside a post sits in that post's stacking context, so later siblings can paint over it
	 * and `overflow` can clip it. While it is open this promotes it with the Popover API, keeps the exact spot its own CSS gave it
	 * (above, below, left or right aligned) and follows the page as it scrolls. It reverts when it closes. Add
	 * `data-clanbite-float="off"` to a popup that must stay in the flow. Browsers without the Popover API keep the old behaviour.
	 *
	 * @param {Element} el A marked popup.
	 */
	function float( el ) {
		if (
			typeof el.showPopover !== 'function' ||
			el.getAttribute( 'data-clanbite-float' ) === 'off'
		) {
			return;
		}
		const state = floating.get( el );
		if ( el.hidden || ! el.isConnected ) {
			if ( state ) {
				state.stop();
			}
			return;
		}
		if ( state ) {
			return;
		}
		const anchor = el.parentElement;
		if ( ! anchor || window.getComputedStyle( el ).position === 'fixed' ) {
			return;
		}
		// Measured in its natural spot, before it moves.
		const natural = el.getBoundingClientRect();
		const base = anchor.getBoundingClientRect();
		const dx = natural.left - base.left;
		const dy = natural.top - base.top;
		const color = window.getComputedStyle( el ).color;

		el.setAttribute( 'popover', 'manual' );
		el.style.color = color; // The popover's default styles would otherwise reset it.
		el.style.position = 'fixed';
		el.style.margin = '0';
		el.style.overflow = 'visible';
		el.style.inset = 'auto';
		el.style.width = natural.width + 'px';
		try {
			el.showPopover();
		} catch {
			el.removeAttribute( 'popover' );
			el.removeAttribute( 'style' );
			return;
		}

		let frame = 0;
		const place = () => {
			frame = 0;
			if ( ! anchor.isConnected ) {
				return;
			}
			const rect = anchor.getBoundingClientRect();
			const width = el.offsetWidth;
			const left = Math.max(
				8,
				Math.min( rect.left + dx, window.innerWidth - width - 8 )
			);
			el.style.left = left + 'px';
			el.style.top = rect.top + dy + 'px';
		};
		const schedule = () => {
			if ( ! frame ) {
				frame = window.requestAnimationFrame( place );
			}
		};
		const stop = () => {
			window.removeEventListener( 'scroll', schedule, true );
			window.removeEventListener( 'resize', schedule );
			if ( frame ) {
				window.cancelAnimationFrame( frame );
			}
			floating.delete( el );
			try {
				el.hidePopover();
			} catch {
				// Already closed.
			}
			el.removeAttribute( 'popover' );
			[
				'color',
				'position',
				'margin',
				'overflow',
				'inset',
				'width',
				'left',
				'top',
			].forEach( ( property ) => el.style.removeProperty( property ) );
		};
		floating.set( el, { stop } );
		window.addEventListener( 'scroll', schedule, true );
		window.addEventListener( 'resize', schedule );
		place();
	}

	function scan( node ) {
		if ( node.nodeType !== 1 ) {
			return;
		}
		if ( node.hasAttribute( 'data-clanbite-popup' ) ) {
			track( node );
		}
		node.querySelectorAll( '[data-clanbite-popup]' ).forEach( track );
	}

	function watchMarkedPopups() {
		scan( window.document.documentElement );
		new window.MutationObserver( ( records ) => {
			for ( const record of records ) {
				if ( record.type === 'attributes' ) {
					if ( record.target.hasAttribute( 'data-clanbite-popup' ) ) {
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
