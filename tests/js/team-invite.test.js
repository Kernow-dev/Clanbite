import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

let definition;
let context;

vi.mock( '@wordpress/interactivity', () => ( {
	store: ( name, parts ) => {
		definition = parts;
		return parts;
	},
	getContext: () => context,
	getElement: () => ( {} ),
} ) );

const json = ( body, ok = true ) =>
	Promise.resolve( { ok, json: () => Promise.resolve( body ) } );

describe( 'team invite box', () => {
	let input;
	let results;
	let status;
	let pending;

	beforeEach( async () => {
		vi.useFakeTimers();
		await import( '../../src/blocks/teams/team-manage-form/view.js' );
		document.body.innerHTML = `
			<div class="clanbite-team-invite">
				<input class="clanbite-team-invite__search">
				<p data-invite-status></p>
				<ul data-invite-results></ul>
				<div data-invite-pending hidden><ul></ul></div>
			</div>`;
		input = document.querySelector( 'input' );
		results = document.querySelector( '[data-invite-results]' );
		status = document.querySelector( '[data-invite-status]' );
		pending = document.querySelector( '[data-invite-pending]' );
		context = {
			searchUrl: 'http://example.test/?rest_route=/clanbite/v1/search',
			inviteUrl: 'http://example.test/invites',
			restNonce: 'n',
			i18n: {
				invite: 'Invite',
				inviteLabel: 'Invite %s',
				sent: 'Sent to %s.',
				found: 'Found: %d',
				none: 'None',
				failed: 'Failed',
			},
		};
	} );

	afterEach( () => {
		vi.useRealTimers();
		vi.unstubAllGlobals();
	} );

	const search = async ( value ) => {
		input.value = value;
		definition.actions.onInviteSearch( { target: input } );
		await vi.advanceTimersByTimeAsync( 400 );
	};
	const click = () =>
		definition.actions.sendInvite( {
			target: results.querySelector( 'button' ),
			preventDefault() {},
		} );

	it( 'searches with the nonce and keeps names as text', async () => {
		const fetch = vi.fn( () =>
			json( [ { id: 7, label: '<img src=x> (seven)' } ] )
		);
		vi.stubGlobal( 'fetch', fetch );
		await search( 'sev' );

		const [ url, options ] = fetch.mock.calls[ 0 ];
		// Works with plain permalinks, where the REST URL already has a query string.
		expect( url.searchParams.get( 'rest_route' ) ).toBe(
			'/clanbite/v1/search'
		);
		expect( url.searchParams.get( 'q' ) ).toBe( 'sev' );
		expect( options.headers[ 'X-WP-Nonce' ] ).toBe( 'n' );
		expect( results.querySelector( 'img' ) ).toBeNull();
		expect( results.querySelector( 'button' ).type ).toBe( 'button' );
		expect( status.textContent ).toBe( 'Found: 1' );
	} );

	it( 'does not search for a single character', async () => {
		const fetch = vi.fn();
		vi.stubGlobal( 'fetch', fetch );
		await search( 'a' );
		expect( fetch ).not.toHaveBeenCalled();
	} );

	it( 'stops Enter from saving the surrounding form', () => {
		const preventDefault = vi.fn();
		definition.actions.onInviteSearchKeydown( {
			key: 'Enter',
			preventDefault,
		} );
		expect( preventDefault ).toHaveBeenCalled();
	} );

	it( 'sends an invitation and lists it as waiting', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn( () => json( [ { id: 7, label: 'Seven (seven)' } ] ) )
		);
		await search( 'sev' );
		const fetch = vi.fn( () => json( { invited: true } ) );
		vi.stubGlobal( 'fetch', fetch );
		await click();

		expect( fetch.mock.calls[ 0 ][ 1 ].method ).toBe( 'POST' );
		expect( JSON.parse( fetch.mock.calls[ 0 ][ 1 ].body ) ).toEqual( {
			user_id: 7,
		} );
		expect( results.children ).toHaveLength( 0 );
		expect( pending.hidden ).toBe( false );
		expect( pending.textContent ).toContain( 'Seven (seven)' );
		expect( status.textContent ).toBe( 'Sent to Seven (seven).' );
	} );

	it( 'shows why an invitation was refused and allows another try', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn( () => json( [ { id: 7, label: 'Seven' } ] ) )
		);
		await search( 'sev' );
		vi.stubGlobal(
			'fetch',
			vi.fn( () => json( { message: 'Already waiting.' }, false ) )
		);
		await click();

		expect( status.textContent ).toBe( 'Already waiting.' );
		expect( results.querySelector( 'button' ).disabled ).toBe( false );
		expect( pending.hidden ).toBe( true );
	} );
} );
