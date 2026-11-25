/**
 * Clanbite skeletons (browser half).
 *
 * `clanbite.skeletons.render( 'avatar', { size: 'small', shape: 'hexagon' } )` returns the same markup the PHP function
 * `clanbite_render_skeleton()` prints, for places the browser builds (Interactivity templates, lists that load more). The classes,
 * sizes and shimmer come from the shared `clanbite-skeleton` stylesheet, so a skeleton drawn here looks exactly like one drawn by PHP.
 *
 * Hooks (wp.hooks):
 * - filter `clanbite.skeleton.args` ( args, type ).
 * - filter `clanbite.skeleton.html` ( html, type, args ): restyle or replace any skeleton.
 *
 * @param {Window} window Browser window.
 */
( function ( window ) {
	'use strict';

	const registry = {};
	const hooks = window.wp && window.wp.hooks;

	/**
	 * Make a value safe for a CSS class name.
	 *
	 * @param {unknown} value Candidate class.
	 * @return {string} Letters, digits, dashes and underscores only.
	 */
	function cls( value ) {
		return String( value || '' ).replace( /[^a-z0-9_-]/gi, '' );
	}

	/**
	 * A plain CSS length (number, optionally with px, %, rem, em, vw, vh or ch); numbers are pixels.
	 *
	 * @param {unknown} value Candidate length.
	 * @return {string} The length, or an empty string when it is not one.
	 */
	function length( value ) {
		const v = String( value ?? '' ).trim();
		if ( ! /^\d+(\.\d+)?(px|%|rem|em|vw|vh|ch)?$/.test( v ) ) {
			return '';
		}
		return /^\d+(\.\d+)?$/.test( v ) ? `${ v }px` : v;
	}

	/**
	 * One primitive, the browser twin of PHP `clanbite_skeleton_element()`.
	 *
	 * @param {string} shape `line`, `block`, `circle` or `avatar`.
	 * @param {Object} args  Options: width, height, radius, size, class, type, context.
	 * @return {string} HTML.
	 */
	function element( shape, args = {} ) {
		const kind = [ 'line', 'block', 'circle', 'avatar' ].includes( shape )
			? shape
			: 'block';
		const classes = [ 'clanbite-skeleton', `clanbite-skeleton--${ kind }` ];
		if ( args.context ) {
			classes.push( `is-${ cls( args.context ) }` );
		}
		String( args.class || '' )
			.split( /\s+/ )
			.forEach( ( c ) => c && classes.push( cls( c ) ) );

		const style = [];
		if ( kind === 'circle' && length( args.size ) ) {
			style.push( `--clanbite-skeleton-width:${ length( args.size ) }` );
			style.push( `--clanbite-skeleton-height:${ length( args.size ) }` );
		}
		[
			[ 'width', '--clanbite-skeleton-width' ],
			[ 'height', '--clanbite-skeleton-height' ],
			[ 'radius', '--clanbite-skeleton-radius' ],
		].forEach( ( [ key, prop ] ) => {
			if ( length( args[ key ] ) ) {
				style.push( `${ prop }:${ length( args[ key ] ) }` );
			}
		} );

		return (
			`<span class="${ classes.filter( Boolean ).join( ' ' ) }" ` +
			`data-clanbite-skeleton="${ cls( args.type || kind ) }"` +
			( style.length ? ` style="${ style.join( ';' ) }"` : '' ) +
			' aria-hidden="true"></span>'
		);
	}

	/**
	 * Register a skeleton type.
	 *
	 * @param {string}                            type      Skeleton slug, the same one registered in PHP.
	 * @param {( ...args: unknown[] ) => unknown} build     `( args ) => string` HTML built from `element()`.
	 * @param {boolean}                           [replace] True to replace a type registered under the same slug.
	 * @return {boolean} True when registered.
	 */
	function register( type, build, replace = false ) {
		const key = cls( type );
		if (
			! key ||
			typeof build !== 'function' ||
			( registry[ key ] && ! replace )
		) {
			return false;
		}
		registry[ key ] = build;
		return true;
	}

	/**
	 * Whether a skeleton type exists.
	 *
	 * @param {string} type Skeleton slug.
	 * @return {boolean} True when registered.
	 */
	function has( type ) {
		return Boolean( registry[ cls( type ) ] );
	}

	/**
	 * Build a skeleton.
	 *
	 * @param {string} type Skeleton slug.
	 * @param {Object} args Arguments for that skeleton.
	 * @return {string} HTML, empty when no such skeleton exists.
	 */
	function render( type, args = {} ) {
		const key = cls( type );
		let a = args;
		if ( hooks ) {
			/**
			 * Filters the arguments of a skeleton before it is built.
			 *
			 * @param {Object} args Arguments passed to `clanbite.skeletons.render()`.
			 * @param {string} type Skeleton type.
			 */
			a = hooks.applyFilters( 'clanbite.skeleton.args', a, key );
		}
		if ( ! registry[ key ] ) {
			return '';
		}
		const html = registry[ key ]( { ...a, type: key } );
		if ( ! hooks ) {
			return html;
		}
		/**
		 * Filters the finished HTML of any skeleton: the one place to restyle or replace them all.
		 *
		 * @param {string} html HTML.
		 * @param {string} type Skeleton type.
		 * @param {Object} args Arguments the skeleton was built with.
		 */
		return hooks.applyFilters( 'clanbite.skeleton.html', html, key, a );
	}

	register( 'avatar', ( a ) => {
		const size = cls( a.size ) || 'medium';
		// Same classes as the PHP preset: the avatar's size and shape classes do the sizing and clipping.
		const shape = cls( a.shape ) || 'circle';
		return element( 'avatar', {
			type: 'avatar',
			context: a.context,
			class: `clanbite-avatar--${ size } clanbite-avatar--shape-${ shape }`,
		} );
	} );
	register( 'line', ( a ) => element( 'line', a ) );
	register( 'block', ( a ) => element( 'block', a ) );
	register( 'circle', ( a ) => element( 'circle', a ) );
	register( 'text', ( a ) => {
		const lines = Math.max(
			1,
			Math.min( 12, parseInt( a.lines, 10 ) || 3 )
		);
		let html = '';
		for ( let i = 1; i <= lines; i++ ) {
			html += element( 'line', {
				type: 'text',
				context: a.context,
				width: lines > 1 && i === lines ? '60%' : '100%',
			} );
		}
		return `<span class="clanbite-skeleton-group clanbite-skeleton-group--text" data-clanbite-skeleton="text" aria-hidden="true">${ html }</span>`;
	} );
	register(
		'list-item',
		( a ) =>
			'<span class="clanbite-skeleton-group clanbite-skeleton-group--list-item" data-clanbite-skeleton="list-item" aria-hidden="true">' +
			render( 'avatar', {
				size: a.size || 'small',
				shape: a.shape,
				context: a.context,
			} ) +
			'<span class="clanbite-skeleton-group__body">' +
			element( 'line', {
				type: 'list-item',
				context: a.context,
				width: '70%',
			} ) +
			element( 'line', {
				type: 'list-item',
				context: a.context,
				width: '45%',
			} ) +
			'</span></span>'
	);

	window.clanbite = window.clanbite || {};
	window.clanbite.skeletons = { register, render, has, element };

	if ( window.clanbite.components ) {
		window.clanbite.components.register( 'skeleton', {
			render,
			has,
		} );
	}
} )( window );
