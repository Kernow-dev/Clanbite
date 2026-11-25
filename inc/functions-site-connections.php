<?php
/**
 * Consent for talking to other Clanbite sites.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clanbite_site_connections_enabled' ) ) {
	/**
	 * Whether a site administrator has turned on connections with other Clanbite sites (Clanbite → Settings → General).
	 *
	 * Off until they do. While off, Clanbite makes no request to another site for team challenges or matches, and its public
	 * endpoints for other sites refuse to answer. Check this before adding any feature that contacts another site.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	function clanbite_site_connections_enabled(): bool {
		$settings = get_option( 'clanbite_general_settings', array() );
		$enabled  = is_array( $settings ) && ! empty( $settings['connect_clanbite_sites'] );

		/**
		 * Filters whether connections with other Clanbite sites are allowed.
		 *
		 * Returning true counts as the site owner's consent: only do so when they have agreed to what is sent.
		 *
		 * @since 1.7.0
		 *
		 * @param bool $enabled Whether the administrator turned the setting on.
		 */
		return (bool) apply_filters( 'clanbite_site_connections_enabled', $enabled );
	}
}

if ( ! function_exists( 'clanbite_allowed_remote_image_url' ) ) {
	/**
	 * An image address that may be shown: anything on this site, or on another site only while connections are on.
	 *
	 * Showing another site's image makes each visitor's browser contact that site.
	 *
	 * @since 1.7.0
	 *
	 * @param string $url Image address.
	 * @return string The address, or an empty string when it may not be shown.
	 */
	function clanbite_allowed_remote_image_url( string $url ): string {
		if ( '' === $url || clanbite_site_connections_enabled() ) {
			return $url;
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( '' === $host ) {
			return $url;
		}
		$own = array_map(
			static fn( string $address ): string => strtolower( (string) wp_parse_url( $address, PHP_URL_HOST ) ),
			array( home_url(), (string) ( wp_get_upload_dir()['baseurl'] ?? '' ) )
		);

		return in_array( $host, $own, true ) ? $url : '';
	}
}
