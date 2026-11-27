<?php
/**
 * Live Preview, part 2: a busy sample community (players, teams, matches, events and things waiting for the visitor).
 *
 * Everything here is made up. Dates are set around the day the preview is opened. An earlier step has already unzipped the
 * artwork into /tmp/clanbite-demo-art/.
 *
 * @package clanbite
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

wp_set_current_user( 1 );

$image = static function ( string $name, string $title ): int {
	$source = "/tmp/clanbite-demo-art/{$name}.webp";
	if ( ! is_readable( $source ) ) {
		return 0;
	}
	$uploads = wp_upload_dir();
	$file    = trailingslashit( $uploads['path'] ) . "clanbite-demo-{$name}.webp";
	copy( $source, $file );
	$id = wp_insert_attachment(
		array(
			'post_title'     => $title,
			'post_mime_type' => 'image/webp',
			'post_status'    => 'inherit',
		),
		$file
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

	return (int) $id;
};

// ---- Players ----
$people  = array(
	'admin'    => array( 'Demo Admin', 'Runs the place. Click anything.' ),
	'ember'    => array( 'Ember', 'First through the door.' ),
	'nightowl' => array( 'NightOwl', 'Calls the shots after midnight.' ),
	'pixel'    => array( 'Pixel', 'Pirate captain. Clutch or bust.' ),
	'rook'     => array( 'Rook', 'Holds the site, always.' ),
	'sable'    => array( 'Sable', 'You will not hear Sable coming.' ),
	'vex'      => array( 'Vex', 'Wolf pack leader. Recruiting now.' ),
);
$players = array();
foreach ( $people as $login => list( $name, $tagline ) ) {
	if ( 'admin' === $login ) {
		$user_id = 1;
		wp_update_user(
			array(
				'ID'           => 1,
				'display_name' => $name,
			)
		);
	} else {
		$user_id = (int) wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => wp_generate_password( 24 ),
				'user_email'   => $login . '@example.com',
				'display_name' => $name,
				'role'         => 'subscriber',
			)
		);
	}
	$players[ $login ] = $user_id;
	update_user_meta( $user_id, 'clanbite_player_tagline', $tagline );
	update_user_meta( $user_id, 'clanbite_player_avatar_id', $image( "avatar-{$login}", "{$name} avatar" ) );
	update_user_meta( $user_id, 'clanbite_player_cover_id', $image( "cover-{$login}", "{$name} cover" ) );
}

// ---- Teams ----
$sample_teams = array(
	'night-owls'    => array( 'Night Owls', 'We play after dark.', 'GB', 'OWL', 24, 6, 2, array( 'admin', 'nightowl', 'ember', 'sable' ) ),
	'pixel-pirates' => array( 'Pixel Pirates', 'Every frame counts.', 'US', 'PXL', 19, 11, 1, array( 'pixel', 'rook', 'admin' ) ),
	'iron-wolves'   => array( 'Iron Wolves', 'Hunt as a pack.', 'DE', 'IRW', 17, 12, 3, array( 'vex', 'rook', 'sable' ) ),
);
$teams_extension = clanbite()->extensions->get( 'clanbite_teams' );
$teams           = array();
foreach ( $sample_teams as $slug => list( $name, $motto, $country, $code, $wins, $losses, $draws, $members ) ) {
	$team_id        = (int) wp_insert_post(
		array(
			'post_type'    => 'clanbite_team',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_name'    => $slug,
			'post_content' => "{$motto} A sample team: open its tabs to see members, matches and events.",
			'post_author'  => $players[ $members[0] ],
		)
	);
	$teams[ $slug ] = $team_id;
	foreach ( compact( 'motto', 'country', 'code', 'wins', 'losses', 'draws' ) as $key => $value ) {
		update_post_meta( $team_id, 'clanbite_team_' . $key, $value );
	}
	update_post_meta( $team_id, 'clanbite_team_avatar_id', $image( "team-{$slug}", "{$name} badge" ) );
	update_post_meta( $team_id, 'clanbite_team_cover_id', $image( "banner-{$slug}", "{$name} banner" ) );
	$roster = array();
	foreach ( $members as $position => $login ) {
		$roster[ $players[ $login ] ] = 0 === $position ? 'admin' : 'member';
	}
	// The visitor is the site admin and can manage the Night Owls.
	if ( 'night-owls' === $slug ) {
		$roster[ $players['nightowl'] ] = 'admin';
	}
	if ( $teams_extension ) {
		$teams_extension->persist_team_roles_map( $team_id, $roster );
	}
}

// ---- Matches ----
$day      = static fn( int $days, int $hour ): string => gmdate( 'Y-m-d H:i:s', strtotime( gmdate( 'Y-m-d' ) ) + $days * DAY_IN_SECONDS + $hour * HOUR_IN_SECONDS );
$schedule = array(
	// home, away, days from today, status, home score, away score, competition.
	array( 'night-owls', 'pixel-pirates', -18, 'finished', 16, 9, 'Weekly league, round 1' ),
	array( 'iron-wolves', 'pixel-pirates', -15, 'finished', 13, 16, 'Weekly league, round 2' ),
	array( 'night-owls', 'iron-wolves', -11, 'finished', 14, 16, 'Weekly league, round 2' ),
	array( 'pixel-pirates', 'night-owls', -8, 'finished', 12, 16, 'Weekly league, round 3' ),
	array( 'iron-wolves', 'night-owls', -4, 'finished', 9, 16, 'Friendly' ),
	array( 'pixel-pirates', 'iron-wolves', -2, 'finished', 16, 14, 'Weekly league, round 4' ),
	array( 'night-owls', 'iron-wolves', 0, 'live', 11, 9, 'Cup quarter-final' ),
	array( 'pixel-pirates', 'night-owls', 3, 'scheduled', 0, 0, 'Weekly league, round 5' ),
	array( 'iron-wolves', 'pixel-pirates', 6, 'scheduled', 0, 0, 'Friendly' ),
	array( 'night-owls', 'pixel-pirates', 10, 'scheduled', 0, 0, 'Cup semi-final' ),
);
$matches  = array();
foreach ( $schedule as list( $home, $away, $offset, $status, $home_score, $away_score, $venue ) ) {
	$match_id  = (int) wp_insert_post(
		array(
			'post_type'   => 'clanbite_match',
			'post_status' => 'publish',
			'post_title'  => $sample_teams[ $home ][0] . ' vs ' . $sample_teams[ $away ][0],
		)
	);
	$matches[] = $match_id;
	$meta      = array(
		'home_team_id' => $teams[ $home ],
		'away_team_id' => $teams[ $away ],
		'scheduled_at' => $day( $offset, 20 ),
		'status'       => $status,
		'home_score'   => $home_score,
		'away_score'   => $away_score,
		'venue'        => $venue,
		'visibility'   => 'public',
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $match_id, 'clanbite_match_' . $key, $value );
	}
}

// ---- Events: enough to fill the calendar ----
$events = array(
	array( 'night-owls', 'Scrim night', -12, 20 ),
	array( 'night-owls', 'Strategy review', -9, 19 ),
	array( 'night-owls', 'Practice: map pool', -6, 20 ),
	array( 'night-owls', 'Community game night', -3, 18 ),
	array( 'night-owls', 'Pre-match warm-up', 0, 19 ),
	array( 'night-owls', 'VOD review', 1, 19 ),
	array( 'night-owls', 'Scrim vs guests', 2, 20 ),
	array( 'night-owls', 'Practice night', 5, 20 ),
	array( 'night-owls', 'Team photo (avatars on!)', 7, 18 ),
	array( 'night-owls', 'Semi-final prep', 9, 20 ),
	array( 'night-owls', 'Cup watch party', 12, 18 ),
	array( 'pixel-pirates', 'New player trials', 4, 19 ),
	array( 'iron-wolves', 'Open tryouts', 5, 18 ),
);
foreach ( $events as list( $team, $title, $offset, $hour ) ) {
	$event_id = (int) wp_insert_post(
		array(
			'post_type'    => 'clanbite_event',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => 'A sample event. Open it to see the details and answer the invitation.',
			'post_author'  => 1,
		)
	);
	$meta     = array(
		'scope'       => 'team',
		'team_id'     => $teams[ $team ],
		'starts_at'   => $day( $offset, $hour ),
		'ends_at'     => $day( $offset, $hour + 2 ),
		'mode'        => 'virtual',
		'virtual_url' => 'https://example.com/voice',
		'visibility'  => 'public',
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $event_id, 'clanbite_event_' . $key, $value );
	}
}

// ---- Things waiting for the visitor ----
if ( function_exists( 'clanbite_notify' ) ) {
	clanbite_notify(
		1,
		'demo_match',
		'The Night Owls are playing now',
		array(
			'message'  => 'Cup quarter-final against the Iron Wolves.',
			'url'      => get_permalink( $matches[6] ),
			'actor_id' => $players['nightowl'],
		)
	);
}
if ( $teams_extension && method_exists( $teams_extension, 'get_invites' ) ) {
	// A real invitation: the visitor can accept or decline it from their notifications.
	$teams_extension->get_invites()->invite( $teams['iron-wolves'], 1, $players['vex'] );
}

update_option(
	'clanbite_demo',
	array(
		'teams'   => $teams,
		'matches' => $matches,
		'live'    => $matches[6],
		'next'    => $matches[7],
	),
	false
);
