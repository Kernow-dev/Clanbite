/**
 * Clanbite toasts (script handle `clanbite-toasts`).
 *
 * One popup system for Clanbite and its extensions, so every popup shares the same position, timing, stacking,
 * accessibility and look. Site owners choose the position, length, hover pause and countdown bar once (Clanbite →
 * Settings → Notifications → Popups); extensions only say what to show.
 *
 *   clanbite.toasts.show( {
 *       type: 'success',                  // info (default) | success | warning | error
 *       title: 'Level up!',
 *       message: 'Corporal -> Sergeant',  // plain text, never HTML
 *       icon: 'https://example.com/rank.svg',
 *       progress: 70,                     // optional 0-100 bar (for example XP towards the next rank)
 *       actions: [ { label: 'View', href: '/players/me/' } ],
 *   } );
 *
 * The API is a plain global, so classic scripts, `wp.element` apps and Interactivity view modules can all use it.
 * Enqueue the `clanbite-toasts` handle (or call `clanbite_enqueue_toasts()` in PHP) first.
 *
 * Everything is filterable through `wp.hooks` (`clanbite.toasts.*`); see the docblocks below and README, "Toasts".
 *
 * @param {Window}   window   Browser window.
 * @param {Document} document Document.
 */
( function ( window, document ) {
	'use strict';

	const config = window.clanbiteToastConfig || {};
	const i18n = config.i18n || {};

	const DEFAULT_POSITIONS = [
		'top-left',
		'top-center',
		'top-right',
		'bottom-left',
		'bottom-center',
		'bottom-right',
	];
	const TYPES = [ 'info', 'success', 'warning', 'error' ];
	const FALLBACK_DURATION = 5000;
	const ERROR_MIN_DURATION = 8000;
	const FALLBACK_MAX = 4;
	const EXIT_MS = 200;

	const stacks = {};
	const live = new Map();
	let sequence = 0;

	function hooks() {
		return window.wp && window.wp.hooks ? window.wp.hooks : null;
	}

	/**
	 * Positions the library accepts: the six built-in ones, or the list the server sent (`clanbite_toast_positions`).
	 *
	 * @return {string[]} Position slugs.
	 */
	function allowedPositions() {
		return Array.isArray( config.positions ) && config.positions.length
			? config.positions
			: DEFAULT_POSITIONS;
	}

	function numberOr( value, fallback ) {
		const n = Number( value );
		return Number.isFinite( n ) ? n : fallback;
	}

	/**
	 * Site-wide behaviour for one toast: the server's settings, adjustable per toast by a filter.
	 *
	 * @param {Object} options Options passed to `show()`.
	 * @return {{enabled: boolean, position: string, duration: number, max: number, timer: boolean, pauseOnHover: boolean}} Effective settings.
	 */
	function siteSettings( options ) {
		let site = {
			enabled: config.enabled !== false,
			position: allowedPositions().includes( config.position )
				? config.position
				: 'top-right',
			duration: Math.max(
				0,
				numberOr( config.duration, FALLBACK_DURATION )
			),
			max: Math.max( 1, numberOr( config.max, FALLBACK_MAX ) ),
			timer: config.showTimer !== false,
			pauseOnHover: config.pauseOnHover !== false,
		};
		const h = hooks();
		if ( h && h.applyFilters ) {
			/**
			 * Filters the site-wide popup settings for one toast, after the server's values are applied.
			 *
			 * Use it to change the defaults for particular toasts (for example a longer time for one `source`). Settings
			 * an extension passes to `show()` itself still win over these.
			 *
			 * @param {Object} settings `enabled`, `position`, `duration` (ms, 0 = until closed), `max` (toasts per corner), `timer` (countdown bar), `pauseOnHover`.
			 * @param {Object} options  The options passed to `clanbite.toasts.show()`.
			 */
			const filtered = h.applyFilters(
				'clanbite.toasts.settings',
				site,
				options
			);
			if ( filtered && typeof filtered === 'object' ) {
				site = { ...site, ...filtered };
			}
		}
		return site;
	}

	/**
	 * Only http(s) and root-relative URLs may be used for icons and action links (no `javascript:`, `data:` or `//host`).
	 *
	 * @param {*}      url     Candidate URL.
	 * @param {string} context `icon` or `action`.
	 * @return {string} The URL, or an empty string when it is not allowed.
	 */
	function safeUrl( url, context ) {
		const value = String( url || '' ).trim();
		let allowed = /^(https?:\/\/|\/(?!\/))/i.test( value );
		const h = hooks();
		if ( value && h && h.applyFilters ) {
			/**
			 * Filters whether a URL may be used in a toast (icon image or action link).
			 *
			 * The default allows `http(s)://` and root-relative URLs only. Return `true` to allow another scheme you trust
			 * (for example a `data:` image), or `false` to block a host.
			 *
			 * @param {boolean} allowed Default decision.
			 * @param {string}  url     The URL.
			 * @param {string}  context `icon` or `action`.
			 */
			allowed = !! h.applyFilters(
				'clanbite.toasts.allowedUrl',
				allowed,
				value,
				context
			);
		}
		return allowed ? value : '';
	}

	function getStack( position ) {
		let stack = stacks[ position ];
		if ( ! stack ) {
			stack = document.createElement( 'div' );
			stack.className = `clanbite-toast-stack clanbite-toast-stack--${ position }`;
			stack.dataset.position = position;
			stack.setAttribute( 'role', 'region' );
			stack.setAttribute( 'aria-label', i18n.region || 'Notifications' );
			stacks[ position ] = stack;
		}
		let parent = document.body;
		const h = hooks();
		if ( h && h.applyFilters ) {
			/**
			 * Filters the element the toast stacks are added to (default `document.body`).
			 *
			 * Checked every time a toast is shown, and an existing stack is moved when the answer changes, so you can
			 * return a dialog or full-screen element only while it is open. Return another element when toasts must live
			 * inside something else, for example a dialog that makes the rest of the page inert.
			 *
			 * @param {HTMLElement} parent   Default parent.
			 * @param {string}      position The stack's position slug.
			 */
			const filtered = h.applyFilters(
				'clanbite.toasts.container',
				parent,
				position
			);
			if ( filtered && filtered.appendChild ) {
				parent = filtered;
			}
		}
		if ( stack.parentNode !== parent ) {
			parent.appendChild( stack );
		}
		return stack;
	}

	function normalize( options, site ) {
		const o = options && typeof options === 'object' ? options : {};
		const type = TYPES.includes( o.type ) ? o.type : 'info';
		let duration = Number( o.duration );
		if ( ! Number.isFinite( duration ) || duration < 0 ) {
			duration = site.duration;
			// Errors stay a little longer by default; they are rarely read in 5 seconds.
			if ( type === 'error' && duration > 0 ) {
				duration = Math.max( duration, ERROR_MIN_DURATION );
			}
		}
		const progress = Number( o.progress );
		const positions = allowedPositions();
		return {
			id: o.id ? String( o.id ) : '',
			type,
			title: String( o.title || '' ),
			message: String( o.message || '' ),
			icon: safeUrl( o.icon, 'icon' ),
			progress:
				o.progress !== undefined &&
				o.progress !== null &&
				Number.isFinite( progress )
					? Math.max( 0, Math.min( 100, progress ) )
					: null,
			duration,
			// Per-toast overrides; unset follows the site settings.
			timer: o.timer === undefined ? !! site.timer : !! o.timer,
			pauseOnHover:
				o.pauseOnHover === undefined
					? !! site.pauseOnHover
					: !! o.pauseOnHover,
			position: positions.includes( o.position )
				? o.position
				: site.position,
			dismissible: o.dismissible !== false,
			actions: Array.isArray( o.actions ) ? o.actions : [],
			className: String( o.className || '' ),
			source: String( o.source || '' ),
		};
	}

	function build( payload, handle ) {
		const item = document.createElement( 'div' );
		item.className = `clanbite-toast-item clanbite-toast-item--${ payload.type }`;
		if ( payload.className ) {
			item.className += ` ${ payload.className }`;
		}
		// Errors interrupt screen readers; everything else waits its turn.
		item.setAttribute(
			'role',
			payload.type === 'error' ? 'alert' : 'status'
		);
		if ( payload.id ) {
			item.dataset.toastId = payload.id;
		}
		if ( payload.source ) {
			item.dataset.source = payload.source;
		}

		if ( payload.icon ) {
			const icon = document.createElement( 'img' );
			icon.className = 'clanbite-toast-item__icon';
			icon.src = payload.icon;
			icon.alt = '';
			icon.width = 40;
			icon.height = 40;
			icon.decoding = 'async';
			item.appendChild( icon );
		}

		const body = document.createElement( 'div' );
		body.className = 'clanbite-toast-item__body';
		if ( payload.title ) {
			const title = document.createElement( 'p' );
			title.className = 'clanbite-toast-item__title';
			title.textContent = payload.title;
			body.appendChild( title );
		}
		if ( payload.message ) {
			const message = document.createElement( 'p' );
			message.className = 'clanbite-toast-item__message';
			message.textContent = payload.message;
			body.appendChild( message );
		}
		if ( payload.progress !== null ) {
			const track = document.createElement( 'div' );
			track.className = 'clanbite-toast-item__track';
			const fill = document.createElement( 'span' );
			fill.className = 'clanbite-toast-item__fill';
			track.appendChild( fill );
			body.appendChild( track );
			window.requestAnimationFrame( () => {
				fill.style.width = `${ payload.progress }%`;
			} );
		}
		const actions = payload.actions.filter(
			( a ) => a && a.label && ( safeUrl( a.href, 'action' ) || a.onClick )
		);
		if ( actions.length ) {
			const row = document.createElement( 'div' );
			row.className = 'clanbite-toast-item__actions';
			actions.forEach( ( a ) => {
				const href = safeUrl( a.href, 'action' );
				const control = document.createElement( href ? 'a' : 'button' );
				control.className = 'clanbite-toast-item__action';
				control.textContent = String( a.label );
				if ( href ) {
					control.href = href;
				} else {
					control.type = 'button';
				}
				control.addEventListener( 'click', ( event ) => {
					if ( typeof a.onClick === 'function' ) {
						a.onClick( event, handle );
					}
					if ( ! a.keepOpen ) {
						handle.dismiss( 'action' );
					}
				} );
				row.appendChild( control );
			} );
			body.appendChild( row );
		}
		item.appendChild( body );

		// Countdown bar: fills as the toast nears expiry. A CSS animation of exactly the toast's length, so pausing the
		// toast (`is-paused`) freezes the bar in step with the real timer. Toasts that stay until closed have none.
		if ( payload.timer && payload.duration > 0 ) {
			const timerTrack = document.createElement( 'div' );
			timerTrack.className = 'clanbite-toast-item__timer';
			timerTrack.setAttribute( 'aria-hidden', 'true' );
			const timerFill = document.createElement( 'span' );
			timerFill.className = 'clanbite-toast-item__timer-fill';
			timerFill.style.animationDuration = `${ payload.duration }ms`;
			timerTrack.appendChild( timerFill );
			item.appendChild( timerTrack );
		}

		if ( payload.dismissible ) {
			const close = document.createElement( 'button' );
			close.type = 'button';
			close.className = 'clanbite-toast-item__close';
			close.setAttribute( 'aria-label', i18n.close || 'Close' );
			close.textContent = '×';
			close.addEventListener( 'click', () => handle.dismiss( 'close' ) );
			item.appendChild( close );
		}
		return item;
	}

	/**
	 * Show a toast.
	 *
	 * Returns `null` when toasts are switched off in the settings, a filter suppressed the toast, or there is nothing
	 * to show. Showing a toast with the `id` of one that is still on screen replaces that toast instead of stacking a
	 * duplicate.
	 *
	 * @param {Object}   options                Toast options.
	 * @param {string}   [options.id]           Stable id; de-duplicates and lets you dismiss it later.
	 * @param {string}   [options.type]         `info` (default), `success`, `warning` or `error`.
	 * @param {string}   [options.title]        Bold first line (plain text).
	 * @param {string}   [options.message]      Body text (plain text).
	 * @param {string}   [options.icon]         http(s) or root-relative image URL.
	 * @param {number}   [options.progress]     0-100 to show a bar (not the countdown).
	 * @param {number}   [options.duration]     Milliseconds on screen; `0` stays until closed. Defaults to the site setting.
	 * @param {string}   [options.position]     One of `top-left` … `bottom-right`. Leave unset to follow the site setting.
	 * @param {boolean}  [options.dismissible]  Show a close button (default `true`).
	 * @param {boolean}  [options.timer]        Show the countdown bar. Defaults to the site setting; never shown when `duration` is 0.
	 * @param {boolean}  [options.pauseOnHover] Pause the countdown while hovered. Defaults to the site setting.
	 * @param {Object[]} [options.actions]      `{ label, href | onClick(event, handle), keepOpen }` links or buttons.
	 * @param {string}   [options.className]    Extra class on the toast element.
	 * @param {string}   [options.source]       Your plugin slug, for styling, filtering and debugging (`data-source`).
	 * @return {{id: string, element: HTMLElement, dismiss: Function}|null} Handle, or `null` when nothing was shown.
	 */
	function show( options ) {
		const site = siteSettings( options );
		if ( site.enabled === false ) {
			return null;
		}
		let payload = normalize( options, site );
		const h = hooks();
		if ( h && h.applyFilters ) {
			/**
			 * Filters a toast's options before it is shown.
			 *
			 * Use it to adjust wording, duration or position for every toast, or only one `source`. Return the (modified)
			 * options object.
			 *
			 * @param {Object} payload Normalised options: `id`, `type`, `title`, `message`, `icon`, `progress`, `duration` (ms), `timer`, `pauseOnHover`, `position`, `dismissible`, `actions`, `className`, `source`.
			 */
			payload = normalize(
				h.applyFilters( 'clanbite.toasts.payload', payload ),
				site
			);
			/**
			 * Filters whether a toast is shown. Return `false` to suppress it.
			 *
			 * @param {boolean} show    Default `true`.
			 * @param {Object}  payload Normalised toast options (see `clanbite.toasts.payload`).
			 */
			if (
				h.applyFilters( 'clanbite.toasts.shouldShow', true, payload ) ===
				false
			) {
				return null;
			}
		}
		if ( ! payload.title && ! payload.message ) {
			return null;
		}

		if ( payload.id && live.has( payload.id ) ) {
			live.get( payload.id ).dismiss( 'replaced', true );
		}

		sequence += 1;
		let timer = 0;
		let remaining = payload.duration;
		let startedAt = 0;
		let closed = false;
		const stack = getStack( payload.position );
		const handle = {
			id: payload.id || `toast-${ sequence }`,
			element: null,
			dismiss( reason = 'api', immediate = false ) {
				if ( closed ) {
					return;
				}
				closed = true;
				window.clearTimeout( timer );
				if ( live.get( handle.id ) === handle ) {
					live.delete( handle.id );
				}
				const el = handle.element;
				const remove = () => el.remove();
				if ( immediate ) {
					remove();
				} else {
					el.classList.add( 'is-leaving' );
					window.setTimeout( remove, EXIT_MS );
				}
				if ( h && h.doAction ) {
					/**
					 * Fires after a toast is dismissed.
					 *
					 * @param {{id: string, element: HTMLElement}} handle  The toast handle.
					 * @param {Object}                             payload Toast options.
					 * @param {string}                             reason  `timeout`, `close`, `escape`, `action`, `replaced`, `overflow` or `api`.
					 */
					h.doAction( 'clanbite.toasts.dismissed', handle, payload, reason );
				}
			},
		};

		let element = null;
		if ( h && h.applyFilters ) {
			/**
			 * Lets a handler build the toast element itself (custom markup).
			 *
			 * Return an `HTMLElement` to use it instead of the default. Clanbite still places it in the stack and runs the
			 * timer, hover pause, `Escape` and overflow handling; add your own close control that calls `handle.dismiss()`.
			 * Return `null` (the default) for the standard toast.
			 *
			 * @param {HTMLElement|null}                   element Default `null`.
			 * @param {Object}                             payload Toast options.
			 * @param {{id: string, element: HTMLElement}} handle  The toast handle (`element` is set after this filter).
			 */
			const custom = h.applyFilters(
				'clanbite.toasts.element',
				null,
				payload,
				handle
			);
			if ( custom && custom.nodeType === 1 ) {
				element = custom;
			}
		}
		handle.element = element || build( payload, handle );
		handle.element.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' && payload.dismissible ) {
				handle.dismiss( 'escape' );
			}
		} );

		const startTimer = () => {
			if ( remaining > 0 ) {
				startedAt = Date.now();
				timer = window.setTimeout(
					() => handle.dismiss( 'timeout' ),
					remaining
				);
			}
		};
		// Hovering a toast (when `pauseOnHover` is on) or focusing it with the keyboard pauses its countdown, so it can
		// be read and clicked. Keyboard focus always pauses: nobody should lose a toast they are tabbing through.
		let hovered = false;
		let focused = false;
		const pause = () => {
			if ( remaining > 0 && timer ) {
				window.clearTimeout( timer );
				timer = 0;
				remaining = Math.max( 1, remaining - ( Date.now() - startedAt ) );
			}
			handle.element.classList.add( 'is-paused' );
		};
		const resume = () => {
			if ( closed || hovered || focused ) {
				return;
			}
			handle.element.classList.remove( 'is-paused' );
			if ( ! timer && payload.duration > 0 ) {
				startTimer();
			}
		};
		handle.element.addEventListener( 'mouseenter', () => {
			if ( payload.pauseOnHover ) {
				hovered = true;
				pause();
			}
		} );
		handle.element.addEventListener( 'mouseleave', () => {
			hovered = false;
			resume();
		} );
		handle.element.addEventListener( 'focusin', () => {
			focused = true;
			pause();
		} );
		handle.element.addEventListener( 'focusout', () => {
			focused = false;
			resume();
		} );

		// Top stacks grow downwards from the newest toast; bottom stacks grow upwards.
		if ( payload.position.startsWith( 'top' ) ) {
			stack.insertBefore( handle.element, stack.firstChild );
		} else {
			stack.appendChild( handle.element );
		}
		live.set( handle.id, handle );
		startTimer();

		// Keep the screen tidy: the oldest toast in this corner makes room.
		const items = Array.from( stack.children ).filter(
			( el ) => ! el.classList.contains( 'is-leaving' )
		);
		if ( items.length > site.max ) {
			const oldest = payload.position.startsWith( 'top' )
				? items[ items.length - 1 ]
				: items[ 0 ];
			live.forEach( ( other ) => {
				if ( other.element === oldest ) {
					other.dismiss( 'overflow', true );
				}
			} );
		}

		if ( h && h.doAction ) {
			/**
			 * Fires after a toast is on screen.
			 *
			 * @param {{id: string, element: HTMLElement}} handle  The toast handle.
			 * @param {Object}                             payload Toast options.
			 */
			h.doAction( 'clanbite.toasts.shown', handle, payload );
		}
		return handle;
	}

	/**
	 * Dismiss one toast by id, or every toast when no id is given.
	 *
	 * @param {string} [id] Toast id.
	 */
	function dismiss( id ) {
		if ( id ) {
			const handle = live.get( String( id ) );
			if ( handle ) {
				handle.dismiss( 'api' );
			}
			return;
		}
		Array.from( live.values() ).forEach( ( handle ) =>
			handle.dismiss( 'api' )
		);
	}

	window.clanbite = window.clanbite || {};
	window.clanbite.toasts = {
		show,
		dismiss,
		positions: allowedPositions().slice(),
		types: TYPES.slice(),
	};
} )( window, document );
