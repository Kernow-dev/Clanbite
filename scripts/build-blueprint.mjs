/**
 * Writes the WordPress.org Live Preview blueprint from the readable PHP in scripts/blueprint/.
 *
 * Run `npm run build:blueprint` after changing either file and commit the result. The release workflow copies
 * .wordpress-org/ (this blueprint included) to the plugin directory's assets folder.
 */
import { readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '..', import.meta.url ) );
const php = ( name ) =>
	readFileSync( `${ root }scripts/blueprint/${ name }.php`, 'utf8' );

// The artwork travels inside the blueprint, so the preview needs no other files. This step unpacks it.
const art = readdirSync( `${ root }scripts/blueprint/art` )
	.filter( ( file ) => file.endsWith( '.webp' ) )
	.sort()
	.map(
		( file ) =>
			`'${ file }' => '${ readFileSync(
				`${ root }scripts/blueprint/art/${ file }`
			).toString( 'base64' ) }',`
	)
	.join( '\n' );
const unpack = `<?php
@mkdir( '/tmp/clanbite-demo-art', 0777, true );
foreach ( array(
${ art }
) as $name => $data ) {
	file_put_contents( '/tmp/clanbite-demo-art/' . $name, base64_decode( $data ) );
}
`;

const blueprint = {
	$schema: 'https://playground.wordpress.net/blueprint-schema.json',
	meta: {
		title: 'Clanbite demo community',
		description:
			'Clanbite with sample players, teams, matches and events.',
		author: 'kernowdev',
	},
	landingPage: '/',
	preferredVersions: { php: '8.3', wp: 'latest' },
	steps: [
		{ step: 'login', username: 'admin' },
		{
			step: 'setSiteOptions',
			options: { blogname: 'Clanbite Demo' },
		},
		{
			step: 'installPlugin',
			pluginData: {
				resource: 'wordpress.org/plugins',
				slug: 'clanbite',
			},
			options: { activate: true },
		},
		{ step: 'runPHP', code: php( 'settings' ) },
		{ step: 'runPHP', code: unpack },
		{ step: 'runPHP', code: php( 'content' ) },
		{ step: 'runPHP', code: php( 'design' ) },
	],
};

writeFileSync(
	`${ root }.wordpress-org/blueprints/blueprint.json`,
	`${ JSON.stringify( blueprint, null, '\t' ) }\n`
);
