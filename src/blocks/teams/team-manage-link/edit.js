import { __ } from '@wordpress/i18n';
import { ServerPreview } from '../../shared/server-preview';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { label } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Link text', 'clanbite' ) }>
					<TextControl
						label={ __( 'Label', 'clanbite' ) }
						help={ __(
							'Leave empty to use the default “Manage team” label on the front end.',
							'clanbite'
						) }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value ?? '' } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<ServerPreview
					block="clanbite/team-manage-link"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
