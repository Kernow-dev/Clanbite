<?php
/**
 * Visibility and capability checks for `clanbite_event` posts.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Events;

defined( 'ABSPATH' ) || exit;


/**
 * Permissions for team/group scoped events.
 */
final class Event_Permissions {

	/**
	 * Whether the viewer can see an event post (including draft rules).
	 *
	 * @param \WP_Post $post      Event post.
	 * @param int      $viewer_id Viewer user ID (0 = guest).
	 * @return bool
	 */
	public static function viewer_can_see( \WP_Post $post, int $viewer_id ): bool {
		if ( Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( 'publish' !== $post->post_status ) {
			return self::user_can_manage_event( $post->ID, $viewer_id );
		}

		$visibility = (string) get_post_meta( $post->ID, 'clanbite_event_visibility', true );
		$visibility = $visibility ? $visibility : Event_Post_Type::VISIBILITY_PUBLIC;

		$scope    = (string) get_post_meta( $post->ID, 'clanbite_event_scope', true );
		$team_id  = (int) get_post_meta( $post->ID, 'clanbite_event_team_id', true );
		$group_id = (int) get_post_meta( $post->ID, 'clanbite_event_group_id', true );

		if ( Event_Post_Type::SCOPE_PLAYER === $scope ) {
			return self::viewer_can_see_player_event( $post, $viewer_id, $visibility );
		}

		// A group's privacy comes first: an event marked public in a private group is still for that group only. Whoever
		// manages the group (not merely the event's author, who may have left or been banned) and site admins pass.
		if ( Event_Post_Type::SCOPE_GROUP === $scope && $group_id > 0 && function_exists( 'clanbite_groups_viewer_can_see_content' )
			&& ! clanbite_groups_viewer_can_see_content( $group_id, $viewer_id )
			&& ! ( $viewer_id > 0 && ( user_can( $viewer_id, 'manage_options' )
				|| ( function_exists( 'clanbite_groups_user_can_manage' ) && clanbite_groups_user_can_manage( $group_id, $viewer_id ) ) ) ) ) {
			return false;
		}

		if ( Event_Post_Type::VISIBILITY_PUBLIC === $visibility ) {
			return true;
		}

		if ( $viewer_id <= 0 ) {
			return false;
		}

		if ( Event_Post_Type::VISIBILITY_MEMBERS === $visibility ) {
			return true;
		}

		if ( Event_Post_Type::SCOPE_TEAM === $scope && $team_id > 0 ) {
			if ( Event_Post_Type::VISIBILITY_TEAM_ADMINS === $visibility ) {
				return function_exists( 'clanbite_teams_user_can_manage' )
					&& clanbite_teams_user_can_manage( $team_id, $viewer_id );
			}
			if ( Event_Post_Type::VISIBILITY_TEAM_MEMBERS === $visibility ) {
				return function_exists( 'clanbite_teams_get_member_role' )
					&& null !== clanbite_teams_get_member_role( $team_id, $viewer_id );
			}
		}

		if ( Event_Post_Type::SCOPE_GROUP === $scope && $group_id > 0 ) {
			if ( Event_Post_Type::VISIBILITY_TEAM_ADMINS === $visibility ) {
				return function_exists( 'clanbite_groups_user_can_manage' )
					&& clanbite_groups_user_can_manage( $group_id, $viewer_id );
			}
			if ( Event_Post_Type::VISIBILITY_TEAM_MEMBERS === $visibility ) {
				return function_exists( 'clanbite_groups_user_is_member' )
					&& clanbite_groups_user_is_member( $group_id, $viewer_id );
			}
		}

		/**
		 * Filter whether a viewer can see a published `clanbite_event`.
		 *
		 * @param bool     $can       Whether the viewer can see the event.
		 * @param \WP_Post $post      Event post.
		 * @param int      $viewer_id Viewer user ID.
		 */
		return (bool) apply_filters( 'clanbite_event_post_viewer_can_see', false, $post, $viewer_id );
	}

	/**
	 * Whether the viewer can see a published player-owned event.
	 *
	 * The owner and site administrators always can. Otherwise the owner's choice applies: `public` (anyone, signed in or not),
	 * `friends` (accepted friends only) or `only_me` (nobody else).
	 *
	 * @param \WP_Post $post       Event post.
	 * @param int      $viewer_id  Viewer user ID (0 = guest).
	 * @param string   $visibility Stored `clanbite_event_visibility`.
	 * @return bool
	 */
	private static function viewer_can_see_player_event( \WP_Post $post, int $viewer_id, string $visibility ): bool {
		$owner_id = (int) $post->post_author;
		$can      = false;

		if ( $viewer_id > 0 && ( $viewer_id === $owner_id || user_can( $viewer_id, 'manage_options' ) ) ) {
			$can = true;
		} elseif ( Event_Post_Type::VISIBILITY_PUBLIC === $visibility ) {
			$can = true;
		} elseif ( Event_Post_Type::VISIBILITY_FRIENDS === $visibility && $viewer_id > 0 ) {
			// Friends, and anyone the owner invited (invitations only exist on events that are not `only_me`).
			$can = ( function_exists( 'clanbite_players_are_friends' ) && clanbite_players_are_friends( $owner_id, $viewer_id ) )
				|| ( function_exists( 'clanbite_events_get_invited_user_ids' ) && in_array( $viewer_id, clanbite_events_get_invited_user_ids( $post->ID ), true ) );
		}

		/**
		 * Filter whether a viewer can see a player-owned event.
		 *
		 * @param bool     $can        Whether the viewer can see the event.
		 * @param \WP_Post $post       Event post.
		 * @param int      $viewer_id  Viewer user ID (0 for guests).
		 * @param string   $visibility `public`, `friends` or `only_me`.
		 */
		return (bool) apply_filters( 'clanbite_player_event_viewer_can_see', $can, $post, $viewer_id, $visibility );
	}

	/**
	 * Whether the user can create/edit/delete this event (REST + forms).
	 *
	 * @param int $post_id Event post ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function user_can_manage_event( int $post_id, int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$post = get_post( $post_id );
		if ( ! ( $post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		$scope    = (string) get_post_meta( $post_id, 'clanbite_event_scope', true );
		$team_id  = (int) get_post_meta( $post_id, 'clanbite_event_team_id', true );
		$group_id = (int) get_post_meta( $post_id, 'clanbite_event_group_id', true );

		if ( Event_Post_Type::SCOPE_TEAM === $scope && $team_id > 0 && function_exists( 'clanbite_teams_user_can_manage' ) ) {
			return clanbite_teams_user_can_manage( $team_id, $user_id );
		}

		if ( Event_Post_Type::SCOPE_GROUP === $scope && $group_id > 0 && function_exists( 'clanbite_groups_user_can_manage' ) ) {
			return clanbite_groups_user_can_manage( $group_id, $user_id );
		}

		/**
		 * Filter whether a user can manage a `clanbite_event`.
		 *
		 * @param bool $can     Whether the user can manage.
		 * @param int  $post_id Event post ID.
		 * @param int  $user_id User ID.
		 */
		return (bool) apply_filters( 'clanbite_event_user_can_manage', false, $post_id, $user_id );
	}

