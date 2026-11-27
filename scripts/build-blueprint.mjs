/**
 * Writes the WordPress.org Live Preview blueprint from the readable PHP in scripts/blueprint/.
 *
 * Run `npm run build:blueprint` after changing either file and commit the result. The release workflow copies
 * .wordpress-org/ (this blueprint included) to the plugin directory's assets folder.
 */
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import {
	existsSync,
	readdirSync,
	readFileSync,
	renameSync,
	rmSync,
	writeFileSync,
} from 'node:fs';
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

// Whether an archive is intact and holds exactly today's pictures.
const holdsTheArt = ( archive ) => {
	try {
		execFileSync( 'unzip', [ '-tqq', archive ], { stdio: 'ignore' } );
		const names = execFileSync( 'unzip', [ '-Z1', archive ] )
			.toString()
			.trim()
			.split( '\n' )
			.sort();

		return (
			names.join() === art.join() &&
			art.every( ( file ) =>
				execFileSync( 'unzip', [ '-p', archive, file ], {
					maxBuffer: 20 * 1024 * 1024,
				} ).equals( readFileSync( artDir + file ) )
			)
		);
	} catch {
		return false;
	}
};

// A sound zip is kept, so that rebuilding does not change it. A new one is made under a temporary name, checked, and only
// then moved into place: a half-written file is never left under the real name.
if ( ! existsSync( assets + zipName ) || ! holdsTheArt( assets + zipName ) ) {
	const draft = `${ assets }.draft-${ process.pid }-${ zipName }`;
	try {
		execFileSync( 'zip', [ '-X', '-q', '-j', draft, ...art ], {
			cwd: artDir,
		} );
		if ( ! holdsTheArt( draft ) ) {
			throw new Error( 'The new artwork zip failed its check.' );
		}
		renameSync( draft, assets + zipName );
	} finally {
		rmSync( draft, { force: true } );
	}
}

const blueprint = {
	$schema: 'https://playground.wordpress.net/blueprint-schema.json',
	meta: {
		title: 'Clanbite demo community',
		description: 'Clanbite with sample players, teams, matches and events.',
		author: 'kernowdev',
	},
	landingPage: '/',
	// The demo's own PHP downloads its pictures.
	features: { networking: true },
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
			step: 'runPHP',
			code: php( 'artwork' ).replaceAll( '{{ART_ZIP}}', zipName ),
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
