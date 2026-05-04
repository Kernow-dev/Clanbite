<?php
/**
 * Lazy-load wp-admin helper includes for APIs normally loaded only in wp-admin.
 *
 * Do not use this to bootstrap WordPress (never load `wp-load.php`, `wp-blog-header.php`,
 * or `wp-config.php` from the plugin). These helpers mirror core patterns (see
 * {@see \WP_REST_Attachments_Controller}) by loading the small administration API
 * files that define `wp_handle_upload()`, `media_handle_upload()`, `dbDelta()`, etc.,
 * only when a caller is about to invoke those functions.
 *
 * Paths are composed with {@see path_join()} and {@see ABSPATH} for portability across installs.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Absolute filesystem path to a bundled administration API partial (basename only).
 *
 * @param string $basename File name only (e.g. `file.php`, `upgrade.php`).
 * @return string
 */
function clanbite_wp_admin_includes_filepath( string $basename ): string {
	return path_join( path_join( path_join( ABSPATH, 'wp-admin' ), 'includes' ), $basename );
}

/**
 * Ensures the core file upload / Filesystem API helpers are loaded.
 *
 * @return void
 */
function clanbite_require_wp_admin_file_includes(): void {
	if ( function_exists( 'wp_handle_upload' ) || function_exists( 'WP_Filesystem' ) ) {
		return;
	}

	require_once clanbite_wp_admin_includes_filepath( 'file.php' );
}

/**
 * Ensures image helpers are loaded for {@see wp_generate_attachment_metadata()} outside wp-admin.
 *
 * @return void
 */
function clanbite_require_wp_admin_image_includes(): void {
	if ( function_exists( 'wp_read_image_metadata' ) ) {
		return;
	}

	clanbite_require_wp_admin_file_includes();
	require_once clanbite_wp_admin_includes_filepath( 'image.php' );
}

/**
 * Ensures `media_handle_upload()` and its dependencies are available.
 *
 * @return void
 */
function clanbite_require_wp_admin_media_includes(): void {
	if ( function_exists( 'media_handle_upload' ) ) {
		return;
	}

	require_once clanbite_wp_admin_includes_filepath( 'file.php' );
	require_once clanbite_wp_admin_includes_filepath( 'media.php' );
	require_once clanbite_wp_admin_includes_filepath( 'image.php' );
}

/**
 * Ensures `dbDelta()` is available for schema upgrades.
 *
 * @return void
 */
function clanbite_require_wp_admin_upgrade_includes(): void {
	if ( function_exists( 'dbDelta' ) ) {
		return;
	}

	require_once clanbite_wp_admin_includes_filepath( 'upgrade.php' );
}

/**
 * WordPress Filesystem API using direct PHP I/O (local disk only).
 *
 * Call only after validating that paths live under `wp_upload_dir()['basedir']` (or another
 * trusted tree). Prefer this over raw `rename()` / `copy()` in plugin code.
 *
 * @return \WP_Filesystem_Direct
 */
function clanbite_wp_filesystem_direct(): \WP_Filesystem_Direct {
	require_once clanbite_wp_admin_includes_filepath( 'class-wp-filesystem-base.php' );
	require_once clanbite_wp_admin_includes_filepath( 'class-wp-filesystem-direct.php' );

	return new \WP_Filesystem_Direct( false );
}
