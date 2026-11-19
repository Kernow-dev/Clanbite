<?php
/**
 * Components registry: shared UI building blocks that extensions and third parties register and use.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where the registry keeps its state (one array for the request).
 *
 * @since 1.6.3
 *
 * @return array{components: array<string, array<string, mixed>>, booted: bool}
 */
function &clanbite_components_state(): array {
	static $state = array(
		'components' => array(),
		'booted'     => false,
	);

	return $state;
}

/**
 * Register a component: a piece of UI or behaviour (an avatar, reaction chips, an @mention picker) other extensions can use.
 *
 * Call it on the action `clanbite_register_components`. A component has a `render` callback (returns HTML), an `enqueue` callback
 * (loads its scripts and styles) or both. Consumers never reach into the provider's classes: they ask the registry, so a missing
 * provider means a quiet fallback (`clanbite_render_component()` returns an empty string) and a replacement provider needs no change
 * in the consumers.
 *
 * ```php
 * add_action( 'clanbite_register_components', function () {
 *     clanbite_register_component( 'reaction-summary', array(
 *         'label'    => __( 'Reaction chips', 'my-plugin' ),
 *         'provider' => 'my_extension',
 *         'version'  => '1.0.0',
 *         'requires' => array( 'reactions' ),
 *         'render'   => fn( array $args ): string => my_render_chips( $args ),
 *     ) );
 * } );
 * ```
 *
 * @since 1.6.3
 *
 * @param string               $slug Lowercase slug (`a-z0-9_-`), e.g. `avatar`, `reaction-picker`.
 * @param array<string, mixed> $args {
 *     Component definition.
 *
 *     @type string   $label       Readable name.
 *     @type string   $description What it is for.
 *     @type string   $provider    Slug of the extension that provides it (`clanbite_social_kit`, `clanbite`…).
 *     @type string   $version     Component version `x.y.z` (so consumers can tell what they are using).
 *     @type callable $render      `( array $args ): string` HTML for the arguments the caller passes. Optional.
 *     @type callable $enqueue     `(): void` loads the component's scripts and styles. Optional.
 *     @type string[] $requires    Slugs of components this one needs; it is unavailable while one is missing.
 *     @type string[] $surfaces    Where it is known to be shown (`feed`, `forums`…), documented for developers.
 *     @type array    $args        Documentation of the arguments `render` accepts, `name => description`.
 *     @type bool     $replace     True to replace a component another provider registered under the same slug.
 * }
 * @return bool True when registered; false for an invalid definition or a slug taken without `replace`.
 */
function clanbite_register_component( string $slug, array $args ): bool {
	$slug = sanitize_key( $slug );
	if ( '' === $slug || ( ! isset( $args['render'] ) && ! isset( $args['enqueue'] ) ) ) {
		return false;
	}
	foreach ( array( 'render', 'enqueue' ) as $callback ) {
		if ( isset( $args[ $callback ] ) && ! is_callable( $args[ $callback ] ) ) {
			unset( $args[ $callback ] );
		}
	}
	if ( ! isset( $args['render'] ) && ! isset( $args['enqueue'] ) ) {
		return false;
	}

	$state = &clanbite_components_state();
	if ( isset( $state['components'][ $slug ] ) && empty( $args['replace'] ) ) {
		return false;
	}

	$state['components'][ $slug ] = array(
		'slug'        => $slug,
		'label'       => isset( $args['label'] ) ? (string) $args['label'] : $slug,
		'description' => isset( $args['description'] ) ? (string) $args['description'] : '',
		'provider'    => isset( $args['provider'] ) ? sanitize_key( (string) $args['provider'] ) : '',
		'version'     => isset( $args['version'] ) ? (string) $args['version'] : '',
		'render'      => $args['render'] ?? null,
		'enqueue'     => $args['enqueue'] ?? null,
		'requires'    => isset( $args['requires'] ) ? array_values( array_map( 'sanitize_key', (array) $args['requires'] ) ) : array(),
		'surfaces'    => isset( $args['surfaces'] ) ? array_values( array_map( 'sanitize_key', (array) $args['surfaces'] ) ) : array(),
		'args'        => isset( $args['args'] ) && is_array( $args['args'] ) ? $args['args'] : array(),
	);

	return true;
}

/**
 * All registered components (collects the registrations once, from `init` on, when every extension has added its hook).
 *
 * @since 1.6.3
 *
 * @return array<string, array<string, mixed>> Definitions keyed by slug (`slug`, `label`, `description`, `provider`, `version`,
 *                                             `requires`, `surfaces`, `args`, and the `render` / `enqueue` callbacks).
 */
