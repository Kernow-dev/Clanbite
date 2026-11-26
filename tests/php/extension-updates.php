<?php
/**
 * An extension's updater runs once when its version goes up. Run `php tests/php/extension-updates.php` against a development
 * database. The stored list is put back afterwards.
 *
 * @package clanbite
 */

require __DIR__ . '/harness.php';

$loader = clanbite()->extensions;
if ( ! $loader->get( 'clanbite_points' ) ) {
	// Core's own extensions have empty updaters, so an add-on is used to see the updater run.
	echo "SKIP: switch the Points extension on to run these tests.\n";
	exit( 0 );
}
$original = get_option( 'clanbite_installed_extensions' );
$t->cleanup( static fn() => update_option( 'clanbite_installed_extensions', $original ) );

$current                             = (string) $loader->get( 'clanbite_points' )->version;
$stale                               = (array) $original;
$stale['clanbite_points']['version'] = '0.0.1';
update_option( 'clanbite_installed_extensions', $stale );

$ran = 0;
add_action(
	'clanbite_extension_updater_clanbite_points',
	static function () use ( &$ran ) {
		++$ran;
	}
);

$loader->setup_extensions();
$saved = (array) get_option( 'clanbite_installed_extensions' );
$t->ok( 'an older stored version runs the updater', 1 === $ran, $ran );
$t->ok( 'the new version is saved', $current === ( $saved['clanbite_points']['version'] ?? '' ), $saved['clanbite_points'] ?? null );
$t->ok( 'the list of installed extensions is otherwise unchanged', array_keys( (array) $original ) === array_keys( $saved ), array_keys( $saved ) );

$loader->setup_extensions();
$t->ok( 'the next request does not run it again', 1 === $ran, $ran );

$writes = 0;
add_filter(
	'pre_update_option_clanbite_installed_extensions',
	static function ( $value ) use ( &$writes ) {
		++$writes;

		return $value;
	}
);
$loader->setup_extensions();
$t->ok( 'nothing is written when nothing was updated', 0 === $writes, $writes );

exit( $t->finish() );
