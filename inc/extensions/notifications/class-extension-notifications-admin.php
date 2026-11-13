<?php
/**
 * Option-backed settings for the Notifications extension (unified React admin tab).
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Extensions\Notifications;

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Extensions\Abstract_Settings;

/**
 * Notifications extension settings for the unified React admin (`clanbite_notifications_settings`).
 */
class Admin extends Abstract_Settings {
	/**
	 * Option that stores these settings (also read by {@see clanbite_get_toast_settings()} for the shared popups).
	 *
	 * @since 1.5.0
	 */
	public const OPTION_KEY = 'clanbite_notifications_settings';

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
	protected string $settings_group = 'clanbite_notifications';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	protected string $page_slug = 'clanbite-notifications';

	/**
	 * Browser title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_page_title(): string {
		return __( 'Notifications', 'clanbite' );
	}

	/**
	 * Menu title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_menu_title(): string {
		return __( 'Notifications', 'clanbite' );
	}

	/**
	 * Default option values before registration and filters run.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults(): array {
		return array(
			'subpage_player'        => true,
			'poll_long_polling'     => false,
			'toasts_enabled'        => true,
			'toast_position'        => 'top-right',
			'toast_duration'        => '5',
			'toast_duration_custom' => 8,
			'toast_pause_on_hover'  => true,
			'toast_show_timer'      => true,
		);
	}

	/**
	 * Section and field definitions for the settings API / REST schema export.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_sections(): array {
		return array(
			'profile_subpages'  => array(
				'title'  => __( 'Profile subpages', 'clanbite' ),
				'fields' => array(
					'subpage_player' => array(
						'label'       => __( 'Player profile: Notifications tab', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'When off, the notifications template and tab are omitted and that URL redirects to the profile root.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
					),
				),
			),
			'notification_bell' => array(
				'title'  => __( 'Notification bell', 'clanbite' ),
				'fields' => array(
					'poll_long_polling' => array(
						'label'       => __( 'Use long-polling', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'When enabled, poll requests may stay open until new notifications arrive or a timeout is reached (fewer HTTP round-trips). When off, each poll checks once and returns immediately (better for busy sites and limited PHP workers). Developers can still override behavior with the clanbite_notification_poll_blocking_wait filter.', 'clanbite' ),
						'default'     => false,
						'sanitize'    => 'rest_sanitize_boolean',
					),
				),
			),
			'popups'            => array(
				'title'  => __( 'Popups', 'clanbite' ),
				'fields' => array(
					'toasts_enabled'        => array(
						'label'       => __( 'Show popups', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'Short notices that appear on the screen, such as a rank-up, points earned, a confirmation or an error. Every Clanbite extension uses the same popups, so these settings apply to all of them. When off, no popups are shown.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
					),
					'toast_position'        => array(
						'label'       => __( 'Popup position', 'clanbite' ),
						'type'        => 'select',
						'description' => __( 'Where on the screen popups appear.', 'clanbite' ),
						'default'     => 'top-right',
						'options'     => clanbite_toast_positions(),
						'sanitize'    => array( $this, 'sanitize_toast_position' ),
						'depends_on'  => array(
							'field' => 'toasts_enabled',
							'value' => true,
						),
					),
					'toast_duration'        => array(
						'label'       => __( 'Show popups for', 'clanbite' ),
						'type'        => 'select',
						'description' => __( 'How long a popup stays before it disappears. Choose Custom to type your own length. Visitors can always close it sooner.', 'clanbite' ),
						'default'     => '5',
						'options'     => clanbite_toast_durations(),
						'sanitize'    => array( $this, 'sanitize_toast_duration' ),
						'depends_on'  => array(
							'field' => 'toasts_enabled',
							'value' => true,
						),
					),
					'toast_duration_custom' => array(
						'label'       => __( 'Custom length (seconds)', 'clanbite' ),
						'type'        => 'number',
						'description' => sprintf(
							/* translators: 1: shortest allowed length in seconds, 2: longest allowed length in seconds. */
							__( 'Whole seconds, from %1$d to %2$d.', 'clanbite' ),
							clanbite_toast_custom_duration_range()['min'],
							clanbite_toast_custom_duration_range()['max']
						),
						'default'     => 8,
						'sanitize'    => array( $this, 'sanitize_toast_duration_custom' ),
						// Both must hold: popups on, and the length set to Custom.
						'depends_on'  => array(
							array(
								'field' => 'toasts_enabled',
								'value' => true,
							),
							array(
								'field' => 'toast_duration',
								'value' => 'custom',
							),
						),
					),
					'toast_pause_on_hover'  => array(
						'label'       => __( 'Pause when hovered', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'Hovering over a popup pauses its countdown so it can be read; moving away resumes it. Popups always pause while they have keyboard focus.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
						'depends_on'  => array(
							'field' => 'toasts_enabled',
							'value' => true,
						),
					),
					'toast_show_timer'      => array(
						'label'       => __( 'Show a countdown bar', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'A thin bar along the bottom of each popup fills up as it gets close to disappearing. Popups that stay until closed have no bar.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
						'depends_on'  => array(
							'field' => 'toasts_enabled',
							'value' => true,
						),
					),
				),
			),
		);
	}

	/**
	 * Sanitize the popup position: one of the known positions, otherwise the default.
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_toast_position( $value ): string {
		$value = sanitize_key( (string) $value );

		return array_key_exists( $value, clanbite_toast_positions() ) ? $value : 'top-right';
	}

	/**
	 * Sanitize the popup length: one of the offered values (seconds, `custom` or `0` = until closed), otherwise the default.
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_toast_duration( $value ): string {
		$value = is_numeric( $value ) ? (string) (int) $value : sanitize_key( (string) $value );

		return array_key_exists( $value, clanbite_toast_durations() ) ? $value : '5';
	}

	/**
	 * Sanitize the custom popup length: whole seconds within {@see clanbite_toast_custom_duration_range()}.
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_toast_duration_custom( $value ): int {
		$range = clanbite_toast_custom_duration_range();

		return max( $range['min'], min( $range['max'], is_numeric( $value ) ? (int) $value : 8 ) );
	}

	/**
	 * Render the classic PHP settings page shell (submenu mode only).
	 *
	 * @return void
	 */
	public function render_page(): void {
		$this->render_settings_page( __( 'Notifications', 'clanbite' ) );
	}
}