function clanbite_get_components(): array {
	$state = &clanbite_components_state();
	if ( ! $state['booted'] && did_action( 'init' ) ) {
		$state['booted'] = true;

		/**
		 * Fires once when components are collected: call `clanbite_register_component()` here.
		 *
		 * Core registers its own (`avatar`, `toasts`) first; extensions register theirs (Social Kit: `reactions`,
		 * `reaction-summary`, `reaction-picker`, `mentions`). A third party can replace one with the `replace` argument.
		 *
		 * @since 1.6.3
		 */
		do_action( 'clanbite_register_components' );
	}

	/**
	 * Filters the registered components, for example to remove or swap one site-wide.
	 *
	 * @since 1.6.3
	 *
	 * @param array<string, array<string, mixed>> $components Definitions keyed by slug.
	 */
	return (array) apply_filters( 'clanbite_components', $state['components'] );
}

/**
 * One component definition.
 *
 * @since 1.6.3
 *
 * @param string $slug Component slug.
 * @return array<string, mixed>|null The definition, or null when it is not registered.
 */
function clanbite_get_component( string $slug ): ?array {
	$components = clanbite_get_components();

	return $components[ sanitize_key( $slug ) ] ?? null;
}

/**
 * Whether a component can be used right now: registered, and every component it requires is available too.
 *
 * Use this instead of `function_exists()` checks on another plugin's functions.
 *
 * @since 1.6.3
 *
 * @param string $slug Component slug.
 * @return bool
 */
function clanbite_has_component( string $slug ): bool {
	return clanbite_component_missing_requirements( $slug ) === array() && null !== clanbite_get_component( $slug );
}

/**
 * Required components that are not available for a component (an empty array when it is fine).
 *
 * @since 1.6.3
 *
 * @param string $slug  Component slug.
 * @param array  $stack Slugs already being checked (guards against a circular `requires`).
 * @return string[]
 */
function clanbite_component_missing_requirements( string $slug, array $stack = array() ): array {
	$def = clanbite_get_component( $slug );
	if ( null === $def || in_array( $def['slug'], $stack, true ) ) {
		return array();
	}
	$missing = array();
	foreach ( (array) $def['requires'] as $required ) {
		$needed = clanbite_get_component( $required );
		if ( null === $needed || array() !== clanbite_component_missing_requirements( $required, array_merge( $stack, array( $def['slug'] ) ) ) ) {
			$missing[] = $required;
		}
	}

	/**
	 * Filters whether a component is considered available (for example to switch one off for a site setting).
	 *
	 * @since 1.6.3
	 *
	 * @param bool                 $available Whether it is available so far.
	 * @param string               $slug      Component slug.
	 * @param array<string, mixed> $def       Its definition.
	 */
	if ( ! (bool) apply_filters( 'clanbite_component_available', array() === $missing, $slug, $def ) && array() === $missing ) {
		$missing[] = $slug;
	}

	return $missing;
}

/**
 * Render a component. Returns an empty string when it (or something it requires) is not available.
 *
 * @since 1.6.3
 *
 * @param string               $slug Component slug.
 * @param array<string, mixed> $args Arguments for the component. `surface` (where it is shown: `feed`, `forums`…) is understood by
 *                                   every component and becomes the class `is-{surface}`; `viewer_id` defaults to the current user.
 * @return string HTML as the component printed it (the provider escapes it).
 */
function clanbite_render_component( string $slug, array $args = array() ): string {
	$def = clanbite_get_component( $slug );
	if ( null === $def || ! is_callable( $def['render'] ?? null ) || ! clanbite_has_component( $slug ) ) {
		return '';
	}

	/**
	 * Filters the arguments a component renders with, for every component.
	 *
	 * Runs before the provider's callback: add context (`surface`, `object_type`…), or change an argument for one place.
	 *
	 * @since 1.6.3
	 *
	 * @param array<string, mixed> $args Arguments passed by the caller.
	 * @param string               $slug Component slug.
	 * @param array<string, mixed> $def  Component definition.
	 */
	$args = (array) apply_filters( 'clanbite_component_args', $args, $def['slug'], $def );
	/** This filter is documented in inc/components.php (clanbite_component_args). */
	$args = (array) apply_filters( "clanbite_component_args_{$def['slug']}", $args, $def['slug'], $def );

	$html = (string) call_user_func( $def['render'], $args );

	/**
	 * Filters a component's HTML, for every component.
	 *
	 * @since 1.6.3
	 *
	 * @param string               $html Markup.
	 * @param string               $slug Component slug.
	 * @param array<string, mixed> $args Arguments it was rendered with.
	 * @param array<string, mixed> $def  Component definition.
	 */
	return (string) apply_filters( 'clanbite_component_html', $html, $def['slug'], $args, $def );
}

/**
 * Load a component's scripts and styles (and those of the components it requires).
 *
 * @since 1.6.3
 *
 * @param string $slug Component slug.
 * @return bool True when the component is available and was enqueued.
 */
function clanbite_enqueue_component( string $slug ): bool {
	$def = clanbite_get_component( $slug );
	if ( null === $def || ! clanbite_has_component( $slug ) ) {
		return false;
	}
	foreach ( (array) $def['requires'] as $required ) {
		clanbite_enqueue_component( $required );
	}
	if ( is_callable( $def['enqueue'] ?? null ) ) {
		call_user_func( $def['enqueue'] );
	}

	return true;
}

