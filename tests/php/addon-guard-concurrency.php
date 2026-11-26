<?php
/**
 * Two requests running the add-on guard at the same time must not repeat its work. Run `php tests/php/addon-guard-concurrency.php`
 * against a development database.
 *
 * Real WordPress processes race each other. They only ever see throwaway plugins: the list of active plugins and the guard's own
 * options are redirected to `zz_guard_*` options, so the site's real values are never read or written by the guard.
 *
 * @package clanbite
 */

const ZZ_REDIRECT = array(
	'active_plugins'                       => 'zz_guard_active',
	'clanbite_disabled_addons'             => 'zz_guard_store',
	'clanbite_addon_guard_signature'       => 'zz_guard_sig',
	'clanbite_addon_guard_signature_files' => 'zz_guard_sig_files',
);

// ---- Hooks that must exist before WordPress loads ----
$zz_hook = static function ( string $tag, callable $callback, int $args = 1 ): void {
	$GLOBALS['wp_filter'][ $tag ][10][] = array(
		'function'      => $callback,
		'accepted_args' => $args,
	);
};
// Read straight from the database: another process may have just changed the value.
$zz_read = static function ( string $name ) {
	global $wpdb;
	$raw = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );

	return null === $raw ? null : maybe_unserialize( $raw );
};
foreach ( ZZ_REDIRECT as $zz_real => $zz_virtual ) {
	$zz_hook(
		"pre_option_{$zz_real}",
		static function () use ( $zz_read, $zz_virtual ) {
			$value = $zz_read( $zz_virtual );

			return null === $value ? array() : $value;
		}
	);
	$zz_hook(
		"pre_update_option_{$zz_real}",
		static function ( $value, $old ) use ( $zz_virtual ) {
			global $wpdb;
			$wpdb->replace(
				$wpdb->options,
				array(
					'option_name'  => $zz_virtual,
					'option_value' => maybe_serialize( $value ),
					'autoload'     => 'off',
				)
			);

			// Returning the old value stops WordPress writing the real option.
			return $old;
		},
		2
	);
}
$zz_worker = 'worker' === ( $argv[1] ?? '' );
// Workers hold the guard back while WordPress loads, so they can start it together.
$GLOBALS['zz_guard_on'] = false;
$zz_hook( 'clanbite_addon_guard_enabled', static fn() => $GLOBALS['zz_guard_on'] );
if ( $zz_worker ) {
	define( 'DOING_CRON', true );
}

require __DIR__ . '/harness.php';

use Kernowdev\Clanbite\Compat\Compat_Monitor;

if ( $zz_worker ) {
	list( , , $dir, $start ) = $argv;
	add_action(
		'clanbite_addon_disabled',
		static function ( $file ) use ( $dir ): void {
			file_put_contents( "{$dir}/events.log", "disabled {$file}\n", FILE_APPEND );
		}
	);
	add_action(
		'clanbite_addon_reenabled',
		static function ( $file ) use ( $dir ): void {
			file_put_contents( "{$dir}/events.log", "reenabled {$file}\n", FILE_APPEND );
		}
	);
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	// What an ordinary request has already read before the guard runs.
	get_plugins();
	while ( microtime( true ) < (float) $start ) {
		usleep( 1000 );
	}
	$GLOBALS['zz_guard_on'] = true;
	Compat_Monitor::maybe_sweep();
	exit( 0 );
}

global $wpdb;
$dir = sys_get_temp_dir() . '/clanbite-guard-' . wp_generate_password( 8, false );
wp_mkdir_p( $dir );
$plugins = array(
	// Declares a Clanbite it cannot run on: must be switched off.
	'zz-guard-old'  => '^99.0.0',
	// Was switched off earlier and fits again: must be switched back on.
	'zz-guard-back' => '^1.0.0',
);
$real    = $zz_read( 'active_plugins' );
$remove  = static function () use ( $plugins, $dir, $wpdb ): void {
	foreach ( array_keys( $plugins ) as $slug ) {
		@unlink( WP_PLUGIN_DIR . "/{$slug}/{$slug}.php" );
		@rmdir( WP_PLUGIN_DIR . "/{$slug}" );
	}
	foreach ( array_merge( array_values( ZZ_REDIRECT ), array( 'clanbite_addon_guard.lock' ) ) as $name ) {
		$wpdb->delete( $wpdb->options, array( 'option_name' => $name ) );
	}
	array_map( 'unlink', (array) glob( "{$dir}/*" ) );
	@rmdir( $dir );
	wp_cache_flush();
};
$t->cleanup( $remove );

