<?php
/**
 * Team ownership tests: two captains, an editor and a player. Run: `php tests/php/team-permissions.php`.
 *
 * Checks that the manage screen, the REST API and WordPress' own post and meta permissions agree. Team rank checks run
 * only when Clanbite Ranks is active.
 *
 * @package clanbite
 */

define( 'DISABLE_WP_CRON', true );
require __DIR__ . '/harness.php';

if ( ! function_exists( 'clanbite_teams' ) || ! clanbite_teams() ) {
	fwrite( STDERR, "Enable the Teams extension first.\n" );
	exit( 2 );
}

add_filter( 'pre_wp_mail', '__return_true' );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'blocked', 'Blocked in tests.' ) );
// Team ranks on for this process only; nothing is saved.
add_filter( 'option_clanbite_ranks_settings', static fn( $value ) => array_merge( (array) $value, array( 'team_ranks_enabled' => true ) ) );
do_action( 'rest_api_init' );

/** Thrown instead of redirecting or dying, so form handlers can be called directly. */
final class Clanbite_Test_Stop extends Exception {}
add_filter(
	'wp_redirect',
	static function ( $url ) {
		throw new Clanbite_Test_Stop( 'redirect ' . wp_parse_url( $url, PHP_URL_QUERY ) );
	}
);
add_filter(
	'wp_die_handler',
	static fn() => static function () {
		throw new Clanbite_Test_Stop( 'denied' );
	}
);

$teams_ext = clanbite_teams();
$run       = 'zzperm' . wp_rand( 10000, 99999 );
$u         = array();
$team      = array();

$t->cleanup(
	static function () use ( &$u, &$team ): void {
		global $wpdb;
		wp_set_current_user( 0 );
		foreach ( $team as $id ) {
			wp_delete_post( $id, true );
		}
		$feed = $wpdb->prefix . 'clanbite_social_posts';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $feed ) ) === $feed ) {
			foreach ( $team as $id ) {
				$wpdb->delete( $feed, array( 'team_id' => $id ) );
			}
		}
		$notes = $wpdb->prefix . 'clanbite_notifications';
		foreach ( $u as $id ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $notes ) ) === $notes ) {
				$wpdb->delete( $notes, array( 'user_id' => $id ) );
			}
			wp_delete_user( $id );
		}
	}
);
// Clean up even if a test stops with a fatal error.
$finished = false;
register_shutdown_function(
	static function () use ( $t, &$finished ): void {
		if ( ! $finished ) {
			$t->ok( 'the run reached the end (it stopped early)', false, (string) ( error_get_last()['message'] ?? '' ) );
			$t->finish();
			exit( 1 );
		}
	}
);

$roles = array(
	'capA'    => 'subscriber',
	'capB'    => 'author',
	'editorA' => 'subscriber',
	'player'  => 'subscriber',
	'invitee' => 'subscriber',
	'siteEd'  => 'editor',
	'admin'   => 'administrator',
);
foreach ( $roles as $name => $role ) {
	$u[ $name ] = wp_insert_user(
		array(
			'user_login' => "{$run}-{$name}",
			'user_pass'  => wp_generate_password( 32 ),
			'user_email' => "{$run}-{$name}@example.invalid",
			'role'       => $role,
		)
	);
}
foreach ( array( 'A' => 'capA', 'B' => 'capB' ) as $key => $captain ) {
	$team[ $key ] = wp_insert_post(
		array(
			'post_type'   => 'clanbite_team',
			'post_status' => 'publish',
			'post_title'  => "{$run} team {$key}",
			'post_name'   => "{$run}-team-" . strtolower( $key ),
			'post_author' => $u[ $captain ],
		)
	);
	update_post_meta( $team[ $key ], 'clanbite_team_allow_frontend_edit', 1 );
}
$roster_a = array(
	$u['capA']    => 'admin',
	$u['editorA'] => 'editor',
	$u['player']  => 'member',
);
$teams_ext->persist_team_roles_map( $team['A'], $roster_a );
$teams_ext->persist_team_roles_map( $team['B'], array( $u['capB'] => 'admin' ) );

