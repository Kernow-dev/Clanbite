<?php

defined( 'ABSPATH' ) || exit;

/**
 * Notification helper functions for developers.
 *
 * @package Clanbite
 */

use Kernowdev\Clanbite\Extensions\Notification\Notification_Data_Access;

/**
 * Whether the Clanbite Notifications extension (`cp_notifications`) is installed and enabled.
 *
 * Other extensions and plugins should use this (or handle a `WP_Error` from `clanbite_notify()`)
 * before relying on in-site notifications, REST routes, or the bell block — those only load when
 * the extension is active.
 *
 * @return bool
 */
function clanbite_notifications_extension_active(): bool {
	if ( ! class_exists( \Kernowdev\Clanbite\Extensions\Loader::class ) ) {
		return false;
	}

	$active = \Kernowdev\Clanbite\Extensions\Loader::instance()->is_extension_installed( 'cp_notifications' );

	/**
	 * Filter whether notifications are considered active for theme and third-party checks.
	 *
	 * @param bool $active True when `cp_notifications` is in the installed-extensions option.
	 */
	return (bool) apply_filters( 'clanbite_notifications_extension_active', $active );
}

/**
 * Stored Notifications extension settings merged with defaults (`clanbite_notifications_settings`).
 *
 * @return array<string, mixed> {
 *     @type bool $subpage_player    Player profile notifications subpage enabled.
 *     @type bool $poll_long_polling Notification bell uses blocking long-polling when true.
 * }
 */
