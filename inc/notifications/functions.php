<?php
/**
 * Notification helper functions for developers.
 *
 * @package Clanspress
 */

use Kernowdev\Clanspress\Notifications\Notification_Data_Access;
use Kernowdev\Clanspress\Notifications\Notification_Schema;

/**
 * Send a notification to a user.
 *
 * @param int                  $user_id User to notify.
 * @param string               $type    Notification type slug (e.g., 'team_invite', 'friend_request').
 * @param string               $title   Short notification title.
 * @param array<string, mixed> $args    {
 *     Optional arguments.
 *
 *     @type string $message     Longer message text.
 *     @type string $url         Link URL when notification is clicked.
 *     @type int    $actor_id    User who triggered the notification.
 *     @type string $object_type Related object type (e.g., 'team', 'post', 'comment').
 *     @type int    $object_id   Related object ID.
 *     @type array  $data        Additional data to store (will be JSON encoded).
 *     @type array  $actions     Action buttons. Each action is an array with:
 *                               - key: (string) Unique action identifier (e.g., 'accept', 'decline').
 *                               - label: (string) Button label.
 *                               - style: (string) 'primary', 'secondary', or 'danger'. Default 'secondary'.
 *                               - handler: (string) Handler identifier for the action.
 *                               - status: (string) Status to set after action ('accepted', 'declined', 'dismissed').
 *                               - success_message: (string) Message to show on success.
 *                               - confirm: (string|false) Confirmation message, or false for no confirm.
 *     @type bool   $dedupe      If true, won't create if similar notification exists. Default true.
 * }
 * @return int|\WP_Error Notification ID or error.
 *
 * @example
 * // Simple notification (no actions)
 * clanspress_notify( $user_id, 'mention', 'You were mentioned in a post', [
 *     'url' => $post_url,
 *     'actor_id' => $mentioner_id,
 * ] );
 *
 * @example
 * // Interactive notification with actions
 * clanspress_notify( $user_id, 'team_invite', sprintf( '%s invited you to join %s', $inviter_name, $team_name ), [
 *     'actor_id' => $inviter_id,
 *     'object_type' => 'team',
 *     'object_id' => $team_id,
 *     'url' => $team_url,
 *     'actions' => [
 *         [
 *             'key' => 'accept',
 *             'label' => __( 'Accept', 'clanspress' ),
 *             'style' => 'primary',
 *             'handler' => 'team_invite_accept',
 *             'status' => 'accepted',
 *             'success_message' => __( 'You have joined the team!', 'clanspress' ),
 *         ],
 *         [
 *             'key' => 'decline',
 *             'label' => __( 'Decline', 'clanspress' ),
 *             'style' => 'secondary',
 *             'handler' => 'team_invite_decline',
 *             'status' => 'declined',
 *             'success_message' => __( 'Invitation declined.', 'clanspress' ),
 *         ],
 *     ],
 * ] );
 */
function clanspress_notify( int $user_id, string $type, string $title, array $args = array() ) {
	$dedupe = $args['dedupe'] ?? true;

	if ( $dedupe ) {
		$object_type = $args['object_type'] ?? '';
		$object_id   = $args['object_id'] ?? 0;
		$actor_id    = $args['actor_id'] ?? 0;

		if ( Notification_Data_Access::exists( $user_id, $type, $object_type, $object_id, $actor_id ) ) {
			return new \WP_Error( 'duplicate', __( 'Notification already exists.', 'clanspress' ) );
		}
	}

	$data = array_merge(
		$args,
		array(
			'user_id' => $user_id,
			'type'    => $type,
			'title'   => $title,
		)
	);

	unset( $data['dedupe'] );

	return Notification_Data_Access::insert( $data );
}

/**
 * Get notifications for a user.
 *
 * @param int  $user_id     User ID.
 * @param int  $page        Page number.
 * @param int  $per_page    Per page.
 * @param bool $unread_only Only unread notifications.
 * @return array{notifications: object[], total: int, unread_count: int}
 */
function clanspress_get_notifications( int $user_id, int $page = 1, int $per_page = 20, bool $unread_only = false ): array {
	return Notification_Data_Access::get_for_user( $user_id, $page, $per_page, $unread_only );
}

/**
 * Get a single notification.
 *
 * @param int $notification_id Notification ID.
 * @return object|null
 */
