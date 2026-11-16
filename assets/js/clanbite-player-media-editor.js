/**
 * Avatar and cover positioning in Player settings → Profile.
 *
 * Adds a "Position" editor (a draggable focal point and a zoom slider with a live preview, like the Cover block's focal point
 * control) and "Remove" buttons to the avatar and cover fields. Nothing is sent from here: Apply and Remove only fill the hidden form
 * fields (`profile_avatar_focal_x/y`, `profile_avatar_zoom`, `profile_cover_position_x/y`, `profile_cover_zoom` and
 * `profile_*_remove`), which the normal "Save changes" button submits.
 *
 * The preview uses the same maths as the server, so the saved image matches what is shown: the image fills its frame
 * (`object-fit: cover`) at `object-position: x% y%`, then is scaled by the zoom around the same point.
 *
 * @param {Window} window Browser window.
 */
( function ( window ) {
	'use strict';

	const document = window.document;
	const { __ } = window.wp && window.wp.i18n ? window.wp.i18n : { __: ( s ) => s };

	const root = () => document.querySelector( '[data-cp-media-editor]' );

	function config() {
		const el = root();
		if ( ! el ) {
			return null;
		}
		if ( ! el.__cpMedia ) {
			try {
				el.__cpMedia = JSON.parse( el.getAttribute( 'data-cp-media-editor' ) );
			} catch ( e ) {
				return null;
			}
			el.__cpMedia.pendingFile = { avatar: '', cover: '' };
		}
		return el.__cpMedia;
	}

	const field = ( name ) => document.querySelector( `input[name="${ name }"]` );
	const num = ( v, fallback ) => {
		const n = parseFloat( v );
		return Number.isFinite( n ) ? n : fallback;
	};
	const clamp = ( v, lo, hi ) => Math.min( hi, Math.max( lo, v ) );

	const NAMES = {
		avatar: { x: 'profile_avatar_focal_x', y: 'profile_avatar_focal_y', zoom: 'profile_avatar_zoom', remove: 'profile_avatar_remove' },
		cover: { x: 'profile_cover_position_x', y: 'profile_cover_position_y', zoom: 'profile_cover_zoom', remove: 'profile_cover_remove' },
	};

	function read( kind ) {
		const n = NAMES[ kind ];
		return {
			x: clamp( num( field( n.x )?.value, 0.5 ), 0, 1 ),
			y: clamp( num( field( n.y )?.value, 0.5 ), 0, 1 ),
			zoom: clamp( num( field( n.zoom )?.value, 1 ), 1, config()?.maxZoom || 4 ),
		};
	}

	function write( kind, pos ) {
		const n = NAMES[ kind ];
		[ [ n.x, pos.x ], [ n.y, pos.y ], [ n.zoom, pos.zoom ] ].forEach( ( [ name, value ] ) => {
			const input = field( name );
			if ( input ) {
				input.value = String( Math.round( value * 1000 ) / 1000 );
			}
		} );
	}

	/**
	 * Style an image the way the site draws it.
	 *
	 * @param {HTMLImageElement} img Image inside an overflow-hidden frame.
	 * @param {Object}           pos { x, y, zoom }.
	 */
	function applyPosition( img, pos ) {
		const origin = `${ pos.x * 100 }% ${ pos.y * 100 }%`;
		img.style.objectFit = 'cover';
		img.style.objectPosition = origin;
		img.style.transformOrigin = origin;
		img.style.transform = pos.zoom > 1 ? `scale(${ pos.zoom })` : '';
	}

	function previewImg( kind ) {
		return document.querySelector( `.${ kind }-preview img.cp-media-preview__img` );
	}

	function note( text ) {
		const el = document.querySelector( '.cp-media-note' );
		if ( ! el ) {
			return;
		}
		el.textContent = text || '';
		el.hidden = ! text;
	}

	function syncButtons( kind, hasImage ) {
		document
			.querySelectorAll( `[data-cp-media-open="${ kind }"], [data-cp-media-remove="${ kind }"]` )
			.forEach( ( b ) => {
				b.hidden = ! hasImage;
				b.classList.remove( 'is-armed' );
			} );
	}

	/** The image the editor works on: a file chosen but not saved yet, otherwise the stored original. */
	function sourceUrl( kind ) {
		const cfg = config();
		if ( cfg.pendingFile[ kind ] ) {
			return cfg.pendingFile[ kind ];
		}
		return kind === 'avatar' ? cfg.state.avatarSourceUrl || cfg.state.avatarUrl : cfg.state.coverUrl;
	}

	/* ---------------------------------------------------------------- Editor dialog */

	let dialog = null;

	function openEditor( kind ) {
		const cfg = config();
		const url = sourceUrl( kind );
		if ( ! cfg || ! url ) {
			return;
		}
		const i18n = cfg.i18n;
		let pos = read( kind );
		const lastFocus = document.activeElement;

		closeEditor( false );
		dialog = document.createElement( 'div' );
		dialog.className = 'cp-media-dialog';
		dialog.setAttribute( 'role', 'dialog' );
		dialog.setAttribute( 'aria-modal', 'true' );
		dialog.setAttribute( 'aria-label', kind === 'avatar' ? i18n.avatarTitle : i18n.coverTitle );

		const panel = document.createElement( 'div' );
		panel.className = 'cp-media-dialog__panel';
		dialog.appendChild( panel );

		const title = document.createElement( 'h2' );
		title.className = 'cp-media-dialog__title';
		title.textContent = kind === 'avatar' ? i18n.avatarTitle : i18n.coverTitle;
		panel.appendChild( title );

		const help = document.createElement( 'p' );
		help.className = 'cp-media-dialog__help';
		help.textContent = i18n.help;
		panel.appendChild( help );

		const body = document.createElement( 'div' );
		body.className = `cp-media-dialog__body is-${ kind }`;
		panel.appendChild( body );

		// Stage: the whole image with the focal point to drag.
		const stage = document.createElement( 'div' );
		stage.className = 'cp-media-stage';
		const stageImg = document.createElement( 'img' );
		stageImg.src = url;
		stageImg.alt = '';
		stageImg.draggable = false;
		const dot = document.createElement( 'button' );
		dot.type = 'button';
		dot.className = 'cp-media-stage__dot';
		dot.setAttribute( 'aria-label', i18n.focalLabel );
		stage.appendChild( stageImg );
		stage.appendChild( dot );
		body.appendChild( stage );

		// Preview: the result, drawn like the site draws it.
		const previewWrap = document.createElement( 'div' );
		previewWrap.className = 'cp-media-preview';
		const previewLabel = document.createElement( 'span' );
		previewLabel.className = 'cp-media-preview__label';
		previewLabel.textContent = i18n.previewLabel;
		const frame = document.createElement( 'div' );
		frame.className = `cp-media-preview__frame is-${ kind }`;
		const frameImg = document.createElement( 'img' );
		frameImg.src = url;
		frameImg.alt = '';
		frame.appendChild( frameImg );
		previewWrap.appendChild( previewLabel );
		previewWrap.appendChild( frame );
		body.appendChild( previewWrap );

		// Zoom.
		const zoomRow = document.createElement( 'label' );
		zoomRow.className = 'cp-media-dialog__zoom';
		const zoomText = document.createElement( 'span' );
		zoomText.textContent = i18n.zoomLabel;
		const zoom = document.createElement( 'input' );
		zoom.type = 'range';
		zoom.min = '1';
		zoom.max = String( cfg.maxZoom || 4 );
		zoom.step = '0.01';
		zoomRow.appendChild( zoomText );
		zoomRow.appendChild( zoom );
		panel.appendChild( zoomRow );

		// Buttons.
		const actions = document.createElement( 'div' );
		actions.className = 'cp-media-dialog__actions';
		const mk = ( label, cls, onClick ) => {
			const b = document.createElement( 'button' );
			b.type = 'button';
			b.className = `wp-element-button clanbite-button clanbite-button--small ${ cls }`;
			b.textContent = label;
			b.addEventListener( 'click', onClick );
			actions.appendChild( b );
			return b;
		};
		mk( i18n.reset, 'clanbite-button--ghost cp-media-dialog__reset', () => {
			pos = { x: 0.5, y: 0.5, zoom: 1 };
			render();
		} );
		const spacer = document.createElement( 'span' );
		spacer.className = 'cp-media-dialog__spacer';
		actions.appendChild( spacer );
		mk( i18n.cancel, 'clanbite-button--ghost', () => closeEditor( true, lastFocus ) );
		const applyBtn = mk( i18n.apply, 'clanbite-button--primary', () => {
			write( kind, pos );
			const img = previewImg( kind );
			if ( img ) {
				applyPosition( img, pos );
			}
			note( i18n.applied );
			closeEditor( true, lastFocus );
		} );
		panel.appendChild( actions );

		function render() {
			dot.style.left = `${ pos.x * 100 }%`;
			dot.style.top = `${ pos.y * 100 }%`;
			zoom.value = String( pos.zoom );
			applyPosition( frameImg, pos );
		}

		function setFromPointer( event ) {
			const rect = stageImg.getBoundingClientRect();
			if ( ! rect.width || ! rect.height ) {
				return;
			}
			pos.x = clamp( ( event.clientX - rect.left ) / rect.width, 0, 1 );
			pos.y = clamp( ( event.clientY - rect.top ) / rect.height, 0, 1 );
			render();
		}

		stage.addEventListener( 'pointerdown', ( event ) => {
			stage.setPointerCapture( event.pointerId );
			setFromPointer( event );
			stage.classList.add( 'is-dragging' );
		} );
		stage.addEventListener( 'pointermove', ( event ) => {
			if ( stage.hasPointerCapture( event.pointerId ) ) {
				setFromPointer( event );
			}
		} );
		const endDrag = ( event ) => {
			if ( stage.hasPointerCapture( event.pointerId ) ) {
				stage.releasePointerCapture( event.pointerId );
			}
			stage.classList.remove( 'is-dragging' );
		};
		stage.addEventListener( 'pointerup', endDrag );
		stage.addEventListener( 'pointercancel', endDrag );
		dot.addEventListener( 'keydown', ( event ) => {
			const step = event.shiftKey ? 0.1 : 0.02;
			const moves = { ArrowLeft: [ -step, 0 ], ArrowRight: [ step, 0 ], ArrowUp: [ 0, -step ], ArrowDown: [ 0, step ] };
			if ( moves[ event.key ] ) {
				event.preventDefault();
				pos.x = clamp( pos.x + moves[ event.key ][ 0 ], 0, 1 );
				pos.y = clamp( pos.y + moves[ event.key ][ 1 ], 0, 1 );
				render();
			}
		} );
		zoom.addEventListener( 'input', () => {
			pos.zoom = clamp( num( zoom.value, 1 ), 1, cfg.maxZoom || 4 );
			render();
		} );
		dialog.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' ) {
				event.stopPropagation();
				closeEditor( true, lastFocus );
			}
		} );
		dialog.addEventListener( 'mousedown', ( event ) => {
			if ( event.target === dialog ) {
				closeEditor( true, lastFocus );
			}
		} );

		document.body.appendChild( dialog );
		render();
		applyBtn.focus();
	}

	function closeEditor( restore, lastFocus ) {
		if ( dialog ) {
			dialog.remove();
			dialog = null;
		}
		if ( restore && lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	/* ---------------------------------------------------------------- Remove / reset */

	function armOrRemove( button ) {
		const kind = button.getAttribute( 'data-cp-media-remove' );
		const cfg = config();
		if ( ! cfg ) {
			return;
		}
		if ( ! button.classList.contains( 'is-armed' ) ) {
			// First click only asks: a second click within a few seconds removes.
			button.classList.add( 'is-armed' );
			button.dataset.label = button.textContent;
			button.textContent = cfg.i18n.confirmRemove;
			window.setTimeout( () => {
				if ( button.classList.contains( 'is-armed' ) ) {
					button.classList.remove( 'is-armed' );
					button.textContent = button.dataset.label || button.textContent;
				}
			}, 4000 );
			return;
		}
		button.classList.remove( 'is-armed' );
		button.textContent = button.dataset.label || button.textContent;
		const removeField = field( NAMES[ kind ].remove );
		if ( removeField ) {
			removeField.value = '1';
		}
		write( kind, { x: 0.5, y: 0.5, zoom: 1 } );
		cfg.pendingFile[ kind ] = '';
		const input = document.querySelector( kind === 'avatar' ? 'input[name="profile_avatar"]' : 'input[name="profile_cover"]' );
		if ( input ) {
			input.value = '';
		}
		const img = previewImg( kind );
		if ( img ) {
			img.src = kind === 'avatar' ? cfg.defaultAvatar : cfg.defaultCover;
			applyPosition( img, { x: 0.5, y: 0.5, zoom: 1 } );
		}
		syncButtons( kind, false );
		note( cfg.i18n.removed );
	}

	/* ---------------------------------------------------------------- Events */

	document.addEventListener( 'click', ( event ) => {
		const open = event.target.closest && event.target.closest( '[data-cp-media-open]' );
		if ( open ) {
			event.preventDefault();
			openEditor( open.getAttribute( 'data-cp-media-open' ) );
			return;
		}
		const remove = event.target.closest && event.target.closest( '[data-cp-media-remove]' );
		if ( remove ) {
			event.preventDefault();
			armOrRemove( remove );
		}
	} );

	// A new file chosen: it replaces the image, so the position starts in the centre and a pending removal is cancelled.
	document.addEventListener( 'change', ( event ) => {
		const input = event.target;
		if ( ! ( input instanceof window.HTMLInputElement ) || input.type !== 'file' ) {
			return;
		}
		const kind = input.name === 'profile_avatar' ? 'avatar' : input.name === 'profile_cover' ? 'cover' : '';
		const cfg = config();
		if ( ! kind || ! cfg || ! input.files || ! input.files[ 0 ] ) {
			return;
		}
		const url = window.URL.createObjectURL( input.files[ 0 ] );
		cfg.pendingFile[ kind ] = url;
		write( kind, { x: 0.5, y: 0.5, zoom: 1 } );
		const removeField = field( NAMES[ kind ].remove );
		if ( removeField ) {
			removeField.value = '0';
		}
		const img = previewImg( kind );
		if ( img ) {
			img.src = url;
			applyPosition( img, { x: 0.5, y: 0.5, zoom: 1 } );
		}
		syncButtons( kind, true );
		note( cfg.i18n.chosen );
	} );

	// After a successful save the page tells us what is stored now.
	document.addEventListener( 'clanbite:player-settings-saved', ( event ) => {
		const data = event.detail || {};
		const cfg = config();
		if ( ! cfg ) {
			return;
		}
		cfg.state = Object.assign( cfg.state, data );
		cfg.pendingFile = { avatar: '', cover: '' };
		[ 'avatar', 'cover' ].forEach( ( kind ) => {
			const removeField = field( NAMES[ kind ].remove );
			if ( removeField ) {
				removeField.value = '0';
			}
			const has = kind === 'avatar' ? data.hasAvatar : data.hasCover;
			if ( typeof has === 'boolean' ) {
				syncButtons( kind, has );
			}
		} );
		if ( data.avatarFocal ) {
			write( 'avatar', data.avatarFocal );
		}
		// The saved avatar is already cut to the chosen part: show it as it is.
		const avatarImg = previewImg( 'avatar' );
		if ( avatarImg ) {
			applyPosition( avatarImg, { x: 0.5, y: 0.5, zoom: 1 } );
		}
		if ( data.coverPosition ) {
			write( 'cover', data.coverPosition );
			const cover = previewImg( 'cover' );
			if ( cover ) {
				applyPosition( cover, data.coverPosition );
			}
		}
		note( '' );
	} );

	void __;
} )( window );
