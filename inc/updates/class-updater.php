<?php
/**
 * Plugs licensed Clanbite add-ons into WordPress' normal update screens.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Updates;

defined( 'ABSPATH' ) || exit;

/**
 * An add-on takes part by carrying an `Update URI` header on the update server's host, for example
 * `Update URI: https://services.clanbite.com/clanbite-points`. WordPress then skips wordpress.org for it (so nobody can hijack
 * the slug there) and asks the `update_plugins_{host}` filter instead.
 *
 *  - **Check**: one batched request for every add-on on the site, answered from the same data for each plugin in the run.
 *  - **Offer**: an update whose licence allows it carries a placeholder package address; one that needs a renewal or a licence
 *    carries none, and a note under the plugin explains why.
 *  - **Install**: when the administrator clicks Update (or an auto-update runs), `upgrader_pre_download` asks the server for a
 *    single-use download, fetches it from the server's own host, verifies its SHA-256 and hands the file to WordPress.
 */
final class Updater {

	/**
	 * Marker host path of the placeholder package address.
	 */
	private const PLACEHOLDER = '/clanbite-package/';

	/**
	 * Answer of the batched check for the current request (null until asked).
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static ?array $batch = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_host_filter' ), 1 );
		add_filter( 'plugins_api', array( self::class, 'plugins_api' ), 20, 3 );
		add_filter( 'upgrader_pre_download', array( self::class, 'pre_download' ), 10, 4 );
		add_filter( 'upgrader_source_selection', array( self::class, 'verify_source' ), 10, 4 );
		add_filter( 'plugin_action_links', array( self::class, 'action_links' ), 10, 2 );
		add_action( 'load-plugins.php', array( self::class, 'register_update_messages' ) );
		add_action( 'load-update-core.php', array( self::class, 'register_update_messages' ) );
	}

	/**
	 * Hook the per-host update filter for the configured server.
	 *
	 * @return void
	 */
	public static function register_host_filter(): void {
		$host = License_Client::server_host();
		if ( '' !== $host ) {
			add_filter( 'update_plugins_' . $host, array( self::class, 'filter_update' ), 10, 4 );
		}
	}

	/**
	 * Installed plugins that take part (their `Update URI` host is the update server's host).
	 *
	 * @param array<string, array<string, mixed>> $plugins Result of `get_plugins()`.
	 * @return array<string, array<string, mixed>> Plugin file => plugin data.
	 */
	public static function discover( array $plugins ): array {
		$host = License_Client::server_host();
		$out  = array();
		foreach ( $plugins as $file => $data ) {
			$uri = (string) ( $data['UpdateURI'] ?? '' );
			if ( '' !== $host && '' !== $uri && strtolower( (string) wp_parse_url( $uri, PHP_URL_HOST ) ) === $host ) {
				$out[ $file ] = $data;
			}
		}

		/**
		 * Filters the plugins that receive updates from the Clanbite update server.
		 *
		 * Add a plugin file here only if the plugin also carries an `Update URI` header on the server's host (WordPress asks
		 * wordpress.org for plugins without one).
		 *
		 * @since 1.6.0
		 *
		 * @param array<string, array<string, mixed>> $out     Plugin file => plugin data.
		 * @param array<string, array<string, mixed>> $plugins Every installed plugin.
		 */
		return (array) apply_filters( 'clanbite_updatable_plugins', $out, $plugins );
	}

