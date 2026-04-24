<?php
/**
 * Front template: create team (classic / hybrid themes).
 *
 * Block markup: `teams-create.html` (also registered for FSE as `clanbite//teams-create`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/teams-create.html' );
}

get_footer();
