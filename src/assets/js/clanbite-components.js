/**
 * Clanbite components registry (browser half).
 *
 * Extensions register the browser side of a component here (`clanbite.components.register( 'reactions', { toggle, apply } )`) and
 * other extensions ask for it (`clanbite.components.get( 'reactions' )?.toggle( … )`) instead of reaching into another plugin's
 * globals. A missing component returns `null`, so a consumer degrades quietly.
 *
 * Hooks (wp.hooks): action `clanbite.components.registered` ( slug, api ).
 *
 * @param {Window} window Browser window.
 */
( function ( window ) {
	'use strict';

	const registry = {};
	const hooks = window.wp && window.wp.hooks;

	/**
	 * Register a component's browser API.
	 *
	 * @param {string}  slug      Component slug, the same one registered in PHP.
	 * @param {Object}  api       What the component exposes.
	 * @param {boolean} [replace] True to replace an API registered under the same slug.
	 * @return {boolean} True when registered.
	 */
	function register( slug, api, replace = false ) {
		const key = String( slug || '' );
		if ( ! key || ! api || ( registry[ key ] && ! replace ) ) {
			return false;
		}
		registry[ key ] = api;
		if ( hooks ) {
			/**
			 * Fires when a component registers its browser API.
			 *
			 * @param {string} slug Component slug.
			 * @param {Object} api  The registered API.
			 */
			hooks.doAction( 'clanbite.components.registered', key, api );
		}
		return true;
	}

	/**
	 * A component's browser API.
	 *
	 * @param {string} slug Component slug.
	 * @return {Object|null} The API, or null when nothing registered it.
	 */
	function get( slug ) {
		return registry[ String( slug || '' ) ] || null;
	}

	/**
	 * Whether a component has registered a browser API.
	 *
	 * @param {string} slug Component slug.
	 * @return {boolean} True when registered.
	 */
	function has( slug ) {
		return get( slug ) !== null;
	}

	window.clanbite = window.clanbite || {};
	window.clanbite.components = { register, get, has };
} )( window );
