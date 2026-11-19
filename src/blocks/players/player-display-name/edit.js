import { __ } from '@wordpress/i18n';
import { ServerPreview } from '../../shared/server-preview';
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
				<ServerPreview
					block="clanbite/player-display-name"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
