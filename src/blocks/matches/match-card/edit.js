/**
 * Match card block: choose match post ID.
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { matchId } = attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Match card', 'clanbite' ) }>
					<TextControl
						label={ __( 'Match post ID', 'clanbite' ) }
						help={ __(
							'The `cp_match` post ID to display.',
							'clanbite'
						) }
						type="number"
						value={ matchId || '' }
						onChange={ ( v ) =>
							setAttributes( { matchId: parseInt( v, 10 ) || 0 } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<p className="clanbite-match-card-editor-note">
				{ __( 'Match card (preview on the front end).', 'clanbite' ) }
			</p>
		</div>
	);
}
