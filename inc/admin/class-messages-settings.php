<?php
/**
 * Messages: the site owner's wording for notifications, popups and automatic posts.
 *
 * The fields are generated from the message registry (see {@see clanbite_register_message()}), so every extension that
 * registers a message appears here without any settings code of its own.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Admin;

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Extensions\Abstract_Settings;

/**
 * Custom wording stored in {@see Messages_Settings::OPTION_KEY}, keyed by {@see clanbite_message_field_id()}.
 *
 * An empty value means "use the default", so defaults stay translatable and keep improving with plugin updates.
 */
class Messages_Settings extends Abstract_Settings {
	public const OPTION_KEY = 'clanbite_messages_settings';

	/**
	 * Longest custom message accepted.
	 */
	private const MAX_LENGTH = 500;

	/**
	 * Option name storing this settings bucket.
	 *
	 * @var string
	 */
	protected string $option_key = self::OPTION_KEY;

	/**
	 * Settings API group for `register_setting()`.
	 *
	 * @var string
	 */
	protected string $settings_group = 'clanbite_messages';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	protected string $page_slug = 'clanbite-messages';

	/**
	 * Unified React admin: do not add a separate submenu.
	 *
	 * @var bool
	 */
	protected bool $register_standalone_submenu = false;

	/**
	 * Browser title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_page_title(): string {
		return __( 'Messages', 'clanbite' );
	}

	/**
	 * Menu title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_menu_title(): string {
		return __( 'Messages', 'clanbite' );
	}

	/**
	 * Every message starts empty (= the registered default).
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults(): array {
		$defaults = array();
		foreach ( array_keys( clanbite_get_message_definitions() ) as $key ) {
			$defaults[ clanbite_message_field_id( (string) $key ) ] = '';
		}

		return $defaults;
	}

	/**
	 * One section per message group, one field per registered message.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_sections(): array {
		$groups   = clanbite_message_groups();
		$sections = array();

		foreach ( clanbite_get_message_definitions() as $key => $def ) {
			$group = $def['group'];
			if ( ! isset( $sections[ $group ] ) ) {
				$sections[ $group ] = array(
					'title'  => $groups[ $group ] ?? $group,
					'fields' => array(),
				);
			}

			$help = $def['description'];
			if ( array() !== $def['placeholders'] ) {
				$tokens = array();
				foreach ( $def['placeholders'] as $token => $what ) {
					$tokens[] = '{' . $token . '}' . ( '' !== $what ? ' (' . $what . ')' : '' );
				}
				$help = trim(
					$help . ' ' . sprintf(
						/* translators: %s: list of placeholders, for example "{team} (the team name), {user} (the member)". */
						__( 'You can use: %s.', 'clanbite' ),
						implode( ', ', $tokens )
					)
				);
			}
			$help = trim( $help . ' ' . __( 'Leave empty to use the default.', 'clanbite' ) );

			$sections[ $group ]['fields'][ clanbite_message_field_id( (string) $key ) ] = array(
				'label'       => $def['label'],
				'type'        => $def['multiline'] ? 'textarea' : 'text',
				'description' => $help,
				'placeholder' => $def['default'],
				'default'     => '',
				'sanitize'    => array( $this, 'sanitize_message' ),
			);
		}

		// Keep the group order from clanbite_message_groups(), and drop groups with no messages.
		$ordered = array();
		foreach ( array_keys( $groups ) as $slug ) {
			if ( isset( $sections[ $slug ] ) ) {
				$ordered[ $slug ] = $sections[ $slug ];
			}
		}

		return $ordered;
	}

	/**
	 * REST schema (sections and fields) for some message groups only.
	 *
	 * Each extension tab shows the groups that belong to it as its Messages sub-tab.
	 *
	 * @param array<int, string> $groups Group slugs.
	 * @return array<int, array<string, mixed>> Same shape as {@see Abstract_Settings::export_rest_schema()}.
	 */
	public function export_rest_schema_for_groups( array $groups ): array {
		return array_values(
			array_filter(
				$this->export_rest_schema(),
				static fn( array $section ): bool => in_array( (string) ( $section['id'] ?? '' ), $groups, true )
			)
		);
	}

	/**
	 * Render the classic PHP settings page shell (submenu mode only).
	 *
	 * @return void
	 */
	public function render_page(): void {
		$this->render_settings_page( __( 'Messages', 'clanbite' ) );
	}

	/**
	 * Sanitize a custom message: plain text, no markup, a sensible length.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_message( $value ): string {
		$value = sanitize_textarea_field( (string) $value );

		return mb_substr( $value, 0, self::MAX_LENGTH );
	}
}
