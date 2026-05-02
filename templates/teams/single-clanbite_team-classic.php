<?php
/**
 * Single team template for classic (non-block) themes.
 *
 * Renders the same block markup as the FSE template (`single-clanbite_team.html`) via {@see do_blocks()}
 * so team blocks resolve the current post in the loop.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Classic template locals in the loop scope.

get_header();

while ( have_posts() ) {
	the_post();

	$markup_path = __DIR__ . '/single-clanbite_team.html';
	if ( ! is_readable( $markup_path ) ) {
		the_content();
		continue;
	}

	$markup = file_get_contents( $markup_path );
	if ( false === $markup ) {
		the_content();
		continue;
	}

	echo clanbite_esc_block_fragment_html( do_blocks( $markup ) );
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

get_footer();
