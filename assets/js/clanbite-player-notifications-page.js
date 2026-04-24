/**
 * Mark single notification read before following notification links (player notifications subpage).
 *
 * @package clanbite
 */
(function () {
	'use strict';

	const i18n =
		typeof clanbitePlayerNotificationsPageI18n === 'object' &&
		clanbitePlayerNotificationsPageI18n !== null
			? clanbitePlayerNotificationsPageI18n
			: {};

	const root = document.querySelector(
		'.clanbite-notifications-page[data-clanbite-notifications-rest]'
	);
	if (!root) {
		return;
	}
	const restBase = root.getAttribute('data-clanbite-notifications-rest');
	const nonce = root.getAttribute('data-clanbite-notifications-nonce');
	if (!restBase || !nonce) {
		return;
	}

	function isPlainLeftClick(ev) {
		return (
			ev.button === 0 &&
			!ev.metaKey &&
			!ev.ctrlKey &&
			!ev.shiftKey &&
			!ev.altKey
		);
	}

	root.addEventListener('click', function (ev) {
		const a = ev.target.closest(
			'a.clanbite-notification__link[data-notification-id]'
		);
		if (!a || !root.contains(a)) {
			return;
		}
		if (!isPlainLeftClick(ev)) {
			return;
		}
		const id = a.getAttribute('data-notification-id');
		if (!id) {
			return;
		}
		const row = a.closest('.clanbite-notification');
		if (row && row.classList.contains('is-read')) {
			return;
		}
		ev.preventDefault();
		const href = a.getAttribute('href');
		const url =
			restBase.replace(/\/?$/, '/') +
			'notifications/' +
			encodeURIComponent(id) +
			'/read';
		fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce,
			},
		})
			.then(function (res) {
				if (!res.ok) {
					return res.text().then(function (body) {
						var msg = body ? body.slice(0, 200) : '';
						throw new Error(msg || 'HTTP ' + res.status);
					});
				}
			})
			.then(function () {
				if (href) {
					window.location.assign(href);
				}
			})
			.catch(function (err) {
				// eslint-disable-next-line no-console
				console.error('Clanbite: failed to mark notification as read', err);
				var note = document.createElement('p');
				note.className = 'clanbite-notifications-page__read-error';
				note.setAttribute('role', 'alert');
				note.textContent =
					i18n.markReadError ||
					'Could not mark this notification as read. Opening the link anyway — see the browser console for details.';
				var header = root.querySelector('.clanbite-notifications-page__header');
				if (header && header.nextSibling) {
					root.insertBefore(note, header.nextSibling);
				} else {
					root.insertBefore(note, root.firstChild);
				}
				setTimeout(function () {
					if (href) {
						window.location.assign(href);
					}
				}, 2000);
			});
	});
})();
