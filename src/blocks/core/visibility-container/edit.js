/**
 * Visibility container block editor.
 */
import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	FormTokenField,
	Notice,
} from '@wordpress/components';

import './editor.scss';

const SHOW_OPTIONS = [
	{ label: __( 'Everyone', 'clanbite' ), value: 'all' },
	{
		label: __( 'Guests only (not logged in)', 'clanbite' ),
		value: 'guests',
	},
	{ label: __( 'Logged-in users', 'clanbite' ), value: 'logged_in' },
	{ label: __( 'Selected roles only', 'clanbite' ), value: 'roles' },
];

const HIDE_OPTIONS = [
	{ label: __( 'No one (do not hide)', 'clanbite' ), value: 'none' },
	{ label: __( 'Guests', 'clanbite' ), value: 'guests' },
	{ label: __( 'Logged-in users', 'clanbite' ), value: 'logged_in' },
	{ label: __( 'Selected roles', 'clanbite' ), value: 'roles' },
];

function getRoleSuggestions() {
	const cfg =
		typeof window !== 'undefined'
			? window.clanbiteVisibilityContainer
			: null;
	if ( ! cfg?.roles?.length ) {
		return [];
	}
	return cfg.roles.map( ( r ) => r.label || r.slug );
}

function tokensToSlugs( tokens ) {
	const cfg =
		typeof window !== 'undefined'
			? window.clanbiteVisibilityContainer
			: null;
	if ( ! cfg?.roles?.length ) {
		return tokens.map( ( t ) =>
			String( t ).toLowerCase().replace( /\s+/g, '_' )
		);
	}
	const byLabel = new Map(
		cfg.roles.map( ( r ) => [ String( r.label ).toLowerCase(), r.slug ] )
	);
	return tokens.map( ( t ) => {
		const key = String( t ).toLowerCase();
		if ( byLabel.has( key ) ) {
			return byLabel.get( key );
		}
		return sanitizeSlug( t );
	} );
}

function slugsToTokens( slugs, suggestionsData ) {
	if ( ! suggestionsData?.length || ! slugs?.length ) {
		return slugs || [];
	}
	const bySlug = new Map(
		suggestionsData.map( ( r ) => [ r.slug, r.label || r.slug ] )
	);
	return slugs.map( ( s ) => bySlug.get( s ) || s );
}

function sanitizeSlug( raw ) {
	return String( raw )
		.toLowerCase()
		.replace( /[^a-z0-9_-]+/g, '_' )
		.replace( /^_+|_+$/g, '' );
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		showTo,
		hideFrom,
		showToRoles = [],
		hideFromRoles = [],
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'clanbite-visibility-container-editor',
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		renderAppender: InnerBlocks.ButtonBlockAppender,
	} );

	const suggestions = getRoleSuggestions();
	const cfg =
		typeof window !== 'undefined'
			? window.clanbiteVisibilityContainer
			: null;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Visibility', 'clanbite' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Show to', 'clanbite' ) }
						help={ __(
							'Who should see the content inside this block.',
							'clanbite'
						) }
						value={ showTo }
						options={ SHOW_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { showTo: value || 'all' } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ showTo === 'roles' && (
						<FormTokenField
							label={ __( 'Roles (show to)', 'clanbite' ) }
							value={ slugsToTokens( showToRoles, cfg?.roles ) }
							suggestions={ suggestions }
							onChange={ ( tokens ) =>
								setAttributes( {
									showToRoles: tokensToSlugs( tokens ),
								} )
							}
							__experimentalShowHowTo={ false }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					<SelectControl
						label={ __( 'Hide from', 'clanbite' ) }
						help={ __(
							'Remove the block for these visitors (applied after “Show to”).',
							'clanbite'
						) }
						value={ hideFrom }
						options={ HIDE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { hideFrom: value || 'none' } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ hideFrom === 'roles' && (
						<FormTokenField
							label={ __( 'Roles (hide from)', 'clanbite' ) }
							value={ slugsToTokens( hideFromRoles, cfg?.roles ) }
							suggestions={ suggestions }
							onChange={ ( tokens ) =>
								setAttributes( {
									hideFromRoles: tokensToSlugs( tokens ),
								} )
							}
							__experimentalShowHowTo={ false }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					{ showTo === 'roles' && showToRoles.length === 0 && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'Pick at least one role. Until you do, the block is shown to everyone (same as “Everyone”).',
								'clanbite'
							) }
						</Notice>
					) }
					{ ! cfg?.roles?.length && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Role labels load from the site; you can type role slugs (e.g. administrator) in the token fields.',
								'clanbite'
							) }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}
