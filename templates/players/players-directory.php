<?php
/**
 * Title: Players Directory
 * Slug: players-directory
 * Description: Lists site members at /players/ with links to each profile.
 *
 * Block markup: `players-directory.html` (also registered for FSE as `clanbite//players-directory`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/players-directory.html' );
}

get_footer();
