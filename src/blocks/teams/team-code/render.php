<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team code.
 *
 * @package clanbite
 */

$team_id = clanbite_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-team-code clanbite-team-code--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><span class="clanbite-team-code__value">' . esc_html__( 'Team code', 'clanbite' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$code = (string) get_post_meta( $team_id, 'cp_team_code', true );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-code' . ( '' === $code ? ' clanbite-team-code--empty' : '' ),
	),
	$block
);

$display = '' === $code ? __( '—', 'clanbite' ) : $code;

echo '<div ' . $wrapper_attributes . '><span class="clanbite-team-code__value">' . esc_html( $display ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
