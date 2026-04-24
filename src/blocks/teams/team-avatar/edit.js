import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { EntityLinkInspector } from '../../shared/entity-link-inspector';
import './editor.scss';

const AVATAR_PRESET_OPTIONS = [
	{
		label: __( 'Large — team profiles', 'clanbite' ),
		value: 'large',
	},
	{
		label: __( 'Medium — feeds & lists', 'clanbite' ),
		value: 'medium',
	},
	{
		label: __( 'Small — compact UI', 'clanbite' ),
		value: 'small',
	},
];

export default function Edit( { attributes, setAttributes } ) {
	const { avatarPreset } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Avatar output', 'clanbite' ) }>
					<SelectControl
						label={ __( 'Image size preset', 'clanbite' ) }
						help={ __(
							'Uses the matching size from Clanbite → Teams → Team avatar image sizes.',
							'clanbite'
						) }
						value={ avatarPreset || 'large' }
						options={ AVATAR_PRESET_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( {
								avatarPreset: value || 'large',
							} )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<EntityLinkInspector
				attributes={ attributes }
				setAttributes={ setAttributes }
				toggleLabel={ __( 'Link image to team profile', 'clanbite' ) }
			/>
			<div { ...useBlockProps() }>
				<p className="clanbite-team-block-placeholder">
					{ __( 'Team avatar (single team template)', 'clanbite' ) }
				</p>
			</div>
		</>
	);
}
