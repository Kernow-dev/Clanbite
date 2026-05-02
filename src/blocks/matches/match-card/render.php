<?php
/**
 * Server render for the Match card block.
 *
 * @package clanbite
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
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

$attrs = is_array( $attributes ) ? $attributes : array();

$markup = $extension->render_card_block_markup( $attrs );

/**
 * Filter: `clanbite_match_card_block_inner_markup` — inner HTML for the Match card block (inside the wrapper div).
 *
 * @param string   $markup     Default inner markup.
 * @param array    $attrs      Block attributes.
 * @param WP_Block $block      Block instance.
 */
$markup = (string) apply_filters( 'clanbite_match_card_block_inner_markup', $markup, $attrs, $block );

/**
 * Filter: `clanbite_match_block_wrapper_attributes` — attribute array passed to {@see get_block_wrapper_attributes()} for Match blocks.
 *
 * @param array    $wrapper_attrs Attribute map (empty default).
 * @param string   $block_name    Block name (e.g. `clanbite/match-card`).
 * @param WP_Block $block         Block instance.
 */
$wrapper_attrs = (array) apply_filters( 'clanbite_match_block_wrapper_attributes', array(), 'clanbite/match-card', $block );
$wrapper       = get_block_wrapper_attributes( $wrapper_attrs, $block );
echo wp_kses( '<div ' . $wrapper . '>' . $markup . '</div>', clanbite_block_fragment_allowed_html());
