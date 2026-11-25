/**
 * Tabbed team manage UI (mirrors clanbite-team-create-form behaviour; all tabs are reachable without a wizard).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import {
	syncTeamFormTabs,
	focusActiveTabButton,
} from '../shared/sync-team-form-tabs.js';

// Search timer and latest query, per invite box.
const inviteSearches = new WeakMap();

/**
 * The invite box an event came from, with its parts.
 *
 * @param {Element|undefined} element Element inside the box.
 * @return {Object} The box and its search input, status line and result list.
 */
function inviteBox( element ) {
	const box = element?.closest( '.clanbite-team-invite' );
	return {
		box,
		input: box?.querySelector( '.clanbite-team-invite__search' ),
		status: box?.querySelector( '[data-invite-status]' ),
		results: box?.querySelector( '[data-invite-results]' ),
	};
}

/**
 * Replace the search results with one "Invite" button per player.
 *
 * @param {Element}                               list    Result list.
 * @param {Array<{id: number, label: string}>}    players Players found.
 * @param {{invite: string, inviteLabel: string}} i18n    Button wording.
 */
function showInviteResults( list, players, i18n ) {
	list.replaceChildren(
		...players.map( ( player ) => {
			const item = list.ownerDocument.createElement( 'li' );
			const name = list.ownerDocument.createElement( 'span' );
			const button = list.ownerDocument.createElement( 'button' );
			name.textContent = String( player.label );
			button.type = 'button';
			button.className =
				'wp-element-button clanbite-button clanbite-button--primary';
			button.textContent = i18n.invite;
			button.setAttribute(
				'aria-label',
				i18n.inviteLabel.replace( '%s', player.label )
			);
			button.dataset.inviteUserId = String( Number( player.id ) );
			button.dataset.inviteName = String( player.label );
			item.append( name, button );
			return item;
		} )
	);
}

const { state } = store( 'clanbite-team-manage-form', {
	state: {
		root: null,
		step: 1,
		stepCount: 1,
		maxStep() {
			const total = Number( state.stepCount );
			return total > 0 ? total : 1;
		},
		get canGoNext() {
			return state.step < state.maxStep();
		},
		get canGoBack() {
			return state.step > 1;
		},
	},
	actions: {
		onInviteSearch( event ) {
			const { box, input, status, results } = inviteBox( event.target );
			if ( ! box ) {
				return;
			}
			// Read the context now: it is not available inside the timer.
			const { searchUrl, restNonce, i18n } = getContext();
			const query = input.value.trim();
			const search = inviteSearches.get( box ) || {};
			clearTimeout( search.timer );
			search.query = query;
			inviteSearches.set( box, search );
			if ( query.length < 2 ) {
				results.replaceChildren();
				status.textContent = '';
				return;
			}
			search.timer = setTimeout( async () => {
				try {
					const url = new URL( searchUrl );
					url.searchParams.set( 'q', query );
					const response = await fetch( url, {
						credentials: 'same-origin',
						headers: { 'X-WP-Nonce': restNonce },
					} );
					const body = await response.json();
					// A slower, older search must not replace a newer one.
					if ( search.query !== query ) {
						return;
					}
					if ( ! response.ok || ! Array.isArray( body ) ) {
						results.replaceChildren();
						status.textContent = body?.message || i18n.failed;
						return;
					}
					showInviteResults( results, body, i18n );
					status.textContent = body.length
						? i18n.found.replace( '%d', String( body.length ) )
						: i18n.none;
				} catch {
					status.textContent = i18n.failed;
				}
			}, 300 );
		},
		onInviteSearchKeydown( event ) {
			// Enter must not submit (and so save) the manage form around the box.
			if ( event.key === 'Enter' ) {
				event.preventDefault();
			}
		},
		async sendInvite( event ) {
			const button = event.target.closest(
				'button[data-invite-user-id]'
			);
			if ( ! button || button.disabled ) {
				return;
			}
			event.preventDefault();
			const { box, input, status, results } = inviteBox( button );
			const { inviteUrl, restNonce, i18n } = getContext();
			const name = button.dataset.inviteName;
			button.disabled = true;
			try {
				const response = await fetch( inviteUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': restNonce,
						'Content-Type': 'application/json',
					},
					body: JSON.stringify( {
						user_id: Number( button.dataset.inviteUserId ),
					} ),
				} );
				const body = await response.json();
				if ( ! response.ok ) {
					button.disabled = false;
					status.textContent = body?.message || i18n.failed;
					return;
				}
				button.closest( 'li' ).remove();
				const pending = box.querySelector( '[data-invite-pending]' );
				const item = box.ownerDocument.createElement( 'li' );
				item.textContent = name;
				pending.querySelector( 'ul' ).append( item );
				pending.hidden = false;
				status.textContent = i18n.sent.replace( '%s', name );
				// The button that had focus is gone.
				if ( ! results.children.length ) {
					input.value = '';
				}
				input.focus();
			} catch {
				button.disabled = false;
				status.textContent = i18n.failed;
			}
		},
		nextStep( event ) {
			event.preventDefault();
			if ( state.step >= state.maxStep() ) {
				return;
			}
			state.step += 1;
			const { ref } = getElement();
			const root = ref?.closest( '.clanbite-team-manage-form--tabbed' );
			syncTeamFormTabs( root, state.step, { wizard: false } );
			focusActiveTabButton( root, state.step );
		},
		previousStep( event ) {
			event.preventDefault();
			if ( state.step <= 1 ) {
				return;
			}
			state.step -= 1;
			const { ref } = getElement();
			const root = ref?.closest( '.clanbite-team-manage-form--tabbed' );
			syncTeamFormTabs( root, state.step, { wizard: false } );
			focusActiveTabButton( root, state.step );
		},
		goToStepTab( event ) {
			event.preventDefault();
			const btn = event?.currentTarget;
			const n = Number( btn?.getAttribute( 'data-team-tab' ) );
			if ( ! n || n < 1 || n > state.maxStep() || n === state.step ) {
				return;
			}
			state.step = n;
			const { ref } = getElement();
			const root = ref?.closest( '.clanbite-team-manage-form--tabbed' );
			syncTeamFormTabs( root, state.step, { wizard: false } );
		},
		onTabListKeydown( event ) {
			const key = event.key;
			if (
				key !== 'ArrowLeft' &&
				key !== 'ArrowRight' &&
				key !== 'Home' &&
				key !== 'End'
			) {
				return;
			}
			event.preventDefault();
			const max = state.maxStep();
			let next = state.step;
			if ( key === 'ArrowRight' ) {
				next = Math.min( state.step + 1, max );
			} else if ( key === 'ArrowLeft' ) {
				next = Math.max( state.step - 1, 1 );
			} else if ( key === 'Home' ) {
				next = 1;
			} else if ( key === 'End' ) {
				next = max;
			}
			if ( next === state.step ) {
				return;
			}
			state.step = next;
			const { ref } = getElement();
			const root = ref?.closest( '.clanbite-team-manage-form--tabbed' );
			syncTeamFormTabs( root, state.step, { wizard: false } );
			focusActiveTabButton( root, state.step );
		},
	},
	callbacks: {
		init() {
			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}
			const ctx = getContext();
			const fromServer = Number( ctx?.stepCount );
			state.stepCount =
				Number.isFinite( fromServer ) && fromServer > 0
					? fromServer
					: 1;
			state.root = ref;
			syncTeamFormTabs( ref, state.step, { wizard: false } );
		},
	},
} );
