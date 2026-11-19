import { ServerPreview } from '../../shared/server-preview';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit( { attributes } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerPreview
				block="clanbite/team-code"
				attributes={ attributes }
			/>
		</div>
	);
}
