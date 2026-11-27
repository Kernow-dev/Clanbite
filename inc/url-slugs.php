<?php
/**
 * Editable first parts of front-end addresses (players, teams, groups), shared by every area.
 *
 * Each area has its own helpers ({@see clanbite_players_url()}, {@see clanbite_teams_url()} and Social Kit's group helpers).
 * The rules they share live here: which slugs may be chosen, reading a request path, and sending old addresses to new ones.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * The areas whose address slug the site owner can choose.
 *
 * @since 1.9.0
 *
 * @return array<string, array{default: string, post_type: string, current: callable}> Area => its default slug, the content type
 *                                                                                  that uses it (empty for none) and a callback returning the slug in use.
 */
function clanbite_url_slug_areas(): array {
	$areas = array(
		'players' => array(
			'default'   => 'players',
			'post_type' => '',
			'current'   => 'clanbite_players_get_slug',
		),
		'teams'   => array(
			'default'   => 'teams',
			'post_type' => 'clanbite_team',
			'current'   => 'clanbite_teams_get_slug',
		),
	);

	/**
	 * Filter the areas with an editable address slug. An add-on that owns such an area adds it, so no other area can take its slug.
	 *
	 * @since 1.9.0
	 *
	 * @param array<string, array{default: string, post_type: string, current: callable}> $areas Areas.
	 */
	return (array) apply_filters( 'clanbite_url_slug_areas', $areas );
}

/**
 * Reduce a value to lowercase letters, numbers and inner hyphens.
 *
 * @since 1.9.0
 *
 * @param string $slug     Value.
 * @param string $fallback Used when nothing is left.
 * @return string
 */
function clanbite_url_slug_clean( string $slug, string $fallback ): string {
	$slug = trim( (string) preg_replace( '/[^a-z0-9-]+/', '', strtolower( $slug ) ), '-' );

	return '' === $slug ? $fallback : $slug;
}

/**
 * Why a slug cannot be used for an area's addresses.
 *
 * @since 1.9.0
 *
 * @param string $slug Candidate, already lowercased.
 * @param string $area Area that wants it (a key of {@see clanbite_url_slug_areas()}).
 * @return string An explanation for the site owner, or an empty string when the slug is fine.
 */
