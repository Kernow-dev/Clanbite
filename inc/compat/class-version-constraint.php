<?php
/**
 * Version constraints (`^1.5.0`, `>=1.2 <3`).
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Compat;

defined( 'ABSPATH' ) || exit;

/**
 * The small constraint language used in plugin headers (`Requires Clanbite: ^1.5.0`):
 *
 *  - `^X.Y.Z`: this version or any later one in the same major (`^0.Y.Z` stays in the same minor, `^0.0.Z` is exact). A bare
 *    `X.Y.Z` means the same as `^X.Y.Z`: Clanbite promises backward compatibility within a major version.
 *  - Comparators `>=`, `>`, `<=`, `<`, `=`, separated by spaces, all of which must hold: `>=1.2 <3`.
 *  - `*` accepts any version.
 *
 * Upper bounds ignore a pre-release suffix on the checked version, so `2.0.0-beta.1` is **not** inside `<2.0.0`. The same logic lives in
 * the Add-on Manager and the add-on server: keep all three identical.
 */
final class Version_Constraint {

	private const VERSION = '\d+(?:\.\d+){0,3}(?:[-+][0-9A-Za-z.\-]+)?';

	/**
	 * Parse an expression into comparators.
	 *
	 * @param string $expr Constraint.
	 * @return list<array{0: string, 1: string}>|null List of `[operator, version]`, or null when the expression is invalid. An empty list means "any".
	 */
	public static function parse( string $expr ): ?array {
		$expr = trim( $expr );
		if ( '' === $expr || strlen( $expr ) > 100 ) {
			return null;
		}
		if ( '*' === $expr ) {
			return array();
		}
		$out = array();
		foreach ( (array) preg_split( '/\s+/', $expr ) as $token ) {
			if ( ! preg_match( '/^(\^|>=|<=|==|>|<|=)?(' . self::VERSION . ')$/', $token, $m ) ) {
				return null;
			}
			$op      = '' === $m[1] ? '^' : $m[1];
			$version = $m[2];
			if ( '^' === $op ) {
				$parts = array_map( 'intval', explode( '.', preg_replace( '/[-+].*$/', '', $version ) ?? $version ) );
				$parts = array_pad( $parts, 3, 0 );
				if ( $parts[0] > 0 ) {
					$upper = ( $parts[0] + 1 ) . '.0.0';
				} elseif ( $parts[1] > 0 ) {
					$upper = '0.' . ( $parts[1] + 1 ) . '.0';
				} else {
					$upper = '0.0.' . ( $parts[2] + 1 );
				}
				$out[] = array( '>=', $version );
				$out[] = array( '<', $upper );
			} else {
				$out[] = array( '==' === $op ? '=' : $op, $version );
			}
		}

		return $out;
	}

	/**
	 * Whether an expression is syntactically valid.
	 *
	 * @param string $expr Constraint.
	 * @return bool
	 */
	public static function is_valid( string $expr ): bool {
		return null !== self::parse( $expr );
	}

	/**
	 * Whether a version satisfies a constraint.
	 *
	 * @param string $version Version to test (for example `1.6.0` or `1.6.0-beta.1`).
	 * @param string $expr    Constraint.
	 * @return bool False for an invalid constraint or an unusable version.
	 */
	public static function satisfies( string $version, string $expr ): bool {
		$rules   = self::parse( $expr );
		$version = trim( $version );
		if ( null === $rules || ! preg_match( '/^' . self::VERSION . '$/', $version ) ) {
			return false;
		}
		$base = preg_replace( '/[-+].*$/', '', $version ) ?? $version;
		foreach ( $rules as [ $op, $bound ] ) {
			$ok = match ( $op ) {
				'>='    => version_compare( $version, $bound, '>=' ),
				'>'     => version_compare( $version, $bound, '>' ),
				'<='    => version_compare( $base, $bound, '<=' ),
				'<'     => version_compare( $base, $bound, '<' ),
				default => version_compare( $version, $bound, '==' ),
			};
			if ( ! $ok ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Parse the `Requires Clanbite Add-ons` header: `clanbite-points ^1.3, clanbite-forums >=1.2 <3`.
	 *
	 * @param string $header Header value.
	 * @return array<string, string>|null Slug => constraint, or null when any entry is invalid.
	 */
	public static function parse_addons( string $header ): ?array {
		$out = array();
		foreach ( explode( ',', $header ) as $entry ) {
			$entry = trim( $entry );
			if ( '' === $entry ) {
				continue;
			}
			if ( ! preg_match( '/^([a-z0-9_-]+)\s+(.+)$/', $entry, $m ) || ! self::is_valid( $m[2] ) ) {
				return null;
			}
			$out[ $m[1] ] = trim( $m[2] );
		}

		return $out;
	}
}
