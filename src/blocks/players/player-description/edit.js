import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p className="clanbite-player-block-placeholder">
				{ __(
					'Player description (only shows when the player has saved a bio)',
					'clanbite'
				) }
			</p>
		</div>
	);
}
