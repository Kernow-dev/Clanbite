<?php
/**
 * Shared sanitization for superglobals used across extensions (POST, request URI).
 *
 * POST helpers assume the caller has already verified a nonce or equivalent capability
 * (e.g. `check_admin_referer`, `save_post` hooks). Do not call them on unauthenticated input.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize `$_SERVER['REQUEST_URI']` for parsing or URL building.
 *
 * @param string $when_missing Value when the header is missing or empty after sanitization.
 * @return string
 */
function clanbite_sanitize_request_uri( string $when_missing = '' ): string {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return $when_missing;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Request path; normalized below.
	$out = sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) );

	return '' === $out ? $when_missing : $out;
}

/**
 * Request path after the home URL path (no leading/trailing slashes), for virtual route matching.
 *
 * @return string
 */
function clanbite_get_canonical_request_path(): string {
	$uri = clanbite_sanitize_request_uri( '' );
	if ( '' === $uri ) {
		return '';
	}

	$path = wp_parse_url( $uri, PHP_URL_PATH );
	if ( ! is_string( $path ) ) {
		return '';
	}

	$path = rawurldecode( $path );

	$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = is_string( $home_path ) ? $home_path : '';

	if ( '' !== $home_path && '/' !== $home_path ) {
		$home_trim = untrailingslashit( $home_path );
		if ( str_starts_with( $path, $home_trim ) ) {
			$path = substr( $path, strlen( $home_trim ) );
		}
	}

	$path = ltrim( $path, '/' );
	$path = preg_replace( '#^index\.php/?#i', '', $path );

	return trim( (string) $path, '/' );
}

/**
 * Read a scalar `$_POST` field as plain text.
 *
 * Call only after nonce/capability checks in the same request.
 *
 * @param string $key     Field name.
 * @param string $default Default when missing.
 * @return string
 */
