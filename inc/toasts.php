<?php
/**
 * Shared popups ("toasts") for Clanbite and its extensions.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Screen positions a toast can appear in.
 *
 * @since 1.5.0
 *
 * @return array<string, string> Position slug => translated label (`top-right` is the default).
 */
function clanbite_toast_positions(): array {
	$positions = array(
		'top-left'      => __( 'Top left', 'clanbite' ),
		'top-center'    => __( 'Top center', 'clanbite' ),
		'top-right'     => __( 'Top right', 'clanbite' ),
		'bottom-left'   => __( 'Bottom left', 'clanbite' ),
		'bottom-center' => __( 'Bottom center', 'clanbite' ),
		'bottom-right'  => __( 'Bottom right', 'clanbite' ),
	);

	/**
	 * Filters the screen positions a toast can appear in (the choices in Settings → Notifications → Popups).
	 *
	 * A position you add needs a matching `.clanbite-toast-stack--{slug}` rule in your CSS. `top-right` must stay in the
	 * list: it is the fallback.
	 *
	 * @since 1.5.0
	 *
	 * @param array<string, string> $positions Position slug => translated label.
	 */
	$positions = (array) apply_filters( 'clanbite_toast_positions', $positions );
	if ( ! isset( $positions['top-right'] ) ) {
		$positions['top-right'] = __( 'Top right', 'clanbite' );
	}

	return $positions;
}

/**
 * Shortest and longest length, in seconds, the site owner can type into the "Custom" length field.
 *
 * @since 1.5.0
 *
 * @return array{min: int, max: int} Whole seconds.
 */
function clanbite_toast_custom_duration_range(): array {
	$range = array(
		'min' => 1,
		'max' => 300,
	);

	/**
	 * Filters the range allowed for a custom popup length (Settings → Notifications → Popups).
	 *
	 * @since 1.5.0
	 *
	 * @param array{min: int, max: int} $range Shortest and longest length in whole seconds (default 1 to 300).
	 */
	$range = (array) apply_filters( 'clanbite_toast_custom_duration_range', $range );
	$min   = max( 1, (int) ( $range['min'] ?? 1 ) );
	$max   = max( $min, (int) ( $range['max'] ?? 300 ) );

	return array(
		'min' => $min,
		'max' => $max,
	);
}

/**
 * How long a toast stays on screen by default.
 *
 * Besides the fixed lengths there is `custom`, which uses the number of seconds typed into the custom length field.
 *
 * @since 1.5.0
 *
 * @return array<string, string> Seconds as a string, or `custom`; `0` = until the visitor closes it. Value => translated label.
 */
function clanbite_toast_durations(): array {
	$choices = array();
	foreach ( array( 3, 4, 5, 6, 8, 10, 15 ) as $seconds ) {
		$choices[ (string) $seconds ] = sprintf(
			/* translators: %d: number of seconds a popup stays on screen. */
			_n( '%d second', '%d seconds', $seconds, 'clanbite' ),
			$seconds
		);
	}
	$choices['custom'] = __( 'Custom…', 'clanbite' );
	$choices['0']      = __( 'Until it is closed', 'clanbite' );

	/**
	 * Filters how long a toast can stay on screen (the choices in Settings → Notifications → Popups).
	 *
	 * Keys are whole seconds as strings, or `custom` (the seconds typed into the custom length field); `0` means "until it
	 * is closed". `5` must stay in the list: it is the fallback.
	 *
	 * @since 1.5.0
	 *
	 * @param array<string, string> $choices Seconds => translated label.
	 */
	$choices = (array) apply_filters( 'clanbite_toast_durations', $choices );
	if ( ! isset( $choices['5'] ) ) {
		$choices['5'] = sprintf(
			/* translators: %d: number of seconds a popup stays on screen. */
			_n( '%d second', '%d seconds', 5, 'clanbite' ),
			5
		);
	}

	return $choices;
}

