<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders the player birthday when set.
 *
 * @package clanbite
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

$user_id = function_exists( 'clanbite_player_blocks_resolve_subject_user_id' )
	? (int) clanbite_player_blocks_resolve_subject_user_id( $block )
	: 0;

if ( $user_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-player-birthday clanbite-player-birthday--placeholder',
		),
		$block
	);
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><p class="clanbite-player-birthday__text">' . esc_html__( 'Player birthday', 'clanbite' ) . '</p></div>' );
	return;
}

$birthday = function_exists( 'clanbite_players_get_display_birthday' )
	? trim( (string) clanbite_players_get_display_birthday( $user_id ) )
	: '';

if ( '' === $birthday ) {
	return '';
}

$align = isset( $attributes['textAlign'] ) ? sanitize_key( (string) $attributes['textAlign'] ) : '';
$class = array( 'clanbite-player-birthday__text' );
if ( $align && in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
	$class[] = 'has-text-align-' . $align;
}

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );

echo clanbite_esc_block_fragment_html(
	sprintf(
		'<div %1$s><p class="%2$s">%3$s</p></div>',
		$wrapper_attributes,
		esc_attr( implode( ' ', $class ) ),
		esc_html( $birthday )
	)
);
