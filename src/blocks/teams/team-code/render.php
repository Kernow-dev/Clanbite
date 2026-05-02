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
	echo wp_kses( '<div ' . $wrapper . '><span class="clanbite-team-code__value">' . esc_html__( 'Team code', 'clanbite' ) . '</span></div>', clanbite_block_fragment_allowed_html());
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

echo wp_kses( '<div ' . $wrapper_attributes . '><span class="clanbite-team-code__value">' . esc_html( $display ) . '</span></div>', clanbite_block_fragment_allowed_html());
