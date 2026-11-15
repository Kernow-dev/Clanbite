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
	protected string $settings_group = 'clanbite_general';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	protected string $page_slug = 'clanbite-general';

	/**
	 * Unified React admin: do not add a separate submenu.
	 *
	 * @var bool
	 */
	protected bool $register_standalone_submenu = false;

	/**
	 * {@inheritDoc}
	 */
	public function hooks(): void {
		parent::hooks();
		add_filter( 'show_admin_bar', array( $this, 'filter_show_admin_bar' ), 99 );
		add_filter( 'login_redirect', array( $this, 'filter_login_redirect' ), 20, 3 );
	}

	/**
	 * Send members to the page chosen under Clanbite → General → Login after they sign in.
	 *
	 * Only a plain sign-in is redirected. When the login request already carries a `redirect_to` (for example a visitor was bounced
	 * from a protected page, or opened `/wp-admin/`), that destination is kept.
	 *
	 * @param string                  $redirect_to           Destination WordPress would use.
	 * @param string                  $requested_redirect_to Destination asked for in the request (empty for a plain sign-in).
	 * @param \WP_User|\WP_Error|null $user                  Signed-in user, or an error.
	 * @return string
	 */
	public function filter_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		if ( ! $user instanceof \WP_User || '' !== (string) $requested_redirect_to ) {
			return $redirect_to;
		}
		$mode = (string) $this->get( 'login_redirect', 'dashboard' );
		$url  = '';
		if ( 'home' === $mode ) {
			$url = home_url( '/' );
		} elseif ( 'custom' === $mode ) {
			$url = $this->resolve_login_redirect_url( (string) $this->get( 'login_redirect_url', '' ) );
		}

		/**
		 * Filters where a member is sent after a plain sign-in.
		 *
		 * Return an empty string to keep the WordPress default (the dashboard). The destination must be on this site or an allowed
		 * redirect host: WordPress passes it through `wp_safe_redirect()`.
		 *
		 * @param string   $url  Destination chosen in the settings (empty for "Dashboard").
		 * @param \WP_User $user Signed-in user.
		 * @param string   $mode Setting value: `dashboard`, `home` or `custom`.
		 */
		$url = (string) apply_filters( 'clanbite_login_redirect_url', $url, $user, $mode );

		return '' !== $url ? $url : $redirect_to;
	}

	/**
	 * Turn the custom address setting into a full URL on this site.
	 *
	 * @param string $value Stored value: a full address or a path such as `/welcome/`.
	 * @return string Empty when unusable.
	 */
	private function resolve_login_redirect_url( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( str_starts_with( $value, '/' ) && ! str_starts_with( $value, '//' ) ) {
			return home_url( $value );
		}

		return (string) wp_validate_redirect( esc_url_raw( $value ), '' );
	}

	/**
	 * Sanitise the redirect mode.
	 *
	 * @param mixed $value Raw value.
	 * @return string One of `dashboard`, `home`, `custom`.
	 */
	public function sanitize_login_redirect_mode( $value ): string {
		return in_array( $value, array( 'home', 'custom' ), true ) ? (string) $value : 'dashboard';
	}

	/**
	 * Sanitise the custom login address (a full address or a path on this site).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_login_redirect_url( $value ): string {
		$value = trim( (string) $value );
		if ( str_starts_with( $value, '/' ) && ! str_starts_with( $value, '//' ) ) {
			return '/' . ltrim( sanitize_text_field( $value ), '/' );
		}

		return esc_url_raw( $value );
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

	/**
	 * {@inheritDoc}
	 */
	protected function get_page_title(): string {
		return __( 'Clanbite', 'clanbite' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_menu_title(): string {
		return __( 'General', 'clanbite' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_defaults(): array {
		// Filters run once in {@see Abstract_Settings::register_settings()} as `clanbite_general_settings_clanbite_persisted_option_default_map`.
		return array(
			'admin_notes'                      => '',
			'events_enabled'                   => true,
			'wordban_enabled'                  => false,
			'wordban_custom_list'              => '',
			'hide_wp_admin_bar_for_non_admins' => false,
			'login_redirect'                   => 'dashboard',
			'login_redirect_url'               => '',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_sections(): array {
		// Filters run once in {@see Abstract_Settings::register_settings()} as `clanbite_general_settings_clanbite_settings_ui_section_registry`.
		return array(
			'overview'   => array(
				'title'  => __( 'Overview', 'clanbite' ),
				'fields' => array(
					'admin_notes'                      => array(
						'label'       => __( 'Internal notes', 'clanbite' ),
						'type'        => 'textarea',
						'description' => __( 'Optional notes for other site administrators (not shown on the front end).', 'clanbite' ),
						'default'     => '',
						'sanitize'    => 'sanitize_textarea_field',
					),
					'events_enabled'                   => array(
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
			'login'      => array(
				'title'  => __( 'Login', 'clanbite' ),
				'fields' => array(
					'login_redirect'     => array(
						'label'       => __( 'After signing in, send members to', 'clanbite' ),
						'type'        => 'select',
						'description' => __( 'Where members land after they sign in from the login page. If they were sent to the login page from somewhere else, they return there instead.', 'clanbite' ),
						'default'     => 'dashboard',
						'options'     => array(
							'dashboard' => __( 'The dashboard (WordPress default)', 'clanbite' ),
							'home'      => __( 'The home page', 'clanbite' ),
							'custom'    => __( 'A custom page', 'clanbite' ),
						),
						'sanitize'    => array( $this, 'sanitize_login_redirect_mode' ),
					),
					'login_redirect_url' => array(
						'label'       => __( 'Custom page', 'clanbite' ),
						'type'        => 'text',
						'description' => __( 'A page on this site: a full address, or a path such as /members/.', 'clanbite' ),
						'default'     => '',
						'sanitize'    => array( $this, 'sanitize_login_redirect_url' ),
						'depends_on'  => array(
							'field' => 'login_redirect',
							'value' => 'custom',
						),
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

	/**
	 * {@inheritDoc}
	 */
	public function render_page(): void {
		$this->render_settings_page( __( 'Clanbite', 'clanbite' ) );
	}
}
