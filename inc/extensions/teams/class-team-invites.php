<?php
/**
 * Inviting players to a team after it exists.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Extensions\Teams;

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Extensions\Teams;
use Kernowdev\Clanbite\Extensions\Notification\Notification_Data_Access;
use Kernowdev\Clanbite\Extensions\Notification\Notification_Schema;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Rules and REST routes for team invitations.
 *
 * Routes (`clanbite/v1`, logged-in team admins and teams site admins only):
 * - `GET  /teams/{team_id}/invite-search?q=` players who can be invited.
 * - `POST /teams/{team_id}/invites` with `user_id` to invite one.
 */
final class Team_Invites {

	/**
	 * Teams extension.
	 *
	 * @var Teams
	 */
	private Teams $teams;

	/**
	 * Constructor.
	 *
	 * @param Teams $teams Teams extension.
	 */
	public function __construct( Teams $teams ) {
		$this->teams = $teams;
	}

	/**
	 * Hook the routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register the invite routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'clanbite/v1',
			'/teams/(?P<team_id>\d+)/invite-search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_search' ),
				'permission_callback' => array( $this, 'rest_permission' ),
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'clanbite/v1',
			'/teams/(?P<team_id>\d+)/invites',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_invite' ),
				'permission_callback' => array( $this, 'rest_permission' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Route permission: the same rule as {@see Team_Invites::check_can_invite()}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function rest_permission( WP_REST_Request $request ) {
		return $this->check_can_invite( (int) $request['team_id'], get_current_user_id() );
	}

	/**
	 * Whether a user may invite players to a team right now.
	 *
	 * Team admins (while front-end editing is on) and teams site admins may, if the team allows invitations and
	 * the Notifications extension can deliver them.
	 *
	 * @param int $team_id Team post ID.
	 * @param int $user_id Inviting user ID.
	 * @return true|WP_Error
	 */
	public function check_can_invite( int $team_id, int $user_id ) {
		$status = array( 'status' => $user_id > 0 ? 403 : 401 );

		if ( 'clanbite_team' !== get_post_type( $team_id ) || ! $this->teams->user_is_team_admin_on_frontend( $team_id, $user_id ) ) {
			return new WP_Error( 'clanbite_invite_forbidden', __( 'You cannot invite players to this team.', 'clanbite' ), $status );
		}

		if ( ! $this->teams->can_invite_players( $team_id ) ) {
			return new WP_Error( 'clanbite_invites_off', __( 'Invitations are turned off for this team.', 'clanbite' ), $status );
		}

		if ( ! self::notifications_available() ) {
			return new WP_Error( 'notifications_unavailable', __( 'Invitations need the Notifications extension.', 'clanbite' ), $status );
		}

		return true;
	}

	/**
	 * Whether invitations can be delivered.
	 *
	 * @return bool
	 */
	public static function notifications_available(): bool {
		return function_exists( 'clanbite_notify' )
			&& function_exists( 'clanbite_notifications_extension_active' )
			&& clanbite_notifications_extension_active();
	}

