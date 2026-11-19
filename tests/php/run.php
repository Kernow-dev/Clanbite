<?php
/**
 * Core tests: components registry, relative time and avatar context. Run: `php tests/php/run.php`.
 *
 * @package clanbite
 */

require __DIR__ . '/harness.php';

do_action( 'init' );
wp_set_current_user( 1 );

// Registry.
$components = clanbite_get_components();
foreach ( array( 'avatar', 'toasts', 'popups', 'clipboard', 'time' ) as $slug ) {
	$t->ok( "core registers {$slug}", isset( $components[ $slug ] ) );
}
$t->ok( 'unknown component is absent and renders nothing', ! clanbite_has_component( 'zz-none' ) && '' === clanbite_render_component( 'zz-none' ) );

clanbite_register_component(
	'zz-needs',
	array(
		'provider' => 'test',
		'requires' => array( 'zz-missing' ),
		'render'   => static fn(): string => 'x',
	)
);
$t->ok( 'missing requirement makes it unavailable', ! clanbite_has_component( 'zz-needs' ) && array( 'zz-missing' ) === clanbite_component_missing_requirements( 'zz-needs' ) );
$t->ok( 'duplicate slug is refused', false === clanbite_register_component( 'avatar', array( 'render' => static fn(): string => 'x' ) ) );
$t->ok( 'replace swaps the provider', clanbite_register_component( 'zz-needs', array( 'replace' => true, 'provider' => 'test2', 'render' => static fn(): string => 'ok' ) ) && 'ok' === clanbite_render_component( 'zz-needs' ) );
add_filter( 'clanbite_component_html', static fn( $h, $s ) => 'zz-needs' === $s ? 'filtered' : $h, 10, 2 );
$t->ok( 'html filter applies', 'filtered' === clanbite_render_component( 'zz-needs' ) );
add_filter( 'clanbite_component_available', static fn( $a, $s ) => 'zz-needs' === $s ? false : $a, 10, 2 );
$t->ok( 'availability filter applies', ! clanbite_has_component( 'zz-needs' ) );
$t->ok( 'enqueue runs a component', clanbite_enqueue_component( 'popups' ) && wp_script_is( 'clanbite-popups', 'enqueued' ) );

// Time.
$t->ok( 'relative time wording', '5 minutes ago' === clanbite_human_time_ago( time() - 300 ) );
$t->ok( 'time component renders it', '5 minutes ago' === clanbite_render_component( 'time', array( 'timestamp' => time() - 300 ) ) );
add_filter( 'clanbite_human_time_ago', static fn( $text ) => '~' . $text );
$t->ok( 'time wording is filterable', 0 === strpos( clanbite_human_time_ago( time() - 7200 ), '~' ) );

// Avatar context.
$html = clanbite_render_component(
	'avatar',
	array(
		'type'    => 'player',
		'id'      => 1,
		'size'    => 'small',
		'context' => 'my_surface',
	)
);
$t->ok( 'avatar carries is-{context}', false !== strpos( $html, 'is-my-surface' ) );
$seen = null;
add_filter(
	'clanbite_avatar_args',
	static function ( $args ) use ( &$seen ) {
		$seen = $args;
		return $args;
	}
);
clanbite_render_avatar( array( 'type' => 'player', 'id' => 1, 'context' => 'x', 'object_type' => 'post', 'extension' => 'zz' ) );
$t->ok( 'avatar args carry viewer and object context', is_array( $seen ) && 1 === $seen['viewer_id'] && true === $seen['is_viewer'] && 'post' === $seen['object_type'] && 'zz' === $seen['extension'] );

// The hexagon's progress arc starts at 12 o'clock (x = 50), not at the left end of the top corner.
$hex_start = explode( ',', explode( ' ', clanbite_avatar_hexagon_points( 43 ) )[0] );
$t->ok( 'hexagon ring starts at the top centre', 50.0 === (float) $hex_start[0] && (float) $hex_start[1] < 12 );

// Player events: who can see them, invitations, and creating one through REST.
use Kernowdev\Clanbite\Events\Event_Permissions;
use Kernowdev\Clanbite\Events\Event_Post_Type;

