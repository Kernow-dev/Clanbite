<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders the player @ handle (nicename) when set.
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
			'class' => 'clanbite-player-handle clanbite-player-handle--placeholder',
		),
		$block
	);
	echo wp_kses( '<div ' . $wrapper . '><p class="clanbite-player-handle__text">@' . esc_html__( 'username', 'clanbite' ) . '</p></div>', clanbite_block_fragment_allowed_html());
	return;
}

$user = get_userdata( $user_id );
if ( ! $user instanceof WP_User ) {
	return '';
}

$nicename = trim( (string) $user->user_nicename );
if ( '' === $nicename ) {
	return '';
}

$text = '@' . $nicename;

/**
 * Filters the @ handle string for the Player @ handle block.
 *
 * @param string   $text     Full display string (normally includes leading @).
 * @param int      $user_id  Resolved player user ID.
 * @param string   $nicename Nicename without @.
 * @param WP_Block $block    Block instance.
 */
$text = (string) apply_filters( 'clanbite_player_handle_block_text', $text, $user_id, $nicename, $block );

if ( '' === trim( $text ) ) {
	return '';
}

$align = '';
if ( isset( $attributes['style']['typography']['textAlign'] ) ) {
	$align = sanitize_key( (string) $attributes['style']['typography']['textAlign'] );
} elseif ( isset( $attributes['textAlign'] ) ) {
	$align = sanitize_key( (string) $attributes['textAlign'] );
}

$class = array( 'clanbite-player-handle__text' );
if ( $align && in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
	$class[] = 'has-text-align-' . $align;
}

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );

echo wp_kses(
	sprintf(
		'<div %1$s><p class="%2$s">%3$s</p></div>',
		$wrapper_attributes,
		esc_attr( implode( ' ', $class ) ),
		esc_html( $text )
	), clanbite_block_fragment_allowed_html()
);