$as   = static function ( string $who ) use ( &$u ): void {
	wp_set_current_user( $u[ $who ] );
};
$rest = static function ( string $who, int $team_id, array $body ) use ( $as ): int {
	$as( $who );
	$request = new WP_REST_Request( 'POST', "/wp/v2/clanbite_team/{$team_id}" );
	$request->set_header( 'content-type', 'application/json' );
	$request->set_body( wp_json_encode( $body ) );
	return rest_do_request( $request )->get_status();
};
$submit = static function ( callable $handler, array $fields ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test builds the request, with a real nonce.
	$_POST    = $fields;
	$_REQUEST = $fields;
	try {
		$handler();
	} catch ( Clanbite_Test_Stop $stop ) {
		return $stop->getMessage();
	}
	return 'returned';
};
$form = static function ( string $who, int $team_id, array $extra = array() ) use ( $as, $submit, $teams_ext ): bool {
	$as( $who );
	$result = $submit(
		array( $teams_ext, 'handle_save_team_manage' ),
		array_merge(
			array(
				'clanbite_team_id'            => $team_id,
				'team_name'                   => get_the_title( $team_id ),
				'team_allow_frontend_edit'    => '1',
				'team_allow_invites'          => '1',
				'_clanbite_team_manage_nonce' => wp_create_nonce( 'clanbite_team_manage_' . $team_id ),
			),
			$extra
		)
	);
	return false !== strpos( $result, 'status=saved' );
};
$role = static fn( string $key, string $who ) => $teams_ext->get_team_member_role( $team[ $key ], $u[ $who ] );

// Each captain manages only their own team, on the screen and through REST.
foreach ( array( 'capA' => array( 'A', 'B' ), 'capB' => array( 'B', 'A' ), 'editorA' => array( 'A', 'B' ) ) as $who => $pair ) {
	list( $own, $other ) = $pair;
	$t->ok( "{$who} saves own team on the screen", $form( $who, $team[ $own ] ) );
	$t->ok( "{$who} saves own team through REST", 200 === $rest( $who, $team[ $own ], array( 'meta' => array( 'clanbite_team_wins' => 1 ) ) ) );
	$t->ok( "{$who} cannot save the other team on the screen", ! $form( $who, $team[ $other ] ) );
	$t->ok( "{$who} cannot save the other team through REST", 403 === $rest( $who, $team[ $other ], array( 'meta' => array( 'clanbite_team_wins' => 1 ) ) ) );
}
$t->ok( 'a member cannot save the team', ! $form( 'player', $team['A'] ) && 403 === $rest( 'player', $team['A'], array( 'title' => 'x' ) ) );
$t->ok( 'a manager may still change the title through REST', 200 === $rest( 'editorA', $team['A'], array( 'title' => "{$run} renamed" ) ) );

// Ownership, status and address stay with the site's roles.
$before = get_post( $team['A'] );
foreach (
	array(
		'author'   => array( 'author' => $u['editorA'] ),
		'status'   => array( 'status' => 'draft' ),
		'address'  => array( 'slug' => "{$run}-stolen" ),
		'password' => array( 'password' => 'x' ),
	) as $label => $body
) {
	$t->ok( "REST refuses a manager changing the {$label}", 403 === $rest( 'editorA', $team['A'], $body ) );
}
$t->ok( 'REST accepts unchanged protected values', 200 === $rest( 'capA', $team['A'], array( 'status' => 'publish' ) ) );

// The same fields when saved any other way (wp-admin, quick edit, XML-RPC all end in wp_update_post()).
$as( 'editorA' );
wp_update_post(
	array(
		'ID'          => $team['A'],
		'post_author' => $u['editorA'],
		'post_status' => 'draft',
		'post_name'   => "{$run}-stolen",
	)
);
$after = get_post( $team['A'] );
$t->ok( 'saving by other routes keeps the author', $after->post_author === $before->post_author );
$t->ok( 'saving by other routes keeps the status and address', 'publish' === $after->post_status && $after->post_name === $before->post_name );
$t->ok( 'an editor still cannot delete the team', ! $teams_ext->user_can_delete_team_on_frontend( $team['A'], $u['editorA'] ) );

