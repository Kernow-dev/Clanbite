<?php
/**
 * Server render for the Match list block.
 *
 * @package clanbite
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content (unused for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
$extension = function_exists( 'clanbite_matches' ) ? clanbite_matches() : null;

if ( ! $extension instanceof \Kernowdev\Clanbite\Extensions\Matches ) {
	echo '';
	return;
}

$attributes = is_array( $attributes ) ? $attributes : array();
$team_qv    = (int) get_query_var( 'clanbite_matches_team_id' );
if ( 0 === (int) ( $attributes['teamId'] ?? 0 ) && $team_qv > 0 ) {
	$attributes['teamId'] = $team_qv;
}

$markup  = $extension->render_list_block_markup( $attributes );
$wrapper = get_block_wrapper_attributes( array(), $block );
echo wp_kses( '<div ' . $wrapper . '>' . $markup . '</div>', clanbite_block_fragment_allowed_html());
