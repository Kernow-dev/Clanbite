/**
 * Block editor script: Team challenge button (front behavior in view.js).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/team-challenge-button';
import { ServerPreview } from '../../shared/server-preview';
import './style.scss';
import './editor.scss';

function Edit( { attributes } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerPreview
				block="clanbite/team-challenge-button"
				attributes={ attributes }
			/>
		</div>
	);
}

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: Edit,
} );