	/**
	 * `update_plugins_{host}` callback.
	 *
	 * @param array<string, mixed>|false $update      Update data so far.
	 * @param array<string, mixed>       $plugin_data Plugin headers.
	 * @param string                     $plugin_file Plugin file relative to the plugins directory.
	 * @param string[]                   $locales     Installed locales.
	 * @return array<string, mixed>|false
	 */
	public static function filter_update( $update, array $plugin_data, string $plugin_file, array $locales ) {
		unset( $locales );
		$slug  = dirname( $plugin_file );
		$batch = self::batch();
		if ( ! isset( $batch[ $slug ] ) ) {
			return $update;
		}
		$row     = $batch[ $slug ];
		$version = (string) $row['new_version'];
		// Nothing installable, but a newer release exists that is held back (not compatible yet, or needs a renewal): list it without a
		// package so the Plugins screen can say why, instead of showing nothing.
		if ( empty( $row['package_available'] ) ) {
			if ( ! empty( $row['blocked']['version'] ) ) {
				$version = (string) $row['blocked']['version'];
			} elseif ( ! empty( $row['renew_for_latest'] ) && ! empty( $row['latest_version'] ) ) {
				$version = (string) $row['latest_version'];
			}
		}

		return array(
			'id'             => 'cbes/' . $slug,
			'slug'           => $slug,
			'plugin'         => $plugin_file,
			'version'        => $version,
			'url'            => (string) ( $plugin_data['PluginURI'] ?? '' ),
			'package'        => ! empty( $row['package_available'] ) ? clanbite_update_server_url() . self::PLACEHOLDER . rawurlencode( $slug ) . '/' . rawurlencode( $version ) : '',
			'tested'         => (string) ( $row['tested'] ?? '' ),
			'requires'       => (string) ( $row['requires'] ?? '' ),
			'requires_php'   => (string) ( $row['requires_php'] ?? '' ),
			'upgrade_notice' => (string) ( $row['upgrade_notice'] ?? '' ),
		);
	}

	/**
	 * One batched check per request.
	 *
	 * @return array<string, array<string, mixed>> Slug => update row (empty when not activated or on error with no history).
	 */
	private static function batch(): array {
		if ( null !== self::$batch ) {
			return self::$batch;
		}
		$installed = array();
		foreach ( self::discover( get_plugins() ) as $file => $data ) {
			$installed[ dirname( $file ) ] = (string) $data['Version'];
		}
		$result = $installed ? License_Client::check( $installed ) : array();

		self::$batch = is_wp_error( $result ) ? array() : $result;

		return self::$batch;
	}

