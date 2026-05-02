<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team losses count.
 *
 * @package clanbite
 */

$team_id = clanbite_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-team-stat clanbite-team-stat--losses clanbite-team-stat--placeholder',
		),
		$block
	);
	echo wp_kses( '<div ' . $wrapper . '><span>' . esc_html__( 'Losses', 'clanbite' ) . '</span></div>', clanbite_block_fragment_allowed_html());
	return;
}

$val = (int) get_post_meta( $team_id, 'cp_team_losses', true );
$val = max( 0, $val );

$prefix_raw    = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
$prefix_plain  = trim( wp_strip_all_tags( $prefix_raw ) );
$postfix_raw   = isset( $attributes['postfix'] ) ? (string) $attributes['postfix'] : '';
$postfix_plain = trim( wp_strip_all_tags( $postfix_raw ) );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-stat clanbite-team-stat--losses',
	),
	$block
);

$parts = array();
if ( '' !== $prefix_plain ) {
	$parts[] = '<span class="clanbite-team-stat__prefix">' . wp_kses_post( $prefix_raw ) . '</span>';
}
$parts[] = '<span class="clanbite-team-stat__value">' . esc_html( (string) $val ) . '</span>';
if ( '' !== $postfix_plain ) {
	$parts[] = '<span class="clanbite-team-stat__postfix">' . wp_kses_post( $postfix_raw ) . '</span>';
}

echo wp_kses( '<div ' . $wrapper_attributes . '>' . implode( '', $parts ) . '</div>', clanbite_block_fragment_allowed_html());
