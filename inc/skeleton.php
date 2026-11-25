<?php
/**
 * Skeleton loaders: one set of loading placeholders for every Clanbite surface.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where the skeleton registry keeps its state (one array for the request).
 *
 * @since 1.7.0
 *
 * @return array{skeletons: array<string, array<string, mixed>>, booted: bool}
 */
function &clanbite_skeletons_state(): array {
	static $state = array(
		'skeletons' => array(),
		'booted'    => false,
	);

	return $state;
}

/**
 * Register a skeleton: the loading placeholder for one kind of content (an avatar, a card, a feed post).
 *
 * Call it on the action `clanbite_register_skeletons`. A skeleton is a `render` callback that returns HTML built from the shared
 * primitives ({@see clanbite_skeleton_element()}), so every skeleton uses the same colours, shimmer and reduced-motion behaviour and a
 * theme restyles all of them with the CSS tokens `--clanbite-skeleton-base`, `-highlight`, `-radius` and `-duration`.
 *
 * ```php
 * add_action( 'clanbite_register_skeletons', function () {
 *     clanbite_register_skeleton( 'match-card', array(
 *         'label'  => __( 'Match card', 'my-plugin' ),
 *         'render' => fn( array $args ): string => clanbite_skeleton_element( 'block', array( 'height' => '6rem' ) ),
 *     ) );
 * } );
 * ```
 *
 * @since 1.7.0
 *
 * @param string               $slug Lowercase slug (`a-z0-9_-`), for example `avatar`, `card`.
 * @param array<string, mixed> $args {
 *     Skeleton definition.
 *
 *     @type string   $label       Readable name.
 *     @type string   $description What it stands in for.
 *     @type callable $render      `( array $args ): string` HTML for the arguments the caller passes. Required.
 *     @type array    $args        Documentation of the arguments `render` accepts, `name => description`.
 *     @type bool     $replace     True to replace a skeleton registered under the same slug (for example a core preset).
 * }
 * @return bool True when registered; false for an invalid definition or a slug taken without `replace`.
 */
function clanbite_register_skeleton( string $slug, array $args ): bool {
	$slug = sanitize_key( $slug );
	if ( '' === $slug || ! isset( $args['render'] ) || ! is_callable( $args['render'] ) ) {
		return false;
	}

	$state = &clanbite_skeletons_state();
	if ( isset( $state['skeletons'][ $slug ] ) && empty( $args['replace'] ) ) {
		return false;
	}

	$state['skeletons'][ $slug ] = array(
		'slug'        => $slug,
		'label'       => isset( $args['label'] ) ? (string) $args['label'] : $slug,
		'description' => isset( $args['description'] ) ? (string) $args['description'] : '',
		'render'      => $args['render'],
		'args'        => isset( $args['args'] ) && is_array( $args['args'] ) ? $args['args'] : array(),
	);

	return true;
}

/**
 * All registered skeletons (core's presets first, then everything added on `clanbite_register_skeletons`).
 *
 * @since 1.7.0
 *
 * @return array<string, array<string, mixed>> Definitions keyed by slug (`slug`, `label`, `description`, `args`, `render`).
 */
function clanbite_get_skeletons(): array {
	$state = &clanbite_skeletons_state();
	if ( ! $state['booted'] && did_action( 'init' ) ) {
		$state['booted'] = true;
		clanbite_register_core_skeletons();

		/**
		 * Fires once when skeletons are collected: call `clanbite_register_skeleton()` here.
		 *
		 * Core registers its presets (`avatar`, `line`, `text`, `block`, `circle`, `card`, `list-item`) first; a third party can
		 * replace one with the `replace` argument.
		 *
		 * @since 1.7.0
		 */
		do_action( 'clanbite_register_skeletons' );
	}

	/**
	 * Filters the registered skeletons, for example to remove or swap one site-wide.
	 *
	 * @since 1.7.0
	 *
	 * @param array<string, array<string, mixed>> $skeletons Definitions keyed by slug.
	 */
	return (array) apply_filters( 'clanbite_skeletons', $state['skeletons'] );
}

/**
 * Whether a skeleton is registered.
 *
 * @since 1.7.0
 *
 * @param string $slug Skeleton slug.
 * @return bool
 */
function clanbite_has_skeleton( string $slug ): bool {
	return isset( clanbite_get_skeletons()[ sanitize_key( $slug ) ] );
}

