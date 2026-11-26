<?php
/**
 * Option-backed settings for the Events extension (unified React admin tab).
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Extensions;

defined( 'ABSPATH' ) || exit;

/**
 * Events settings (`clanbite_events_settings`): the site-wide switches for scheduled events and the profile tabs the extension adds.
 *
 * One home for everything Events: the switch that used to sit under General and the three "Events tab" toggles that used to be
 * scattered over the Players, Teams and Groups tabs. Read them with the `clanbite_events_*()` helpers, never the option directly:
 * the helpers also understand the old locations until a site has saved this tab.
 */
class Events_Admin extends Abstract_Settings {
	/**
	 * Option that stores these settings.
	 */
	public const OPTION_KEY = 'clanbite_events_settings';

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
	protected string $settings_group = 'clanbite_events';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	protected string $page_slug = 'clanbite-events';

	/**
	 * Browser title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_page_title(): string {
		return __( 'Events', 'clanbite' );
	}

	/**
	 * Menu title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_menu_title(): string {
		return __( 'Events', 'clanbite' );
	}

	/**
	 * Default option values before registration and filters run.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults(): array {
		return array(
			'events_enabled'        => true,
			'player_events_enabled' => true,
			'subpage_player'        => true,
			'subpage_team'          => true,
			'subpage_group'         => true,
		);
	}

	/**
	 * Section and field definitions for the settings API / REST schema export.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_sections(): array {
		return array(
			'events'       => array(
				'title'  => __( 'Scheduled events', 'clanbite' ),
				'fields' => array(
					'events_enabled'        => array(
						'label'       => __( 'Enable scheduled events', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'When off, player, team and group events, their REST endpoints and front-end event routes are disabled site-wide. Individual teams and groups can still turn events off when this is on.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
					),
					'player_events_enabled' => array(
						'label'       => __( 'Let players create their own events', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'Players can add events to their own profile, choose who can see them (anyone, friends or only themselves) and invite friends. Team and group events are not affected.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
						'depends_on'  => array(
							'field' => 'events_enabled',
							'value' => true,
						),
					),
				),
			),
			'profile_tabs' => array(
				'title'  => __( 'Profile tabs', 'clanbite' ),
				'fields' => array(
					'subpage_player' => array(
						'label'       => __( 'Player profile: Events tab', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'Show the Events tab and /{players slug}/{user}/events/. When off, that URL redirects to the profile root.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
						'depends_on'  => array(
							'field' => 'events_enabled',
							'value' => true,
						),
					),
					'subpage_team'   => array(
						'label'       => __( 'Team profile: Events tab', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'Show the Events tab and /teams/{team}/events/ when teams use directory URLs. When off, those URLs are not available.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
						'depends_on'  => array(
							'field' => 'events_enabled',
							'value' => true,
						),
					),
					'subpage_group'  => array(
						'hidden'      => ! \clanbite_groups_available(),
						'label'       => __( 'Group profile: Events tab', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'Show the Events tab on group profiles. When off, the group events page is not registered.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
						'depends_on'  => array(
							'field' => 'events_enabled',
							'value' => true,
						),
					),
				),
			),
		);
	}

	/**
	 * Render the classic PHP settings page shell (submenu mode only).
	 *
	 * @return void
	 */
	public function render_page(): void {
		$this->render_settings_page( __( 'Events', 'clanbite' ) );
	}
}
