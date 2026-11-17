<?php
/**
 * Dependency-free test harness: boots WordPress and gives tests `$t->ok()`.
 *
 * Run with `php tests/php/run.php` (set WP_LOAD_PATH to wp-load.php when the plugin is not under wp-content/plugins) or with
 * `wp eval-file tests/php/run.php`. Tests create their own users and rows and remove them again. Use a development database.
 *
 * @package clanbite
 */

if ( ! defined( 'ABSPATH' ) ) {
	$clanbite_wp_load = getenv( 'WP_LOAD_PATH' ) ? (string) getenv( 'WP_LOAD_PATH' ) : dirname( __DIR__, 5 ) . '/wp-load.php';
	if ( ! is_readable( $clanbite_wp_load ) ) {
		fwrite( STDERR, "wp-load.php not found; set WP_LOAD_PATH.\n" );
		exit( 2 );
	}
	define( 'WP_USE_THEMES', false );
	require $clanbite_wp_load;
}

/**
 * Tiny assertion collector.
 */
final class Clanbite_Test_Harness {
	/** @var int */
	public int $passed = 0;
	/** @var string[] */
	public array $failures = array();
	/** @var callable[] */
	private array $cleanups = array();

	/**
	 * Record one assertion.
	 *
	 * @param string $name      What is checked.
	 * @param bool   $condition Result.
	 * @param mixed  $detail    Shown when it fails.
	 * @return void
	 */
	public function ok( string $name, bool $condition, $detail = '' ): void {
		if ( $condition ) {
			++$this->passed;
			echo "PASS {$name}\n";
			return;
		}
		$this->failures[] = $name;
		echo "FAIL {$name}" . ( '' === $detail ? '' : ' -> ' . substr( is_string( $detail ) ? $detail : wp_json_encode( $detail ), 0, 300 ) ) . "\n";
	}

	/**
	 * Run something when the tests finish (delete what a test created).
	 *
	 * @param callable $fn Cleanup.
	 * @return void
	 */
	public function cleanup( callable $fn ): void {
		$this->cleanups[] = $fn;
	}

	/**
	 * Run cleanups and print the summary.
	 *
	 * @return int Exit code.
	 */
	public function finish(): int {
		foreach ( array_reverse( $this->cleanups ) as $fn ) {
			$fn();
		}
		echo "\n{$this->passed} passed, " . count( $this->failures ) . " failed\n";

		return array() === $this->failures ? 0 : 1;
	}
}

require_once ABSPATH . 'wp-admin/includes/user.php';

$t = new Clanbite_Test_Harness();