/**
 * Register Clanbite core's own components.
 *
 * @since 1.6.3
 *
 * @return void
 */
function clanbite_register_core_components(): void {
	clanbite_register_component(
		'avatar',
		array(
			'label'       => __( 'Avatar', 'clanbite' ),
			'description' => __( 'A player, team or group avatar with its shape, XP ring and rank badge.', 'clanbite' ),
			'provider'    => 'clanbite',
			'version'     => '1.0.0',
			'args'        => array(
				'type'        => 'player, team or group.',
				'id'          => 'Entity ID.',
				'size'        => 'large, medium, small or a size slug.',
				'context'     => 'Where it is shown; added as the class is-{context}.',
				'mode'        => '"static" (default) or "interactive" for Interactivity API templates.',
				'object_type' => 'What it sits in (post, reply, notification…).',
				'extension'   => 'Slug of the extension rendering it.',
			),
			'render'      => static function ( array $args ): string {
				$mode = isset( $args['mode'] ) ? (string) $args['mode'] : 'static';
				unset( $args['mode'] );

				return 'interactive' === $mode ? clanbite_render_avatar_for_interactivity( $args ) : clanbite_render_avatar( $args );
			},
			'enqueue'     => static function (): void {
				wp_enqueue_style( 'clanbite-avatars' );
			},
		)
	);

	clanbite_register_component(
		'popups',
		array(
			'label'       => __( 'Popup lifecycle', 'clanbite' ),
			'description' => __( 'clanbite.popups.announce() / allowOpen(): the hooks clanbite.popup.toggled and clanbite.popup.shouldOpen for every popup.', 'clanbite' ),
			'provider'    => 'clanbite',
			'version'     => '1.0.0',
			'enqueue'     => static function (): void {
				wp_enqueue_script( 'clanbite-popups' );
			},
		)
	);

	clanbite_register_component(
		'clipboard',
		array(
			'label'       => __( 'Copy to clipboard', 'clanbite' ),
			'description' => __( 'clanbite.clipboard.copy( text ): copies text, with a fallback for browsers without the clipboard API.', 'clanbite' ),
			'provider'    => 'clanbite',
			'version'     => '1.0.0',
			'enqueue'     => static function (): void {
				wp_enqueue_script( 'clanbite-clipboard' );
			},
		)
	);

	clanbite_register_component(
		'time',
		array(
			'label'       => __( 'Relative time', 'clanbite' ),
			'description' => __( 'Relative wording such as "5 minutes ago", filterable in one place (clanbite_human_time_ago).', 'clanbite' ),
			'provider'    => 'clanbite',
			'version'     => '1.0.0',
			'args'        => array(
				'timestamp' => 'Unix timestamp of the moment.',
				'now'       => 'Unix timestamp to measure from (default: now).',
			),
			'render'      => static fn( array $args ): string => clanbite_human_time_ago( (int) ( $args['timestamp'] ?? 0 ), isset( $args['now'] ) ? (int) $args['now'] : null ),
		)
	);

	clanbite_register_component(
		'toasts',
		array(
			'label'       => __( 'Popups (toasts)', 'clanbite' ),
			'description' => __( 'The shared popup system: clanbite.toasts.show() in the browser.', 'clanbite' ),
			'provider'    => 'clanbite',
			'version'     => '1.0.0',
			'enqueue'     => static function (): void {
				clanbite_enqueue_toasts();
			},
		)
	);
}
add_action( 'clanbite_register_components', 'clanbite_register_core_components', 5 );

/**
 * List the registered components in Tools → Site Health → Info, so support can see which extension provides what.
 *
 * @since 1.6.3
 *
 * @param array<string, mixed> $info Site Health debug information.
 * @return array<string, mixed>
 */
function clanbite_components_debug_information( array $info ): array {
	$fields = array();
	foreach ( clanbite_get_components() as $slug => $def ) {
		$missing         = clanbite_component_missing_requirements( $slug );
		$fields[ $slug ] = array(
			'label' => (string) $def['label'],
			'value' => trim(
				sprintf(
					/* translators: 1: provider slug, 2: version */
					__( 'Provided by %1$s %2$s', 'clanbite' ),
					'' !== $def['provider'] ? $def['provider'] : __( 'unknown', 'clanbite' ),
					$def['version']
				)
			) . ( array() === $missing ? '' : ' — ' . sprintf(
				/* translators: %s: component slugs */
				__( 'unavailable, needs: %s', 'clanbite' ),
				implode( ', ', $missing )
			) ),
		);
	}
	$info['clanbite-components'] = array(
		'label'  => __( 'Clanbite components', 'clanbite' ),
		'fields' => $fields,
	);

	return $info;
}
add_filter( 'debug_information', 'clanbite_components_debug_information' );
