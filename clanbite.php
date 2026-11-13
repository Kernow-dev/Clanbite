<?php
/**
 * Plugin Name: Clanbite: Team Management System
 * Plugin URI: https://clanbite.com
 * Description: Community management system for Gamers and Sports teams
 * Version: 1.5.0
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Author: kernow.dev
 * Author URI: https://kernow.dev
 * Donate link: https://kernow.dev
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clanbite
 * Domain Path: /languages
 *
 * @link    https://clanbite.com/
 *
 * @package clanbite
 * @version 1.3.0
 */

namespace Kernowdev\Clanbite;

defined( 'ABSPATH' ) || exit;

use AllowDynamicProperties;
use Kernowdev\Clanbite\Admin\Settings;
use Kernowdev\Clanbite\Extensions\Loader as Extension_Loader;
use Kernowdev\Clanbite\Cross_Site_Match_Sync;
use Kernowdev\Clanbite\Public_Rest;
use Kernowdev\Clanbite\Wordban;

// Composer autoload handles all procedural functions and classes.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Fallback for production if vendor folder is missing (manual upload without composer).
	require_once __DIR__ . '/inc/clanbite-wp-admin-includes.php';
	require_once __DIR__ . '/inc/class-block-patterns.php';
	require_once __DIR__ . '/inc/functions-block-templates.php';
	require_once __DIR__ . '/inc/functions-request-input.php';
	require_once __DIR__ . '/inc/clanbite-private-media.php';
	require_once __DIR__ . '/inc/clanbite-team-challenge-uploads.php';
	require_once __DIR__ . '/inc/clanbite-block-render-escape.php';
	require_once __DIR__ . '/inc/functions-country-flags.php';
	require_once __DIR__ . '/inc/functions-block-entity-link.php';
	require_once __DIR__ . '/inc/profile-subpages.php';
	require_once __DIR__ . '/inc/visibility-container.php';
	require_once __DIR__ . '/inc/avatar-helpers.php';
	require_once __DIR__ . '/inc/extensions/players/functions.php';
	require_once __DIR__ . '/inc/extensions/teams/functions.php';
	require_once __DIR__ . '/inc/groups/functions.php';
	require_once __DIR__ . '/build/shared/components/avatar-progress-bar.php';
	require_once __DIR__ . '/build/shared/components/avatar-rank-icon.php';
	require_once __DIR__ . '/shortcut-function.php';
}

// Loaded here only (not via Composer's `files`), so it works with a stale vendor/ and is never declared twice.
require_once __DIR__ . '/inc/admin/functions.php';
require_once __DIR__ . '/inc/toasts.php';
require_once __DIR__ . '/inc/messages.php';

/**
 * Main initiation class.
 */
#[AllowDynamicProperties]
final class Main {
	/**
	 * Current version.
	 *
	 * @var string
	 */
	public const VERSION = '1.5.0';

	/**
	 * Maintenance upgrade counter (single step for 1.0.0 public release).
	 *
	 * @var int
	 */
	public const MAINTENANCE_VERSION = 1;

	/**
	 * Singleton instance of plugin.
	 *
	 * @var Main|null
	 */
	protected static ?Main $instance = null;

	/**
	 * The token, used to prefix values in DB.
	 *
	 * @var   string
	 */
	protected string $_token = 'clanbite_';

	/**
	 * URL of the plugin directory.
	 *
	 * @var string
	 */
	public string $url = '';

	/**
	 * Path of the plugin directory.
	 *
	 * @var string
	 */
	public string $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	public string $basename = '';

	/**
	 * The main plugin file.
	 *
	 * @var string
	 */
	public string $file;

	/**
	 * Detailed activation error messages.
	 *
	 * @var array
	 */
	protected array $activation_errors = array();

