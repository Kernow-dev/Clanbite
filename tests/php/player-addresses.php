<?php
/**
 * The player, team and group address slugs, and the player "Enable profiles" switch. Run `php tests/php/player-addresses.php` against a development
 * database. The site's settings are never written: they are supplied through a filter.
 *
 * @package clanbite
 */

require __DIR__ . '/harness.php';

$settings = array(
	'enable_profiles' => true,
	'player_url_slug' => '',
);
$supply   = static function () use ( &$settings ) {
	return $settings;
};
add_filter( 'pre_option_clanbite_players_settings', $supply );
$user_id = wp_insert_user(
	array(
		'user_login' => 'zz_addr_' . wp_generate_password( 6, false ),
		'user_pass'  => wp_generate_password(),
		'user_email' => 'zz_addr_' . wp_generate_password( 6, false ) . '@example.test',
	)
);
$t->cleanup(
	static function () use ( $user_id ): void {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );
	}
);
$nice    = get_userdata( $user_id )->user_nicename;
$players = clanbite()->extensions->get( 'clanbite_players' );
$visit   = static function ( string $path ): void {
	$_SERVER['REQUEST_URI'] = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) . ltrim( $path, '/' );
};
$before  = $_SERVER['REQUEST_URI'] ?? null;

// ---- Default address ----
$t->ok( 'the default slug is players', 'players' === clanbite_players_get_slug() && home_url( '/players/' ) === clanbite_players_url() );
$t->ok( 'profile links use it', home_url( "/players/{$nice}/" ) === clanbite_get_player_profile_url( $user_id ) );
$visit( "players/{$nice}/events/" );
$t->ok( 'the owner of an address is found', $user_id === clanbite_player_user_id_from_canonical_request_path() );

// ---- A chosen slug ----
$settings['player_url_slug'] = 'members';
$t->ok( 'a chosen slug is used for links', home_url( "/members/{$nice}/" ) === clanbite_get_player_profile_url( $user_id ) && home_url( '/members/settings/' ) === clanbite_players_url( 'settings/' ) );
$t->ok( 'request paths read as player routes', 'players/jane/events' === clanbite_players_route_path( 'members/jane/events' ) && 'players' === clanbite_players_route_path( 'members' ) );
$t->ok( 'the old word is no longer a player route, and look-alikes never were', ! str_starts_with( clanbite_players_route_path( 'players/jane' ), 'players' ) && 'membership/jane' === clanbite_players_route_path( 'membership/jane' ) );
$visit( "members/{$nice}/" );
$t->ok( 'the profile owner is found under the new slug', $user_id === clanbite_player_user_id_from_canonical_request_path() );
$visit( "players/{$nice}/" );
$t->ok( 'and not under the old one', 0 === clanbite_player_user_id_from_canonical_request_path() );

global $wp_rewrite;
$kept                        = $wp_rewrite->extra_rules_top;
$wp_rewrite->extra_rules_top = array();
$players->register_profile_endpoints();
$rules                       = array_keys( $wp_rewrite->extra_rules_top );
$wp_rewrite->extra_rules_top = $kept;
$t->ok( 'every player rewrite rule starts with the slug', 8 === count( $rules ) && array() === array_filter( $rules, static fn( $r ) => ! str_starts_with( $r, '^members' ) ), $rules );
$match = static function ( string $url ) use ( $rules ): bool {
	foreach ( $rules as $rule ) {
		if ( preg_match( "#{$rule}#", $url ) ) {
			return true;
		}
	}

	return false;
};
$t->ok( 'the rules match the new addresses only', $match( 'members/jane/' ) && $match( 'members' ) && $match( 'members/settings/profile/profile-info/' ) && ! $match( 'players/jane/' ) && ! $match( 'membership/' ) );

