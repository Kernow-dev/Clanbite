<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Notification Bell block render.
 *
 * @package Clanbite
 */

if ( ! is_user_logged_in() ) {
	return;
}

$user_id        = get_current_user_id();
$show_dropdown  = $attributes['showDropdown'] ?? true;
$dropdown_count = $attributes['dropdownCount'] ?? 10;

$unread_count      = clanbite_get_unread_notification_count( $user_id );
$notifications_url = clanbite_get_notifications_url( $user_id );

// List body is loaded on first open via REST (see view.js loadNotifications) — avoid a duplicate DB query on every page view.
$notifications = array();

$context = array(
	'isOpen'             => false,
	'isLoading'          => false,
	'unreadCount'        => $unread_count,
	'notifications'      => array(),
	'lastId'             => 0,
	'lastTimestamp'      => gmdate( 'Y-m-d H:i:s' ),
	'pollInterval'       => 4000,
	'notificationsUrl'   => $notifications_url,
	'dropdownCount'      => $dropdown_count,
	'syncProviderActive' => false,
	'restUrl'            => trailingslashit( esc_url_raw( rest_url( 'clanbite/v1' ) ) ),
	'nonce'              => wp_create_nonce( 'wp_rest' ),
	'i18n'               => array(
		'noNotifications' => __( 'No notifications yet.', 'clanbite' ),
		'statusLabels'    => array(
			'accepted'  => __( 'Accepted', 'clanbite' ),
			'declined'  => __( 'Declined', 'clanbite' ),
			'dismissed' => __( 'Dismissed', 'clanbite' ),
			'expired'   => __( 'Expired', 'clanbite' ),
		),
	),
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-notification-bell',
	),
	$block
);
$dropdown_id = wp_unique_id( 'clanbite-notification-bell-dropdown-' );
?>
<?php ob_start(); ?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered; escaped via clanbite_esc_block_fragment_html() before output. ?>
	data-wp-interactive="clanbite/notification-bell"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
	data-wp-init="callbacks.init"
	data-wp-on-document--click="actions.handleOutsideClick"
	data-wp-on-document--keydown="actions.handleKeydown"
	data-wp-class--is-open="context.isOpen"
	data-wp-class--has-unread="context.unreadCount"
>
	<button
		type="button"
		class="clanbite-notification-bell__trigger"
		aria-label="<?php esc_attr_e( 'Notifications', 'clanbite' ); ?>"
		aria-expanded="false"
		aria-haspopup="menu"
		aria-controls="<?php echo esc_attr( $dropdown_id ); ?>"
		data-wp-on--click="actions.toggleDropdown"
		data-wp-bind--aria-expanded="context.isOpen"
	>
		<svg
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 24 24"
			width="24"
			height="24"
			fill="currentColor"
			class="clanbite-notification-bell__icon"
		>
			<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z" />
		</svg>
		<span
			class="clanbite-notification-bell__badge"
			data-wp-text="context.unreadCount"
			data-wp-bind--hidden="!context.unreadCount"
			aria-live="polite"
			<?php echo $unread_count < 1 ? ' hidden' : ''; ?>
		><?php echo esc_html( $unread_count ); ?></span>
	</button>

	<?php if ( $show_dropdown ) : ?>
		<div
			id="<?php echo esc_attr( $dropdown_id ); ?>"
			class="clanbite-notification-bell__dropdown"
			role="menu"
			aria-label="<?php esc_attr_e( 'Notifications', 'clanbite' ); ?>"
			hidden
			data-wp-bind--hidden="!context.isOpen"
		>
			<div class="clanbite-notification-bell__dropdown-header">
				<span class="clanbite-notification-bell__dropdown-title">
					<?php esc_html_e( 'Notifications', 'clanbite' ); ?>
				</span>
				<button
					type="button"
					class="clanbite-notification-bell__mark-all-read"
					data-wp-on--click="actions.markAllRead"
					data-wp-bind--disabled="!context.unreadCount"
				>
					<?php esc_html_e( 'Mark all read', 'clanbite' ); ?>
				</button>
			</div>

			<div
				class="clanbite-notification-bell__dropdown-content"
				data-wp-class--is-loading="context.isLoading"
			>
				<div
					class="clanbite-notification-bell__list"
					data-wp-watch="callbacks.renderNotifications"
				>
					<?php if ( empty( $notifications ) ) : ?>
						<p class="clanbite-notification-bell__empty">
							<?php esc_html_e( 'No notifications yet.', 'clanbite' ); ?>
						</p>
					<?php else : ?>
						<?php foreach ( $notifications as $notification ) : ?>
							<?php echo clanbite_render_notification( $notification, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered; escaped via clanbite_esc_block_fragment_html() before output. ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

			<div class="clanbite-notification-bell__loading" hidden data-wp-bind--hidden="!context.isLoading">
				<span class="clanbite-notification-bell__spinner"></span>
			</div>
			</div>

			<div class="clanbite-notification-bell__dropdown-footer">
				<a
					href="<?php echo esc_url( $notifications_url ); ?>"
					class="clanbite-notification-bell__view-all"
				>
					<?php esc_html_e( 'View all notifications', 'clanbite' ); ?>
				</a>
			</div>
		</div>
	<?php endif; ?>
</div>
<?php echo clanbite_esc_block_fragment_html( (string) ob_get_clean() ); ?>