$as( 'siteEd' );
wp_update_post( array( 'ID' => $team['A'], 'post_status' => 'draft' ) );
$t->ok( 'a WordPress Editor can still change the status', 'draft' === get_post_status( $team['A'] ) );
wp_update_post( array( 'ID' => $team['A'], 'post_status' => 'publish' ) );

$as( 'capB' );
$t->ok( 'an Author who owns a team can still trash it', (bool) wp_trash_post( $team['B'] ) && 'trash' === get_post_status( $team['B'] ) );
wp_untrash_post( $team['B'] );
$t->ok( 'and restore it', 'trash' !== get_post_status( $team['B'] ) );
wp_set_current_user( 0 );
wp_update_post( array( 'ID' => $team['B'], 'post_status' => 'publish' ) );

// Stored roster roles cannot be written through WordPress' meta permissions.
foreach ( array( 'clanbite_team_member_roles', 'CLANBITE_TEAM_MEMBER_ROLES', ' clanbite_team_member_roles ' ) as $meta_key ) {
	foreach ( array( 'add_post_meta', 'edit_post_meta', 'delete_post_meta' ) as $cap ) {
		$t->ok( "no manager has {$cap} on '{$meta_key}'", ! user_can( $u['editorA'], $cap, $team['A'], $meta_key ) && ! user_can( $u['capA'], $cap, $team['A'], $meta_key ) );
	}
}
$t->ok( 'a site admin keeps the meta permission', user_can( $u['admin'], 'edit_post_meta', $team['A'], 'clanbite_team_member_roles' ) );

// Joining needs an invitation.
$form( 'capB', $team['B'], array( 'member_roles' => array( $u['capB'] => 'admin', $u['player'] => 'member' ) ) );
$t->ok( 'a captain cannot add a player from the roster form', null === $role( 'B', 'player' ) );
$form( 'capA', $team['A'], array( 'member_roles' => array( $u['capA'] => 'admin', $u['editorA'] => 'editor', $u['player'] => 'editor' ) ) );
$t->ok( 'a captain can still change a member role', 'editor' === $role( 'A', 'player' ) );
$form( 'editorA', $team['A'], array( 'member_roles' => array( $u['editorA'] => 'admin' ) ) );
$t->ok( 'an editor cannot change the roster', 'editor' === $role( 'A', 'editorA' ) && 'admin' === $role( 'A', 'capA' ) );

wp_set_current_user( 0 );
$accept = $teams_ext->handle_notification_actions(
	null,
	'team_invite_accept',
	(object) array(
		'object_id' => $team['B'],
		'actor_id'  => $u['capB'],
	),
	array(),
	$u['invitee']
);
$t->ok( 'accepting an invitation still adds the player', ! empty( $accept['success'] ) && 'member' === $role( 'B', 'invitee' ) );

// Team ranks follow current membership.
$ranks = '\Kernowdev\ClanbiteRanks\Ranks\Team_Ranks';
$rank  = static function ( string $who, int $team_id, int $user_id ) use ( $as, $submit, $ranks ): string {
	$as( $who );
	return $submit(
		array( $ranks, 'handle_save_team_ranks' ),
		array(
			'team_id'                    => $team_id,
			'_clanbite_ranks_team_nonce' => wp_create_nonce( 'clanbite_ranks_team_' . $team_id ),
			'team_rank_defs'             => array( 'label' => array( 'Starter' ), 'icon_url' => array( '' ) ),
			'member_rank'                => array( $user_id => 'Starter' ),
		)
	);
};
if ( class_exists( $ranks ) ) {
	$held = static fn(): bool => isset( $ranks::get_team_rank_assignments( $team['A'] )[ $u['player'] ] );
	$rank( 'capA', $team['A'], $u['player'] );
	$t->ok( 'a captain ranks a current member', $held() );
	$t->ok( 'a captain cannot rank players on another team', 'denied' === $rank( 'capA', $team['B'], $u['player'] ) );
	$form( 'capA', $team['A'], array( 'member_roles' => array( $u['capA'] => 'admin', $u['editorA'] => 'editor' ) ) );
	$stored = (array) get_post_meta( $team['A'], 'clanbite_team_ranks_assignments', true );
	$t->ok( 'removing a player clears the stored rank', ! isset( $stored[ $u['player'] ] ) );
	$rank( 'capA', $team['A'], $u['player'] );
	$t->ok( 'a departed player cannot be ranked', ! $held() );
	update_post_meta( $team['A'], 'clanbite_team_ranks_assignments', array( $u['player'] => 'Starter' ) );
	$t->ok( 'a rank left over from before is ignored', ! $held() );
}

