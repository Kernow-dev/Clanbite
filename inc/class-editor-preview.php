<?php
/**
 * Block editor previews: lets blocks show their real front-end markup, with demo data where there is none.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite;

defined( 'ABSPATH' ) || exit;

/**
 * Editor preview mode.
 *
 * A block's editor component asks WordPress to render the block on the server with core's `@wordpress/server-side-render`, which calls the
 * block-renderer REST route (`/wp/v2/block-renderer/{block}`). While such a request runs, {@see Editor_Preview::active()} is true and every
 * Clanbite plugin can supply **demo data** so the block looks like the front end even on an empty site: demo profile
 * details, sample posts and so on. Nothing here replaces core: the editor side is `ServerSideRender` inside core's `Disabled`.
 *
 * Rules for extension authors:
 * - Ask `clanbite_is_editor_preview()` (a global function) before substituting demo data, never earlier; it is false on every front-end
 *   and REST request that is not an editor preview of a block.
 * - Only substitute what is missing. Real data wins, so editing a real team or player previews that team or player.
 * - Never write demo data to the database; it exists only for the duration of the preview request.
 * - Provide demo values through the `clanbite_editor_preview_demo` filter so other plugins can read and extend them.
 */
final class Editor_Preview {

	/**
	 * Whether a block preview is being rendered.
	 *
	 * @var bool
	 */
	private static bool $active = false;

	/**
	 * Guards the demo-meta filter against recursion.
	 *
	 * @var bool
	 */
	private static bool $reading = false;

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'rest_pre_dispatch', array( self::class, 'maybe_start' ), 1, 3 );
		add_filter( 'rest_post_dispatch', array( self::class, 'stop' ), 9999 );
	}

	/**
	 * Whether a block preview is being rendered right now.
	 *
	 * @return bool
	 */
	public static function active(): bool {
		return self::$active;
	}

	/**
	 * Start preview mode for a block-renderer request that asks for it.
	 *
	 * @param mixed            $result  Pre-dispatch result (passed through).
	 * @param \WP_REST_Server  $server  Server.
	 * @param \WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function maybe_start( $result, $server, $request ) {
		unset( $server );
		if ( $request instanceof \WP_REST_Request
			&& 0 === strpos( $request->get_route(), '/wp/v2/block-renderer/' )
			&& 'edit' === (string) $request->get_param( 'context' )
			&& current_user_can( 'edit_posts' ) ) {
			self::$active = true;
			add_filter( 'get_user_metadata', array( self::class, 'demo_user_meta' ), 10, 4 );

			/**
			 * Fires when a block preview starts rendering. Plugins can add their own demo-data filters here; they are removed with
			 * {@see 'clanbite_editor_preview_end'}.
			 */
			do_action( 'clanbite_editor_preview_start' );
		}

		return $result;
	}

	/**
	 * End preview mode.
	 *
	 * @param mixed $response Response (passed through).
	 * @return mixed
	 */
	public static function stop( $response ) {
		if ( self::$active ) {
			self::$active = false;
			remove_filter( 'get_user_metadata', array( self::class, 'demo_user_meta' ), 10 );

			/**
			 * Fires when a block preview finished rendering.
			 */
			do_action( 'clanbite_editor_preview_end' );
		}

		return $response;
	}

	/**
	 * Demo values by kind.
	 *
	 * Kinds: `player` (profile details), `events` (list rows), plus whatever other plugins add.
	 *
	 * @param string $kind Kind of demo data.
	 * @return array<string, mixed>
	 */
	public static function demo( string $kind ): array {
		$all = array(
			'events' => array(
				array(
					'title'     => __( 'Weekly scrims', 'clanbite' ),
					'permalink' => '#',
					'startsAt'  => gmdate( 'Y-m-d H:i:s', strtotime( 'next tuesday 19:00' ) ),
				),
				array(
					'title'     => __( 'Community game night', 'clanbite' ),
					'permalink' => '#',
					'startsAt'  => gmdate( 'Y-m-d H:i:s', strtotime( 'next friday 20:00' ) ),
				),
				array(
					'title'     => __( 'Regional qualifier', 'clanbite' ),
					'permalink' => '#',
					'startsAt'  => gmdate( 'Y-m-d H:i:s', strtotime( '+3 weeks saturday 14:00' ) ),
				),
			),
			'player' => array(
				'clanbite_player_tagline'          => __( 'Always up for one more round', 'clanbite' ),
				'clanbite_player_bio'              => __( 'Competitive player and community regular. Plays to win, stays for the people. Looking for a team that takes practice as seriously as the finals.', 'clanbite' ),
				'clanbite_player_website'          => 'https://example.com',
				'clanbite_player_city'             => 'Plymouth',
				'clanbite_player_country'          => 'GB',
				'clanbite_player_birthday'         => '1994-06-18',
				'clanbite_player_social_facebook'  => 'https://facebook.com/example',
				'clanbite_player_social_x'         => 'https://x.com/example',
				'clanbite_player_social_instagram' => 'https://instagram.com/example',
				'clanbite_player_social_youtube'   => 'https://youtube.com/@example',
			),
		);

		/**
		 * Filter the demo data the editor previews use.
		 *
		 * @param array<string, array<string, mixed>> $all Demo values keyed by kind (`player`, `team`, …).
		 */
		$all = (array) apply_filters( 'clanbite_editor_preview_demo', $all );

		return isset( $all[ $kind ] ) && is_array( $all[ $kind ] ) ? $all[ $kind ] : array();
	}

	/**
	 * Fill empty profile details with demo values while a preview renders.
	 *
	 * @param mixed  $value     Short-circuit value (null to continue).
	 * @param int    $object_id User id.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether a single value is wanted.
	 * @return mixed
	 */
	public static function demo_user_meta( $value, $object_id, $meta_key, $single ) {
		if ( null !== $value || self::$reading || ! $single || 0 !== strpos( (string) $meta_key, 'clanbite_player_' ) ) {
			return $value;
		}
		$demo = self::demo( 'player' );
		if ( ! isset( $demo[ $meta_key ] ) ) {
			return $value;
		}
		self::$reading = true;
		$real          = get_user_meta( (int) $object_id, (string) $meta_key, true );
		self::$reading = false;

		return '' === (string) $real ? $demo[ $meta_key ] : $value;
	}

	/**
	 * The team to preview: the first published team, or 0 when the site has none (the block then shows its own empty state).
	 *
	 * @return int Team post id.
	 */
	public static function team_id(): int {
		if ( ! post_type_exists( 'clanbite_team' ) ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'clanbite_team',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		return array() !== $ids ? (int) $ids[0] : 0;
	}
}
