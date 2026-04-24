import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p className="clanbite-player-block-placeholder">
				<span className="clanbite-player-handle__text">@username</span>
				{ ' ' }
				<span className="clanbite-player-handle__hint">
					{ __(
						'(shown when the player has a nicename; hidden on the site if empty)',
						'clanbite'
					) }
				</span>
			</p>
		</div>
	);
}
