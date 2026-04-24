import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p>{ __( 'Event detail', 'clanbite' ) }</p>
		</div>
	);
}
