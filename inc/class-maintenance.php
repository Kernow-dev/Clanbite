<?php
/**
 * Maintenance class for handling database updates and other maintenance tasks.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite;

defined( 'ABSPATH' ) || exit;


/**
 * Runs one-shot maintenance steps keyed by {@see Main::MAINTENANCE_VERSION}.
 *
 * Add a step as `run_N()` and raise {@see Main::MAINTENANCE_VERSION} to N; each step runs once per site, in order, on the first request after the update (and on a fresh install, where it finds nothing to change). Steps must be safe to run twice.
 */
class Maintenance {

	/**
	 * Run maintenance tasks for a specific version.
	 *
	 * @param int $version The maintenance version to run.
	 * @return void
	 */
	public static function run( int $version ): void {
		$method = 'run_' . $version;
		if ( method_exists( __CLASS__, $method ) ) {
			self::$method();
		}
	}

	/**
	 * Initial public release: flush rewrite rules so virtual routes and CPT rules resolve.
	 *
	 * @return void
	 */
	private static function run_1(): void {
		flush_rewrite_rules( false );
	}

	/**
	 * The rebrand: everything stored under an old name moves to its Clanbite name.
	 *
	 * First the post types (`cp_team` to `clanbite_team`, and so on), then every remaining `cp_` name: extension slugs, meta keys,
	 * options, saved template slugs and the add-ons' tables. See `inc/clanbite-cpt-identifier-migration.php` and
	 * `inc/clanbite-legacy-prefix-migration.php`; each keeps its own marker so it never repeats.
	 *
	 * @return void
	 */
	private static function run_2(): void {
		clanbite_maybe_migrate_cpt_identifiers();
		clanbite_maybe_migrate_legacy_prefix();
	}

	/**
	 * Add-on installing and updating moved to the Add-on Manager: forget cached plugin update data, which may still hold
	 * download addresses that only Clanbite's old updater could use. The stored licence activation is kept for the manager.
	 *
	 * @return void
	 */
	private static function run_3(): void {
		delete_site_transient( 'update_plugins' );
	}

	/**
	 * WordPress 7.2 took `/random/` for its random-post addresses, so it can no longer start player, team or group addresses.
	 * 1.9.0 allowed it: a site that chose it moves back to the default word (or, if another area has taken that, the default
	 * with `-2`). Old links are redirected. The group setting belongs to Social Kit, which follows the same address rules.
	 *
	 * @return void
	 */
	private static function run_4(): void {
		// Area => settings option, key in it. Each area's default word is its own name.
		$areas = array(
			'players' => array( 'clanbite_players_settings', 'player_url_slug' ),
			'teams'   => array( 'clanbite_teams_settings', 'team_url_slug' ),
			'groups'  => array( 'clanbite_social_kit_settings', 'group_url_slug' ),
		);
		// Read from the stored settings: extensions have not started yet when maintenance runs.
		$in_use = static function ( string $area ) use ( $areas ): string {
			$settings = get_option( $areas[ $area ][0], array() );
			$slug     = is_array( $settings ) ? (string) ( $settings[ $areas[ $area ][1] ] ?? '' ) : '';

			return '' === $slug ? $area : $slug;
		};

		foreach ( $areas as $area => list( $option, $key ) ) {
			if ( 'random' !== $in_use( $area ) ) {
				continue;
			}
			$others = array_map( $in_use, array_values( array_diff( array_keys( $areas ), array( $area ) ) ) );
			$slug   = in_array( $area, $others, true ) ? $area . '-2' : $area;

			$settings         = (array) get_option( $option, array() );
			$settings[ $key ] = $slug === $area ? '' : $slug;
			update_option( $option, $settings );
			\clanbite_url_slug_changed( $area, 'random', $slug );
		}
	}
}
