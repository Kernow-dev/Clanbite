<?php
/**
 * WordPress.org directory rules. Run: `php tests/php/directory-compliance.php` (development database only).
 *
 * Guideline 7: no contact with other sites without consent. Guideline 8: no installing or updating plugins from elsewhere.
 * Every outbound request is recorded and blocked.
 *
 * @package clanbite
 */

define( 'DISABLE_WP_CRON', true );
require __DIR__ . '/harness.php';

use Kernowdev\Clanbite\Cross_Site_Match_Sync;

$requests = array();
add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) use ( &$requests ) {
		$requests[] = $url;
		return new WP_Error( 'blocked', 'Blocked in tests.' );
	},
	10,
	3
);
add_filter( 'pre_wp_mail', '__return_true' );

// The setting is controlled in memory: nothing is saved.
$setting = null;
$apply   = static function ( $value ) use ( &$setting ) {
	$value = (array) $value;
	if ( null === $setting ) {
		unset( $value['connect_clanbite_sites'] );
	} else {
		$value['connect_clanbite_sites'] = $setting;
	}
	return $value;
};
add_filter( 'option_clanbite_general_settings', $apply );
add_filter( 'default_option_clanbite_general_settings', $apply );
do_action( 'rest_api_init' );

// Guideline 8: core contains no way to install or update plugins from anywhere else.
$root  = dirname( __DIR__, 2 );
$found = array();
$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $files as $file ) {
	$path = substr( (string) $file, strlen( $root ) + 1 );
	if ( ! preg_match( '/\.(php|js)$/', $path ) || preg_match( '#^(node_modules|vendor|tests|scripts)/#', $path ) ) {
		continue;
	}
	if ( preg_match( '/Plugin_Upgrader|Theme_Upgrader|upgrader_pre_download|update_plugins_|pre_set_site_transient_update|plugins_api|\bdownload_url\s*\(|wp_ajax_install|\/wp\/v2\/plugins/', (string) file_get_contents( (string) $file ), $m ) ) {
		$found[] = "{$path}: {$m[0]}";
	}
}
$t->ok( 'core has no code that installs or updates plugins', array() === $found, $found );
$t->ok( 'the old updater classes and helpers are gone', ! class_exists( 'Kernowdev\\Clanbite\\Updates\\Updater' ) && ! function_exists( 'clanbite_update_server_url' ) && ! has_filter( 'upgrader_pre_download' ) );
$t->ok( 'there are no licence routes', array() === preg_grep( '#/clanbite/v1/admin/license#', array_keys( rest_get_server()->get_routes() ) ) );
$t->ok( 'the add-on guard is still active', false !== has_action( 'plugins_loaded', array( 'Kernowdev\\Clanbite\\Compat\\Compat_Monitor', 'maybe_sweep' ) ) );

// A full update check does not make Clanbite contact the add-on server.
$requests = array();
wp_set_current_user( 1 );
delete_site_transient( 'update_plugins' );
wp_update_plugins();
$t->ok( 'a plugin update check never contacts the add-on server', array() === preg_grep( '/services\.clanbite\.com/', $requests ), $requests );

// Guideline 7: off by default.
$t->ok( 'connections are off until an administrator turns them on', ! clanbite_site_connections_enabled() );

