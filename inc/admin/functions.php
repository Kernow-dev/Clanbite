<?php
/**
 * Procedural helpers for the unified Clanbite admin screen (Clanbite → Settings).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add an entry to the sub-navigation of any tab on the Clanbite settings screen.
 *
 * Every tab (General, Extensions, an extension's own tab, an integration tab) can carry a small sub-menu such as
 * "Settings | Manage members". The first entry is always the tab's own content; each entry you register adds a
 * view next to it. Several plugins may register on the same tab; entries are ordered by `priority`.
 *
 * The view is rendered by your own JavaScript: core prints an empty container and fires the DOM events
 * `clanbite:admin-integration-mount` and `clanbite:admin-integration-unmount` on it with
 * `event.detail.slotId` set to your `slotId` (see README, "Admin sub-navigation"). The events can fire more
 * than once for the same element, so mount idempotently.
 *
 * The active view is kept in the URL as `admin.php?page=clanbite&tab={tab_id}&view={id}`.
 *
 * Call this before the admin REST bootstrap is built (any time on `plugins_loaded` or `init` is fine). It is a
 * thin wrapper around the `clanbite_admin_rest_sub_tabs` filter, which you can use directly when you need the
 * tab descriptor.
 *
 * @since 1.4.0
 *
 * @param string               $tab_id Tab id, exactly as in the tab descriptor: `general`, `extensions`, `core-groups`,
 *                                     `ext-{extension_slug}`, or an
 *                                     integration tab id added through `clanbite_admin_rest_tabs`.
 * @param array<string, mixed> $args   {
 *     Sub-tab definition.
 *
 *     @type string $id         Required. Unique id within the tab (`a-z0-9_-`); used as the `view` query value.
 *     @type string $label      Required. Translated label shown in the sub-navigation.
 *     @type string $slotId     Identifier for the container your script mounts into (`a-z0-9_-`). Required unless you pass `sectionGroups`.
 *     @type array  $sectionGroups Optional alternative to `slotId`: settings the admin screen renders itself, so no script is needed.
 *                                 Each group has `kind` (`primary`), `name`, `slug`, `optionKey` and `sections` (the shape of
 *                                 {@see \Kernowdev\Clanbite\Extensions\Abstract_Settings::export_rest_schema()}); the option must be
 *                                 registered through `clanbite_admin_rest_settings_registry`.
 *     @type string $capability Optional. Capability required to see the entry. Default `manage_options`.
 *     @type int    $priority   Optional. Lower numbers come first. Default 10.
 * }
 * @return void
 */
function clanbite_register_admin_sub_tab( string $tab_id, array $args ): void {
	// Used verbatim: it must equal the tab descriptor's `id` (integration tabs may use any characters).
	$tab_id = trim( $tab_id );
	if ( '' === $tab_id ) {
		return;
	}
	add_filter(
		'clanbite_admin_rest_sub_tabs',
		static function ( $sub_tabs, $for_tab_id ) use ( $tab_id, $args ) {
			if ( $for_tab_id === $tab_id ) {
				$sub_tabs   = is_array( $sub_tabs ) ? $sub_tabs : array();
				$sub_tabs[] = $args;
			}

			return $sub_tabs;
		},
		10,
		2
	);
}

/**
 * Validate sub-tab definitions for the admin bootstrap payload.
 *
 * Drops entries without an id, label or slot id, entries the current user lacks the capability for, and duplicate
 * ids (first wins), then orders by `priority` (stable). Used by the admin REST controller; exposed so tests and
 * integrations can reuse the same rules.
 *
 * @since 1.4.0
 *
 * @param mixed $items Raw list from the `clanbite_admin_rest_sub_tabs` filter.
 * @return array<int, array{id: string, label: string, slotId?: string, sectionGroups?: array<int, array<string, mixed>>}>
 */
function clanbite_normalize_admin_sub_tabs( $items ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$clean = array();
	$seen  = array();
	foreach ( array_values( $items ) as $order => $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$id      = sanitize_key( (string) ( $item['id'] ?? '' ) );
		$label   = trim( wp_strip_all_tags( (string) ( $item['label'] ?? '' ) ) );
		$slot_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $item['slotId'] ?? $item['slot_id'] ?? '' ) );
		$groups  = isset( $item['sectionGroups'] ) && is_array( $item['sectionGroups'] ) ? array_values( $item['sectionGroups'] ) : array();
		if ( '' === $id || '' === $label || ( '' === $slot_id && array() === $groups ) || isset( $seen[ $id ] ) ) {
			continue;
		}
		$capability = sanitize_text_field( (string) ( $item['capability'] ?? 'manage_options' ) );
		if ( '' !== $capability && ! current_user_can( $capability ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$clean[]     = array(
			'priority' => (int) ( $item['priority'] ?? 10 ),
			'order'    => $order,
			'id'       => $id,
			'label'    => $label,
			'slotId'   => $slot_id,
			'groups'   => $groups,
		);
	}

	usort(
		$clean,
		static fn( array $a, array $b ): int => array( $a['priority'], $a['order'] ) <=> array( $b['priority'], $b['order'] )
	);

	return array_map(
		static function ( array $row ): array {
			$out = array(
				'id'    => $row['id'],
				'label' => $row['label'],
			);
			if ( '' !== $row['slotId'] ) {
				$out['slotId'] = $row['slotId'];
			}
			if ( array() !== $row['groups'] ) {
				$out['sectionGroups'] = $row['groups'];
			}

			return $out;
		},
		$clean
	);
}
