import { __ } from '@wordpress/i18n';
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
				<p className="clanbite-team-manage-link clanbite-team-manage-link--editor-preview">
					<a
						href="#clanbite-team-manage-link-preview"
						onClick={ ( e ) => e.preventDefault() }
					>
						{ label || __( 'Manage team', 'clanbite' ) }
					</a>
				</p>
				<p className="clanbite-team-block-placeholder">
					{ __(
						'On the site, this link only appears for users who can edit this team.',
						'clanbite'
					) }
				</p>
			</div>
		</>
	);
}
