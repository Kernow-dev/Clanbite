<?php
/**
 * One-time migration: legacy CPT identifiers (`cp_*`) → `clanbite_*` post types.
 *
 * Post meta keys keep their historical `cp_*` prefixes so existing rows in wp_postmeta keep working.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option key storing the migration marker (value matches plugin version when complete).
 */
const CLANBITE_CPT_IDS_MIGRATION_OPTION = 'clanbite_cpt_identifiers_v202602';

/**
 * Map legacy CPT slugs to prefixed slugs (longest legacy slug first).
 *
 * @return array<string, string>
 */
function clanbite_cpt_identifier_migration_map(): array {
	return array(
		'cp_team_challenge' => 'clanbite_team_challenge',
		'cp_team'           => 'clanbite_team',
		'cp_match'          => 'clanbite_match',
		'cp_event'          => 'clanbite_event',
	);
}

/**
 * Migrate `wp_posts.post_type` values on the current blog (no-op when already migrated).
 *
 * @return void
 */
function clanbite_migrate_cpt_identifiers_for_current_site(): void {
	$done_for = get_option( CLANBITE_CPT_IDS_MIGRATION_OPTION, '' );
	if ( $done_for === \Kernowdev\Clanbite\Main::VERSION ) {
		return;
	}

	global $wpdb;

	$map = clanbite_cpt_identifier_migration_map();
	uksort(
		$map,
		static function ( string $a, string $b ): int {
			return strlen( $b ) <=> strlen( $a );
		}
	);

	foreach ( $map as $old => $new ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration; no portable API for bulk post_type updates.
		$wpdb->update(
			$wpdb->posts,
			array( 'post_type' => $new ),
			array( 'post_type' => $old ),
			array( '%s' ),
			array( '%s' )
		);
	}

	update_option( CLANBITE_CPT_IDS_MIGRATION_OPTION, \Kernowdev\Clanbite\Main::VERSION, true );

	// CPT rewrite tags register on `init`; flush afterward so rules include new slugs.
	update_option( 'clanbite_pending_rewrite_flush', '1', false );
}

/**
 * Run CPT slug migration on all sites (multisite) or the current site.
 *
 * @return void
 */
function clanbite_maybe_migrate_cpt_identifiers(): void {
	if ( wp_installing() ) {
		return;
	}

	if ( is_multisite() ) {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			clanbite_migrate_cpt_identifiers_for_current_site();
			restore_current_blog();
		}

		return;
	}

	clanbite_migrate_cpt_identifiers_for_current_site();
}

/**
 * Flush permalinks once after CPT slug migration (must run after post types register).
 *
 * @return void
 */
function clanbite_flush_rewrite_rules_after_cpt_migration(): void {
	if ( '1' !== get_option( 'clanbite_pending_rewrite_flush', '' ) ) {
		return;
	}

	delete_option( 'clanbite_pending_rewrite_flush' );
	flush_rewrite_rules( false );
}

add_action( 'init', 'clanbite_flush_rewrite_rules_after_cpt_migration', 999 );