function clanspress_get_notification( int $notification_id ): ?object {
	return Notification_Data_Access::get( $notification_id );
}

/**
 * Get unread notification count for a user.
 *
 * @param int $user_id User ID.
 * @return int
 */
function clanspress_get_unread_notification_count( int $user_id ): int {
	return Notification_Data_Access::get_unread_count( $user_id );
}

/**
 * Mark a notification as read.
 *
 * @param int $notification_id Notification ID.
 * @param int $user_id         User ID (for permission check).
 * @return bool
 */
function clanspress_mark_notification_read( int $notification_id, int $user_id ): bool {
	return Notification_Data_Access::mark_read( $notification_id, $user_id );
}

/**
 * Mark all notifications as read for a user.
 *
 * @param int $user_id User ID.
 * @return int Number marked read.
 */
function clanspress_mark_all_notifications_read( int $user_id ): int {
	return Notification_Data_Access::mark_all_read( $user_id );
}

/**
 * Delete a notification.
 *
 * @param int $notification_id Notification ID.
 * @param int $user_id         User ID (for permission check).
 * @return bool
 */
function clanspress_delete_notification( int $notification_id, int $user_id ): bool {
	return Notification_Data_Access::delete( $notification_id, $user_id );
}

/**
 * Delete all notifications for a user.
 *
 * @param int $user_id User ID.
 * @return int Number deleted.
 */
function clanspress_delete_all_notifications( int $user_id ): int {
	return Notification_Data_Access::delete_all_for_user( $user_id );
}

/**
 * Delete notifications related to an object.
 *
 * Useful when deleting a team, post, etc.
 *
 * @param string $object_type Object type.
 * @param int    $object_id   Object ID.
 * @return int Number deleted.
 */
function clanspress_delete_notifications_for_object( string $object_type, int $object_id ): int {
	return Notification_Data_Access::delete_by_object( $object_type, $object_id );
}

/**
 * Execute an action on a notification.
 *
 * @param int    $notification_id Notification ID.
 * @param string $action_key      Action key to execute.
 * @param int    $user_id         User ID (defaults to current user).
 * @return array{success: bool, message: string, redirect?: string}|\WP_Error
 */
function clanspress_execute_notification_action( int $notification_id, string $action_key, int $user_id = 0 ) {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}
	return Notification_Data_Access::execute_action( $notification_id, $user_id, $action_key );
}

/**
 * Dismiss a notification (mark as actioned without accepting/declining).
 *
 * @param int $notification_id Notification ID.
 * @param int $user_id         User ID (defaults to current user).
 * @return bool
 */
function clanspress_dismiss_notification( int $notification_id, int $user_id = 0 ): bool {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	$notification = Notification_Data_Access::get( $notification_id );
	if ( ! $notification || (int) $notification->user_id !== $user_id ) {
		return false;
	}

	return Notification_Data_Access::update_status( $notification_id, Notification_Data_Access::STATUS_DISMISSED );
}

/**
 * Get the URL for the notifications page.
 *
 * @param int|null $user_id User ID (defaults to current user).
 * @return string
 */
function clanspress_get_notifications_url( ?int $user_id = null ): string {
	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return '';
	}

	if ( function_exists( 'clanspress_get_player_profile_url' ) ) {
		$profile_url = clanspress_get_player_profile_url( $user_id );
		if ( $profile_url ) {
			return trailingslashit( $profile_url ) . 'notifications/';
		}
	}

	return home_url( '/players/notifications/' );
}

/**
 * Get registered notification types with their labels and icons.
 *
 * @return array<string, array{label: string, icon: string}>
 */
function clanspress_get_notification_types(): array {
	$types = array(
		'team_invite'     => array(
			'label' => __( 'Team Invite', 'clanspress' ),
			'icon'  => 'groups',
		),
		'team_join'       => array(
			'label' => __( 'Team Join', 'clanspress' ),
			'icon'  => 'groups',
		),
		'team_role'       => array(
			'label' => __( 'Team Role Change', 'clanspress' ),
			'icon'  => 'admin-users',
		),
		'team_removed'    => array(
			'label' => __( 'Removed from Team', 'clanspress' ),
			'icon'  => 'dismiss',
		),
		'mention'         => array(
			'label' => __( 'Mention', 'clanspress' ),
			'icon'  => 'format-status',
		),
		'system'          => array(
			'label' => __( 'System', 'clanspress' ),
			'icon'  => 'info',
		),
	);

	/**
	 * Filter registered notification types.
	 *
	 * Third-party developers can add their own types here.
	 *
	 * @param array<string, array{label: string, icon: string}> $types Notification types.
	 */
	return (array) apply_filters( 'clanspress_notification_types', $types );
}