function clanbite_request_post_text( string $key, string $default = '' ): string {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verified nonce/caps; see file header.
	if ( ! isset( $_POST[ $key ] ) ) {
		return $default;
	}

	return sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Read a `$_POST` field as block-safe HTML (`wp_kses_post`).
 *
 * @param string $key     Field name.
 * @param string $default Default when missing.
 * @return string
 */
function clanbite_request_post_html( string $key, string $default = '' ): string {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verified nonce/caps; see file header.
	if ( ! isset( $_POST[ $key ] ) ) {
		return $default;
	}

	return wp_kses_post( wp_unslash( (string) $_POST[ $key ] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Read a `$_POST` field as a non-negative integer.
 *
 * Use only after the request’s nonce (or other auth gate) has been verified in the same handler
 * (e.g. `check_admin_referer`, `check_ajax_referer`, `wp_verify_nonce` for REST). This helper
 * does not know the form’s nonce action name.
 *
 * @param string $key     Field name.
 * @param int    $default Default when missing or invalid.
 * @return int
 */
function clanbite_request_post_absint( string $key, int $default = 0 ): int {
	// phpcs:disable WordPress.Security.NonceVerification.Missing,PluginCheck.Security.VerifyNonce -- Caller verifies nonce before POST reads; cannot embed nonce action inside generic helper.
	if ( ! isset( $_POST[ $key ] ) ) {
		return $default;
	}

	return absint( wp_unslash( $_POST[ $key ] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing,PluginCheck.Security.VerifyNonce
}

/**
 * Whether a POST key is present, even when the value is empty (vs `isset` alone).
 *
 * @param string $key Field name.
 * @return bool
 */
function clanbite_request_post_has_key( string $key ): bool {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verified nonce/caps; see file header.
	return array_key_exists( $key, $_POST );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Whether a POST field is present and truthy (checkbox / remove flag).
 *
 * Matches historical `! empty( $_POST[ $key ] )` for typical HTML checkboxes (`1`, `on`, etc.).
 *
 * @param string $key Field name.
 * @return bool
 */
function clanbite_request_post_flag( string $key ): bool {
	// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Caller verified nonce/caps; loose truthy check for checkbox/removal fields.
	if ( ! isset( $_POST[ $key ] ) ) {
		return false;
	}

	$raw = wp_unslash( $_POST[ $key ] );
	if ( is_array( $raw ) ) {
		return false;
	}

	return ! empty( $raw );
	// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
}

/**
 * Sanitize the full `$_POST` tree for the player settings AJAX save entry point.
 *
 * Call only after nonce verification. Unknown keys are treated as plain text; known rich-text
 * fields use {@see wp_kses_post()}.
 *
 * @param array<string, mixed> $post Raw POST (`wp_unslash( $_POST )` or `$_POST` with slashes intact — values are unslashed per field).
 * @return array<string, mixed>
 */
function clanbite_sanitize_player_settings_post_array( array $post ): array {
	$skip = array(
		'action'                               => true,
		'_clanbite_profile_settings_save_nonce' => true,
		'_wp_http_referer'                     => true,
	);

	$html_keys = array(
		'profile_description' => true,
	);

	$out = array();

	foreach ( $post as $key => $value ) {
		$key = sanitize_key( (string) $key );
		if ( '' === $key || isset( $skip[ $key ] ) ) {
			continue;
		}

		if ( is_array( $value ) ) {
			$out[ $key ] = map_deep( wp_unslash( $value ), 'sanitize_text_field' );
			continue;
		}

		if ( ! is_string( $value ) ) {
			continue;
		}

		$unslashed = wp_unslash( $value );

		if ( isset( $html_keys[ $key ] ) ) {
			$out[ $key ] = wp_kses_post( $unslashed );
		} else {
			$out[ $key ] = sanitize_text_field( $unslashed );
		}
	}

	return $out;
}

/**
 * Normalize one PHP `$_FILES` row for `wp_handle_upload()` / `media_handle_upload()`.
 *
 * Does not validate `tmp_name` against `is_uploaded_file()` (callers / WordPress core do).
 *
 * @param array<string, mixed> $file Raw upload row (typically `wp_unslash( $_FILES['field'] )`).
 * @return array{name: string, type: string, tmp_name: string, error: int, size: int}|null Null if structure invalid.
 */
function clanbite_sanitize_files_array_entry( array $file ): ?array {
	if ( ! isset( $file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size'] ) ) {
		return null;
	}

	$name = sanitize_file_name( (string) $file['name'] );
	if ( '' === $name ) {
		return null;
	}

	$tmp = (string) $file['tmp_name'];
	if ( '' === $tmp ) {
		return null;
	}

	return array(
		'name'     => $name,
		'type'     => sanitize_mime_type( (string) $file['type'] ),
		'tmp_name' => $tmp,
		'error'    => (int) $file['error'],
		'size'     => (int) $file['size'],
	);
}

/**
 * Whitelist and normalize upload payloads for the player settings save flow.
 *
 * @return array<string, array<string, mixed>> Map of field name to PHP file array shape.
 */
function clanbite_sanitize_player_settings_files_array(): array {
	$allowed_fields = array( 'profile_avatar', 'profile_cover' );
	$out            = array();

	foreach ( $allowed_fields as $field ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verified nonce before invoking.
		if ( empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ] ) ) {
			continue;
		}
		$file = clanbite_sanitize_files_array_entry( wp_unslash( $_FILES[ $field ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( null === $file ) {
			continue;
		}

		$out[ $field ] = $file;
	}

	return $out;
}

/**
 * Sanitize `$_POST` for the front-end team create form (`handle_create_team`).
 *
 * Call only after nonce verification. Preserves arbitrary extra field names from the form
 * (for example custom steps registered on `clanbite_team_create_form_step_*`) while
 * normalizing values to plain text or block-safe HTML for known rich fields.
 *
 * @return array<string, mixed>
 */
function clanbite_sanitize_team_create_post_array(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verified `check_admin_referer` before invoking.
	if ( empty( $_POST ) || ! is_array( $_POST ) ) {
		return array();
	}

	$post = wp_unslash( $_POST );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	$skip = array(
		'_clanbite_create_team_nonce' => true,
		'_wp_http_referer'              => true,
	);

	$html_keys = array(
		'team_description' => true,
	);

	$out = array();

	foreach ( $post as $key => $value ) {
		$key = sanitize_key( (string) $key );
		if ( '' === $key || isset( $skip[ $key ] ) ) {
			continue;
		}

		if ( is_array( $value ) ) {
			$out[ $key ] = map_deep( wp_unslash( $value ), 'sanitize_text_field' );
			continue;
		}

		if ( ! is_string( $value ) ) {
			continue;
		}

		$unslashed = wp_unslash( $value );

		if ( isset( $html_keys[ $key ] ) ) {
			$out[ $key ] = wp_kses_post( $unslashed );
		} else {
			$out[ $key ] = sanitize_text_field( $unslashed );
		}
	}

	return $out;
}