	/**
	 * Extension loader (assigned in {@see init()} after textdomain load).
	 *
	 * @var Extension_Loader|null Null until {@see init()} runs.
	 */
	public ?Extension_Loader $extensions = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return Main A single instance of this class.
	 */
	public static function instance(): Main {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets up our plugin.
	 */
	protected function __construct() {
		$this->file     = basename( __FILE__ );
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Activate the plugin.
	 */
	public function _activate(): void {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		Extension_Loader::persist_required_extensions_on_activation();
		// Regenerate on next request so extensions that register rewrites on `init` are included.
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Check if the plugin meets requirements before bootstrapping.
	 *
	 * When requirements fail, an admin notice is shown and {@see init()} does not run.
	 * The plugin file stays loaded; the site owner fixes the environment or deactivates
	 * Clanbite manually from **Plugins** (WordPress.org discourages calling `deactivate_plugins()`).
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements(): bool {
		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Per WordPress.org guidelines, plugins must not programmatically change activation
		// state (including self-deactivation). Surface the failure in admin and skip bootstrapping
		// until requirements are fixed or the site owner deactivates Clanbite from Plugins.
		add_action(
			'all_admin_notices',
			array( $this, 'requirements_not_met_notice' )
		);

		return false;
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements(): bool {
		// Check PHP version (as declared in plugin header: Requires PHP: 8.2).
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			$this->activation_errors[] = sprintf(
				/* translators: %s: Current PHP version */
				__( 'Clanbite requires PHP 8.2 or higher. You are running version %s.', 'clanbite' ),
				PHP_VERSION
			);

			return false;
		}

		// Check WordPress version (as declared in plugin header: Requires at least: 6.9).
		global $wp_version;
		if ( version_compare( $wp_version, '6.9', '<' ) ) {
			$this->activation_errors[] = sprintf(
				/* translators: %s: Current WordPress version */
				__( 'Clanbite requires WordPress 6.9 or higher. You are running version %s.', 'clanbite' ),
				$wp_version
			);

			return false;
		}

		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 */
	public function requirements_not_met_notice(): void {
		// Compile default message.
		$default_message = sprintf(
			/* translators: %s: URL to the Plugins admin screen. */
			__(
				'Clanbite cannot run until requirements are met. Fix the issues below, or deactivate Clanbite on the <a href="%s">Plugins</a> screen if you need to continue without it.',
				'clanbite'
			),
			esc_url( admin_url( 'plugins.php' ) )
		);

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors ) {
			$details = '<small>' . implode(
				'</small><br /><small>',
				$this->activation_errors
			) . '</small>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<p><?php echo wp_kses_post( $default_message ); ?></p>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 */
	public function _deactivate(): void {
	}

	/**
	 * Hooks run at 0.
	 */
	public function early_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 1 );
	}

	/**
	 * Load translations early (`plugins_loaded`, priority 1).
	 *
	 * Since WordPress 6.7, translating a string before its text domain is loaded logs a "too early" notice. Every Clanbite
	 * plugin uses this same loader; WordPress still finds language packs in `WP_LANG_DIR` on its own.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Registers the plugin's languages path.
		load_plugin_textdomain( 'clanbite', false, dirname( $this->basename ) . '/languages' );
	}

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'init' ), 0 );

		Abilities_Api::register_hooks();

		add_action( 'init', array( Front_Seo::class, 'register' ), 20 );

		add_action( 'init', array( $this, 'register_shared_styles' ), 5 );
		add_filter( 'template_include', array( $this, 'enqueue_interactivity_for_plugin_templates' ), 9999 );
		add_action( 'init', array( $this, 'register_core_blocks' ), 20 );

		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_visibility_container_block_editor' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_icon_styles' ) );

		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'filter_plugin_action_links' ) );

		add_filter( 'block_categories_all', array( $this, 'register_block_categories' ), 5, 2 );

		// Scripts and styles.
		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_admin_styles' )
		);
	}

	/**
	 * Register block editor categories (core Clanbite + per-extension groups).
	 *
	 * @param array<int, array<string, mixed>> $categories Existing categories.
	 * @param \WP_Block_Editor_Context|null    $context    Editor context (unused).
	 * @return array<int, array<string, mixed>>
	 */
	public function register_block_categories( array $categories, $context ): array {
		$ours = array(
			array(
				'slug'  => 'clanbite',
				'title' => __( 'Clanbite', 'clanbite' ),
			),
			array(
				'slug'  => 'clanbite-players',
				'title' => __( 'Clanbite Players', 'clanbite' ),
			),
			array(
				'slug'  => 'clanbite-teams',
				'title' => __( 'Clanbite Teams', 'clanbite' ),
			),
			array(
				'slug'  => 'clanbite-matches',
				'title' => __( 'Clanbite Matches', 'clanbite' ),
			),
		);

		// Check if categories already exist to prevent duplicates.
		$existing_slugs = array_column( $categories, 'slug' );
		foreach ( $ours as $cat ) {
			if ( ! in_array( $cat['slug'], $existing_slugs, true ) ) {
				array_unshift( $categories, $cat );
			}
		}

		return $categories;
	}

	/**
	 * Prepend Settings and website links on the Plugins list screen.
	 *
	 * @param array<int, string> $links Existing action link HTML.
	 * @return array<int, string>
	 */
	public function filter_plugin_action_links( array $links ): array {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return $links;
		}

		$prepend = array();

		if ( current_user_can( 'manage_options' ) ) {
			$prepend[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'admin.php?page=clanbite' ) ),
				esc_html__( 'Settings', 'clanbite' )
			);
		}

		$prepend[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://clanbite.com/' ),
			esc_html__( 'Website', 'clanbite' )
		);

		return array_merge( $prepend, $links );
	}