add_filter( 'clanbite_players_slug', static fn( $s ) => 'members' === $s ? 'The Crew!' : $s );
$t->ok( 'whatever a filter returns is reduced to a safe slug', 'thecrew' === clanbite_players_get_slug() );
remove_all_filters( 'clanbite_players_slug' );

// ---- What may be chosen ----
foreach ( array( 'members', 'roster', 'the-crew', 'u2' ) as $good ) {
	$t->ok( "accepted: {$good}", '' === clanbite_players_slug_problem( $good ), clanbite_players_slug_problem( $good ) );
}
foreach ( array( 'wp-admin', 'wp-json', 'feed', 'page', 'author', 'settings', '123', '-x', 'x-', 'a b', 'ümlaut', str_repeat( 'a', 41 ) ) as $bad ) {
	$t->ok( "refused: {$bad}", '' !== clanbite_players_slug_problem( $bad ) );
}
$page = wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => 'ZZ taken address',
		'post_name'   => 'zz-taken-address',
	)
);
$t->cleanup( static fn() => wp_delete_post( $page, true ) );
$t->ok( 'refused: the address of an existing page', '' !== clanbite_players_slug_problem( 'zz-taken-address' ) );
register_post_type(
	'zz_addr_type',
	array(
		'public'  => true,
		'rewrite' => array( 'slug' => 'zz-library' ),
	)
);
$t->ok( 'refused: the address of a content type', '' !== clanbite_players_slug_problem( 'zz-library' ) );
unregister_post_type( 'zz_addr_type' );

$admin = new \Kernowdev\Clanbite\Extensions\Players\Admin();
$t->ok( 'a refused slug keeps the saved one and says why', 'members' === $admin->sanitize_player_url_slug( 'wp-admin' ) && 1 === count( $admin->get_save_warnings() ), $admin->get_save_warnings() );
$t->ok( 'a missing field keeps the saved one; empty or "players" means the default', 'members' === $admin->sanitize_player_url_slug( null ) && '' === $admin->sanitize_player_url_slug( '' ) && '' === $admin->sanitize_player_url_slug( 'Players' ) && 'roster' === $admin->sanitize_player_url_slug( '/Roster/ ' ) );

// ---- Remembering old addresses ----
$old_list = null;
add_filter(
	'pre_option_clanbite_players_previous_slugs',
	static function () use ( &$old_list ) {
		return null === $old_list ? array() : $old_list;
	}
);
add_filter(
	'pre_update_option_clanbite_players_previous_slugs',
	static function ( $value, $old ) use ( &$old_list ) {
		$old_list = $value;

		return $old;
	},
	10,
	2
);
$flush = false;
add_filter(
	'pre_update_option_clanbite_pending_rewrite_flush',
	static function ( $value, $old ) use ( &$flush ) {
		$flush = true;

		return $old;
	},
	10,
	2
);
\Kernowdev\Clanbite\Extensions\Players::on_settings_saved( array( 'player_url_slug' => '' ), array( 'player_url_slug' => 'members' ) );
$t->ok( 'changing the slug remembers the old one and asks for new permalinks', array( 'players' ) === $old_list && $flush );
\Kernowdev\Clanbite\Extensions\Players::on_settings_saved( array( 'player_url_slug' => 'members' ), array( 'player_url_slug' => '' ) );
$t->ok( 'changing back forgets the one now in use', array( 'members' ) === $old_list, $old_list );
$flush = false;
\Kernowdev\Clanbite\Extensions\Players::on_settings_saved( array( 'player_url_slug' => 'x1' ), array( 'player_url_slug' => 'x1' ) );
$t->ok( 'saving without a change does nothing', ! $flush );

