<?php
/**
 * Keeps add-ons that cannot run on this Clanbite from running.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Compat;

defined( 'ABSPATH' ) || exit;

/**
 * Updates to Clanbite itself come from wordpress.org (or an auto-update, or FTP), so nothing outside the site can gate them. This
 * class is the safety net on the site: any plugin whose main file declares `Requires Clanbite: <constraint>` is checked against the Clanbite that
 * is actually installed, and when it does not fit it is **deactivated** (silently, so none of its code runs) instead of being left to
 * fatal the site. It also covers the add-ons' other declared needs: `Requires Clanbite Add-ons`, `Requires PHP` and `Requires at least`.
 *
 * When the check runs:
 *  - right after Clanbite or any plugin is updated or installed (using the **new** version found on disk, before the next request
 *    loads old add-ons on a new core),
 *  - right after a plugin is activated,
 *  - on the first request after anything relevant changed (an FTP update, a manual downgrade).
 *
 * What it does: deactivates the plugin, remembers why (`clanbite_disabled_addons`), shows a notice and a Site Health entry, warns before a
 * Clanbite update that would turn add-ons off, and **re-enables** what it disabled as soon as the plugin is compatible again (for
 * example after the add-on is updated). Opt out with the `clanbite_addon_guard_enabled` filter or the `CLANBITE_DISABLE_ADDON_GUARD`
 * constant. Plugins without a `Requires Clanbite` header are not touched, except first-party add-ons (known by their `Update URI`
 * header), which are assumed to be built for Clanbite 1.x.
 */
final class Compat_Monitor {

	public const OPTION = 'clanbite_disabled_addons';

	private const SIGNATURE = 'clanbite_addon_guard_signature';

	/**
	 * Header names. WordPress keys the plugin data by the header name itself for extra headers.
	 */
	private const HEADER_CORE = 'Requires Clanbite';

	private const HEADER_ADDONS = 'Requires Clanbite Add-ons';

