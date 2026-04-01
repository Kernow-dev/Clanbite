<?php
/**
 * Template for player notifications subpage.
 *
 * @package Clanspress
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( clanspress_get_notifications_url() ) );
	exit;
}

$user_id      = get_current_user_id();
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page     = 20;

$result        = clanspress_get_notifications( $user_id, $current_page, $per_page );
$notifications = $result['notifications'];
$total         = $result['total'];
$unread_count  = $result['unread_count'];
$total_pages   = ceil( $total / $per_page );

get_header();
?>

<div class="clanspress-notifications-page">
	<div class="clanspress-notifications-page__header">
		<h1><?php esc_html_e( 'Notifications', 'clanspress' ); ?></h1>
		<?php if ( $unread_count > 0 ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="clanspress-notifications-page__mark-all">
				<?php wp_nonce_field( 'clanspress_mark_all_read', '_cpnonce' ); ?>
				<input type="hidden" name="action" value="clanspress_mark_all_notifications_read" />
				<button type="submit" class="clanspress-notifications-page__mark-all-btn">
					<?php esc_html_e( 'Mark all as read', 'clanspress' ); ?>
				</button>
			</form>
		<?php endif; ?>
	</div>

	<?php if ( empty( $notifications ) ) : ?>
		<div class="clanspress-notifications-page__empty">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="currentColor" opacity="0.3">
				<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z" />
			</svg>
			<p><?php esc_html_e( 'No notifications yet.', 'clanspress' ); ?></p>
		</div>
	<?php else : ?>
		<div class="clanspress-notifications-page__list">
			<?php foreach ( $notifications as $notification ) : ?>
				<?php echo clanspress_render_notification( $notification ); // phpcs:ignore ?>
			<?php endforeach; ?>
		</div>

		<?php if ( $total_pages > 1 ) : ?>
			<nav class="clanspress-notifications-page__pagination">
				<?php if ( $current_page > 1 ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>" class="clanspress-notifications-page__pagination-prev">
						&laquo; <?php esc_html_e( 'Previous', 'clanspress' ); ?>
					</a>
				<?php endif; ?>

				<span class="clanspress-notifications-page__pagination-info">
					<?php
					printf(
						/* translators: 1: current page, 2: total pages */
						esc_html__( 'Page %1$d of %2$d', 'clanspress' ),
						$current_page,
						$total_pages
					);
					?>
				</span>

				<?php if ( $current_page < $total_pages ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>" class="clanspress-notifications-page__pagination-next">
						<?php esc_html_e( 'Next', 'clanspress' ); ?> &raquo;
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</div>

<style>
.clanspress-notifications-page {
	max-width: 700px;
	margin: 2rem auto;
	padding: 0 1rem;
}

.clanspress-notifications-page__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 1.5rem;
	padding-bottom: 1rem;
	border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.clanspress-notifications-page__header h1 {
	margin: 0;
	font-size: 1.5rem;
}

.clanspress-notifications-page__mark-all-btn {
	padding: 0.5rem 1rem;
	font-size: 0.875rem;
	color: var(--wp--preset--color--primary, #0073aa);
	background: transparent;
	border: 1px solid currentColor;
	border-radius: 4px;
	cursor: pointer;
}

.clanspress-notifications-page__mark-all-btn:hover {
	background: var(--wp--preset--color--primary, #0073aa);
	color: #fff;
}

.clanspress-notifications-page__empty {
	text-align: center;
	padding: 4rem 2rem;
	color: rgba(0, 0, 0, 0.5);
}

.clanspress-notifications-page__empty p {
	margin-top: 1rem;
	font-size: 1rem;
}

.clanspress-notifications-page__list {
	display: flex;
	flex-direction: column;
	border: 1px solid rgba(0, 0, 0, 0.1);
	border-radius: 8px;
	overflow: hidden;
}

.clanspress-notifications-page__list .clanspress-notification {
	padding: 1rem 1.25rem;
}

.clanspress-notifications-page__pagination {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 1.5rem;
	margin-top: 1.5rem;
	padding-top: 1rem;
}

.clanspress-notifications-page__pagination a {
	color: var(--wp--preset--color--primary, #0073aa);
	text-decoration: none;
}

.clanspress-notifications-page__pagination a:hover {
	text-decoration: underline;
}

.clanspress-notifications-page__pagination-info {
	color: rgba(0, 0, 0, 0.5);
	font-size: 0.875rem;
}
</style>

<?php
get_footer();
