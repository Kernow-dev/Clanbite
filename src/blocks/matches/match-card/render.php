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

$markup  = $extension->render_card_block_markup( is_array( $attributes ) ? $attributes : array() );
$wrapper = get_block_wrapper_attributes( array(), $block );
echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '>' . $markup . '</div>' );
