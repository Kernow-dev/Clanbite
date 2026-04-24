<?php
/**
 * Front template: create team event (classic / hybrid themes).
 *
 * Block markup: `teams-events-create.html` (FSE: `clanbite//teams-events-create`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/teams-events-create.html' );
}

get_footer();
