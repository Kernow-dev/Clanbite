<?php
/**
 * Front template: team events list (classic / hybrid themes).
 *
 * Block markup: `teams-events.html` (also registered for FSE as `clanbite//teams-events`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/teams-events.html' );
}

get_footer();
