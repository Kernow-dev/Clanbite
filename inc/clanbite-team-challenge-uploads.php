<?php
/**
 * Team challenge logo upload paths under uploads/clanbite/teams/…/matches/….
 *
 * Logos are stored in a staging folder until a match exists, then moved to the match folder on accept.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post meta on attachments uploaded via {@see Team_Challenges::rest_upload_challenge_media()}.
 * Value is the challenged `cp_team` ID (string) the upload was scoped to.
 */
const CLANBITE_TEAM_CHALLENGE_LOGO_TEAM_META = '_clanbite_team_challenge_logo';

/**
 * Relative uploads path (no leading slash): clanbite/teams/{team_id}/matches/{match_id}
 *
 * @param int $team_id  Challenged team post ID on this site.
 * @param int $match_id Match post ID.
 * @return string
 */
function clanbite_team_match_logo_relative_dir( int $team_id, int $match_id ): string {
	return 'clanbite/teams/' . max( 0, $team_id ) . '/matches/' . max( 0, $match_id );
}

/**
 * Staging relative dir before a match exists: clanbite/teams/{team_id}/matches/staging
 *
 * @param int $challenged_team_id Challenged `cp_team` ID.
 * @return string
 */
function clanbite_team_challenge_logo_staging_relative_dir( int $challenged_team_id ): string {
	return 'clanbite/teams/' . max( 0, $challenged_team_id ) . '/matches/staging';
}

/**
 * Run a callback with `upload_dir` forced to a subdirectory of the uploads base (path + url + subdir).
 *
 * @param string   $relative_dir Path under uploads base, e.g. `clanbite/teams/1/matches/staging` (no leading slash).
 * @param callable $callback     Invoked while the filter is active.
 * @return mixed Return value of `$callback`.
 */
function clanbite_with_upload_subdir( string $relative_dir, callable $callback ) {
	$relative_dir = trim( str_replace( '\\', '/', $relative_dir ), '/' );
	$filter       = static function ( array $uploads ) use ( $relative_dir ): array {
		if ( ! empty( $uploads['error'] ) ) {
			return $uploads;
		}
		$subdir            = '/' . $relative_dir;
		$uploads['subdir'] = $subdir;
		$uploads['path']   = $uploads['basedir'] . $subdir;
		$uploads['url']    = $uploads['baseurl'] . $subdir;
		return $uploads;
	};

	add_filter( 'upload_dir', $filter, 99 );
	$out = $callback();
	remove_filter( 'upload_dir', $filter, 99 );

	return $out;
}

/**
 * Whether an attachment was uploaded as a team-challenge logo for the given challenged team.
 *
 * @param int $attachment_id      Attachment ID.
 * @param int $challenged_team_id Expected challenged team ID.
 * @return bool
 */
function clanbite_team_challenge_logo_attachment_matches_team( int $attachment_id, int $challenged_team_id ): bool {
	if ( $attachment_id < 1 || $challenged_team_id < 1 ) {
		return false;
	}
	$stored = get_post_meta( $attachment_id, CLANBITE_TEAM_CHALLENGE_LOGO_TEAM_META, true );

	return (string) $challenged_team_id === (string) $stored;
}

/**
 * Move a challenge-logo attachment into the match directory and refresh metadata.
 *
 * @param int $attachment_id      Attachment to move.
 * @param int $challenged_team_id Challenged team ID (must match {@see CLANBITE_TEAM_CHALLENGE_LOGO_TEAM_META}).
 * @param int $match_id           New match ID.
 * @return bool True when the file now lives under the match directory (or already did).
 *
 * When `WP_DEBUG_LOG` is enabled, missing source paths are written to the debug log to simplify host debugging.
 */
function clanbite_relocate_team_challenge_logo_to_match_dir( int $attachment_id, int $challenged_team_id, int $match_id ): bool {
	if ( $attachment_id < 1 || $match_id < 1 || ! clanbite_team_challenge_logo_attachment_matches_team( $attachment_id, $challenged_team_id ) ) {
		return false;
	}

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return false;
	}

	$old_path = get_attached_file( $attachment_id );
	if ( ! is_string( $old_path ) || '' === $old_path || ! file_exists( $old_path ) ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Opt-in debug logging for failed relocations.
			error_log(
				sprintf(
					'Clanbite: team challenge logo relocate skipped; source missing or invalid path (attachment %1$d, path "%2$s").',
					$attachment_id,
					is_string( $old_path ) ? $old_path : ''
				)
			);
		}
		return false;
	}

	$relative_dir = clanbite_team_match_logo_relative_dir( $challenged_team_id, $match_id );
	$dest_dir     = path_join( $uploads['basedir'], $relative_dir );
	if ( ! wp_mkdir_p( $dest_dir ) ) {
		return false;
	}

	$filename   = wp_basename( $old_path );
	$dest_path  = path_join( $dest_dir, wp_unique_filename( $dest_dir, $filename ) );
	$old_norm   = wp_normalize_path( $old_path );
	$dest_norm  = wp_normalize_path( $dest_path );

	if ( $old_norm === $dest_norm ) {
		return true;
	}

	$basedir_root = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );
	if ( 0 !== strpos( $old_norm, $basedir_root ) || 0 !== strpos( $dest_norm, $basedir_root ) ) {
		return false;
	}

	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( is_array( $meta ) && ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		$old_dir = dirname( $old_path );
		foreach ( $meta['sizes'] as $size ) {
			if ( empty( $size['file'] ) || ! is_string( $size['file'] ) ) {
				continue;
			}
			$thumb = path_join( $old_dir, $size['file'] );
			if ( is_string( $thumb ) && file_exists( $thumb ) ) {
				wp_delete_file( $thumb );
			}
		}
	}

	if ( ! file_exists( $old_path ) ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Opt-in debug logging for failed relocations.
			error_log(
				sprintf(
					'Clanbite: team challenge logo relocate aborted; source file missing before move (attachment %1$d, path "%2$s").',
					$attachment_id,
					$old_path
				)
			);
		}
		return false;
	}

	clanbite_require_wp_admin_file_includes();

	// Use WordPress filesystem classes (not bare `rename()` / `copy()`). Paths are confined to
	// `wp_upload_dir()['basedir']` above — never the plugin directory.
	$fs    = clanbite_wp_filesystem_direct();
	$moved = $fs->move( $old_path, $dest_path, true );
	if ( ! $moved ) {
		$moved = ( $fs->copy( $old_path, $dest_path, true ) && $fs->delete( $old_path, false ) );
	}

	if ( ! $moved ) {
		return false;
	}

	$relative_file = $relative_dir . '/' . wp_basename( $dest_path );
	wp_update_attached_file( $attachment_id, $relative_file );
	clanbite_require_wp_admin_image_includes();
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $dest_path ) );

	return true;
}
