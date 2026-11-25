<?php
/**
 * Procedural helpers for scheduled events (feature flags, team/group scope).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Admin\General_Settings;

/**
 * Whether events are enabled site-wide (Clanbite → Settings → General).
 *
 * @return bool
 */
function clanbite_events_are_globally_enabled(): bool {
	// Clanbite → Events. Sites that have not saved that tab yet still have the switch under General.
	$stored = get_option( 'clanbite_events_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'events_enabled', $stored ) ) {
		return ! empty( $stored['events_enabled'] );
	}

	$legacy = get_option( General_Settings::OPTION_KEY, array() );
	if ( is_array( $legacy ) && array_key_exists( 'events_enabled', $legacy ) ) {
		return ! empty( $legacy['events_enabled'] );
	}

	return true;
}

/**
 * Default `per_page` for `event-posts` list requests that use `starts_after` / `starts_before` (calendar windows).
 *
 * Kept in sync with {@see \Kernowdev\Clanbite\Events\Event_Entity_Rest_Controller} and event block clients via localized config.
 *
 * @return int Positive integer (default 200).
 */
function clanbite_events_rest_default_per_page_for_range_query(): int {
	$default = 200;

	/**
	 * Filter default page size for ranged event queries (calendar SSR + `fetchEvents`).
	 *
	 * @param int $default Default per_page before request clamping.
	 */
	return (int) max( 1, (int) apply_filters( 'clanbite_events_rest_default_per_page_range', $default ) );
}

/**
 * Maximum `per_page` for ranged `event-posts` queries (hard cap in the REST controller).
 *
 * @return int Positive integer (default 500).
 */
function clanbite_events_rest_max_per_page_for_range_query(): int {
	$max = 500;

	/**
	 * Filter maximum page size for ranged event queries.
	 *
	 * @param int $max Upper bound for `per_page` when a start/end window is set.
	 */
	return (int) max( 1, (int) apply_filters( 'clanbite_events_rest_max_per_page_range', $max ) );
}

/**
 * Default `per_page` for paginated `event-posts` list requests (no start/end window).
 *
 * @return int Positive integer (default 20).
 */
function clanbite_events_rest_default_per_page_paginated(): int {
	$default = 20;

	/**
	 * Filter default page size for paginated event list queries (no calendar window).
	 *
	 * @param int $default Default per_page before clamping to the paginated max.
	 */
	return (int) max( 1, (int) apply_filters( 'clanbite_events_rest_default_per_page_paginated', $default ) );
}

/**
 * Maximum `per_page` for paginated `event-posts` requests (event list block, non-range API use).
 *
 * @return int Positive integer (default 50).
 */
function clanbite_events_rest_max_per_page_paginated(): int {
	$max = 50;

	/**
	 * Filter maximum page size for paginated event list queries.
	 *
	 * @param int $max Upper bound for `per_page` when no start/end window is set.
	 */
	return (int) max( 1, (int) apply_filters( 'clanbite_events_rest_max_per_page_paginated', $max ) );
}

/**
 * Whether players may create their own events (global events switch, filterable).
 *
 * @return bool
 */
function clanbite_player_events_enabled(): bool {
	$enabled = clanbite_events_are_globally_enabled();
	$stored  = get_option( 'clanbite_events_settings', array() );
	if ( $enabled && is_array( $stored ) && array_key_exists( 'player_events_enabled', $stored ) ) {
		$enabled = ! empty( $stored['player_events_enabled'] );
	}

	/**
	 * Filters whether players can create their own events.
	 *
	 * @param bool $enabled Default: events are globally enabled.
	 */
	return (bool) apply_filters( 'clanbite_player_events_enabled', $enabled );
}

/**
 * URL of a player's Events tab, optionally pointing at one event or the "new event" form.
 *
 * @param int        $owner_id Player user ID.
 * @param int|string $event    Event post ID, `new` for the create form, or 0 for the calendar.
 * @return string Empty when the player has no profile URL.
 */
