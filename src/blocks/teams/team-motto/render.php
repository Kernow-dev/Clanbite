<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team motto.
 *
 * @package clanbite
 */

$team_id = clanbite_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-team-motto clanbite-team-motto--placeholder',
		),
		$block
	);
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><span>' . esc_html__( 'Team motto', 'clanbite' ) . '</span></div>' );
	return;
}

$motto = (string) get_post_meta( $team_id, 'cp_team_motto', true );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-motto' . ( '' === $motto ? ' clanbite-team-motto--empty' : '' ),
	),
	$block
);

$display = '' === $motto ? __( 'No motto set.', 'clanbite' ) : $motto;

echo clanbite_esc_block_fragment_html( '<div ' . $wrapper_attributes . '><p class="clanbite-team-motto__text">' . esc_html( $display ) . '</p></div>' );
