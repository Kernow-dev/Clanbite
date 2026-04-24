import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { EntityLinkInspector } from '../../shared/entity-link-inspector';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<>
			<EntityLinkInspector
				attributes={ attributes }
				setAttributes={ setAttributes }
				toggleLabel={ __( 'Link to player profile', 'clanbite' ) }
			/>
			<div { ...useBlockProps() }>
				<p className="clanbite-player-block-placeholder">
					{ __(
						'Player display name (profile template)',
						'clanbite'
					) }
				</p>
			</div>
		</>
	);
}
