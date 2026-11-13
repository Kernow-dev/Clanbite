<?php
/**
 * Customisable messages: one registry for every notification, popup and automatic post Clanbite and its extensions write.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Areas the messages are grouped under: one per extension, shown on that extension's Messages sub-tab.
 *
 * @since 1.5.0
 *
 * @return array<string, string> Group slug => translated label.
 */
function clanbite_message_groups(): array {
	$groups = array(
		'general'       => __( 'General', 'clanbite' ),
		'teams'         => __( 'Teams', 'clanbite' ),
		'events'        => __( 'Events', 'clanbite' ),
		'players'       => __( 'Players', 'clanbite' ),
		'notifications' => __( 'Notifications', 'clanbite' ),
	);

	/**
	 * Filters the groups messages are listed under (one per extension; see `clanbite_message_group_extensions`).
	 *
	 * Add a group for your extension, then pass its slug as `group` when you register messages.
	 *
	 * @since 1.5.0
	 *
	 * @param array<string, string> $groups Group slug => translated label.
	 */
	$groups = (array) apply_filters( 'clanbite_message_groups', $groups );
	if ( ! isset( $groups['general'] ) ) {
		$groups['general'] = __( 'General', 'clanbite' );
	}

	return $groups;
}

/**
 * Which extension each message group belongs to.
 *
 * Messages are edited on the tab of the extension that sends them (Ranks messages under Ranks → Messages, and so on). A
 * group mapped to `general`, or to no extension at all, appears under General → Messages. A group mapped to an extension
 * that is switched off is hidden until the extension is on again.
 *
 * @since 1.5.0
 *
 * @return array<string, string> Group slug => extension slug (for example `cp_teams`), or `general`.
 */
function clanbite_message_group_extensions(): array {
	$map = array(
		'general'       => 'general',
		'teams'         => 'cp_teams',
		'events'        => 'cp_events',
		'players'       => 'cp_players',
		'notifications' => 'cp_notifications',
	);

	/**
	 * Filters which extension's settings tab lists each message group.
	 *
	 * Map the group you added with `clanbite_message_groups` to your extension slug so its messages appear under your
	 * extension's own **Messages** sub-tab. A group left out of the map is listed under General → Messages.
	 *
	 * @since 1.5.0
	 *
	 * @param array<string, string> $map Group slug => extension slug (`cp_teams`), or `general`.
	 */
	$map = (array) apply_filters( 'clanbite_message_group_extensions', $map );

	return array_map( 'strval', $map );
}

/**
 * Register a customisable message.
 *
 * A message is a short piece of wording the site owner may want to change: a notification title, a popup, the text of an
 * automatic post. Register it with its default and the placeholders you will fill in, then build the text with
 * {@see clanbite_get_message()} instead of a hard-coded `__()` string. Site owners edit it under the **Messages** sub-tab
 * of the extension's settings tab (see {@see clanbite_message_group_extensions()}); an empty field keeps your default.
 *
 * Call this on or after `init` (so `__()` already has its translations), or add the definition through the
 * `clanbite_message_definitions` filter, which is evaluated lazily.
 *
 * @since 1.5.0
 *
 * @param string               $key  Unique key, lowercase with an area prefix: `team_invite.title`. Letters, numbers, `_` and one `.` between parts.
 * @param array<string, mixed> $args {
 *     Message definition.
 *
 *     @type string                $label        Required. Short name shown above the field.
 *     @type string                $default      Required. Default wording, with `{placeholder}` tokens.
 *     @type string                $group        Group slug from {@see clanbite_message_groups()}. Default `general`.
 *     @type string                $description  Where the message appears (one sentence).
 *     @type array<string, string> $placeholders Token name => what it contains, for the placeholders `$default` may use.
 *     @type bool                  $multiline    Use a multi-line field (default false).
 * }
 * @return void
 */
function clanbite_register_message( string $key, array $args ): void {
	add_filter(
		'clanbite_message_definitions',
		static function ( $definitions ) use ( $key, $args ) {
			$definitions         = is_array( $definitions ) ? $definitions : array();
			$definitions[ $key ] = $args;

			return $definitions;
		}
	);
}

/**
 * Registered messages, normalised.
 *
 * @since 1.5.0
 *
 * @return array<string, array{label: string, default: string, group: string, description: string, placeholders: array<string, string>, multiline: bool}>
 */
