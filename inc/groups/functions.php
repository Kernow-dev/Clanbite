<?php
/**
 * Integration API for group profiles (no `clanbite_group` CPT and no group templates or blocks in core).
 *
 * A separate plugin may register `clanbite_group`, group blocks, templates, and virtual template parts;
 * it should use these helpers and filters so the Events extension and shared front-end patterns stay aligned.
 *
 * Core loads this file on every request so `clanbite_register_group_subpage()` and related helpers exist
 * even when no groups plugin is active (they no-op or return safe defaults).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register a group profile subpage (tab) for front-end group profiles.
 *
 * Call on `init` (priority ≤ 20) so subpages exist before block rendering.
 *
 * @param string $slug Unique slug (URL segment after the group base URL).
 * @param array  $args {
 *     Optional. Subpage arguments.
 *
 *     @type string $label        Human-readable nav label.
 *     @type string $template_id  FSE template id (default `clanbite-group-{slug}`).
 *     @type string $default_blocks Optional default block markup for the template.
 *     @type string $capability   Capability required to see the tab in nav (default `read`).
 *     @type int    $position     Sort order (lower first).
 * }
 * @return void
 */
function clanbite_register_group_subpage( string $slug, array $args = array() ): void {
	if ( function_exists( 'clanbite_register_profile_subpage' ) ) {
		clanbite_register_profile_subpage( 'group', $slug, $args );
	}
}

/**
 * All registered group profile subpages.
 *
 * @return array<string, array<string, mixed>>
 */
function clanbite_get_group_subpages(): array {
	return function_exists( 'clanbite_get_profile_subpages' ) ? clanbite_get_profile_subpages( 'group' ) : array();
}

/**
 * Resolve a single group profile subpage config by slug.
 *
 * @param string $slug Subpage slug.
 * @return array<string, mixed>|null
 */
function clanbite_get_group_subpage( string $slug ): ?array {
	return function_exists( 'clanbite_get_profile_subpage' ) ? clanbite_get_profile_subpage( 'group', $slug ) : null;
}

/**
 * Front-end URL for group settings / manage (extension-defined).
 *
 * @param int $group_id Group object ID (meaning defined by the extension).
 * @return string Empty string when no extension provides a URL.
 */
function clanbite_groups_get_manage_url( int $group_id ): string {
	if ( $group_id < 1 ) {
		return '';
	}

	/**
	 * Filter the group “settings” / manage URL used in group profile navigation.
	 *
	 * @param string $url      URL or empty string.
	 * @param int    $group_id Group ID.
	 */
	return (string) apply_filters( 'clanbite_groups_manage_url', '', $group_id );
}

/**
 * Whether a viewer may see what belongs to a group, such as its events.
 *
 * The plugin that owns `clanbite_group` answers through the filter, applying the group's join setting and bans. This is a
 * ceiling: an item's own visibility can be stricter, never wider.
 *
 * @since 1.7.0
 *
 * @param int      $group_id Group post ID.
 * @param int|null $user_id  Viewer, or null for the current user (0 when signed out).
 * @return bool
 */
function clanbite_groups_viewer_can_see_content( int $group_id, ?int $user_id = null ): bool {
	$user_id = (int) ( $user_id ?? get_current_user_id() );

	/**
	 * Filters whether a viewer may see a group's content.
	 *
	 * @since 1.7.0
	 *
	 * @param bool $can      Default true.
	 * @param int  $group_id Group post ID.
	 * @param int  $user_id  Viewer (0 when signed out).
	 */
	return (bool) apply_filters( 'clanbite_groups_viewer_can_see_content', true, $group_id, $user_id );
}

/**
 * Whether the user may see the Settings link in group profile navigation.
 *
 * Extensions implementing group roles should filter this (e.g. group admin/editor).
 *
 * @param int      $group_id Group ID.
 * @param int|null $user_id  User ID or null for the current user.
 * @return bool
 */
