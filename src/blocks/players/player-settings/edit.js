import { useBlockProps } from '@wordpress/block-editor';
import { ServerPreview } from '../../shared/server-preview';
import './editor.scss';

/**
 * Editor preview: the signed-in editor's own settings screen, exactly as the front end renders it (inert).
 * @param root0
 * @param root0.attributes
 */
export default function Edit( { attributes } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerPreview
				block="clanbite/player-settings"
				attributes={ attributes }
			/>
		</div>
	);
}
