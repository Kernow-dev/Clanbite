<?php
/**
 * One-time migration: stored names that used the old `cp_` / `cp-` prefix now use `clanbite_` / `clanbite-`.
 *
 * Covers what lives in the database: extension slugs (installed list and per-extension data), post types, post / user / term /
 * comment meta keys, option names, template slugs and the custom tables of the add-ons. The rule is the same one the code was
 * renamed with: a name that starts with `cp_` (or has it after a separator) gets `clanbite_` instead.
 *
 * Runs once per site (marker option) before the extension loader reads the installed list, never touches a row that already has
 * the new name, and is safe to run twice. Add-ons call nothing here: their tables and keys are found by pattern.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option key storing the migration marker.
 */
const CLANBITE_LEGACY_PREFIX_MIGRATION_OPTION = 'clanbite_legacy_prefix_v1';

/**
 * New name for a stored identifier that may still use the old prefix.
 *
 * @param string $name Identifier such as `cp_team_wins`, `_cp_mentions` or `cp-visibility`.
 * @return string The same identifier with `clanbite_` / `clanbite-` in place of the old prefix.
 */
function clanbite_legacy_prefix_new_name( string $name ): string {
	return (string) preg_replace(
		array( '/(?<![A-Za-z0-9])cp_/', '/(?<![A-Za-z0-9])cp-/' ),
		array( 'clanbite_', 'clanbite-' ),
		$name
	);
}

/**
 * Whether a string is exactly one identifier with the old prefix.
 *
 * @param mixed $value Value to test.
 * @return bool
 */
function clanbite_legacy_prefix_is_legacy_name( $value ): bool {
	return is_string( $value ) && 1 === preg_match( '/^_?cp[_-][a-z0-9_-]+$/', $value );
}

/**
 * Rename legacy array keys and whole-string values, recursively (extension slugs in option arrays).
 *
 * A key whose new name is already present is dropped in favour of the existing one.
 *
 * @param mixed $data Option value.
 * @return mixed
 */
function clanbite_legacy_prefix_map_value( $data ) {
	if ( is_string( $data ) ) {
		return clanbite_legacy_prefix_is_legacy_name( $data ) ? clanbite_legacy_prefix_new_name( $data ) : $data;
	}
	if ( ! is_array( $data ) ) {
		return $data;
	}

	$out = array();
	foreach ( $data as $key => $value ) {
		if ( is_string( $key ) && clanbite_legacy_prefix_is_legacy_name( $key ) ) {
			$new = clanbite_legacy_prefix_new_name( $key );
			if ( array_key_exists( $new, $data ) ) {
				continue;
			}
			$key = $new;
		}
		$out[ $key ] = clanbite_legacy_prefix_map_value( $value );
	}

	return $out;
}

/**
 * Rename options and rewrite legacy slugs inside option arrays on the current site.
 *
 * @return void
 */
function clanbite_legacy_prefix_migrate_options(): void {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery -- One-time migration over the options table.
	$names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_value LIKE %s OR option_value LIKE %s",
			$wpdb->esc_like( 'cp_' ) . '%',
			'%' . $wpdb->esc_like( '_cp_' ) . '%',
			'%' . $wpdb->esc_like( 'cp_' ) . '%',
			'%' . $wpdb->esc_like( 'cp-' ) . '%'
		)
	);
	// phpcs:enable

	foreach ( $names as $name ) {
		$name = (string) $name;
		if ( 0 === strpos( $name, '_transient' ) || 0 === strpos( $name, '_site_transient' ) ) {
			continue;
		}

		$value = get_option( $name, null );
		if ( null === $value ) {
			continue;
		}
		$mapped = clanbite_legacy_prefix_map_value( $value );
		$new    = clanbite_legacy_prefix_new_name( $name );

		if ( $new === $name ) {
			if ( $mapped !== $value ) {
				update_option( $name, $mapped );
			}
			continue;
		}

		// Never overwrite a value that already lives under the new name.
		if ( null === get_option( $new, null ) ) {
			add_option( $new, $mapped );
		}
		delete_option( $name );
	}
}

/**
 * Rename meta keys in one meta table (old rows with a duplicate new key are removed first).
 *
 * @param string $table     Meta table name.
 * @param string $id_column Object id column (`post_id`, `user_id`, …).
 * @return void
 */
function clanbite_legacy_prefix_migrate_meta_table( string $table, string $id_column ): void {
	global $wpdb;

	// Old prefix => [ new prefix, first SQL position after the old prefix ].
	$prefixes = array(
		'cp_'  => array( 'clanbite_', 4 ),
		'_cp_' => array( '_clanbite_', 5 ),
	);

	foreach ( $prefixes as $old_prefix => $replacement ) {
		list( $new, $strip ) = $replacement;
		$pattern             = $wpdb->esc_like( $old_prefix ) . '%';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- One-time migration; table and column names are fixed by the callers.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE old FROM {$table} old INNER JOIN {$table} cur ON cur.{$id_column} = old.{$id_column} AND cur.meta_key = CONCAT( %s, SUBSTRING( old.meta_key, %d ) ) WHERE old.meta_key LIKE %s",
				$new,
				$strip,
				$pattern
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET meta_key = CONCAT( %s, SUBSTRING( meta_key, %d ) ) WHERE meta_key LIKE %s",
				$new,
				$strip,
				$pattern
			)
		);
		// phpcs:enable
	}
}

