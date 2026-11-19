import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/profile-nav';
import { ServerPreview } from '../../shared/server-preview';
import './style.scss';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit( { attributes } ) {
		return (
			<div { ...useBlockProps() }>
				<ServerPreview
					block="clanbite/player-profile-nav"
					attributes={ attributes }
				/>
			</div>
		);
	},
} );
