<?php
/**
 * Front template: player profile / author archive (classic / hybrid themes).
 *
 * Block markup: `player-profile.html` (also registered for FSE as `clanbite//players-player-profile`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/player-profile.html' );
}

get_footer();
