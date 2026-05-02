<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server render: Event detail block.
 *
 * @package clanbite
 */

use Kernowdev\Clanbite\Events\Event_Permissions;
use Kernowdev\Clanbite\Events\Event_Post_Type;

$event_id = (int) ( $attributes['eventId'] ?? 0 );

if ( $event_id < 1 ) {
	$event_id = (int) get_query_var( 'clanbite_team_event_id' );
}
if ( $event_id < 1 ) {
	$event_id = (int) get_query_var( 'cp_group_event_id' );
}

$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;

if ( $event_id < 1 ) {
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanbite-event-detail clanbite-event-detail--placeholder' ), $block );
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><p>' . esc_html__( 'No event selected.', 'clanbite' ) . '</p></div>' );
	return;
}

$post = get_post( $event_id );
if ( ! ( $post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanbite-event-detail clanbite-event-detail--placeholder' ), $block );
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><p>' . esc_html__( 'Event not found.', 'clanbite' ) . '</p></div>' );
	return;
}

if ( ! Event_Permissions::viewer_can_see( $post, $viewer_id ) ) {
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanbite-event-detail clanbite-event-detail--forbidden' ), $block );
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><p>' . esc_html__( 'This event is not available.', 'clanbite' ) . '</p></div>' );
	return;
}

$scope_ev = sanitize_key( (string) get_post_meta( $event_id, 'cp_event_scope', true ) );
$tid      = 0;
$gid      = 0;
if ( Event_Post_Type::SCOPE_TEAM === $scope_ev ) {
	$tid = (int) get_post_meta( $event_id, 'cp_event_team_id', true );
	if ( function_exists( 'clanbite_events_are_enabled_for_team' ) && ! clanbite_events_are_enabled_for_team( $tid ) ) {
		return '';
	}
} elseif ( Event_Post_Type::SCOPE_GROUP === $scope_ev ) {
	$gid = (int) get_post_meta( $event_id, 'cp_event_group_id', true );
	if ( function_exists( 'clanbite_events_are_enabled_for_group' ) && ! clanbite_events_are_enabled_for_group( $gid ) ) {
		return '';
	}
}

$mode     = (string) get_post_meta( $event_id, 'cp_event_mode', true );
$starts   = (string) get_post_meta( $event_id, 'cp_event_starts_at', true );
$ends     = (string) get_post_meta( $event_id, 'cp_event_ends_at', true );
$vurl     = (string) get_post_meta( $event_id, 'cp_event_virtual_url', true );
$line1    = (string) get_post_meta( $event_id, 'cp_event_address_line1', true );
$line2    = (string) get_post_meta( $event_id, 'cp_event_address_line2', true );
$locality = (string) get_post_meta( $event_id, 'cp_event_locality', true );
$region   = (string) get_post_meta( $event_id, 'cp_event_region', true );
$postcode = (string) get_post_meta( $event_id, 'cp_event_postcode', true );
$country  = (string) get_post_meta( $event_id, 'cp_event_country', true );

$start_label = '';
if ( $starts ) {
	$ts = strtotime( $starts . ' UTC' );
	$start_label = $ts ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) : '';
}
$end_label = '';
if ( $ends ) {
	$ts = strtotime( $ends . ' UTC' );
	$end_label = $ts ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) : '';
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanbite-event-detail' ), $block );

