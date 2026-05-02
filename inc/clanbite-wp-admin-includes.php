<?php
/**
 * Lazy-load wp-admin helper includes for APIs normally loaded only in wp-admin.
 *
 * Do not use this to bootstrap WordPress (never load `wp-load.php`, `wp-blog-header.php`,
 * or `wp-config.php` from the plugin). These wrappers only pull in specific
 * `wp-admin/includes/*.php` files immediately before calling functions defined there,
 * per WordPress guidance for REST/async uploads, attachment metadata, dbDelta, etc.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ensures `wp-admin/includes/file.php` is loaded (`wp_handle_upload`, `WP_Filesystem`, …).
 *
 * @return void
 */
function clanbite_require_wp_admin_file_includes(): void {
	if ( function_exists( 'wp_handle_upload' ) || function_exists( 'WP_Filesystem' ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
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
	require_once ABSPATH . 'wp-admin/includes/image.php';
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

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
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

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
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
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

	return new \WP_Filesystem_Direct( false );
}
