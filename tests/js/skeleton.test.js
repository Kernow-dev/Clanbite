/**
 * Unit tests for the browser half of the skeleton loaders.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

const createHooks = () => {
	const filters = {};
	return {
		doAction: () => {},
		addFilter: ( name, ns, fn ) => ( filters[ name ] ||= [] ).push( fn ),
		applyFilters: ( name, value, ...args ) =>
			( filters[ name ] || [] ).reduce(
				( acc, fn ) => fn( acc, ...args ),
				value
			),
	};
};

const scripts = {
	'clanbite-components.js': () =>
		import( '../../src/assets/js/clanbite-components.js' ),
	'clanbite-popups.js': () =>
		import( '../../src/assets/js/clanbite-popups.js' ),
	'clanbite-copy-text.js': () =>
		import( '../../src/assets/js/clanbite-copy-text.js' ),
	'clanbite-skeleton.js': () =>
		import( '../../src/assets/js/clanbite-skeleton.js' ),
};

// The scripts are plain browser scripts that attach themselves to `window`: run each one afresh.
const load = async ( file ) => {
	vi.resetModules();
	await scripts[ file ]();
};

const html = ( markup ) => {
	const box = document.createElement( 'div' );
	box.innerHTML = markup;
	return box;
};

beforeEach( async () => {
	delete window.clanbite;
	window.wp = { hooks: createHooks() };
	await load( 'clanbite-components.js' );
	await load( 'clanbite-skeleton.js' );
} );

describe( 'skeleton avatar', () => {
	it( 'uses the avatar size and shape classes so it matches a real avatar', () => {
		const el = html(
			window.clanbite.skeletons.render( 'avatar', {
				size: 'small',
				shape: 'hexagon',
				context: 'forums',
			} )
		).firstElementChild;
		expect( el.classList.contains( 'clanbite-skeleton--avatar' ) ).toBe(
			true
		);
		expect( el.classList.contains( 'clanbite-avatar--small' ) ).toBe(
			true
		);
		expect(
			el.classList.contains( 'clanbite-avatar--shape-hexagon' )
		).toBe( true );
		expect( el.classList.contains( 'is-forums' ) ).toBe( true );
		expect( el.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
	} );

	it( 'defaults to a medium circle', () => {
		const el = html(
			window.clanbite.skeletons.render( 'avatar' )
		).firstElementChild;
		expect( el.classList.contains( 'clanbite-avatar--medium' ) ).toBe(
			true
		);
		expect( el.classList.contains( 'clanbite-avatar--shape-circle' ) ).toBe(
			true
		);
	} );
} );

describe( 'skeleton safety', () => {
	it( 'drops anything that is not a plain CSS length', () => {
		const el = html(
			window.clanbite.skeletons.render( 'line', {
				width: '50%;background:red',
				height: '2rem',
			} )
		).firstElementChild;
		expect( el.getAttribute( 'style' ) ).toBe(
			'--clanbite-skeleton-height:2rem'
		);
	} );

	it( 'strips unsafe characters from class names', () => {
		const el = html(
			window.clanbite.skeletons.render( 'avatar', {
				size: 'big" onload="x',
			} )
		).firstElementChild;
		expect( el.hasAttribute( 'onload' ) ).toBe( false );
	} );

	it( 'returns an empty string for an unknown type', () => {
		expect( window.clanbite.skeletons.render( 'nope' ) ).toBe( '' );
	} );
} );

describe( 'skeleton extension points', () => {
	it( 'lets a third party register a type and replace a core one', () => {
		const { skeletons } = window.clanbite;
		expect( skeletons.has( 'match-card' ) ).toBe( false );
		expect(
			skeletons.register( 'match-card', () =>
				skeletons.element( 'block', { height: '6rem' } )
			)
		).toBe( true );
		expect( skeletons.register( 'match-card', () => '' ) ).toBe( false );
		expect(
			html(
				skeletons.render( 'match-card' )
			).firstElementChild.getAttribute( 'style' )
		).toBe( '--clanbite-skeleton-height:6rem' );
		expect( skeletons.register( 'line', () => '<i></i>', true ) ).toBe(
			true
		);
		expect( skeletons.render( 'line' ) ).toBe( '<i></i>' );
	} );

	it( 'runs the args and html filters', () => {
		window.wp.hooks.addFilter(
			'clanbite.skeleton.args',
			'test',
			( args ) => ( { ...args, size: 'large' } )
		);
		window.wp.hooks.addFilter(
			'clanbite.skeleton.html',
			'test',
			( markup, type ) => `<div data-type="${ type }">${ markup }</div>`
		);
		const wrapper = html(
			window.clanbite.skeletons.render( 'avatar', { size: 'small' } )
		).firstElementChild;
		expect( wrapper.getAttribute( 'data-type' ) ).toBe( 'avatar' );
		expect(
			wrapper.firstElementChild.classList.contains(
				'clanbite-avatar--large'
			)
		).toBe( true );
	} );

	it( 'registers itself as a component', () => {
		expect( window.clanbite.components.has( 'skeleton' ) ).toBe( true );
	} );
} );
