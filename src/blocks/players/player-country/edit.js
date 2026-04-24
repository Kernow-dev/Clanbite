import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { showCode, countryDisplay, flagFirst } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Country display', 'clanbite' ) }>
					<SelectControl
						label={ __( 'Show', 'clanbite' ) }
						value={ countryDisplay || 'both' }
						options={ [
							{
								label: __( 'Flag and country', 'clanbite' ),
								value: 'both',
							},
							{
								label: __( 'Flag only', 'clanbite' ),
								value: 'flag',
							},
							{
								label: __( 'Country only', 'clanbite' ),
								value: 'text',
							},
						] }
						onChange={ ( v ) =>
							setAttributes( { countryDisplay: v || 'both' } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ countryDisplay === 'both' || ! countryDisplay ? (
						<SelectControl
							label={ __( 'Order', 'clanbite' ) }
							value={ flagFirst ? 'flag' : 'text' }
							options={ [
								{
									label: __( 'Flag first', 'clanbite' ),
									value: 'flag',
								},
								{
									label: __( 'Country first', 'clanbite' ),
									value: 'text',
								},
							] }
							onChange={ ( v ) =>
								setAttributes( { flagFirst: v === 'flag' } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) : null }
					<ToggleControl
						label={ __(
							'Show country code in text',
							'clanbite'
						) }
						checked={ !! showCode }
						onChange={ ( v ) =>
							setAttributes( { showCode: !! v } )
						}
						help={ __(
							'When the country name is shown, append the ISO code in parentheses.',
							'clanbite'
						) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{ ...useBlockProps( {
					className: 'clanbite-player-country-editor',
				} ) }
			>
				<div className="clanbite-country-display clanbite-country-display--preview">
					{ ( countryDisplay === 'both' || ! countryDisplay ) && (
						<>
							{ flagFirst ? (
								<>
									<span
										className="clanbite-country-flag clanbite-country-flag--preview"
										aria-hidden="true"
									/>
									<span className="clanbite-country-display__label">
										{ __( 'United Kingdom', 'clanbite' ) }
									</span>
								</>
							) : (
								<>
									<span className="clanbite-country-display__label">
										{ __( 'United Kingdom', 'clanbite' ) }
									</span>
									<span
										className="clanbite-country-flag clanbite-country-flag--preview"
										aria-hidden="true"
									/>
								</>
							) }
						</>
					) }
					{ countryDisplay === 'text' && (
						<span className="clanbite-country-display__label">
							{ __( 'United Kingdom', 'clanbite' ) }
						</span>
					) }
					{ countryDisplay === 'flag' && (
						<span
							className="clanbite-country-flag clanbite-country-flag--preview"
							aria-hidden="true"
						/>
					) }
				</div>
				<p className="clanbite-player-block-placeholder">
					{ __( 'Player country', 'clanbite' ) }
				</p>
			</div>
		</>
	);
}
