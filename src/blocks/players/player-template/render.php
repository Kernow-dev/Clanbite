<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server-side render: repeat inner blocks for each roster user with `clanbite/playerId` context.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Saved inner HTML (unused).
 * @var WP_Block $block      Block instance.
 *
 * @package clanbite
 */

$inherit = isset( $block->context['clanbite/inheritTeamContext'] )
	? (bool) $block->context['clanbite/inheritTeamContext']
	: true;

$attr_team_id = (int) ( $block->context['clanbite/teamId'] ?? 0 );

$exclude_banned = ! isset( $block->context['clanbite/excludeBannedMembers'] )
	|| (bool) $block->context['clanbite/excludeBannedMembers'];

$team_id = $attr_team_id;
if ( $team_id < 1 && $inherit && function_exists( 'clanbite_team_block_resolve_team_id' ) ) {
	$team_id = (int) clanbite_team_block_resolve_team_id(
		isset( $block->context ) && is_array( $block->context ) ? $block->context : array()
	);
}

if ( $team_id < 1 || ! function_exists( 'clanbite_player_query_resolve_member_user_ids' ) ) {
	return '';
}

$query_options = function_exists( 'clanbite_player_query_options_from_block_context' )
	? clanbite_player_query_options_from_block_context(
		isset( $block->context ) && is_array( $block->context ) ? $block->context : array()
	)
	: array();

$user_ids = clanbite_player_query_resolve_member_user_ids( $team_id, $exclude_banned, $query_options, $block );
if ( array() === $user_ids ) {
	return '';
}

$base_context = isset( $block->context ) && is_array( $block->context ) ? $block->context : array();

$items_html = '';
foreach ( $user_ids as $member_id ) {
	$member_id = (int) $member_id;
	if ( $member_id < 1 ) {
		continue;
	}

	$merged_context = array_merge(
		$base_context,
		array( 'clanbite/playerId' => $member_id )
	);

	$row_html = '';
	foreach ( $block->inner_blocks as $inner_block ) {
		$inner = new WP_Block( $inner_block->parsed_block, $merged_context );
		$row_html .= $inner->render();
	}

	$items_html .= '<li class="clanbite-player-template__item">' . $row_html . '</li>';
}

if ( '' === $items_html ) {
	return '';
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-player-template__entries',
	),
	$block
);

echo wp_kses( '<ul ' . $wrapper . '>' . $items_html . '</ul>', clanbite_block_fragment_allowed_html());
