import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p className="clanbite-team-block-placeholder">
				{ __( 'Team motto (single team template)', 'clanbite' ) }
			</p>
		</div>
	);
}