	/**
	 * Users with an invitation to the team that is still waiting for a reply.
	 *
	 * Invitations whose sender may no longer invite are marked expired, so they neither work nor block a new one.
	 *
	 * @param int $team_id Team post ID.
	 * @return int[] User IDs.
	 */
	public function get_pending_user_ids( int $team_id ): array {
		global $wpdb;

		if ( ! self::notifications_available() ) {
			return array();
		}

		$table = Notification_Schema::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from the schema class; the list must be current.
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, actor_id FROM {$table} WHERE type = %s AND object_type = %s AND object_id = %d AND status = %s", 'team_invite', 'team', $team_id, Notification_Data_Access::STATUS_PENDING ) );

		$ids = array();
		foreach ( $rows as $row ) {
			if ( ! $this->is_still_valid( $team_id, (int) $row->user_id, (int) $row->actor_id ) ) {
				Notification_Data_Access::update_status( (int) $row->id, Notification_Data_Access::STATUS_EXPIRED );
				continue;
			}
			$ids[] = (int) $row->user_id;
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Whether an invitation may still be accepted: its sender can still invite and the team still allows it.
	 *
	 * @param int $team_id    Team post ID.
	 * @param int $user_id    Invited user ID.
	 * @param int $inviter_id User who sent it.
	 * @return bool
	 */
	public function is_still_valid( int $team_id, int $user_id, int $inviter_id ): bool {
		$valid = $inviter_id > 0
			&& $this->teams->can_invite_players( $team_id )
			&& $this->teams->user_is_team_admin_on_frontend( $team_id, $inviter_id );

		/**
		 * Filters whether a team invitation can still be accepted.
		 *
		 * Return true for invitations your own code sends on behalf of someone who is not a team admin.
		 *
		 * @since 1.7.0
		 *
		 * @param bool $valid      Whether the invitation is still valid.
		 * @param int  $team_id    Team post ID.
		 * @param int  $user_id    Invited user ID.
		 * @param int  $inviter_id User who sent it.
		 */
		return (bool) apply_filters( 'clanbite_team_invite_is_still_valid', $valid, $team_id, $user_id, $inviter_id );
	}

	/**
	 * Invite a player, applying every rule.
	 *
	 * @param int $team_id    Team post ID.
	 * @param int $user_id    Player to invite.
	 * @param int $inviter_id Inviting user ID.
	 * @return true|WP_Error
	 */
	public function invite( int $team_id, int $user_id, int $inviter_id ) {
		$allowed = $this->check_can_invite( $team_id, $inviter_id );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$bad = array( 'status' => 400 );
		if ( $user_id === $inviter_id || ! get_userdata( $user_id ) || ( is_multisite() && ! is_user_member_of_blog( $user_id ) ) ) {
			return new WP_Error( 'clanbite_invite_unavailable', __( 'That player cannot be invited.', 'clanbite' ), $bad );
		}
		if ( null !== $this->teams->get_team_member_role( $team_id, $user_id ) ) {
			return new WP_Error( 'clanbite_invite_on_roster', __( 'That player is already on the roster.', 'clanbite' ), $bad );
		}
		// One message for "does not accept invitations" and "at their team limit", so neither is disclosed.
		if ( ! $this->teams->user_can_appear_in_team_invite_search( $user_id ) ) {
			return new WP_Error( 'clanbite_invite_unavailable', __( 'That player cannot be invited.', 'clanbite' ), $bad );
		}
		if ( in_array( $user_id, $this->get_pending_user_ids( $team_id ), true ) ) {
			return new WP_Error( 'duplicate', __( 'That player already has an invitation waiting.', 'clanbite' ), $bad );
		}

		/**
		 * Filters how many invitations one user may send per hour.
		 *
		 * @since 1.7.0
		 *
		 * @param int $limit      Invitations per hour (default 30; 0 removes the limit).
		 * @param int $inviter_id Inviting user ID.
		 * @param int $team_id    Team post ID.
		 */
		$limit = (int) apply_filters( 'clanbite_team_invite_rate_limit', 30, $inviter_id, $team_id );
		$key   = 'clanbite_team_invites_' . $inviter_id;
		$sent  = (array) get_transient( $key );
		$sent  = array_filter( $sent, static fn( $time ): bool => (int) $time > time() - HOUR_IN_SECONDS );
		if ( $limit > 0 && count( $sent ) >= $limit && ! $this->teams->user_is_teams_site_admin( $inviter_id ) ) {
			return new WP_Error( 'clanbite_invite_rate_limited', __( 'You have sent a lot of invitations. Please try again later.', 'clanbite' ), array( 'status' => 429 ) );
		}

		$result = $this->teams->send_team_invite( $team_id, $user_id, $inviter_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$sent[] = time();
		set_transient( $key, array_values( $sent ), HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * `GET /teams/{team_id}/invite-search`.
	 *
	 * Matches names and usernames. Email addresses are searched only for teams site admins, so captains cannot use the
	 * box to find out whose address is registered.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function rest_search( WP_REST_Request $request ): WP_REST_Response {
		$team_id = (int) $request['team_id'];
		$query   = trim( (string) $request['q'], " \t\n\r\0\x0B*" );
		if ( strlen( $query ) < 2 ) {
			return new WP_REST_Response( array() );
		}

		$columns = array( 'user_login', 'display_name' );
		if ( $this->teams->user_is_teams_site_admin() ) {
			$columns[] = 'user_email';
		}

		$users = get_users(
			array(
				'number'         => 30,
				'search'         => '*' . $query . '*',
				'search_columns' => $columns,
				'orderby'        => 'display_name',
				'exclude'        => array_merge(
					array( get_current_user_id() ),
					array_keys( $this->teams->get_team_member_roles_map( $team_id ) ),
					$this->get_pending_user_ids( $team_id )
				),
			)
		);

		$results = array();
		foreach ( $users as $user ) {
			if ( ! $this->teams->user_can_appear_in_team_invite_search( (int) $user->ID ) ) {
				continue;
			}
			$results[] = array(
				'id'    => (int) $user->ID,
				'label' => sprintf( '%1$s (%2$s)', $user->display_name, $user->user_login ),
			);
			if ( count( $results ) >= 10 ) {
				break;
			}
		}

		return new WP_REST_Response( $results );
	}

	/**
	 * `POST /teams/{team_id}/invites`.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_invite( WP_REST_Request $request ) {
		$user_id = (int) $request['user_id'];
		$result  = $this->invite( (int) $request['team_id'], $user_id, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			if ( ! isset( $result->get_error_data()['status'] ) ) {
				$result->add_data( array( 'status' => 400 ) );
			}
			return $result;
		}

		return new WP_REST_Response(
			array(
				'invited' => true,
				'user_id' => $user_id,
			),
			201
		);
	}
}