/**
 * Load the skeleton stylesheet (it depends on the tokens and avatar styles, so sizes and shapes always match real avatars).
 *
 * @since 1.7.0
 *
 * @return void
 */
function clanbite_enqueue_skeleton(): void {
	wp_enqueue_style( 'clanbite-skeleton' );
}

/**
 * A CSS length that is safe to print into a `style` attribute, or the default.
 *
 * @since 1.7.0
 *
 * @param mixed  $value   Candidate length such as `3rem`, `60%` or `40` (pixels).
 * @param string $fallback Returned when the value is not a plain number with an optional unit.
 * @return string
 */
function clanbite_skeleton_css_length( $value, string $fallback = '' ): string {
	if ( is_int( $value ) || is_float( $value ) ) {
		$value = (string) $value;
	}
	if ( ! is_string( $value ) ) {
		return $fallback;
	}
	$value = trim( $value );
	if ( 1 !== preg_match( '/^\d+(?:\.\d+)?(?:px|%|rem|em|vw|vh|ch)?$/', $value ) ) {
		return $fallback;
	}

	return ctype_digit( $value ) || is_numeric( $value ) ? $value . 'px' : $value;
}

/**
 * One skeleton primitive: a line, a block or a circle that shimmers.
 *
 * Presets are built from these, and so should skeletons registered by extensions, so they all look and move the same.
 *
 * @since 1.7.0
 *
 * @param string               $shape `line`, `block`, `circle` or `avatar` (sized and shaped by the avatar classes in `class`).
 * @param array<string, mixed> $args {
 *     Options.
 *
 *     @type string|int $width   CSS length (`60%`, `8rem`, `40` = px). Default: full width for line and block.
 *     @type string|int $height  CSS length. Default: one line of text for `line`, `4rem` for `block`.
 *     @type string|int $radius  CSS length overriding the corner radius.
 *     @type string|int $size    Width and height of a `circle`.
 *     @type string     $class   Extra CSS classes.
 *     @type string     $type    Skeleton preset this belongs to (used for the filter and a data attribute).
 *     @type string     $context Where it is shown; added as the class `is-{context}`.
 * }
 * @return string HTML.
 */
function clanbite_skeleton_element( string $shape, array $args = array() ): string {
	$shape = in_array( $shape, array( 'line', 'block', 'circle', 'avatar' ), true ) ? $shape : 'block';
	$type  = isset( $args['type'] ) ? sanitize_key( (string) $args['type'] ) : $shape;

	$classes = array( 'clanbite-skeleton', 'clanbite-skeleton--' . $shape );
	if ( ! empty( $args['context'] ) ) {
		$classes[] = 'is-' . sanitize_html_class( (string) $args['context'] );
	}
	if ( ! empty( $args['class'] ) ) {
		$extras = preg_split( '/\s+/', (string) $args['class'] );
		foreach ( false === $extras ? array() : $extras as $extra ) {
			$classes[] = sanitize_html_class( $extra );
		}
	}

	$style = array();
	if ( 'circle' === $shape && isset( $args['size'] ) ) {
		$size = clanbite_skeleton_css_length( $args['size'] );
		if ( '' !== $size ) {
			$style[] = '--clanbite-skeleton-width:' . $size;
			$style[] = '--clanbite-skeleton-height:' . $size;
		}
	}
	foreach ( array(
		'width'  => '--clanbite-skeleton-width',
		'height' => '--clanbite-skeleton-height',
		'radius' => '--clanbite-skeleton-radius',
	) as $key => $property ) {
		$length = isset( $args[ $key ] ) ? clanbite_skeleton_css_length( $args[ $key ] ) : '';
		if ( '' !== $length ) {
			$style[] = $property . ':' . $length;
		}
	}

	/**
	 * Filters the CSS classes of a skeleton primitive.
	 *
	 * @since 1.7.0
	 *
	 * @param string[]             $classes CSS classes.
	 * @param string               $type    Skeleton preset the primitive belongs to.
	 * @param array<string, mixed> $args    Arguments passed to {@see clanbite_skeleton_element()}.
	 */
	$classes = (array) apply_filters( 'clanbite_skeleton_classes', $classes, $type, $args );

	return sprintf(
		'<span class="%1$s" data-clanbite-skeleton="%2$s"%3$s aria-hidden="true"></span>',
		esc_attr( implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) ) ),
		esc_attr( $type ),
		array() === $style ? '' : ' style="' . esc_attr( implode( ';', $style ) ) . '"'
	);
}

