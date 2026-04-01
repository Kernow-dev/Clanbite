import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit() {
		return (
			<p>
				{ 'Player profile navigation (rendered on front-end).' }
			</p>
		);
	},
} );

