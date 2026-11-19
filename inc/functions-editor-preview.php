<?php
/**
 * Global helper for block editor previews.
 *
 * Lives in its own un-namespaced file: a function declared inside a namespaced file is not global, so `function_exists( 'clanbite_is_editor_preview' )`
 * could never find it.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clanbite_is_editor_preview' ) ) {
	/**
	 * Whether a block is being rendered for an editor preview (see {@see \Kernowdev\Clanbite\Editor_Preview}).
	 *
	 * Gate demo data on this; it is false for visitors and for every request that is not the block editor's server render of a block.
	 *
	 * @return bool
	 */
	function clanbite_is_editor_preview(): bool {
		return \Kernowdev\Clanbite\Editor_Preview::active();
	}
}