// ---- Profiles switched off ----
$settings = array(
	'enable_profiles' => false,
	'player_url_slug' => 'members',
);
$closed   = static function ( string $path ) use ( $visit, $players ): bool {
	$visit( $path );

	return $players->is_closed_player_route();
};
wp_set_current_user( 0 );
$t->ok( 'there are no profile links', ! clanbite_players_profiles_enabled() && '' === clanbite_get_player_profile_url( $user_id ) );
$t->ok( 'signed out: the directory, profiles and every tab are closed', $closed( 'members/' ) && $closed( 'members/page/2/' ) && $closed( "members/{$nice}/" ) && $closed( "members/{$nice}/events/" ) && $closed( "members/{$nice}/notifications/" ) );
$t->ok( 'account settings and other pages are not closed', ! $closed( 'members/settings/' ) && ! $closed( 'members/settings/profile/profile-info/' ) && ! $closed( 'shop/' ) && ! $closed( '' ) );
wp_set_current_user( $user_id );
$t->ok( 'signed in: only the tool tabs open', ! $closed( "members/{$nice}/events/" ) && ! $closed( "members/{$nice}/notifications/" ) && $closed( "members/{$nice}/" ) && $closed( "members/{$nice}/friends/" ) && $closed( 'members/' ) && $closed( 'members/page/events/' ) );
$visit( "members/{$nice}/" );
$t->ok( 'a closed address becomes "not found" before it is looked up', array( 'error' => '404' ) === $players->filter_request_for_players_virtual_routes( array( 'author_name' => $nice ) ) );
$menu = array_column( clanbite_get_user_nav_menu_items( $user_id ), 'url', 'id' );
$t->ok( 'the user menu drops "My Profile" but keeps settings', ! isset( $menu['profile'] ) && home_url( '/members/settings/' ) === $menu['settings'], $menu );
if ( function_exists( 'clanbite_get_notifications_url' ) ) {
	$t->ok( 'the notifications link still works', home_url( "/members/{$nice}/notifications/" ) === clanbite_get_notifications_url( $user_id ), clanbite_get_notifications_url( $user_id ) );
}
if ( function_exists( 'clanbite_events_get_player_events_url' ) ) {
	$t->ok( 'event links still work', home_url( "/members/{$nice}/events/" ) === clanbite_events_get_player_events_url( $user_id ), clanbite_events_get_player_events_url( $user_id ) );
}
$avatar = clanbite_render_avatar(
	array(
		'type' => 'player',
		'id'   => $user_id,
		'link' => true,
		'echo' => false,
	)
);
$t->ok( 'avatars are shown without a link', str_contains( (string) $avatar, 'clanbite-avatar' ) && ! str_contains( (string) $avatar, 'clanbite-avatar__link' ), $avatar );
$t->ok( 'the player directory shortcode shows nothing', '' === $players->render_players_directory_shortcode() );
$t->ok( 'author links go back to WordPress’ own', 'https://example.test/author/x/' === $players->modify_author_links( 'https://example.test/author/x/', $user_id, $nice ) );

