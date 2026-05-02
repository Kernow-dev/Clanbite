<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: player country.
 *
 * @package clanbite
 */

$user_id = function_exists( 'clanbite_player_blocks_resolve_subject_user_id' )
	? (int) clanbite_player_blocks_resolve_subject_user_id( $block )
	: 0;

if ( $user_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-player-country clanbite-country-display clanbite-country-display--placeholder',
		),
		$block
	);
	echo wp_kses( '<div ' . $wrapper . '><span class="clanbite-country-display__label">' . esc_html__( 'Player country', 'clanbite' ) . '</span></div>', clanbite_block_fragment_allowed_html());
	return;
}

$code = (string) get_user_meta( $user_id, 'cp_player_country', true );

$label = '' !== $code && function_exists( 'clanbite_team_country_label' )
	? clanbite_team_country_label( $code )
	: '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-player-country clanbite-country-display' . ( '' === $code ? ' clanbite-country-display--empty' : '' ),
	),
	$block
);

if ( ! function_exists( 'clanbite_country_block_inner_html' ) ) {
	if ( '' === $code ) {
		$inner = '';
	} else {
		$inner = '<span class="clanbite-country-display__label">' . esc_html( $label ) . '</span>';
	}
	echo wp_kses( '<div ' . $wrapper_attributes . '>' . $inner . '</div>', clanbite_block_fragment_allowed_html());
	return;
}

if ( '' === $code ) {
	$inner = '';
} else {
	$inner = clanbite_country_block_inner_html(
		$attributes,
		$code,
		$label,
		'player',
		'clanbite/player-country',
		$block
	);
}

if ( '' === $inner && '' !== $code ) {
	$inner = '<span class="clanbite-country-display__label">' . esc_html__( '—', 'clanbite' ) . '</span>';
}

echo wp_kses( '<div ' . $wrapper_attributes . '>' . $inner . '</div>', clanbite_block_fragment_allowed_html());
