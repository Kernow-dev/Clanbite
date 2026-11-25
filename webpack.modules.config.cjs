/**
 * Script modules that belong to a screen rather than a block. The block build only picks up modules named in a
 * `block.json`, so these use the same WordPress module settings with their own entries.
 */
const path = require( 'path' );

process.env.WP_EXPERIMENTAL_MODULES = 'true';
const [
	,
	moduleConfig,
] = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...moduleConfig,
	entry: {
		'teams/team-manage-form/view': path.resolve(
			__dirname,
			'src/blocks/teams/team-manage-form/view.js'
		),
	},
	// Runs after the block build, into the same folder: keep what is there.
	output: { ...moduleConfig.output, clean: false },
};