// ---- Teams (and groups) use the same rules ----
$settings   = array(
	'enable_profiles' => true,
	'player_url_slug' => 'members',
);
$team_saved = array( 'team_url_slug' => '' );
add_filter(
	'pre_option_clanbite_teams_settings',
	static function () use ( &$team_saved ) {
		return $team_saved;
	}
);
$t->ok( 'the default team slug is teams', 'teams' === clanbite_teams_get_slug() && home_url( '/teams/create/' ) === clanbite_teams_url( 'create/' ) );
$team_saved['team_url_slug'] = 'clans';
$t->ok( 'a chosen team slug is used', home_url( '/clans/alpha/' ) === clanbite_teams_url( 'alpha/' ) );
$t->ok( 'team paths read as team routes, and the old word no longer does', 'teams/alpha/events' === clanbite_teams_route_path( 'clans/alpha/events' ) && ! str_starts_with( clanbite_teams_route_path( 'teams/alpha' ), 'teams' ) && 'clansmen/x' === clanbite_teams_route_path( 'clansmen/x' ) );
$teams = clanbite()->extensions->get( 'clanbite_teams' );
if ( $teams ) {
	$kept                        = $wp_rewrite->extra_rules_top;
	$wp_rewrite->extra_rules_top = array();
	$teams->register_team_front_routes();
	$team_rules                  = array_keys( $wp_rewrite->extra_rules_top );
	$wp_rewrite->extra_rules_top = $kept;
	$t->ok( 'every team rewrite rule starts with the slug', array() !== $team_rules && array() === array_filter( $team_rules, static fn( $r ) => ! str_starts_with( $r, '^clans/' ) ), $team_rules );
	$visit( 'clans/create/' );
	$t->ok( 'the create screen answers at the new address', '1' === ( $teams->filter_request_for_team_virtual_pages( array() )['clanbite_team_create'] ?? '' ) );
	$visit( 'clans/alpha/manage/' );
	$asked = $teams->filter_request_for_team_virtual_pages( array() );
	$t->ok( 'so does a team’s manage screen', 'alpha' === ( $asked['clanbite_team_slug'] ?? '' ) && 'manage' === ( $asked['clanbite_team_action'] ?? '' ), $asked );
	$visit( 'teams/alpha/manage/' );
	$t->ok( 'but not at the old one', array() === $teams->filter_request_for_team_virtual_pages( array() ) );
}
$parsed = clanbite_parse_team_profile_url( 'https://other.example/teams/alpha/events/' );
$t->ok( 'another site’s team address is still read', 'alpha' === ( $parsed['slug'] ?? '' ), $parsed );
$parsed = clanbite_parse_team_profile_url( home_url( '/clans/alpha/' ) );
$t->ok( 'so is one with this site’s word', 'alpha' === ( $parsed['slug'] ?? '' ), $parsed );
$parsed = clanbite_parse_team_profile_url( 'https://other.example/guilds/bravo/' );
$t->ok( 'and one from a site with its own word', 'bravo' === ( $parsed['slug'] ?? '' ), $parsed );
$t->ok( 'other addresses are not team addresses', null === clanbite_parse_team_profile_url( 'https://other.example/' ) && null === clanbite_parse_team_profile_url( 'https://other.example/about' ) && null === clanbite_parse_team_profile_url( 'https://other.example/teams/create/' ) && null === clanbite_parse_team_profile_url( 'https://other.example/a/b/c' ) );

$t->ok( 'players can not take the teams slug, nor teams the players slug', '' !== clanbite_url_slug_problem( 'clans', 'players' ) && '' !== clanbite_url_slug_problem( 'members', 'teams' ) );
$t->ok( 'a team word that is free is accepted', '' === clanbite_url_slug_problem( 'clans', 'teams' ) && '' === clanbite_url_slug_problem( 'squads', 'teams' ), clanbite_url_slug_problem( 'clans', 'teams' ) );
foreach ( array( 'create', 'manage', 'feed', 'zz-taken-address' ) as $bad ) {
	$t->ok( "refused for teams: {$bad}", '' !== clanbite_url_slug_problem( $bad, 'teams' ) );
}
$result = clanbite_url_slug_submitted( '', 'clans', 'teams' );
$t->ok( 'emptying the field goes back to the default', '' === $result['value'] && '' === $result['problem'], $result );
$settings['player_url_slug'] = 'teams';
$result                      = clanbite_url_slug_submitted( '', 'clans', 'teams' );
$t->ok( 'unless something else has taken the default word', 'clans' === $result['value'] && '' !== $result['problem'], $result );
$settings['player_url_slug'] = 'members';
if ( isset( clanbite_url_slug_areas()['groups'] ) ) {
	$word = call_user_func( clanbite_url_slug_areas()['groups']['current'] );
	$t->ok( 'neither can take the groups slug', '' !== clanbite_url_slug_problem( $word, 'teams' ) && '' !== clanbite_url_slug_problem( $word, 'players' ), $word );
	$t->ok( 'and groups can not take theirs', '' !== clanbite_url_slug_problem( 'clans', 'groups' ) && '' !== clanbite_url_slug_problem( 'members', 'groups' ) && '' === clanbite_url_slug_problem( 'cities', 'groups' ), clanbite_url_slug_problem( 'cities', 'groups' ) );
}

