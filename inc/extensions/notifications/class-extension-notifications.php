<?php
/**
 * Extension: Notifications (first-party).
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Extensions;
defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Extensions\Abstract_Settings;
use Kernowdev\Clanbite\Extensions\Notification\Notification_Schema;
use Kernowdev\Clanbite\Extensions\Notification\Notifications_Runtime;
use Kernowdev\Clanbite\Extensions\Notifications\Admin as Notifications_Settings_Admin;

require_once __DIR__ . '/class-extension-notifications-admin.php';

/**
 * Official extension that boots the in-site notifications subsystem (DB, REST, bell block, profile tab).
 */
class Notifications extends Skeleton {

	/**
	 * Option-backed settings for the unified Clanbite React admin.
	 *
	 * @var Notifications_Settings_Admin
	 */
	protected Notifications_Settings_Admin $admin;

	/**
	 * Registers the extension definition.
	 */
	public function __construct() {
		parent::__construct(
			'Notifications',
			'cp_notifications',
			__(
				'In-site notifications, REST API, and the notification bell block.',
				'clanbite'
			),
			'',
			'1.0.0',
			array( 'cp_players' )
		);
	}

	/**
	 * Use the official-extensions filter (not third-party registration).
	 *
	 * @param string $name        Extension name.
	 * @param string $slug        Extension slug.
	 * @param string $description Description.
	 * @param string $parent_slug Parent slug.
	 * @param string $version              Version.
	 * @param array  $requires             Required extension slugs.
	 * @param string $requires_clanbite  Minimum Clanbite core version (`x.y.z`).
	 */
	public function setup_extension(
		string $name,
		string $slug,
		string $description,
		string $parent_slug,
		string $version,
		array $requires,
		string $requires_clanbite = ''
	): void {
		parent::setup_extension(
			$name,
			$slug,
			$description,
			$parent_slug,
			$version,
			$requires,
			$requires_clanbite
		);

		remove_filter( 'clanbite_registered_extensions', array( $this, 'register_extension' ) );
		add_filter(
			'clanbite_official_registered_extensions',
			array( $this, 'register_extension' )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_installer(): void {
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_uninstaller(): void {
		Notification_Schema::drop_tables();
		parent::run_uninstaller();
	}

	/**
	 * {@inheritDoc}
	 */
	public function run_updater(): void {
	}

	/**
	 * Boot notifications (hooks, blocks, schema upgrades).
	 *
	 * @return void
	 */
	public function run(): void {
		$this->admin = new Notifications_Settings_Admin();
		Notifications_Runtime::instance();
	}

	/**
	 * Settings handler for the unified React admin (own tab: root extension).
	 *
	 * @return Abstract_Settings|null
	 */
	public function get_settings_admin(): ?Abstract_Settings {
		return isset( $this->admin ) ? $this->admin : null;
	}
}