function clanbite_get_message_definitions(): array {
	/**
	 * Filters the registered messages (see {@see clanbite_register_message()} for the shape of each entry).
	 *
	 * Core and every extension add their messages here, so a plugin can also change another plugin's default wording
	 * or remove a message from the Messages sub-tabs.
	 *
	 * @since 1.5.0
	 *
	 * @param array<string, array<string, mixed>> $definitions Message key => definition.
	 */
	$raw = (array) apply_filters( 'clanbite_message_definitions', array() );

	$groups = clanbite_message_groups();
	$out    = array();
	foreach ( $raw as $key => $def ) {
		$key = (string) $key;
		if ( ! is_array( $def ) || ! preg_match( '/^[a-z0-9_]+(\.[a-z0-9_]+)?$/', $key ) ) {
			continue;
		}
		$default = isset( $def['default'] ) ? (string) $def['default'] : '';
		$label   = isset( $def['label'] ) ? (string) $def['label'] : '';
		if ( '' === $default || '' === $label ) {
			continue;
		}
		$group = isset( $def['group'] ) ? sanitize_key( (string) $def['group'] ) : 'general';
		$group = isset( $groups[ $group ] ) ? $group : 'general';

		$placeholders = array();
		foreach ( (array) ( $def['placeholders'] ?? array() ) as $token => $what ) {
			$token = (string) $token;
			if ( preg_match( '/^[a-z0-9_]+$/', $token ) ) {
				$placeholders[ $token ] = (string) $what;
			}
		}

		$out[ $key ] = array(
			'label'        => $label,
			'default'      => $default,
			'group'        => $group,
			'description'  => isset( $def['description'] ) ? (string) $def['description'] : '',
			'placeholders' => $placeholders,
			'multiline'    => ! empty( $def['multiline'] ),
		);
	}

	return $out;
}

/**
 * Settings field id for a message key (the option array key: the dot becomes a hyphen, which a message key cannot contain, so ids never collide).
 *
 * @since 1.5.0
 *
 * @param string $key Message key.
 * @return string
 */
function clanbite_message_field_id( string $key ): string {
	// A key may hold letters, numbers and underscores plus one dot, never a hyphen, so mapping the dot to a hyphen cannot
	// make two different keys share a field id (`__` could: `a.b__c` and `a__b.c`).
	return str_replace( '.', '-', $key );
}

/**
 * The wording a message uses: the site owner's version, or the default.
 *
 * The result still contains `{placeholder}` tokens; use {@see clanbite_get_message()} to fill them in. Use this when the
 * values are only known later, for example to hand the template to JavaScript.
 *
 * @since 1.5.0
 *
 * @param string $key Message key.
 * @return string Template, or an empty string for an unregistered key.
 */
function clanbite_get_message_template( string $key ): string {
	$definitions = clanbite_get_message_definitions();
	if ( ! isset( $definitions[ $key ] ) ) {
		return '';
	}

	$stored   = get_option( \Kernowdev\Clanbite\Admin\Messages_Settings::OPTION_KEY, array() );
	$override = is_array( $stored ) ? trim( (string) ( $stored[ clanbite_message_field_id( $key ) ] ?? '' ) ) : '';
	$template = '' !== $override ? $override : $definitions[ $key ]['default'];

	/**
	 * Filters a message's wording before its placeholders are filled in.
	 *
	 * @since 1.5.0
	 *
	 * @param string $template Wording: the site owner's version, or the default.
	 * @param string $key      Message key, for example `team_invite.title`.
	 * @param bool   $is_default Whether `$template` is the registered default (no custom wording saved).
	 */
	return (string) apply_filters( 'clanbite_message_template', $template, $key, '' === $override );
}

/**
 * Replace `{placeholder}` tokens in a template.
 *
 * Only tokens that have a value in `$vars` are replaced; anything else is left as written, so a typo stays visible.
 *
 * @since 1.5.0
 *
 * @param string                $template Wording with `{token}` placeholders.
 * @param array<string, scalar> $vars     Token => value (plain text).
 * @return string
 */
