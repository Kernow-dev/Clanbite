<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server render: Event calendar (Interactivity: month / week / day, REST range queries).
 *
 * @package clanbite
 */

$scope          = sanitize_key( (string) ( $attributes['scopeType'] ?? 'team' ) );
$team_id        = (int) ( $attributes['teamId'] ?? 0 );
$group_id       = (int) ( $attributes['groupId'] ?? 0 );
$player_user_id = (int) ( $attributes['playerUserId'] ?? 0 );
$default_view   = sanitize_key( (string) ( $attributes['defaultView'] ?? 'month' ) );

$resolved_player_id = 0;

if ( ! in_array( $default_view, array( 'month', 'week', 'day', 'list' ), true ) ) {
	$default_view = 'month';
}

if ( $team_id < 1 ) {
	$team_id = (int) get_query_var( 'clanbite_events_team_id' );
}
if ( $group_id < 1 ) {
	$group_id = (int) get_query_var( 'clanbite_events_group_id' );
}
if ( $group_id < 1 && 'group' === $scope && function_exists( 'clanbite_group_profile_context_group_id' ) ) {
	$group_id = (int) clanbite_group_profile_context_group_id();
}

if ( 'player' === $scope ) {
	if ( $player_user_id < 1 && function_exists( 'clanbite_player_profile_context_user_id' ) ) {
		$player_user_id = (int) clanbite_player_profile_context_user_id();
	}

	$resolved_player_id = $player_user_id;

	if ( ! is_user_logged_in() || (int) get_current_user_id() !== $player_user_id || $player_user_id < 1 ) {
		return '';
	}

	if ( ! function_exists( 'clanbite_events_are_globally_enabled' ) || ! clanbite_events_are_globally_enabled() ) {
		return '';
	}

	$scope_api = 'player';
} elseif ( 'team' === $scope ) {
	if ( $team_id < 1 ) {
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanbite-event-calendar clanbite-event-calendar--placeholder' ), $block );
		echo wp_kses( '<div ' . $wrapper . '><p>' . esc_html__( 'No team context for events.', 'clanbite' ) . '</p></div>', clanbite_block_fragment_allowed_html());
		return;
	}

	if ( function_exists( 'clanbite_events_are_enabled_for_team' ) && ! clanbite_events_are_enabled_for_team( $team_id ) ) {
		return '';
	}

	$scope_api = 'team';
} elseif ( 'group' === $scope ) {
	if ( $group_id < 1 ) {
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanbite-event-calendar clanbite-event-calendar--placeholder' ), $block );
		echo wp_kses( '<div ' . $wrapper . '><p>' . esc_html__( 'No group context for events.', 'clanbite' ) . '</p></div>', clanbite_block_fragment_allowed_html());
		return;
	}

	if ( function_exists( 'clanbite_events_are_enabled_for_group' ) && ! clanbite_events_are_enabled_for_group( $group_id ) ) {
		return '';
	}

	$scope_api = 'group';
} else {
	return '';
}

$create_url = '';
if ( 'team' === $scope_api && $team_id > 0 && is_user_logged_in() && function_exists( 'clanbite_teams_user_can_manage' ) && clanbite_teams_user_can_manage( $team_id ) && function_exists( 'clanbite_teams_get_team_events_create_url' ) ) {
	$create_url = clanbite_teams_get_team_events_create_url( $team_id );
}
if ( 'group' === $scope_api && $group_id > 0 && is_user_logged_in() && function_exists( 'clanbite_groups_user_can_manage' ) ) {
	$uid = (int) get_current_user_id();
	if ( clanbite_groups_user_can_manage( $group_id, $uid ) ) {
		/**
		 * Filter URL for “add event” from the group event calendar (extension-defined).
		 *
		 * @param string $url      URL or empty.
		 * @param int    $group_id Group post ID.
		 */
		$create_url = (string) apply_filters( 'clanbite_group_events_create_url', '', $group_id );
	}
}

