import { ServerPreview } from '../../shared/server-preview';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit( { attributes } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerPreview
				block="clanbite/event-list"
				attributes={ attributes }
			/>
		</div>
	);
}
