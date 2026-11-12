<?php
/**
 * Avatar progress ring (deprecated).
 *
 * The XP ring is now part of the avatar frame drawn by {@see clanbite_avatar_frame_svg()} in `inc/avatar-helpers.php`, in
 * every avatar shape and size. This file only keeps the old function name so third-party code does not fatal.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render avatar progress bar for a user.
 *
 * @deprecated Use `clanbite_render_avatar()`: the ring is part of the avatar and follows the Ranks settings.
 *
 * @param int    $user_id    User ID.
 * @param string $shape      Avatar shape.
 * @param string $size_class Avatar size class.
 * @return string Always an empty string.
 */
function clanbite_render_avatar_progress_bar( int $user_id, string $shape = 'circle', string $size_class = 'large' ): string {
	_deprecated_function( __FUNCTION__, '1.0.0', 'clanbite_render_avatar()' );

	return '';
}
