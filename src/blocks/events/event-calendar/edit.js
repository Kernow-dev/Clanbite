import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { scopeType, teamId, groupId, playerUserId, defaultView } =
		attributes;
	const blockProps = useBlockProps( {
		className: 'clanbite-event-calendar-editor',
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Context', 'clanbite' ) }>
					<SelectControl
						label={ __( 'Scope', 'clanbite' ) }
						value={ scopeType }
						options={ [
							{
								label: __( 'Team', 'clanbite' ),
								value: 'team',
							},
							{
								label: __( 'Group', 'clanbite' ),
								value: 'group',
							},
							{
								label: __(
									'Player (own profile only)',
									'clanbite'
								),
								value: 'player',
							},
						] }
						onChange={ ( v ) => setAttributes( { scopeType: v } ) }
					/>
					{ scopeType === 'team' && (
						<TextControl
							label={ __(
								'Team ID (0 = URL context)',
								'clanbite'
							) }
							type="number"
							value={ teamId || '' }
							onChange={ ( v ) =>
								setAttributes( {
									teamId: parseInt( v, 10 ) || 0,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					{ scopeType === 'group' && (
						<TextControl
							label={ __(
								'Group ID (0 = URL context)',
								'clanbite'
							) }
							type="number"
							value={ groupId || '' }
							onChange={ ( v ) =>
								setAttributes( {
									groupId: parseInt( v, 10 ) || 0,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					{ scopeType === 'player' && (
						<TextControl
							help={ __(
								'0 uses the profile user from the URL; only shown to that logged-in user.',
								'clanbite'
							) }
							label={ __( 'User ID override', 'clanbite' ) }
							type="number"
							value={ playerUserId || '' }
							onChange={ ( v ) =>
								setAttributes( {
									playerUserId: parseInt( v, 10 ) || 0,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					<SelectControl
						label={ __( 'Default view', 'clanbite' ) }
						value={ defaultView }
						options={ [
							{
								label: __( 'Month', 'clanbite' ),
								value: 'month',
							},
							{
								label: __( 'Week', 'clanbite' ),
								value: 'week',
							},
							{ label: __( 'Day', 'clanbite' ), value: 'day' },
							{
								label: __( 'List', 'clanbite' ),
								value: 'list',
							},
						] }
						onChange={ ( v ) =>
							setAttributes( { defaultView: v } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<p>{ __( 'Event calendar (front-end)', 'clanbite' ) }</p>
		</div>
	);
}