function clanbite_format_message( string $template, array $vars ): string {
	$replace = array();
	foreach ( $vars as $token => $value ) {
		if ( is_scalar( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
			$replace[ '{' . $token . '}' ] = (string) $value;
		}
	}

	return strtr( $template, $replace );
}

/**
 * Build a message: the site's wording with the placeholders filled in.
 *
 * The result is plain text. Escape it for the place you print it, exactly as you would the `__()` string it replaces.
 *
 * @since 1.5.0
 *
 * @param string                $key  Message key (see {@see clanbite_register_message()}).
 * @param array<string, scalar> $vars Token => value, for the placeholders the message declares.
 * @return string
 */
function clanbite_get_message( string $key, array $vars = array() ): string {
	$template = clanbite_get_message_template( $key );
	$message  = clanbite_format_message( $template, $vars );

	/**
	 * Filters a finished message.
	 *
	 * Runs on every message, so check `$key` before changing anything.
	 *
	 * @since 1.5.0
	 *
	 * @param string                $message  The message with placeholders filled in.
	 * @param string                $key      Message key.
	 * @param array<string, scalar> $vars     The values that were substituted.
	 * @param string                $template The wording before substitution.
	 */
	return (string) apply_filters( 'clanbite_message', $message, $key, $vars, $template );
}

/**
 * Wording a message ships with, ignoring anything the site owner saved.
 *
 * @since 1.5.0
 *
 * @param string $key Message key.
 * @return string Default wording, or an empty string for an unregistered key.
 */
function clanbite_get_message_default( string $key ): string {
	$definitions = clanbite_get_message_definitions();

	return isset( $definitions[ $key ] ) ? $definitions[ $key ]['default'] : '';
}

/**
 * Messages written by core Clanbite (teams, events, the player settings screen).
 *
 * Extensions register their own the same way, through `clanbite_message_definitions`.
 *
 * @since 1.5.0
 *
 * @param mixed $definitions Messages registered so far.
 * @return array<string, array<string, mixed>>
 */
function clanbite_core_message_definitions( $definitions ): array {
	$definitions = is_array( $definitions ) ? $definitions : array();

	$invite_tokens    = array(
		'inviter' => __( 'name of the person who sent the invitation', 'clanbite' ),
		'team'    => __( 'team name', 'clanbite' ),
	);
	$challenge_tokens = array(
		'challenger' => __( 'name of the person who sent the challenge', 'clanbite' ),
		'team'       => __( 'name of the challenged team', 'clanbite' ),
	);
	$event_tokens     = array( 'event' => __( 'event title', 'clanbite' ) );

	return array_merge(
		$definitions,
		array(
			'team_invite.title'               => array(
				'group'        => 'teams',
				'label'        => __( 'Team invitation: notification title', 'clanbite' ),
				'description'  => __( 'Sent to a member when they are invited to a team.', 'clanbite' ),
				'default'      => __( '{inviter} invited you to join {team}', 'clanbite' ),
				'placeholders' => $invite_tokens,
			),
			'team_invite.accept_success'      => array(
				'group'       => 'teams',
				'label'       => __( 'Team invitation: popup after Accept', 'clanbite' ),
				'description' => __( 'Popup shown after a member accepts the invitation.', 'clanbite' ),
				'default'     => __( 'You have joined the team!', 'clanbite' ),
			),
			'team_invite.decline_success'     => array(
				'group'       => 'teams',
				'label'       => __( 'Team invitation: popup after Decline', 'clanbite' ),
				'description' => __( 'Popup shown after a member declines the invitation.', 'clanbite' ),
				'default'     => __( 'Invitation declined.', 'clanbite' ),
			),
			'team_challenge.title'            => array(
				'group'        => 'teams',
				'label'        => __( 'Team challenge: notification title', 'clanbite' ),
				'description'  => __( 'Sent to the challenged team’s admins.', 'clanbite' ),
				'default'      => __( '{challenger} challenged {team}', 'clanbite' ),
				'placeholders' => $challenge_tokens,
			),
			'team_challenge.message'          => array(
				'group'       => 'teams',
				'label'       => __( 'Team challenge: notification text', 'clanbite' ),
				'description' => __( 'The line under the title of a challenge notification.', 'clanbite' ),
				'default'     => __( 'Review the challenge and accept or decline.', 'clanbite' ),
			),
			'team_challenge.accept_success'   => array(
				'group'       => 'teams',
				'label'       => __( 'Team challenge: popup after Accept', 'clanbite' ),
				'description' => __( 'Popup shown after a challenge is accepted.', 'clanbite' ),
				'default'     => __( 'Challenge accepted. Match and event were created.', 'clanbite' ),
			),
			'team_challenge.decline_success'  => array(
				'group'       => 'teams',
				'label'       => __( 'Team challenge: popup after Decline', 'clanbite' ),
				'description' => __( 'Popup shown after a challenge is declined.', 'clanbite' ),
				'default'     => __( 'Challenge declined.', 'clanbite' ),
			),
			'team_match_event.title'          => array(
				'group'       => 'teams',
				'label'       => __( 'New team match: notification title', 'clanbite' ),
				'description' => __( 'Sent to every team member when an accepted challenge creates a match event.', 'clanbite' ),
				'default'     => __( 'New match scheduled for your team', 'clanbite' ),
			),
			'team_match_event.message'        => array(
				'group'       => 'teams',
				'label'       => __( 'New team match: notification text', 'clanbite' ),
				'description' => __( 'The line under the title of the new match notification.', 'clanbite' ),
				'default'     => __( 'You have been added to the match event. Please confirm your attendance.', 'clanbite' ),
			),
			'event_invite.title'              => array(
				'group'        => 'events',
				'label'        => __( 'Event invitation: notification title', 'clanbite' ),
				'description'  => __( 'Sent when a manager asks members to confirm their attendance.', 'clanbite' ),
				'default'      => __( 'You\'re invited: {event}', 'clanbite' ),
				'placeholders' => $event_tokens,
			),
			'event_invite.message'            => array(
				'group'       => 'events',
				'label'       => __( 'Event invitation: notification text', 'clanbite' ),
				'description' => __( 'The line under the title of an event invitation.', 'clanbite' ),
				'default'     => __( 'Open the event to confirm your attendance.', 'clanbite' ),
			),
			'team_event.title'                => array(
				'group'        => 'events',
				'label'        => __( 'New team event: notification title', 'clanbite' ),
				'description'  => __( 'Sent to the roster when a manager posts a team event.', 'clanbite' ),
				'default'      => __( 'New team event: {event}', 'clanbite' ),
				'placeholders' => $event_tokens,
			),
			'team_event.message'              => array(
				'group'       => 'events',
				'label'       => __( 'New team event: notification text', 'clanbite' ),
				'description' => __( 'The line under the title of a new team event.', 'clanbite' ),
				'default'     => __( 'A manager posted a new event for your roster.', 'clanbite' ),
			),
			'group_event.title'               => array(
				'group'        => 'events',
				'label'        => __( 'New group event: notification title', 'clanbite' ),
				'description'  => __( 'Sent to group members when a manager posts a group event.', 'clanbite' ),
				'default'      => __( 'New group event: {event}', 'clanbite' ),
				'placeholders' => $event_tokens,
			),
			'group_event.message'             => array(
				'group'       => 'events',
				'label'       => __( 'New group event: notification text', 'clanbite' ),
				'description' => __( 'The line under the title of a new group event.', 'clanbite' ),
				'default'     => __( 'A manager posted a new event for your group.', 'clanbite' ),
			),
			'notification_bell.action_failed' => array(
				'group'       => 'notifications',
				'label'       => __( 'Notification button failed: popup', 'clanbite' ),
				'description' => __( 'Popup shown when a button on a notification (Accept, Decline, ...) fails and the server gave no reason.', 'clanbite' ),
				'default'     => __( 'That did not work. Please try again.', 'clanbite' ),
			),
			'player_settings.saved'           => array(
				'group'       => 'players',
				'label'       => __( 'Profile settings: saved popup', 'clanbite' ),
				'description' => __( 'Popup shown after a member saves their profile settings.', 'clanbite' ),
				'default'     => __( 'Your changes were saved successfully.', 'clanbite' ),
			),
			'player_settings.save_failed'     => array(
				'group'       => 'players',
				'label'       => __( 'Profile settings: save failed popup', 'clanbite' ),
				'description' => __( 'Popup shown when the server rejects the save.', 'clanbite' ),
				'default'     => __( 'There was an error while saving changes.', 'clanbite' ),
			),
			'player_settings.network_error'   => array(
				'group'       => 'players',
				'label'       => __( 'Profile settings: connection problem popup', 'clanbite' ),
				'description' => __( 'Popup shown when the save request could not reach the server.', 'clanbite' ),
				'default'     => __( 'Network error while saving changes.', 'clanbite' ),
			),
			'player_settings.bad_url'         => array(
				'group'       => 'players',
				'label'       => __( 'Profile settings: reload needed popup', 'clanbite' ),
				'description' => __( 'Popup shown when the page cannot work out where to save.', 'clanbite' ),
				'default'     => __( 'Could not resolve a valid save URL. Try reloading the page.', 'clanbite' ),
			),
		)
	);
}
add_filter( 'clanbite_message_definitions', 'clanbite_core_message_definitions', 5 );
