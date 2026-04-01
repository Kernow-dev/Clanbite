/**
 * Notification Bell block - Interactivity API view script.
 *
 * Uses HTTP long polling by default. Third-party plugins can provide
 * WebSocket transport via the 'sync.providers' filter (same pattern as WP 7.0 RTC).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Simple fetch wrapper for REST API calls.
 * Uses native fetch since @wordpress/api-fetch isn't available in module scripts.
 *
 * @param {string} restUrl  Base REST URL from context.
 * @param {string} nonce    WP REST nonce from context.
 * @param {Object} options  Fetch options.
 * @param {string} options.path   REST API path (relative to namespace, e.g., 'notifications').
 * @param {string} options.method HTTP method (default GET).
 * @param {Object} options.data   Request body data.
 * @return {Promise<Object>} Response JSON.
 */
async function restFetch( restUrl, nonce, { path, method = 'GET', data } ) {
	// Remove leading slash from path to avoid double slashes.
	const cleanPath = path.replace( /^\/+/, '' );
	const url = `${ restUrl }${ cleanPath }`;

	const options = {
		method,
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
		},
	};

	if ( nonce ) {
		options.headers[ 'X-WP-Nonce' ] = nonce;
	}

	if ( data && method !== 'GET' && method !== 'HEAD' ) {
		options.body = JSON.stringify( data );
	}

	const response = await fetch( url, options );

	if ( ! response.ok ) {
		const error = new Error( `REST request failed: ${ response.status }` );
		error.response = response;
		throw error;
	}

	return response.json();
}

/**
 * Escape HTML entities in a string.
 *
 * @param {string} str String to escape.
 * @return {string} Escaped string.
 */
function escapeHtml( str ) {
	if ( ! str ) {
		return '';
	}
	const div = document.createElement( 'div' );
	div.textContent = str;
	return div.innerHTML;
}

/**
 * Render a single notification item as HTML string.
 *
 * @param {Object} notification Notification data.
 * @param {Object} i18n         Internationalization strings.
 * @return {string} HTML string.
 */
function renderNotificationItem( notification, i18n ) {
	const timeAgo = notification.time_ago || '';
	const isUnread = ! notification.is_read;
	const isActionable = notification.is_actionable;

	let classes = 'clanspress-notification is-compact';
	if ( isUnread ) {
		classes += ' is-unread';
	}
	if ( isActionable ) {
		classes += ' is-actionable';
	}
	if ( notification.status && notification.status !== 'pending' ) {
		classes += ` is-${ notification.status }`;
	}

	let avatarHtml = '';
	if ( notification.actor && notification.actor.avatar_url ) {
		avatarHtml = `
			<div class="clanspress-notification__avatar">
				<img src="${ notification.actor.avatar_url }" alt="" />
			</div>
		`;
	} else {
		avatarHtml = `
			<div class="clanspress-notification__icon">
				<span class="dashicons dashicons-bell"></span>
			</div>
		`;
	}

	let titleHtml = '';
	if ( notification.url && ! isActionable ) {
		titleHtml = `
			<a href="${ notification.url }" class="clanspress-notification__link" data-notification-id="${ notification.id }">
				<span class="clanspress-notification__title">${ escapeHtml( notification.title ) }</span>
			</a>
		`;
	} else {
		titleHtml = `<span class="clanspress-notification__title">${ escapeHtml( notification.title ) }</span>`;
	}

	let messageHtml = '';
	if ( notification.message ) {
		messageHtml = `<p class="clanspress-notification__message">${ escapeHtml( notification.message ) }</p>`;
	}

	let actionsHtml = '';
	if ( isActionable && notification.actions && notification.actions.length > 0 ) {
		const buttons = notification.actions.map( ( action ) => {
			const style = action.style || 'secondary';
			const confirmAttr = action.confirm ? ` data-confirm="${ escapeHtml( action.confirm ) }"` : '';
			return `
				<button
					type="button"
					class="clanspress-notification__action clanspress-notification__action--${ style }"
					data-action="${ action.key }"
					data-notification-id="${ notification.id }"
					${ confirmAttr }
				>${ escapeHtml( action.label ) }</button>
			`;
		} ).join( '' );
		actionsHtml = `<div class="clanspress-notification__actions">${ buttons }</div>`;
	} else if ( notification.status && notification.status !== 'pending' ) {
		const statusLabels = i18n?.statusLabels || {
			accepted: 'Accepted',
			declined: 'Declined',
			dismissed: 'Dismissed',
			expired: 'Expired',
		};
		actionsHtml = `<div class="clanspress-notification__status">${ statusLabels[ notification.status ] || notification.status }</div>`;
	}

	let unreadDot = '';
	if ( isUnread && ! isActionable ) {
		unreadDot = '<div class="clanspress-notification__unread-dot"></div>';
	}

	return `
		<div class="${ classes }" data-notification-id="${ notification.id }">
			${ avatarHtml }
			<div class="clanspress-notification__content">
				<div class="clanspress-notification__header">
					${ titleHtml }
					<span class="clanspress-notification__time">${ escapeHtml( timeAgo ) }</span>
				</div>
				${ messageHtml }
				${ actionsHtml }
			</div>
			${ unreadDot }
		</div>
	`;
}

