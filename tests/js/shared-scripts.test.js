/**
 * Unit tests for the shared browser scripts: components registry, popup lifecycle and clipboard.
 */
/**
 * A minimal stand-in for `wp.hooks` (the real package is not a dependency of this plugin).
 */
const createHooks = () => {
	const actions = {};
	const filters = {};
	return {
		addAction: ( name, ns, fn ) => ( actions[ name ] ||= [] ).push( fn ),
		doAction: ( name, ...args ) =>
			( actions[ name ] || [] ).forEach( ( fn ) => fn( ...args ) ),
		addFilter: ( name, ns, fn ) => ( filters[ name ] ||= [] ).push( fn ),
		applyFilters: ( name, value, ...args ) =>
			( filters[ name ] || [] ).reduce(
				( acc, fn ) => fn( acc, ...args ),
				value
			),
	};
};

const load = ( file ) => {
	jest.isolateModules( () => {
		require( `../../assets/js/${ file }` );
	} );
};

beforeEach( () => {
	document.body.innerHTML = '';
	delete window.clanbite;
	window.wp = { hooks: createHooks() };
	load( 'clanbite-components.js' );
} );

describe( 'components registry', () => {
	it( 'registers, finds and refuses duplicates', () => {
		const { components } = window.clanbite;
		expect( components.has( 'x' ) ).toBe( false );
		expect( components.register( 'x', { a: 1 } ) ).toBe( true );
		expect( components.get( 'x' ) ).toEqual( { a: 1 } );
		expect( components.register( 'x', { a: 2 } ) ).toBe( false );
		expect( components.register( 'x', { a: 2 }, true ) ).toBe( true );
		expect( components.get( 'x' ).a ).toBe( 2 );
		expect( components.get( 'missing' ) ).toBeNull();
	} );

	it( 'fires an action when a component registers', () => {
		const seen = [];
		window.wp.hooks.addAction(
			'clanbite.components.registered',
			'test',
			( slug ) => seen.push( slug )
		);
		window.clanbite.components.register( 'y', {} );
		expect( seen ).toEqual( [ 'y' ] );
	} );
} );

describe( 'popup lifecycle', () => {
	beforeEach( () => load( 'clanbite-popups.js' ) );

	it( 'does not fire on the first state, then fires on changes only', () => {
		const events = [];
		window.wp.hooks.addAction( 'clanbite.popup.toggled', 'test', ( d ) =>
			events.push( [ d.name, d.open ] )
		);
		const root = document.createElement( 'div' );
		const { popups } = window.clanbite;
		popups.announce( 'menu', false, root );
		popups.announce( 'menu', false, root );
		popups.announce( 'menu', true, root );
		popups.announce( 'menu', false, root );
		expect( events ).toEqual( [
			[ 'menu', true ],
			[ 'menu', false ],
		] );
	} );

	it( 'lets a filter veto opening', () => {
		const { popups } = window.clanbite;
		expect( popups.allowOpen( 'menu', null ) ).toBe( true );
		window.wp.hooks.addFilter(
			'clanbite.popup.shouldOpen',
			'test',
			( allow, d ) => ( d.name === 'menu' ? false : allow )
		);
		expect( popups.allowOpen( 'menu', null ) ).toBe( false );
		expect( popups.allowOpen( 'other', null ) ).toBe( true );
	} );

	it( 'registers itself in the components registry', () => {
		expect( window.clanbite.components.has( 'popups' ) ).toBe( true );
	} );
} );

describe( 'clipboard', () => {
	beforeEach( () => load( 'clanbite-copy-text.js' ) );

	it( 'uses the async clipboard API when it exists', async () => {
		const writeText = jest.fn().mockResolvedValue();
		Object.defineProperty( window.navigator, 'clipboard', {
			value: { writeText },
			configurable: true,
		} );
		expect( await window.clanbite.clipboard.copy( 'hello' ) ).toBe( true );
		expect( writeText ).toHaveBeenCalledWith( 'hello' );
	} );

	it( 'falls back to execCommand and ignores empty text', async () => {
		Object.defineProperty( window.navigator, 'clipboard', {
			value: undefined,
			configurable: true,
		} );
		document.execCommand = jest.fn().mockReturnValue( true );
		expect( await window.clanbite.clipboard.copy( 'hello' ) ).toBe( true );
		expect( document.execCommand ).toHaveBeenCalledWith( 'copy' );
		expect( await window.clanbite.clipboard.copy( '' ) ).toBe( false );
	} );
} );