function clanbite_events_get_player_events_url( int $owner_id, $event = 0 ): string {
	if ( $owner_id < 1 || ! function_exists( 'clanbite_get_player_profile_url' ) ) {
		return '';
	}

	$base = trailingslashit( (string) clanbite_get_player_profile_url( $owner_id ) ) . 'events/';
	if ( 'new' === $event ) {
		return add_query_arg( 'clanbite_event_view', 'new', $base );
	}
	if ( (int) $event > 0 ) {
		return add_query_arg( 'clanbite_event_view', (int) $event, $base );
	}

	return $base;
}

/**
 * The event a player Events page is showing: a post ID, `new`, or 0 for the calendar.
 *
 * Read from the `clanbite_event_view` query argument, which only selects what to display (every action behind it checks permission itself).
 *
 * @return int|string
 */
function clanbite_events_requested_player_event() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display selector only; permissions are checked by the blocks and REST routes.
	$raw = isset( $_GET['clanbite_event_view'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['clanbite_event_view'] ) ) : '';
	if ( 'new' === $raw ) {
		return 'new';
	}

	return absint( $raw );
}

/**
 * The players invited to an event (each invitation is one `clanbite_event_invited` meta row so lists can query it).
 *
 * @param int $event_id Event post ID.
 * @return int[]
 */
function clanbite_events_get_invited_user_ids( int $event_id ): array {
	return array_values( array_unique( array_filter( array_map( 'absint', (array) get_post_meta( $event_id, 'clanbite_event_invited', false ) ) ) ) );
}

/**
 * Replace the invitations on an event and tell the people who were newly invited.
 *
 * Only the owner's accepted friends can be invited, the owner cannot invite themselves, and an `only_me` event has no invitations.
 *
 * @param int   $event_id Event post ID.
 * @param int   $owner_id Owner (author) user ID.
 * @param int[] $user_ids Wanted invitees.
 * @return int[] The user IDs invited just now (not before).
 */
function clanbite_events_set_invited_user_ids( int $event_id, int $owner_id, array $user_ids ): array {
	$allowed = array();
	if ( Kernowdev\Clanbite\Events\Event_Post_Type::VISIBILITY_ONLY_ME !== (string) get_post_meta( $event_id, 'clanbite_event_visibility', true ) ) {
		$friends = clanbite_players_get_friend_ids( $owner_id );
		$allowed = array_values( array_intersect( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ), $friends ) );
		$allowed = array_values( array_diff( $allowed, array( $owner_id ) ) );
	}

	/**
	 * Filters who can be invited to a player event.
	 *
	 * @param int[] $allowed  Friend user IDs that will be invited.
	 * @param int   $event_id Event post ID.
	 * @param int   $owner_id Owner user ID.
	 */
	$allowed = array_values( array_unique( array_filter( array_map( 'absint', (array) apply_filters( 'clanbite_player_event_invitees', $allowed, $event_id, $owner_id ) ) ) ) );

	$before = clanbite_events_get_invited_user_ids( $event_id );
	$added  = array_values( array_diff( $allowed, $before ) );

	foreach ( array_diff( $before, $allowed ) as $removed ) {
		delete_post_meta( $event_id, 'clanbite_event_invited', $removed );
	}
	foreach ( $added as $user_id ) {
		add_post_meta( $event_id, 'clanbite_event_invited', $user_id );
	}

	if ( $added && 'publish' === get_post_status( $event_id ) && function_exists( 'clanbite_notify' ) && function_exists( 'clanbite_notifications_extension_active' ) && clanbite_notifications_extension_active() ) {
		$title = get_the_title( $event_id );
		$url   = clanbite_events_get_player_events_url( $owner_id, $event_id );
		foreach ( $added as $user_id ) {
			clanbite_notify(
				$user_id,
				'event_invite',
				clanbite_get_message( 'event_invite.title', array( 'event' => $title ) ),
				array(
					'message'     => clanbite_get_message( 'event_invite.message', array( 'event' => $title ) ),
					'url'         => '' !== $url ? $url : home_url( '/' ),
					'dedupe'      => false,
					'object_type' => Kernowdev\Clanbite\Events\Event_Post_Type::POST_TYPE,
					'object_id'   => $event_id,
				)
			);
		}
	}

	/**
	 * Fires after the invitations on a player event were saved.
	 *
	 * @param int   $event_id Event post ID.
	 * @param int[] $invited  Everyone now invited.
	 * @param int[] $added    Newly invited just now.
	 */
	do_action( 'clanbite_player_event_invites_saved', $event_id, $allowed, $added );

	return $added;
}

