import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { iconSize } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Icon size', 'clanbite' ) }>
					<SelectControl
						label={ __( 'Size', 'clanbite' ) }
						value={ iconSize }
						options={ [
							{
								label: __( 'Small', 'clanbite' ),
								value: 'small',
							},
							{
								label: __( 'Medium', 'clanbite' ),
								value: 'medium',
							},
							{
								label: __( 'Large', 'clanbite' ),
								value: 'large',
							},
						] }
						onChange={ ( v ) => setAttributes( { iconSize: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<p className="clanbite-player-block-placeholder">
					{ __(
						'Player social links (icons from Profile → Social Networks)',
						'clanbite'
					) }
				</p>
			</div>
		</>
	);
}
