<?php
/**
 * Events extension helpers for themes and third-party code.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the Events extension (`cp_events`) is installed and enabled.
 *
 * `cp_event` CPT, RSVP tables, REST routes, and event blocks load only when this is true.
 *
 * @return bool
 */
function clanbite_events_extension_active(): bool {
	if ( ! class_exists( \Kernowdev\Clanbite\Extensions\Loader::class ) ) {
		return false;
	}

	$active = \Kernowdev\Clanbite\Extensions\Loader::instance()->is_extension_installed( 'cp_events' );

	/**
	 * Filter whether the Events extension is considered active.
	 *
	 * @param bool $active True when `cp_events` is in the installed-extensions option.
	 */
	return (bool) apply_filters( 'clanbite_events_extension_active', $active );
}

/**
 * Whether the Events extension should register the player profile Events subpage and template.
 *
 * Controlled from Clanbite → Events → Profile tabs (`clanbite_events_settings.subpage_player`).
 * Falls back to the old Players toggle (`clanbite_players_settings.events_profile_subpage`) until that tab is saved.
 *
 * @return bool
 */
function clanbite_events_subpage_player_enabled(): bool {
	if ( ! clanbite_events_extension_active() ) {
		return false;
	}

	// Clanbite → Events. Sites that have not saved that tab yet keep the old toggle on the Players tab.
	$stored = get_option( 'clanbite_events_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'subpage_player', $stored ) ) {
		return ! empty( $stored['subpage_player'] );
	}

	$legacy = get_option( 'clanbite_players_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'events_profile_subpage', $legacy ) ) {
		return ! empty( $legacy['events_profile_subpage'] );
	}

	return true;
}

/**
 * Whether the Events extension should register the team profile Events subpage and routes.
 *
 * Controlled from Clanbite → Events → Profile tabs (`clanbite_events_settings.subpage_team`).
 * Falls back to the old Teams toggle (`clanbite_teams_settings.events_profile_subpage`) until that tab is saved.
 *
 * @return bool
 */
function clanbite_events_subpage_team_enabled(): bool {
	if ( ! clanbite_events_extension_active() ) {
		return false;
	}

	// Clanbite → Events. Sites that have not saved that tab yet keep the old toggle on the Teams tab.
	$stored = get_option( 'clanbite_events_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'subpage_team', $stored ) ) {
		return ! empty( $stored['subpage_team'] );
	}

	$legacy = get_option( 'clanbite_teams_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'events_profile_subpage', $legacy ) ) {
		return ! empty( $legacy['events_profile_subpage'] );
	}

	return true;
}

/**
 * Whether the Events extension should register the group profile Events subpage and block template.
 *
 * Controlled from Clanbite → Events → Profile tabs (`clanbite_events_settings.subpage_group`).
 * Falls back to the old Groups toggle (`clanbite_groups_settings.events_profile_subpage`) until that tab is saved.
 *
 * @return bool
 */
function clanbite_events_subpage_group_enabled(): bool {
	if ( ! clanbite_events_extension_active() ) {
		return false;
	}

	// Clanbite → Events. Sites that have not saved that tab yet keep the old toggle on the Groups tab.
	$stored = get_option( 'clanbite_events_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'subpage_group', $stored ) ) {
		return ! empty( $stored['subpage_group'] );
	}

	$legacy = get_option( 'clanbite_groups_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'events_profile_subpage', $legacy ) ) {
		return ! empty( $legacy['events_profile_subpage'] );
	}

	return true;
}

/**
 * One-time move of the Events switches from their old places into Clanbite → Events (`clanbite_events_settings`).
 *
 * The switch for scheduled events used to live under General and the three "Events tab" toggles on the Players, Teams and Groups
 * tabs. Their values are copied once, before any of those tabs is saved again (saving rebuilds an option from the fields it still
 * shows). Nothing is deleted: the old keys stay where they are.
 *
 * @return void
 */
function clanbite_events_maybe_migrate_settings(): void {
	if ( get_option( 'clanbite_events_settings_migrated' ) ) {
		return;
	}

	$events = get_option( 'clanbite_events_settings', array() );
	$events = is_array( $events ) ? $events : array();

	$general = get_option( 'clanbite_general_settings', array() );
	if ( ! array_key_exists( 'events_enabled', $events ) && is_array( $general ) && array_key_exists( 'events_enabled', $general ) ) {
		$events['events_enabled'] = ! empty( $general['events_enabled'] );
	}

	$sources = array(
		'subpage_player' => 'clanbite_players_settings',
		'subpage_team'   => 'clanbite_teams_settings',
		'subpage_group'  => 'clanbite_groups_settings',
	);
	foreach ( $sources as $key => $option ) {
		$old = get_option( $option, array() );
		if ( ! array_key_exists( $key, $events ) && is_array( $old ) && array_key_exists( 'events_profile_subpage', $old ) ) {
			$events[ $key ] = ! empty( $old['events_profile_subpage'] );
		}
	}

	update_option( 'clanbite_events_settings', $events );
	update_option( 'clanbite_events_settings_migrated', 1, true );
}
add_action( 'init', 'clanbite_events_maybe_migrate_settings', 1 );
