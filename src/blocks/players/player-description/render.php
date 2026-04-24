<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders the player profile description (bio HTML) when set.
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
			'class' => 'clanbite-player-description clanbite-player-description--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><div class="clanbite-player-description__content">' . esc_html__( 'Player description', 'clanbite' ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$bio = function_exists( 'clanbite_players_get_display_bio' )
	? (string) clanbite_players_get_display_bio( $user_id )
	: '';

if ( '' === trim( wp_strip_all_tags( $bio ) ) ) {
	return '';
}

$align = isset( $attributes['textAlign'] ) ? sanitize_key( (string) $attributes['textAlign'] ) : '';
$class = array( 'clanbite-player-description__content' );
if ( $align && in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
	$class[] = 'has-text-align-' . $align;
}

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );
$content            = wp_kses_post( $bio );

printf(
	'<div %1$s><div class="%2$s">%3$s</div></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	esc_attr( implode( ' ', $class ) ),
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post().
);
