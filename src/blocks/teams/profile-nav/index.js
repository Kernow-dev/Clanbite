import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/profile-nav';
import { ServerPreview } from '../../shared/server-preview';
import './style.scss';

/**
 * Editor preview: the navigation exactly as the front end renders it (inert).
 *
 * @param {Object} props            Block edit props.
 * @param {Object} props.attributes Block attributes.
 * @return {import('react').ReactNode} The preview.
 */
function Edit( { attributes } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerPreview
				block="clanbite/team-profile-nav"
				attributes={ attributes }
			/>
		</div>
	);
}

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: Edit,
} );