/**
 * Render the notifications list into the DOM.
 *
 * @param {Object}      ctx Context object with notifications and i18n.
 * @param {HTMLElement} ref Block element reference.
 */
function renderNotificationsList( ctx, ref ) {
	if ( ! ref ) {
		return;
	}

	const listEl = ref.querySelector( '.clanspress-notification-bell__list' );
	if ( ! listEl ) {
		return;
	}

	if ( ! ctx.notifications || ctx.notifications.length === 0 ) {
		listEl.innerHTML = `<p class="clanspress-notification-bell__empty">${ ctx.i18n?.noNotifications || 'No notifications yet.' }</p>`;
		return;
	}

	listEl.innerHTML = ctx.notifications.map( ( n ) => renderNotificationItem( n, ctx.i18n ) ).join( '' );
}

/**
 * Show a toast notification.
 *
 * @param {string} message Toast message.
 * @param {string} type    Toast type ('info', 'success', 'error').
 */
function showToast( message, type = 'info' ) {
	/**
	 * Filter to customize toast notification display.
	 */
	const handled = window.wp?.hooks?.applyFilters?.(
		'clanspress.notifications.showToast',
		false,
		message,
		type
	);

	if ( handled ) {
		return;
	}

	// Simple fallback toast.
	const toast = document.createElement( 'div' );
	toast.className = `clanspress-toast clanspress-toast--${ type }`;
	toast.textContent = message;
	toast.style.cssText = `
		position: fixed;
		bottom: 20px;
		right: 20px;
		padding: 12px 20px;
		background: ${ type === 'error' ? '#d63638' : '#00a32a' };
		color: white;
		border-radius: 4px;
		z-index: 100000;
		animation: fadeIn 0.3s ease;
	`;

	document.body.appendChild( toast );

	setTimeout( () => {
		toast.style.opacity = '0';
		toast.style.transition = 'opacity 0.3s ease';
		setTimeout( () => toast.remove(), 300 );
	}, 3000 );
}