$mk_user = static function ( string $login ) use ( $t ): int {
	$stale = get_user_by( 'login', $login );
	if ( $stale ) {
		wp_delete_user( $stale->ID );
	}
	$id = wp_insert_user(
		array(
			'user_login' => $login,
			'user_pass'  => wp_generate_password(),
			'user_email' => $login . '@example.test',
		)
	);
	$t->cleanup( static fn() => wp_delete_user( (int) $id ) );

	return (int) $id;
};
$owner    = $mk_user( 'zz_ev_owner' );
$friend   = $mk_user( 'zz_ev_friend' );
$stranger = $mk_user( 'zz_ev_stranger' );
add_filter( 'clanbite_players_friends_available', '__return_true' );
add_filter(
	'clanbite_players_friend_ids',
	static fn( $ids, $uid ) => (int) $uid === $owner ? array( $friend ) : ( (int) $uid === $friend ? array( $owner ) : $ids ),
	10,
	2
);
add_filter(
	'clanbite_players_are_friends',
	static fn( $yes, $a, $b ) => $yes || ( in_array( (int) $a, array( $owner, $friend ), true ) && in_array( (int) $b, array( $owner, $friend ), true ) && $a !== $b ),
	10,
	3
);

$mk_event = static function ( string $visibility ) use ( $owner, $t ): \WP_Post {
	$id = wp_insert_post(
		array(
			'post_type'   => Event_Post_Type::POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => 'zz test event',
			'post_author' => $owner,
		)
	);
	update_post_meta( $id, 'clanbite_event_scope', Event_Post_Type::SCOPE_PLAYER );
	update_post_meta( $id, 'clanbite_event_owner_id', $owner );
	update_post_meta( $id, 'clanbite_event_visibility', $visibility );
	$t->cleanup( static fn() => wp_delete_post( (int) $id, true ) );

	return get_post( $id );
};
$ev_any  = $mk_event( 'public' );
$ev_fr   = $mk_event( 'friends' );
$ev_self = $mk_event( 'only_me' );
$t->ok( 'anyone: guest, stranger, friend, owner see it', Event_Permissions::viewer_can_see( $ev_any, 0 ) && Event_Permissions::viewer_can_see( $ev_any, $stranger ) && Event_Permissions::viewer_can_see( $ev_any, $friend ) && Event_Permissions::viewer_can_see( $ev_any, $owner ) );
$t->ok( 'friends: friend and owner only', Event_Permissions::viewer_can_see( $ev_fr, $friend ) && Event_Permissions::viewer_can_see( $ev_fr, $owner ) && ! Event_Permissions::viewer_can_see( $ev_fr, $stranger ) && ! Event_Permissions::viewer_can_see( $ev_fr, 0 ) );
$t->ok( 'only me: nobody else, not even a friend', Event_Permissions::viewer_can_see( $ev_self, $owner ) && ! Event_Permissions::viewer_can_see( $ev_self, $friend ) && ! Event_Permissions::viewer_can_see( $ev_self, $stranger ) && ! Event_Permissions::viewer_can_see( $ev_self, 0 ) );
$t->ok( 'owner manages, others do not', Event_Permissions::user_can_manage_event( $ev_fr->ID, $owner ) && ! Event_Permissions::user_can_manage_event( $ev_fr->ID, $friend ) );

$added = clanbite_events_set_invited_user_ids( $ev_fr->ID, $owner, array( $friend, $stranger, $owner, 999999 ) );
$t->ok( 'only friends are invited (not strangers, the owner or unknown ids)', array( $friend ) === $added && array( $friend ) === clanbite_events_get_invited_user_ids( $ev_fr->ID ) );
$t->ok( 'inviting again adds no one new', array() === clanbite_events_set_invited_user_ids( $ev_fr->ID, $owner, array( $friend ) ) );
$t->ok( 'invitations can be removed', array() === clanbite_events_set_invited_user_ids( $ev_fr->ID, $owner, array() ) && array() === clanbite_events_get_invited_user_ids( $ev_fr->ID ) );
clanbite_events_set_invited_user_ids( $ev_self->ID, $owner, array( $friend ) );
$t->ok( 'an only-me event takes no invitations', array() === clanbite_events_get_invited_user_ids( $ev_self->ID ) );
$t->ok( 'invitee sees a friends event and the attendee list', ( clanbite_events_set_invited_user_ids( $ev_fr->ID, $owner, array( $friend ) ) || true ) && Event_Permissions::viewer_can_see( $ev_fr, $friend ) && Event_Permissions::can_view_attendees( $ev_fr, $friend ) && ! Event_Permissions::can_view_attendees( $ev_fr, $stranger ) );
$t->ok( 'player events URL carries the event', false !== strpos( clanbite_events_get_player_events_url( $owner, 5 ), 'clanbite_event_view=5' ) && false !== strpos( clanbite_events_get_player_events_url( $owner, 'new' ), 'clanbite_event_view=new' ) );

