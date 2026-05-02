<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders the player public website when set.
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
			'class' => 'clanbite-player-website clanbite-player-website--placeholder',
		),
		$block
	);
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><p class="clanbite-player-website__text">' . esc_html__( 'Player website', 'clanbite' ) . '</p></div>' );
	return;
}

$raw = function_exists( 'clanbite_players_get_display_website' )
	? trim( (string) clanbite_players_get_display_website( $user_id ) )
	: '';

if ( '' === $raw ) {
	return '';
}

$align = isset( $attributes['textAlign'] ) ? sanitize_key( (string) $attributes['textAlign'] ) : '';
$p_class = array( 'clanbite-player-website__text' );
if ( $align && in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
	$p_class[] = 'has-text-align-' . $align;
}

$candidate = preg_match( '#^https?://#i', $raw ) ? $raw : 'https://' . $raw;
$href_raw  = esc_url( $candidate );
$href      = ( '' !== $href_raw && wp_http_validate_url( $href_raw ) ) ? $href_raw : '';

$inner = esc_html( $raw );

if ( ! empty( $attributes['isLink'] ) && '' !== $href && function_exists( 'clanbite_block_entity_link_url' ) ) {
	$href_filtered = clanbite_block_entity_link_url( $href, 'clanbite/player-website', $user_id, $block );
	if ( '' !== $href_filtered ) {
		$target = ( isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ) ? ' target="_blank"' : '';
		$rel    = function_exists( 'clanbite_block_entity_link_rel' ) ? clanbite_block_entity_link_rel( $attributes ) : '';
		$rel_at = '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '';
		$inner  = '<a class="clanbite-player-website__link" href="' . esc_url( $href_filtered ) . '"' . $target . $rel_at . '>' . $inner . '</a>';
	}
}

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );

echo clanbite_esc_block_fragment_html(
	sprintf(
		'<div %1$s><p class="%2$s">%3$s</p></div>',
		$wrapper_attributes,
		esc_attr( implode( ' ', $p_class ) ),
		$inner
	)
);
