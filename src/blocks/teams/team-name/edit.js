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
				toggleLabel={ __( 'Link to team profile', 'clanbite' ) }
			/>
			<div { ...useBlockProps() }>
				<ServerPreview
					block="clanbite/team-name"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