/**
 * Validated toast settings (Clanbite → Settings → Notifications → Popups).
 *
 * The values are stored with the Notifications settings. They keep applying, with their saved values, even if the
 * Notifications extension is later turned off (the tab is then hidden, so they can no longer be edited).
 *
 * Extensions normally do not need this: the `clanbite-toasts` script applies the same values in the browser. Read it to
 * follow the site's choices from PHP (for example to decide whether to show a popup at all).
 *
 * @since 1.5.0
 *
 * @return array{enabled: bool, position: string, duration_ms: int, max: int, pause_on_hover: bool, show_timer: bool} `duration_ms` is 0
 *         when toasts stay until closed.
 */
function clanbite_get_toast_settings(): array {
	$stored = get_option( \Kernowdev\Clanbite\Extensions\Notifications\Admin::OPTION_KEY, array() );
	$stored = is_array( $stored ) ? $stored : array();

	$position = sanitize_key( (string) ( $stored['toast_position'] ?? 'top-right' ) );
	if ( ! array_key_exists( $position, clanbite_toast_positions() ) ) {
		$position = 'top-right';
	}
	$seconds = (string) ( $stored['toast_duration'] ?? '5' );
	if ( 'custom' === $seconds && array_key_exists( 'custom', clanbite_toast_durations() ) ) {
		$range   = clanbite_toast_custom_duration_range();
		$seconds = (string) max( $range['min'], min( $range['max'], (int) ( $stored['toast_duration_custom'] ?? 8 ) ) );
	} elseif ( ! is_numeric( $seconds ) || ! array_key_exists( (string) (int) $seconds, clanbite_toast_durations() ) ) {
		$seconds = '5';
	}

	$settings = array(
		'enabled'        => clanbite_toasts_available(),
		'position'       => $position,
		'duration_ms'    => (int) $seconds * 1000,
		'max'            => 4,
		// Both default to on, including for sites that saved their settings before these options existed.
		'pause_on_hover' => ! array_key_exists( 'toast_pause_on_hover', $stored ) || ! empty( $stored['toast_pause_on_hover'] ),
		'show_timer'     => ! array_key_exists( 'toast_show_timer', $stored ) || ! empty( $stored['toast_show_timer'] ),
	);

	/**
	 * Filters the toast settings before they are used (PHP) and sent to the browser (`clanbiteToastConfig`).
	 *
	 * @since 1.5.0
	 *
	 * @param array{enabled: bool, position: string, duration_ms: int, max: int, pause_on_hover: bool, show_timer: bool} $settings
	 *        `enabled`; `position` (a key of {@see clanbite_toast_positions()}); `duration_ms` (0 = until closed);
	 *        `max` (toasts shown at once per corner); `pause_on_hover` (hovering pauses the countdown); `show_timer`
	 *        (the countdown bar along the bottom of each toast).
	 */
	$settings = (array) apply_filters( 'clanbite_toast_settings', $settings );

	return array(
		'enabled'        => ! empty( $settings['enabled'] ),
		'position'       => array_key_exists( (string) ( $settings['position'] ?? '' ), clanbite_toast_positions() ) ? (string) $settings['position'] : 'top-right',
		'duration_ms'    => max( 0, (int) ( $settings['duration_ms'] ?? 5000 ) ),
		'max'            => max( 1, min( 10, (int) ( $settings['max'] ?? 4 ) ) ),
		'pause_on_hover' => ! isset( $settings['pause_on_hover'] ) || ! empty( $settings['pause_on_hover'] ),
		'show_timer'     => ! isset( $settings['show_timer'] ) || ! empty( $settings['show_timer'] ),
	);
}

/**
 * Whether popups can appear on this site at all.
 *
 * Popups belong to the Notifications extension: with it switched off no popup is shown, and the popup options in
 * Notifications, Ranks, Points and other extensions are hidden. Use this to hide your own popup options the same way.
 *
 * @since 1.5.0
 *
 * @return bool
 */
