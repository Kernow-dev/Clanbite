/**
 * Editor preview of a dynamic block: the block's own server render (render.php), so it looks like the front end.
 *
 * Core's ServerSideRender fetches the markup; core's Disabled makes it inert so a click selects the block. While it renders,
 * `clanbite_is_editor_preview()` is true on the server, which is where demo data is supplied when the site has none.
 */
import ServerSideRender from '@wordpress/server-side-render';
import { Disabled } from '@wordpress/components';

export function ServerPreview( { block, attributes } ) {
	return (
		<Disabled>
			<ServerSideRender
				block={ block }
				attributes={ attributes }
				skipBlockSupportAttributes
			/>
		</Disabled>
	);
}
