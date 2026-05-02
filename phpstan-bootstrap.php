<?php
/**
 * Minimal environment for static analysis (WordPress constants not loaded by stubs).
 *
 * Loaded only by PHPStan (`phpstan.neon.dist`). Not included by the plugin bootstrap.
 */
if ( defined( 'ABSPATH' ) ) {
	return;
}

// PHPStan sets this while analysing (see phpstan/phpstan). Allow without requiring CLI SAPI.
$phpstan_running = defined( '__PHPSTAN_RUNNING__' );
$cli_like_sapi   = defined( 'PHP_SAPI' ) && in_array( PHP_SAPI, array( 'cli', 'phpdbg', 'embed' ), true );

// Narrow direct-access guard: typical HTTP-style requests only (avoids blocking other non-CLI embeds).
if ( ! $phpstan_running && ! $cli_like_sapi && isset( $_SERVER['REQUEST_METHOD'] ) ) {
	exit;
}

define( 'ABSPATH', '/' );
