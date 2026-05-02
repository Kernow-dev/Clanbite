<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team description (post content).
 *
 * @package clanbite
 */

$team_id = clanbite_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-team-description clanbite-team-description--placeholder',
		),
		$block
	);
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><span class="clanbite-team-description__content">' . esc_html__( 'Team description', 'clanbite' ) . '</span></div>' );
	return;
}

$post = get_post( $team_id );
if ( ! $post || 'clanbite_team' !== $post->post_type ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-team-description clanbite-team-description--placeholder',
		),
		$block
	);
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper . '><span class="clanbite-team-description__content">' . esc_html__( 'Team description', 'clanbite' ) . '</span></div>' );
	return;
}

$content = $post->post_content;
if ( '' === trim( $content ) ) {
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-team-description clanbite-team-description--empty entry-content',
		),
		$block
	);
	echo clanbite_esc_block_fragment_html( '<div ' . $wrapper_attributes . '><div class="clanbite-team-description__content"><p>' . esc_html__( 'No description yet.', 'clanbite' ) . '</p></div></div>' );
	return;
}

/** This filter is documented in wp-includes/post-template.php */
$html = apply_filters( 'the_content', $content );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-description entry-content',
	),
	$block
);

echo clanbite_esc_block_fragment_html( '<div ' . $wrapper_attributes . '><div class="clanbite-team-description__content">' . $html . '</div></div>' );