const { state, actions, callbacks } = store( 'clanspress/notification-bell', {
	state: {
		get hasUnread() {
			const ctx = getContext();
			return ctx.unreadCount > 0;
		},
	},

	actions: {
		toggleDropdown( event ) {
			event.stopPropagation();
			const ctx = getContext();
			ctx.isOpen = ! ctx.isOpen;

			if ( ctx.isOpen ) {
				actions.loadNotifications();
			}
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

		async loadNotifications() {
			const ctx = getContext();
			const { ref } = getElement();
			ctx.isLoading = true;

			try {
				const response = await restFetch( ctx.restUrl, ctx.nonce, {
					path: `notifications?per_page=${ ctx.dropdownCount }`,
				} );

				ctx.notifications = response.notifications || [];
				ctx.unreadCount = response.unread_count || 0;

				if ( ctx.notifications.length > 0 ) {
					ctx.lastId = ctx.notifications[ 0 ].id;
				}

				renderNotificationsList( ctx, ref );
			} catch ( error ) {
				console.error( 'Failed to load notifications:', error );
			} finally {
				ctx.isLoading = false;
			}
		},

		async markAllRead() {
			const ctx = getContext();
			const { ref } = getElement();

			try {
				await restFetch( ctx.restUrl, ctx.nonce, {
					path: 'notifications/read-all',
					method: 'POST',
				} );

				ctx.unreadCount = 0;
				ctx.notifications = ctx.notifications.map( ( n ) => ( {
					...n,
					is_read: true,
				} ) );

				renderNotificationsList( ctx, ref );
			} catch ( error ) {
				console.error( 'Failed to mark all as read:', error );
			}
		},

		async markRead( event ) {
			const notificationId = event.target.closest( '[data-notification-id]' )?.dataset?.notificationId;
			if ( ! notificationId ) {
				return;
			}

			const ctx = getContext();
			const { ref } = getElement();

			try {
				const response = await restFetch( ctx.restUrl, ctx.nonce, {
					path: `notifications/${ notificationId }/read`,
					method: 'POST',
				} );

				ctx.unreadCount = response.unread_count || 0;

				const idx = ctx.notifications.findIndex( ( n ) => n.id === parseInt( notificationId, 10 ) );
				if ( idx !== -1 ) {
					ctx.notifications[ idx ].is_read = true;
				}

				renderNotificationsList( ctx, ref );
			} catch ( error ) {
				console.error( 'Failed to mark as read:', error );
			}
		},

		async executeAction( event ) {
			const button = event.target.closest( '[data-action]' );
			if ( ! button ) {
				return;
			}

			const notificationId = button.dataset.notificationId;
			const actionKey = button.dataset.action;
			const confirmMsg = button.dataset.confirm;

			if ( confirmMsg && ! window.confirm( confirmMsg ) ) {
				return;
			}

			const ctx = getContext();
			const { ref } = getElement();
			button.disabled = true;

			try {
				const response = await restFetch( ctx.restUrl, ctx.nonce, {
					path: `notifications/${ notificationId }/action`,
					method: 'POST',
					data: { action: actionKey },
				} );

				ctx.unreadCount = response.unread_count || 0;

				// Remove or update the notification.
				const idx = ctx.notifications.findIndex( ( n ) => n.id === parseInt( notificationId, 10 ) );
				if ( idx !== -1 ) {
					ctx.notifications[ idx ].is_actionable = false;
					ctx.notifications[ idx ].is_read = true;
					ctx.notifications[ idx ].status = response.status || 'dismissed';
				}

				renderNotificationsList( ctx, ref );

				// Show success message.
				if ( response.message ) {
					showToast( response.message, 'success' );
				}

				// Handle redirect if provided.
				if ( response.redirect ) {
					window.location.href = response.redirect;
				}
			} catch ( error ) {
				console.error( 'Failed to execute action:', error );
				showToast( error.message || 'Action failed', 'error' );
			} finally {
				button.disabled = false;
			}
		},
	},

	callbacks: {
		init() {
			const ctx = getContext();
			const { ref } = getElement();

			// Initialize notifications array if not set.
			if ( ! ctx.notifications ) {
				ctx.notifications = [];
			}

			// Store element reference for use in async callbacks.
			ctx._ref = ref;

			// Fetch initial count immediately.
			fetchInitialCount( ctx );

			/**
			 * Filter to provide alternative sync providers (e.g., WebSocket).
			 *
			 * Uses the same 'sync.providers' pattern as WordPress 7.0 RTC.
			 * Providers should implement: { subscribe( channel, callback ), unsubscribe( channel ) }
			 *
			 * @param {Array}  providers Array of sync provider objects.
			 * @param {string} channel   The channel name ('clanspress.notifications').
			 */
			const providers = window.wp?.hooks?.applyFilters?.( 'sync.providers', [], 'clanspress.notifications' ) || [];
			const wsProvider = providers.find( ( p ) => p && typeof p.subscribe === 'function' );

			if ( wsProvider ) {
				// Use WebSocket provider.
				ctx.syncProvider = wsProvider;
				initSyncProvider( ctx );
			} else {
				// Fall back to HTTP long polling.
				startPolling( ctx );
			}
		},
	},
} );

/**
 * Fetch initial notification count.
 *
 * @param {Object} ctx Context object.
 */
async function fetchInitialCount( ctx ) {
	try {
		const response = await restFetch( ctx.restUrl, ctx.nonce, {
			path: 'notifications/count',
		} );

		ctx.unreadCount = response.unread_count || 0;
		ctx.lastTimestamp = response.timestamp;
	} catch ( error ) {
		// Silently fail - the badge will just show the server-rendered count.
		console.error( 'Failed to fetch notification count:', error );
	}
}

/**
 * Start HTTP long polling for notifications.
 *
 * @param {Object} ctx Context object.
 */
function startPolling( ctx ) {
	const poll = async () => {
		// Don't poll if sync provider is active.
		if ( ctx.syncProviderActive ) {
			return;
		}

		try {
			const params = new URLSearchParams( {
				last_id: ctx.lastId || 0,
				timeout: 30,
			} );

			if ( ctx.lastTimestamp && ! ctx.lastId ) {
				params.set( 'since', ctx.lastTimestamp );
			}

			const response = await restFetch( ctx.restUrl, ctx.nonce, {
				path: `notifications/poll?${ params.toString() }`,
			} );

			// Update state with new notifications.
			if ( response.notifications && response.notifications.length > 0 ) {
				ctx.notifications = [
					...response.notifications,
					...( ctx.notifications || [] ),
				].slice( 0, ctx.dropdownCount );

				ctx.lastId = response.notifications[ 0 ].id;

				// Re-render if dropdown is open.
				if ( ctx.isOpen && ctx._ref ) {
					renderNotificationsList( ctx, ctx._ref );
				}

				// Fire event for other components.
				window.wp?.hooks?.doAction?.(
					'clanspress.notifications.received',
					response.notifications
				);
			}

			ctx.unreadCount = response.unread_count ?? ctx.unreadCount;
			ctx.lastTimestamp = response.timestamp;
			ctx.pollInterval = response.next_poll || 4000;
		} catch ( error ) {
			// Increase interval on error.
			ctx.pollInterval = Math.min( ctx.pollInterval * 2, 30000 );
			console.error( 'Notification poll failed:', error );
		}

		// Schedule next poll.
		setTimeout( poll, ctx.pollInterval );
	};

	// Start polling immediately.
	poll();
}

/**
 * Initialize WebSocket sync provider.
 *
 * @param {Object} ctx Context object.
 */
function initSyncProvider( ctx ) {
	const provider = ctx.syncProvider;

	if ( ! provider || typeof provider.subscribe !== 'function' ) {
		startPolling( ctx );
		return;
	}

	try {
		provider.subscribe( 'clanspress.notifications', ( data ) => {
			if ( data.type === 'notification' && data.notification ) {
				ctx.notifications = [
					data.notification,
					...( ctx.notifications || [] ),
				].slice( 0, ctx.dropdownCount );

				ctx.unreadCount = data.unread_count || ctx.unreadCount + 1;
				ctx.lastId = data.notification.id;

				if ( ctx.isOpen && ctx._ref ) {
					renderNotificationsList( ctx, ctx._ref );
				}

				window.wp?.hooks?.doAction?.(
					'clanspress.notifications.received',
					[ data.notification ]
				);
			} else if ( data.type === 'count' ) {
				ctx.unreadCount = data.unread_count || 0;
			}
		} );

		ctx.syncProviderActive = true;
	} catch ( error ) {
		console.error( 'Failed to initialize sync provider:', error );
		startPolling( ctx );
	}
}