/**
 * Render a notification for display.
 *
 * @param object $notification Notification object.
 * @param bool   $compact      Compact mode (for dropdown). Default false.
 * @return string HTML.
 */
function clanspress_render_notification( object $notification, bool $compact = false ): string {
	$types = clanspress_get_notification_types();
	$type_info = $types[ $notification->type ] ?? array(
		'label' => $notification->type,
		'icon'  => 'bell',
	);

	$time_ago = human_time_diff( strtotime( $notification->created_at ), time() );

	$classes = array( 'clanspress-notification' );
	$classes[] = $notification->is_read ? 'is-read' : 'is-unread';
	if ( $notification->is_actionable ) {
		$classes[] = 'is-actionable';
	}
	if ( $compact ) {
		$classes[] = 'is-compact';
	}
	if ( isset( $notification->status ) && 'pending' !== $notification->status ) {
		$classes[] = 'is-' . $notification->status;
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-notification-id="<?php echo esc_attr( $notification->id ); ?>">
		<?php if ( isset( $notification->actor ) ) : ?>
			<div class="clanspress-notification__avatar">
				<img src="<?php echo esc_url( $notification->actor->avatar_url ); ?>" alt="" />
			</div>
		<?php else : ?>
			<div class="clanspress-notification__icon">
				<span class="dashicons dashicons-<?php echo esc_attr( $type_info['icon'] ); ?>"></span>
			</div>
		<?php endif; ?>
		<div class="clanspress-notification__content">
			<div class="clanspress-notification__header">
				<?php if ( $notification->url && ! $notification->is_actionable ) : ?>
					<a href="<?php echo esc_url( $notification->url ); ?>" class="clanspress-notification__link">
						<span class="clanspress-notification__title"><?php echo esc_html( $notification->title ); ?></span>
					</a>
				<?php else : ?>
					<span class="clanspress-notification__title"><?php echo esc_html( $notification->title ); ?></span>
				<?php endif; ?>
				<span class="clanspress-notification__time"><?php echo esc_html( $time_ago ); ?></span>
			</div>
			<?php if ( $notification->message ) : ?>
				<p class="clanspress-notification__message"><?php echo esc_html( $notification->message ); ?></p>
			<?php endif; ?>
			<?php if ( $notification->is_actionable && is_array( $notification->actions ) ) : ?>
				<div class="clanspress-notification__actions">
					<?php foreach ( $notification->actions as $action ) : ?>
						<?php
						$style = $action['style'] ?? 'secondary';
						$confirm = $action['confirm'] ?? false;
						?>
						<button
							type="button"
							class="clanspress-notification__action clanspress-notification__action--<?php echo esc_attr( $style ); ?>"
							data-action="<?php echo esc_attr( $action['key'] ); ?>"
							data-notification-id="<?php echo esc_attr( $notification->id ); ?>"
							<?php if ( $confirm ) : ?>
								data-confirm="<?php echo esc_attr( $confirm ); ?>"
							<?php endif; ?>
						>
							<?php echo esc_html( $action['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php elseif ( isset( $notification->status ) && 'pending' !== $notification->status ) : ?>
				<div class="clanspress-notification__status">
					<?php
					$status_labels = array(
						'accepted'  => __( 'Accepted', 'clanspress' ),
						'declined'  => __( 'Declined', 'clanspress' ),
						'dismissed' => __( 'Dismissed', 'clanspress' ),
						'expired'   => __( 'Expired', 'clanspress' ),
					);
					echo esc_html( $status_labels[ $notification->status ] ?? $notification->status );
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( ! $notification->is_read && ! $notification->is_actionable ) : ?>
			<div class="clanspress-notification__unread-dot"></div>
		<?php endif; ?>
	</div>
	<?php
	$html = ob_get_clean();

	/**
	 * Filter the rendered notification HTML.
	 *
	 * @param string $html         Rendered HTML.
	 * @param object $notification Notification object.
	 * @param bool   $compact      Compact mode.
	 */
	return (string) apply_filters( 'clanspress_render_notification', $html, $notification, $compact );
}
