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

	// Shortcode blocks (e.g. the players directory) are not expanded by do_blocks(); block templates run do_shortcode() too.
	clanbite_echo_block_fragment_html( do_shortcode( do_blocks( $markup ) ) );
}

/**
 * Resolve the template for a Clanbite route: a theme PHP file, then the block template, then the plugin's PHP loader.
 *
 * Only a real theme template may be handed to {@see locate_block_template()}. Passing the plugin's own PHP loader
 * makes WordPress treat it as a theme template that wins over the block template, so block themes would fall back to
 * the classic loader and blocks would render after `wp_head` (no import map, broken Interactivity API modules).
 *
 * @param string   $theme_template  Result of `locate_template()` (empty when the theme has no matching PHP file).
 * @param string   $plugin_template Absolute path of the plugin's classic PHP loader used as the last resort.
 * @param string   $slug            Block template slug registered with `register_block_template()` (id segment after `//`).
 * @param string[] $hierarchy       Template file hierarchy for the route (for example `array( 'teams-create.php', 'index.php' )`).
 * @return string Template path to hand back to `template_include`.
 */
function clanbite_locate_block_or_plugin_template( string $theme_template, string $plugin_template, string $slug, array $hierarchy ): string {
	$resolved = function_exists( 'locate_block_template' ) ? locate_block_template( $theme_template, $slug, $hierarchy ) : $theme_template;

	if ( ! $resolved ) {
		$resolved = '' !== $theme_template ? $theme_template : $plugin_template;
	}

	return (string) $resolved;
}