// Turning front-end editing off applies everywhere, whatever the captain's WordPress role.
update_post_meta( $team['B'], 'clanbite_team_allow_frontend_edit', 0 );
$t->ok( 'editing off: screen refuses the captain', ! $form( 'capB', $team['B'] ) );
$t->ok( 'editing off: REST refuses an Author captain', 403 === $rest( 'capB', $team['B'], array( 'meta' => array( 'clanbite_team_wins' => 9 ) ) ) );
if ( class_exists( $ranks ) ) {
	$t->ok( 'editing off: captain cannot manage team ranks', ! $ranks::user_can_manage_team_ranks( $team['B'], $u['capB'] ) );
}
$t->ok( 'editing off: a WordPress Editor keeps access', user_can( $u['siteEd'], 'edit_post', $team['B'] ) );
$t->ok( 'editing off: a site admin keeps access', user_can( $u['admin'], 'edit_post', $team['B'] ) );

// Switches that are on by default must stay off once turned off.
delete_post_meta( $team['A'], 'clanbite_team_accept_challenges' );
$t->ok( 'a team that never chose accepts challenges', $teams_ext->get_team( $team['A'] )->get_accept_challenges() );
$form( 'capA', $team['A'], array( 'team_accept_challenges' => '0' ) );
$t->ok( 'turning challenges off on the manage screen sticks', ! $teams_ext->get_team( $team['A'] )->get_accept_challenges() && ! $teams_ext->get_team_options( $team['A'] )['accept_challenges'] );
$form( 'capA', $team['A'], array( 'team_accept_challenges' => '1' ) );
$t->ok( 'and they can be turned back on', $teams_ext->get_team( $team['A'] )->get_accept_challenges() );
$t->ok( 'a player who never chose accepts invitations', $teams_ext->user_accepts_team_invites( $u['invitee'] ) );
$teams_ext->save_player_team_preferences( array( 'open_to_team_invites' => '0' ), array(), array(), $u['invitee'] );
$t->ok( 'turning invitations off in player settings sticks', ! $teams_ext->user_accepts_team_invites( $u['invitee'] ) );
$teams_ext->save_player_team_preferences( array( 'open_to_team_invites' => '1' ), array(), array(), $u['invitee'] );
$t->ok( 'and they can be turned back on in settings', $teams_ext->user_accepts_team_invites( $u['invitee'] ) );

// The manage screen's tabs and invite box need this script, which no block.json names.
$t->ok( 'the manage screen script has been built', is_readable( clanbite()->path . 'build/teams/team-manage-form/view.js' ), 'run npm run build:production' );