function clanbite_url_slug_problem( string $slug, string $area ): string {
	if ( 1 !== preg_match( '/^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])?$/', $slug ) || ctype_digit( $slug ) ) {
		return __( 'Use up to 40 lowercase letters, numbers and hyphens, with at least one letter and no hyphen at either end.', 'clanbite' );
	}

	// Addresses WordPress needs for itself.
	$taken = array( 'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'wp-login', 'wp-sitemap', 'admin', 'login', 'register', 'feed', 'rss', 'rss2', 'atom', 'rdf', 'embed', 'trackback', 'comments', 'comment-page', 'page', 'search', 'author', 'attachment', 'type', 'category', 'tag', 'sitemap', 'robots', 'favicon', 'index', 'settings', 'create', 'manage', 'random' );
	if ( in_array( $slug, $taken, true ) ) {
		return __( 'WordPress or Clanbite already uses that address.', 'clanbite' );
	}

	$areas = clanbite_url_slug_areas();
	foreach ( $areas as $key => $other ) {
		if ( $key !== $area && is_callable( $other['current'] ) && call_user_func( $other['current'] ) === $slug ) {
			/* translators: %s: area, for example "teams". */
			return sprintf( __( 'The %s addresses already use that slug.', 'clanbite' ), $key );
		}
	}

	$own_type = (string) ( $areas[ $area ]['post_type'] ?? '' );
	$objects  = array_merge( array_values( get_post_types( array(), 'objects' ) ), array_values( get_taxonomies( array(), 'objects' ) ) );
	foreach ( $objects as $object ) {
		// The area's own content type is what is being renamed, and the other areas were checked above.
		if ( $object instanceof WP_Post_Type && ( $object->name === $own_type || in_array( $object->name, array_filter( array_column( $areas, 'post_type' ) ), true ) ) ) {
			continue;
		}
		$bases = array();
		if ( is_array( $object->rewrite ) && ! empty( $object->rewrite['slug'] ) ) {
			$bases[] = (string) $object->rewrite['slug'];
		}
		if ( ! empty( $object->has_archive ) && is_string( $object->has_archive ) ) {
			$bases[] = $object->has_archive;
		}
		foreach ( $bases as $base ) {
			if ( explode( '/', trim( $base, '/' ) )[0] === $slug ) {
				/* translators: %s: content type name. */
				return sprintf( __( '“%s” already uses that address.', 'clanbite' ), $object->label );
			}
		}
	}
	if ( get_page_by_path( $slug, OBJECT, array( 'page', 'post' ) ) ) {
		return __( 'A page or post already has that address. Rename it first, or choose another slug.', 'clanbite' );
	}

	return '';
}

/**
 * Check a slug submitted on a settings screen.
 *
 * @since 1.9.0
 *
 * @param mixed  $value Submitted value; null when the field was not sent.
 * @param string $saved Value saved now (empty for the default).
 * @param string $area  Area.
 * @return array{value: string, problem: string} What to save, and why the submitted value was refused (empty when it was not).
 */
function clanbite_url_slug_submitted( $value, string $saved, string $area ): array {
	if ( null === $value ) {
		return array(
			'value'   => $saved,
			'problem' => '',
		);
	}
	$default = (string) ( clanbite_url_slug_areas()[ $area ]['default'] ?? $area );
	$slug    = strtolower( trim( sanitize_text_field( (string) $value ), " \t\n/" ) );
	if ( $slug === $saved ) {
		return array(
			'value'   => $saved,
			'problem' => '',
		);
	}
	// An empty field means the default, which still has to be free (another area may have taken it).
	$problem = clanbite_url_slug_problem( '' === $slug ? $default : $slug, $area );
	if ( '' !== $problem ) {
		return array(
			'value'   => $saved,
			/* translators: 1: rejected slug, 2: reason. */
			'problem' => sprintf( __( 'The address slug “%1$s” was not saved. %2$s', 'clanbite' ), '' === $slug ? $default : $slug, $problem ),
		);
	}

	return array(
		'value'   => $slug === $default ? '' : $slug,
		'problem' => '',
	);
}

/**
 * Express a request path with an area's default slug in place of the chosen one, so that route checks can be written one way.
 *
 * With teams renamed to `clans`, `clans/alpha` becomes `teams/alpha`. A path that really starts with the default is then not that
 * area's route, so it is returned with a leading underscore and cannot match one. Other paths are returned unchanged.
 *
 * @since 1.9.0
 *
 * @param string $path     Request path relative to the home URL, without leading slash.
 * @param string $slug     Slug in use.
 * @param string $fallback Default slug.
 * @return string
 */
function clanbite_url_slug_route_path( string $path, string $slug, string $fallback ): string {
	if ( $slug === $fallback ) {
		return $path;
	}
	if ( $path === $slug || str_starts_with( $path, $slug . '/' ) ) {
		return $fallback . substr( $path, strlen( $slug ) );
	}
	if ( $path === $fallback || str_starts_with( $path, $fallback . '/' ) ) {
		return '_' . $path;
	}

	return $path;
}

/**
 * Remember a slug that was replaced, and ask for the permalinks to be rebuilt.
 *
 * @since 1.9.0
 *
 * @param string $area   Area.
 * @param string $before Slug used until now.
 * @param string $after  Slug used from now on.
 * @return void
 */
function clanbite_url_slug_changed( string $area, string $before, string $after ): void {
	if ( $before === $after ) {
		return;
	}
	$option   = 'clanbite_' . sanitize_key( $area ) . '_previous_slugs';
	$previous = (array) get_option( $option, array() );
	$previous = array_values( array_diff( array_unique( array_merge( $previous, array( $before ) ) ), array( $after ) ) );
	update_option( $option, array_slice( $previous, -5 ), false );
	update_option( 'clanbite_pending_rewrite_flush', '1', false );
}

/**
 * On a "not found" request for an address that uses one of an area's old slugs, send the visitor to the same place under the
 * current one. Does nothing otherwise. Call it on `template_redirect`.
 *
 * @since 1.9.0
 *
 * @param string $area    Area.
 * @param string $current Slug in use.
 * @return void
 */
function clanbite_url_slug_redirect_old( string $area, string $current ): void {
	if ( ! is_404() ) {
		return;
	}
	$path = clanbite_get_canonical_request_path();
	foreach ( (array) get_option( 'clanbite_' . sanitize_key( $area ) . '_previous_slugs', array() ) as $slug ) {
		$slug = (string) $slug;
		if ( '' === $slug || $slug === $current || ( $path !== $slug && ! str_starts_with( $path, $slug . '/' ) ) ) {
			continue;
		}
		$rest   = ltrim( substr( $path, strlen( $slug ) ), '/' );
		$target = home_url( '/' . $current . '/' . ( '' === $rest ? '' : trailingslashit( $rest ) ) );
		$query  = (string) wp_parse_url( clanbite_sanitize_request_uri( '' ), PHP_URL_QUERY );
		// Temporary, so that a slug changed back again is not stuck in browsers' caches.
		wp_safe_redirect( '' === $query ? $target : $target . '?' . $query, 302 );
		exit;
	}
}
