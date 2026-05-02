<?php
/**
 * Isolated Clanbite uploads under uploads/clanbite/… and hidden from the Media Library.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post meta flag: attachment should not appear in Media Library / REST collections.
 */
const CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY = '_clanbite_hide_from_library';

/**
 * Register filters once.
 *
 * @return void
 */
function clanbite_private_media_bootstrap(): void {
	add_filter( 'rest_attachment_query', 'clanbite_private_media_filter_rest_attachment_query', 20, 2 );
	add_filter( 'ajax_query_attachments_args', 'clanbite_private_media_filter_ajax_attachments' );
	add_action( 'pre_get_posts', 'clanbite_private_media_filter_upload_admin_list' );
	add_action( 'added_post_meta', 'clanbite_private_media_mark_team_attachment', 10, 4 );
	add_action( 'updated_post_meta', 'clanbite_private_media_mark_team_attachment', 10, 4 );
	add_action( 'added_user_meta', 'clanbite_private_media_mark_player_attachment', 10, 4 );
	add_action( 'updated_user_meta', 'clanbite_private_media_mark_player_attachment', 10, 4 );
}

/**
 * Hide team avatar/cover attachments from the library when IDs are saved (including block editor).
 *
 * @param int    $meta_id    Meta ID.
 * @param int    $object_id  Post ID.
 * @param string $meta_key   Key.
 * @param mixed  $meta_value Value.
 * @return void
 */
function clanbite_private_media_mark_team_attachment( $meta_id, $object_id, $meta_key, $meta_value ): void {
	unset( $meta_id );
	if ( ! in_array( $meta_key, array( 'cp_team_avatar_id', 'cp_team_cover_id' ), true ) ) {
		return;
	}
	if ( 'clanbite_team' !== get_post_type( (int) $object_id ) ) {
		return;
	}
	$aid = absint( $meta_value );
	if ( $aid > 0 ) {
		update_post_meta( $aid, CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY, '1' );
	}
}

/**
 * Hide player avatar/cover attachments from the library when IDs are saved (including block editor).
 *
 * @param int    $meta_id    Meta ID.
 * @param int    $user_id    User ID.
 * @param string $meta_key   Key.
 * @param mixed  $meta_value Value.
 * @return void
 */
function clanbite_private_media_mark_player_attachment( $meta_id, $user_id, $meta_key, $meta_value ): void {
	unset( $meta_id );
	if ( ! in_array( $meta_key, array( 'cp_player_avatar_id', 'cp_player_cover_id' ), true ) ) {
		return;
	}
	$aid = absint( $meta_value );
	if ( $aid > 0 ) {
		update_post_meta( $aid, CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY, '1' );
	}
}

/**
 * Hide flagged attachments from REST media collections (block editor library uses this).
 *
 * @param array<string, mixed>           $args    Query args.
 * @param \WP_REST_Request               $request Request.
 * @return array<string, mixed>
 */
function clanbite_private_media_filter_rest_attachment_query( array $args, $request ): array {
	unset( $request );
	if ( empty( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
		$args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Initialize before appending NOT EXISTS clause.
	}
	$args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Single NOT EXISTS clause to hide private uploads.
		'key'     => CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY,
		'compare' => 'NOT EXISTS',
	);
	return $args;
}

/**
 * Hide flagged attachments from the classic media modal AJAX query.
 *
 * @param array<string, mixed> $query Query args.
 * @return array<string, mixed>
 */
