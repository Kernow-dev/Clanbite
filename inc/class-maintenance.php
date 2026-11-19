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
}
