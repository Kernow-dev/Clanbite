/**
 * Avatar and cover editor in Player settings → Profile.
 *
 * "Set avatar" and "Set cover image" open a small popup: choose an image, drag the focal point to pick which part is shown, zoom,
 * reset the position, or remove the image to go back to the default. Nothing is sent from here: Apply and Remove only fill the hidden
 * form fields (`profile_avatar_focal_x/y`, `profile_avatar_zoom`, `profile_cover_position_x/y`, `profile_cover_zoom`,
 * `profile_*_remove`) and the file inputs, which the normal "Save changes" button submits.
 *
 * The preview uses the same maths as the server, so the saved image matches what is shown: the image fills its frame
 * (`object-fit: cover`) at `object-position: x% y%`, then is scaled by the zoom around the same point.
 *
 * Extend the popup with `wp.hooks`: the action `clanbite.playerMedia.dialog` (add your own controls) and the filter
 * `clanbite.playerMedia.maxZoom`.
 *
 * @param {Window} window Browser window.
 */
( function ( window ) {
	'use strict';

	const document = window.document;

	const rootEl = () =>
		document.querySelector( '[data-clanbite-media-editor]' );

	function config() {
		const editorRoot = rootEl();
		if ( ! editorRoot ) {
			return null;
		}
		if ( ! editorRoot.__clanbiteMedia ) {
			try {
				editorRoot.__clanbiteMedia = JSON.parse(
					editorRoot.getAttribute( 'data-clanbite-media-editor' )
				);
			} catch {
				return null;
			}
			editorRoot.__clanbiteMedia.pending = { avatar: '', cover: '' };
		}
		return editorRoot.__clanbiteMedia;
	}

	const field = ( name ) =>
		document.querySelector( `input[name="${ name }"]` );
	const num = ( v, fallback ) => {
		const n = parseFloat( v );
		return Number.isFinite( n ) ? n : fallback;
	};
	const clamp = ( v, lo, hi ) => Math.min( hi, Math.max( lo, v ) );
	const CENTRE = () => ( { x: 0.5, y: 0.5, zoom: 1 } );

	const NAMES = {
		avatar: {
			x: 'profile_avatar_focal_x',
			y: 'profile_avatar_focal_y',
			zoom: 'profile_avatar_zoom',
			remove: 'profile_avatar_remove',
			file: 'profile_avatar',
		},
		cover: {
			x: 'profile_cover_position_x',
			y: 'profile_cover_position_y',
			zoom: 'profile_cover_zoom',
			remove: 'profile_cover_remove',
			file: 'profile_cover',
		},
	};

	function maxZoom( kind ) {
		const cfg = config();
		const base = ( cfg && cfg.maxZoom ) || 4;
		const hooks = window.wp && window.wp.hooks;
		if ( hooks && hooks.applyFilters ) {
			/**
			 * Filters the largest zoom the avatar and cover editor offers.
			 *
			 * @param {number} zoom Largest zoom (the server clamps to 4).
			 * @param {string} kind `avatar` or `cover`.
			 */
			return clamp(
				num(
					hooks.applyFilters(
						'clanbite.playerMedia.maxZoom',
						base,
						kind
					),
					base
				),
				1,
				4
			);
		}
		return base;
	}

	function readPos( kind ) {
		const n = NAMES[ kind ];
		return {
			x: clamp( num( field( n.x )?.value, 0.5 ), 0, 1 ),
			y: clamp( num( field( n.y )?.value, 0.5 ), 0, 1 ),
			zoom: clamp( num( field( n.zoom )?.value, 1 ), 1, 4 ),
		};
	}

	function writePos( kind, pos ) {
		const n = NAMES[ kind ];
		[
			[ n.x, pos.x ],
			[ n.y, pos.y ],
			[ n.zoom, pos.zoom ],
		].forEach( ( [ name, value ] ) => {
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

	/**
	 * The image in the page preview: the cover banner or the avatar (the shared avatar's picture).
	 *
	 * @param {string} kind `avatar` or `cover`.
	 */
	const previewImg = ( kind ) =>
		document.querySelector( `.${ kind }-preview img` );

	/**
	 * Shape and ratio: the cover frame follows the real cover on this page, so the preview is cropped the way the site crops it.
	 *
	 * @param {HTMLElement} target Preview frame that gets the ratio.
	 */
	function setCoverRatio( target ) {
		const cover = document.querySelector( '.clanbite-cover' );
		const rect = cover && cover.getBoundingClientRect();
		if ( rect && rect.width > 0 && rect.height > 0 ) {
			target.style.setProperty(
				'--clanbite-media-cover-ratio',
				`${ Math.round( rect.width ) } / ${ Math.round( rect.height ) }`
			);
		}
	}

	/** Shape of the avatar on this site (hexagon, circle, …), read from the preview so the popup matches. */
	function avatarShape() {
		const avatarEl = document.querySelector(
			'.avatar-preview .clanbite-avatar'
		);
		const m =
			avatarEl &&
			/clanbite-avatar--shape-([a-z-]+)/.exec( avatarEl.className );
		return m ? m[ 1 ] : 'circle';
	}

	function note( text ) {
		const noteEl = document.querySelector( '.clanbite-media-note' );
		if ( noteEl ) {
			noteEl.textContent = text || '';
			noteEl.hidden = ! text;
		}
	}

	/**
	 * Is there an uploaded image (stored, or chosen and not saved yet)?
	 *
	 * @param {string} kind `avatar` or `cover`.
	 */
	function hasImage( kind ) {
		if ( field( NAMES[ kind ].remove )?.value === '1' ) {
			return false;
		}
		const cfg = config();
		if ( cfg.pending[ kind ] ) {
			return true;
		}
		return kind === 'avatar'
			? !! cfg.state.hasAvatar
			: !! cfg.state.hasCover;
	}

	function sourceUrl( kind ) {
		const cfg = config();
		if ( cfg.pending[ kind ] ) {
			return cfg.pending[ kind ];
		}
		if ( ! hasImage( kind ) ) {
			return '';
		}
		return kind === 'avatar'
			? cfg.state.avatarSourceUrl || cfg.state.avatarUrl
			: cfg.state.coverUrl;
	}

	/* ---------------------------------------------------------------- Popup */

	let dialog = null;
	let session = null; // State of the open popup: { kind, pos, refresh }.

	function el( tag, className, text ) {
		const node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	}

	function button( label, className, onClick ) {
		const b = el(
			'button',
			`wp-element-button clanbite-button clanbite-button--small ${ className }`,
			label
		);
		b.type = 'button';
		b.addEventListener( 'click', onClick );
		return b;
	}

	function openEditor( kind ) {
		const cfg = config();
		if ( ! cfg ) {
			return;
		}
		const t = cfg.i18n;
		const lastFocus = rootEl().ownerDocument.activeElement;
		const limit = maxZoom( kind );
		let pos = readPos( kind );
		let armed = false;

		closeEditor( false );
		dialog = el( 'div', 'clanbite-media-dialog' );
		dialog.setAttribute( 'role', 'dialog' );
		dialog.setAttribute( 'aria-modal', 'true' );
		dialog.setAttribute(
			'aria-label',
			kind === 'avatar' ? t.avatarTitle : t.coverTitle
		);

		const panel = el( 'div', 'clanbite-media-dialog__panel' );
		dialog.appendChild( panel );
		panel.appendChild(
			el(
				'h2',
				'clanbite-media-dialog__title',
				kind === 'avatar' ? t.avatarTitle : t.coverTitle
			)
		);

		const body = el( 'div', `clanbite-media-dialog__body is-${ kind }` );
		panel.appendChild( body );

		// Stage: the whole image with the focal point to drag.
		const stage = el( 'div', 'clanbite-media-stage' );
		const stageImg = el( 'img' );
		stageImg.alt = '';
		stageImg.draggable = false;
		const dot = el( 'button', 'clanbite-media-stage__dot' );
		dot.type = 'button';
		dot.setAttribute( 'aria-label', t.focalLabel );
		const empty = el( 'p', 'clanbite-media-stage__empty', t.noImage );
		stage.append( stageImg, dot, empty );
		body.appendChild( stage );

		// Preview: the result, drawn like the site draws it.
		const previewWrap = el( 'div', 'clanbite-media-preview' );
		previewWrap.appendChild(
			el( 'span', 'clanbite-media-preview__label', t.previewLabel )
		);
		let frame;
		let frameImg;
		const pageAvatar =
			kind === 'avatar'
				? document.querySelector( '.avatar-preview .clanbite-avatar' )
				: null;
		if ( pageAvatar ) {
			// The avatar exactly as the site draws it (shape, XP ring, badge), so the preview is what will be shown.
			frame = pageAvatar.cloneNode( true );
			frame.className =
				frame.className.replace(
					/clanbite-avatar--(?:xs|sm|small|medium|large|xl)\b/g,
					''
				) + ' clanbite-avatar--large clanbite-media-preview__avatar';
			frameImg =
				frame.querySelector( '.clanbite-avatar__img' ) ||
				frame.querySelector( 'img' );
		}
		if ( ! frameImg ) {
			frame = el( 'div', `clanbite-media-preview__frame is-${ kind }` );
			if ( kind === 'avatar' ) {
				frame.dataset.shape = avatarShape();
			}
			frameImg = el( 'img' );
			frame.appendChild( frameImg );
		}
		frameImg.alt = '';
		frameImg.removeAttribute( 'srcset' );
		frameImg.removeAttribute( 'loading' );
		previewWrap.appendChild( frame );
		body.appendChild( previewWrap );

		// Zoom and reset.
		const controls = el( 'div', 'clanbite-media-dialog__controls' );
		const zoomRow = el( 'label', 'clanbite-media-dialog__zoom' );
		zoomRow.appendChild( el( 'span', '', t.zoomLabel ) );
		const zoom = el( 'input' );
		zoom.type = 'range';
		zoom.min = '1';
		zoom.max = String( limit );
		zoom.step = '0.01';
		zoomRow.appendChild( zoom );
		controls.appendChild( zoomRow );
		const resetBtn = button( t.reset, 'clanbite-button--ghost', () => {
			pos = CENTRE();
			render();
		} );
		controls.appendChild( resetBtn );
		panel.appendChild( controls );
		panel.appendChild( el( 'p', 'clanbite-media-dialog__help', t.help ) );

		// Room for add-ons to add their own controls.
		const extras = el( 'div', 'clanbite-media-dialog__extras' );
		panel.appendChild( extras );

		// Footer.
		const actions = el( 'div', 'clanbite-media-dialog__actions' );
		const chooseBtn = button( '', 'clanbite-button--ghost', () =>
			field( NAMES[ kind ].file )?.click()
		);
		const removeBtn = button(
			t.remove,
			'clanbite-button--ghost clanbite-media-dialog__remove',
			() => {
				if ( ! armed ) {
					armed = true;
					removeBtn.textContent = t.confirmRemove;
					window.setTimeout( () => {
						armed = false;
						removeBtn.textContent = t.remove;
					}, 4000 );
					return;
				}
				removeImage( kind );
				closeEditor( true, lastFocus );
			}
		);
		const spacer = el( 'span', 'clanbite-media-dialog__spacer' );
		const cancelBtn = button( t.cancel, 'clanbite-button--ghost', () =>
			closeEditor( true, lastFocus )
		);
		const applyBtn = button( t.apply, 'clanbite-button--primary', () => {
			writePos( kind, pos );
			const img = previewImg( kind );
			if ( img && hasImage( kind ) ) {
				applyPosition( img, pos );
			}
			note( t.applied );
			closeEditor( true, lastFocus );
		} );
		actions.append( chooseBtn, removeBtn, spacer, cancelBtn, applyBtn );
		panel.appendChild( actions );

		function render() {
			const url = sourceUrl( kind );
			const has = !! url;
			stage.classList.toggle( 'is-empty', ! has );
			if ( has ) {
				stageImg.src = url;
				frameImg.src = url;
			} else {
				stageImg.removeAttribute( 'src' );
				frameImg.src =
					kind === 'avatar' ? cfg.defaultAvatar : cfg.defaultCover;
			}
			dot.hidden = ! has;
			dot.style.left = `${ pos.x * 100 }%`;
			dot.style.top = `${ pos.y * 100 }%`;
			zoom.value = String( pos.zoom );
			zoom.disabled = ! has;
			resetBtn.disabled = ! has;
			removeBtn.hidden = ! has;
			chooseBtn.textContent = has ? t.change : t.choose;
			applyPosition( frameImg, has ? pos : CENTRE() );
		}
		session = {
			kind,
			setPos: ( p ) => {
				pos = p;
			},
			render,
		};

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
			if ( stage.classList.contains( 'is-empty' ) ) {
				return;
			}
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
			const moves = {
				ArrowLeft: [ -step, 0 ],
				ArrowRight: [ step, 0 ],
				ArrowUp: [ 0, -step ],
				ArrowDown: [ 0, step ],
			};
			if ( moves[ event.key ] ) {
				event.preventDefault();
				pos.x = clamp( pos.x + moves[ event.key ][ 0 ], 0, 1 );
				pos.y = clamp( pos.y + moves[ event.key ][ 1 ], 0, 1 );
				render();
			}
		} );
		zoom.addEventListener( 'input', () => {
			pos.zoom = clamp( num( zoom.value, 1 ), 1, limit );
			render();
		} );

		dialog.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' ) {
				event.stopPropagation();
				closeEditor( true, lastFocus );
			} else if ( event.key === 'Tab' ) {
				// Keep focus inside the popup.
				const items = Array.from(
					dialog.querySelectorAll(
						'button:not([hidden]):not(:disabled), input:not(:disabled)'
					)
				).filter( ( n ) => n.offsetParent !== null );
				if ( items.length ) {
					const first = items[ 0 ];
					const last = items[ items.length - 1 ];
					if (
						event.shiftKey &&
						dialog.ownerDocument.activeElement === first
					) {
						event.preventDefault();
						last.focus();
					} else if (
						! event.shiftKey &&
						dialog.ownerDocument.activeElement === last
					) {
						event.preventDefault();
						first.focus();
					}
				}
			}
		} );
		dialog.addEventListener( 'mousedown', ( event ) => {
			if ( event.target === dialog ) {
				closeEditor( true, lastFocus );
			}
		} );

		setCoverRatio( dialog );
		document.body.appendChild( dialog );
		render();
		( sourceUrl( kind ) ? applyBtn : chooseBtn ).focus();

		/**
		 * Fires when the avatar or cover popup opens, so add-ons can add their own controls to it.
		 *
		 * @param {Object}                            context             What is open.
		 * @param {string}                            context.kind        `avatar` or `cover`.
		 * @param {HTMLElement}                       context.dialog      The popup element.
		 * @param {HTMLElement}                       context.extras      An empty container for your controls.
		 * @param {( ...args: unknown[] ) => unknown} context.getPosition Returns the current `{ x, y, zoom }`.
		 * @param {( ...args: unknown[] ) => unknown} context.setPosition Sets the position (`{ x, y, zoom }`) and redraws the preview.
		 */
		window.wp?.hooks?.doAction?.( 'clanbite.playerMedia.dialog', {
			kind,
			dialog,
			extras,
			getPosition: () => ( { ...pos } ),
			setPosition: ( next ) => {
				pos = {
					x: clamp( num( next?.x, pos.x ), 0, 1 ),
					y: clamp( num( next?.y, pos.y ), 0, 1 ),
					zoom: clamp( num( next?.zoom, pos.zoom ), 1, limit ),
				};
				render();
			},
		} );
	}

	function closeEditor( restore, lastFocus ) {
		if ( dialog ) {
			dialog.remove();
			dialog = null;
		}
		session = null;
		if ( restore && lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	/**
	 * Remove the image: the default is shown and the form is told to remove it when saved.
	 *
	 * @param {string} kind `avatar` or `cover`.
	 */
	function removeImage( kind ) {
		const cfg = config();
		const removeField = field( NAMES[ kind ].remove );
		if ( removeField ) {
			removeField.value = '1';
		}
		cfg.pending[ kind ] = '';
		const input = field( NAMES[ kind ].file );
		if ( input ) {
			input.value = '';
		}
		writePos( kind, CENTRE() );
		const img = previewImg( kind );
		if ( img ) {
			img.src = kind === 'avatar' ? cfg.defaultAvatar : cfg.defaultCover;
			applyPosition( img, CENTRE() );
		}
		note( cfg.i18n.removed );
	}

	/* ---------------------------------------------------------------- Events */

	const initRatio = () => {
		const root = rootEl();
		if ( root ) {
			setCoverRatio( root );
		}
	};
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initRatio );
	} else {
		initRatio();
	}

	document.addEventListener( 'click', ( event ) => {
		const open =
			event.target.closest &&
			event.target.closest( '[data-clanbite-media-open]' );
		if ( open ) {
			event.preventDefault();
			openEditor( open.getAttribute( 'data-clanbite-media-open' ) );
		}
	} );

	// A file chosen: it replaces the image straight away, the position starts in the centre and a pending removal is cancelled.
	document.addEventListener( 'change', ( event ) => {
		const input = event.target;
		if (
			! ( input instanceof window.HTMLInputElement ) ||
			input.type !== 'file'
		) {
			return;
		}
		let kind = '';
		if ( input.name === NAMES.avatar.file ) {
			kind = 'avatar';
		} else if ( input.name === NAMES.cover.file ) {
			kind = 'cover';
		}
		const cfg = config();
		if ( ! kind || ! cfg || ! input.files || ! input.files[ 0 ] ) {
			return;
		}
		const url = window.URL.createObjectURL( input.files[ 0 ] );
		cfg.pending[ kind ] = url;
		writePos( kind, CENTRE() );
		const removeField = field( NAMES[ kind ].remove );
		if ( removeField ) {
			removeField.value = '0';
		}
		const img = previewImg( kind );
		if ( img ) {
			img.src = url;
			applyPosition( img, CENTRE() );
		}
		note( cfg.i18n.applied );
		if ( session && session.kind === kind ) {
			session.setPos( CENTRE() );
			session.render();
		}
	} );

	// After a successful save the page tells us what is stored now.
	document.addEventListener( 'clanbite:player-settings-saved', ( event ) => {
		const data = event.detail || {};
		const cfg = config();
		if ( ! cfg ) {
			return;
		}
		cfg.state = Object.assign( cfg.state, data );
		cfg.pending = { avatar: '', cover: '' };
		[ 'avatar', 'cover' ].forEach( ( kind ) => {
			const removeField = field( NAMES[ kind ].remove );
			if ( removeField ) {
				removeField.value = '0';
			}
		} );
		if ( data.avatarFocal ) {
			writePos( 'avatar', data.avatarFocal );
		}
		// The saved avatar is already cut to the chosen part: show it as it is.
		const avatarImg = previewImg( 'avatar' );
		if ( avatarImg ) {
			applyPosition( avatarImg, CENTRE() );
		}
		if ( data.coverPosition ) {
			writePos( 'cover', data.coverPosition );
			const cover = previewImg( 'cover' );
			if ( cover ) {
				applyPosition( cover, data.coverPosition );
			}
		}
		note( '' );
	} );
} )( window );
