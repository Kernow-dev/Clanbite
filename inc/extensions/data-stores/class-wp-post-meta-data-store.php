<?php
/**
 * Shared helpers for persistence backed by WordPress metadata APIs.
 *
 * Intended for custom post types (and similar objects) where meta is stored in
 * `wp_postmeta` or `wp_usermeta`. Subclasses set {@see $meta_type} and
 * {@see $internal_meta_keys}. Custom stores may ignore this class and use
 * `update_post_meta` directly when they prefer.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Extensions\Data_Stores;

defined( 'ABSPATH' ) || exit;


/**
 * Low-level read/write helpers for core meta tables.
 */
abstract class WP_Post_Meta_Data_Store {

	/**
	 * Metadata API type: `post` or `user` (core tables only for now).
	 *
	 * @var string
	 */
	protected string $meta_type = 'post';

	/**
	 * Meta keys treated as part of the object schema (not arbitrary extension meta).
	 *
	 * @var array<int, string>
	 */
	protected array $internal_meta_keys = array();

	/**
	 * Keys that must remain in the database even when the value is empty string or empty array.
	 *
	 * @var array<int, string>
	 */
	protected array $must_exist_meta_keys = array();

	/**
	 * Column name linking meta rows to the object, when not the default for {@see $meta_type}.
	 *
	 * @var string
	 */
	protected string $object_id_field_for_meta = '';

	/**
	 * Table name, object ID column, and meta primary key column.
	 *
	 * @return array{table: string, object_id_field: string, meta_id_field: string}
	 */
	protected function get_db_info(): array {
		global $wpdb;

		if ( 'user' === $this->meta_type ) {
			return array(
				'table'           => $wpdb->usermeta,
				'object_id_field' => 'user_id',
				'meta_id_field'   => 'umeta_id',
			);
		}

		$object_id_field = 'post_id';
		if ( '' !== $this->object_id_field_for_meta ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $wpdb->postmeta,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => 'meta_id',
		);
	}

	/**
	 * All internal meta keys (subclass list only; extend in subclass if you map props to meta).
	 *
	 * @return array<int, string>
	 */
	public function get_internal_meta_keys(): array {
		return array_values( array_unique( $this->internal_meta_keys ) );
	}

