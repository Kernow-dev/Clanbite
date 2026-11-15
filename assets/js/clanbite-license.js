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
	const {
		createElement: h,
		Fragment,
		useState,
		useEffect,
		createRoot,
	} = wp.element;
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

	function Badge( { state, children } ) {
		return h(
			'span',
			{ className: `clanbite-license__badge is-${ state || 'none' }` },
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
		const onSubmit = ( event ) => {
			event.preventDefault();
			if ( ! busy && ( constant || key.trim() ) ) {
				submit();
			}
		};
		return h(
			'form',
			{ className: 'clanbite-license__activate', onSubmit },
			h(
				'p',
				{ className: 'clanbite-license__lead' },
				constant
					? __(
							'A licence key is defined in wp-config.php. Activate it for this site.',
							'clanbite'
					  )
					: __(
							'Enter the licence key from your Clanbite account to receive official extension updates.',
							'clanbite'
					  )
			),
			h(
				'div',
				{ className: 'clanbite-license__field' },
				constant
					? null
					: h( TextControl, {
							label: __( 'Licence key', 'clanbite' ),
							value: key,
							onChange: setKey,
							placeholder: 'CB-XXXX-XXXX-XXXX-XXXX',
							autoComplete: 'off',
							spellCheck: false,
							disabled: busy,
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
					  } ),
				h(
					Button,
					{
						variant: 'primary',
						type: 'submit',
						disabled: busy || ( ! constant && ! key.trim() ),
						isBusy: busy,
						__next40pxDefaultSize: true,
					},
					__( 'Activate licence', 'clanbite' )
				)
			)
		);
	}

	function Plugins( { rows } ) {
		if ( ! rows.length ) {
			return null;
		}
		return h(
			'div',
			{ className: 'clanbite-license__card' },
			h(
				'h2',
				{ className: 'clanbite-license__title' },
				__( 'Add-ons', 'clanbite' )
			),
			h(
				'table',
				{ className: 'clanbite-license__table' },
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
			)
		);
	}

	function Catalog( { catalog, accountUrl, onChanged, onError } ) {
		const [ working, setWorking ] = useState( '' );
		const [ done, setDone ] = useState( {} );
		const run = ( slug, action ) => {
			setWorking( `${ action }:${ slug }` );
			onError( '' );
			apiFetch( {
				path: `/clanbite/v1/admin/license/${ action }`,
				method: 'POST',
				data: { slug },
			} )
				.then( () => {
					setDone( ( d ) => ( { ...d, [ slug ]: action } ) );
					onChanged();
				} )
				.catch( ( e ) => onError( errorMessage( e ) ) )
				.finally( () => setWorking( '' ) );
		};

		const rows = catalog.extensions;
		return h(
			'div',
			{ className: 'clanbite-license__card' },
			h(
				'h2',
				{ className: 'clanbite-license__title' },
				__( 'Available add-ons', 'clanbite' )
			),
			h(
				'ul',
				{ className: 'clanbite-license__addons' },
				rows.map( ( row ) => {
					const busy = working.endsWith( `:${ row.slug }` );
					let action = null;
					let status = null;
					if ( row.installed ) {
						if ( row.update_available ) {
							status = h(
								Badge,
								{ state: 'expired' },
								__( 'Update available', 'clanbite' )
							);
							action = h(
								Button,
								{
									variant: 'secondary',
									href: window.ajaxurl
										? window.ajaxurl.replace(
												'admin-ajax.php',
												'update-core.php'
										  )
										: 'update-core.php',
								},
								__( 'Update', 'clanbite' )
							);
						} else if ( row.active ) {
							status = h(
								Badge,
								{ state: 'ok' },
								__( 'Active', 'clanbite' )
							);
						} else {
							status = h(
								Badge,
								{ state: 'none' },
								__( 'Installed', 'clanbite' )
							);
							action = catalog.can_activate
								? h(
										Button,
										{
											variant: 'secondary',
											onClick: () =>
												run(
													row.slug,
													'activate-addon'
												),
											isBusy: busy,
											disabled: !! working,
										},
										__( 'Activate', 'clanbite' )
								  )
								: null;
						}
					} else if ( row.installable && catalog.can_install ) {
						action = h(
							Button,
							{
								variant: 'primary',
								onClick: () => run( row.slug, 'install' ),
								isBusy: busy,
								disabled: !! working,
							},
							__( 'Install', 'clanbite' )
						);
					} else if (
						! row.installable &&
						row.access === 'required'
					) {
						status = h(
							Badge,
							{ state: 'none' },
							__( 'Not included', 'clanbite' )
						);
						action = accountUrl
							? h(
									'a',
									{
										href: accountUrl,
										target: '_blank',
										rel: 'noopener noreferrer',
									},
									__( 'Get this add-on', 'clanbite' )
							  )
							: null;
					} else if ( ! row.installable && row.blocked ) {
						status = h(
							Badge,
							{ state: 'expired' },
							__( 'Needs a newer Clanbite', 'clanbite' )
						);
					} else if ( row.access === 'expired' ) {
						status = h(
							Badge,
							{ state: 'expired' },
							__( 'Expired', 'clanbite' )
						);
					}
					return h(
						'li',
						{ key: row.slug, className: 'clanbite-license__addon' },
						row.icon
							? h( 'img', {
									className: 'clanbite-license__addon-icon',
									src: row.icon,
									alt: '',
									width: 48,
									height: 48,
							  } )
							: h( 'span', {
									className:
										'clanbite-license__addon-icon is-empty',
									'aria-hidden': 'true',
							  } ),
						h(
							'div',
							{ className: 'clanbite-license__addon-body' },
							h(
								'strong',
								null,
								row.name,
								row.free
									? h(
											'span',
											{
												className:
													'clanbite-license__tag',
											},
											__( 'Free', 'clanbite' )
									  )
									: null
							),
							row.description
								? h( 'p', null, row.description )
								: null,
							h(
								'span',
								{ className: 'clanbite-license__addon-meta' },
								row.installed
									? sprintf(
											/* translators: 1: installed version, 2: available version */
											__(
												'Installed %1$s · Available %2$s',
												'clanbite'
											),
											row.installed,
											row.version
									  )
									: sprintf(
											/* translators: %s: version number */
											__( 'Version %s', 'clanbite' ),
											row.version
									  )
							),
							done[ row.slug ] === 'install'
								? h(
										'p',
										{ className: 'clanbite-license__hint' },
										__(
											'Installed. Activate it, then switch it on under Clanbite → Extensions.',
											'clanbite'
										)
								  )
								: null,
							done[ row.slug ] === 'activate-addon'
								? h(
										'p',
										{ className: 'clanbite-license__hint' },
										__(
											'Activated. Switch it on under Clanbite → Extensions.',
											'clanbite'
										)
								  )
								: null,
							row.blocked && ! row.installable
								? h(
										'p',
										{ className: 'description' },
										( row.blocked.reasons || [] ).join(
											' '
										)
								  )
								: null
						),
						h(
							'div',
							{ className: 'clanbite-license__addon-side' },
							status,
							action
						)
					);
				} )
			)
		);
	}

	function App() {
		const [ summary, setSummary ] = useState( null );
		const [ error, setError ] = useState( '' );
		const [ busy, setBusy ] = useState( false );
		const [ catalog, setCatalog ] = useState( null );

		const loadCatalog = () =>
			apiFetch( { path: '/clanbite/v1/admin/license/catalog' } )
				.then( setCatalog )
				.catch( () => setCatalog( null ) );

		useEffect( () => {
			apiFetch( { path: '/clanbite/v1/admin/license' } )
				.then( setSummary )
				.catch( ( e ) => setError( errorMessage( e ) ) );
		}, [] );

		// The list depends on the licence: reload it whenever the site is (de)activated.
		const activated = summary ? !! summary.activated : null;
		useEffect( () => {
			if ( activated !== null ) {
				loadCatalog();
			}
		}, [ activated ] );

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

		const catalogView =
			catalog && catalog.extensions && catalog.extensions.length
				? h( Catalog, {
						catalog,
						accountUrl: summary.account_url,
						onChanged: loadCatalog,
						onError: setError,
				  } )
				: h( Plugins, { rows: summary.plugins || [] } );
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
						Fragment,
						null,
						h(
							'div',
							{ className: 'clanbite-license__card' },
							h(
								'h2',
								{ className: 'clanbite-license__title' },
								__( 'Your licence', 'clanbite' )
							),
							h(
								'dl',
								{ className: 'clanbite-license__facts' },
								h( 'dt', null, __( 'Licence', 'clanbite' ) ),
								h(
									'dd',
									null,
									h( 'code', null, summary.hint ),
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
												{ className: 'description' },
												__(
													'Agency licence',
													'clanbite'
												)
										  )
										: null
								),
								h( 'dt', null, __( 'Expires', 'clanbite' ) ),
								h( 'dd', null, formatDate( license.expires ) ),
								h( 'dt', null, __( 'Sites', 'clanbite' ) ),
								h(
									'dd',
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
								),
								h( 'dt', null, __( 'This site', 'clanbite' ) ),
								h( 'dd', null, summary.site_url )
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
							h(
								'p',
								{ className: 'clanbite-license__actions' },
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
						),
						catalogView
				  )
				: h(
						'div',
						{ className: 'clanbite-license__card' },
						h(
							'h2',
							{ className: 'clanbite-license__title' },
							__( 'Activate your licence', 'clanbite' )
						),
						h( ActivateForm, {
							constant: summary.constant,
							onSummary: setSummary,
							onError: setError,
						} )
				  ),
			! summary.activated ? catalogView : null
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
