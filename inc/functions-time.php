<?php
/**
 * Shared time wording for Clanbite and its extensions.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * A timestamp as relative wording ("5 minutes ago"), the same everywhere Clanbite shows one.
 *
 * Feeds, forums and notifications call this instead of formatting their own, so a site (or a translation) changes the wording in one
 * place with the `clanbite_human_time_ago` filter.
 *
 * @since 1.6.3
 *
 * @param int      $timestamp Unix timestamp of the moment.
 * @param int|null $now       Unix timestamp to measure from. Default: now (`time()`). Pass `current_time( 'timestamp' )` when
 *                            `$timestamp` was built from a site-local date.
 * @return string Text such as "5 minutes ago".
 */
function clanbite_human_time_ago( int $timestamp, ?int $now = null ): string {
	$now = null === $now ? time() : $now;
	/* translators: %s: Human-readable time span, e.g. "5 minutes". */
	$text = sprintf( __( '%s ago', 'clanbite' ), human_time_diff( $timestamp, $now ) );

	/**
	 * Filters relative time wording shown by Clanbite and its extensions.
	 *
	 * @since 1.6.3
	 *
	 * @param string $text      Relative text such as "5 minutes ago".
	 * @param int    $timestamp Unix timestamp of the moment.
	 * @param int    $now       Unix timestamp it was measured from.
	 */
	return (string) apply_filters( 'clanbite_human_time_ago', $text, $timestamp, $now );
}
