<?php
/**
 * Main notifications system class.
 *
 * @package Clanspress
 */

namespace Kernowdev\Clanspress\Notifications;

/**
 * Initializes the notifications system.
 */
final class Notifications {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->register();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'maybe_create_tables' ), 5 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'init', array( $this, 'register_blocks' ), 20 );
		add_action( 'init', array( $this, 'register_notifications_subpage' ), 20 );

		// Clean up notifications when user is deleted.
		add_action( 'delete_user', array( $this, 'on_user_deleted' ) );

		// Clean up notifications when team is deleted.
		add_action( 'clanspress_team_deleted', array( $this, 'on_team_deleted' ) );
	}

	/**
	 * Create tables if needed.
	 *
	 * @return void
	 */
	public function maybe_create_tables(): void {
		Notification_Schema::maybe_upgrade();
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		( new Notification_Rest_Controller() )->register_routes();
	}

	/**
	 * Register the notification bell block.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$block_path = \clanspress()->path . 'build/notifications/notification-bell';
		if ( is_dir( $block_path ) ) {
			register_block_type( $block_path );
		}
	}

	/**
	 * Register the notifications player subpage.
	 *
	 * @return void
	 */
	public function register_notifications_subpage(): void {
		if ( ! function_exists( 'clanspress_register_player_subpage' ) ) {
			return;
		}

		clanspress_register_player_subpage(
			'notifications',
			array(
				'label'    => __( 'Notifications', 'clanspress' ),
				'position' => 80,
			)
		);
	}

	/**
	 * Clean up when user is deleted.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function on_user_deleted( int $user_id ): void {
		Notification_Data_Access::delete_all_for_user( $user_id );
	}

	/**
	 * Clean up when team is deleted.
	 *
	 * @param int $team_id Team ID.
	 * @return void
	 */
	public function on_team_deleted( int $team_id ): void {
		Notification_Data_Access::delete_by_object( 'team', $team_id );
	}
}