	/**
	 * "View details" modal for add-ons.
	 *
	 * @param false|object|array<string, mixed> $result Result so far.
	 * @param string                            $action API action.
	 * @param object                            $args   Request arguments.
	 * @return false|object|array<string, mixed>
	 */
	public static function plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $result;
		}
		$slug = (string) $args->slug;
		$ours = false;
		foreach ( array_keys( self::discover( get_plugins() ) ) as $file ) {
			if ( dirname( $file ) === $slug ) {
				$ours = true;
				break;
			}
		}
		if ( ! $ours ) {
			return $result;
		}
		$info = License_Client::plugin_info( $slug );
		if ( is_wp_error( $info ) ) {
			return new \WP_Error( 'plugins_api_failed', $info->get_error_message() );
		}

		return (object) array(
			'name'         => (string) ( $info['name'] ?? $slug ),
			'slug'         => $slug,
			'version'      => (string) ( $info['version'] ?? '' ),
			'author'       => (string) ( $info['author'] ?? 'kernow.dev' ),
			'requires'     => (string) ( $info['requires'] ?? '' ),
			'requires_php' => (string) ( $info['requires_php'] ?? '' ),
			'tested'       => (string) ( $info['tested'] ?? '' ),
			'last_updated' => (string) ( $info['last_updated'] ?? '' ),
			'sections'     => array_map( 'wp_kses_post', (array) ( $info['sections'] ?? array() ) ),
			'external'     => true,
		);
	}

	/**
	 * Replace the placeholder package with the real, single-use download.
	 *
	 * @param bool|string|\WP_Error $reply      Value so far (`false` to proceed with the default download).
	 * @param string                $package    Package address.
	 * @param \WP_Upgrader          $upgrader   Upgrader.
	 * @param array<string, mixed>  $hook_extra Extra data.
	 * @return bool|string|\WP_Error Path of the downloaded zip, or an error.
	 */
	public static function pre_download( $reply, $package, $upgrader, $hook_extra ) {
		unset( $upgrader, $hook_extra );
		$prefix = clanbite_update_server_url() . self::PLACEHOLDER;
		if ( false !== $reply || ! is_string( $package ) || ! str_starts_with( $package, $prefix ) ) {
			return $reply;
		}
		$parts = explode( '/', substr( $package, strlen( $prefix ) ) );
		if ( 2 !== count( $parts ) ) {
			return new \WP_Error( 'clanbite_update_package', __( 'The update package address is not valid.', 'clanbite' ) );
		}
		$slug    = sanitize_key( rawurldecode( $parts[0] ) );
		$version = preg_replace( '/[^0-9A-Za-z.\-+]/', '', rawurldecode( $parts[1] ) );

		$ticket = License_Client::request_package( $slug, (string) $version );
		if ( is_wp_error( $ticket ) ) {
			return new \WP_Error( $ticket->get_error_code(), self::describe_error( $ticket ) );
		}
		$file = download_url( $ticket['url'], 300 );
		if ( is_wp_error( $file ) ) {
			return new \WP_Error( 'clanbite_update_download', __( 'The update could not be downloaded. Try again in a moment.', 'clanbite' ) );
		}
		$hash = hash_file( 'sha256', $file );
		if ( '' === $ticket['sha256'] || false === $hash || ! hash_equals( $ticket['sha256'], $hash ) ) {
			wp_delete_file( $file );

			return new \WP_Error( 'clanbite_update_checksum', __( 'The downloaded update did not match its checksum and was discarded.', 'clanbite' ) );
		}

		// Supply-chain check: the release must be signed with a key the update server does not hold. The hash signed is the one just
		// computed from the downloaded bytes, so neither a swapped file nor a swapped checksum passes.
		$keys = clanbite_release_public_keys();
		if ( $keys && ! Release_Signature::verify( $slug, (string) $version, $hash, (string) $ticket['signature'], $keys ) ) {
			wp_delete_file( $file );

			return new \WP_Error( 'clanbite_update_signature', __( 'The update is not signed by Clanbite (or the signature is invalid) and was discarded.', 'clanbite' ) );
		}

		return $file;
	}

	/**
	 * Make sure the unpacked folder is the plugin being updated.
	 *
	 * @param string|\WP_Error     $source        Path of the unpacked folder.
	 * @param string               $remote_source Path of the temporary extraction directory.
	 * @param \WP_Upgrader         $upgrader      Upgrader.
	 * @param array<string, mixed> $hook_extra    Extra data (`plugin` for plugin updates).
	 * @return string|\WP_Error
	 */
	public static function verify_source( $source, $remote_source, $upgrader, $hook_extra ) {
		unset( $remote_source, $upgrader );
		if ( is_wp_error( $source ) || empty( $hook_extra['plugin'] ) || ! is_string( $source ) ) {
			return $source;
		}
		$file = (string) $hook_extra['plugin'];
		if ( ! array_key_exists( $file, self::discover( get_plugins() ) ) ) {
			return $source;
		}
		if ( basename( untrailingslashit( $source ) ) !== dirname( $file ) ) {
			return new \WP_Error( 'clanbite_update_layout', __( 'The update package has an unexpected folder layout and was not installed.', 'clanbite' ) );
		}
		// Last line of defence: whatever the server offered, never install a package that cannot run on this site.
		$compat = Compat_Guard::check_unpacked( $source, $file );
		if ( is_wp_error( $compat ) ) {
			return $compat;
		}

		return $source;
	}

	/**
	 * "Licence" link under the plugin name.
	 *
	 * @param string[] $links Links so far.
	 * @param string   $file  Plugin file.
	 * @return string[]
	 */
	public static function action_links( $links, $file ) {
		if ( is_array( $links ) && array_key_exists( (string) $file, self::discover( get_plugins() ) ) ) {
			$links[] = '<a href="' . esc_url( License_Admin::url() ) . '">' . esc_html__( 'Licence', 'clanbite' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Hook the explanation printed under an update that cannot be installed.
	 *
	 * @return void
	 */
	public static function register_update_messages(): void {
		foreach ( array_keys( self::discover( get_plugins() ) ) as $file ) {
			add_action( 'in_plugin_update_message-' . $file, array( self::class, 'update_message' ), 10, 2 );
		}
	}

	/**
	 * Explain why an offered update cannot be installed, or that a newer one needs a renewal.
	 *
	 * @param array<string, mixed> $plugin_data Plugin headers.
	 * @param object               $response    Update response.
	 * @return void
	 */
	public static function update_message( $plugin_data, $response ): void {
		unset( $plugin_data );
		$slug = isset( $response->slug ) ? (string) $response->slug : '';
		$row  = (array) ( License_Client::get_state()['plugins'][ $slug ] ?? array() );
		if ( ! $row ) {
			return;
		}
		$url     = esc_url( License_Admin::url() );
		$access  = (string) ( $row['access'] ?? '' );
		$blocked = is_array( $row['blocked'] ?? null ) ? $row['blocked'] : array();

		if ( empty( $response->package ) ) {
			if ( 'compat' === ( $blocked['kind'] ?? '' ) && ! empty( $blocked['reasons'] ) ) {
				printf(
					/* translators: 1: version, 2: reason */
					' <strong>' . esc_html__( 'Version %1$s is not installed automatically because it would not work with this site yet: %2$s', 'clanbite' ) . '</strong>',
					esc_html( (string) ( $blocked['version'] ?? '' ) ),
					esc_html( implode( ' ', array_map( 'strval', (array) $blocked['reasons'] ) ) )
				);

				return;
			}
			$text = match ( $access ) {
				'expired'   => __( 'Your licence for this add-on has expired.', 'clanbite' ),
				'suspended' => __( 'Your licence is not active.', 'clanbite' ),
				default     => __( 'Your licence does not include this add-on.', 'clanbite' ),
			};
			echo ' <strong>' . esc_html( $text ) . '</strong> <a href="' . $url . '">' . esc_html__( 'Manage licence', 'clanbite' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $url is escaped above.

			return;
		}
		if ( 'compat' === ( $blocked['kind'] ?? '' ) && ! empty( $blocked['reasons'] ) ) {
			printf(
				/* translators: 1: version, 2: reason */
				' <em>' . esc_html__( 'Newer version %1$s is waiting: %2$s', 'clanbite' ) . '</em>',
				esc_html( (string) ( $blocked['version'] ?? '' ) ),
				esc_html( implode( ' ', array_map( 'strval', (array) $blocked['reasons'] ) ) )
			);
		}
		if ( ! empty( $row['renew_for_latest'] ) && ! empty( $row['latest_version'] ) ) {
			printf(
				/* translators: %s: newest version number */
				' <em>' . esc_html__( 'Version %s was released after your licence ended. Renew to install it.', 'clanbite' ) . '</em>',
				esc_html( (string) $row['latest_version'] )
			);
		}
	}

	/**
	 * Turn a server error into a message for the upgrader screen.
	 *
	 * @param \WP_Error $error Error from the client.
	 * @return string
	 */
	private static function describe_error( \WP_Error $error ): string {
		return match ( $error->get_error_code() ) {
			'license_expired'   => __( 'Your licence has expired. Renew it to install versions released after it ended.', 'clanbite' ),
			'license_required'  => __( 'Your licence does not include this add-on.', 'clanbite' ),
			'license_suspended', 'license_inactive' => __( 'Your licence is not active.', 'clanbite' ),
			'invalid_token'     => __( 'This site is not activated. Enter your licence key under Clanbite → Settings → Licence.', 'clanbite' ),
			'limit_reached'     => __( 'This licence is already active on the maximum number of sites.', 'clanbite' ),
			default             => $error->get_error_message(),
		};
	}
}
