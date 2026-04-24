<?php
/**
 * Front template: team matches list (classic / hybrid themes).
 *
 * Block markup: `teams-matches.html` (also registered for FSE as `clanbite//teams-matches`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/teams-matches.html' );
}

get_footer();
