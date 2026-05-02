<?php
/**
 * Helpers for Clanbite block-based front templates (FSE + classic themes).
 *
 * Block markup lives in `templates/**.html` for {@see register_block_template()}.
 * Matching `.php` files in the same folder wrap that markup with `get_header()` /
 * `get_footer()` and {@see do_blocks()} so classic themes do not print raw serialised blocks.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load a file of block-serialised markup and echo it through {@see do_blocks()}.
 *
 * @param string $markup_file Absolute path to a readable `.html` (or other) file.
 * @return void
 */
function clanbite_render_block_markup_file( string $markup_file ): void {
	if ( ! is_readable( $markup_file ) ) {
		return;
	}

	$markup = file_get_contents( $markup_file );
	if ( false === $markup || '' === trim( $markup ) ) {
		return;
	}

	/**
	 * Filter serialized block markup before {@see do_blocks()} runs for a Clanbite template file.
	 *
	 * @param string $markup      Raw markup read from `$markup_file`.
	 * @param string $markup_file Absolute path to the template source file.
	 */
	$markup = (string) apply_filters( 'clanbite_block_markup_before_do_blocks', $markup, $markup_file );

	/**
	 * Fires before Clanbite echoes template markup through {@see do_blocks()}.
	 *
	 * @param string $markup_file Absolute path to the template source file.
	 * @param string $markup      Markup after {@see clanbite_block_markup_before_do_blocks}.
	 */
	do_action( 'clanbite_render_block_markup_file', $markup_file, $markup );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo do_blocks( $markup );
}