/**
 * Whether the player “Events” profile tab should appear for the current viewer.
 *
 * The owner sees their own calendar (events they created or were invited to); other visitors see only the player's own events that
 * the player's privacy setting lets them see.
 *
 * @param int $player_id Profile user ID.
 * @return bool
 */
function clanbite_player_profile_events_subpage_visible_for_viewer( int $player_id ): bool {
	if ( $player_id < 1 ) {
		return false;
	}

	// Everyone sees the tab: the owner gets their calendar, other visitors only the events the owner lets them see.
	return clanbite_player_events_enabled();
}

/**
 * Read a team's or group's own "events enabled" switch, which is on until someone turns it off.
 *
 * A saved "off" is stored as an empty string, so only a switch that was never saved counts as on when empty.
 *
 * @since 1.7.0
 *
 * @param int    $post_id  Team or group post ID.
 * @param string $meta_key Meta key of the switch.
 * @return bool
 */
function clanbite_events_entity_switch_is_on( int $post_id, string $meta_key ): bool {
	if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
		return true;
	}

	$raw = get_post_meta( $post_id, $meta_key, true );

	return '' !== $raw && clanbite_events_parse_entity_enabled_meta( $raw );
}

/**
 * Read a per-post "events enabled" flag: empty meta inherits enabled when global is on; explicit 0 disables.
 *
 * @param mixed $raw Raw meta value.
 * @return bool True when the entity allows events (subject to global).
 */
function clanbite_events_parse_entity_enabled_meta( $raw ): bool {
	if ( '' === $raw || null === $raw ) {
		return true;
	}
	if ( false === $raw || 0 === $raw || '0' === $raw ) {
		return false;
	}
	if ( true === $raw || 1 === $raw || '1' === $raw ) {
		return true;
	}
	if ( is_string( $raw ) ) {
		return in_array( strtolower( trim( $raw ) ), array( 'yes', 'on', 'true' ), true );
	}

	return (bool) $raw;
}

/**
 * Whether events are available for a team (global + per-team meta).
 *
 * @param int $team_id Team post ID (`clanbite_team`).
 * @return bool
 */
function clanbite_events_are_enabled_for_team( int $team_id ): bool {
	if ( $team_id < 1 ) {
		return false;
	}
	if ( ! clanbite_events_are_globally_enabled() ) {
		return false;
	}

	return clanbite_events_entity_switch_is_on( $team_id, 'clanbite_team_events_enabled' );
}

/**
 * Whether events are available for a group (global + per-group meta).
 *
 * @param int $group_id Group post ID (`clanbite_group`).
 * @return bool
 */
function clanbite_events_are_enabled_for_group( int $group_id ): bool {
	if ( $group_id < 1 ) {
		return false;
	}
	if ( ! clanbite_events_are_globally_enabled() ) {
		return false;
	}

	return clanbite_events_entity_switch_is_on( $group_id, 'clanbite_group_events_enabled' );
}

/**
 * Team post IDs used when building a player’s merged “my events” calendar.
 *
 * Defaults to `clanbite_team_membership_ids` user meta. Extensions may add IDs via
 * {@see clanbite_events_player_calendar_team_ids}.
 *
 * @param int $user_id WordPress user ID.
 * @return int[] Unique team IDs.
 */
