<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team country.
 *
 * @package clanbite
 */

$team_id = clanbite_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-team-country clanbite-country-display clanbite-country-display--placeholder',
		),
		$block
	);
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><span class="clanbite-country-display__label">' . esc_html__( 'Team country', 'clanbite' ) . '</span></div>' );
	return;
}

$code = (string) get_post_meta( $team_id, 'cp_team_country', true );

$label = '' !== $code && function_exists( 'clanbite_team_country_label' )
	? clanbite_team_country_label( $code )
	: '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-country clanbite-country-display' . ( '' === $code ? ' clanbite-country-display--empty' : '' ),
	),
	$block
);

if ( ! function_exists( 'clanbite_country_block_inner_html' ) ) {
	if ( '' === $code ) {
		$inner = '';
	} else {
		$inner = '<span class="clanbite-country-display__label">' . esc_html( $label ) . '</span>';
	}
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper_attributes . '>' . $inner . '</div>' );
	return;
}

if ( '' === $code ) {
	$inner = '';
} else {
	$inner = clanbite_country_block_inner_html(
		$attributes,
		$code,
		$label,
		'team',
		'clanbite/team-country',
		$block
	);
}

if ( '' === $inner && '' !== $code ) {
	$inner = '<span class="clanbite-country-display__label">' . esc_html__( '—', 'clanbite' ) . '</span>';
}

echo clanbite_esc_block_fragment_html( '<div ' . $wrapper_attributes . '>' . $inner . '</div>' );
