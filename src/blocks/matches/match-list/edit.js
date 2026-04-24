/**
 * Match list block: inspector controls for query attributes.
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';

const STATUS_OPTIONS = [
	{ label: __( 'Any status', 'clanbite' ), value: '' },
	{ label: __( 'Scheduled', 'clanbite' ), value: 'scheduled' },
	{ label: __( 'Live', 'clanbite' ), value: 'live' },
	{ label: __( 'Finished', 'clanbite' ), value: 'finished' },
	{ label: __( 'Cancelled', 'clanbite' ), value: 'cancelled' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { teamId, limit, statusFilter, order } = attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Match list', 'clanbite' ) }>
					<TextControl
						label={ __( 'Team post ID (optional)', 'clanbite' ) }
						help={ __(
							'Limit to matches involving this team (`cp_team` ID). Leave 0 for all teams.',
							'clanbite'
						) }
						type="number"
						value={ teamId || '' }
						onChange={ ( v ) =>
							setAttributes( { teamId: parseInt( v, 10 ) || 0 } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Max matches', 'clanbite' ) }
						help={ __(
							'0 uses the default from Clanbite → Matches settings.',
							'clanbite'
						) }
						type="number"
						value={ limit || '' }
						onChange={ ( v ) =>
							setAttributes( { limit: parseInt( v, 10 ) || 0 } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Status filter', 'clanbite' ) }
						value={ statusFilter }
						options={ STATUS_OPTIONS }
						onChange={ ( v ) =>
							setAttributes( { statusFilter: v } )
						}
					/>
					<SelectControl
						label={ __( 'Sort by scheduled time', 'clanbite' ) }
						value={ order }
						options={ [
							{
								label: __( 'Ascending', 'clanbite' ),
								value: 'asc',
							},
							{
								label: __( 'Descending', 'clanbite' ),
								value: 'desc',
							},
						] }
						onChange={ ( v ) => setAttributes( { order: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<p className="clanbite-match-list-editor-note">
				{ __( 'Match list (preview on the front end).', 'clanbite' ) }
			</p>
		</div>
	);
}