function clanbite_groups_user_can_manage( int $group_id, ?int $user_id = null ): bool {
	if ( $group_id < 1 ) {
		return false;
	}

	$user_id = $user_id ?? get_current_user_id();
	if ( $user_id < 1 ) {
		return false;
	}

	/**
	 * Filter whether the user may open group settings from the profile nav.
	 *
	 * @param bool $can      Whether the link should show. Default false.
	 * @param int  $group_id Group ID.
	 * @param int  $user_id  User ID.
	 */
	return (bool) apply_filters( 'clanbite_groups_user_can_manage', false, $group_id, $user_id );
}

/**
 * Whether the user is a member of the group (visibility checks, e.g. team_members–style event access).
 *
 * The plugin that registers `clanbite_group` should filter this; core defaults to false.
 *
 * @param int      $group_id Group object ID.
 * @param int|null $user_id  User ID or null for the current user.
 * @return bool
 */
function clanbite_groups_user_is_member( int $group_id, ?int $user_id = null ): bool {
	if ( $group_id < 1 ) {
		return false;
	}

	$user_id = $user_id ?? get_current_user_id();
	if ( $user_id < 1 ) {
		return false;
	}

	/**
	 * Filter whether a user counts as a group member for core features (events, etc.).
	 *
	 * @param bool $is_member Whether the user is a member.
	 * @param int  $group_id  Group ID.
	 * @param int  $user_id   User ID.
	 */
	return (bool) apply_filters( 'clanbite_groups_user_is_member', false, $group_id, $user_id );
}

/**
 * Group ID for profile header/nav (singular `clanbite_group` or `clanbite_group_profile_nav_context`).
 *
 * @return int
 */
function clanbite_group_profile_context_group_id(): int {
	/**
	 * Virtual group profile context when not on a singular `clanbite_group` post.
	 *
	 * @param array<string, mixed>|null $context Context or null.
	 */
	$virtual = apply_filters( 'clanbite_group_profile_nav_context', null );
	if ( is_array( $virtual ) && isset( $virtual['group_id'] ) && (int) $virtual['group_id'] > 0 ) {
		return (int) $virtual['group_id'];
	}

	if ( is_singular( 'clanbite_group' ) ) {
		$qid = (int) get_queried_object_id();
		if ( $qid > 0 ) {
			return $qid;
		}
	}

	// The block editor's server render previews the first published group, when the site has one.
	if ( function_exists( 'clanbite_is_editor_preview' ) && clanbite_is_editor_preview() ) {
		$groups = get_posts(
			array(
				'post_type'      => 'clanbite_group',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		return array() !== $groups ? (int) $groups[0] : 0;
	}

	return 0;
}

/**
 * Active group profile sub-route slug (extension filter or `clanbite_group_subpage`).
 *
 * @return string
 */
function clanbite_group_profile_route_current_slug(): string {
	/**
	 * Virtual group profile context when not on a singular `clanbite_group` post.
	 *
	 * @param array<string, mixed>|null $context Context or null.
	 */
	$virtual = apply_filters( 'clanbite_group_profile_nav_context', null );
	if ( is_array( $virtual ) && isset( $virtual['current_slug'] ) ) {
		return sanitize_key( (string) $virtual['current_slug'] );
	}

	if ( is_singular( 'clanbite_group' ) ) {
		return sanitize_key( (string) get_query_var( 'clanbite_group_subpage' ) );
	}

	return '';
}

/**
 * Get the configured group avatar shape from settings.
 *
 * @return string Avatar shape: 'circle', 'square', or 'hexagon'. Default 'circle'.
 */
function clanbite_groups_get_avatar_shape(): string {
	$settings = get_option( 'clanbite_groups_settings', array() );
	$shape    = 'circle';

	if ( is_array( $settings ) && isset( $settings['group_avatar_shape'] ) ) {
		$shape = sanitize_key( (string) $settings['group_avatar_shape'] );
	}

	// Sanitize and validate.
	if ( ! in_array( $shape, array( 'circle', 'square', 'hexagon' ), true ) ) {
		$shape = 'circle';
	}

	/**
	 * Filter the group avatar shape.
	 *
	 * @param string $shape Avatar shape.
	 */
	return (string) apply_filters( 'clanbite_groups_avatar_shape', $shape );
}