$push     = static function () use ( &$requests ): void {
	$team = (int) get_posts(
		array(
			'post_type'   => 'clanbite_team',
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	)[0];
	Cross_Site_Match_Sync::maybe_push_mirror_match(
		1,
		1,
		$team,
		array(
			'source'       => 'remote',
			'origin'       => 'https://example.com',
			'remoteTeamId' => 5,
		),
		gmdate( 'Y-m-d H:i:s' )
	);
};
$fetch_key = new ReflectionMethod( Cross_Site_Match_Sync::class, 'fetch_peer_public_key' );
$incoming  = static function () {
	$request = new WP_REST_Request( 'POST', '/clanbite/v1/sync-peer-match' );
	$request->set_header( 'content-type', 'application/json' );
	$request->set_header( 'x-clanbite-sync', 'v1:' . time() . ':abc' );
	$request->set_body( wp_json_encode( array( 'source_site' => 'https://example.com/', 'v' => 1, 'ts' => time() ) ) );
	return rest_do_request( $request );
};
$teams     = clanbite()->extensions->get( 'clanbite_teams' );
$bundle    = static function () {
	$instance = \Kernowdev\Clanbite\Extensions\Teams\Team_Challenges::instance();
	$method   = new ReflectionMethod( $instance, 'fetch_remote_team_bundle' );
	return $method->invoke( $instance, 'https://example.com/teams/rivals/' );
};

$requests = array();
$result   = $bundle();
$t->ok( 'off: a team lookup contacts nobody', is_wp_error( $result ) && 'clanbite_connections_off' === $result->get_error_code() && array() === $requests, $requests );
$push();
$t->ok( 'off: accepting sends nothing to the other site', array() === $requests, $requests );
$t->ok( 'off: no signing key is fetched', '' === $fetch_key->invoke( null, 'https://example.com/' ) && array() === $requests );
$response = $incoming();
$t->ok( 'off: an incoming match is refused without contacting the sender', 403 === $response->get_status() && 'clanbite_connections_off' === $response->as_error()->get_error_code() && array() === $requests );
foreach ( array( 'discovery', 'site-sync-public-key', 'public-team' ) as $route ) {
	wp_set_current_user( 0 );
	$response = rest_do_request( new WP_REST_Request( 'GET', "/clanbite/v1/{$route}" ) );
	$data     = (string) wp_json_encode( $response->get_data() );
	$t->ok( "off: {$route} reveals nothing", 403 === $response->get_status() && false === strpos( $data, 'version' ) && false === strpos( $data, 'public_key' ) );
}
$t->ok( 'off: another site\'s image is not shown', '' === clanbite_allowed_remote_image_url( 'https://example.com/logo.png' ) );
$t->ok( 'off: this site\'s images are', '' !== clanbite_allowed_remote_image_url( home_url( '/wp-content/uploads/logo.png' ) ) );

// The challenge form.
$team_id = (int) get_posts(
	array(
		'post_type'   => 'clanbite_team',
		'numberposts' => 1,
		'fields'      => 'ids',
	)
)[0];
$form    = static function () use ( $team_id ): string {
	wp_set_current_user( 0 );
	add_filter( 'clanbite_team_challenge_button_visible', '__return_true' );
	$block = new WP_Block( array( 'blockName' => 'clanbite/team-challenge-button' ), array( 'postId' => $team_id, 'postType' => 'clanbite_team' ) );
	return (string) $block->render();
};
$html    = $form();
if ( false === strpos( $html, 'clanbite-team-challenge__dialog' ) ) {
	echo "SKIP challenge form (not rendered for the first team)\n";
} else {
	$t->ok( 'off: the form does not offer a site address', false === strpos( $html, 'opponent_team_url' ) );
	$setting = true;
	$html    = $form();
	$t->ok( 'on: the form explains what a lookup does and waits to be asked', false !== strpos( $html, 'opponent_team_url' ) && false !== strpos( $html, 'Look up team' ) && false !== strpos( $html, 'makes this website contact that address' ) && false === strpos( $html, 'on--blur' ) );
}

// Turned on: the features do make their requests (controls for the checks above).
$setting  = true;
$requests = array();
$bundle();
$t->ok( 'on: a lookup contacts the entered site', array() !== preg_grep( '#^https://example\.com/wp-json/clanbite/v1/discovery#', $requests ), $requests );
$requests = array();
delete_transient( 'clanbite_sync_pk_v1_' . md5( 'https://example.com/' ) . '_fail' );
$fetch_key->invoke( null, 'https://example.com/' );
$t->ok( 'on: the sender\'s key is fetched', 1 === count( $requests ) );
delete_transient( 'clanbite_sync_pk_v1_' . md5( 'https://example.com/' ) . '_fail' );
$requests = array();
$push();
$t->ok( 'on: an accepted match is sent', array() !== preg_grep( '#sync-peer-match#', $requests ), $requests );
wp_set_current_user( 0 );
$t->ok( 'on: other sites can discover this one', 200 === rest_do_request( new WP_REST_Request( 'GET', '/clanbite/v1/discovery' ) )->get_status() );
$t->ok( 'on: another site\'s image is shown', '' !== clanbite_allowed_remote_image_url( 'https://example.com/logo.png' ) );
delete_site_transient( 'update_plugins' );

exit( $t->finish() );