	/**
	 * Whether the viewer may see the RSVP / attendee list for an event post.
	 *
	 * @param \WP_Post $post      Event post.
	 * @param int      $viewer_id Viewer user ID (0 = guest).
	 * @return bool
	 */
	public static function can_view_attendees( \WP_Post $post, int $viewer_id ): bool {
		if ( Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( ! self::viewer_can_see( $post, $viewer_id ) ) {
			return false;
		}

		$att = (string) get_post_meta( $post->ID, 'clanbite_event_attendees_visibility', true );
		if ( 'public' === $att ) {
			return true;
		}

		if ( $viewer_id <= 0 ) {
			return false;
		}

		if ( self::user_can_manage_event( $post->ID, $viewer_id ) ) {
			return true;
		}

		$scope    = (string) get_post_meta( $post->ID, 'clanbite_event_scope', true );
		$team_id  = (int) get_post_meta( $post->ID, 'clanbite_event_team_id', true );
		$group_id = (int) get_post_meta( $post->ID, 'clanbite_event_group_id', true );

		if ( Event_Post_Type::SCOPE_PLAYER === $scope ) {
			return function_exists( 'clanbite_events_get_invited_user_ids' ) && in_array( $viewer_id, clanbite_events_get_invited_user_ids( $post->ID ), true );
		}

		if ( Event_Post_Type::SCOPE_TEAM === $scope && $team_id > 0 && function_exists( 'clanbite_teams_get_member_role' ) ) {
			return null !== clanbite_teams_get_member_role( $team_id, $viewer_id );
		}

		if ( Event_Post_Type::SCOPE_GROUP === $scope && $group_id > 0 && function_exists( 'clanbite_groups_user_is_member' ) ) {
			return clanbite_groups_user_is_member( $group_id, $viewer_id );
		}

		return false;
	}
}