/**
 * Filter calendar “add event” link (team/group).
 *
 * @param string $create_url URL or empty.
 * @param string $scope_api  `team`, `group`, or `player`.
 * @param int    $team_id    Team ID when applicable.
 * @param int    $group_id   Group ID when applicable.
 */
$create_url = (string) apply_filters( 'clanbite_event_calendar_create_url', $create_url, $scope_api, $team_id, $group_id );

$today_ymd = wp_date( 'Y-m-d' );

$range_per_page = function_exists( 'clanbite_events_rest_default_per_page_for_range_query' )
	? clanbite_events_rest_default_per_page_for_range_query()
	: 200;

$config = array(
	'scope'       => $scope_api,
	'teamId'      => $team_id,
	'groupId'     => $group_id,
	'playerId'    => 'player' === $scope_api ? $resolved_player_id : 0,
	'view'        => $default_view,
	'restUrl'     => esc_url_raw( rest_url( 'clanbite/v1/event-posts' ) ),
	'nonce'       => wp_create_nonce( 'wp_rest' ),
	'anchor'      => wp_date( 'Y-m-d' ),
	'rangePerPage' => (int) $range_per_page,
	'createUrl'   => $create_url ? esc_url_raw( $create_url ) : '',
	'i18n'        => array(
		'loading'     => __( 'Loading…', 'clanbite' ),
		'error'       => __( 'Could not load events.', 'clanbite' ),
		'noEvents'    => __( 'No events in this range.', 'clanbite' ),
		'month'       => __( 'Month', 'clanbite' ),
		'week'        => __( 'Week', 'clanbite' ),
		'day'         => __( 'Day', 'clanbite' ),
		'list'        => __( 'List', 'clanbite' ),
		'today'       => __( 'Today', 'clanbite' ),
		'prev'        => __( 'Previous', 'clanbite' ),
		'next'        => __( 'Next', 'clanbite' ),
		'addEvent'    => __( 'Add event', 'clanbite' ),
		'untitled'    => __( '(Untitled)', 'clanbite' ),
		'weekdays'    => array(
			__( 'Sun', 'clanbite' ),
			__( 'Mon', 'clanbite' ),
			__( 'Tue', 'clanbite' ),
			__( 'Wed', 'clanbite' ),
			__( 'Thu', 'clanbite' ),
			__( 'Fri', 'clanbite' ),
			__( 'Sat', 'clanbite' ),
		),
	),
);

$calendar_heading = '';
$calendar_surface = '';
$config['calSsrHydrated'] = false;

if ( function_exists( 'clanbite_events_block_query_collection' ) ) {
	$range_iso = clanbite_events_calendar_range_iso_for_view( $default_view, $config['anchor'] );
	$query_args = array(
		'per_page'      => (int) $range_per_page,
		'page'          => 1,
		'time_scope'    => 'all',
		'order'         => 'asc',
		'starts_after'  => $range_iso['starts_after'],
		'starts_before' => $range_iso['starts_before'],
	);
	if ( 'team' === $scope_api ) {
		$query_args['team_id'] = $team_id;
	} elseif ( 'group' === $scope_api ) {
		$query_args['group_id'] = $group_id;
	} else {
		$query_args['player_user_id'] = $resolved_player_id;
	}

	$cal_items = array();
	$coll      = clanbite_events_block_query_collection( $query_args );
	if ( ! is_wp_error( $coll ) && isset( $coll['items'] ) && is_array( $coll['items'] ) ) {
		$cal_items = $coll['items'];
	}

	$calendar_heading = clanbite_events_calendar_heading_for_view( $default_view, $config['anchor'] );
	$calendar_surface = clanbite_event_calendar_render_surface_html(
		$default_view,
		$config['anchor'],
		$cal_items,
		$config['i18n']['weekdays'],
		$today_ymd,
		array(
			'untitled' => $config['i18n']['untitled'],
			'noEvents' => $config['i18n']['noEvents'],
		)
	);
	$config['calSsrHydrated'] = true;
} elseif ( 'month' === $default_view && function_exists( 'clanbite_event_calendar_month_grid_markup' ) ) {
	$anchor_ts = strtotime( $config['anchor'] . ' 12:00:00' );
	$calendar_heading = $anchor_ts ? wp_date( 'F Y', $anchor_ts ) : '';
	$calendar_surface = clanbite_event_calendar_month_grid_markup(
		$config['anchor'],
		$config['i18n']['weekdays'],
		$today_ymd
	);
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-event-calendar-wrap clanbite-event-calendar-wrap--interactive',
	),
	$block
);
?>
<?php ob_start(); ?>
<div
	<?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered; escaped via wp_kses(, clanbite_block_fragment_allowed_html()) before output. ?>
	data-wp-interactive="clanbite-event-calendar"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
	data-wp-init="callbacks.init"
