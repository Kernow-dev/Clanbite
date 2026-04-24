<?php
/**
 * Front template: player notifications subpage (`/players/{nicename}/notifications/`).
 *
 * Block markup: `player-notifications.html` (registered for FSE as `clanbite//player-notifications`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/player-notifications.html' );
}

get_footer();
