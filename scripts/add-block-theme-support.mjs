/**
 * Merge theme.json-oriented `supports` and `selectors` into each block.json under src/blocks.
 * Run: node scripts/add-block-theme-support.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const root = path.resolve( __dirname, '..' );
const blocksRoot = path.join( root, 'src', 'blocks' );

function wpBlockClass( blockName ) {
	return '.wp-block-' + blockName.replace( '/', '-' );
}

/** Second object wins (theme author / existing block.json overrides defaults). */
function deepMergePreferSecond( base, over ) {
	if ( ! over || typeof over !== 'object' || Array.isArray( over ) ) {
		return base;
	}
	const out = { ...( base && typeof base === 'object' ? base : {} ) };
	for ( const key of Object.keys( over ) ) {
		const bv = out[ key ];
		const ov = over[ key ];
		if (
			ov &&
			typeof ov === 'object' &&
			! Array.isArray( ov ) &&
			bv &&
			typeof bv === 'object' &&
			! Array.isArray( bv )
		) {
			out[ key ] = deepMergePreferSecond( bv, ov );
		} else {
			out[ key ] = ov;
		}
	}
	return out;
}

const DEFAULT_SUPPORTS_BASE = {
	spacing: {
		margin: true,
		padding: true,
		blockGap: true,
	},
	border: {
		color: true,
		radius: true,
		style: true,
		width: true,
	},
	shadow: true,
	color: {
		text: true,
		background: true,
	},
};

const NO_BACKGROUND = new Set( [
	'clanbite/player-cover',
	'clanbite/team-cover',
] );

const COLOR_LINK = new Set( [
	'clanbite/team-name',
	'clanbite/player-display-name',
	'clanbite/team-profile-nav',
	'clanbite/player-profile-nav',
	'clanbite/user-nav',
	'clanbite/match-card',
	'clanbite/match-list',
] );

/**
 * @param {string} name
 * @returns {Record<string, unknown>}
 */
