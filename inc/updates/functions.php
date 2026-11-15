<?php
/**
 * Licence and update helpers for themes and add-ons.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clanbite_update_server_url' ) ) {
	/**
	 * Base URL of the Clanbite Extension Server that licenses and delivers add-on updates.
	 *
	 * Define `CLANBITE_UPDATE_SERVER` in `wp-config.php` to point a site at another server (a staging server, for example).
	 * Add-on plugins must carry an `Update URI` header on the same host.
	 *
	 * @since 1.6.0
	 *
	 * @return string URL without a trailing slash.
	 */
	function clanbite_update_server_url(): string {
		$url = defined( 'CLANBITE_UPDATE_SERVER' ) && is_string( CLANBITE_UPDATE_SERVER ) ? CLANBITE_UPDATE_SERVER : 'https://services.clanbite.com';

		/**
		 * Filters the update server URL.
		 *
		 * Only HTTPS URLs are used (plain HTTP is accepted on `local` environments). Prefer the constant: filters added by a
		 * theme load too late for the update check.
		 *
		 * @since 1.6.0
		 *
		 * @param string $url Base URL.
		 */
		$url = (string) apply_filters( 'clanbite_update_server_url', $url );

		return untrailingslashit( esc_url_raw( $url ) );
	}
}

if ( ! function_exists( 'clanbite_release_public_keys' ) ) {
	/**
	 * Ed25519 public keys (base64) that add-on release signatures must verify against.
	 *
	 * When this list is not empty every add-on update must carry a valid signature, or it is discarded before installation. Define
	 * `CLANBITE_RELEASE_PUBLIC_KEY` in `wp-config.php`, or filter `clanbite_release_public_keys` (several keys while rotating).
	 *
	 * @since 1.6.0
	 *
	 * @return list<string>
	 */
	function clanbite_release_public_keys(): array {
		$keys = array();
		if ( defined( 'CLANBITE_RELEASE_PUBLIC_KEY' ) && is_string( CLANBITE_RELEASE_PUBLIC_KEY ) && '' !== CLANBITE_RELEASE_PUBLIC_KEY ) {
			$keys[] = CLANBITE_RELEASE_PUBLIC_KEY;
		}

		/**
		 * Filters the trusted release public keys (base64 Ed25519).
		 *
		 * @since 1.6.0
		 *
		 * @param string[] $keys Public keys. Empty means signatures are not checked.
		 */
		return array_values( array_filter( array_map( 'strval', (array) apply_filters( 'clanbite_release_public_keys', $keys ) ) ) );
	}
}

if ( ! function_exists( 'clanbite_get_license_status' ) ) {
	/**
	 * Summary of this site's Clanbite licence, for add-ons that want to adapt (never includes the key).
	 *
	 * @since 1.6.0
	 *
	 * @return array{activated: bool, status: string, plan: string, unlimited: bool, expires: int, entitlements: list<array{slug: string, name: string, state: string, expires: int, free: bool}>} `status` is `active`, `expired`, `suspended`, `revoked` or `none`; `expires` is a timestamp (0 = none).
	 */
	function clanbite_get_license_status(): array {
		return \Kernowdev\Clanbite\Updates\License_Client::public_status();
	}
}

if ( ! function_exists( 'clanbite_license_allows_extension' ) ) {
	/**
	 * Whether this site's licence currently covers a plugin slug (for example `clanbite-points`).
	 *
	 * Based on the last server answer, so it is cheap; it never makes an HTTP request.
	 *
	 * @since 1.6.0
	 *
	 * @param string $slug Plugin folder slug.
	 * @return bool
	 */
	function clanbite_license_allows_extension( string $slug ): bool {
		foreach ( clanbite_get_license_status()['entitlements'] as $row ) {
			if ( $row['slug'] === $slug ) {
				return 'ok' === $row['state'];
			}
		}

		return false;
	}
}