// Inviting players after the team exists.
if ( ! \Kernowdev\Clanbite\Extensions\Teams\Team_Invites::notifications_available() ) {
	echo "SKIP invitations (Notifications extension is off)\n";
} else {
	$data   = '\Kernowdev\Clanbite\Extensions\Notification\Notification_Data_Access';
	$call   = static function ( string $who, string $method, int $team_id, string $route, array $params ) use ( &$u ) {
		wp_set_current_user( '' === $who ? 0 : $u[ $who ] );
		$request = new WP_REST_Request( $method, "/clanbite/v1/teams/{$team_id}/{$route}" );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_do_request( $request );
	};
	$find   = static fn( string $who, int $team_id, string $q ) => $call( $who, 'GET', $team_id, 'invite-search', array( 'q' => $q ) );
	$found  = static fn( $response ): array => array_map( 'intval', wp_list_pluck( (array) $response->get_data(), 'id' ) );
	$invite = static fn( string $who, int $team_id, string $whom ): int => $call( $who, 'POST', $team_id, 'invites', array( 'user_id' => $u[ $whom ] ) )->get_status();
	// Answer through the same call the notification buttons use.
	$answer = static function ( string $who, int $team_id, string $action ) use ( &$u, $data ) {
		global $wpdb;
		wp_set_current_user( $u[ $who ] );
		$table = \Kernowdev\Clanbite\Extensions\Notification\Notification_Schema::table_name();
		$id    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE user_id = %d AND type = 'team_invite' AND object_id = %d ORDER BY id DESC LIMIT 1", $u[ $who ], $team_id ) );
		return $data::execute_action( $id, $u[ $who ], $action );
	};
	update_post_meta( $team['B'], 'clanbite_team_allow_frontend_edit', 1 );

	foreach ( array( 'editorA', 'capB', 'invitee' ) as $who ) {
		$t->ok( "{$who} cannot search or invite for team A", 403 === $find( $who, $team['A'], $run )->get_status() && 403 === $invite( $who, $team['A'], 'player' ) );
	}
	$t->ok( 'a signed-out visitor cannot invite', 401 === $invite( '', $team['A'], 'player' ) );

	// The invite checks start with the player off team A. Do it here: the team ranks section, which also removes them, only runs with Ranks.
	$form( 'capA', $team['A'], array( 'member_roles' => array( $u['capA'] => 'admin', $u['editorA'] => 'editor' ) ) );

	$ids = $found( $find( 'capA', $team['A'], $run ) );
	$t->ok( 'the captain finds a player who is not on the roster', in_array( $u['player'], $ids, true ) );
	$t->ok( 'the search leaves out the roster and the captain', ! array_intersect( $ids, array( $u['capA'], $u['editorA'] ) ) );
	$t->ok( 'a captain cannot search by email address', array() === $found( $find( 'capA', $team['A'], "{$run}-player@example.invalid" ) ) );
	$t->ok( 'a wildcard alone searches nothing', array() === $found( $find( 'capA', $team['A'], '**' ) ) );

	$t->ok( 'the captain invites the player', 201 === $invite( 'capA', $team['A'], 'player' ) );
	$t->ok( 'inviting does not add the player', null === $role( 'A', 'player' ) );
	$t->ok( 'a second invitation is refused while one is waiting', 400 === $invite( 'capA', $team['A'], 'player' ) );
	$t->ok( 'an invited player leaves the search', ! in_array( $u['player'], $found( $find( 'capA', $team['A'], $run ) ), true ) );
	$t->ok( 'someone on the roster cannot be invited', 400 === $invite( 'capA', $team['A'], 'editorA' ) );
	$answer( 'player', $team['A'], 'decline' );
	$t->ok( 'declining does not add the player', null === $role( 'A', 'player' ) );
	$t->ok( 'the captain can invite again after a decline', 201 === $invite( 'capA', $team['A'], 'player' ) );
	$answer( 'player', $team['A'], 'accept' );
	$t->ok( 'accepting adds the player as a member', 'member' === $role( 'A', 'player' ) );

	// The original scenario: move the player from team A to team B.
	$form( 'capA', $team['A'], array( 'member_roles' => array( $u['capA'] => 'admin', $u['editorA'] => 'editor' ) ) );
	$t->ok( 'captain B invites the player captain A removed', 201 === $invite( 'capB', $team['B'], 'player' ) );
	$answer( 'player', $team['B'], 'accept' );
	$t->ok( 'the player has moved from A to B', null === $role( 'A', 'player' ) && 'member' === $role( 'B', 'player' ) );

	$teams_ext->save_player_team_preferences( array( 'open_to_team_invites' => '0' ), array(), array(), $u['player'] );
	$t->ok( 'a player who turned invitations off is not listed or invited', ! in_array( $u['player'], $found( $find( 'capA', $team['A'], $run ) ), true ) && 400 === $invite( 'capA', $team['A'], 'player' ) );
	delete_user_meta( $u['player'], 'clanbite_open_to_team_invites' );

	// An invitation stops working when its sender or the team may no longer invite.
	$invite( 'capA', $team['A'], 'player' );
	update_post_meta( $team['A'], 'clanbite_team_allow_invites', 0 );
	$t->ok( 'invitations off: the captain cannot invite', 403 === $invite( 'capA', $team['A'], 'siteEd' ) );
	$result = $answer( 'player', $team['A'], 'accept' );
	$t->ok( 'invitations off: a waiting invitation no longer works', is_wp_error( $result ) && null === $role( 'A', 'player' ) );
	update_post_meta( $team['A'], 'clanbite_team_allow_invites', 1 );

	$teams_ext->persist_team_roles_map( $team['A'], array( $u['capA'] => 'admin', $u['editorA'] => 'admin' ) );
	$invite( 'editorA', $team['A'], 'player' );
	$teams_ext->persist_team_roles_map( $team['A'], array( $u['capA'] => 'admin', $u['editorA'] => 'editor' ) );
	$result = $answer( 'player', $team['A'], 'accept' );
	$t->ok( 'a demoted sender: the invitation no longer works', is_wp_error( $result ) && null === $role( 'A', 'player' ) );
	$t->ok( 'and does not block a new one', 201 === $invite( 'capA', $team['A'], 'player' ) );

	// One team per player: the invitation waits until they leave the other team.
	$single = static fn( $value ) => array_merge( (array) $value, array( 'player_team_membership' => 'single' ) );
	add_filter( 'option_clanbite_teams_settings', $single );
	add_filter( 'default_option_clanbite_teams_settings', $single );
	$result = $answer( 'player', $team['A'], 'accept' );
	$t->ok( 'one-team sites: a player already on a team cannot accept another', is_wp_error( $result ) && 'clanbite_invite_team_limit' === $result->get_error_code() && null === $role( 'A', 'player' ) );
	$teams_ext->persist_team_roles_map( $team['B'], array( $u['capB'] => 'admin' ) );
	$answer( 'player', $team['A'], 'accept' );
	$t->ok( 'one-team sites: they can accept after leaving', 'member' === $role( 'A', 'player' ) );
	remove_filter( 'option_clanbite_teams_settings', $single );
	remove_filter( 'default_option_clanbite_teams_settings', $single );

	$one = static fn() => 1;
	add_filter( 'clanbite_team_invite_rate_limit', $one );
	delete_transient( 'clanbite_team_invites_' . $u['capA'] );
	$first  = $invite( 'capA', $team['A'], 'siteEd' );
	$second = $invite( 'capA', $team['A'], 'admin' );
	$t->ok( 'the hourly limit applies', 201 === $first && 429 === $second, array( $first, $second ) );
	remove_filter( 'clanbite_team_invite_rate_limit', $one );

	// The Roster tab.
	$roster = static function ( int $team_id ) use ( $teams_ext ): string {
		ob_start();
		$teams_ext->render_team_manage_section_roster( $team_id, $teams_ext );
		return (string) ob_get_clean();
	};
	wp_set_current_user( $u['capA'] );
	$html = $roster( $team['A'] );
	$t->ok( 'the Roster tab shows the invite box and who is waiting', false !== strpos( $html, 'clanbite-team-invite__search' ) && false !== strpos( $html, "{$run}-siteEd" ) );
	$box = substr( $html, (int) strpos( $html, 'class="clanbite-team-invite"' ) );
	$t->ok( 'the invite box adds nothing to the manage form submission', false === strpos( $box, ' name="' ) && false === strpos( $box, 'type="submit"' ) );
	update_post_meta( $team['A'], 'clanbite_team_allow_invites', 0 );
	$html = $roster( $team['A'] );
	$t->ok( 'with invitations off the tab says so instead', false === strpos( $html, 'clanbite-team-invite__search' ) && false !== strpos( $html, 'turned off' ) );
	update_post_meta( $team['A'], 'clanbite_team_allow_invites', 1 );
	foreach ( array( 'capA', 'editorA' ) as $who ) {
		delete_transient( 'clanbite_team_invites_' . $u[ $who ] );
	}
	delete_transient( 'clanbite_team_invites_' . $u['capB'] );
}

$finished = true;
exit( $t->finish() );