foreach ( $plugins as $slug => $needs ) {
	wp_mkdir_p( WP_PLUGIN_DIR . "/{$slug}" );
	// The activation hook is slow, so a second request has time to get in, and it records every time it runs.
	file_put_contents(
		WP_PLUGIN_DIR . "/{$slug}/{$slug}.php",
		"<?php\n/**\n * Plugin Name: {$slug}\n * Version: 1.0.0\n * Requires Clanbite: {$needs}\n */\ndefined( 'ABSPATH' ) || exit;\n"
		. "register_activation_hook( __FILE__, static function () { file_put_contents( " . var_export( "{$dir}/events.log", true ) . ", \"activation {$slug}\\n\", FILE_APPEND ); usleep( 700000 ); } );\n"
	);
}
$core  = plugin_basename( dirname( __DIR__, 2 ) . '/clanbite.php' );
$old   = 'zz-guard-old/zz-guard-old.php';
$back  = 'zz-guard-back/zz-guard-back.php';
$reset = static function () use ( $wpdb, $dir, $core, $old, $back ): void {
	$values = array(
		'zz_guard_active'    => array( $core, $old ),
		'zz_guard_store'     => array(
			$back => array(
				'name'    => 'zz-guard-back',
				'version' => '1.0.0',
				'reasons' => array( 'test' ),
				'core'    => '0.9.0',
				'time'    => time(),
			),
		),
		'zz_guard_sig'       => 'stale',
		'zz_guard_sig_files' => array(),
	);
	foreach ( $values as $name => $value ) {
		$wpdb->replace(
			$wpdb->options,
			array(
				'option_name'  => $name,
				'option_value' => maybe_serialize( $value ),
				'autoload'     => 'off',
			)
		);
	}
	$wpdb->delete( $wpdb->options, array( 'option_name' => 'clanbite_addon_guard.lock' ) );
	@unlink( "{$dir}/events.log" );
	wp_cache_flush();
};
$race  = static function ( int $workers ) use ( $dir ): array {
	$start = microtime( true ) + 4;
	$ini   = (string) ini_get( 'mysqli.default_socket' );
	$procs = array();
	for ( $i = 0; $i < $workers; $i++ ) {
		$command = array( PHP_BINARY );
		if ( '' !== $ini ) {
			array_push( $command, '-d', "mysqli.default_socket={$ini}" );
		}
		array_push( $command, __FILE__, 'worker', $dir, (string) $start );
		$procs[] = proc_open( $command, array( 1 => array( 'file', "{$dir}/w{$i}.out", 'w' ), 2 => array( 'file', "{$dir}/w{$i}.err", 'w' ) ), $pipes );
	}
	$codes = array_map( 'proc_close', $procs );
	$log   = is_file( "{$dir}/events.log" ) ? array_filter( explode( "\n", (string) file_get_contents( "{$dir}/events.log" ) ) ) : array();

	return array( $codes, array_count_values( $log ) );
};

for ( $round = 1; $round <= 3; $round++ ) {
	$reset();
	list( $codes, $count ) = $race( 3 );
	$t->ok( "round {$round}: every request finishes", array( 0, 0, 0 ) === $codes, $codes );
	$t->ok( "round {$round}: the add-on is switched off once", 1 === ( $count[ "disabled {$old}" ] ?? 0 ), $count );
	$t->ok( "round {$round}: the returning add-on's activation hook runs once", 1 === ( $count['activation zz-guard-back'] ?? 0 ) && 1 === ( $count[ "reenabled {$back}" ] ?? 0 ), $count );
	$store = (array) $zz_read( 'zz_guard_store' );
	$list  = array_values( (array) $zz_read( 'zz_guard_active' ) );
	$t->ok( "round {$round}: the record of what was switched off is kept, and the right plugins are active", isset( $store[ $old ] ) && ! isset( $store[ $back ] ) && in_array( $back, $list, true ) && ! in_array( $old, $list, true ), array( array_keys( $store ), $list ) );
}
$t->ok( 'the lock is released afterwards', null === $zz_read( 'clanbite_addon_guard.lock' ) );

// A request that dies while holding the lock must not stop the guard for good.
$reset();
$wpdb->insert(
	$wpdb->options,
	array(
		'option_name'  => 'clanbite_addon_guard.lock',
		'option_value' => time() - 3600,
		'autoload'     => 'off',
	)
);
list( , $count ) = $race( 1 );
$t->ok( 'a lock left behind long ago is taken over', 1 === ( $count[ "disabled {$old}" ] ?? 0 ), $count );

// While another request holds the lock, an ordinary request carries on without waiting.
$reset();
$wpdb->insert(
	$wpdb->options,
	array(
		'option_name'  => 'clanbite_addon_guard.lock',
		'option_value' => time(),
		'autoload'     => 'off',
	)
);
list( , $count ) = $race( 1 );
$t->ok( 'an ordinary request leaves the work to the one that holds the lock', array() === $count && 'stale' === $zz_read( 'zz_guard_sig' ), $count );

$t->ok( 'the site’s real list of active plugins was never touched', $zz_read( 'active_plugins' ) === $real );

exit( $t->finish() );