/**
 * Render a skeleton.
 *
 * ```php
 * clanbite_enqueue_skeleton();
 * echo clanbite_render_skeleton( 'avatar', array( 'size' => 'small', 'context' => 'forums' ) );
 * echo clanbite_render_skeleton( 'text', array( 'lines' => 3 ) );
 * ```
 *
 * The skeleton is decorative (`aria-hidden`). Mark the region that is loading with `aria-busy="true"` and remove the skeleton when the
 * content arrives.
 *
 * @since 1.7.0
 *
 * @param string               $type Skeleton slug: a core preset or one registered with {@see clanbite_register_skeleton()}.
 * @param array<string, mixed> $args Arguments for that skeleton (the avatar takes `type`, `size`, `shape`, `context`; see the
 *                                   `args` of each definition).
 * @return string HTML, empty when no such skeleton exists.
 */
function clanbite_render_skeleton( string $type, array $args = array() ): string {
	$type = sanitize_key( $type );

	/**
	 * Filters the arguments of a skeleton before it is built.
	 *
	 * @since 1.7.0
	 *
	 * @param array<string, mixed> $args Arguments passed to {@see clanbite_render_skeleton()}.
	 * @param string               $type Skeleton slug.
	 */
	$args = (array) apply_filters( 'clanbite_skeleton_args', $args, $type );

	$skeletons = clanbite_get_skeletons();
	if ( ! isset( $skeletons[ $type ] ) ) {
		return '';
	}
	$args['type'] = $type;
	$html         = (string) call_user_func( $skeletons[ $type ]['render'], $args );

	/**
	 * Filters the finished HTML of any skeleton: the one place to restyle or replace them all.
	 *
	 * @since 1.7.0
	 *
	 * @param string               $html HTML.
	 * @param string               $type Skeleton slug.
	 * @param array<string, mixed> $args Arguments the skeleton was built with.
	 */
	$html = (string) apply_filters( 'clanbite_skeleton_html', $html, $type, $args );

	/**
	 * Filters the finished HTML of one kind of skeleton (`clanbite_skeleton_html_avatar`, `clanbite_skeleton_html_card`…).
	 *
	 * @since 1.7.0
	 *
	 * @param string               $html HTML.
	 * @param array<string, mixed> $args Arguments the skeleton was built with.
	 */
	return (string) apply_filters( "clanbite_skeleton_html_{$type}", $html, $args );
}

/**
 * Register Clanbite core's skeleton presets.
 *
 * @since 1.7.0
 *
 * @return void
 */
