<?php
/**
 * Installs and activates add-ons from the Licence tab.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Updates;

defined( 'ABSPATH' ) || exit;

/**
 * The "Available add-ons" list: asks the update server which extensions this site may install (all of them for an activated licence,
 * only the free ones without a licence), and installs one through WordPress' normal plugin installer.
 *
 * Nothing here trusts the browser: the extension must be in the server's own answer, the download is the same single-use,
 * checksum- and signature-verified package used for updates, and the unpacked plugin must declare a compatible `Requires Clanbite`
 * before WordPress copies it into place. Only users who may install plugins can use it, and it is unavailable where WordPress
 * forbids file changes (`DISALLOW_FILE_MODS`).
 */
final class Installer {

	/**
	 * Slug being installed right now ('' outside an install), read by the source check in {@see Updater::verify_source()}.
	 *
	 * @var string
	 */
	private static string $installing = '';

	/**
	 * Slug of the add-on being installed, or an empty string.
	 *
	 * @return string
	 */
	public static function installing(): string {
		return self::$installing;
	}

	/**
	 * Whether this user may install add-ons from the Licence tab on this site.
	 *
	 * @return bool
	 */
	public static function can_install(): bool {
		$allowed = current_user_can( 'install_plugins' ) && wp_is_file_mod_allowed( 'install_plugins' );

		/**
		 * Filters whether the Licence tab offers to install add-ons.
		 *
		 * Return false to hide the Install buttons (for example on a managed host that handles plugins itself).
		 *
		 * @since 1.6.0
		 *
		 * @param bool $allowed Whether the current user may install plugins and file changes are allowed.
		 */
		return (bool) apply_filters( 'clanbite_license_can_install_addons', $allowed );
	}

	/**
	 * The server's catalog joined with what is installed on this site.
	 *
	 * @return array{extensions: list<array<string, mixed>>, can_install: bool, can_activate: bool}|\WP_Error
	 */
	public static function catalog() {
		$rows = License_Client::catalog();
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		$out     = array();
		foreach ( $rows as $row ) {
			$slug      = sanitize_key( (string) ( $row['slug'] ?? '' ) );
			$file      = '';
			$installed = '';
			foreach ( $plugins as $plugin_file => $data ) {
				if ( dirname( (string) $plugin_file ) === $slug ) {
					$file      = (string) $plugin_file;
					$installed = (string) $data['Version'];
					break;
				}
			}
			$row['slug']             = $slug;
			$row['installed']        = $installed;
			$row['file']             = $file;
			$row['active']           = '' !== $file && is_plugin_active( $file );
			$row['update_available'] = '' !== $installed && ! empty( $row['installable'] ) && version_compare( (string) $row['version'], $installed, '>' );
			$row['icon']             = esc_url_raw( (string) ( $row['icon'] ?? '' ), array( 'https' ) );
			$out[]                   = $row;
		}

		return array(
			'extensions'   => $out,
			'can_install'  => self::can_install(),
			'can_activate' => current_user_can( 'activate_plugins' ),
		);
	}

	/**
	 * Install an add-on the server offers this site.
	 *
	 * @param string $slug Plugin slug.
	 * @return array{file: string}|\WP_Error
	 */
	public static function install( string $slug ) {
		$slug = sanitize_key( $slug );
		if ( ! self::can_install() ) {
			return new \WP_Error( 'clanbite_install_forbidden', __( 'Plugins cannot be installed on this site from here.', 'clanbite' ), array( 'status' => 403 ) );
		}
		$catalog = License_Client::catalog();
		if ( is_wp_error( $catalog ) ) {
			return $catalog;
		}
		$row = null;
		foreach ( $catalog as $candidate ) {
			if ( sanitize_key( (string) ( $candidate['slug'] ?? '' ) ) === $slug ) {
				$row = $candidate;
				break;
			}
		}
		if ( ! $row || empty( $row['installable'] ) ) {
			return new \WP_Error( 'clanbite_install_unavailable', __( 'This add-on is not available for this site.', 'clanbite' ), array( 'status' => 404 ) );
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( array_keys( get_plugins() ) as $file ) {
			if ( dirname( (string) $file ) === $slug ) {
				return new \WP_Error( 'clanbite_install_exists', __( 'This add-on is already installed.', 'clanbite' ), array( 'status' => 409 ) );
			}
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
		if ( ! \WP_Filesystem() ) {
			return new \WP_Error( 'clanbite_install_filesystem', __( 'WordPress cannot write to the plugins folder directly. Install the add-on from Plugins → Add New, or ask your host.', 'clanbite' ), array( 'status' => 500 ) );
		}

		$skin             = new \Automatic_Upgrader_Skin();
		$upgrader         = new \Plugin_Upgrader( $skin );
		self::$installing = $slug;
		$result           = $upgrader->install( Updater::package_url( $slug, (string) $row['version'] ) );
		self::$installing = '';

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
		}
		$file = (string) $upgrader->plugin_info();
		if ( true !== $result || '' === $file ) {
			// The upgrader reports why on the skin (for example the package check above) or as a message.
			$reason  = isset( $skin->result ) && is_wp_error( $skin->result ) ? $skin->result : null;
			$message = $reason ? $reason->get_error_message() : '';
			if ( '' === $message ) {
				$message = implode( ' ', array_filter( array_map( 'wp_strip_all_tags', (array) $skin->get_upgrade_messages() ) ) );
			}

			return new \WP_Error( 'clanbite_install_failed', '' !== $message ? $message : __( 'The add-on could not be installed.', 'clanbite' ), array( 'status' => 500 ) );
		}
		wp_clean_plugins_cache( true );
		delete_site_transient( 'update_plugins' );

		/**
		 * Fires after an add-on was installed from the Licence tab (it is installed, not yet activated).
		 *
		 * @since 1.6.0
		 *
		 * @param string $slug Plugin slug.
		 * @param string $file Plugin file relative to the plugins directory.
		 */
		do_action( 'clanbite_addon_installed', $slug, $file );

		return array( 'file' => $file );
	}

	/**
	 * Activate an installed add-on (the WordPress plugin, not the Clanbite extension switch).
	 *
	 * @param string $slug Plugin slug.
	 * @return array{file: string}|\WP_Error
	 */
	public static function activate( string $slug ) {
		$slug = sanitize_key( $slug );
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new \WP_Error( 'clanbite_activate_forbidden', __( 'You cannot activate plugins on this site.', 'clanbite' ), array( 'status' => 403 ) );
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$known = array_keys( Updater::discover( get_plugins() ) );
		foreach ( $known as $file ) {
			if ( dirname( (string) $file ) === $slug ) {
				$result = activate_plugin( (string) $file );

				return is_wp_error( $result ) ? new \WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) ) : array( 'file' => (string) $file );
			}
		}

		return new \WP_Error( 'clanbite_activate_unknown', __( 'This add-on is not installed.', 'clanbite' ), array( 'status' => 404 ) );
	}
}