function clanbite_events_get_user_team_ids_for_calendar( int $user_id ): array {
	if ( $user_id < 1 ) {
		return array();
	}

	if ( ! function_exists( 'clanbite_teams' ) || null === clanbite_teams() ) {
		return array();
	}

	$raw = get_user_meta( $user_id, 'clanbite_team_membership_ids', true );
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}
	$ids = array_values(
		array_unique(
			array_filter(
				array_map( 'intval', $raw ),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		)
	);

	/**
	 * Filter team IDs included in a player’s merged event calendar REST query.
	 *
	 * @param int[] $team_ids Team post IDs.
	 * @param int   $user_id  Profile owner user ID.
	 */
	$filtered = apply_filters( 'clanbite_events_player_calendar_team_ids', $ids, $user_id );

	return array_values(
		array_unique(
			array_filter(
				array_map( 'intval', is_array( $filtered ) ? $filtered : array() ),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		)
	);
}

/**
 * Group post IDs used when building a player’s merged calendar (extensions add IDs via filter).
 *
 * Core returns an empty list; extensions should populate via
 * {@see clanbite_events_player_calendar_group_ids}.
 *
 * @param int $user_id WordPress user ID.
 * @return int[] Unique group IDs.
 */
function clanbite_events_get_user_group_ids_for_calendar( int $user_id ): array {
	if ( $user_id < 1 ) {
		return array();
	}

	$groups_feature_active = post_type_exists( 'clanbite_group' );

	/**
	 * Filter whether group-scoped events may appear in a player’s merged calendar.
	 *
	 * Default: true when the `clanbite_group` post type is registered (group product active).
	 *
	 * @param bool $active   Whether group events are included.
	 * @param int  $user_id Profile owner user ID.
	 */
	$groups_feature_active = (bool) apply_filters( 'clanbite_groups_feature_active', $groups_feature_active, $user_id );

	if ( ! $groups_feature_active ) {
		return array();
	}

	/**
	 * Filter group IDs included in a player’s merged event calendar REST query.
	 *
	 * @param int[] $group_ids Group post IDs (empty by default).
	 * @param int   $user_id   Profile owner user ID.
	 */
	$filtered = apply_filters( 'clanbite_events_player_calendar_group_ids', array(), $user_id );

	return array_values(
		array_unique(
			array_filter(
				array_map( 'intval', is_array( $filtered ) ? $filtered : array() ),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		)
	);
}

/**
 * Build static month grid markup for the event calendar block (empty cells; matches client grid classes).
 *
 * @param string $anchor_ymd     Any date in the target month (`Y-m-d`, site timezone).
 * @param array  $weekday_labels Seven short labels (Sunday-first), same order as JS `getDay()`.
 * @param string $today_ymd      Site-local today as `Y-m-d` (e.g. from `wp_date( 'Y-m-d' )`).
 * @return string Safe HTML fragment (no outer wrapper).
 */
function clanbite_event_calendar_month_grid_markup( string $anchor_ymd, array $weekday_labels, string $today_ymd ): string {
	$tz = wp_timezone();

	try {
		$anchor = new \DateTimeImmutable( $anchor_ymd . ' 12:00:00', $tz );
	} catch ( \Exception $e ) {
		$anchor = new \DateTimeImmutable( 'now', $tz );
	}

	$first_of_month = $anchor->modify( 'first day of this month' )->setTime( 0, 0, 0 );
	$dow            = (int) $first_of_month->format( 'w' );
	$start          = $first_of_month->modify( '-' . $dow . ' days' );

	$html  = '<div class="clanbite-event-calendar__month">';
	$html .= '<div class="clanbite-event-calendar__dow">';
	for ( $i = 0; $i < 7; $i++ ) {
		$lab   = isset( $weekday_labels[ $i ] ) ? (string) $weekday_labels[ $i ] : '';
		$html .= '<div class="clanbite-event-calendar__dow-cell">' . esc_html( $lab ) . '</div>';
	}
	$html .= '</div><div class="clanbite-event-calendar__grid">';

	$cursor = clone $start;
	for ( $w = 0; $w < 6; $w++ ) {
		for ( $c = 0; $c < 7; $c++ ) {
			$ymd      = $cursor->format( 'Y-m-d' );
			$in_month = (int) $cursor->format( 'n' ) === (int) $anchor->format( 'n' );
			$is_today = ( $ymd === $today_ymd );

			$cell_cls = 'clanbite-event-calendar__cell';
			if ( ! $in_month ) {
				$cell_cls .= ' is-muted';
			}
			if ( $is_today ) {
				$cell_cls .= ' is-today';
			}

			$day_num = (int) $cursor->format( 'j' );
			$html   .= '<div class="' . esc_attr( $cell_cls ) . '"><div class="clanbite-event-calendar__cell-num">' . esc_html( (string) $day_num ) . '</div><ul class="clanbite-event-calendar__cell-events"></ul></div>';
			$cursor  = $cursor->modify( '+1 day' );
		}
	}

	$html .= '</div></div>';

	return $html;
}

require_once __DIR__ . '/event-block-ssr.php';