function buildSelectors( name ) {
	const wp = wpBlockClass( name );
	const table = {
		'clanbite/team-motto': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-team-motto__text`,
				background: wp,
			},
			typography: `${ wp } .clanbite-team-motto__text`,
			border: wp,
		},
		'clanbite/team-name': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-team-name__heading, ${ wp } .clanbite-team-name__link`,
				background: wp,
				link: `${ wp } .clanbite-team-name__link`,
			},
			typography: `${ wp } .clanbite-team-name__heading`,
			border: wp,
		},
		'clanbite/player-display-name': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-player-display-name__text, ${ wp } .clanbite-player-display-name__link`,
				background: wp,
				link: `${ wp } .clanbite-player-display-name__link`,
			},
			// Wrapper only: matches editor useBlockProps() and render.php wrapper. Inner __text/__link use font:inherit (style.scss).
			typography: wp,
			border: wp,
		},
		'clanbite/team-description': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-team-description__content`,
				background: wp,
			},
			typography: `${ wp } .clanbite-team-description__content`,
			border: wp,
		},
		'clanbite/team-code': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-team-code__value`,
				background: wp,
			},
			typography: `${ wp } .clanbite-team-code__value`,
			border: wp,
		},
		'clanbite/team-country': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-country-display__label, ${ wp } .clanbite-country-display__flag`,
				background: wp,
			},
			typography: `${ wp } .clanbite-country-display__label`,
			border: wp,
		},
		'clanbite/player-country': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-country-display__label, ${ wp } .clanbite-country-display__flag`,
				background: wp,
			},
			typography: `${ wp } .clanbite-country-display__label`,
			border: wp,
		},
		'clanbite/team-profile-nav': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-team-profile-nav__link`,
				background: wp,
				link: `${ wp } .clanbite-team-profile-nav__link`,
			},
			typography: `${ wp } .clanbite-team-profile-nav__link`,
			border: wp,
		},
		'clanbite/player-profile-nav': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-player-profile-nav__link`,
				background: wp,
				link: `${ wp } .clanbite-player-profile-nav__link`,
			},
			typography: `${ wp } .clanbite-player-profile-nav__link`,
			border: wp,
		},
		'clanbite/user-nav': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-user-nav__link`,
				background: wp,
				link: `${ wp } .clanbite-user-nav__link`,
			},
			typography: `${ wp } .clanbite-user-nav__link`,
			border: wp,
		},
		'clanbite/team-manage-link': {
			root: wp,
			color: {
				text: `${ wp } .wp-block-button__link`,
				background: `${ wp } .wp-block-button__link`,
			},
			typography: `${ wp } .wp-block-button__link`,
			border: wp,
		},
		'clanbite/player-settings-link': {
			root: wp,
			color: {
				text: `${ wp } .wp-block-button__link`,
				background: `${ wp } .wp-block-button__link`,
			},
			typography: `${ wp } .wp-block-button__link`,
			border: wp,
		},
		'clanbite/team-challenge-button': {
			root: wp,
			color: {
				text: `${ wp } .wp-block-button__link, ${ wp } button`,
				background: `${ wp } .wp-block-button__link, ${ wp } button`,
			},
			typography: `${ wp } .wp-block-button__link`,
			border: wp,
		},
		'clanbite/match-card': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-match-card, ${ wp } .clanbite-match-card a`,
				background: wp,
				link: `${ wp } .clanbite-match-card a`,
			},
			typography: `${ wp } .clanbite-match-card`,
			border: wp,
		},
		'clanbite/match-list': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-match-list, ${ wp } .clanbite-match-list__link`,
				background: wp,
				link: `${ wp } .clanbite-match-list__link`,
			},
			typography: `${ wp } .clanbite-match-list`,
			border: wp,
		},
		'clanbite/player-template': {
			root: wp,
			color: {
				text: wp,
				background: wp,
				link: `${ wp } a`,
			},
			typography: wp,
			border: wp,
		},
		'clanbite/player-cover': {
			root: wp,
			color: {
				text: `${ wp } .player-cover__content-container`,
				background: wp,
			},
			typography: `${ wp } .player-cover__content-container`,
			border: wp,
		},
		'clanbite/team-cover': {
			root: wp,
			color: {
				text: `${ wp } .team-cover__content-container`,
				background: wp,
			},
			typography: `${ wp } .team-cover__content-container`,
			border: wp,
		},
		'clanbite/player-avatar': {
			root: wp,
			color: { background: wp },
			border: `${ wp } .clanbite-player-avatar`,
		},
		'clanbite/team-avatar': {
			root: wp,
			color: { background: wp },
			border: `${ wp } .clanbite-team-avatar`,
		},
		'clanbite/notification-bell': {
			root: wp,
			color: {
				text: `${ wp } .clanbite-notification-bell__trigger, ${ wp } .clanbite-notification-bell__dropdown`,
				background: wp,
			},
			typography: `${ wp } .clanbite-notification-bell__trigger`,
			border: wp,
		},
	};
	if ( table[ name ] ) {
		return structuredClone( table[ name ] );
	}
	return {
		root: wp,
		color: wp,
		typography: wp,
		border: wp,
	};
}

function walkBlockJson( dir ) {
	const out = [];
	if ( ! fs.existsSync( dir ) ) {
		return out;
	}
	for ( const n of fs.readdirSync( dir ) ) {
		const p = path.join( dir, n );
		if ( fs.statSync( p ).isDirectory() ) {
			out.push( ...walkBlockJson( p ) );
		} else if ( n === 'block.json' ) {
			out.push( p );
		}
	}
	return out;
}

for ( const file of walkBlockJson( blocksRoot ) ) {
	const raw = fs.readFileSync( file, 'utf8' );
	const original = JSON.parse( raw );
	const name = original.name;
	if ( ! name || typeof name !== 'string' ) {
		continue;
	}

	let pack = structuredClone( DEFAULT_SUPPORTS_BASE );
	if ( NO_BACKGROUND.has( name ) && pack.color ) {
		pack.color = { ...pack.color, background: false };
	}
	if ( COLOR_LINK.has( name ) && pack.color ) {
		pack.color = { ...pack.color, link: true };
	}

	original.supports = deepMergePreferSecond( pack, original.supports || {} );

	const gen = buildSelectors( name );
	const prevSel = original.selectors || {};
	// Regenerate layout selectors each run; preserve only `filter` (e.g. duotone) from block.json.
	const preserve = prevSel.filter ? { filter: prevSel.filter } : {};
	original.selectors = deepMergePreferSecond( gen, preserve );

	fs.writeFileSync(
		file,
		JSON.stringify( original, null, '\t' ) + '\n',
		'utf8'
	);
	process.stdout.write( 'updated ' + path.relative( root, file ) + '\n' );
}
