<?php
/**
 * Applies match visibility to WordPress' own ways of reading a post.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Extensions\Matches;

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Extensions\Matches;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;

/**
 * Matches are a public post type, so WordPress would show every published one on its web page, in archives, feeds,
 * embeds, sitemaps, search and `wp/v2` REST, whatever its visibility. This closes each of those with the same rule the
 * Clanbite routes and blocks use ({@see Matches::viewer_can_see_match()}). People who can edit other users' posts see
 * everything.
 */
final class Match_Privacy {

	/**
	 * Matches extension.
	 *
	 * @var Matches
	 */
	private Matches $matches;

	/**
	 * Hidden match ids per viewer, for this request.
	 *
	 * @var array<int, int[]>
	 */
	private array $hidden = array();

	/**
	 * Constructor.
	 *
	 * @param Matches $matches Matches extension.
	 */
	public function __construct( Matches $matches ) {
		$this->matches = $matches;
	}

	/**
	 * Hook every public way in.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'rest_dispatch_request', array( $this, 'guard_rest_item' ), 10, 2 );
		add_filter( 'rest_clanbite_match_query', array( $this, 'filter_rest_query_args' ) );
		add_filter( 'rest_post_search_query', array( $this, 'filter_rest_query_args' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_front_queries' ) );
		add_action( 'template_redirect', array( $this, 'guard_single_page' ), 0 );
		add_filter( 'oembed_response_data', array( $this, 'filter_oembed' ), 10, 2 );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'filter_sitemap_args' ), 10, 2 );
	}

	/**
	 * Whether the current visitor may see a match.
	 *
	 * @param WP_Post $post Match post.
	 * @return bool
	 */
	private function can_see( WP_Post $post ): bool {
		return current_user_can( 'edit_others_posts' ) || $this->matches->viewer_can_see_match( $post, get_current_user_id() );
	}

	/**
	 * Published matches the current visitor may not see. Only matches that are not public need checking.
	 *
	 * @return int[]
	 */
	private function get_hidden_ids(): array {
		$viewer = get_current_user_id();
		if ( isset( $this->hidden[ $viewer ] ) ) {
			return $this->hidden[ $viewer ];
		}
		$this->hidden[ $viewer ] = array();
		if ( current_user_can( 'edit_others_posts' ) ) {
			return array();
		}

		// Keep get_posts(): it skips query filters by default, so another plugin's filter cannot drop a match from this
		// list and leave it visible.
		// phpcs:disable WordPress.DB.SlowDBQuery -- Restricted matches are found by their visibility meta.
		$posts = get_posts(
			array(
				'post_type'      => 'clanbite_match',
				'post_status'    => 'publish',
				'posts_per_page' => 2000,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => 'clanbite_match_visibility',
						'value'   => array( '', Matches::VISIBILITY_PUBLIC ),
						'compare' => 'NOT IN',
					),
				),
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery

		foreach ( $posts as $post ) {
			if ( ! $this->matches->viewer_can_see_match( $post, $viewer ) ) {
				$this->hidden[ $viewer ][] = (int) $post->ID;
			}
		}

		return $this->hidden[ $viewer ];
	}

	/**
	 * Add the hidden matches to a query's exclusions. WordPress ignores `post__not_in` when `post__in` is set, so that
	 * list is narrowed as well.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<string, mixed>
	 */
	private function exclude_hidden( array $args ): array {
		$hidden = $this->get_hidden_ids();
		if ( array() === $hidden ) {
			return $args;
		}

		$args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), $hidden ) ) );
		$wanted               = array_map( 'intval', array_filter( (array) ( $args['post__in'] ?? array() ) ) );
		if ( array() !== $wanted ) {
			$left             = array_values( array_diff( $wanted, $hidden ) );
			$args['post__in'] = array() === $left ? array( 0 ) : $left;
		}

		return $args;
	}

	/**
	 * `wp/v2` match lists and `wp/v2/search`.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<string, mixed>
	 */
	public function filter_rest_query_args( $args ) {
		return $this->exclude_hidden( (array) $args );
	}

	/**
	 * A single match through `wp/v2`, including its sub-routes.
	 *
	 * @param mixed           $result  Pre-dispatch result.
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function guard_rest_item( $result, $request ) {
		$object = get_post_type_object( 'clanbite_match' );
		$base   = $object && $object->rest_base ? (string) $object->rest_base : 'clanbite_match';
		if ( 1 !== preg_match( '#^/wp/v2/' . preg_quote( $base, '#' ) . '/(\d+)(?:/|$)#i', (string) $request->get_route(), $m ) ) {
			return $result;
		}

		$post = get_post( (int) $m[1] );
		if ( ! $post instanceof WP_Post || 'clanbite_match' !== $post->post_type || $this->can_see( $post ) ) {
			return $result;
		}

		return new WP_Error( 'clanbite_match_forbidden', __( 'You cannot view this match.', 'clanbite' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Front-end archives, search results and feeds.
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public function filter_front_queries( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
			return;
		}

		$types = (array) $query->get( 'post_type' );
		if ( ! $query->is_search() && ! $query->is_feed() && ! in_array( 'clanbite_match', $types, true ) ) {
			return;
		}

		$args = $this->exclude_hidden(
			array(
				'post__in'     => $query->get( 'post__in' ),
				'post__not_in' => $query->get( 'post__not_in' ),
			)
		);
		foreach ( $args as $key => $value ) {
			if ( ! empty( $value ) ) {
				$query->set( $key, $value );
			}
		}
	}

	/**
	 * The match's own page (and its feed and embed) is "not found" for people who may not see it.
	 *
	 * @return void
	 */
	public function guard_single_page(): void {
		if ( ! is_singular( 'clanbite_match' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || $this->can_see( $post ) ) {
			return;
		}

		global $wp_query;
		$wp_query->set_404();
		$wp_query->posts      = array();
		$wp_query->post       = null;
		$wp_query->post_count = 0;
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Link previews (oEmbed) are fetched without a signed-in visitor, so only public matches get one.
	 *
	 * @param array<string, mixed>|false $data Response data.
	 * @param WP_Post                    $post Post.
	 * @return array<string, mixed>|false
	 */
	public function filter_oembed( $data, $post ) {
		if ( $post instanceof WP_Post && 'clanbite_match' === $post->post_type && ! $this->matches->viewer_can_see_match( $post, 0 ) ) {
			return false;
		}

		return $data;
	}

	/**
	 * Keep restricted matches out of XML sitemaps.
	 *
	 * @param array<string, mixed> $args      Query arguments.
	 * @param string               $post_type Post type of the sitemap.
	 * @return array<string, mixed>
	 */
	public function filter_sitemap_args( $args, $post_type ) {
		return 'clanbite_match' === $post_type ? $this->exclude_hidden( (array) $args ) : $args;
	}
}