ob_start();
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered; fragment escaped via clanbite_esc_block_fragment_html() after capture. ?>>
	<h1 class="clanbite-event-detail__title"><?php echo esc_html( get_the_title( $post ) ); ?></h1>
	<?php if ( $start_label ) : ?>
	<p class="clanbite-event-detail__meta">
		<?php
		echo esc_html( $start_label );
		if ( $end_label ) {
			echo ' — ';
			echo esc_html( $end_label );
		}
		?>
	</p>
	<?php endif; ?>
	<div class="clanbite-event-detail__content entry-content">
		<?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered; escaped via clanbite_esc_block_fragment_html() after capture. ?>
	</div>
	<?php if ( Event_Post_Type::MODE_VIRTUAL === $mode ) : ?>
		<h2 class="clanbite-event-detail__section-title"><?php esc_html_e( 'Virtual', 'clanbite' ); ?></h2>
		<?php if ( $vurl ) : ?>
			<p><a href="<?php echo esc_url( $vurl ); ?>"><?php esc_html_e( 'Join link', 'clanbite' ); ?></a></p>
		<?php else : ?>
			<p><?php esc_html_e( 'Virtual meeting (link TBD)', 'clanbite' ); ?></p>
		<?php endif; ?>
	<?php else : ?>
		<h2 class="clanbite-event-detail__section-title"><?php esc_html_e( 'Location', 'clanbite' ); ?></h2>
		<div class="clanbite-event-detail__address">
			<?php
			$lines = array_filter( array( $line1, $line2, trim( $locality . ( $region ? ', ' . $region : '' ) . ( $postcode ? ' ' . $postcode : '' ) ), $country ) );
			echo esc_html( implode( "\n", $lines ) );
			?>
		</div>
	<?php endif; ?>
	<?php
	$can_manage      = Event_Permissions::user_can_manage_event( $event_id, $viewer_id );
	$delete_redirect = home_url( '/' );
	if ( Event_Post_Type::SCOPE_TEAM === $scope_ev && $tid > 0 && function_exists( 'clanbite_teams_get_team_action_url' ) ) {
		$team_events_url = clanbite_teams_get_team_action_url( $tid, 'events' );
		if ( is_string( $team_events_url ) && '' !== $team_events_url ) {
			$delete_redirect = $team_events_url;
		}
	} elseif ( Event_Post_Type::SCOPE_GROUP === $scope_ev && $gid > 0 ) {
		$group_permalink = get_permalink( $gid );
		if ( is_string( $group_permalink ) && '' !== $group_permalink ) {
			$delete_redirect = trailingslashit( $group_permalink ) . 'events/';
		}
	}
	?>
	<?php if ( $can_manage ) : ?>
		<details class="clanbite-event-detail__edit-panel">
			<summary class="clanbite-event-detail__edit-summary"><?php esc_html_e( 'Edit this event', 'clanbite' ); ?></summary>
			<div class="clanbite-event-detail__edit-panel-inner">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered; escaped via clanbite_esc_block_fragment_html() after capture.
				echo render_block(
					array(
						'blockName' => 'clanbite/event-create-form',
						'attrs'     => array(
							'scopeType' => Event_Post_Type::SCOPE_TEAM === $scope_ev ? 'team' : 'group',
							'teamId'    => $tid,
							'groupId'   => $gid,
							'eventId'   => $event_id,
						),
					)
				);
				?>
			</div>
		</details>
		<div class="clanbite-event-detail__manage">
			<h2 class="clanbite-event-detail__section-title"><?php esc_html_e( 'Manage', 'clanbite' ); ?></h2>
			<form class="clanbite-event-detail__delete-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'clanbite_delete_event_' . $event_id ); ?>
				<input type="hidden" name="action" value="clanbite_delete_event" />
				<input type="hidden" name="clanbite_event_id" value="<?php echo esc_attr( (string) $event_id ); ?>" />
				<input type="hidden" name="clanbite_event_delete_redirect" value="<?php echo esc_attr( $delete_redirect ); ?>" />
				<button type="submit" class="clanbite-event-detail__delete-button button" onclick="return window.confirm( <?php echo wp_json_encode( __( 'Move this event to the trash?', 'clanbite' ) ); ?> );">
					<?php esc_html_e( 'Delete event', 'clanbite' ); ?>
				</button>
			</form>
		</div>
	<?php endif; ?>
</div>
<?php
$html = (string) ob_get_clean();
echo clanbite_esc_block_fragment_html( $html );

$rsvp_markup = sprintf(
	'<!-- wp:clanbite/event-rsvp {"eventType":"clanbite_event","eventId":%d,"showAttendees":true} /-->',
	$event_id
);
echo clanbite_esc_block_fragment_html( do_blocks( $rsvp_markup ) );