function clanbite_private_media_filter_ajax_attachments( array $query ): array {
	if ( empty( $query['meta_query'] ) || ! is_array( $query['meta_query'] ) ) {
		$query['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Initialize before appending NOT EXISTS clause.
	}
	$query['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Single NOT EXISTS clause for media modal.
		'key'     => CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY,
		'compare' => 'NOT EXISTS',
	);
	return $query;
}

/**
 * Hide flagged attachments on the Media admin screen list.
 *
 * @param \WP_Query $query Query.
 * @return void
 */
function clanbite_private_media_filter_upload_admin_list( $query ): void {
	if ( ! is_admin() || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'upload' !== $screen->id ) {
		return;
	}

	if ( 'attachment' !== $query->get( 'post_type' ) ) {
		return;
	}

	$mq = $query->get( 'meta_query' );
	if ( ! is_array( $mq ) ) {
		$mq = array();
	}
	$mq[] = array(
		'key'     => CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY,
		'compare' => 'NOT EXISTS',
	);
	$query->set( 'meta_query', $mq );
}

/**
 * Handle a single image upload into uploads/clanbite/{subdir}/ with a stable filename.
 *
 * Marks the attachment so it stays out of the Media Library UI. Direct REST fetch by ID
 * (e.g. block editor preview) still works.
 *
 * @param string $files_key   Key in $_FILES (e.g. team_avatar).
 * @param int    $post_parent Attachment parent post ID (0 for not linked).
 * @param string $subdir      Path under uploads, e.g. clanbite/teams/12 (no leading/trailing slashes).
 * @param string $filename_base Filename without extension, e.g. avatar or cover.
 * @return int|\WP_Error Attachment ID or error.
 */
function clanbite_handle_isolated_image_upload( string $files_key, int $post_parent, string $subdir, string $filename_base ) {
	clanbite_require_wp_admin_media_includes();

	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- `$_FILES` normalized before `media_handle_upload()`; callers verify auth and nonces.
	try {
		if ( empty( $_FILES[ $files_key ] ) || ! is_array( $_FILES[ $files_key ] ) ) {
			return new \WP_Error( 'clanbite_no_file', __( 'No file was uploaded.', 'clanbite' ) );
		}

		$file = clanbite_sanitize_files_array_entry( wp_unslash( $_FILES[ $files_key ] ) );
		if ( null === $file ) {
			return new \WP_Error( 'clanbite_no_file', __( 'No file was uploaded.', 'clanbite' ) );
		}

		// `media_handle_upload()` reads `$_FILES[ $field ]`; replace with the sanitized row.
		$_FILES[ $files_key ] = $file;

		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new \WP_Error( 'clanbite_upload_err', __( 'File upload failed.', 'clanbite' ) );
		}

		$subdir = trim( str_replace( '\\', '/', $subdir ), '/' );
		if ( '' === $subdir ) {
			return new \WP_Error( 'clanbite_bad_subdir', __( 'Invalid upload path.', 'clanbite' ) );
		}

		$filename_base = sanitize_file_name( $filename_base );
		if ( '' === $filename_base ) {
			return new \WP_Error( 'clanbite_bad_name', __( 'Invalid filename.', 'clanbite' ) );
		}

		$orig_name = isset( $file['name'] ) ? (string) $file['name'] : '';
		$ext       = strtolower( (string) pathinfo( $orig_name, PATHINFO_EXTENSION ) );
		if ( '' === $orig_name ) {
			return new \WP_Error( 'clanbite_no_name', __( 'Invalid upload.', 'clanbite' ) );
		}
		if ( '' === $ext ) {
			$chk = wp_check_filetype( $orig_name );
			if ( ! empty( $chk['ext'] ) ) {
				$ext = strtolower( (string) $chk['ext'] );
			}
		}

		if ( '' === $ext ) {
			return new \WP_Error( 'clanbite_no_ext', __( 'Could not determine file type.', 'clanbite' ) );
		}

		$final_name = $filename_base . '.' . $ext;

		$upload_dir_cb = static function ( array $dirs ) use ( $subdir ): array {
			$dirs['subdir'] = '/' . $subdir;
			$dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
			$dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
			return $dirs;
		};

		$prefilter_cb = static function ( array $file ) use ( $final_name ): array {
			$file['name'] = $final_name;
			return $file;
		};

		add_filter( 'upload_dir', $upload_dir_cb );
		add_filter( 'wp_handle_upload_prefilter', $prefilter_cb );

		$attachment_id = media_handle_upload( $files_key, $post_parent );

		remove_filter( 'upload_dir', $upload_dir_cb );
		remove_filter( 'wp_handle_upload_prefilter', $prefilter_cb );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$attachment_id = (int) $attachment_id;
		update_post_meta( $attachment_id, CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY, '1' );

		return $attachment_id;
	} finally {
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
	}
}

add_action( 'plugins_loaded', 'clanbite_private_media_bootstrap', 2 );