	/**
	 * Re-entrancy guard (activation inside a sweep triggers `activated_plugin`).
	 *
	 * @var bool
	 */
	private static bool $running = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'extra_plugin_headers', array( self::class, 'extra_headers' ) );
		add_action( 'plugins_loaded', array( self::class, 'maybe_sweep' ), 0 );
		add_action( 'upgrader_process_complete', array( self::class, 'after_upgrade' ), 20, 2 );
		add_action( 'activated_plugin', array( self::class, 'after_activation' ), 20 );
		add_action( 'admin_notices', array( self::class, 'notice' ) );
		add_action( 'load-plugins.php', array( self::class, 'register_rows' ) );
		add_action( 'load-plugins.php', array( self::class, 'register_core_warning' ) );
		add_action( 'load-update-core.php', array( self::class, 'register_core_warning' ) );
		add_filter( 'site_status_tests', array( self::class, 'site_health_tests' ) );
	}

	/**
	 * Ask WordPress to read the compatibility headers into `get_plugins()` data.
	 *
	 * @param array<string, string> $headers Extra headers.
	 * @return array<string, string>
	 */
	public static function extra_headers( $headers ): array {
		$headers   = is_array( $headers ) ? $headers : array();
		$headers[] = self::HEADER_CORE;
		$headers[] = self::HEADER_ADDONS;

		return $headers;
	}

	/**
	 * Whether the guard is switched on.
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		if ( defined( 'CLANBITE_DISABLE_ADDON_GUARD' ) && CLANBITE_DISABLE_ADDON_GUARD ) {
			return false;
		}

		/**
		 * Filters whether incompatible add-ons are deactivated automatically.
		 *
		 * Return false to only show warnings (not recommended: an incompatible add-on can crash the site).
		 *
		 * @since 1.6.0
		 *
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'clanbite_addon_guard_enabled', true );
	}

	/**
	 * Plugins this site has switched off because they are incompatible.
	 *
	 * @return array<string, array{name: string, version: string, reasons: list<string>, core: string, time: int}>
	 */
	public static function disabled(): array {
		$stored = get_option( self::OPTION, array() );

		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Active plugin files (site and network).
	 *
	 * @return list<string>
	 */
	private static function active_files(): array {
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		return array_values( array_unique( array_map( 'strval', $active ) ) );
	}

	/**
	 * Cheap fingerprint of everything that can change the verdict: Clanbite's version, which plugins are active and the files of the
	 * plugins known to declare a Clanbite requirement.
	 *
	 * @return string
	 */
	private static function signature(): string {
		$known = array();
		foreach ( (array) get_option( self::SIGNATURE . '_files', array() ) as $file ) {
			$path           = WP_PLUGIN_DIR . '/' . $file;
			$known[ $file ] = is_file( $path ) ? (int) filemtime( $path ) : 0;
		}

		return md5( \Kernowdev\Clanbite\Main::VERSION . '|' . wp_json_encode( self::active_files() ) . '|' . wp_json_encode( $known ) );
	}

	/**
	 * First request after something changed: run a sweep.
	 *
	 * @return void
	 */
	public static function maybe_sweep(): void {
		if ( ! self::enabled() || self::$running ) {
			return;
		}
		if ( get_option( self::SIGNATURE ) !== self::signature() ) {
			self::sweep();
		}
	}

	/**
	 * After any plugin was updated or installed.
	 *
	 * Runs in the process that performed the update, where the OLD Clanbite is still in memory: the version to judge against is read
	 * from the main plugin file on disk.
	 *
	 * @param \WP_Upgrader         $upgrader Upgrader.
	 * @param array<string, mixed> $extra    What was upgraded.
	 * @return void
	 */
	public static function after_upgrade( $upgrader, $extra ): void {
		unset( $upgrader );
		if ( ! is_array( $extra ) || 'plugin' !== ( $extra['type'] ?? '' ) || ! self::enabled() ) {
			return;
		}
		wp_clean_plugins_cache( false );
		$core = '';
		if ( is_readable( WP_PLUGIN_DIR . '/' . clanbite()->basename ) ) {
			$core = (string) get_file_data( WP_PLUGIN_DIR . '/' . clanbite()->basename, array( 'v' => 'Version' ) )['v'];
		}
		self::sweep( '' !== $core ? $core : null );
	}

	/**
	 * After a plugin was activated by hand (or by another plugin).
	 *
	 * @param string $plugin Plugin file.
	 * @return void
	 */
	public static function after_activation( $plugin ): void {
		unset( $plugin );
		if ( self::enabled() && ! self::$running ) {
			self::sweep();
		}
	}

	/**
	 * The Clanbite versions a plugin says it works with.
	 *
	 * A plugin that declares `Requires Clanbite` uses that. A first-party add-on released before the header existed (recognised by the
	 * address in its `Update URI` header; nothing is contacted) is assumed to be built for Clanbite 1.x, which is true of every add-on that predates the
	 * header. Anything else without the header is not judged.
	 *
	 * @param array<string, mixed> $data Plugin data from `get_plugins()`.
	 * @return string Constraint, or an empty string when the plugin makes no claim.
	 */
	public static function constraint_for( array $data ): string {
		$declared = trim( (string) ( $data[ self::HEADER_CORE ] ?? '' ) );
		if ( '' !== $declared ) {
			return $declared;
		}
		if ( ! Addon_Manager_Notice::is_first_party( $data ) ) {
			return '';
		}

		/**
		 * Filters the Clanbite versions assumed for a first-party add-on that has no `Requires Clanbite` header.
		 *
		 * @since 1.6.0
		 *
		 * @param string               $constraint Default `^1.0.0`.
		 * @param array<string, mixed> $data       Plugin data.
		 */
		return (string) apply_filters( 'clanbite_legacy_addon_constraint', '^1.0.0', $data );
	}

	/**
	 * Judge plugins against a Clanbite version.
	 *
	 * @param string                              $core    Clanbite version to judge against.
	 * @param array<string, array<string, mixed>> $plugins All installed plugins (`get_plugins()` data).
	 * @param array<int|string, string>           $active  Plugin files that count as active.
	 * @return array<string, list<string>> Plugin file => reasons it cannot run. Only plugins that declare `Requires Clanbite` and are in `$active` appear.
	 */
	public static function evaluate( string $core, array $plugins, array $active ): array {
		$guarded = array();
		foreach ( $active as $file ) {
			if ( isset( $plugins[ $file ] ) && '' !== self::constraint_for( $plugins[ $file ] ) ) {
				$guarded[ $file ] = $plugins[ $file ];
			}
		}
		$failing = array();
		do {
			$changed = false;
			foreach ( $guarded as $file => $data ) {
				if ( isset( $failing[ $file ] ) ) {
					continue;
				}
				$reasons = self::reasons( $core, $data, $plugins, array_diff( $active, array_keys( $failing ) ) );
				if ( $reasons ) {
					$failing[ $file ] = $reasons;
					$changed          = true;
				}
			}
		} while ( $changed );

		return $failing;
	}

	/**
	 * Reasons one plugin cannot run.
	 *
	 * @param string                              $core    Clanbite version.
	 * @param array<string, mixed>                $data    Plugin data.
	 * @param array<string, array<string, mixed>> $plugins All plugins.
	 * @param array<int|string, string>           $active  Files that count as active (list of plugin files).
	 * @return list<string>
	 */
	private static function reasons( string $core, array $data, array $plugins, array $active ): array {
		$out        = array();
		$constraint = self::constraint_for( $data );
		// A malformed declaration is a packaging mistake, not a reason to switch a plugin off.
		if ( Version_Constraint::is_valid( $constraint ) && ! Version_Constraint::satisfies( $core, $constraint ) ) {
			$out[] = sprintf(
				/* translators: 1: required Clanbite versions, 2: installed Clanbite version */
				__( 'built for Clanbite %1$s, but this site runs Clanbite %2$s', 'clanbite' ),
				$constraint,
				$core
			);
		}
		$php = trim( (string) ( $data['RequiresPHP'] ?? '' ) );
		if ( '' !== $php && version_compare( PHP_VERSION, $php, '<' ) ) {
			/* translators: 1: required PHP version, 2: running PHP version */
			$out[] = sprintf( __( 'needs PHP %1$s or newer (this site runs %2$s)', 'clanbite' ), $php, PHP_VERSION );
		}
		$wp = trim( (string) ( $data['RequiresWP'] ?? '' ) );
		if ( '' !== $wp && version_compare( get_bloginfo( 'version' ), $wp, '<' ) ) {
			/* translators: 1: required WordPress version, 2: running WordPress version */
			$out[] = sprintf( __( 'needs WordPress %1$s or newer (this site runs %2$s)', 'clanbite' ), $wp, get_bloginfo( 'version' ) );
		}
		$needs = Version_Constraint::parse_addons( (string) ( $data[ self::HEADER_ADDONS ] ?? '' ) );
		foreach ( (array) $needs as $slug => $dep_constraint ) {
			$dep_file = '';
			foreach ( $plugins as $file => $candidate ) {
				if ( dirname( (string) $file ) === $slug ) {
					$dep_file = (string) $file;
					break;
				}
			}
			if ( '' === $dep_file || ! in_array( $dep_file, $active, true ) ) {
				/* translators: 1: add-on slug, 2: required versions */
				$out[] = sprintf( __( 'needs the add-on "%1$s" %2$s, which is not active', 'clanbite' ), $slug, $dep_constraint );
			} elseif ( ! Version_Constraint::satisfies( (string) $plugins[ $dep_file ]['Version'], $dep_constraint ) ) {
				/* translators: 1: add-on slug, 2: required versions, 3: installed version */
				$out[] = sprintf( __( 'needs the add-on "%1$s" %2$s (this site has %3$s)', 'clanbite' ), $slug, $dep_constraint, (string) $plugins[ $dep_file ]['Version'] );
			}
		}

		return $out;
	}

	/**
	 * Check every plugin, switch off what cannot run and switch back on what can again.
	 *
	 * @param string|null $core Clanbite version to judge against (default: the running one).
	 * @return array{disabled: list<string>, reenabled: list<string>}
	 */
	public static function sweep( ?string $core = null ): array {
		$result = array(
			'disabled'  => array(),
			'reenabled' => array(),
		);
		if ( self::$running || ! self::enabled() ) {
			return $result;
		}
		self::$running = true;
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$core    = $core ?? \Kernowdev\Clanbite\Main::VERSION;
		$plugins = get_plugins();
		$active  = self::active_files();
		$store   = self::disabled();

		$failing = self::evaluate( $core, $plugins, $active );
		foreach ( $failing as $file => $reasons ) {
			$network = is_multisite() && is_plugin_active_for_network( $file );
			// Silent: the incompatible plugin's own deactivation code must not run.
			deactivate_plugins( $file, true, $network );
			$store[ $file ]       = array(
				'name'    => (string) $plugins[ $file ]['Name'],
				'version' => (string) $plugins[ $file ]['Version'],
				'reasons' => $reasons,
				'core'    => $core,
				'time'    => time(),
			);
			$result['disabled'][] = $file;

			/**
			 * Fires after an incompatible add-on was deactivated.
			 *
			 * @since 1.6.0
			 *
			 * @param string   $file    Plugin file.
			 * @param string[] $reasons Why it cannot run.
			 * @param string   $core    Clanbite version it was judged against.
			 */
			do_action( 'clanbite_addon_disabled', $file, $reasons, $core );
		}

		// Switch back on what this guard disabled and is compatible again (only in admin or cron, where activation is safe). Repeat
		// until nothing more comes back: one add-on can depend on another that has just been restored.
		if ( $store && ( is_admin() || wp_doing_cron() ) ) {
			for ( $pass = 0; $pass < 5; $pass++ ) {
				$progress   = false;
				$active_now = self::active_files();
				foreach ( $store as $file => $entry ) {
					if ( in_array( $file, $result['disabled'], true ) ) {
						continue;
					}
					if ( ! isset( $plugins[ $file ] ) ) {
						unset( $store[ $file ] );
						continue;
					}
					if ( in_array( $file, $active_now, true ) ) {
						unset( $store[ $file ] ); // Someone turned it back on by hand and it is fine.
						continue;
					}
					if ( array() !== self::evaluate( $core, $plugins, array_merge( $active_now, array( $file ) ) ) ) {
						continue;
					}
					$activated = activate_plugin( $file, '', false, false );
					if ( ! is_wp_error( $activated ) ) {
						unset( $store[ $file ] );
						$result['reenabled'][] = $file;
						$progress              = true;

						/**
						 * Fires after an add-on that was switched off came back because it is compatible again.
						 *
						 * @since 1.6.0
						 *
						 * @param string $file Plugin file.
						 */
						do_action( 'clanbite_addon_reenabled', $file );
						break;
					}
				}
				if ( ! $progress ) {
					break;
				}
			}
		}

		update_option( self::OPTION, $store, false );
		$guarded = array();
		foreach ( $plugins as $file => $data ) {
			if ( '' !== self::constraint_for( $data ) ) {
				$guarded[] = (string) $file;
			}
		}
		update_option( self::SIGNATURE . '_files', $guarded, true );
		update_option( self::SIGNATURE, self::signature(), true );
		self::$running = false;

		return $result;
	}

	/**
	 * What a Clanbite update would switch off.
	 *
	 * @param string $new_core The Clanbite version being offered.
	 * @return array<string, list<string>> Plugin name => reasons.
	 */
	public static function preview( string $new_core ): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		$out     = array();
		foreach ( self::evaluate( $new_core, $plugins, self::active_files() ) as $file => $reasons ) {
			$out[ (string) $plugins[ $file ]['Name'] ] = $reasons;
		}

		return $out;
	}

	/**
	 * Notice listing what was switched off.
	 *
	 * @return void
	 */
	public static function notice(): void {
		$disabled = self::disabled();
		if ( ! $disabled || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Clanbite turned off add-ons that cannot run on this version so they cannot break your site:', 'clanbite' ) . '</strong></p><ul style="list-style:disc;margin-left:20px">';
		foreach ( $disabled as $entry ) {
			echo '<li>' . esc_html( (string) $entry['name'] . ' ' . (string) $entry['version'] ) . ': ' . esc_html( implode( '; ', array_map( 'strval', (array) $entry['reasons'] ) ) ) . '</li>';
		}
		echo '</ul><p>' . esc_html__( 'They turn back on by themselves as soon as a compatible version is installed.', 'clanbite' ) . ' <a href="' . esc_url( admin_url( 'update-core.php?force-check=1' ) ) . '">' . esc_html__( 'Check for updates', 'clanbite' ) . '</a></p></div>';
	}

	/**
	 * Explain it under each switched-off plugin on the Plugins screen.
	 *
	 * @return void
	 */
	public static function register_rows(): void {
		foreach ( array_keys( self::disabled() ) as $file ) {
			add_action( 'after_plugin_row_' . $file, array( self::class, 'plugin_row' ), 10, 1 );
		}
	}

	/**
	 * One explanatory row.
	 *
	 * @param string $plugin_file Plugin file.
	 * @return void
	 */
	public static function plugin_row( $plugin_file ): void {
		$entry = self::disabled()[ (string) $plugin_file ] ?? null;
		if ( ! $entry ) {
			return;
		}
		$list    = function_exists( '_get_list_table' ) ? _get_list_table( 'WP_Plugins_List_Table' ) : null;
		$columns = $list ? (int) $list->get_column_count() : 4;
		echo '<tr class="plugin-update-tr inactive"><td colspan="' . esc_attr( (string) $columns ) . '" class="plugin-update colspanchange"><div class="notice inline notice-warning notice-alt" style="margin:0 20px 10px"><p>' . esc_html__( 'Turned off by Clanbite:', 'clanbite' ) . ' ' . esc_html( implode( '; ', array_map( 'strval', (array) $entry['reasons'] ) ) ) . '. ' . esc_html__( 'It turns back on by itself when a compatible version is installed.', 'clanbite' ) . '</p></div></td></tr>';
	}

	/**
	 * Warn on Clanbite's own update row about add-ons that the new version would switch off.
	 *
	 * @return void
	 */
	public static function register_core_warning(): void {
		add_action( 'in_plugin_update_message-' . clanbite()->basename, array( self::class, 'core_update_warning' ), 10, 2 );
	}

	/**
	 * The warning.
	 *
	 * @param array<string, mixed> $plugin_data Plugin headers.
	 * @param object               $response    Update response (`new_version`).
	 * @return void
	 */
	public static function core_update_warning( $plugin_data, $response ): void {
		unset( $plugin_data );
		$new = isset( $response->new_version ) ? (string) $response->new_version : '';
		if ( '' === $new || ! self::enabled() ) {
			return;
		}
		$affected = self::preview( $new );
		if ( ! $affected ) {
			return;
		}
		$lines = array();
		foreach ( $affected as $name => $reasons ) {
			$lines[] = $name . ' (' . implode( '; ', array_map( 'strval', $reasons ) ) . ')';
		}
		printf(
			/* translators: 1: Clanbite version, 2: list of add-ons */
			'<br><strong>' . esc_html__( 'Heads up: Clanbite %1$s is not compatible with these add-ons, which would be turned off until updated: %2$s', 'clanbite' ) . '</strong>',
			esc_html( $new ),
			esc_html( implode( ', ', $lines ) )
		);
	}

	/**
	 * Site Health entry.
	 *
	 * @param array<string, array<string, mixed>> $tests Tests so far.
	 * @return array<string, array<string, mixed>>
	 */
	public static function site_health_tests( $tests ) {
		if ( is_array( $tests ) ) {
			$tests['direct']['clanbite_addon_compat'] = array(
				'label' => __( 'Clanbite add-on compatibility', 'clanbite' ),
				'test'  => array( self::class, 'site_health_test' ),
			);
		}

		return $tests;
	}

	/**
	 * Site Health result.
	 *
	 * @return array<string, mixed>
	 */
	public static function site_health_test(): array {
		$result   = array(
			'label'       => __( 'All Clanbite add-ons are compatible with this site', 'clanbite' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Clanbite', 'clanbite' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html__( 'No add-on had to be turned off.', 'clanbite' ) . '</p>',
			'actions'     => '',
			'test'        => 'clanbite_addon_compat',
		);
		$disabled = self::disabled();
		if ( $disabled ) {
			$items = '';
			foreach ( $disabled as $entry ) {
				$items .= '<li>' . esc_html( (string) $entry['name'] . ' ' . (string) $entry['version'] . ': ' . implode( '; ', array_map( 'strval', (array) $entry['reasons'] ) ) ) . '</li>';
			}
			$result['status']      = 'recommended';
			$result['label']       = __( 'Some Clanbite add-ons were turned off because they are not compatible', 'clanbite' );
			$result['description'] = '<ul>' . $items . '</ul><p>' . esc_html__( 'Update them to turn them back on.', 'clanbite' ) . '</p>';
			$result['actions']     = '<a href="' . esc_url( admin_url( 'update-core.php?force-check=1' ) ) . '">' . esc_html__( 'Check for updates', 'clanbite' ) . '</a>';
		}
		if ( ! self::enabled() ) {
			$result['status']      = 'recommended';
			$result['label']       = __( 'Clanbite add-on protection is switched off', 'clanbite' );
			$result['description'] = '<p>' . esc_html__( 'An add-on that is not compatible with this Clanbite version can crash the site. Remove CLANBITE_DISABLE_ADDON_GUARD (or the clanbite_addon_guard_enabled filter) to turn the protection back on.', 'clanbite' ) . '</p>';
		}

		return $result;
	}
}