wp_set_current_user( $owner );
$req = new WP_REST_Request( 'POST', '/clanbite/v1/event-posts' );
$req->set_header( 'content-type', 'application/json' );
$req->set_body( wp_json_encode( array( 'scope' => 'player', 'title' => 'zz rest event', 'visibility' => 'friends', 'invite_user_ids' => array( $friend, $stranger ) ) ) );
$res = rest_do_request( $req );
$data = $res->get_data();
if ( 201 === $res->get_status() && ! empty( $data['id'] ) ) {
	$t->cleanup( static fn() => wp_delete_post( (int) $data['id'], true ) );
	$t->ok( 'REST creates a player event owned by the author', 'player' === $data['scope'] && $owner === $data['ownerId'] && 'friends' === $data['visibility'] );
	$t->ok( 'REST invites only the friend', array( $friend ) === $data['invitedUserIds'] );
	$list = rest_do_request( new WP_REST_Request( 'GET', '/clanbite/v1/event-posts' ) );
	wp_set_current_user( $stranger );
	$req2 = new WP_REST_Request( 'GET', '/clanbite/v1/event-posts' );
	$req2->set_param( 'player_user_id', $owner );
	$stranger_list = rest_do_request( $req2 )->get_data();
	$t->ok( "a stranger's view of the profile hides a friends-only event", 0 === count( (array) ( $stranger_list['items'] ?? array() ) ) );
	wp_set_current_user( $friend );
	$req3 = new WP_REST_Request( 'GET', '/clanbite/v1/event-posts' );
	$req3->set_param( 'player_user_id', $owner );
	$friend_list = rest_do_request( $req3 )->get_data();
	$t->ok( "a friend's view of the profile shows it", count( (array) ( $friend_list['items'] ?? array() ) ) >= 1 );
	$req4 = new WP_REST_Request( 'GET', '/clanbite/v1/event-posts' );
	$req4->set_param( 'player_user_id', $friend );
	$own_list = rest_do_request( $req4 )->get_data();
	$ids = wp_list_pluck( (array) ( $own_list['items'] ?? array() ), 'id' );
	$t->ok( 'the invited friend sees it in their own calendar', in_array( (int) $data['id'], array_map( 'intval', $ids ), true ), $ids );
	wp_set_current_user( $stranger );
	$bad = new WP_REST_Request( 'PUT', '/clanbite/v1/event-posts/' . (int) $data['id'] );
	$bad->set_header( 'content-type', 'application/json' );
	$bad->set_body( wp_json_encode( array( 'title' => 'hijack' ) ) );
	$t->ok( 'a stranger cannot edit it', 403 === rest_do_request( $bad )->get_status() );
} else {
	$t->ok( 'REST creates a player event (events extension is installed)', false, $data );
}
wp_set_current_user( 1 );

// Events settings moved to Clanbite → Events: the old toggles are carried over once and the helpers keep working.
$saved = array();
foreach ( array( 'clanbite_events_settings', 'clanbite_events_settings_migrated', 'clanbite_general_settings', 'clanbite_players_settings', 'clanbite_teams_settings', 'clanbite_groups_settings' ) as $opt ) {
	$saved[ $opt ] = get_option( $opt, null );
}
$t->cleanup(
	static function () use ( $saved ): void {
		foreach ( $saved as $opt => $value ) {
			null === $value ? delete_option( $opt ) : update_option( $opt, $value );
		}
	}
);
delete_option( 'clanbite_events_settings' );
delete_option( 'clanbite_events_settings_migrated' );
update_option( 'clanbite_general_settings', array_merge( (array) $saved['clanbite_general_settings'], array( 'events_enabled' => false ) ) );
update_option( 'clanbite_teams_settings', array_merge( (array) $saved['clanbite_teams_settings'], array( 'events_profile_subpage' => false ) ) );
$t->ok( 'old General switch still counts before migration', false === clanbite_events_are_globally_enabled() );
$t->ok( 'old Teams toggle still counts before migration', false === clanbite_events_subpage_team_enabled() );
clanbite_events_maybe_migrate_settings();
$moved = (array) get_option( 'clanbite_events_settings', array() );
$t->ok( 'migration copies the old values into the Events option', isset( $moved['events_enabled'], $moved['subpage_team'] ) && false === $moved['events_enabled'] && false === $moved['subpage_team'] );
update_option( 'clanbite_events_settings', array_merge( $moved, array( 'events_enabled' => true, 'subpage_team' => true ) ) );
$t->ok( 'the Events option wins once it exists', true === clanbite_events_are_globally_enabled() && true === clanbite_events_subpage_team_enabled() );
update_option( 'clanbite_events_settings', array_merge( $moved, array( 'events_enabled' => true, 'player_events_enabled' => false ) ) );
$t->ok( 'players can be switched off from creating events', false === clanbite_player_events_enabled() );

