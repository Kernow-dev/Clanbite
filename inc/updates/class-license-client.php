<?php
/**
 * Client for the Clanbite Extension Server (licence activation and update API).
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Updates;

defined( 'ABSPATH' ) || exit;

/**
 * One licence key per site owner unlocks every add-on they bought. This class activates the key for this site, keeps the
 * activation token, asks for updates in a single batched request and requests the (single-use) download at the moment the
 * administrator clicks Update.
 *
 * State lives in the option `clanbite_license_state` (autoload off). The key and the activation token are encrypted with a key
 * derived from the site's salts, never returned to the browser and never logged. Define `CLANBITE_LICENSE_KEY` in `wp-config.php`
 * to supply the key from code (managed hosts, deployments).
 */
final class License_Client {

	public const OPTION = 'clanbite_license_state';

	/**
	 * Saved state with defaults.
	 *
	 * @return array{key: string, token: string, hint: string, site_url: string, license: array<string, mixed>, entitlements: list<array<string, mixed>>, plugins: array<string, array<string, mixed>>, etag: string, checked: int, error: string}
	 */
	public static function get_state(): array {
		$state = get_option( self::OPTION, array() );

		return array_merge(
			array(
				'key'          => '',
				'token'        => '',
				'hint'         => '',
				'site_url'     => '',
				'license'      => array(),
				'entitlements' => array(),
				'plugins'      => array(),
				'etag'         => '',
				'checked'      => 0,
				'error'        => '',
			),
			is_array( $state ) ? $state : array()
		);
	}

	/**
	 * Save state.
	 *
	 * @param array<string, mixed> $state Full state.
	 * @return void
	 */
	private static function save_state( array $state ): void {
		update_option( self::OPTION, $state, false );
	}

	/**
	 * Whether this site holds an activation token.
	 *
	 * @return bool
	 */
	public static function is_activated(): bool {
		return '' !== self::token();
	}

