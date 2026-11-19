import { useBlockProps } from '@wordpress/block-editor';
import { ServerPreview } from '../../shared/server-preview';

export default function Edit( { attributes } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerPreview
				block="clanbite/team-create-form"
				attributes={ attributes }
			/>
		</div>
	);
}