// Old `cp_` names stored in the database move to `clanbite_` once, without touching rows that already have the new name.
global $wpdb;
$lp_post  = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'ZZ legacy prefix' ) );
$lp_user  = $owner;
$lp_table = $wpdb->prefix . 'cp_zz_legacy';
$lp_new   = $wpdb->prefix . 'clanbite_zz_legacy';
$wpdb->query( "DROP TABLE IF EXISTS `{$lp_table}`, `{$lp_new}`" );
$wpdb->query( "CREATE TABLE `{$lp_table}` ( id int )" );
$wpdb->query( "INSERT INTO `{$lp_table}` VALUES (1)" );
$lp_saved_installed = get_option( 'clanbite_installed_extensions' );
update_post_meta( $lp_post, 'cp_team_wins', '7' );
update_post_meta( $lp_post, '_cp_mentions', 'a' );
update_post_meta( $lp_post, 'cp_zz_both', 'old' );
update_post_meta( $lp_post, 'clanbite_zz_both', 'new' );
update_user_meta( $lp_user, 'cp_player_tagline', 'hi' );
update_option( 'cp_zz_option', 'x' );
update_option( 'clanbite_installed_extensions', array( 'cp_shop' => array( 'version' => '1' ), 'cp_forums' => array( 'version' => '2' ), 'clanbite_forums' => array( 'version' => '3' ) ) );
delete_option( CLANBITE_LEGACY_PREFIX_MIGRATION_OPTION );
clanbite_legacy_prefix_migrate_current_site();
$lp_installed = (array) get_option( 'clanbite_installed_extensions' );
$t->ok( 'extension slugs are renamed and an existing new entry wins', isset( $lp_installed['clanbite_shop'] ) && ! isset( $lp_installed['cp_shop'] ) && ! isset( $lp_installed['cp_forums'] ) && '3' === $lp_installed['clanbite_forums']['version'] );
$t->ok( 'post meta keys are renamed', '7' === get_post_meta( $lp_post, 'clanbite_team_wins', true ) && '' === get_post_meta( $lp_post, 'cp_team_wins', true ) && 'a' === get_post_meta( $lp_post, '_clanbite_mentions', true ) );
$t->ok( 'an old meta row is dropped when the new key already exists', 'new' === get_post_meta( $lp_post, 'clanbite_zz_both', true ) && '' === get_post_meta( $lp_post, 'cp_zz_both', true ) );
$t->ok( 'user meta keys are renamed', 'hi' === get_user_meta( $lp_user, 'clanbite_player_tagline', true ) && '' === get_user_meta( $lp_user, 'cp_player_tagline', true ) );
$t->ok( 'options are renamed', 'x' === get_option( 'clanbite_zz_option' ) && false === get_option( 'cp_zz_option' ) );
$t->ok( 'custom tables are renamed with their rows', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$lp_new}`" ) && null === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $lp_table ) ) );
$t->ok( 'the migration marks itself done and does not run twice', '1' === get_option( CLANBITE_LEGACY_PREFIX_MIGRATION_OPTION ) );
$wpdb->query( "DROP TABLE IF EXISTS `{$lp_new}`" );
delete_option( 'clanbite_zz_option' );
delete_user_meta( $lp_user, 'clanbite_player_tagline' );
update_option( 'clanbite_installed_extensions', $lp_saved_installed );
wp_delete_post( $lp_post, true );

exit( $t->finish() );