	/**
	 * Whether this site is missing a licence that one of its add-ons needs.
	 *
	 * False once the site is activated, when no add-on takes part, and when every installed add-on is one the server reported as free
	 * on the last check. An add-on the server has not described yet counts as licensed, so the prompt errs on the side of showing.
	 *
	 * @return bool
	 */
	public static function needs_license(): bool {
		if ( self::is_activated() ) {
			return false;
		}
		$rows = (array) self::get_state()['plugins'];
		foreach ( array_keys( Updater::discover( get_plugins() ) ) as $file ) {
			if ( empty( $rows[ dirname( (string) $file ) ]['free'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cheap summary for {@see clanbite_get_license_status()}.
	 *
	 * @return array{activated: bool, status: string, plan: string, unlimited: bool, expires: int, entitlements: list<array{slug: string, name: string, state: string, expires: int, free: bool}>}
	 */
	public static function public_status(): array {
		$state   = self::get_state();
		$license = (array) $state['license'];

		return array(
			'activated'    => self::is_activated(),
			'status'       => self::is_activated() ? (string) ( $license['status'] ?? 'active' ) : 'none',
			'plan'         => (string) ( $license['plan'] ?? 'standard' ),
			'unlimited'    => ! empty( $license['unlimited'] ),
			'expires'      => (int) ( $license['expires'] ?? 0 ),
			'entitlements' => array_values( (array) $state['entitlements'] ),
		);
	}

	/**
	 * Host of the update server.
	 *
	 * @return string
	 */
	public static function server_host(): string {
		return strtolower( (string) wp_parse_url( clanbite_update_server_url(), PHP_URL_HOST ) );
	}

	/**
	 * Whether the configured server URL may be used (HTTPS, or HTTP on a `local` environment).
	 *
	 * @return bool
	 */
	public static function server_usable(): bool {
		$url = clanbite_update_server_url();

		return str_starts_with( $url, 'https://' ) || ( str_starts_with( $url, 'http://' ) && 'local' === wp_get_environment_type() );
	}

	/**
	 * The licence key in use: the constant when defined, otherwise the stored (decrypted) one.
	 *
	 * @return string Empty string when none.
	 */
	public static function key(): string {
		if ( defined( 'CLANBITE_LICENSE_KEY' ) && is_string( CLANBITE_LICENSE_KEY ) && '' !== trim( CLANBITE_LICENSE_KEY ) ) {
			return trim( CLANBITE_LICENSE_KEY );
		}

		return self::unseal( (string) self::get_state()['key'] );
	}

	/**
	 * Whether the key comes from `CLANBITE_LICENSE_KEY`.
	 *
	 * @return bool
	 */
	public static function key_from_constant(): bool {
		return defined( 'CLANBITE_LICENSE_KEY' ) && is_string( CLANBITE_LICENSE_KEY ) && '' !== trim( CLANBITE_LICENSE_KEY );
	}

	/**
	 * Decrypted activation token.
	 *
	 * @return string
	 */
	private static function token(): string {
		return self::unseal( (string) self::get_state()['token'] );
	}

	/**
	 * Activate a licence key for this site.
	 *
	 * @param string $key Licence key as typed.
	 * @return array<string, mixed>|\WP_Error The new state summary, or the server's error.
	 */
	public static function activate( string $key ) {
		$key = trim( $key );
		if ( '' === $key ) {
			return new \WP_Error( 'clanbite_license_empty', __( 'Enter your licence key.', 'clanbite' ) );
		}
		$res = self::request( 'licenses/activate', array_merge( self::site_payload(), array( 'license_key' => $key ) ) );
		if ( is_wp_error( $res ) ) {
			$state          = self::get_state();
			$state['error'] = $res->get_error_message();
			self::save_state( $state );

			return $res;
		}
		$data  = $res['data'];
		$state = self::get_state();

		$state['key']          = self::key_from_constant() ? '' : self::seal( $key );
		$state['token']        = self::seal( (string) ( $data['activation_token'] ?? '' ) );
		$state['hint']         = (string) ( $data['license']['hint'] ?? '' );
		$state['site_url']     = home_url();
		$state['license']      = (array) ( $data['license'] ?? array() );
		$state['entitlements'] = (array) ( $data['entitlements'] ?? array() );
		$state['plugins']      = array();
		$state['etag']         = '';
		$state['checked']      = 0;
		$state['error']        = '';
		self::save_state( $state );
		delete_site_transient( 'update_plugins' );

		/**
		 * Fires after this site activated a Clanbite licence.
		 *
		 * @since 1.6.0
		 *
		 * @param array<string, mixed> $license Licence summary from the server (`status`, `plan`, `expires`, `sites_used`, …).
		 */
		do_action( 'clanbite_license_activated', $state['license'] );

		return self::summary();
	}

	/**
	 * Deactivate this site (frees its seat) and forget the key.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function deactivate() {
		if ( self::is_activated() ) {
			$res = self::request( 'licenses/deactivate', self::authed() );
			// An already-invalid token means the server forgot this site; carry on and clear it locally.
			if ( is_wp_error( $res ) && 'invalid_token' !== $res->get_error_code() ) {
				return $res;
			}
		}
		self::save_state( array() );
		delete_site_transient( 'update_plugins' );

		/**
		 * Fires after this site's licence was deactivated.
		 *
		 * @since 1.6.0
		 */
		do_action( 'clanbite_license_deactivated' );

		return self::summary();
	}

	/**
	 * Ask the server which installed add-ons have updates.
	 *
	 * Re-activates silently once when the server no longer knows this site's token and the key is still available. On a network
	 * error the last known answer is returned (with the error stored) so the Updates screen keeps working.
	 *
	 * @param array<string, string> $plugins Installed add-on slug => version.
	 * @return array<string, array<string, mixed>>|\WP_Error Slug => update row, empty when the site is not activated.
	 */
	public static function check( array $plugins ) {
		$licensed = self::is_activated() || self::auto_activate();
		if ( ! self::server_usable() ) {
			return $licensed ? new \WP_Error( 'clanbite_license_server', __( 'The update server URL must use HTTPS.', 'clanbite' ) ) : array();
		}

		/**
		 * Filters the release channel this site asks for.
		 *
		 * `beta` also offers pre-releases of add-ons the server marks as having a beta channel; anything else means `stable`.
		 *
		 * @since 1.6.0
		 *
		 * @param string $channel Default `stable`.
		 */
		$channel = (string) apply_filters( 'clanbite_update_channel', 'stable' );
		$state   = self::get_state();
		$body    = array_merge(
			// Without a licence the site still asks, anonymously, about free add-ons (the server answers for those only).
			$licensed ? self::authed() : self::site_payload(),
			array(
				'plugins' => $plugins,
				'channel' => $channel,
			)
		);
		$route = $licensed ? 'updates/check' : 'free/check';
		$res   = self::request( $route, $body, '' !== $state['etag'] ? array( 'If-None-Match' => $state['etag'] ) : array() );

		if ( $licensed && is_wp_error( $res ) && 'invalid_token' === $res->get_error_code() && self::key() !== '' ) {
			self::clear_token();
			if ( self::auto_activate() ) {
				$body['activation_token'] = self::token();
				$res                      = self::request( 'updates/check', $body );
			}
		}
		if ( is_wp_error( $res ) ) {
			$state          = self::get_state();
			$state['error'] = $res->get_error_message();
			self::save_state( $state );

			return 'invalid_token' === $res->get_error_code() ? $res : ( (array) $state['plugins'] );
		}

		$state            = self::get_state();
		$state['checked'] = time();
		$state['error']   = '';
		if ( 304 !== $res['status'] ) {
			$state['plugins'] = (array) ( $res['data']['plugins'] ?? array() );
			$state['etag']    = (string) ( $res['etag'] ?? '' );
			if ( ! empty( $res['data']['license'] ) ) {
				$state['license'] = (array) $res['data']['license'];
			}
		}
		self::save_state( $state );

		return (array) $state['plugins'];
	}

	/**
	 * The extensions this site may see and install: everything for an activated licence, only free ones without one.
	 *
	 * @return list<array<string, mixed>>|\WP_Error
	 */
	public static function catalog() {
		$licensed = self::is_activated();
		$res      = self::request(
			$licensed ? 'catalog' : 'free/catalog',
			array_merge( $licensed ? self::authed() : self::site_payload(), array( 'plugins' => Compat_Guard::installed_addons() ) )
		);
		if ( $licensed && is_wp_error( $res ) && 'invalid_token' === $res->get_error_code() && self::key() !== '' ) {
			self::clear_token();
			if ( self::auto_activate() ) {
				$res = self::request( 'catalog', array_merge( self::authed(), array( 'plugins' => Compat_Guard::installed_addons() ) ) );
			}
		}
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return array_values( array_filter( (array) ( $res['data']['extensions'] ?? array() ), 'is_array' ) );
	}

	/**
	 * Refresh the licence summary and entitlements from the server.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function refresh() {
		if ( ! self::is_activated() && ! self::auto_activate() ) {
			return self::summary();
		}
		$res = self::request( 'licenses/status', self::authed() );
		if ( is_wp_error( $res ) && 'invalid_token' === $res->get_error_code() && self::key() !== '' ) {
			self::clear_token();
			if ( self::auto_activate() ) {
				$res = self::request( 'licenses/status', self::authed() );
			}
		}
		$state = self::get_state();
		if ( is_wp_error( $res ) ) {
			$state['error'] = $res->get_error_message();
			self::save_state( $state );

			return $res;
		}
		$state['license']      = (array) ( $res['data']['license'] ?? array() );
		$state['entitlements'] = (array) ( $res['data']['entitlements'] ?? array() );
		$state['error']        = '';
		$state['etag']         = '';
		self::save_state( $state );
		delete_site_transient( 'update_plugins' );

		return self::summary();
	}

	/**
	 * Request a single-use download for a release.
	 *
	 * @param string $slug    Plugin slug.
	 * @param string $version Version to install.
	 * @return array{url: string, sha256: string, signature: string, size: int}|\WP_Error
	 */
	public static function request_package( string $slug, string $version ) {
		// A site without a licence can still install free add-ons; the server refuses anything else on this route.
		$licensed = self::is_activated();
		$res      = self::request(
			$licensed ? 'updates/package' : 'free/package',
			array_merge(
				$licensed ? self::authed() : self::site_payload(),
				array(
					'slug'    => $slug,
					'version' => $version,
					'plugins' => Compat_Guard::installed_addons(),
				)
			)
		);
		if ( $licensed && is_wp_error( $res ) && 'invalid_token' === $res->get_error_code() && self::key() !== '' ) {
			self::clear_token();
			if ( self::auto_activate() ) {
				$res = self::request(
					'updates/package',
					array_merge(
						self::authed(),
						array(
							'slug'    => $slug,
							'version' => $version,
							'plugins' => Compat_Guard::installed_addons(),
						)
					)
				);
			}
		}
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$data = $res['data'];
		// Only the single-use ticket is taken from the answer. The address is rebuilt from the configured server, so a response can
		// never point the download at another host.
		$ticket = basename( (string) wp_parse_url( (string) ( $data['url'] ?? '' ), PHP_URL_PATH ) );
		if ( ! preg_match( '/^[A-Za-z0-9_-]{20,80}$/', $ticket ) ) {
			return new \WP_Error( 'clanbite_license_download_host', __( 'The update server returned an unexpected download address.', 'clanbite' ) );
		}
		$url = clanbite_update_server_url() . '/wp-json/cbes/v1/download/' . $ticket;

		return array(
			'url'       => $url,
			'sha256'    => strtolower( (string) ( $data['sha256'] ?? '' ) ),
			'signature' => (string) ( $data['signature'] ?? '' ),
			'size'      => (int) ( $data['size'] ?? 0 ),
		);
	}

	/**
	 * Details for the "View details" modal.
	 *
	 * @param string $slug Plugin slug.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function plugin_info( string $slug ) {
		if ( ! self::is_activated() ) {
			// Free add-ons describe themselves to anyone; anything else asks for a licence.
			$res = self::request( 'free/info', array_merge( self::site_payload(), array( 'slug' => $slug ) ) );

			return is_wp_error( $res ) ? new \WP_Error( 'clanbite_license_inactive', __( 'Activate your licence to see details.', 'clanbite' ) ) : (array) $res['data'];
		}
		$res = self::request( 'plugins/info', array_merge( self::authed(), array( 'slug' => $slug ) ) );

		return is_wp_error( $res ) ? $res : (array) $res['data'];
	}

	/**
	 * Everything the Licence screen shows (never the key).
	 *
	 * @return array<string, mixed>
	 */
	public static function summary(): array {
		$state   = self::get_state();
		$license = (array) $state['license'];
		$all     = function_exists( 'get_plugins' ) ? get_plugins() : array();
		$plugins = array();
		foreach ( Updater::discover( $all ) as $file => $data ) {
			$slug      = dirname( $file );
			$row       = (array) ( $state['plugins'][ $slug ] ?? array() );
			$plugins[] = array(
				'slug'             => $slug,
				'name'             => (string) $data['Name'],
				'installed'        => (string) $data['Version'],
				'available'        => (string) ( $row['new_version'] ?? '' ),
				'latest'           => (string) ( $row['latest_version'] ?? '' ),
				'update_available' => ! empty( $row['update_available'] ),
				'access'           => (string) ( $row['access'] ?? '' ),
				'renew_for_latest' => ! empty( $row['renew_for_latest'] ),
				'blocked_version'  => (string) ( $row['blocked']['version'] ?? '' ),
				'blocked_reason'   => (string) ( ( (array) ( $row['blocked']['reasons'] ?? array() ) )[0] ?? '' ),
			);
		}

		return array(
			'activated'    => self::is_activated(),
			'hint'         => (string) $state['hint'],
			'constant'     => self::key_from_constant(),
			'site_url'     => home_url(),
			'server'       => clanbite_update_server_url(),
			'server_ok'    => self::server_usable(),
			'license'      => $license,
			'entitlements' => array_values( (array) $state['entitlements'] ),
			'plugins'      => $plugins,
			'checked'      => (int) $state['checked'],
			'error'        => (string) $state['error'],
			/**
			 * Filters the URL where customers manage or renew their licence.
			 *
			 * The default is the address the update server sends (its Customer account page setting); an empty string hides the link.
			 *
			 * @since 1.6.0
			 *
			 * @param string $url An https address, or an empty string for none.
			 */
			'account_url'  => esc_url_raw( (string) apply_filters( 'clanbite_license_account_url', (string) ( $license['account_url'] ?? '' ) ), array( 'https' ) ),
		);
	}

	/**
	 * Activate from `CLANBITE_LICENSE_KEY` when defined and not yet activated (throttled after a failure).
	 *
	 * @return bool Whether the site is activated afterwards.
	 */
	public static function auto_activate(): bool {
		$key = self::key();
		if ( '' === $key || get_transient( 'clanbite_license_autoactivate_failed' ) ) {
			return self::is_activated();
		}
		$result = self::activate( $key );
		if ( is_wp_error( $result ) ) {
			set_transient( 'clanbite_license_autoactivate_failed', 1, HOUR_IN_SECONDS );

			return false;
		}

		return true;
	}

	/**
	 * Forget the activation token only (keeps the key so the site can re-activate itself).
	 *
	 * @return void
	 */
	private static function clear_token(): void {
		$state          = self::get_state();
		$state['token'] = '';
		self::save_state( $state );
	}

	/**
	 * Fields identifying this site.
	 *
	 * @return array<string, mixed>
	 */
	private static function site_payload(): array {
		return array(
			'site_url'     => home_url(),
			'site_name'    => wp_specialchars_decode( (string) get_option( 'blogname' ), ENT_QUOTES ),
			'wp_version'   => get_bloginfo( 'version' ),
			'php_version'  => PHP_VERSION,
			'core_version' => \Kernowdev\Clanbite\Main::VERSION,
		);
	}

	/**
	 * Fields every authenticated call carries.
	 *
	 * @return array<string, mixed>
	 */
	private static function authed(): array {
		return array_merge( self::site_payload(), array( 'activation_token' => self::token() ) );
	}

	/**
	 * POST to the update server.
	 *
	 * @param string                $route   Route below `/wp-json/cbes/v1/`.
	 * @param array<string, mixed>  $body    JSON body.
	 * @param array<string, string> $headers Extra headers.
	 * @return array{status: int, data: array<string, mixed>, etag: string}|\WP_Error The server's error code and message on failure.
	 */
	private static function request( string $route, array $body, array $headers = array() ) {
		if ( ! self::server_usable() ) {
			return new \WP_Error( 'clanbite_license_server', __( 'The update server URL must use HTTPS.', 'clanbite' ) );
		}
		$res = wp_remote_post(
			clanbite_update_server_url() . '/wp-json/cbes/v1/' . $route,
			array(
				'timeout' => 20,
				'headers' => array_merge(
					array(
						'Content-Type' => 'application/json',
						'Accept'       => 'application/json',
						'User-Agent'   => 'Clanbite/' . \Kernowdev\Clanbite\Main::VERSION . '; ' . home_url(),
					),
					$headers
				),
				'body'    => (string) wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return new \WP_Error( 'clanbite_license_unreachable', __( 'The Clanbite update server could not be reached. Try again later.', 'clanbite' ), $res->get_error_message() );
		}
		$status = (int) wp_remote_retrieve_response_code( $res );
		$data   = json_decode( wp_remote_retrieve_body( $res ), true );
		$data   = is_array( $data ) ? $data : array();
		if ( 304 === $status ) {
			return array(
				'status' => 304,
				'data'   => array(),
				'etag'   => (string) wp_remote_retrieve_header( $res, 'etag' ),
			);
		}
		if ( $status < 200 || $status >= 300 ) {
			$code    = isset( $data['code'] ) ? sanitize_key( (string) $data['code'] ) : 'clanbite_license_http_' . $status;
			$message = isset( $data['message'] ) ? sanitize_text_field( (string) $data['message'] ) : __( 'The update server returned an unexpected answer.', 'clanbite' );

			return new \WP_Error( $code, $message, array( 'status' => $status ) );
		}

		return array(
			'status' => $status,
			'data'   => $data,
			'etag'   => (string) wp_remote_retrieve_header( $res, 'etag' ),
		);
	}

	/**
	 * Encrypt a secret with a key derived from the site's salts.
	 *
	 * @param string $plain Plain text.
	 * @return string URL-safe ciphertext, empty for empty input.
	 */
	private static function seal( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		return rtrim( strtr( base64_encode( $nonce . sodium_crypto_secretbox( $plain, $nonce, self::crypto_key() ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoding ciphertext, not obfuscation.
	}

	/**
	 * Decrypt a value from {@see License_Client::seal()}.
	 *
	 * @param string $sealed Ciphertext.
	 * @return string Empty string when it cannot be opened (for example after the salts changed).
	 */
	private static function unseal( string $sealed ): string {
		if ( '' === $sealed ) {
			return '';
		}
		$raw = base64_decode( strtr( $sealed, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding ciphertext, not obfuscation.
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return '';
		}
		$plain = sodium_crypto_secretbox_open( substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), self::crypto_key() );

		return false === $plain ? '' : $plain;
	}

	/**
	 * Encryption key.
	 *
	 * @return string
	 */
	private static function crypto_key(): string {
		return sodium_crypto_generichash( wp_salt( 'auth' ) . '|clanbite-license', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}
}