	/**
	 * Load raw meta rows for an object ID (includes `meta_id`).
	 *
	 * @param int $object_id Post ID or user ID.
	 * @return array<int, \stdClass> Rows with meta_id, meta_key, meta_value.
	 */
	public function read_meta_rows( int $object_id ): array {
		if ( $object_id < 1 ) {
			return array();
		}

		global $wpdb;
		$db = $this->get_db_info();

		$trusted = self::trusted_meta_sql_identifiers( $db );
		if ( null === $trusted ) {
			return array();
		}

		// Build a fixed query shape from allow-listed identifiers (no runtime SQL shell formatting).
		$query = '';
		if ( $trusted['table'] === $wpdb->postmeta && 'meta_id' === $trusted['meta_id_field'] && 'post_id' === $trusted['object_id_field'] ) {
			$query = "SELECT meta_id AS meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_id";
		} elseif ( $trusted['table'] === $wpdb->postmeta && 'meta_id' === $trusted['meta_id_field'] && 'user_id' === $trusted['object_id_field'] ) {
			$query = "SELECT meta_id AS meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE user_id = %d ORDER BY meta_id";
		} elseif ( $trusted['table'] === $wpdb->usermeta && 'umeta_id' === $trusted['meta_id_field'] && 'user_id' === $trusted['object_id_field'] ) {
			$query = "SELECT umeta_id AS meta_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d ORDER BY umeta_id";
		}
		if ( '' === $query ) {
			if ( function_exists( '_doing_it_wrong' ) ) {
				_doing_it_wrong(
					__METHOD__,
					/* translators: 1: meta table, 2: object ID field, 3: meta ID field */
					sprintf(
						__( 'Unexpected metadata SQL mapping: table=%1$s, object_id_field=%2$s, meta_id_field=%3$s.', 'clanbite' ),
						(string) $trusted['table'],
						(string) $trusted['object_id_field'],
						(string) $trusted['meta_id_field']
					),
					'1.0.0'
				);
			}
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Identifier shell allow-listed; values bound via prepare(); no bulk raw-meta API in core.
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $object_id ) );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		return $this->filter_raw_meta_rows( $rows );
	}

	/**
	 * Remove internal keys and `wp_*` keys from a raw meta result set.
	 *
	 * @param array<int, object> $raw_meta_data Rows from the database.
	 * @return array<int, \stdClass>
	 */
	public function filter_raw_meta_rows( array $raw_meta_data ): array {
		$internal = array_fill_keys( $this->get_internal_meta_keys(), true );

		return array_values(
			array_filter(
				$raw_meta_data,
				static function ( $row ) use ( $internal ): bool {
					if ( ! $row instanceof \stdClass || ! isset( $row->meta_key ) ) {
						return false;
					}
					$key = (string) $row->meta_key;
					if ( isset( $internal[ $key ] ) ) {
						return false;
					}
					return 0 !== stripos( $key, 'wp_' );
				}
			)
		);
	}

	/**
	 * Delete a meta row by its `meta_id`.
	 *
	 * @param \stdClass $meta Object with at least `id` (meta_id).
	 */
	public function delete_meta_row( \stdClass $meta ): void {
		if ( empty( $meta->id ) ) {
			return;
		}
		delete_metadata_by_mid( $this->meta_type, (int) $meta->id );
	}

	/**
	 * Insert a meta row.
	 *
	 * @param int       $object_id Post or user ID.
	 * @param \stdClass $meta      `key` and `value` properties.
	 * @return int|false New meta ID or false.
	 */
	public function add_meta_row( int $object_id, \stdClass $meta ) {
		$key = isset( $meta->key ) ? (string) $meta->key : '';
		if ( '' === $key || $object_id < 1 ) {
			return false;
		}
		$value = $meta->value ?? null;
		$store = is_string( $value ) ? wp_slash( $value ) : $value;

		return add_metadata( $this->meta_type, $object_id, wp_slash( $key ), $store, false );
	}

	/**
	 * Update a meta row by mid.
	 *
	 * @param \stdClass $meta `id`, `key`, `value`.
	 */
	public function update_meta_row( \stdClass $meta ): void {
		if ( empty( $meta->id ) ) {
			return;
		}
		$key   = isset( $meta->key ) ? (string) $meta->key : '';
		$value = $meta->value ?? null;
		update_metadata_by_mid( $this->meta_type, (int) $meta->id, $value, $key );
	}

	/**
	 * Update or delete post meta, skipping storage for empty string/array unless the key is required.
	 *
	 * Only for {@see $meta_type} `post`.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $meta_key  Meta key.
	 * @param mixed  $meta_value Value to persist.
	 * @return bool True if something was written or deleted.
	 */
	protected function update_or_delete_post_meta( int $post_id, string $meta_key, $meta_value ): bool {
		if ( 'post' !== $this->meta_type || $post_id < 1 ) {
			return false;
		}

		if ( in_array( $meta_value, array( array(), '' ), true )
			&& ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			return (bool) delete_post_meta( $post_id, $meta_key );
		}

		return (bool) update_post_meta( $post_id, $meta_key, $meta_value );
	}

	/**
	 * Which meta keys need writing: changed props or missing in DB.
	 *
	 * @param array<string, string> $meta_key_to_prop Map meta_key => property name on `$changes`.
	 * @param int                   $object_id        Object ID.
	 * @param array<string, mixed>  $changes          Dirty properties (prop => value).
	 * @return array<string, string> Subset of $meta_key_to_prop to update.
	 */
	protected function get_meta_keys_to_update( array $meta_key_to_prop, int $object_id, array $changes ): array {
		if ( $object_id < 1 ) {
			return $meta_key_to_prop;
		}

		$props_to_update = array();
		foreach ( $meta_key_to_prop as $meta_key => $prop ) {
			if ( array_key_exists( $prop, $changes ) || ! metadata_exists( $this->meta_type, $object_id, $meta_key ) ) {
				$props_to_update[ $meta_key ] = $prop;
			}
		}

		return $props_to_update;
	}

	/**
	 * Allow only core meta tables and known ID columns (identifiers cannot use prepare() placeholders).
	 *
	 * @param array{table: string, object_id_field: string, meta_id_field: string} $db Output of get_db_info().
	 * @return array{table: string, object_id_field: string, meta_id_field: string}|null
	 */
	private static function trusted_meta_sql_identifiers( array $db ): ?array {
		global $wpdb;

		$allowed_tables = array( $wpdb->postmeta, $wpdb->usermeta );
		if ( ! in_array( $db['table'], $allowed_tables, true ) ) {
			return null;
		}

		$allowed_meta_id = array( 'meta_id', 'umeta_id' );
		$allowed_object  = array( 'post_id', 'user_id' );
		if ( ! in_array( $db['meta_id_field'], $allowed_meta_id, true )
			|| ! in_array( $db['object_id_field'], $allowed_object, true ) ) {
			return null;
		}

		return array(
			'table'           => $db['table'],
			'object_id_field' => $db['object_id_field'],
			'meta_id_field'   => $db['meta_id_field'],
		);
	}
}
