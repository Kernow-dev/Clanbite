<?php
/**
 * Privacy of events and matches. Run: `php tests/php/content-privacy.php` (development database only).
 *
 * Covers Clanbite's routes and WordPress' own (REST, search, pages, sitemaps, embeds). Group checks need Social Kit
 * with Groups turned on; they are skipped otherwise.
 *
 * @package clanbite
 */

define( 'DISABLE_WP_CRON', true );
require __DIR__ . '/harness.php';

use Kernowdev\Clanbite\Extensions\Matches\Match_Privacy;

add_filter( 'pre_wp_mail', '__return_true' );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'blocked', 'Blocked in tests.' ) );
do_action( 'rest_api_init' );

global $wpdb;
$last_ids = array();
foreach ( $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%' . $wpdb->esc_like( 'clanbite' ) . '%' ) ) as $table ) {
	if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$table}` LIKE 'id'" ) ) {
		$last_ids[ $table ] = (int) $wpdb->get_var( "SELECT MAX(id) FROM `{$table}`" );
	}
}

$u     = array();
$posts = array();
$run   = 'zzpriv' . wp_rand( 10000, 99999 );
$t->cleanup(
	static function () use ( &$u, &$posts, $last_ids, $wpdb ): void {
		wp_set_current_user( 0 );
		foreach ( $posts as $id ) {
			wp_delete_post( $id, true );
			$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $id ) );
		}
		foreach ( $u as $id ) {
			wp_delete_user( $id );
		}
		foreach ( $last_ids as $table => $last ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE id > %d", $last ) );
		}
	}
);
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
	'owner'     => 'subscriber',
	'groupmod'  => 'subscriber',
	'member'    => 'subscriber',
	'outsider'  => 'subscriber',
	'banned'    => 'subscriber',
	'wpauthor'  => 'author',
	'wpeditor'  => 'editor',
	'siteadmin' => 'administrator',
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
$id_of = static fn( string $who ): int => '' === $who ? 0 : $u[ $who ];
$call  = static function ( string $who, string $method, string $route, array $params = array() ) use ( $id_of ) {
	wp_set_current_user( $id_of( $who ) );
	$request = new WP_REST_Request( $method, $route );
	if ( 'GET' === $method ) {
		$request->set_query_params( $params );
	} else {
		$request->set_body_params( $params );
	}
	return rest_do_request( $request );
};
$name  = static fn( string $who ): string => '' === $who ? 'a signed-out visitor' : $who;
$json  = static fn( $response ): string => (string) wp_json_encode( rest_get_server()->response_to_data( $response, false ) );
$make_post  = static function ( string $type, string $title, array $meta, string $body = '' ) use ( &$posts, &$u ): int {
	$id = (int) wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $body,
			'post_author'  => $u['owner'],
		)
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	$posts[] = $id;
	return $id;
};

// WordPress' own event routes.
if ( post_type_exists( 'clanbite_event' ) ) {
	$only_me = $make_post(
		'clanbite_event',
		"{$run} PRIVATEEVENT",
		array(
			'clanbite_event_scope'      => 'player',
			'clanbite_event_visibility' => 'only_me',
		),
		'PRIVATEBODY'
	);
	foreach ( array( '', 'outsider', 'wpauthor' ) as $who ) {
		$expected = '' === $who ? 401 : 403;
		foreach ( array( "/wp/v2/clanbite_event/{$only_me}", '/wp/v2/clanbite_event', "/WP/V2/Clanbite_Event/{$only_me}" ) as $route ) {
			$response = $call( $who, 'GET', $route );
			$t->ok( $name( $who ) . " cannot read events through {$route}", $expected === $response->get_status() && false === strpos( $json( $response ), 'PRIVATE' ), $response->get_status() );
		}
	}
	$t->ok( 'an Author cannot create events through the WordPress route', 403 === $call( 'wpauthor', 'POST', '/wp/v2/clanbite_event', array( 'title' => 'x' ) )->get_status() );
	foreach ( array( 'wpeditor', 'siteadmin' ) as $who ) {
		$t->ok( "{$who} keeps the WordPress event route", 200 === $call( $who, 'GET', "/wp/v2/clanbite_event/{$only_me}" )->get_status() );
	}
} else {
	echo "SKIP events (Events is off)\n";
}

// Events in groups.
$groups_class = '\Kernowdev\ClanbiteSocialKit\Groups\Group_Data_Access';
if ( post_type_exists( 'clanbite_event' ) && post_type_exists( 'clanbite_group' ) && class_exists( $groups_class ) ) {
	$make_group = static function ( string $mode ) use ( &$posts, &$u, $run, $groups_class ): int {
		wp_set_current_user( $u['owner'] );
		$id      = (int) $groups_class::create_group(
			array(
				'name'       => "{$run} {$mode} group",
				'creator_id' => $u['owner'],
				'join_mode'  => $mode,
			)
		);
		$posts[] = $id;
		$groups_class::set_member_role( $id, $u['groupmod'], 'editor' );
		$groups_class::set_member_role( $id, $u['member'], 'member' );
		$groups_class::set_member_role( $id, $u['banned'], 'banned' );
		return $id;
	};
	$event      = static function ( int $group, string $title, string $visibility, string $attendees = 'hidden', string $who = 'owner' ) use ( $call, &$posts ): int {
		$response = $call(
			$who,
			'POST',
			'/clanbite/v1/event-posts',
			array(
				'scope'                => 'group',
				'group_id'             => $group,
				'title'                => $title,
				'mode'                 => 'virtual',
				'virtual_url'          => 'https://meet.example.invalid/private',
				'starts_at'            => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
				'visibility'           => $visibility,
				'attendees_visibility' => $attendees,
			)
		);
		$id       = (int) ( $response->get_data()['id'] ?? 0 );
		if ( $id ) {
			$posts[] = $id;
		}
		return $id;
	};
	$read       = static fn( string $who, int $id ): int => $call( $who, 'GET', "/clanbite/v1/event-posts/{$id}" )->get_status();
	$listing    = static fn( string $who, int $group, array $extra = array() ) => $call( $who, 'GET', '/clanbite/v1/event-posts', array( 'group_id' => $group ) + $extra )->get_data();
	$rsvp       = static fn( string $who, int $id ): int => $call( $who, 'POST', "/clanbite/v1/events/clanbite_event/{$id}/rsvp", array( 'status' => 'accepted' ) )->get_status();
	$attendees  = static fn( string $who, int $id ) => $call( $who, 'GET', "/clanbite/v1/events/clanbite_event/{$id}/attendees" );

	foreach ( array( 'request', 'invite' ) as $mode ) {
		$group  = $make_group( $mode );
		$open_e = $event( $group, "{$run} marked public", 'public', 'public' );
		$all_e  = $event( $group, "{$run} signed in", 'members' );
		$t->ok( "{$mode}: the test events were created", $open_e > 0 && $all_e > 0 );
		$rsvp( 'member', $open_e );
		foreach ( array( '', 'outsider', 'banned', 'wpauthor' ) as $who ) {
			$expected = 403;
			$t->ok( "{$mode}: " . $name( $who ) . ' cannot open events marked public or signed-in', $expected === $read( $who, $open_e ) && $expected === $read( $who, $all_e ) );
			$data = $listing( $who, $group );
			$t->ok( "{$mode}: " . $name( $who ) . ' gets an empty list with a total of 0', array() === $data['items'] && 0 === $data['total'], $data['total'] );
			$response = $attendees( $who, $open_e );
			$t->ok( "{$mode}: " . $name( $who ) . ' gets no attendee names', 200 !== $response->get_status() && false === strpos( $json( $response ), "{$run}-member" ) );
			if ( '' !== $who ) {
				$t->ok( "{$mode}: {$who} cannot RSVP", 403 === $rsvp( $who, $open_e ) );
			}
		}
		foreach ( array( 'owner', 'groupmod', 'member', 'siteadmin' ) as $who ) {
			$t->ok( "{$mode}: {$who} still sees the events", 200 === $read( $who, $open_e ) && 2 === $listing( $who, $group )['total'] );
		}
		$t->ok( "{$mode}: a member can RSVP and see public attendees", 200 === $rsvp( 'member', $all_e ) && 200 === $attendees( 'member', $open_e )->get_status() );
		$admins_e = $event( $group, "{$run} hiddenword", 'team_admins' );
		$t->ok( "{$mode}: an event's own setting can still be stricter than the group's", 403 === $read( 'member', $admins_e ) && 200 === $read( 'groupmod', $admins_e ) );
		$data = $listing( 'member', $group, array( 'search' => 'hiddenword' ) );
		$t->ok( "{$mode}: searching does not count a hidden event", array() === $data['items'] && 0 === $data['total'], $data['total'] );
		$first  = $listing( 'member', $group, array( 'per_page' => 1 ) );
		$second = $listing(
			'member',
			$group,
			array(
				'per_page' => 1,
				'page'     => 2,
			)
		);
		$t->ok( "{$mode}: pages and totals cover only visible events", 2 === $first['total'] && 1 === count( $first['items'] ) && 1 === count( $second['items'] ) && $first['items'][0]['id'] !== $second['items'][0]['id'] );
	}

	$group  = $make_group( 'open' );
	$public = $event( $group, "{$run} open public", 'public', 'public' );
	$t->ok( 'open: a signed-out visitor still sees a public event', 200 === $read( '', $public ) && 1 === $listing( '', $group )['total'] );
	$t->ok( 'open: another player can RSVP', 200 === $rsvp( 'outsider', $public ) );
	$t->ok( 'open: a banned player cannot see it or RSVP', 403 === $read( 'banned', $public ) && 403 === $rsvp( 'banned', $public ) );
	update_post_meta( $group, 'clanbite_group_join_mode', 'invite' );
	$t->ok( 'making a group private also closes its existing public events', 403 === $read( '', $public ) );
	update_post_meta( $group, 'clanbite_group_join_mode', 'open' );
	$by_mod = $event( $group, "{$run} by a moderator", 'public', 'hidden', 'groupmod' );
	$groups_class::set_member_role( $group, $u['groupmod'], 'banned' );
	$t->ok( 'a banned former editor cannot read even an event they created', 403 === $read( 'groupmod', $by_mod ) );
} else {
	echo "SKIP group events (needs Events and Social Kit groups)\n";
}

// Matches.
$matches = clanbite()->extensions->get( 'clanbite_matches' );
if ( post_type_exists( 'clanbite_match' ) && $matches ) {
	$team   = $make_post( 'clanbite_team', "{$run} team", array( 'clanbite_team_member_roles' => array( $u['member'] => 'member' ) ) );
	$secret = $make_post(
		'clanbite_match',
		"{$run} SECRETSCRIM",
		array(
			'clanbite_match_visibility'   => 'team_members',
			'clanbite_match_home_team_id' => $team,
		),
		'SECRETTACTICS'
	);
	$admins = $make_post( 'clanbite_match', "{$run} SECRETADMIN", array( 'clanbite_match_visibility' => 'team_admins' ), 'SECRETTACTICS' );
	$shown  = $make_post( 'clanbite_match', "{$run} OPENMATCH", array( 'clanbite_match_visibility' => 'public' ) );
	$ids    = static fn( $response ): array => array_map( 'intval', wp_list_pluck( (array) $response->get_data(), 'id' ) );
	$seen   = static function ( string $who, int $id ) use ( $call, $ids, $run ): array {
		return array(
			'single'  => 200 === $call( $who, 'GET', "/wp/v2/clanbite_match/{$id}" )->get_status(),
			'list'    => in_array( $id, $ids( $call( $who, 'GET', '/wp/v2/clanbite_match', array( 'search' => $run, 'per_page' => 100 ) ) ), true ),
			'include' => in_array( $id, $ids( $call( $who, 'GET', '/wp/v2/clanbite_match', array( 'include' => array( $id ) ) ) ), true ),
			'search'  => in_array(
				$id,
				$ids(
					$call(
						$who,
						'GET',
						'/wp/v2/search',
						array(
							'search'   => $run,
							'per_page' => 100,
						)
					)
				),
				true
			),
		);
	};
	foreach ( array( '', 'outsider' ) as $who ) {
		$t->ok( $name( $who ) . ' finds no restricted match through WordPress REST or search', array() === array_filter( $seen( $who, $secret ) ) && array() === array_filter( $seen( $who, $admins ) ), array( $seen( $who, $secret ), $seen( $who, $admins ) ) );
		$t->ok( $name( $who ) . ' still sees a public match every way', 4 === count( array_filter( $seen( $who, $shown ) ) ), $seen( $who, $shown ) );
	}
	$t->ok( 'a team member sees the team-only match, not the admins-only one', 4 === count( array_filter( $seen( 'member', $secret ) ) ) && array() === array_filter( $seen( 'member', $admins ) ), array( $seen( 'member', $secret ), $seen( 'member', $admins ) ) );
	$t->ok( 'a WordPress Editor sees everything', 4 === count( array_filter( $seen( 'wpeditor', $admins ) ) ) );

	$privacy = new Match_Privacy( $matches );
	global $wp_query, $wp_the_query;
	$kept = array( $wp_query, $wp_the_query );
	$page = static function ( string $who, array $vars ) use ( $id_of, $privacy ) {
		global $wp_query, $wp_the_query;
		wp_set_current_user( $id_of( $who ) );
		$wp_the_query = new WP_Query();
		$wp_query     = $wp_the_query;
		$wp_query->query( $vars );
		$privacy->guard_single_page();
		return $wp_query;
	};
	$result = $page(
		'',
		array(
			'p'         => $secret,
			'post_type' => 'clanbite_match',
		)
	);
	$t->ok( 'the match page is not found for a signed-out visitor', $result->is_404() && 0 === $result->post_count );
	$result = $page(
		'member',
		array(
			'p'         => $secret,
			'post_type' => 'clanbite_match',
		)
	);
	$t->ok( 'and opens for a team member', ! $result->is_404() && 1 === $result->post_count );
	foreach (
		array(
			'archive' => array(
				'post_type'      => 'clanbite_match',
				'posts_per_page' => 100,
			),
			'search'  => array(
				's'              => $run,
				'posts_per_page' => 100,
			),
			'feed'    => array(
				'feed'      => 'rss2',
				'post_type' => 'clanbite_match',
			),
		) as $label => $vars
	) {
		$found = array_map( 'intval', wp_list_pluck( $page( '', $vars )->posts, 'ID' ) );
		$t->ok( "the front-end {$label} leaves restricted matches out and keeps public ones", ! array_intersect( $found, array( $secret, $admins ) ) && in_array( $shown, $found, true ) );
	}
	list( $wp_query, $wp_the_query ) = $kept;

	wp_set_current_user( 0 );
	$args = apply_filters( 'wp_sitemaps_posts_query_args', array( 'post_type' => 'clanbite_match' ), 'clanbite_match' );
	$t->ok( 'sitemaps leave restricted matches out', ! array_diff( array( $secret, $admins ), (array) ( $args['post__not_in'] ?? array() ) ) && ! in_array( $shown, (array) ( $args['post__not_in'] ?? array() ), true ) );
	$t->ok( 'link previews are given for public matches only', false === get_oembed_response_data( $secret, 600 ) && is_array( get_oembed_response_data( $shown, 600 ) ) );
} else {
	echo "SKIP matches (Matches is off)\n";
}

$finished = true;
exit( $t->finish() );
