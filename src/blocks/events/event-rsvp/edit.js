import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ServerPreview } from '../../shared/server-preview';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { eventType, eventId, showAttendees } = attributes;
	const blockProps = useBlockProps( {
		className: 'clanbite-event-rsvp-editor',
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Event', 'clanbite' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Event type', 'clanbite' ) }
						value={ eventType }
						options={ [
							{
								label: __( 'Match (cp_match)', 'clanbite' ),
								value: 'match',
							},
							{
								label: __( 'Group (cp_group)', 'clanbite' ),
								value: 'group',
							},
							{
								label: __(
									'Scheduled event (cp_event)',
									'clanbite'
								),
								value: 'clanbite_event',
							},
						] }
						onChange={ ( v ) => setAttributes( { eventType: v } ) }
					/>
					<TextControl
						label={ __( 'Event ID (optional)', 'clanbite' ) }
						help={ __(
							'Leave 0 to use the current template post in the editor or on single event pages.',
							'clanbite'
						) }
						type="number"
						value={ eventId || '' }
						onChange={ ( v ) =>
							setAttributes( { eventId: parseInt( v, 10 ) || 0 } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show attendee list', 'clanbite' ) }
						checked={ showAttendees }
						onChange={ ( v ) =>
							setAttributes( { showAttendees: v } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<ServerPreview
				block="clanbite/event-rsvp"
				attributes={ attributes }
			/>
		</div>
	);
}
