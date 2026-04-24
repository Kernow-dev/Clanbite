<?php
defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Main;

/**
 * Grab the Main object and return it.
 * Wrapper for Main::instance().
 *
 * @return Main Singleton instance of plugin class.
 */
function clanbite(): Main {
	return Main::instance();
}
