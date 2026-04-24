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
	echo '<div ' . $wrapper . '><span class="clanbite-country-display__label">' . esc_html__( 'Team country', 'clanbite' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
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
	echo '<div ' . $wrapper_attributes . '>' . $inner . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes from get_block_wrapper_attributes(); $inner escaped.
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

echo '<div ' . $wrapper_attributes . '>' . $inner . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes from get_block_wrapper_attributes(); $inner built via esc_html / filters.
