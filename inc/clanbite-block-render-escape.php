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
 * Global HTML attributes aligned with WordPress core post-tag behaviour
 * ({@see _wp_add_global_attributes()} in `wp-includes/kses.php`), kept locally so Clanbite does not
 * depend on Core’s underscore-prefixed helper.
 *
 * @return array<string, bool>
 */
function clanbite_block_fragment_global_attributes(): array {
	return array(
		'aria-controls'    => true,
		'aria-current'     => true,
		'aria-describedby' => true,
		'aria-details'     => true,
		'aria-expanded'    => true,
		'aria-hidden'      => true,
		'aria-label'       => true,
		'aria-labelledby'  => true,
		'aria-live'        => true,
		'class'            => true,
		'data-*'           => true,
		'dir'              => true,
		'hidden'           => true,
		'id'               => true,
		'lang'             => true,
		'style'            => true,
		'title'            => true,
		'role'             => true,
		'xml:lang'         => true,
	);
}

/**
 * Merge tag-specific allow-list entries with {@see clanbite_block_fragment_global_attributes()},
 * matching Core precedence: `array_merge( $specific, $globals )`.
 *
 * @param array<string, bool> $specific Allowed attributes for one tag.
 * @return array<string, bool>
 */
function clanbite_block_fragment_merge_global_attrs( array $specific ): array {
	return array_merge( $specific, clanbite_block_fragment_global_attributes() );
}

/**
 * Allowed HTML tags for block fragments passed through {@see wp_kses()} /
 * {@see clanbite_esc_block_fragment_html()} (post-like rules plus SVG primitives).
 *
 * @return array<string, array<string, bool>>
 */
function clanbite_block_fragment_allowed_html(): array {
	static $base_allowed = null;
	if ( null === $base_allowed ) {
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

		$base_allowed = $allowed;

		/*
		 * Front-end blocks (player settings, etc.) emit native form controls. Core `post` KSES allows
		 * `textarea` but omits `input`, `select`, and `option`, so wp_kses would strip fields entirely.
		 */
		$base_allowed['input'] = clanbite_block_fragment_merge_global_attrs(
			array(
				'type'             => true,
				'name'             => true,
				'value'            => true,
				'placeholder'      => true,
				'checked'          => true,
				'disabled'         => true,
				'readonly'         => true,
				'required'         => true,
				'maxlength'        => true,
				'minlength'        => true,
				'min'              => true,
				'max'              => true,
				'step'             => true,
				'pattern'          => true,
				'accept'           => true,
				'multiple'         => true,
				'autocomplete'     => true,
				'size'             => true,
				'inputmode'        => true,
				'list'             => true,
				'tabindex'         => true,
				'aria-valuenow'    => true,
				'aria-valuemin'    => true,
				'aria-valuemax'    => true,
			)
		);

		$base_allowed['select'] = clanbite_block_fragment_merge_global_attrs(
			array(
				'name'         => true,
				'autocomplete' => true,
				'required'     => true,
				'multiple'     => true,
				'size'         => true,
				'tabindex'     => true,
			)
		);

		$base_allowed['option'] = clanbite_block_fragment_merge_global_attrs(
			array(
				'value'    => true,
				'selected' => true,
				'disabled' => true,
			)
		);

		if ( isset( $base_allowed['textarea'] ) && is_array( $base_allowed['textarea'] ) ) {
			$base_allowed['textarea']['placeholder'] = true;
			$base_allowed['textarea']['maxlength']   = true;
		}
	}

	/**
	 * Filter: `clanbite_block_fragment_allowed_html` — extend safe tags/attributes for {@see wp_kses()} on block fragments.
	 *
	 * Do not mutate the passed array in place; return an altered copy when adding keys.
	 *
	 * @param array<string, array<string, bool>> $allowed Base allow-list (post rules plus Clanbite SVG tags).
	 */
	return (array) apply_filters( 'clanbite_block_fragment_allowed_html', $base_allowed );
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
