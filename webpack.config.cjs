const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// Hand-written (non-block) scripts and styles live in src/assets/ and are built to assets/dist/ with the same relative names.
const src = ( file ) => path.resolve( __dirname, 'src', 'assets', file );

module.exports = {
	...defaultConfig,
	entry: {
		'block-icons-editor': src( 'block-icons-editor.css' ),
		'css/clanbite-avatars': src( 'css/clanbite-avatars.css' ),
		'css/clanbite-buttons': src( 'css/clanbite-buttons.css' ),
		'css/clanbite-forms': src( 'css/clanbite-forms.css' ),
		'css/clanbite-license': src( 'css/clanbite-license.css' ),
		'css/clanbite-player-media-editor': src( 'css/clanbite-player-media-editor.css' ),
		'css/clanbite-player-notifications-page': src( 'css/clanbite-player-notifications-page.css' ),
		'css/clanbite-toasts': src( 'css/clanbite-toasts.css' ),
		'css/clanbite-tokens': src( 'css/clanbite-tokens.css' ),
		'js/admin/team-options-editor': src( 'js/admin/team-options-editor.js' ),
		'js/clanbite-components': src( 'js/clanbite-components.js' ),
		'js/clanbite-copy-text': src( 'js/clanbite-copy-text.js' ),
		'js/clanbite-license': src( 'js/clanbite-license.js' ),
		'js/clanbite-player-media-editor': src( 'js/clanbite-player-media-editor.js' ),
		'js/clanbite-player-notifications-page': src( 'js/clanbite-player-notifications-page.js' ),
		'js/clanbite-popups': src( 'js/clanbite-popups.js' ),
		'js/clanbite-toasts': src( 'js/clanbite-toasts.js' ),
		'clanbite-admin': path.resolve( __dirname, 'src', 'admin', 'index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'assets', 'dist' ),
	},
	plugins: ( defaultConfig.plugins || [] ).filter( ( plugin ) => {
		const n = plugin?.constructor?.name || '';
		// Avoid copying block assets / PHP from the whole plugin tree into dist/.
		return n !== 'CopyPlugin' && n !== 'PhpFilePathsPlugin';
	} ),
};
