<?php
/**
 * Theme- and add-on-friendly helpers for the Clanbite word filter.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Wordban;

/**
 * Whether the site word filter is enabled (Clanbite → Settings → General).
 *
 * @return bool
 */
function clanbite_wordban_enabled(): bool {
	return Wordban::is_enabled();
}

/**
 * If the text contains a blocked word, return an error; otherwise null.
 *
 * @param string $text Text to validate (team name, username, etc.).
 * @return \WP_Error|null
 */
function clanbite_wordban_validate_strict_text( string $text ): ?WP_Error {
	return Wordban::validate_strict_text( $text );
}

/**
 * Mask banned words in plain text (first character kept, remainder asterisks).
 *
 * @param string $text Input.
 * @return string
 */
function clanbite_wordban_mask_plain_text( string $text ): string {
	return Wordban::mask_plain_text( $text );
}

/**
 * Mask banned words outside HTML tags.
 *
 * @param string $html HTML fragment.
 * @return string
 */
function clanbite_wordban_mask_html_content( string $html ): string {
	return Wordban::mask_html_content( $html );
}