	/**
	 * Init hooks
	 */
	public function init(): void {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		require_once $this->path . 'inc/groups/functions.php';

		// Perform maintenance.
		$this->maybe_run_maintenance();

		// Initialise plugin extensions before admin UI (settings tabs need extension metadata).
		$this->extensions = Extension_Loader::instance();

		// Must run on every request: REST (`/wp-json/...`) is not `is_admin()`, but the
		// React settings app calls `clanbite/v1/admin/*` from the browser.
		Settings::instance();

		add_action( 'rest_api_init', array( Public_Rest::class, 'register_routes' ) );
		add_action( 'rest_api_init', array( Cross_Site_Match_Sync::class, 'register_routes' ) );

		add_action( 'init', array( Block_Patterns::class, 'register' ), 100 );

		Wordban::register_hooks();
	}

	/**
	 * Register plugin blocks that are not tied to a single extension bundle.
	 *
	 * @return void
	 */
	public function register_core_blocks(): void {
		if ( ! $this->check_requirements() ) {
			return;
		}

		require_once $this->path . 'inc/visibility-container.php';

		$path = $this->path . 'build/core/visibility-container';
		if ( is_dir( $path ) ) {
			register_block_type( $path );
		}
	}

	/**
	 * Pass role labels into the block editor for the visibility container token fields.
	 *
	 * @return void
	 */
	public function localize_visibility_container_block_editor(): void {
		if ( ! function_exists( 'wp_roles' ) ) {
			return;
		}

		$registry = \WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'clanbite/visibility-container' );
		if ( ! $block instanceof \WP_Block_Type || empty( $block->editor_script ) ) {
			return;
		}

		$handle = $block->editor_script;
		if ( is_array( $handle ) ) {
			$handle = $handle[0];
		}
		$handle = (string) $handle;
		if ( '' === $handle || ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		/**
		 * Filter the editor script handle used to localize visibility-container role data.
		 *
		 * Default is the handle WordPress registered for the block’s `editorScript` (from block.json).
		 *
		 * @param string $handle Editor script handle.
		 */
		$handle = (string) apply_filters( 'clanbite_visibility_container_editor_script_handle', $handle );

		if ( '' === $handle || ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		$roles_out = array();
		foreach ( wp_roles()->roles as $slug => $details ) {
			$roles_out[] = array(
				'slug'  => $slug,
				'label' => translate_user_role( $details['name'] ),
			);
		}

		wp_localize_script(
			$handle,
			'clanbiteVisibilityContainer',
			array( 'roles' => $roles_out )
		);
	}

	/**
	 * Make the Interactivity API available to blocks rendered by a Clanbite plugin's own PHP template.
	 *
	 * Plugin PHP templates (group pages, classic-theme fallbacks) call `get_header()` first and render blocks afterwards, so
	 * script modules enqueued by those blocks come too late for the import map that `wp_head` prints. Without it the
	 * browser cannot resolve `@wordpress/interactivity` and every interactive block stays inert. Enqueuing a tiny module
	 * that depends on it before `wp_head` puts it in the import map; blocks enqueued later then resolve it.
	 *
	 * @param string $template Template file chosen for the request.
	 * @return string Unchanged template path.
	 */
	public function enqueue_interactivity_for_plugin_templates( string $template ): string {
		$plugins_dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) ) . 'clanbite';

		if ( str_starts_with( wp_normalize_path( $template ), $plugins_dir ) ) {
			add_action(
				'wp_enqueue_scripts',
				static function (): void {
					wp_enqueue_script_module( 'clanbite-interactivity-bootstrap' );
				},
				1
			);
		}