function clanbite_toasts_available(): bool {
	// Fail closed: when neither the helper nor the loader can say, popups are off rather than assumed on.
	if ( function_exists( 'clanbite_notifications_extension_active' ) ) {
		$available = clanbite_notifications_extension_active();
	} elseif ( class_exists( \Kernowdev\Clanbite\Extensions\Loader::class ) ) {
		$available = \Kernowdev\Clanbite\Extensions\Loader::instance()->is_extension_installed( 'cp_notifications' );
	} else {
		$available = false;
	}
	if ( $available ) {
		$stored    = get_option( \Kernowdev\Clanbite\Extensions\Notifications\Admin::OPTION_KEY, array() );
		$available = ! is_array( $stored ) || ! array_key_exists( 'toasts_enabled', $stored ) || ! empty( $stored['toasts_enabled'] );
	}

	/**
	 * Filters whether popups are available (Notifications is on and "Show popups" is not switched off).
	 *
	 * @since 1.5.0
	 *
	 * @param bool $available False when the Notifications extension or its "Show popups" switch is off.
	 */
	return (bool) apply_filters( 'clanbite_toasts_available', $available );
}

/**
 * Load the toast library on this page.
 *
 * Call it before the page prints scripts (for example from a block's `render.php` or your `wp_enqueue_scripts` callback),
 * or list the handle `clanbite-toasts` as a dependency of your own script. Both do the same thing. The script exposes
 * `window.clanbite.toasts.show()`; see README, "Toasts".
 *
 * @since 1.5.0
 *
 * @return void
 */
function clanbite_enqueue_toasts(): void {
	wp_enqueue_style( 'clanbite-toasts' );
	wp_enqueue_script( 'clanbite-toasts' );
}

/**
 * Hand the site's toast settings to the browser, as an inline script printed before `clanbite-toasts.js`.
 *
 * Runs when scripts are about to print rather than at registration, so a `clanbite_toast_settings` filter added on any
 * hook (even a late `init` callback) is honoured. Safe to call more than once; the config is attached a single time.
 *
 * @since 1.5.0
 *
 * @return void
 */
function clanbite_attach_toast_config(): void {
	static $attached = false;
	if ( $attached || ! wp_script_is( 'clanbite-toasts', 'registered' ) ) {
		return;
	}
	$attached = true;
	wp_add_inline_script( 'clanbite-toasts', clanbite_get_toast_config_script(), 'before' );
}
add_action( 'wp_print_scripts', 'clanbite_attach_toast_config', 1 );
add_action( 'wp_print_footer_scripts', 'clanbite_attach_toast_config', 1 );
add_action( 'admin_print_scripts', 'clanbite_attach_toast_config', 1 );
add_action( 'admin_print_footer_scripts', 'clanbite_attach_toast_config', 1 );

/**
 * Inline script that hands the site's toast settings to the browser, printed before `clanbite-toasts.js`.
 *
 * @since 1.5.0
 *
 * @return string JavaScript.
 */
function clanbite_get_toast_config_script(): string {
	$settings = clanbite_get_toast_settings();

	$browser_config = array(
		'enabled'      => $settings['enabled'],
		'position'     => $settings['position'],
		'positions'    => array_keys( clanbite_toast_positions() ),
		'duration'     => $settings['duration_ms'],
		'max'          => $settings['max'],
		'pauseOnHover' => $settings['pause_on_hover'],
		'showTimer'    => $settings['show_timer'],
		'i18n'         => array(
			'close'  => __( 'Close', 'clanbite' ),
			'region' => __( 'Notifications', 'clanbite' ),
		),
	);

	/**
	 * Filters the configuration object sent to the browser as `window.clanbiteToastConfig`.
	 *
	 * This is the last word before the page prints: use it to change any value (including the `i18n` strings) or to
	 * pass your own keys to a script that reads the config.
	 *
	 * @since 1.5.0
	 *
	 * @param array<string, mixed> $browser_config `enabled`, `position`, `positions` (allowed slugs), `duration` (ms,
	 *                                             0 = until closed), `max`, `pauseOnHover`, `showTimer`, `i18n`.
	 * @param array<string, mixed> $settings       The validated settings from {@see clanbite_get_toast_settings()}.
	 */
	$browser_config = (array) apply_filters( 'clanbite_toast_config', $browser_config, $settings );

	return 'window.clanbiteToastConfig=' . wp_json_encode( $browser_config ) . ';';
}
