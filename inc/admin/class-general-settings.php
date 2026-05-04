<?php
/**
 * Core plugin settings (General tab in Clanbite admin).
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Admin;

defined( 'ABSPATH' ) || exit;


use Kernowdev\Clanbite\Extensions\Abstract_Settings;

/**
 * General Clanbite options stored in {@see General_Settings::OPTION_KEY}.
 */
class General_Settings extends Abstract_Settings {
	public const OPTION_KEY = 'clanbite_general_settings';

	protected string $option_key     = self::OPTION_KEY;
	protected string $settings_group = 'clanbite_general';
	protected string $page_slug      = 'clanbite-general';

	/**
	 * Unified React admin: do not add a separate submenu.
	 *
	 * @var bool
	 */
	protected bool $register_standalone_submenu = false;

	public function hooks(): void {
		parent::hooks();
		add_filter( 'show_admin_bar', array( $this, 'filter_show_admin_bar' ), 99 );
	}

	/**
	 * Hide the front-end WordPress toolbar for users without `manage_options` when the option is enabled.
	 *
	 * Super admins on multisite always keep the bar. `is_admin()` requests are unchanged.
	 *
	 * @param bool $show Whether WordPress would show the admin bar.
	 * @return bool
	 */
	public function filter_show_admin_bar( bool $show ): bool {
		if ( is_admin() ) {
			return $show;
		}

		if ( ! $this->get( 'hide_wp_admin_bar_for_non_admins', false ) ) {
			return $show;
		}

		if ( ! is_user_logged_in() ) {
			return $show;
		}

		$uid = get_current_user_id();
		if ( $uid > 0 && is_multisite() && is_super_admin( $uid ) ) {
			return $show;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return $show;
		}

		return false;
	}

	protected function get_page_title(): string {
		return __( 'Clanbite', 'clanbite' );
	}

	protected function get_menu_title(): string {
		return __( 'General', 'clanbite' );
	}

	protected function get_defaults(): array {
		// Filters run once in {@see Abstract_Settings::register_settings()} as `clanbite_general_settings_clanbite_persisted_option_default_map`.
		return array(
			'admin_notes'                      => '',
			'events_enabled'                   => true,
			'wordban_enabled'                  => false,
			'wordban_custom_list'              => '',
			'hide_wp_admin_bar_for_non_admins' => false,
		);
	}

	protected function get_sections(): array {
		// Filters run once in {@see Abstract_Settings::register_settings()} as `clanbite_general_settings_clanbite_settings_ui_section_registry`.
		return array(
			'overview' => array(
				'title'  => __( 'Overview', 'clanbite' ),
				'fields' => array(
					'admin_notes'    => array(
						'label'       => __( 'Internal notes', 'clanbite' ),
						'type'        => 'textarea',
						'description' => __( 'Optional notes for other site administrators (not shown on the front end).', 'clanbite' ),
						'default'     => '',
						'sanitize'    => 'sanitize_textarea_field',
					),
					'events_enabled' => array(
						'label'       => __( 'Enable scheduled events', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'When off, team and group events, REST endpoints, and front-end event routes are disabled site-wide. Individual teams and groups can still turn events off when this is on.', 'clanbite' ),
						'default'     => true,
					),
					'hide_wp_admin_bar_for_non_admins' => array(
						'label'       => __( 'Hide WordPress toolbar on the front end for non-administrators', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'When on, only users who can manage options (and super admins on multisite) see the admin bar while viewing the site. The dashboard and block editor are unchanged.', 'clanbite' ),
						'default'     => false,
					),
				),
			),
			'moderation' => array(
				'title'  => __( 'Moderation', 'clanbite' ),
				'fields' => array(
					'wordban_enabled'     => array(
						'label'       => __( 'Enable word filter', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'When on, blocked words cannot be used in team names, group names, usernames, and similar short fields. The same list is masked in longer user-written content (for example social posts, comments, and forums): the first character stays visible and the rest is replaced with asterisks. A built-in list applies; you can add more below.', 'clanbite' ),
						'default'     => false,
					),
					'wordban_custom_list' => array(
						'label'       => __( 'Additional banned words', 'clanbite' ),
						'type'        => 'textarea',
						'description' => __( 'Comma- or line-separated. Multi-word phrases use each word only when it appears as a whole word. Common number or symbol substitutions (such as 1 for i or 3 for e) are treated like letters for matching.', 'clanbite' ),
						'default'     => '',
						'sanitize'    => 'sanitize_textarea_field',
						'depends_on'  => array(
							'field' => 'wordban_enabled',
							'value' => true,
						),
					),
				),
			),
		);
	}

	public function render_page(): void {
		$this->render_settings_page( __( 'Clanbite', 'clanbite' ) );
	}
}
