/**
 * Registers the Match details panel in the block editor document sidebar for `cp_match`.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { PanelRow, TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const STATUS_OPTIONS = [
	{ label: __( 'Scheduled', 'clanbite' ), value: 'scheduled' },
	{ label: __( 'Live', 'clanbite' ), value: 'live' },
	{ label: __( 'Finished', 'clanbite' ), value: 'finished' },
	{ label: __( 'Cancelled', 'clanbite' ), value: 'cancelled' },
];

function MatchMetaFields() {
	const [ meta, setMeta ] = useEntityProp( 'postType', 'clanbite_match', 'meta' );

	const patch = ( key, value ) => {
		setMeta( { ...meta, [ key ]: value } );
	};

	const home = meta?.cp_match_home_team_id ?? 0;
	const away = meta?.cp_match_away_team_id ?? 0;
	const scheduled = meta?.cp_match_scheduled_at ?? '';
	const status = meta?.cp_match_status ?? 'scheduled';
	const homeScore = meta?.cp_match_home_score ?? 0;
	const awayScore = meta?.cp_match_away_score ?? 0;
	const venue = meta?.cp_match_venue ?? '';

	return (
		<>
			<PanelRow>
				<TextControl
					label={ __( 'Home team (post ID)', 'clanbite' ) }
					help={ __( 'Published `cp_team` post ID.', 'clanbite' ) }
					type="number"
					value={ home || '' }
					onChange={ ( v ) =>
						patch( 'cp_match_home_team_id', parseInt( v, 10 ) || 0 )
					}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</PanelRow>
			<PanelRow>
				<TextControl
					label={ __( 'Away team (post ID)', 'clanbite' ) }
					help={ __( 'Published `cp_team` post ID.', 'clanbite' ) }
					type="number"
					value={ away || '' }
					onChange={ ( v ) =>
						patch( 'cp_match_away_team_id', parseInt( v, 10 ) || 0 )
					}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</PanelRow>
			<PanelRow>
				<TextControl
					label={ __( 'Scheduled at (GMT)', 'clanbite' ) }
					help={ __(
						'Format: YYYY-MM-DD HH:MM:SS in GMT. Saved via REST and sanitized server-side.',
						'clanbite'
					) }
					value={ scheduled }
					onChange={ ( v ) => patch( 'cp_match_scheduled_at', v ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</PanelRow>
			<PanelRow>
				<SelectControl
					label={ __( 'Match status', 'clanbite' ) }
					value={ status }
					options={ STATUS_OPTIONS }
					onChange={ ( v ) => patch( 'cp_match_status', v ) }
				/>
			</PanelRow>
			<PanelRow>
				<TextControl
					label={ __( 'Home score', 'clanbite' ) }
					type="number"
					value={ homeScore || '' }
					onChange={ ( v ) =>
						patch( 'cp_match_home_score', parseInt( v, 10 ) || 0 )
					}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</PanelRow>
			<PanelRow>
				<TextControl
					label={ __( 'Away score', 'clanbite' ) }
					type="number"
					value={ awayScore || '' }
					onChange={ ( v ) =>
						patch( 'cp_match_away_score', parseInt( v, 10 ) || 0 )
					}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</PanelRow>
			<PanelRow>
				<TextControl
					label={ __( 'Venue', 'clanbite' ) }
					value={ venue }
					onChange={ ( v ) => patch( 'cp_match_venue', v ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</PanelRow>
		</>
	);
}

function MatchDocumentPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);

	if ( postType !== 'clanbite_match' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="clanbite-match-details"
			title={ __( 'Match details', 'clanbite' ) }
			className="clanbite-match-document-panel"
		>
			<MatchMetaFields />
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'clanbite-cp-match-sidebar', {
	render: MatchDocumentPanel,
} );
