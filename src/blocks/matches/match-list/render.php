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

$markup = $extension->render_list_block_markup( $attributes );

/**
 * Filter: `clanbite_match_list_block_inner_markup` — inner HTML for the Match list block (inside the wrapper div).
 *
 * @param string   $markup     Default inner markup.
 * @param array    $attributes Block attributes.
 * @param WP_Block $block      Block instance.
 */
$markup = (string) apply_filters( 'clanbite_match_list_block_inner_markup', $markup, $attributes, $block );

/**
 * Filter: `clanbite_match_block_wrapper_attributes` — attribute array passed to {@see get_block_wrapper_attributes()} for Match blocks.
 *
 * @param array    $wrapper_attrs Attribute map (empty default).
 * @param string   $block_name    Block name (e.g. `clanbite/match-list`).
 * @param WP_Block $block         Block instance.
 */
$wrapper_attrs = (array) apply_filters( 'clanbite_match_block_wrapper_attributes', array(), 'clanbite/match-list', $block );
$wrapper       = get_block_wrapper_attributes( $wrapper_attrs, $block );
echo wp_kses( '<div ' . $wrapper . '>' . $markup . '</div>', clanbite_block_fragment_allowed_html());
