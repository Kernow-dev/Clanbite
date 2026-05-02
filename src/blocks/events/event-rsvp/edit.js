import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes, context } ) {
	const { eventType, eventId, showAttendees } = attributes;
	const postId = context?.postId ?? 0;
	const postType = context?.postType ?? '';

	const resolvedId =
		eventId > 0
			? eventId
			: postId > 0 &&
			  ( ( eventType === 'match' && postType === 'clanbite_match' ) ||
					( eventType === 'group' && postType === 'cp_group' ) ||
					( eventType === 'clanbite_event' &&
						postType === 'clanbite_event' ) )
			? postId
			: 0;

	const blockProps = useBlockProps( {
		className: 'clanbite-event-rsvp-editor',
	} );

	const typeLabel =
		eventType === 'group'
			? __( 'Group', 'clanbite' )
			: eventType === 'clanbite_event'
			? __( 'Event', 'clanbite' )
			: __( 'Match', 'clanbite' );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Event', 'clanbite' ) }>
					<SelectControl
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
			<p className="clanbite-event-rsvp-editor__title">
				<strong>{ __( 'Event RSVP', 'clanbite' ) }</strong>
			</p>
			{ resolvedId < 1 && (
				<p>
					{ __(
						'Select a match or group template, or set an event ID.',
						'clanbite'
					) }
				</p>
			) }
			{ resolvedId > 0 && (
				<p>
					{ sprintf(
						/* translators: 1: event kind, 2: numeric ID */
						__( 'Linked %1$s — ID %2$d', 'clanbite' ),
						typeLabel,
						resolvedId
					) }
				</p>
			) }
		</div>
	);
}
