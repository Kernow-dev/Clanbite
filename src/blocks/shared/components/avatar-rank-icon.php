<?php
/**
 * Avatar rank icon overlay (deprecated).
 *
 * The rank badge is now part of the avatar, drawn by {@see clanbite_avatar_badge_html()} in `inc/avatar-helpers.php`: a small
 * shape at the avatar's lower right holding the level icon or number. This file only keeps the old function name so
 * third-party code does not fatal.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render avatar rank icon overlay for a user.
 *
 * @deprecated Use `clanbite_render_avatar()`: the badge is part of the avatar and follows the Ranks settings.
 *
 * @param int    $user_id User ID.
 * @param string $shape   Avatar shape.
 * @return string Always an empty string.
 */
function clanbite_render_avatar_rank_icon( int $user_id, string $shape = 'circle' ): string {
	_deprecated_function( __FUNCTION__, '1.0.0', 'clanbite_render_avatar()' );

	return '';
}
