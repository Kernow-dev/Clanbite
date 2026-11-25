<?php
/**
 * Points sites with Clanbite add-ons to the separate Add-on Manager.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Compat;

defined( 'ABSPATH' ) || exit;

/**
 * Clanbite does not install, license or update add-ons: WordPress.org does not allow a directory plugin to do that from
 * another server. The Clanbite Add-on Manager, which people get from clanbite.com, does. When a site has add-ons but no
 * manager, this shows a notice with an ordinary link. It reads only the installed plugins' headers and contacts nothing,
 * and it never downloads, installs or activates the manager.
 */
final class Addon_Manager_Notice {

	/**
	 * Host named in the `Update URI` header of Clanbite's own add-ons. Compared as text only.
	 */
	private const ADDON_HOST = 'services.clanbite.com';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_notices', array( self::class, 'notice' ) );
	}

	/**
	 * Whether plugin headers belong to one of Clanbite's own add-ons.
	 *
	 * @param array<string, mixed> $data Plugin data from `get_plugins()`.
	 * @return bool
	 */
	public static function is_first_party( array $data ): bool {
		return self::ADDON_HOST === strtolower( (string) wp_parse_url( (string) ( $data['UpdateURI'] ?? '' ), PHP_URL_HOST ) );
	}

	/**
	 * Where people get the manager.
	 *
	 * @return string
	 */
	public static function download_page_url(): string {
		return 'https://clanbite.com/add-on-manager/';
	}

	/**
	 * Say that add-on updates need the manager.
	 *
	 * @return void
	 */
	public static function notice(): void {
		if ( defined( 'CLANBITE_ADDON_MANAGER_VERSION' ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'update-core', 'toplevel_page_clanbite' ), true ) ) {
			return;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! array_filter( get_plugins(), array( self::class, 'is_first_party' ) ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'Your Clanbite add-ons receive updates through the Clanbite Add-on Manager, a separate free plugin that is not installed or active on this site.', 'clanbite' ),
			esc_url( self::download_page_url() ),
			esc_html__( 'Get it from clanbite.com', 'clanbite' )
		);
	}
}
