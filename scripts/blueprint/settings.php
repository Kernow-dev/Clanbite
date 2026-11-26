<?php
/**
 * Live Preview, part 1: switch the extensions on and choose settings. Part 2 runs in a new request, so everything these
 * settings turn on is registered by then. Only runs inside WordPress Playground (see scripts/build-blueprint.mjs).
 *
 * @package clanbite
 */

require_once '/wordpress/wp-load.php';

$installed = array();
foreach ( array( 'clanbite_players', 'clanbite_notifications', 'clanbite_teams', 'clanbite_matches', 'clanbite_events' ) as $slug ) {
	$installed[ $slug ] = array( 'version' => '1.0.0' );
}
update_option( 'clanbite_installed_extensions', $installed );
// "Team directories" gives the demo a team list and a profile page for each team.
update_option( 'clanbite_teams_settings', array_merge( (array) get_option( 'clanbite_teams_settings', array() ), array( 'team_mode' => 'team_directories' ) ) );

update_option( 'blogdescription', 'A demo community built with Clanbite' );
global $wp_rewrite;
$wp_rewrite->set_permalink_structure( '/%postname%/' );
delete_option( 'rewrite_rules' );
