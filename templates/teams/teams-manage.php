<?php
/**
 * Front template: manage team (classic / hybrid themes).
 *
 * Block markup: `teams-manage.html` (also registered for FSE as `clanbite//teams-manage`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/teams-manage.html' );
}

get_footer();
