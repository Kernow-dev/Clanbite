import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit() {
		return (
			<p>
				{ 'Team profile navigation (rendered on front-end).' }
			</p>
		);
	},
} );

