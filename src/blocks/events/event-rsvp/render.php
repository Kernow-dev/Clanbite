<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server render: Event RSVP block.
 *
 * @package clanbite
 */

use Kernowdev\Clanbite\Events\Events;

$event_type = sanitize_key( (string) ( $attributes['eventType'] ?? 'match' ) );
$event_id   = (int) ( $attributes['eventId'] ?? 0 );
$show_attendees = ! empty( $attributes['showAttendees'] );

if ( $event_id < 1 && isset( $block->context['postId'] ) ) {
	$pt = (string) ( $block->context['postType'] ?? '' );
	if ( 'match' === $event_type && 'cp_match' === $pt ) {
		$event_id = (int) $block->context['postId'];
	} elseif ( 'group' === $event_type && 'cp_group' === $pt ) {
		$event_id = (int) $block->context['postId'];
	} elseif ( 'clanbite_event' === $event_type && 'cp_event' === $pt ) {
		$event_id = (int) $block->context['postId'];
	}
}

if ( $event_id < 1 && 'clanbite_event' === $event_type ) {
	$tid = (int) get_query_var( 'clanbite_team_event_id' );
	$gid = (int) get_query_var( 'cp_group_event_id' );
	$event_id = $tid > 0 ? $tid : $gid;
}

$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;

if ( $event_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-event-rsvp clanbite-event-rsvp--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><p>' . esc_html__( 'Select an event or place this block on a match or group template.', 'clanbite' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$can_view = Events::viewer_can_see_event( $event_type, $event_id, $viewer_id );

if ( ! $can_view ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-event-rsvp clanbite-event-rsvp--forbidden',
		),
		$block
	);
	echo '<div ' . $wrapper . '><p>' . esc_html__( 'This event is not available.', 'clanbite' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$config = array(
	'eventType'      => $event_type,
	'eventId'        => $event_id,
	'showAttendees'  => (bool) $show_attendees,
	'restUrl'        => esc_url_raw( rest_url( 'clanbite/v1' ) ),
	'nonce'          => wp_create_nonce( 'wp_rest' ),
	'loggedIn'       => is_user_logged_in(),
	'loginUrl'       => wp_login_url( get_permalink() ),
	'canView'        => true,
	'i18n'           => array(
		'currentPrefix'  => __( 'Your response: ', 'clanbite' ),
		'logInToRsvp'    => __( 'Log in to respond', 'clanbite' ),
		'noAttendees'    => __( 'No responses yet.', 'clanbite' ),
		'attendeesHidden' => __( 'Attendee list is hidden.', 'clanbite' ),
		'statusLabels'   => array(
			'accepted'  => __( 'Accepted', 'clanbite' ),
			'declined'  => __( 'Declined', 'clanbite' ),
			'tentative' => __( 'Tentative', 'clanbite' ),
		),
		'buttonLabels' => array(
			'accepted'  => __( 'Accepted', 'clanbite' ),
			'declined'  => __( 'Declined', 'clanbite' ),
			'tentative' => __( 'Tentative', 'clanbite' ),
		),
	),
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-event-rsvp',
	),
	$block
);
?>
<div
	<?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	data-wp-interactive="clanbite-event-rsvp"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
	data-wp-init="callbacks.init"
>
	<div class="clanbite-event-rsvp__actions">
		<?php if ( is_user_logged_in() ) : ?>
			<div class="clanbite-event-rsvp__buttons" role="group" aria-label="<?php esc_attr_e( 'RSVP', 'clanbite' ); ?>">
				<button type="button" class="clanbite-event-rsvp__btn" data-cp-rsvp-status="accepted" data-wp-on--click="actions.postRsvp"><?php echo esc_html( $config['i18n']['buttonLabels']['accepted'] ); ?></button>
				<button type="button" class="clanbite-event-rsvp__btn" data-cp-rsvp-status="tentative" data-wp-on--click="actions.postRsvp"><?php echo esc_html( $config['i18n']['buttonLabels']['tentative'] ); ?></button>
				<button type="button" class="clanbite-event-rsvp__btn" data-cp-rsvp-status="declined" data-wp-on--click="actions.postRsvp"><?php echo esc_html( $config['i18n']['buttonLabels']['declined'] ); ?></button>
			</div>
		<?php endif; ?>
		<p class="clanbite-event-rsvp__status" aria-live="polite">
			<?php if ( ! is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( $config['loginUrl'] ); ?>"><?php echo esc_html( $config['i18n']['logInToRsvp'] ); ?></a>
			<?php endif; ?>
		</p>
	</div>
	<?php if ( $show_attendees ) : ?>
		<div class="clanbite-event-rsvp__attendees">
			<div class="clanbite-event-rsvp__attendees-head">
				<button type="button" class="clanbite-event-rsvp__toggle" data-wp-on--click="actions.toggleAttendees" data-wp-bind--aria-expanded="state.attendeesOpen()">
					<span class="clanbite-event-rsvp__toggle-text" data-wp-bind--hidden="!state.attendeesOpen()"><?php esc_html_e( 'Hide responses', 'clanbite' ); ?></span>
					<span class="clanbite-event-rsvp__toggle-text" hidden data-wp-bind--hidden="state.attendeesOpen()"><?php esc_html_e( 'Show responses', 'clanbite' ); ?></span>
				</button>
				<h3 class="clanbite-event-rsvp__attendees-heading"><?php esc_html_e( 'Responses', 'clanbite' ); ?></h3>
			</div>
			<div class="clanbite-event-rsvp__attendees-body" data-wp-bind--hidden="!state.attendeesOpen()">
				<p class="clanbite-event-rsvp__attendees-note" hidden></p>
				<ul class="clanbite-event-rsvp__list"></ul>
			</div>
		</div>
	<?php endif; ?>
</div>
