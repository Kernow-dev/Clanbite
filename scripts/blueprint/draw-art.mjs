/**
 * Draws the Live Preview's artwork (original vector and pixel art, no third-party images) into scripts/blueprint/art/.
 *
 * Needs Google Chrome and `cwebp`. Run `node scripts/blueprint/draw-art.mjs`, look at the results, then
 * `npm run build:blueprint`. Set CHROME to use another Chrome binary.
 */
import { execFileSync } from 'node:child_process';
import { mkdtempSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const out = fileURLToPath( new URL( './art/', import.meta.url ) );
const chrome =
	process.env.CHROME ||
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const work = mkdtempSync( join( tmpdir(), 'clanbite-art-' ) );

/** A repeatable "random" sequence, so the art is the same on every run. */
const random = ( seed ) => () => {
	seed = ( seed * 1664525 + 1013904223 ) % 4294967296;
	return seed / 4294967296;
};

function render( name, width, height, body, quality = 82 ) {
	const page = join( work, `${ name }.html` );
	const png = join( work, `${ name }.png` );
	writeFileSync(
		page,
		`<!doctype html><style>html,body{margin:0;width:${ width }px;height:${ height }px;overflow:hidden}svg{display:block}</style>` +
			`<svg xmlns="http://www.w3.org/2000/svg" width="${ width }" height="${ height }" viewBox="0 0 ${ width } ${ height }">${ body }</svg>`
	);
	// Headless Chrome will not make a window narrower than about 500px, so small art is drawn larger and cut down.
	const w = Math.max( width, 600 );
	const h = Math.max( height, 600 );
	execFileSync(
		chrome,
		[
			'--headless=new',
			'--disable-gpu',
			'--hide-scrollbars',
			'--force-device-scale-factor=1',
			`--window-size=${ w },${ h }`,
			`--screenshot=${ png }`,
			`file://${ page }`,
		],
		{ stdio: 'ignore' }
	);
	execFileSync( 'cwebp', [
		'-quiet',
		'-q',
		String( quality ),
		'-crop',
		'0',
		'0',
		String( width ),
		String( height ),
		png,
		'-o',
		`${ out }${ name }.webp`,
	] );
}

const grad = ( id, stops, x2 = 0, y2 = 1 ) =>
	`<linearGradient id="${ id }" x1="0" y1="0" x2="${ x2 }" y2="${ y2 }">${ stops
		.map(
			( [ at, colour, opacity = 1 ] ) =>
				`<stop offset="${ at }" stop-color="${ colour }" stop-opacity="${ opacity }"/>`
		)
		.join( '' ) }</linearGradient>`;
const rect = ( x, y, w, h, fill, extra = '' ) =>
	`<rect x="${ x }" y="${ y }" width="${ w }" height="${ h }" fill="${ fill }" ${ extra }/>`;
const poly = ( points, fill, extra = '' ) =>
	`<polygon points="${ points }" fill="${ fill }" ${ extra }/>`;
const circle = ( x, y, r, fill, extra = '' ) =>
	`<circle cx="${ x }" cy="${ y }" r="${ r }" fill="${ fill }" ${ extra }/>`;

/** Pixel art from rows of characters; `.` is empty. */
const pixels = ( rows, palette, x, y, size ) =>
	rows
		.map( ( row, j ) =>
			[ ...row ]
				.map( ( c, i ) =>
					palette[ c ]
						? rect(
								x + i * size,
								y + j * size,
								size + 0.5,
								size + 0.5,
								palette[ c ]
						  )
						: ''
				)
				.join( '' )
		)
		.join( '' );

// ---- Team badges ----
const badge = ( inner, from, to, edge ) =>
	`<defs>${ grad( 'b', [
		[ 0, from ],
		[ 1, to ],
	] ) }</defs>${ rect( 0, 0, 512, 512, 'url(#b)' ) }` +
	poly(
		'256,34 452,128 452,330 256,478 60,330 60,128',
		'none',
		`stroke="${ edge }" stroke-width="14" stroke-linejoin="round"`
	) +
	inner;

render(
	'team-night-owls',
	512,
	512,
	badge(
		poly( '130,140 208,176 256,166 304,176 382,140 372,310 256,420 140,310', '#e9e4ff' ) +
			poly( '130,140 232,214 256,196 280,214 382,140 360,232 256,268 152,232', '#8f7cf0' ) +
			circle( 197, 262, 52, '#ffd25a' ) +
			circle( 315, 262, 52, '#ffd25a' ) +
			circle( 197, 262, 24, '#15102e' ) +
			circle( 315, 262, 24, '#15102e' ) +
			circle( 208, 251, 8, '#fff' ) +
			circle( 326, 251, 8, '#fff' ) +
			poly( '256,282 284,312 256,366 228,312', '#ff9f43' ),
		'#2b2160',
		'#120d2b',
		'#8f7cf0'
	),
	88
);
render(
	'team-pixel-pirates',
	512,
	512,
	badge(
		pixels(
			[
				'....wwwwwwww....',
				'..wwwwwwwwwwww..',
				'.wwwwwwwwwwwwww.',
				'.wwwwwwwwwwwwww.',
				'rrrrrrrrrrrrrrrr',
				'rrrrrrrrrrrrrrrr',
				'.wkkkkwwwwkkkkw.',
				'.wkkkkwwwwkkkkw.',
				'.wkkkkwwwwkkkkw.',
				'.wwwwwwkkwwwwww.',
				'..wwwwkkkkwwww..',
				'....wwwwwwww....',
				'....wkwkkwkw....',
				'....wkwkkwkw....',
			],
			{ w: '#f4fbff', k: '#0c2530', r: '#ff4f8b' },
			128,
			132,
			16
		) + pixels( [ 'y......y', '.y....y.', '..y..y..' ], { y: '#ffd25a' }, 192, 364, 16 ),
		'#0f5e6e',
		'#072631',
		'#35e0d0'
	),
	88
);
render(
	'team-iron-wolves',
	512,
	512,
	badge(
		poly( '120,108 204,186 256,170 308,186 392,108 388,252 424,296 340,342 296,420 256,440 216,420 172,342 88,296 124,252', '#c9d3df' ) +
			poly( '120,108 204,186 150,236', '#7d8a99' ) +
			poly( '392,108 308,186 362,236', '#7d8a99' ) +
			poly( '256,170 308,186 290,300 256,336 222,300 204,186', '#eef2f7' ) +
			poly( '150,258 224,280 208,310 166,296', '#ff4d4d' ) +
			poly( '362,258 288,280 304,310 346,296', '#ff4d4d' ) +
			poly( '222,364 290,364 256,404', '#1b1f27' ) +
			poly( '172,342 222,364 216,420', '#97a3b3' ) +
			poly( '340,342 290,364 296,420', '#97a3b3' ),
		'#4a1620',
		'#17090d',
		'#ff4d4d'
	),
	88
);

// ---- Scenery ----
const stars = ( seed, count, w, h, colour = '#fff' ) => {
	const next = random( seed );
	return Array.from( { length: count }, () =>
		circle( next() * w, next() * h, 0.6 + next() * 1.8, colour, `opacity="${ 0.25 + next() * 0.7 }"` )
	).join( '' );
};
const ridge = ( seed, w, h, base, swing, fill, step = 90 ) => {
	const next = random( seed );
	const points = [ `0,${ h }` ];
	for ( let x = 0; x <= w + step; x += step ) {
		points.push( `${ x },${ base - next() * swing }` );
	}
	points.push( `${ w },${ h }` );
	return poly( points.join( ' ' ), fill );
};
const sky = ( w, h, stops ) =>
	`<defs>${ grad( 's', stops ) }</defs>${ rect( 0, 0, w, h, 'url(#s)' ) }`;

render(
	'banner-night-owls',
	1600,
	440,
	sky( 1600, 440, [ [ 0, '#100b2c' ], [ 0.65, '#33256e' ], [ 1, '#7a4fb0' ] ] ) +
		stars( 7, 220, 1600, 300 ) +
		circle( 1240, 120, 70, '#fdf0c4' ) +
		circle( 1268, 102, 62, '#1d1650' ) +
		ridge( 3, 1600, 440, 340, 130, '#2a1e5c' ) +
		ridge( 11, 1600, 440, 400, 110, '#1a123d', 70 ) +
		ridge( 19, 1600, 440, 440, 70, '#0d0923', 40 )
);
render(
	'banner-pixel-pirates',
	1600,
	440,
	( () => {
		let s = '';
		[ '#0b2a3c', '#10475a', '#16707a', '#35a39a', '#f08a7d', '#ffb36b', '#ffd98a' ].forEach( ( c, i ) => {
			s += rect( 0, i * 46, 1600, 47, c );
		} );
		s += pixels( [ '..yyyy..', '.yyyyyy.', 'yyyyyyyy', 'yyyyyyyy' ], { y: '#fff3b0' }, 704, 226, 24 );
		const next = random( 23 );
		[ '#0f6f8c', '#0b5570', '#083e54', '#05293a' ].forEach( ( c, band ) => {
			for ( let x = 0; x < 1600; x += 32 ) {
				const lift = Math.round( next() * 1.6 ) * 8;
				s += rect( x, 322 + band * 30 - lift, 33, 130, c );
			}
		} );
		return (
			s +
			pixels(
				[
					'......k.....',
					'.....kkkk...',
					'......k.....',
					'...wwwkwww..',
					'..wwwwkwwww.',
					'.wwwwwkwwwww',
					'......k.....',
					'kkkkkkkkkkkk',
					'.kkkkkkkkkk.',
					'..kkkkkkkk..',
				],
				{ k: '#0b1d2a', w: '#ffe9f1' },
				1130,
				190,
				16
			)
		);
	} )()
);
render(
	'banner-iron-wolves',
	1600,
	440,
	sky( 1600, 440, [ [ 0, '#1a0b10' ], [ 0.6, '#5a1822' ], [ 1, '#c2433a' ] ] ) +
		circle( 380, 330, 130, '#ff8a5c', 'opacity="0.85"' ) +
		poly( '0,440 260,180 420,300 640,90 860,300 1010,210 1240,360 1420,150 1600,300 1600,440', '#3a1119' ) +
		poly( '0,440 200,320 420,420 700,250 940,410 1180,280 1400,400 1600,330 1600,440', '#1c080d' ) +
		[ 0, 1, 2 ].map( ( i ) => poly( `${ 1080 + i * 74 },40 ${ 1100 + i * 74 },40 ${ 990 + i * 74 },290 ${ 984 + i * 74 },290`, '#ffd9cf', 'opacity="0.75"' ) ).join( '' )
);

render(
	'hero',
	1600,
	720,
	sky( 1600, 720, [ [ 0, '#0b0f17' ], [ 0.55, '#172238' ], [ 1, '#2c3f63' ] ] ) +
		stars( 41, 190, 1600, 400, '#cfe6ff' ) +
		`<defs>${ grad( 'sun', [ [ 0, '#e8f4ff' ], [ 1, '#5aa7e0' ] ] ) }</defs>` +
		circle( 800, 470, 200, 'url(#sun)' ) +
		[ 0, 1, 2, 3, 4 ].map( ( i ) => rect( 560, 478 + i * 26, 480, 5 + i * 3, '#1d2b47' ) ).join( '' ) +
		ridge( 5, 1600, 560, 540, 200, '#131c30', 110 ) +
		rect( 0, 540, 1600, 180, '#0b0f17' ) +
		Array.from( { length: 25 }, ( _, i ) => `<line x1="800" y1="540" x2="${ -1600 + i * 200 }" y2="720" stroke="#5aa7e0" stroke-width="1.5" opacity="0.55"/>` ).join( '' ) +
		[ 546, 556, 572, 596, 630, 678 ].map( ( y ) => rect( 0, y, 1600, 2, '#5aa7e0', 'opacity="0.5"' ) ).join( '' ),
	80
);

// ---- Player avatars: 16×16 characters ----
const base = [
	'................',
	'................',
	'....hhhhhhhh....',
	'...hhhhhhhhhh...',
	'...hhsssssshh...',
	'...hsssssssshh..',
	'...ssessssess...',
	'...ssessssess...',
	'...ssssssssss...',
	'....sssmmsss....',
	'....ssssssss....',
	'.....ssssss.....',
	'...ccccsscccc...',
	'..cccccccccccc..',
	'.cccccaccaccccc.',
	'.cccccaccaccccc.',
];
const wear = {
	headset: { 2: '....aaaaaaaa....', 3: '...ahhhhhhhha...', 6: '..aassessssessaa', 7: '..aassessssessaa', 8: '..aassssssssssa.', 10: '....sssssaaa....' },
	hood: { 1: '....cccccccc....', 2: '...cccccccccc...', 3: '..cccchhhhcccc..', 4: '..cchsssssshcc..', 5: '..ccsssssssscc..', 6: '..ccsessssescc..', 7: '..ccsessssescc..', 8: '..ccsssssssscc..', 9: '..cccssmmssccc..', 10: '..ccccsssscccc..' },
	visor: { 6: '...aaaaaaaaaa...', 7: '...aawwaaaaaa...' },
	cap: { 1: '....aaaaaaaa....', 2: '...aaaawwaaaa...', 3: '...aaaaaaaaaaaa.' },
	mohawk: { 0: '.......aa.......', 1: '......aaaa......', 2: '....hhaaaahh....' },
	mask: { 8: '...kkkkkkkkkk...', 9: '....kkkkkkkk....', 10: '....kkkkkkkk....' },
	helmet: { 1: '....aaaaaaaa....', 2: '...aaaaaaaaaa...', 3: '..aaaawwwwaaaa..', 4: '..aaaaaaaaaaaa..', 5: '..aasssssssssaa.' },
};
const characters = {
	admin: [ 'headset', '#1d2736', '#9ecbe8', '#f2c9a0', '#4a3325', '#2f4a6b' ],
	ember: [ 'mohawk', '#3a1410', '#ff6a3d', '#e8b48c', '#2a1712', '#7a2418' ],
	nightowl: [ 'hood', '#171236', '#ffd25a', '#c98f6a', '#241a14', '#4a3a8c' ],
	pixel: [ 'cap', '#07333a', '#ff4f8b', '#f5d0b0', '#c26a2e', '#16707a' ],
	rook: [ 'helmet', '#14201a', '#7fa36b', '#a8704e', '#1a120d', '#3c5233' ],
	sable: [ 'mask', '#201a24', '#c77dff', '#f0c4a4', '#121018', '#3b2d4d' ],
	vex: [ 'visor', '#0d2229', '#35e0d0', '#8d5a3b', '#e9eef5', '#1f3a44' ],
};
for ( const [ name, [ kind, bg, accent, skin, hair, cloth ] ] of Object.entries( characters ) ) {
	const rows = base.map( ( row, i ) => wear[ kind ][ i ] || row );
	render(
		`avatar-${ name }`,
		512,
		512,
		`<defs><radialGradient id="r" cx="0.5" cy="0.4" r="0.75"><stop offset="0" stop-color="${ accent }" stop-opacity="0.55"/><stop offset="1" stop-color="${ bg }"/></radialGradient></defs>` +
			rect( 0, 0, 512, 512, bg ) +
			rect( 0, 0, 512, 512, 'url(#r)' ) +
			pixels( rows, { h: hair, s: skin, e: '#15121c', m: '#8a4a3c', c: cloth, a: accent, w: '#f4fbff', k: '#17141f' }, 64, 80, 24 ) +
			rect( 64, 464, 384, 48, cloth ),
		90
	);
}

// ---- Player covers: one pattern each ----
const cover = ( name, from, to, draw ) =>
	render(
		`cover-${ name }`,
		1200,
		420,
		`<defs>${ grad( 'g', [ [ 0, from ], [ 1, to ] ], 1, 1 ) }</defs>` + rect( 0, 0, 1200, 420, 'url(#g)' ) + draw( random( name.length * 977 + name.charCodeAt( 0 ) ) ),
		74
	);
const many = ( n, fn ) => Array.from( { length: n }, ( _, i ) => fn( i ) ).join( '' );
cover( 'admin', '#101826', '#2b4a70', () => many( 90, ( i ) => { const x = ( i % 15 ) * 86 + ( Math.floor( i / 15 ) % 2 ) * 43; const y = Math.floor( i / 15 ) * 74; return poly( `${ x },${ y - 44 } ${ x + 38 },${ y - 22 } ${ x + 38 },${ y + 22 } ${ x },${ y + 44 } ${ x - 38 },${ y + 22 } ${ x - 38 },${ y - 22 }`, 'none', 'stroke="#9ecbe8" stroke-width="2" opacity="0.28"' ); } ) );
cover( 'ember', '#2a0b08', '#b23a1d', ( r ) => many( 70, () => { const x = r() * 1200; const y = r() * 420; const s = 14 + r() * 70; return poly( `${ x },${ y - s } ${ x + s * 0.6 },${ y + s * 0.5 } ${ x - s * 0.6 },${ y + s * 0.5 }`, '#ffb057', `opacity="${ 0.08 + r() * 0.3 }"` ); } ) );
cover( 'nightowl', '#0e0a26', '#43338f', ( r ) => stars( 5, 160, 1200, 420 ) + many( 6, () => circle( r() * 1200, r() * 420, 30 + r() * 90, 'none', 'stroke="#ffd25a" stroke-width="2" opacity="0.25"' ) ) );
cover( 'pixel', '#062c33', '#12808a', ( r ) => many( 190, () => rect( Math.floor( r() * 40 ) * 30, Math.floor( r() * 14 ) * 30, 28, 28, r() > 0.8 ? '#ff4f8b' : '#7ff3e6', `opacity="${ 0.1 + r() * 0.35 }"` ) ) );
cover( 'rook', '#0f1a12', '#4d6b3d', () => many( 14, ( i ) => `<path d="M0 ${ 30 * i } C 300 ${ 30 * i - 90 }, 700 ${ 30 * i + 110 }, 1200 ${ 30 * i - 30 }" fill="none" stroke="#cfe6b8" stroke-width="2" opacity="0.22"/>` ) );
cover( 'sable', '#150f1d', '#5b3a7e', ( r ) => many( 46, ( i ) => rect( i * 40 - 220, -60, 6 + r() * 16, 560, '#d9a6ff', `opacity="${ 0.05 + r() * 0.22 }" transform="rotate(24 ${ i * 40 } 210)"` ) ) );
cover( 'vex', '#061419', '#146070', ( r ) => many( 34, () => { const x = Math.floor( r() * 24 ) * 50; const y = Math.floor( r() * 9 ) * 48; const len = 60 + r() * 220; return `<path d="M${ x } ${ y } h${ len } l30 30 h${ len / 3 }" fill="none" stroke="#35e0d0" stroke-width="3" opacity="0.35"/>` + circle( x, y, 6, '#35e0d0', 'opacity="0.7"' ); } ) );

rmSync( work, { recursive: true, force: true } );
console.log( 'Artwork written to scripts/blueprint/art/' );