		return $template;
	}

	/**
	 * Register stylesheets shared by Clanbite and its companion plugins.
	 *
	 * `clanbite-toasts` (style and script) is the shared popup system, see `inc/toasts.php`.
	 *
	 * `clanbite-tokens` holds the theme-adaptive colour tokens (surface, text, border, accent, danger). `clanbite-buttons`
	 * is the button contract (defaults, variants, states, focus ring on top of theme.json button styles) and depends on the
	 * tokens. List a handle in a block's `style` array in `block.json`, or call `wp_enqueue_style()` from template output.
	 *
	 * @return void
	 */
	public function register_shared_styles(): void {
		wp_register_script_module(
			'clanbite-interactivity-bootstrap',
			$this->url . 'assets/js/clanbite-interactivity-bootstrap.js',
			array( '@wordpress/interactivity' ),
			self::VERSION
		);

		wp_register_style(
			'clanbite-tokens',
			$this->url . 'assets/css/clanbite-tokens.css',
			array(),
			self::VERSION
		);

		wp_register_style(
			'clanbite-buttons',
			$this->url . 'assets/css/clanbite-buttons.css',
			array( 'clanbite-tokens' ),
			self::VERSION
		);

		wp_register_style(
			'clanbite-forms',
			$this->url . 'assets/css/clanbite-forms.css',
			array( 'clanbite-tokens', 'clanbite-buttons' ),
			self::VERSION
		);

		wp_register_style(
			'clanbite-avatars',
			$this->url . 'assets/css/clanbite-avatars.css',
			array( 'clanbite-tokens' ),
			self::VERSION
		);

		// Shared popups. Extensions enqueue these (or call clanbite_enqueue_toasts()), or list the handle as a dependency.
		wp_register_style(
			'clanbite-toasts',
			$this->url . 'assets/css/clanbite-toasts.css',
			array( 'clanbite-tokens' ),
			self::VERSION
		);

		wp_register_script(
			'clanbite-toasts',
			$this->url . 'assets/js/clanbite-toasts.js',
			array( 'wp-hooks' ),
			self::VERSION,
			true
		);
		// The site's position and duration are attached just before the scripts print (see clanbite_attach_toast_config()).
	}

	/**
	 * Block inserter icon sharpening (see assets/block-icons-editor.css).
	 *
	 * @return void
	 */
	public function enqueue_block_editor_icon_styles(): void {
		if ( ! $this->check_requirements() ) {
			return;
		}

		wp_enqueue_style(
			'clanbite-block-icons-editor',
			$this->url . 'assets/block-icons-editor.css',
			array(),
			self::VERSION
		);
	}

	/**
	 * Enqueue admin CSS.
	 */
	public function enqueue_admin_styles(): void {
	}

	/**
	 * Check if any necessary maintenance tasks need to be run and execute them.
	 */
	public function maybe_run_maintenance(): void {
		$maintenance_version = (int) get_option(
			$this->_token . '_maint_version'
		);

		// Pre-1.0.0 installs used higher integers; normalize so the 1.0 maintenance step can run once.
		if ( $maintenance_version > self::MAINTENANCE_VERSION ) {
			$maintenance_version = 0;
		}

		if ( $maintenance_version < self::MAINTENANCE_VERSION ) {
			for (
				$version = $maintenance_version + 1;
				$version <= self::MAINTENANCE_VERSION;
				$version++
			) {
				Maintenance::run( $version );
			}

			update_option(
				$this->_token . '_maint_version',
				self::MAINTENANCE_VERSION
			);
		}
	}

	/**
	 * Returns the current version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return self::VERSION;
	}
}

require_once __DIR__ . '/inc/clanbite-cpt-identifier-migration.php';
// Before {@see Main::early_hooks} (priority 0): CPT rows must match registered slugs before first queries/hooks run.
add_action( 'plugins_loaded', 'clanbite_maybe_migrate_cpt_identifiers', -1 );

// Kick it off.
add_action( 'plugins_loaded', array( clanbite(), 'early_hooks' ), 0 );
add_action( 'plugins_loaded', array( clanbite(), 'hooks' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( clanbite(), '_activate' ) );
register_deactivation_hook( __FILE__, array( clanbite(), '_deactivate' ) );