// ---- The upgrade step for sites that had chosen "random" (WordPress 7.2 uses /random/) ----
$store  = array();
$holds  = array();
$keys   = array( 'clanbite_players_settings', 'clanbite_teams_settings', 'clanbite_social_kit_settings', 'clanbite_players_previous_slugs', 'clanbite_teams_previous_slugs', 'clanbite_groups_previous_slugs', 'clanbite_pending_rewrite_flush' );
$run_it = static function ( array $values ) use ( &$store, &$holds, $keys ): array {
	$store = $values;
	foreach ( $keys as $key ) {
		// Reads come from the array and writes go into it, so nothing reaches the database.
		$holds[ $key ] = array(
			static function () use ( &$store, $key ) {
				return $store[ $key ] ?? array();
			},
			static function ( $value, $old ) use ( &$store, $key ) {
				$store[ $key ] = $value;
				return $old;
			},
		);
		add_filter( "pre_option_{$key}", $holds[ $key ][0], 99 );
		// First in line: filters set earlier in this file would otherwise swap the value before it is seen.
		add_filter( "pre_update_option_{$key}", $holds[ $key ][1], PHP_INT_MIN, 2 );
	}
	\Kernowdev\Clanbite\Maintenance::run( 4 );
	foreach ( $holds as $key => $pair ) {
		remove_filter( "pre_option_{$key}", $pair[0], 99 );
		remove_filter( "pre_update_option_{$key}", $pair[1], PHP_INT_MIN );
	}
	return $store;
};

$after = $run_it(
	array(
		'clanbite_players_settings'    => array(
			'player_url_slug' => 'random',
			'enable_profiles' => true,
		),
		'clanbite_teams_settings'      => array( 'team_url_slug' => 'clans' ),
		'clanbite_social_kit_settings' => array( 'group_url_slug' => '' ),
	)
);
$t->ok( 'random player addresses go back to the default, keeping the other settings', '' === $after['clanbite_players_settings']['player_url_slug'] && true === $after['clanbite_players_settings']['enable_profiles'], $after );
$t->ok( 'old links are remembered for redirecting, and permalinks are rebuilt', array( 'random' ) === ( $after['clanbite_players_previous_slugs'] ?? null ) && '1' === ( $after['clanbite_pending_rewrite_flush'] ?? '' ), $after );
$t->ok( 'other areas are left alone', 'clans' === $after['clanbite_teams_settings']['team_url_slug'] && ! isset( $after['clanbite_teams_previous_slugs'] ) );

$after = $run_it(
	array(
		'clanbite_players_settings' => array( 'player_url_slug' => 'teams' ),
		'clanbite_teams_settings'   => array( 'team_url_slug' => 'random' ),
	)
);
$t->ok( 'if another area has taken the default word, a free one is used', 'teams-2' === $after['clanbite_teams_settings']['team_url_slug'], $after );

$after = $run_it( array( 'clanbite_social_kit_settings' => array( 'group_url_slug' => 'random' ) ) );
$t->ok( 'group addresses are moved too', '' === $after['clanbite_social_kit_settings']['group_url_slug'] && array( 'random' ) === $after['clanbite_groups_previous_slugs'], $after );

$untouched = array(
	'clanbite_players_settings' => array( 'player_url_slug' => 'members' ),
	'clanbite_teams_settings'   => array( 'team_url_slug' => '' ),
);
$t->ok( 'sites that never chose it are not changed', $untouched === $run_it( $untouched ) );

if ( null === $before ) {
	unset( $_SERVER['REQUEST_URI'] );
} else {
	$_SERVER['REQUEST_URI'] = $before;
}
exit( $t->finish() );
