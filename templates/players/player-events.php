<?php
/**
 * Front template: player events subpage (`/players/{nicename}/events/`).
 *
 * Block markup: `player-events.html` (registered for FSE as `clanbite//player-events`).
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanbite_render_block_markup_file' ) ) {
	clanbite_render_block_markup_file( __DIR__ . '/player-events.html' );
}

get_footer();
