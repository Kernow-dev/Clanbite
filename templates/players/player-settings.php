<?php
/**
 * Front template: player settings (classic / hybrid themes).
 *
 * Block markup: `player-settings.html` (also registered for FSE as `clanbite//player-settings`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/player-settings.html' );
}

get_footer();
