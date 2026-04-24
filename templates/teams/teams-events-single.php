<?php
/**
 * Front template: single team event (classic / hybrid themes).
 *
 * Block markup: `teams-events-single.html` (also registered for FSE as `clanbite//teams-events-single`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/teams-events-single.html' );
}

get_footer();
