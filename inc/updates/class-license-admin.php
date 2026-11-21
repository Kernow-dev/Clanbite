<?php
/**
 * The Licence tab in Clanbite → Settings, notices and Site Health.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Updates;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an integration tab (`slotId` {@see License_Admin::SLOT_ID}) whose view is `assets/dist/js/clanbite-license.js`, mounted through the
 * core admin's `clanbite:admin-integration-mount` event. The tab only appears when an add-on that uses the update server is
 * installed (or the site is already activated).
 */
final class License_Admin {

	public const SLOT_ID = 'clanbite-license';

	public const TAB_ID = 'license';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'clanbite_admin_rest_tabs', array( self::class, 'add_tab' ), 20 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_filter( 'site_status_tests', array( self::class, 'site_health_tests' ) );
		add_action( 'admin_notices', array( self::class, 'notice' ) );
		add_action( 'admin_post_clanbite_license_activate', array( self::class, 'handle_activate' ) );
		add_action( 'admin_post_clanbite_license_deactivate', array( self::class, 'handle_deactivate' ) );
		add_action( 'load-plugins.php', array( self::class, 'register_rows' ) );
	}

	/**
	 * Whether the inline licence row has already been printed on this Plugins screen (it appears once, under the first add-on).
	 *
	 * @var bool
	 */
	private static bool $row_printed = false;

	/**
	 * Hook the inline licence row under each participating add-on (only the first one prints it).
	 *
	 * @return void
	 */
	public static function register_rows(): void {
		if ( ! License_Rest::can_manage() || ( ! License_Client::is_activated() && ! License_Client::needs_license() ) ) {
			return; // Only free add-ons and no key: nothing to ask for.
		}
		foreach ( array_keys( Updater::discover( get_plugins() ) ) as $file ) {
			add_action( 'after_plugin_row_' . $file, array( self::class, 'plugin_row' ), 10, 3 );
		}
	}

	/**
	 * One row under the first add-on: a key field when the site is not activated, otherwise the licence state and a Deactivate link.
	 *
	 * Works without JavaScript: the form posts to `admin-post.php`.
	 *
	 * @param string               $plugin_file Plugin file.
	 * @param array<string, mixed> $plugin_data Plugin headers.
	 * @param string               $status      Plugin list status.
	 * @return void
	 */
	public static function plugin_row( $plugin_file, $plugin_data = array(), $status = '' ): void {
		unset( $plugin_data, $status );
		if ( self::$row_printed ) {
			return;
		}
		self::$row_printed = true;

		$list    = function_exists( '_get_list_table' ) ? _get_list_table( 'WP_Plugins_List_Table' ) : null;
		$columns = $list ? (int) $list->get_column_count() : 4;
		$active  = is_plugin_active( (string) $plugin_file ) ? 'active' : 'inactive';
		$summary = License_Client::summary();

		echo '<tr class="plugin-update-tr ' . esc_attr( $active ) . '" id="clanbite-license-row"><td colspan="' . esc_attr( (string) $columns ) . '" class="plugin-update colspanchange"><div class="notice inline notice-alt ' . ( $summary['activated'] ? 'notice-success' : 'notice-info' ) . '" style="margin:0 20px 10px"><p>';
		if ( $summary['activated'] ) {
			$license = (array) $summary['license'];
			$status  = (string) ( $license['status'] ?? 'active' );
			echo '<strong>' . esc_html__( 'Clanbite licence', 'clanbite' ) . '</strong> <code>' . esc_html( (string) $summary['hint'] ) . '</code> · ';
			echo 'active' === $status ? esc_html__( 'active on this site', 'clanbite' ) : esc_html( sprintf( /* translators: %s: licence status */ __( 'status: %s', 'clanbite' ), $status ) );
			echo ' · <a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Manage', 'clanbite' ) . '</a> · ';
			printf(
				'<a href="%1$s" onclick="return confirm(%2$s)">%3$s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=clanbite_license_deactivate' ), 'clanbite_license_deactivate' ) ),
				esc_attr( wp_json_encode( __( 'Deactivate the licence on this site? It keeps working, but stops receiving add-on updates until you activate it again. The seat becomes free for another site.', 'clanbite' ) ) ),
				esc_html__( 'Deactivate this site', 'clanbite' )
			);
		} else {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">';
			echo '<input type="hidden" name="action" value="clanbite_license_activate" />';
			wp_nonce_field( 'clanbite_license_activate' );
			echo '<strong>' . esc_html__( 'Activate your Clanbite licence to receive add-on updates:', 'clanbite' ) . '</strong> ';
			if ( $summary['constant'] ) {
				echo '<span class="description">' . esc_html__( 'A key is defined in wp-config.php.', 'clanbite' ) . '</span>';
			} else {
				echo '<label class="screen-reader-text" for="clanbite-license-key">' . esc_html__( 'Licence key', 'clanbite' ) . '</label>';
				echo '<input type="text" id="clanbite-license-key" name="clanbite_license_key" class="regular-text" placeholder="CB-XXXX-XXXX-XXXX-XXXX" autocomplete="off" spellcheck="false" required />';
			}
			echo '<button type="submit" class="button button-primary">' . esc_html__( 'Activate', 'clanbite' ) . '</button>';
			echo '</form>';
		}
		echo '</p></div></td></tr>';
	}

	/**
	 * `admin-post.php?action=clanbite_license_activate`.
	 *
	 * @return void
	 */
	public static function handle_activate(): void {
		self::guard( 'clanbite_license_activate' );
		$key    = isset( $_POST['clanbite_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['clanbite_license_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in guard().
		$result = '' === $key && License_Client::key_from_constant() ? License_Client::activate( License_Client::key() ) : License_Client::activate( $key );
		self::flash_and_back(
			! is_wp_error( $result ),
			is_wp_error( $result ) ? $result->get_error_message() : __( 'Licence activated. Add-on updates will now appear under Updates.', 'clanbite' )
		);
	}

	/**
	 * `admin-post.php?action=clanbite_license_deactivate`.
	 *
	 * @return void
	 */
	public static function handle_deactivate(): void {
		self::guard( 'clanbite_license_deactivate' );
		$result = License_Client::deactivate();
		self::flash_and_back(
			! is_wp_error( $result ),
			is_wp_error( $result ) ? $result->get_error_message() : __( 'Licence deactivated for this site. Its seat is free again.', 'clanbite' )
		);
	}

	/**
	 * Capability and nonce check for the admin-post handlers.
	 *
	 * @param string $action Nonce action.
	 * @return void
	 */
	private static function guard( string $action ): void {
		if ( ! License_Rest::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to manage the Clanbite licence.', 'clanbite' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Remember a message for the next screen and go back.
	 *
	 * @param bool   $ok      Whether it worked.
	 * @param string $message Message.
	 * @return void
	 */
	private static function flash_and_back( bool $ok, string $message ): void {
		set_transient(
			'clanbite_license_flash_' . get_current_user_id(),
			array(
				'ok'      => $ok,
				'message' => $message,
			),
			MINUTE_IN_SECONDS
		);
		$back = wp_get_referer();
		wp_safe_redirect( $back && str_contains( $back, 'plugins.php' ) ? $back : admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * URL of the Licence tab.
	 *
	 * @return string
	 */
	public static function url(): string {
		return add_query_arg(
			array(
				'page' => 'clanbite',
				'tab'  => self::TAB_ID,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Whether the Licence tab is worth showing on this site.
	 *
	 * @return bool
	 */
	private static function relevant(): bool {
		return License_Client::is_activated() || array() !== Updater::discover( get_plugins() );
	}

	/**
	 * Add the tab.
	 *
	 * @param array<int, array<string, mixed>> $tabs Tabs so far.
	 * @return array<int, array<string, mixed>>
	 */
	public static function add_tab( $tabs ) {
		if ( ! is_array( $tabs ) || ! self::relevant() ) {
			return $tabs;
		}
		$tabs[] = array(
			'id'     => self::TAB_ID,
			'type'   => 'integration',
			'label'  => __( 'Licence', 'clanbite' ),
			'slotId' => self::SLOT_ID,
		);

		return $tabs;
	}

	/**
	 * Load the view script on the settings screen.
	 *
	 * @param string $hook_suffix Admin page hook.
	 * @return void
	 */
	public static function enqueue( $hook_suffix ): void {
		if ( 'toplevel_page_clanbite' !== $hook_suffix || ! self::relevant() ) {
			return;
		}
		wp_enqueue_script(
			'clanbite-license',
			clanbite()->url . 'assets/dist/js/clanbite-license.js',
			array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ),
			\Kernowdev\Clanbite\Main::VERSION,
			true
		);
		wp_enqueue_style(
			'clanbite-license',
			clanbite()->url . 'assets/dist/css/clanbite-license.css',
			array(),
			\Kernowdev\Clanbite\Main::VERSION
		);
		wp_set_script_translations( 'clanbite-license', 'clanbite' );
	}

	/**
	 * Site Health: is the licence set up and the server reachable?
	 *
	 * @param array<string, array<string, mixed>> $tests Tests so far.
	 * @return array<string, array<string, mixed>>
	 */
	public static function site_health_tests( $tests ) {
		if ( is_array( $tests ) && self::relevant() ) {
			$tests['direct']['clanbite_license'] = array(
				'label' => __( 'Clanbite licence and updates', 'clanbite' ),
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
		$result = array(
			'label'       => __( 'Your Clanbite licence is active and updates are working', 'clanbite' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Clanbite', 'clanbite' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html__( 'Licensed add-ons can receive updates.', 'clanbite' ) . '</p>',
			'actions'     => '',
			'test'        => 'clanbite_license',
		);
		$state  = License_Client::get_state();
		$link   = '<p><a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Open the Licence tab', 'clanbite' ) . '</a></p>';

		if ( ! License_Client::is_activated() && ! License_Client::needs_license() ) {
			$result['label']       = __( 'Your Clanbite add-ons are free and need no licence', 'clanbite' );
			$result['description'] = '<p>' . esc_html__( 'Updates for free add-ons are checked without a licence.', 'clanbite' ) . '</p>';

			return $result;
		}
		if ( ! License_Client::is_activated() ) {
			$result['status']      = 'recommended';
			$result['label']       = __( 'Your Clanbite licence is not activated', 'clanbite' );
			$result['description'] = '<p>' . esc_html__( 'Clanbite add-ons are installed but this site has no active licence, so it will not receive their updates.', 'clanbite' ) . '</p>';
			$result['actions']     = $link;

			return $result;
		}
		if ( '' !== $state['error'] ) {
			$result['status']      = 'recommended';
			$result['label']       = __( 'Clanbite could not check for add-on updates', 'clanbite' );
			$result['description'] = '<p>' . esc_html( $state['error'] ) . '</p>';
			$result['actions']     = $link;

			return $result;
		}
		$status = (string) ( $state['license']['status'] ?? 'active' );
		if ( 'active' !== $status ) {
			$result['status']      = 'recommended';
			$result['label']       = __( 'Your Clanbite licence needs attention', 'clanbite' );
			$result['description'] = '<p>' . esc_html( sprintf( /* translators: %s: licence status */ __( 'Licence status: %s.', 'clanbite' ), $status ) ) . '</p>';
			$result['actions']     = $link;
		}

		return $result;
	}

	/**
	 * Remind administrators to activate when add-ons are installed without a licence.
	 *
	 * @return void
	 */
	public static function notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'update-core' ), true ) || ! License_Rest::can_manage() ) {
			return;
		}
		$flash = get_transient( 'clanbite_license_flash_' . get_current_user_id() );
		if ( is_array( $flash ) ) {
			delete_transient( 'clanbite_license_flash_' . get_current_user_id() );
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( ! empty( $flash['ok'] ) ? 'success' : 'error' ), esc_html( (string) ( $flash['message'] ?? '' ) ) );

			return;
		}
		if ( ! License_Client::needs_license() ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'Enter your Clanbite licence key to receive updates for your add-ons.', 'clanbite' ),
			esc_url( self::url() ),
			esc_html__( 'Activate licence', 'clanbite' )
		);
	}
}
