<?php
/**
 * Minimal environment for static analysis (WordPress constants not loaded by stubs).
 *
 * Loaded only by PHPStan (`phpstan.neon.dist`). This file is not included by the plugin bootstrap.
 */
if ( ! defined( 'ABSPATH' ) ) {
	if ( defined( 'PHP_SAPI' ) && 'cli' === PHP_SAPI ) {
		define( 'ABSPATH', '/' );
	} else {
		exit;
	}
}
