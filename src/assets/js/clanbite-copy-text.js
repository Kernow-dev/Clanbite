/**
 * Clanbite clipboard client: copy text, with the legacy fallback for browsers or contexts without the async clipboard API.
 *
 *   const ok = await clanbite.clipboard.copy( url );
 *
 * @param {Window} window Browser window.
 */
( function ( window ) {
	'use strict';

	/**
	 * Copy text with a temporary textarea and `execCommand`.
	 *
	 * @param {string} text Text to copy.
	 * @return {boolean} True when the browser reported success.
	 */
	function legacyCopy( text ) {
		const doc = window.document;
		const area = doc.createElement( 'textarea' );
		area.value = text;
		area.setAttribute( 'readonly', '' );
		area.style.position = 'fixed';
		area.style.left = '-9999px';
		doc.body.appendChild( area );
		area.select();
		let ok = false;
		try {
			ok = doc.execCommand( 'copy' );
		} catch {
			ok = false;
		}
		doc.body.removeChild( area );
		return ok;
	}

	/**
	 * Copy text to the clipboard.
	 *
	 * @param {string} text Text to copy.
	 * @return {Promise<boolean>} True when copied.
	 */
	async function copy( text ) {
		const value = String( text || '' );
		if ( ! value ) {
			return false;
		}
		if ( window.navigator.clipboard?.writeText ) {
			try {
				await window.navigator.clipboard.writeText( value );
				return true;
			} catch {
				// Fall through to the legacy path.
			}
		}
		return legacyCopy( value );
	}

	const api = { copy };
	window.clanbite = window.clanbite || {};
	window.clanbite.clipboard = api;
	window.clanbite.components?.register( 'clipboard', api );
} )( window );