/**
 * Rename post types and template slugs on the current site.
 *
 * @return void
 */
function clanbite_legacy_prefix_migrate_posts(): void {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- One-time migration over core tables.
	$types = $wpdb->get_col(
		$wpdb->prepare( "SELECT DISTINCT post_type FROM {$wpdb->posts} WHERE post_type LIKE %s", $wpdb->esc_like( 'cp_' ) . '%' )
	);
	foreach ( $types as $type ) {
		$wpdb->update( $wpdb->posts, array( 'post_type' => clanbite_legacy_prefix_new_name( (string) $type ) ), array( 'post_type' => $type ) );
	}

	// Saved Site Editor templates keep their customisations under the new slug and markup names.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_type IN ( 'wp_template', 'wp_template_part', 'wp_block', 'wp_navigation' ) AND ( post_name LIKE %s OR post_content LIKE %s OR post_content LIKE %s )",
			'%' . $wpdb->esc_like( 'cp_' ) . '%',
			'%' . $wpdb->esc_like( 'cp_' ) . '%',
			'%' . $wpdb->esc_like( 'cp-' ) . '%'
		)
	);
	foreach ( $rows as $row ) {
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_name'    => clanbite_legacy_prefix_new_name( (string) $row->post_name ),
				'post_content' => clanbite_legacy_prefix_new_name( (string) $row->post_content ),
			),
			array( 'ID' => (int) $row->ID )
		);
	}
	// phpcs:enable
}

/**
 * Rename the add-ons' custom tables (`{prefix}cp_*`) on the current site.
 *
 * A new table that already exists and is empty (an add-on created it first) is replaced by the old one with its rows.
 *
 * @return void
 */
function clanbite_legacy_prefix_migrate_tables(): void {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- One-time DDL; names come from SHOW TABLES.
	$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'cp_' ) . '%' ) );
	foreach ( $tables as $old ) {
		$old = (string) $old;
		$new = $wpdb->prefix . clanbite_legacy_prefix_new_name( substr( $old, strlen( $wpdb->prefix ) ) );

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $new ) ) ) ) {
			if ( 0 !== (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$new}`" ) ) {
				continue; // The new table holds data: leave both alone rather than guess.
			}
			$wpdb->query( "DROP TABLE `{$new}`" );
		}
		$wpdb->query( "RENAME TABLE `{$old}` TO `{$new}`" );
	}
	// phpcs:enable
}

/**
 * Migrate the current site (no-op once the marker is set).
 *
 * @return void
 */
function clanbite_legacy_prefix_migrate_current_site(): void {
	if ( '' !== (string) get_option( CLANBITE_LEGACY_PREFIX_MIGRATION_OPTION, '' ) ) {
		return;
	}

	global $wpdb;

	clanbite_legacy_prefix_migrate_options();
	clanbite_legacy_prefix_migrate_posts();
	clanbite_legacy_prefix_migrate_meta_table( $wpdb->postmeta, 'post_id' );
	clanbite_legacy_prefix_migrate_meta_table( $wpdb->usermeta, 'user_id' );
	clanbite_legacy_prefix_migrate_meta_table( $wpdb->termmeta, 'term_id' );
	clanbite_legacy_prefix_migrate_meta_table( $wpdb->commentmeta, 'comment_id' );
	clanbite_legacy_prefix_migrate_tables();

	// Direct SQL above bypassed the meta and post caches.
	wp_cache_flush();

	update_option( CLANBITE_LEGACY_PREFIX_MIGRATION_OPTION, '1', true );

	// Post type and template slugs changed: rebuild the rewrite rules once post types have registered.
	update_option( 'clanbite_pending_rewrite_flush', '1', false );
}

/**
 * Run the migration on every site (multisite) or the current site, and on the network's installed list.
 *
 * @return void
 */
function clanbite_maybe_migrate_legacy_prefix(): void {
	if ( wp_installing() ) {
		return;
	}

	if ( is_multisite() ) {
		$network = get_site_option( 'clanbite_installed_extensions', null );
		if ( is_array( $network ) ) {
			update_site_option( 'clanbite_installed_extensions', clanbite_legacy_prefix_map_value( $network ) );
		}

		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			clanbite_legacy_prefix_migrate_current_site();
			restore_current_blog();
		}

		return;
	}

	clanbite_legacy_prefix_migrate_current_site();
}
