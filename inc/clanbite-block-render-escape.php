<?php
/**
 * Late escaping helpers for dynamic block / template HTML assembled in render callbacks.
 *
 * Prefer escaping **at output time** (“escape late”), after trusted helpers build markup.
 * Wrapper attribute blobs from {@see get_block_wrapper_attributes()} are escaped by core when
 * assembled; wrap **whole fragments** that include those blobs rather than passing attributes alone.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allowed HTML tags for block fragments passed through {@see wp_kses()} /
 * {@see clanbite_esc_block_fragment_html()} (post-like rules plus SVG primitives).
 *
 * @return array<string, array<string, bool>>
 */
function clanbite_block_fragment_allowed_html(): array {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}

	$allowed = wp_kses_allowed_html( 'post' );

	// Inline SVG used by Clanbite blocks (caret icons, social glyphs). Core post lists omit svg/path.
	$svg_shared = array(
		'class'       => true,
		'aria-hidden' => true,
		'aria-label'  => true,
		'role'        => true,
		'id'          => true,
		'style'       => true,
		'title'       => true,
		'lang'        => true,
		'dir'         => true,
		'focusable'   => true,
		'data-*'      => true,
	);

	if ( empty( $allowed['svg'] ) ) {
		$allowed['svg'] = array_merge(
			array(
				'xmlns'   => true,
				'viewbox' => true,
				'width'   => true,
				'height'  => true,
				'fill'    => true,
				'stroke'  => true,
				'version' => true,
			),
			$svg_shared
		);
	}

	if ( empty( $allowed['path'] ) ) {
		$allowed['path'] = array_merge(
			array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'opacity'         => true,
				'fill-rule'       => true,
				'clip-rule'       => true,
				'transform'       => true,
			),
			$svg_shared
		);
	}

	$cached = $allowed;

	return $cached;
}

/**
 * Escape an HTML fragment built from block markup / helpers for safe storage or composition.
 *
 * Block `render.php` files echo via {@see wp_kses()} directly so static analysis recognizes escaping.
 *
 * @param string $html Assembled markup (balanced fragment recommended).
 * @return string
 */
function clanbite_esc_block_fragment_html( string $html ): string {
	return wp_kses( $html, clanbite_block_fragment_allowed_html() );
}
