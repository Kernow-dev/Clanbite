/**
 * Import-map anchor for the Interactivity API (script module `clanbite-interactivity-bootstrap`).
 *
 * WordPress only adds `@wordpress/interactivity` to the import map printed in <head> when an enqueued module declares it
 * as a dependency. Clanbite enqueues this empty module early on routes that render blocks from a plugin PHP template,
 * so blocks that enqueue their own view modules later can still resolve the bare specifier.
 */
import '@wordpress/interactivity';
