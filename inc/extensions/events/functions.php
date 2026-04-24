<?php

defined( 'ABSPATH' ) || exit;

/**
 * Events extension helpers for themes and third-party code.
 *
 * @package clanbite
 */

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
 * Controlled from Clanbite → Players → Player profile: Events tab (`clanbite_players_settings`).
 * Falls back to legacy `clanbite_events_settings.subpage_player` when the Players field was never saved.
 *
 * @return bool
 */
function clanbite_events_subpage_player_enabled(): bool {
	if ( ! clanbite_events_extension_active() ) {
		return false;
	}

	$stored = get_option( 'clanbite_players_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'events_profile_subpage', $stored ) ) {
		return ! empty( $stored['events_profile_subpage'] );
	}

	$legacy = get_option( 'clanbite_events_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'subpage_player', $legacy ) ) {
		return ! empty( $legacy['subpage_player'] );
	}

	return true;
}

/**
 * Whether the Events extension should register the team profile Events subpage and routes.
 *
 * Controlled from Clanbite → Teams → Team profile: Events tab (`clanbite_teams_settings`).
 * Falls back to legacy `clanbite_events_settings.subpage_team` when the Teams field was never saved.
 *
 * @return bool
 */
function clanbite_events_subpage_team_enabled(): bool {
	if ( ! clanbite_events_extension_active() ) {
		return false;
	}

	$stored = get_option( 'clanbite_teams_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'events_profile_subpage', $stored ) ) {
		return ! empty( $stored['events_profile_subpage'] );
	}

	$legacy = get_option( 'clanbite_events_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'subpage_team', $legacy ) ) {
		return ! empty( $legacy['subpage_team'] );
	}

	return true;
}

/**
 * Whether the Events extension should register the group profile Events subpage and block template.
 *
 * Controlled from Clanbite → Groups → Group profile: Events tab (`clanbite_groups_settings`).
 * Falls back to legacy `clanbite_events_settings.subpage_group` when the Groups field was never saved.
 *
 * @return bool
 */
function clanbite_events_subpage_group_enabled(): bool {
	if ( ! clanbite_events_extension_active() ) {
		return false;
	}

	$stored = get_option( 'clanbite_groups_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'events_profile_subpage', $stored ) ) {
		return ! empty( $stored['events_profile_subpage'] );
	}

	$legacy = get_option( 'clanbite_events_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'subpage_group', $legacy ) ) {
		return ! empty( $legacy['subpage_group'] );
	}

	return true;
}