>
	<div class="clanbite-event-calendar__toolbar">
		<div class="clanbite-event-calendar__views" role="group" aria-label="<?php esc_attr_e( 'Calendar view', 'clanbite' ); ?>">
			<button type="button" class="clanbite-event-calendar__view-btn" data-cal-view="month" data-wp-on--click="actions.setView"><?php echo esc_html( $config['i18n']['month'] ); ?></button>
			<button type="button" class="clanbite-event-calendar__view-btn" data-cal-view="week" data-wp-on--click="actions.setView"><?php echo esc_html( $config['i18n']['week'] ); ?></button>
			<button type="button" class="clanbite-event-calendar__view-btn" data-cal-view="day" data-wp-on--click="actions.setView"><?php echo esc_html( $config['i18n']['day'] ); ?></button>
			<button type="button" class="clanbite-event-calendar__view-btn" data-cal-view="list" data-wp-on--click="actions.setView"><?php echo esc_html( $config['i18n']['list'] ); ?></button>
		</div>
		<div class="clanbite-event-calendar__nav">
			<button type="button" class="clanbite-event-calendar__nav-btn" data-wp-on--click="actions.prevPeriod" aria-label="<?php echo esc_attr( $config['i18n']['prev'] ); ?>">‹</button>
			<button type="button" class="clanbite-event-calendar__today" data-wp-on--click="actions.goToday"><?php echo esc_html( $config['i18n']['today'] ); ?></button>
			<button type="button" class="clanbite-event-calendar__nav-btn" data-wp-on--click="actions.nextPeriod" aria-label="<?php echo esc_attr( $config['i18n']['next'] ); ?>">›</button>
		</div>
		<?php if ( '' !== $config['createUrl'] ) : ?>
			<div class="wp-block-button">
				<a class="wp-block-button__link wp-element-button clanbite-event-calendar__add" href="<?php echo esc_url( $config['createUrl'] ); ?>"><?php echo esc_html( $config['i18n']['addEvent'] ); ?></a>
			</div>
		<?php endif; ?>
	</div>
	<h2 class="clanbite-event-calendar__heading"><?php echo '' !== $calendar_heading ? esc_html( $calendar_heading ) : ''; ?></h2>
	<p class="clanbite-event-calendar__sr-only" hidden data-wp-bind--hidden="!context.calLoading" aria-live="polite"><?php echo esc_html( $config['i18n']['loading'] ); ?></p>
	<p class="clanbite-event-calendar__error" hidden data-wp-bind--hidden="!context.fetchError" data-wp-text="context.fetchError" role="alert"></p>
	<div class="clanbite-event-calendar__surface"><?php echo $calendar_surface ? $calendar_surface : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered; calendar HTML from clanbite_event_calendar_render_surface_html() / grid helpers using esc_html/esc_attr. ?></div>
</div>
<?php echo wp_kses( (string) ob_get_clean(), clanbite_block_fragment_allowed_html()); ?>