function clanbite_register_core_skeletons(): void {
	clanbite_register_skeleton(
		'avatar',
		array(
			'label'       => __( 'Avatar', 'clanbite' ),
			'description' => __( 'The loading placeholder of an avatar: the same size and shape as the real one, so nothing moves when it arrives.', 'clanbite' ),
			'args'        => array(
				'avatar_type' => 'player (default), team or group: the shape follows that type\'s avatar setting.',
				'size'        => 'large, medium, small or a size slug, as for clanbite_render_avatar().',
				'shape'       => 'circle, square or hexagon; empty uses the setting.',
				'context'     => 'Where it is shown; added as the class is-{context}.',
			),
			'render'      => static function ( array $args ): string {
				$entity = isset( $args['avatar_type'] ) ? sanitize_key( (string) $args['avatar_type'] ) : 'player';
				$size   = isset( $args['size'] ) && '' !== (string) $args['size'] ? sanitize_html_class( (string) $args['size'] ) : 'medium';
				$shape  = function_exists( 'clanbite_avatar_resolve_shape' )
					? clanbite_avatar_resolve_shape( $entity, isset( $args['shape'] ) ? sanitize_key( (string) $args['shape'] ) : '' )
					: 'circle';

				// The avatar's own size and shape classes carry the dimensions and the clip, so a skeleton always matches a real avatar.
				return clanbite_skeleton_element(
					'avatar',
					array(
						'type'    => 'avatar',
						'context' => $args['context'] ?? '',
						'class'   => 'clanbite-avatar--' . $size . ' clanbite-avatar--shape-' . sanitize_html_class( $shape ),
					)
				);
			},
		)
	);

	clanbite_register_skeleton(
		'line',
		array(
			'label'       => __( 'Line of text', 'clanbite' ),
			'description' => __( 'One line of text.', 'clanbite' ),
			'args'        => array(
				'width'   => 'CSS length, default 100%.',
				'context' => 'Where it is shown.',
			),
			'render'      => static fn( array $args ): string => clanbite_skeleton_element( 'line', $args ),
		)
	);

	clanbite_register_skeleton(
		'text',
		array(
			'label'       => __( 'Paragraph', 'clanbite' ),
			'description' => __( 'Several lines of text; the last one is shorter.', 'clanbite' ),
			'args'        => array(
				'lines'   => 'Number of lines, default 3.',
				'context' => 'Where it is shown.',
			),
			'render'      => static function ( array $args ): string {
				$lines = max( 1, min( 12, isset( $args['lines'] ) ? (int) $args['lines'] : 3 ) );
				$html  = '';
				for ( $i = 1; $i <= $lines; $i++ ) {
					$html .= clanbite_skeleton_element(
						'line',
						array(
							'type'    => 'text',
							'context' => $args['context'] ?? '',
							'width'   => ( $lines > 1 && $i === $lines ) ? '60%' : '100%',
						)
					);
				}

				return '<span class="clanbite-skeleton-group clanbite-skeleton-group--text" data-clanbite-skeleton="text" aria-hidden="true">' . $html . '</span>';
			},
		)
	);

	clanbite_register_skeleton(
		'block',
		array(
			'label'       => __( 'Block', 'clanbite' ),
			'description' => __( 'A rectangle for an image, a cover or a media tile.', 'clanbite' ),
			'args'        => array(
				'width'   => 'CSS length, default 100%.',
				'height'  => 'CSS length, default 4rem.',
				'radius'  => 'CSS length for the corners.',
				'context' => 'Where it is shown.',
			),
			'render'      => static fn( array $args ): string => clanbite_skeleton_element( 'block', $args ),
		)
	);

	clanbite_register_skeleton(
		'circle',
		array(
			'label'       => __( 'Circle', 'clanbite' ),
			'description' => __( 'A round placeholder, for example an icon.', 'clanbite' ),
			'args'        => array(
				'size'    => 'CSS length, default 2.5rem.',
				'context' => 'Where it is shown.',
			),
			'render'      => static fn( array $args ): string => clanbite_skeleton_element( 'circle', $args ),
		)
	);

	clanbite_register_skeleton(
		'list-item',
		array(
			'label'       => __( 'List item', 'clanbite' ),
			'description' => __( 'An avatar with two lines of text beside it: a member row, a notification, a topic.', 'clanbite' ),
			'args'        => array(
				'size'    => 'Avatar size, default small.',
				'context' => 'Where it is shown.',
			),
			'render'      => static function ( array $args ): string {
				$context = $args['context'] ?? '';
				$avatar  = clanbite_render_skeleton(
					'avatar',
					array(
						'size'    => $args['size'] ?? 'small',
						'context' => $context,
					)
				);
				$text    = clanbite_skeleton_element(
					'line',
					array(
						'type'    => 'list-item',
						'context' => $context,
						'width'   => '70%',
					)
				) . clanbite_skeleton_element(
					'line',
					array(
						'type'    => 'list-item',
						'context' => $context,
						'width'   => '45%',
					)
				);

				return '<span class="clanbite-skeleton-group clanbite-skeleton-group--list-item" data-clanbite-skeleton="list-item" aria-hidden="true">'
					. $avatar . '<span class="clanbite-skeleton-group__body">' . $text . '</span></span>';
			},
		)
	);

	clanbite_register_skeleton(
		'card',
		array(
			'label'       => __( 'Card', 'clanbite' ),
			'description' => __( 'A card with an author row, a few lines of text and an optional media block: a feed post, a topic.', 'clanbite' ),
			'args'        => array(
				'lines'   => 'Lines of text, default 3.',
				'media'   => 'True to add a media block, default false.',
				'context' => 'Where it is shown.',
			),
			'render'      => static function ( array $args ): string {
				$context = $args['context'] ?? '';
				$html    = clanbite_render_skeleton( 'list-item', array( 'context' => $context ) );
				$html   .= clanbite_render_skeleton(
					'text',
					array(
						'lines'   => $args['lines'] ?? 3,
						'context' => $context,
					)
				);
				if ( ! empty( $args['media'] ) ) {
					$html .= clanbite_render_skeleton(
						'block',
						array(
							'height'  => '12rem',
							'context' => $context,
						)
					);
				}

				return '<span class="clanbite-skeleton-group clanbite-skeleton-group--card" data-clanbite-skeleton="card" aria-hidden="true">' . $html . '</span>';
			},
		)
	);
}
