import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p>{ __( 'Team create form block', 'clanbite' ) }</p>
			<p>
				{ __( 'Rendered dynamically on the front end.', 'clanbite' ) }
			</p>
		</div>
	);
}
