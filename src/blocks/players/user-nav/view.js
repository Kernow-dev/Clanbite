/**
 * User Navigation block - Interactivity API view script.
 *
 * Implements the WAI-ARIA menu button pattern for the `role="menu"` dropdown: ArrowDown/ArrowUp open the menu
 * from the trigger, arrows/Home/End move between items, Tab closes it, and Escape closes it and returns focus
 * to the trigger.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Popup lifecycle (`clanbite.popup.toggled`, `clanbite.popup.shouldOpen`) comes from the shared popups client
 * (`window.clanbite.popups`, component `popups`), so every Clanbite popup reports and can be vetoed the same way.
 * @param name
 * @param open
 * @param root
 */
const announcePopup = ( name, open, root ) =>
	window.clanbite?.popups?.announce( name, open, root );
const allowPopupOpen = ( name, root ) =>
	window.clanbite?.popups?.allowOpen( name, root ) !== false;

const ITEM_SELECTOR = '[role="menuitem"]';

/**
 * Focusable menu items for the dropdown inside `root`.
 *
 * @param {Element|null} root Block root element.
 * @return {HTMLElement[]} Items in DOM order.
 */
function getItems( root ) {
	return root
		? Array.from(
				root.querySelectorAll(
					`.clanbite-user-nav__dropdown ${ ITEM_SELECTOR }`
				)
		  )
		: [];
}

/**
 * Focus an item once the dropdown has been revealed (the `hidden` binding updates after the state change).
 *
 * @param {Element|null}   root  Block root element.
 * @param {'first'|'last'} which Which item to focus.
 */
function focusItemSoon( root, which ) {
	window.requestAnimationFrame( () => {
		const items = getItems( root );
		const target =
			which === 'last' ? items[ items.length - 1 ] : items[ 0 ];
		target?.focus();
	} );
}

store( 'clanbite/user-nav', {
	state: {},

	actions: {
		toggleDropdown( event ) {
			// Do not stopPropagation: document click handlers on other header UI (e.g. notification
			// bell) must see the bubble so they can close their popovers when this one opens.
			const ctx = getContext();
			if (
				! ctx.isOpen &&
				! allowPopupOpen( 'user-nav', getElement().ref )
			) {
				return;
			}
			ctx.isOpen = ! ctx.isOpen;

			// `detail === 0` means the click came from the keyboard (Enter/Space): move focus into the menu.
			if ( ctx.isOpen && event?.detail === 0 ) {
				const { ref } = getElement();
				focusItemSoon(
					ref?.closest( '.wp-block-clanbite-user-nav' ),
					'first'
				);
			}
		},

		closeDropdown() {
			const ctx = getContext();
			ctx.isOpen = false;
		},

		handleOutsideClick( event ) {
			const ctx = getContext();
			if ( ! ctx.isOpen ) {
				return;
			}

			const { ref } = getElement();
			if ( ref && ! ref.contains( event.target ) ) {
				ctx.isOpen = false;
			}
		},

		handleKeydown( event ) {
			const ctx = getContext();

			if ( event.key === 'Escape' && ctx.isOpen ) {
				const { ref } = getElement();
				const focusWasInside = !! ref?.contains(
					ref.ownerDocument.activeElement
				);
				ctx.isOpen = false;
				event.preventDefault();

				// Return focus to the trigger, but only when focus was inside this widget.
				if ( focusWasInside ) {
					ref.querySelector( '.clanbite-user-nav__trigger' )?.focus();
				}
			}
		},

		onTriggerKeydown( event ) {
			if ( event.key !== 'ArrowDown' && event.key !== 'ArrowUp' ) {
				return;
			}

			event.preventDefault();
			if ( ! allowPopupOpen( 'user-nav', getElement().ref ) ) {
				return;
			}
			const ctx = getContext();
			ctx.isOpen = true;
			const { ref } = getElement();
			focusItemSoon(
				ref?.closest( '.wp-block-clanbite-user-nav' ),
				event.key === 'ArrowUp' ? 'last' : 'first'
			);
		},

		onMenuKeydown( event ) {
			const ctx = getContext();
			const { ref } = getElement();
			const root = ref?.closest( '.wp-block-clanbite-user-nav' );
			const items = getItems( root );
			const index = items.indexOf( ref?.ownerDocument?.activeElement );

			switch ( event.key ) {
				case 'ArrowDown':
					event.preventDefault();
					items[ ( index + 1 ) % items.length ]?.focus();
					break;
				case 'ArrowUp':
					event.preventDefault();
					items[
						( index - 1 + items.length ) % items.length
					]?.focus();
					break;
				case 'Home':
					event.preventDefault();
					items[ 0 ]?.focus();
					break;
				case 'End':
					event.preventDefault();
					items[ items.length - 1 ]?.focus();
					break;
				case 'Tab':
					// Let focus move on naturally, but do not leave an open menu behind.
					ctx.isOpen = false;
					break;
				default:
					break;
			}
		},
	},

	callbacks: {
		// Runs reactively whenever `context.isOpen` changes (see `data-wp-watch` on the root).
		announcePopup() {
			const { ref } = getElement();
			announcePopup( 'user-nav', !! getContext().isOpen, ref );
		},
	},
} );
