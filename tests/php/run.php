<?php
/**
 * Core tests: components registry, relative time and avatar context. Run: `php tests/php/run.php`.
 *
 * @package clanbite
 */

require __DIR__ . '/harness.php';

do_action( 'init' );
wp_set_current_user( 1 );

// Registry.
$components = clanbite_get_components();
foreach ( array( 'avatar', 'toasts', 'popups', 'clipboard', 'time' ) as $slug ) {
	$t->ok( "core registers {$slug}", isset( $components[ $slug ] ) );
}
$t->ok( 'unknown component is absent and renders nothing', ! clanbite_has_component( 'zz-none' ) && '' === clanbite_render_component( 'zz-none' ) );

clanbite_register_component(
	'zz-needs',
	array(
		'provider' => 'test',
		'requires' => array( 'zz-missing' ),
		'render'   => static fn(): string => 'x',
	)
);
$t->ok( 'missing requirement makes it unavailable', ! clanbite_has_component( 'zz-needs' ) && array( 'zz-missing' ) === clanbite_component_missing_requirements( 'zz-needs' ) );
$t->ok( 'duplicate slug is refused', false === clanbite_register_component( 'avatar', array( 'render' => static fn(): string => 'x' ) ) );
$t->ok( 'replace swaps the provider', clanbite_register_component( 'zz-needs', array( 'replace' => true, 'provider' => 'test2', 'render' => static fn(): string => 'ok' ) ) && 'ok' === clanbite_render_component( 'zz-needs' ) );
add_filter( 'clanbite_component_html', static fn( $h, $s ) => 'zz-needs' === $s ? 'filtered' : $h, 10, 2 );
$t->ok( 'html filter applies', 'filtered' === clanbite_render_component( 'zz-needs' ) );
add_filter( 'clanbite_component_available', static fn( $a, $s ) => 'zz-needs' === $s ? false : $a, 10, 2 );
$t->ok( 'availability filter applies', ! clanbite_has_component( 'zz-needs' ) );
$t->ok( 'enqueue runs a component', clanbite_enqueue_component( 'popups' ) && wp_script_is( 'clanbite-popups', 'enqueued' ) );

// Time.
$t->ok( 'relative time wording', '5 minutes ago' === clanbite_human_time_ago( time() - 300 ) );
$t->ok( 'time component renders it', '5 minutes ago' === clanbite_render_component( 'time', array( 'timestamp' => time() - 300 ) ) );
add_filter( 'clanbite_human_time_ago', static fn( $text ) => '~' . $text );
$t->ok( 'time wording is filterable', 0 === strpos( clanbite_human_time_ago( time() - 7200 ), '~' ) );

// Avatar context.
$html = clanbite_render_component(
	'avatar',
	array(
		'type'    => 'player',
		'id'      => 1,
		'size'    => 'small',
		'context' => 'my_surface',
	)
);
$t->ok( 'avatar carries is-{context}', false !== strpos( $html, 'is-my-surface' ) );
$seen = null;
add_filter(
	'clanbite_avatar_args',
	static function ( $args ) use ( &$seen ) {
		$seen = $args;
		return $args;
	}
);
clanbite_render_avatar( array( 'type' => 'player', 'id' => 1, 'context' => 'x', 'object_type' => 'post', 'extension' => 'zz' ) );
$t->ok( 'avatar args carry viewer and object context', is_array( $seen ) && 1 === $seen['viewer_id'] && true === $seen['is_viewer'] && 'post' === $seen['object_type'] && 'zz' === $seen['extension'] );

// The hexagon's progress arc starts at 12 o'clock (x = 50), not at the left end of the top corner.
$hex_start = explode( ',', explode( ' ', clanbite_avatar_hexagon_points( 43 ) )[0] );
$t->ok( 'hexagon ring starts at the top centre', 50.0 === (float) $hex_start[0] && (float) $hex_start[1] < 12 );

exit( $t->finish() );
