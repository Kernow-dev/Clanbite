/**
 * Writes the WordPress.org Live Preview blueprint from the readable PHP in scripts/blueprint/.
 *
 * Run `npm run build:blueprint` after changing either file and commit the result. The release workflow copies
 * .wordpress-org/ (this blueprint included) to the plugin directory's assets folder.
 */
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { existsSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '..', import.meta.url ) );
const php = ( name ) =>
	readFileSync( `${ root }scripts/blueprint/${ name }.php`, 'utf8' );

// WordPress.org skips a blueprint over 100 KB (its plugin page then says "Missing or invalid blueprint.json"), so the
// artwork is not embedded. It goes in one zip beside the blueprint, which the release uploads to the same folder.
const LIMIT = 100 * 1024;
const assets = `${ root }.wordpress-org/blueprints/`;
const artDir = `${ root }scripts/blueprint/art/`;
const art = readdirSync( artDir )
	.filter( ( file ) => file.endsWith( '.webp' ) )
	.sort();

// The zip's name changes whenever a picture does, so a cached old zip is never used with a new demo.
const fingerprint = createHash( 'sha256' );
for ( const file of art ) {
	fingerprint.update( file ).update( readFileSync( artDir + file ) );
}
const zipName = `demo-art-${ fingerprint.digest( 'hex' ).slice( 0, 10 ) }.zip`;
if ( ! existsSync( assets + zipName ) ) {
	execFileSync( 'zip', [ '-X', '-q', '-j', assets + zipName, ...art ], {
		cwd: artDir,
	} );
}

const blueprint = {
	$schema: 'https://playground.wordpress.net/blueprint-schema.json',
	meta: {
		title: 'Clanbite demo community',
		description: 'Clanbite with sample players, teams, matches and events.',
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
		{
			step: 'unzip',
			zipFile: {
				resource: 'url',
				url: `https://ps.w.org/clanbite/assets/blueprints/${ zipName }`,
			},
			extractToPath: '/tmp/clanbite-demo-art',
		},
		{ step: 'runPHP', code: php( 'content' ) },
		{ step: 'runPHP', code: php( 'design' ) },
	],
};

const json = `${ JSON.stringify( blueprint, null, '\t' ) }\n`;
const size = Buffer.byteLength( json );
if ( size > LIMIT ) {
	throw new Error(
		`blueprint.json is ${ size } bytes; WordPress.org skips files over ${ LIMIT }.`
	);
}
writeFileSync( `${ assets }blueprint.json`, json );
process.stdout.write(
	`blueprint.json: ${ size } of ${ LIMIT } bytes. Artwork: ${ zipName }\n`
);
