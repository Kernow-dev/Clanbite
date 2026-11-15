/**
 * Clanbite → Settings → Licence.
 *
 * Mounted into the core admin integration slot (`clanbite:admin-integration-mount`). Activates or deactivates this site's licence
 * through `clanbite/v1/admin/license` and lists every add-on with its installed version, available version and licence state.
 * The licence key is sent once on activation and is never sent back to the browser.
 *
 * @param {Object} wp The `window.wp` global.
 */
( function ( wp ) {
	'use strict';

	const SLOT_ID = 'clanbite-license';
	const { createElement: h, useState, useEffect, createRoot } = wp.element;
	const { Button, Notice, Spinner, TextControl } = wp.components;
	const apiFetch = wp.apiFetch;
	const { __, sprintf } = wp.i18n;

	function errorMessage( error ) {
		return (
			( error && error.message ) ||
			__( 'Something went wrong. Please try again.', 'clanbite' )
		);
	}

	function formatDate( timestamp ) {
		return timestamp
			? new Date( timestamp * 1000 ).toLocaleDateString()
			: __( 'Never', 'clanbite' );
	}

	function stateLabel( state ) {
		const labels = {
			ok: __( 'Included', 'clanbite' ),
			expired: __( 'Expired', 'clanbite' ),
			required: __( 'Not included', 'clanbite' ),
			suspended: __( 'Inactive', 'clanbite' ),
		};
		return labels[ state ] || '-';
	}

	const BADGE_COLORS = {
		ok: { background: '#d7f0dd', color: '#0a4b1f' },
		expired: { background: '#fcf0c8', color: '#5c4400' },
		suspended: { background: '#fcf0c8', color: '#5c4400' },
	};

	function Badge( { state, children } ) {
		const colors = BADGE_COLORS[ state ] || {
			background: '#f0f0f1',
			color: 'inherit',
		};
		return h(
			'span',
			{
				className: `clanbite-license__badge is-${ state || 'none' }`,
				style: {
					display: 'inline-block',
					padding: '1px 8px',
					borderRadius: '9px',
					fontSize: '12px',
					...colors,
				},
			},
			children
		);
	}

	function ActivateForm( { onSummary, onError, constant } ) {
		const [ key, setKey ] = useState( '' );
		const [ busy, setBusy ] = useState( false );
		const submit = () => {
			setBusy( true );
			onError( '' );
			apiFetch( {
				path: '/clanbite/v1/admin/license/activate',
				method: 'POST',
				data: { license_key: key },
			} )
				.then( ( summary ) => {
					setKey( '' );
					onSummary( summary );
				} )
				.catch( ( e ) => onError( errorMessage( e ) ) )
				.finally( () => setBusy( false ) );
		};
		return h(
			'div',
			{ className: 'clanbite-license__activate' },
			h(
				'p',
				null,
				constant
					? __(
							'A licence key is defined in wp-config.php. Activate it for this site.',
							'clanbite'
					  )
					: __(
							'Enter the licence key from your Clanbite account to receive add-on updates.',
							'clanbite'
					  )
			),
			constant
				? null
				: h( TextControl, {
						label: __( 'Licence key', 'clanbite' ),
						value: key,
						onChange: setKey,
						placeholder: 'CB-XXXX-XXXX-XXXX-XXXX',
						autoComplete: 'off',
						disabled: busy,
						__nextHasNoMarginBottom: true,
						__next40pxDefaultSize: true,
				  } ),
			h(
				Button,
				{
					variant: 'primary',
					onClick: submit,
					disabled: busy || ( ! constant && ! key.trim() ),
					isBusy: busy,
				},
				__( 'Activate licence', 'clanbite' )
			)
		);
	}

	function Plugins( { rows } ) {
		if ( ! rows.length ) {
			return null;
		}
		return h(
			'table',
			{ className: 'widefat striped', style: { maxWidth: '900px' } },
			h(
				'thead',
				null,
				h(
					'tr',
					null,
					[
						__( 'Add-on', 'clanbite' ),
						__( 'Installed', 'clanbite' ),
						__( 'Available', 'clanbite' ),
						__( 'Licence', 'clanbite' ),
					].map( ( label ) => h( 'th', { key: label }, label ) )
				)
			),
			h(
				'tbody',
				null,
				rows.map( ( row ) =>
					h(
						'tr',
						{ key: row.slug },
						h( 'td', null, row.name ),
						h( 'td', null, row.installed ),
						h(
							'td',
							null,
							row.update_available
								? row.available
								: __( 'Up to date', 'clanbite' ),
							row.blocked_reason
								? h(
										'div',
										{ className: 'description' },
										sprintf(
											/* translators: 1: version number, 2: reason */
											__(
												'%1$s is held back: %2$s',
												'clanbite'
											),
											row.blocked_version,
											row.blocked_reason
										)
								  )
								: null,
							row.renew_for_latest && row.latest
								? h(
										'div',
										{ className: 'description' },
										sprintf(
											/* translators: %s: version number */
											__(
												'%s needs a renewal.',
												'clanbite'
											),
											row.latest
										)
								  )
								: null
						),
						h(
							'td',
							null,
							h(
								Badge,
								{ state: row.access },
								stateLabel( row.access )
							)
						)
					)
				)
			)
		);
	}

	function App() {
		const [ summary, setSummary ] = useState( null );
		const [ error, setError ] = useState( '' );
		const [ busy, setBusy ] = useState( false );

		useEffect( () => {
			apiFetch( { path: '/clanbite/v1/admin/license' } )
				.then( setSummary )
				.catch( ( e ) => setError( errorMessage( e ) ) );
		}, [] );

		const call = ( action ) => {
			setBusy( true );
			setError( '' );
			apiFetch( {
				path: `/clanbite/v1/admin/license/${ action }`,
				method: 'POST',
			} )
				.then( setSummary )
				.catch( ( e ) => setError( errorMessage( e ) ) )
				.finally( () => setBusy( false ) );
		};

		if ( ! summary ) {
			return error
				? h( Notice, { status: 'error', isDismissible: false }, error )
				: h( Spinner );
		}

		const license = summary.license || {};
		const unlimited = !! license.unlimited;
		const status = license.status || 'active';

		return h(
			'div',
			{ className: 'clanbite-license' },
			error
				? h(
						Notice,
						{
							status: 'error',
							isDismissible: true,
							onRemove: () => setError( '' ),
						},
						error
				  )
				: null,
			summary.error && summary.activated
				? h(
						Notice,
						{ status: 'warning', isDismissible: false },
						summary.error
				  )
				: null,
			! summary.server_ok
				? h(
						Notice,
						{ status: 'error', isDismissible: false },
						__(
							'The update server address must use HTTPS.',
							'clanbite'
						)
				  )
				: null,
			summary.activated
				? h(
						'div',
						null,
						h(
							'table',
							{ className: 'form-table', role: 'presentation' },
							h(
								'tbody',
								null,
								h(
									'tr',
									null,
									h(
										'th',
										null,
										__( 'Licence', 'clanbite' )
									),
									h(
										'td',
										null,
										h( 'code', null, summary.hint ),
										' ',
										h(
											Badge,
											{
												state:
													status === 'active'
														? 'ok'
														: 'expired',
											},
											status
										),
										license.plan === 'agency'
											? h(
													'span',
													{
														className:
															'description',
													},
													' ' +
														__(
															'Agency licence',
															'clanbite'
														)
											  )
											: null
									)
								),
								h(
									'tr',
									null,
									h(
										'th',
										null,
										__( 'Expires', 'clanbite' )
									),
									h(
										'td',
										null,
										formatDate( license.expires )
									)
								),
								h(
									'tr',
									null,
									h( 'th', null, __( 'Sites', 'clanbite' ) ),
									h(
										'td',
										null,
										unlimited
											? sprintf(
													/* translators: %d: number of sites */
													__(
														'%d in use, unlimited',
														'clanbite'
													),
													license.sites_used || 0
											  )
											: sprintf(
													/* translators: 1: sites in use, 2: site limit */
													__(
														'%1$d of %2$d in use',
														'clanbite'
													),
													license.sites_used || 0,
													license.site_limit || 0
											  )
									)
								),
								h(
									'tr',
									null,
									h(
										'th',
										null,
										__( 'This site', 'clanbite' )
									),
									h( 'td', null, summary.site_url )
								)
							)
						),
						license.stale_days
							? h(
									'p',
									{ className: 'description' },
									sprintf(
										/* translators: %d: number of days */
										__(
											'A site that has not checked in for %d days is released automatically, and takes a seat again by itself if it comes back.',
											'clanbite'
										),
										license.stale_days
									)
							  )
							: null,
						h( Plugins, { rows: summary.plugins || [] } ),
						h(
							'p',
							{ style: { marginTop: '16px' } },
							h(
								Button,
								{
									variant: 'secondary',
									onClick: () => call( 'refresh' ),
									disabled: busy,
									isBusy: busy,
								},
								__( 'Refresh', 'clanbite' )
							),
							' ',
							h(
								Button,
								{
									variant: 'tertiary',
									isDestructive: true,
									onClick: () => call( 'deactivate' ),
									disabled: busy,
								},
								__( 'Deactivate this site', 'clanbite' )
							),
							' ',
							summary.account_url
								? h(
										'a',
										{
											href: summary.account_url,
											target: '_blank',
											rel: 'noopener noreferrer',
										},
										__(
											'Manage or renew licence',
											'clanbite'
										)
								  )
								: null
						),
						summary.checked
							? h(
									'p',
									{ className: 'description' },
									sprintf(
										/* translators: %s: date and time of the last update check */
										__(
											'Last update check: %s',
											'clanbite'
										),
										new Date(
											summary.checked * 1000
										).toLocaleString()
									)
							  )
							: null
				  )
				: h( ActivateForm, {
						constant: summary.constant,
						onSummary: setSummary,
						onError: setError,
				  } ),
			! summary.activated
				? h( Plugins, { rows: summary.plugins || [] } )
				: null
		);
	}

	// One root per element: the core admin can fire mount several times for the same slot.
	const roots = new WeakMap();

	function mount( el ) {
		if ( ! el ) {
			return;
		}
		const entry = roots.get( el );
		if ( entry ) {
			window.clearTimeout( entry.timer );
			entry.timer = 0;
			return;
		}
		const root = createRoot( el );
		roots.set( el, { root, timer: 0 } );
		root.render( h( App ) );
	}

	function unmount( el ) {
		const entry = el && roots.get( el );
		if ( ! entry ) {
			return;
		}
		window.clearTimeout( entry.timer );
		entry.timer = window.setTimeout( () => {
			entry.root.unmount();
			roots.delete( el );
		}, 0 );
	}

	document.addEventListener(
		'clanbite:admin-integration-mount',
		( event ) => {
			if ( event.detail && event.detail.slotId === SLOT_ID ) {
				mount( event.target );
			}
		}
	);
	document.addEventListener(
		'clanbite:admin-integration-unmount',
		( event ) => {
			if ( event.detail && event.detail.slotId === SLOT_ID ) {
				unmount( event.target );
			}
		}
	);

	document
		.querySelectorAll( `[data-clanbite-admin-slot="${ SLOT_ID }"]` )
		.forEach( mount );
} )( window.wp );