function clanbite_notifications_settings_values(): array {
	$defaults = array(
		'subpage_player'   => true,
		'poll_long_polling' => false,
	);
	$stored   = get_option( 'clanbite_notifications_settings', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return wp_parse_args( $stored, $defaults );
}

/**
 * Whether the Notifications extension should register the player profile Notifications subpage and template.
 *
 * @return bool
 */
function clanbite_notifications_subpage_player_enabled(): bool {
	if ( ! clanbite_notifications_extension_active() ) {
		return false;
	}

	$values = clanbite_notifications_settings_values();

	return ! empty( $values['subpage_player'] );
}

/**
 * Whether the notification bell should use blocking long-polling on `/notifications/poll`.
 *
 * When false (default), each poll returns after a single database read. When true, the request may
 * block up to the configured timeout. The filter {@see 'clanbite_notification_poll_blocking_wait'}
 * still runs after this value and can override it.
 *
 * @return bool
 */
function clanbite_notifications_poll_long_polling_enabled(): bool {
	if ( ! clanbite_notifications_extension_active() ) {
		return false;
	}

	$values = clanbite_notifications_settings_values();

	return ! empty( $values['poll_long_polling'] );
}

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
 * clanbite_notify( $user_id, 'mention', 'You were mentioned in a post', [
 *     'url' => $post_url,
 *     'actor_id' => $mentioner_id,
 * ] );
 *
 * @example
 * // Interactive notification with actions
 * clanbite_notify( $user_id, 'team_invite', sprintf( '%s invited you to join %s', $inviter_name, $team_name ), [
 *     'actor_id' => $inviter_id,
 *     'object_type' => 'team',
 *     'object_id' => $team_id,
 *     'url' => $team_url,
 *     'actions' => [
 *         [
 *             'key' => 'accept',
 *             'label' => __( 'Accept', 'clanbite' ),
 *             'style' => 'primary',
 *             'handler' => 'team_invite_accept',
 *             'status' => 'accepted',
 *             'success_message' => __( 'You have joined the team!', 'clanbite' ),
 *         ],
 *         [
 *             'key' => 'decline',
 *             'label' => __( 'Decline', 'clanbite' ),
 *             'style' => 'secondary',
 *             'handler' => 'team_invite_decline',
 *             'status' => 'declined',
 *             'success_message' => __( 'Invitation declined.', 'clanbite' ),
 *         ],
 *     ],
 * ] );
 */
function clanbite_notify( int $user_id, string $type, string $title, array $args = array() ) {
	if ( ! clanbite_notifications_extension_active() ) {
		return new \WP_Error(
			'notifications_inactive',
			__( 'The Clanbite Notifications extension is not enabled.', 'clanbite' )
		);
	}

	$dedupe = $args['dedupe'] ?? true;

	if ( $dedupe ) {
		$object_type = $args['object_type'] ?? '';
		$object_id   = $args['object_id'] ?? 0;
		$actor_id    = $args['actor_id'] ?? 0;

		if ( Notification_Data_Access::exists( $user_id, $type, $object_type, $object_id, $actor_id ) ) {
			return new \WP_Error( 'duplicate', __( 'Notification already exists.', 'clanbite' ) );
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
function clanbite_get_notifications( int $user_id, int $page = 1, int $per_page = 20, bool $unread_only = false ): array {
	if ( ! clanbite_notifications_extension_active() ) {
		return array(
			'notifications' => array(),
			'total'         => 0,
			'unread_count'  => 0,
		);
	}

	return Notification_Data_Access::get_for_user( $user_id, $page, $per_page, $unread_only );
}

/**
 * Get a single notification.
 *
 * @param int $notification_id Notification ID.
 * @return object|null
 */
function clanbite_get_notification( int $notification_id ): ?object {
	if ( ! clanbite_notifications_extension_active() ) {
		return null;
	}

	return Notification_Data_Access::get( $notification_id );
}

/**
 * Get unread notification count for a user.
 *
 * @param int $user_id User ID.
 * @return int
 */
function clanbite_get_unread_notification_count( int $user_id ): int {
	if ( ! clanbite_notifications_extension_active() ) {
		return 0;
	}

	return Notification_Data_Access::get_unread_count( $user_id );
}

/**
 * Mark a notification as read.
 *
 * @param int $notification_id Notification ID.
 * @param int $user_id         User ID (for permission check).
 * @return bool
 */
function clanbite_mark_notification_read( int $notification_id, int $user_id ): bool {
	if ( ! clanbite_notifications_extension_active() ) {
		return false;
	}

	return Notification_Data_Access::mark_read( $notification_id, $user_id );
}

/**
 * Mark all notifications as read for a user.
 *
 * @param int $user_id User ID.
 * @return int Number marked read.
 */
function clanbite_mark_all_notifications_read( int $user_id ): int {
	if ( ! clanbite_notifications_extension_active() ) {
		return 0;
	}

	return Notification_Data_Access::mark_all_read( $user_id );
}

/**
 * Delete a notification.
 *
 * @param int $notification_id Notification ID.
 * @param int $user_id         User ID (for permission check).
 * @return bool
 */
function clanbite_delete_notification( int $notification_id, int $user_id ): bool {
	if ( ! clanbite_notifications_extension_active() ) {
		return false;
	}

	return Notification_Data_Access::delete( $notification_id, $user_id );
}

/**
 * Delete all notifications for a user.
 *
 * @param int $user_id User ID.
 * @return int Number deleted.
 */
function clanbite_delete_all_notifications( int $user_id ): int {
	if ( ! clanbite_notifications_extension_active() ) {
		return 0;
	}

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
function clanbite_delete_notifications_for_object( string $object_type, int $object_id ): int {
	if ( ! clanbite_notifications_extension_active() ) {
		return 0;
	}

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
function clanbite_execute_notification_action( int $notification_id, string $action_key, int $user_id = 0 ) {
	if ( ! clanbite_notifications_extension_active() ) {
		return new \WP_Error(
			'notifications_inactive',
			__( 'The Clanbite Notifications extension is not enabled.', 'clanbite' )
		);
	}

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
function clanbite_dismiss_notification( int $notification_id, int $user_id = 0 ): bool {
	if ( ! clanbite_notifications_extension_active() ) {
		return false;
	}

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
function clanbite_get_notifications_url( ?int $user_id = null ): string {
	if ( ! clanbite_notifications_extension_active() ) {
		return '';
	}

	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return '';
	}

	$profile_url = '';
	if ( function_exists( 'clanbite_get_player_profile_url' ) ) {
		$profile_url = (string) clanbite_get_player_profile_url( $user_id );
	}
	if ( '' === $profile_url ) {
		// Same canonical base as {@see clanbite_get_user_nav_menu_items()}: author URLs are rewritten to /players/{nicename}/.
		$profile_url = (string) get_author_posts_url( $user_id );
	}
	if ( '' === $profile_url ) {
		$user = get_userdata( $user_id );
		if ( $user instanceof \WP_User && is_string( $user->user_nicename ) && $user->user_nicename !== '' ) {
			$profile_url = home_url( '/players/' . $user->user_nicename );
		}
	}

	if ( '' !== $profile_url ) {
		return trailingslashit( $profile_url ) . 'notifications/';
	}

	return '';
}

/**
 * Get registered notification types with their labels and icons.
 *
 * @return array<string, array{label: string, icon: string}>
 */
function clanbite_get_notification_types(): array {
	$types = array(
		'team_invite'     => array(
			'label' => __( 'Team Invite', 'clanbite' ),
			'icon'  => 'groups',
		),
		'team_join'       => array(
			'label' => __( 'Team Join', 'clanbite' ),
			'icon'  => 'groups',
		),
		'team_role'       => array(
			'label' => __( 'Team Role Change', 'clanbite' ),
			'icon'  => 'admin-users',
		),
		'team_removed'    => array(
			'label' => __( 'Removed from Team', 'clanbite' ),
			'icon'  => 'dismiss',
		),
		'team_challenge'  => array(
			'label' => __( 'Team Challenge', 'clanbite' ),
			'icon'  => 'flag',
		),
		'team_match_event' => array(
			'label' => __( 'Team Match Event', 'clanbite' ),
			'icon'  => 'calendar-alt',
		),
		'team_event'       => array(
			'label' => __( 'Team Event', 'clanbite' ),
			'icon'  => 'calendar-alt',
		),
		'group_event'      => array(
			'label' => __( 'Group Event', 'clanbite' ),
			'icon'  => 'calendar-alt',
		),
		'mention'         => array(
			'label' => __( 'Mention', 'clanbite' ),
			'icon'  => 'format-status',
		),
		'system'          => array(
			'label' => __( 'System', 'clanbite' ),
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
	return (array) apply_filters( 'clanbite_notification_types', $types );
}

/**
 * Allowed HTML tags for a single notification row after the render filter runs.
 *
 * @return array<string, array<string, bool>>
 */
function clanbite_notification_allowed_html(): array {
	return array(
		'div'    => array(
			'class'                  => true,
			'data-notification-id'   => true,
		),
		'a'      => array(
			'href'                   => true,
			'class'                  => true,
			'data-notification-id'   => true,
		),
		'button' => array(
			'type'                   => true,
			'class'                  => true,
			'data-action'            => true,
			'data-notification-id'   => true,
			'data-confirm'           => true,
		),
		'span'   => array(
			'class' => true,
		),
		'p'      => array(
			'class' => true,
			'role'  => true,
		),
		'img'    => array(
			'src'      => true,
			'alt'      => true,
			'class'    => true,
			'loading'  => true,
			'decoding' => true,
			'width'    => true,
			'height'   => true,
		),
	);
}

/**
 * Register front assets for the player notifications subpage (enqueued when markup renders).
 *
 * @return void
 */
function clanbite_register_player_notifications_page_assets(): void {
	if ( wp_style_is( 'clanbite-player-notifications-page', 'registered' ) ) {
		return;
	}

	if ( ! function_exists( 'clanbite' ) ) {
		return;
	}

	$main = clanbite();
	$ver  = \Kernowdev\Clanbite\Main::VERSION;

	$css_rel = 'assets/css/clanbite-player-notifications-page.css';
	$js_rel  = 'assets/js/clanbite-player-notifications-page.js';
	$css_abs = $main->path . $css_rel;
	$js_abs  = $main->path . $js_rel;

	$css_v = is_readable( $css_abs ) ? (string) filemtime( $css_abs ) : $ver;
	$js_v  = is_readable( $js_abs ) ? (string) filemtime( $js_abs ) : $ver;

	wp_register_style(
		'clanbite-player-notifications-page',
		$main->url . $css_rel,
		array(),
		$css_v
	);

	wp_register_script(
		'clanbite-player-notifications-page',
		$main->url . $js_rel,
		array(),
		$js_v,
		true
	);

	wp_localize_script(
		'clanbite-player-notifications-page',
		'clanbitePlayerNotificationsPageI18n',
		array(
			'markReadError' => __( 'Could not mark this notification as read. Opening the link anyway — see the browser console for details.', 'clanbite' ),
		)
	);
}

/**
 * Enqueue styles/scripts for {@see clanbite_render_player_notifications_page_markup()}.
 *
 * @return void
 */
function clanbite_enqueue_player_notifications_page_assets(): void {
	clanbite_register_player_notifications_page_assets();
	wp_enqueue_style( 'clanbite-player-notifications-page' );
	wp_enqueue_script( 'clanbite-player-notifications-page' );
}

add_action( 'init', 'clanbite_register_player_notifications_page_assets', 20 );

/**
 * Render a notification for display.
 *
 * The {@see 'clanbite_render_notification'} filter runs, then markup is passed through
 * {@see wp_kses()} with {@see clanbite_notification_allowed_html()} before return. Templates
 * may echo the same {@see wp_kses()} pair again for late-escaping standards (idempotent).
 *
 * @param object $notification Notification object.
 * @param bool   $compact      Compact mode (for dropdown). Default false.
 * @return string Safe HTML fragment.
 */
function clanbite_render_notification( object $notification, bool $compact = false ): string {
	$types = clanbite_get_notification_types();
	$type_info = $types[ $notification->type ] ?? array(
		'label' => $notification->type,
		'icon'  => 'bell',
	);

	$time_ago = human_time_diff( strtotime( $notification->created_at ), time() );

	$classes = array( 'clanbite-notification' );
	$classes[] = $notification->is_read ? 'is-read' : 'is-unread';
	if ( $notification->is_actionable ) {
		$classes[] = 'is-actionable';
	}
	if ( $compact ) {
		$classes[] = 'is-compact';
	}
	if ( isset( $notification->status ) && 'pending' !== $notification->status ) {
		$classes[] = 'is-' . sanitize_key( (string) $notification->status );
	}

	$icon_slug = isset( $type_info['icon'] ) ? sanitize_key( (string) $type_info['icon'] ) : 'bell';
	if ( '' === $icon_slug ) {
		$icon_slug = 'bell';
	}

	$avatar_alt = __( 'User avatar', 'clanbite' );
	if ( isset( $notification->actor->name ) && is_string( $notification->actor->name ) && '' !== $notification->actor->name ) {
		$avatar_alt = sprintf(
			/* translators: %s: User display name. */
			__( 'Avatar for %s', 'clanbite' ),
			$notification->actor->name
		);
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-notification-id="<?php echo esc_attr( $notification->id ); ?>">
		<?php if ( isset( $notification->actor ) ) : ?>
			<div class="clanbite-notification__avatar">
				<img src="<?php echo esc_url( $notification->actor->avatar_url ); ?>" alt="<?php echo esc_attr( $avatar_alt ); ?>" loading="lazy" decoding="async" />
			</div>
		<?php else : ?>
			<div class="clanbite-notification__icon">
				<span class="dashicons dashicons-<?php echo esc_attr( $icon_slug ); ?>"></span>
			</div>
		<?php endif; ?>
		<div class="clanbite-notification__content">
			<div class="clanbite-notification__header">
				<?php if ( $notification->url && ! $notification->is_actionable ) : ?>
					<a href="<?php echo esc_url( $notification->url ); ?>" class="clanbite-notification__link" data-notification-id="<?php echo esc_attr( (string) $notification->id ); ?>">
						<span class="clanbite-notification__title"><?php echo esc_html( $notification->title ); ?></span>
					</a>
				<?php else : ?>
					<span class="clanbite-notification__title"><?php echo esc_html( $notification->title ); ?></span>
				<?php endif; ?>
				<span class="clanbite-notification__time"><?php echo esc_html( $time_ago ); ?></span>
			</div>
			<?php if ( $notification->message ) : ?>
				<p class="clanbite-notification__message"><?php echo esc_html( $notification->message ); ?></p>
			<?php endif; ?>
			<?php if ( $notification->is_actionable && is_array( $notification->actions ) ) : ?>
				<div class="clanbite-notification__actions">
					<?php foreach ( $notification->actions as $action ) : ?>
						<?php
						$style = $action['style'] ?? 'secondary';
						$confirm = $action['confirm'] ?? false;
						?>
						<button
							type="button"
							class="clanbite-notification__action clanbite-notification__action--<?php echo esc_attr( $style ); ?>"
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
				<div class="clanbite-notification__status">
					<?php
					$status_labels = array(
						'accepted'  => __( 'Accepted', 'clanbite' ),
						'declined'  => __( 'Declined', 'clanbite' ),
						'dismissed' => __( 'Dismissed', 'clanbite' ),
						'expired'   => __( 'Expired', 'clanbite' ),
					);
					echo esc_html( $status_labels[ $notification->status ] ?? $notification->status );
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( ! $notification->is_read && ! $notification->is_actionable ) : ?>
			<div class="clanbite-notification__unread-dot"></div>
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
	$html = (string) apply_filters( 'clanbite_render_notification', $html, $notification, $compact );

	return (string) wp_kses( $html, clanbite_notification_allowed_html() );
}

/**
 * Render the full notifications list UI for the player notifications subpage (shortcode / block template).
 *
 * @return string HTML (empty when the extension is inactive).
 */
function clanbite_render_player_notifications_page_markup(): string {
	if ( ! clanbite_notifications_extension_active() ) {
		return '';
	}

	if ( ! is_user_logged_in() ) {
		return '';
	}

	clanbite_enqueue_player_notifications_page_assets();

	$user_id      = get_current_user_id();
	$current_page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per_page     = 20;

	$result        = clanbite_get_notifications( $user_id, $current_page, $per_page );
	$notifications = $result['notifications'];
	$total         = $result['total'];
	$unread_count  = $result['unread_count'];
	$total_pages   = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

	$rest_root = trailingslashit( (string) rest_url( 'clanbite/v1' ) );
	$rest_nonce = wp_create_nonce( 'wp_rest' );

	ob_start();
	?>
	<div
		class="clanbite-notifications-page"
		data-clanbite-notifications-rest="<?php echo esc_url( $rest_root ); ?>"
		data-clanbite-notifications-nonce="<?php echo esc_attr( $rest_nonce ); ?>"
	>
		<div class="clanbite-notifications-page__header">
			<h1><?php esc_html_e( 'Notifications', 'clanbite' ); ?></h1>
			<?php if ( $unread_count > 0 ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="clanbite-notifications-page__mark-all">
					<?php wp_nonce_field( 'clanbite_mark_all_read', '_cpnonce' ); ?>
					<input type="hidden" name="action" value="clanbite_mark_all_notifications_read" />
					<button type="submit" class="clanbite-notifications-page__mark-all-btn">
						<?php esc_html_e( 'Mark all as read', 'clanbite' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>

		<?php if ( empty( $notifications ) ) : ?>
			<div class="clanbite-notifications-page__empty">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="currentColor" opacity="0.3">
					<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z" />
				</svg>
				<p><?php esc_html_e( 'No notifications yet.', 'clanbite' ); ?></p>
			</div>
		<?php else : ?>
			<div class="clanbite-notifications-page__list">
				<?php foreach ( $notifications as $notification ) : ?>
					<?php echo wp_kses( clanbite_render_notification( $notification ), clanbite_notification_allowed_html() ); ?>
				<?php endforeach; ?>
			</div>

			<?php if ( $total_pages > 1 ) : ?>
				<nav class="clanbite-notifications-page__pagination">
					<?php if ( $current_page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>" class="clanbite-notifications-page__pagination-prev">
							&laquo; <?php esc_html_e( 'Previous', 'clanbite' ); ?>
						</a>
					<?php endif; ?>

					<span class="clanbite-notifications-page__pagination-info">
						<?php
						printf(
							/* translators: 1: current page, 2: total pages */
							esc_html__( 'Page %1$d of %2$d', 'clanbite' ),
							absint( $current_page ),
							absint( $total_pages )
						);
						?>
					</span>

					<?php if ( $current_page < $total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>" class="clanbite-notifications-page__pagination-next">
							<?php esc_html_e( 'Next', 'clanbite' ); ?> &raquo;
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	$html = (string) ob_get_clean();

	/**
	 * Filter the full notifications subpage markup.
	 *
	 * @param string $html HTML output.
	 */
	return (string) apply_filters( 'clanbite_player_notifications_page_markup', $html );
}

/**
 * Shortcode: notifications list for the player notifications template (`[clanbite_player_notifications]`).
 *
 * @param array<string, string> $atts Shortcode attributes (unused).
 * @return string
 */
function clanbite_player_notifications_shortcode( $atts = array() ): string {
	return clanbite_render_player_notifications_page_markup();
}
