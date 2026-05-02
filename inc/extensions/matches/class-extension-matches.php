<?php
/**
 * Matches extension: schedule and track matches between teams.
 *
 * Depends on Teams (`cp_teams`) via {@see Skeleton::$requires} only; this is a top-level
 * extension (`parent_slug` empty). Blocks are registered from the root `build/matches` metadata collection.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Extensions;
defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Extensions\Abstract_Settings;
use Kernowdev\Clanbite\Extensions\Matches\Admin as Matches_Settings_Admin;
use Kernowdev\Clanbite\Extensions\Matches\Rest_Controller;
use WP_Post;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/class-extension-matches-admin.php';
require_once __DIR__ . '/class-matches-rest.php';

/**
 * Registers the `cp_match` post type, REST routes, block libraries, and editor meta UI hooks.
 */
class Matches extends Skeleton {

	public const STATUS_SCHEDULED = 'scheduled';
	public const STATUS_LIVE      = 'live';
	public const STATUS_FINISHED  = 'finished';
	public const STATUS_CANCELLED = 'cancelled';

	/**
	 * Visibility levels for matches (and match-backed schedule events).
	 */
	public const VISIBILITY_PUBLIC       = 'public';
	public const VISIBILITY_MEMBERS      = 'members';
	public const VISIBILITY_TEAM_MEMBERS = 'team_members';
	public const VISIBILITY_TEAM_ADMINS  = 'team_admins';

	/**
	 * Option-backed settings surfaced in the unified Clanbite React admin.
	 *
	 * @var Matches_Settings_Admin
	 */
	protected Matches_Settings_Admin $admin;

	/**
	 * REST controller for public match queries.
	 *
	 * @var Rest_Controller
	 */
	protected Rest_Controller $matches_rest;

	/**
	 * Construct extension metadata and register on the official extensions filter.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Matches', 'clanbite' ),
			'cp_matches',
			__( 'Register matches between teams, track status and scores, and display them with blocks or REST.', 'clanbite' ),
			'',
			'1.0.0',
			array( 'cp_teams' )
		);
	}

	/**
	 * Register as a first-party (whitelisted) extension instead of a third-party filter entry.
	 *
	 * @param string $name        Human-readable name.
	 * @param string $slug        Unique slug.
	 * @param string $description Short description.
	 * @param string $parent_slug Parent extension slug, or empty string for a root extension.
	 * @param string $version              Semantic version `x.y.z`.
	 * @param array  $requires             Required extension slugs.
	 * @param string $requires_clanbite  Minimum Clanbite core version (`x.y.z`).
	 * @return void
	 */
	public function setup_extension(
		string $name,
		string $slug,
		string $description,
		string $parent_slug,
		string $version,
		array $requires,
		string $requires_clanbite = ''
	): void {
		parent::setup_extension(
			$name,
			$slug,
			$description,
			$parent_slug,
			$version,
			$requires,
			$requires_clanbite
		);

		remove_filter( 'clanbite_registered_extensions', array( $this, 'register_extension' ) );
		add_filter( 'clanbite_official_registered_extensions', array( $this, 'register_extension' ) );
	}

	/**
	 * Ensure rewrite rules exist after the extension is first enabled.
	 *
	 * @return void
	 */
	public function run_installer(): void {
		flush_rewrite_rules( false );
	}

	/**
	 * Remove all match posts and flush rewrites when the extension is disabled.
	 *
	 * @return void
	 */
	public function run_uninstaller(): void {
		$ids = get_posts(
			array(
				'post_type'      => 'clanbite_match',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
		flush_rewrite_rules( false );
	}

	/**
	 * Run migrations when the bundled extension version increases.
	 *
	 * @return void
	 */
	public function run_updater(): void {
	}

	/**
	 * Wire WordPress hooks for CPT, meta, blocks, REST, admin list columns, and editor assets.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->admin          = new Matches_Settings_Admin();
		$this->matches_rest = new Rest_Controller( $this );

		add_action( 'init', array( $this, 'register_match_post_type' ), 11 );
		add_action( 'init', array( $this, 'register_match_meta' ), 11 );
		add_action( 'init', array( $this, 'register_match_block_libraries' ), 11 );
		add_action( 'rest_api_init', array( $this->matches_rest, 'register_routes' ) );
		add_filter( 'clanbite_event_can_view_attendees', array( $this, 'events_can_view_match_attendees' ), 10, 4 );
		add_filter( 'clanbite_team_front_action_rewrite_slugs', array( $this, 'filter_team_front_action_matches_slug' ), 20, 2 );
		add_action( 'init', array( $this, 'register_team_matches_subpage' ), 15 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_match_editor' ) );
		add_filter( 'manage_cp_match_posts_columns', array( $this, 'match_admin_columns' ) );
		add_action( 'manage_cp_match_posts_custom_column', array( $this, 'render_match_admin_column' ), 10, 2 );
		add_action( 'save_post_clanbite_match', array( $this, 'validate_match_on_save' ), 10, 2 );
		add_filter( 'single_template', array( $this, 'maybe_single_match_template' ) );
		add_filter( 'clanbite_team_create_form_steps', array( $this, 'register_team_create_matches_step' ), 25 );
	}

	/**
	 * Allow team members/admins to view match attendee lists when hidden.
	 *
	 * @param bool   $can_view_attendees Whether attendees are visible per settings.
	 * @param string $event_type         Event type.
	 * @param int    $event_id           Event ID.
	 * @param int    $viewer_id          Viewer user ID (0 for anon).
	 * @return bool
	 */
	public function events_can_view_match_attendees( bool $can_view_attendees, string $event_type, int $event_id, int $viewer_id ): bool {
		if ( 'match' !== $event_type ) {
			return $can_view_attendees;
		}
		if ( $can_view_attendees ) {
			return true;
		}
		if ( $viewer_id <= 0 ) {
			return false;
		}

		$post = get_post( $event_id );
		if ( ! ( $post instanceof \WP_Post ) || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( $this->viewer_is_team_admin( $post, $viewer_id ) ) {
			return true;
		}

		return $this->viewer_is_team_member( $post, $viewer_id );
	}

	/**
	 * Append the Matches step to the front-end team registration wizard when this extension is active.
	 *
	 * @param array<string, array<string, string>> $steps Step map.
	 * @return array<string, array<string, string>>
	 */
	public function register_team_create_matches_step( $steps ): array {
		if ( ! is_array( $steps ) ) {
			return array();
		}

		$steps['matches'] = array(
			'label'       => __( 'Matches', 'clanbite' ),
			'title'       => __( 'Matches', 'clanbite' ),
			'description' => __( 'Control whether other teams can challenge yours.', 'clanbite' ),
		);

		return $steps;
	}

	/**
	 * Settings handler for the unified React admin (own tab: root extension).
	 *
	 * @return Abstract_Settings|null
	 */
	public function get_settings_admin(): ?Abstract_Settings {
		return isset( $this->admin ) ? $this->admin : null;
	}

	/**
	 * Add the `matches` segment to team directory rewrites when the team Matches subpage is enabled.
	 *
	 * @param array<string, string>                $actions Slug => label.
	 * @param \Kernowdev\Clanbite\Extensions\Teams $teams   Teams extension instance.
	 * @return array<string, string>
	 */
	public function filter_team_front_action_matches_slug( array $actions, $teams ): array {
		unset( $teams );
		if ( ! function_exists( 'clanbite_matches_subpage_team_enabled' ) || ! clanbite_matches_subpage_team_enabled() ) {
			return $actions;
		}

		$actions['matches'] = __( 'Matches', 'clanbite' );

		return $actions;
	}

	/**
	 * Register the team profile Matches tab when Teams uses directory URLs.
	 *
	 * @return void
	 */
	public function register_team_matches_subpage(): void {
		if ( ! function_exists( 'clanbite_register_team_subpage' ) ) {
			return;
		}

		if ( ! function_exists( 'clanbite_teams_get_team_mode' ) ) {
			return;
		}

		if ( 'team_directories' !== clanbite_teams_get_team_mode() ) {
			return;
		}

		if ( ! function_exists( 'clanbite_matches_subpage_team_enabled' ) || ! clanbite_matches_subpage_team_enabled() ) {
			return;
		}

		clanbite_register_team_subpage(
			'matches',
			array(
				'label'    => __( 'Matches', 'clanbite' ),
				'position' => 18,
			)
		);
	}

	/**
	 * Shape a match post for REST consumers and internal use.
	 *
	 * @param WP_Post $post Match post object.
	 * @return array<string, mixed>
	 */
	public function match_to_rest_array( WP_Post $post ): array {
		$home_id = (int) get_post_meta( $post->ID, 'cp_match_home_team_id', true );
		$away_id = (int) get_post_meta( $post->ID, 'cp_match_away_team_id', true );
		$fmt     = $this->admin->get( 'datetime_format', 'M j, Y g:i a' );
		$visibility = (string) get_post_meta( $post->ID, 'cp_match_visibility', true );

		$away_title = function_exists( 'clanbite_matches_resolve_away_team_title' )
			? clanbite_matches_resolve_away_team_title( $post->ID )
			: clanbite_matches_team_title( $away_id );

		$away_ext = array(
			'label'       => (string) get_post_meta( $post->ID, 'cp_match_away_external_label', true ),
			'logoUrl'     => (string) get_post_meta( $post->ID, 'cp_match_away_external_logo_url', true ),
			'profileUrl'  => (string) get_post_meta( $post->ID, 'cp_match_away_external_profile_url', true ),
		);
		if ( $away_id > 0 ) {
			$away_ext = array(
				'label'      => '',
				'logoUrl'    => '',
				'profileUrl' => '',
			);
		}

		return array(
			'id'             => $post->ID,
			'title'          => get_the_title( $post ),
			'slug'           => $post->post_name,
			'link'           => get_permalink( $post ),
			'status'         => (string) get_post_meta( $post->ID, 'cp_match_status', true ),
			'scheduledAt'    => (string) get_post_meta( $post->ID, 'cp_match_scheduled_at', true ),
			'scheduledLabel' => clanbite_matches_format_datetime_local(
				(string) get_post_meta( $post->ID, 'cp_match_scheduled_at', true ),
				(string) $fmt
			),
			'homeTeamId'     => $home_id,
			'awayTeamId'     => $away_id,
			'homeTeamTitle'  => clanbite_matches_team_title( $home_id ),
			'awayTeamTitle'  => $away_title,
			'awayExternal'   => $away_ext,
			'homeScore'      => (int) get_post_meta( $post->ID, 'cp_match_home_score', true ),
			'awayScore'      => (int) get_post_meta( $post->ID, 'cp_match_away_score', true ),
			'venue'          => (string) get_post_meta( $post->ID, 'cp_match_venue', true ),
			'visibility'     => $this->sanitize_match_visibility( $visibility ),
			'postStatus'     => $post->post_status,
		);
	}

	/**
	 * Whether the current viewer may see a match.
	 *
	 * @param WP_Post $post Match post.
	 * @param int     $viewer_id Viewer user ID (0 for guests).
	 * @return bool
	 */
	public function viewer_can_see_match( WP_Post $post, int $viewer_id = 0 ): bool {
		$can = $this->resolve_viewer_can_see_match( $post, $viewer_id );

		/**
		 * Filter whether a viewer may see a match (REST, blocks, templates).
		 *
		 * @param bool    $can       Default decision from Clanbite visibility rules.
		 * @param WP_Post $post      Match post.
		 * @param int     $viewer_id Viewer user ID (0 for guests).
		 */
		return (bool) apply_filters( 'clanbite_viewer_can_see_match', $can, $post, $viewer_id );
	}

	/**
	 * Core visibility logic before {@see clanbite_viewer_can_see_match}.
	 *
	 * @param WP_Post $post      Match post.
	 * @param int     $viewer_id Viewer user ID (0 for guests).
	 * @return bool
	 */
	private function resolve_viewer_can_see_match( WP_Post $post, int $viewer_id = 0 ): bool {
		// Draft/pending/private matches are admin-only unless the viewer can read the post.
		if ( 'publish' !== $post->post_status ) {
			return $viewer_id > 0 && current_user_can( 'read_post', (int) $post->ID );
		}

		$vis = $this->sanitize_match_visibility( (string) get_post_meta( $post->ID, 'cp_match_visibility', true ) );

		if ( self::VISIBILITY_PUBLIC === $vis ) {
			return true;
		}

		if ( self::VISIBILITY_MEMBERS === $vis ) {
			return $viewer_id > 0;
		}

		$home_id = (int) get_post_meta( $post->ID, 'cp_match_home_team_id', true );
		$away_id = (int) get_post_meta( $post->ID, 'cp_match_away_team_id', true );

		if ( self::VISIBILITY_TEAM_ADMINS === $vis ) {
			if ( $viewer_id <= 0 || ! function_exists( 'clanbite_teams_user_can_manage' ) ) {
				return false;
			}
			return ( $home_id > 0 && clanbite_teams_user_can_manage( $home_id, $viewer_id ) )
				|| ( $away_id > 0 && clanbite_teams_user_can_manage( $away_id, $viewer_id ) )
				|| current_user_can( 'manage_options' );
		}

		// team_members
		if ( $viewer_id <= 0 ) {
			return false;
		}

		if ( function_exists( 'clanbite_teams_get_member_role' ) ) {
			return ( $home_id > 0 && null !== clanbite_teams_get_member_role( $home_id, $viewer_id ) )
				|| ( $away_id > 0 && null !== clanbite_teams_get_member_role( $away_id, $viewer_id ) );
		}

		// Conservative fallback when membership helper isn't available.
		return false;
	}

	/**
	 * Localized labels for match status keys.
	 *
	 * @return array<string, string> Status slug => label.
	 */
	public function get_status_choices(): array {
		return array(
			self::STATUS_SCHEDULED => __( 'Scheduled', 'clanbite' ),
			self::STATUS_LIVE      => __( 'Live', 'clanbite' ),
			self::STATUS_FINISHED  => __( 'Finished', 'clanbite' ),
			self::STATUS_CANCELLED => __( 'Cancelled', 'clanbite' ),
		);
	}

	/**
	 * Register the public `cp_match` post type.
	 *
	 * @return void
	 */
	public function register_match_post_type(): void {
		$labels = array(
			'name'               => _x( 'Matches', 'post type general name', 'clanbite' ),
			'singular_name'      => _x( 'Match', 'post type singular name', 'clanbite' ),
			'menu_name'          => _x( 'Matches', 'admin menu', 'clanbite' ),
			'name_admin_bar'     => _x( 'Match', 'add new on admin bar', 'clanbite' ),
			'add_new'            => _x( 'Add New', 'match', 'clanbite' ),
			'add_new_item'       => __( 'Add New Match', 'clanbite' ),
			'new_item'           => __( 'New Match', 'clanbite' ),
			'edit_item'          => __( 'Edit Match', 'clanbite' ),
			'view_item'          => __( 'View Match', 'clanbite' ),
			'all_items'          => __( 'All Matches', 'clanbite' ),
			'search_items'       => __( 'Search Matches', 'clanbite' ),
			'parent_item_colon'  => __( 'Parent Match:', 'clanbite' ),
			'not_found'          => __( 'No matches found.', 'clanbite' ),
			'not_found_in_trash' => __( 'No matches found in Trash.', 'clanbite' ),
		);

		register_post_type(
			'clanbite_match',
			array(
				'labels'          => $labels,
				'description'     => __( 'Scheduled or completed matches between teams.', 'clanbite' ),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => 'clanbite',
				'show_in_rest'    => true,
				'has_archive'     => true,
				'rewrite'         => array( 'slug' => 'matches' ),
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'menu_icon'       => 'dashicons-calendar-alt',
			)
		);
	}

	/**
	 * Register post meta with REST exposure; editor fields are rendered in JS (see `enqueue_match_editor`).
	 *
	 * @return void
	 */
	public function register_match_meta(): void {
		$status_schema = array(
			'type' => 'string',
			'enum' => array(
				self::STATUS_SCHEDULED,
				self::STATUS_LIVE,
				self::STATUS_FINISHED,
				self::STATUS_CANCELLED,
			),
		);

		$visibility_schema = array(
			'type' => 'string',
			'enum' => array(
				self::VISIBILITY_PUBLIC,
				self::VISIBILITY_MEMBERS,
				self::VISIBILITY_TEAM_MEMBERS,
				self::VISIBILITY_TEAM_ADMINS,
			),
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_home_team_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_away_team_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_away_external_label',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_away_external_logo_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_away_external_profile_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_scheduled_at',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_scheduled_at' ),
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_status',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => self::STATUS_SCHEDULED,
				'sanitize_callback' => array( $this, 'sanitize_match_status' ),
				'show_in_rest'      => array(
					'schema' => $status_schema,
				),
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_home_score',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_away_score',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_venue',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_visibility',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => self::VISIBILITY_PUBLIC,
				'sanitize_callback' => array( $this, 'sanitize_match_visibility' ),
				'show_in_rest'      => array(
					'schema' => $visibility_schema,
				),
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		$attendees_visibility_schema = array(
			'type' => 'string',
			'enum' => array( 'public', 'hidden' ),
		);

		register_post_meta(
			'clanbite_match',
			'cp_match_attendees_visibility',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'hidden',
				'sanitize_callback' => array( $this, 'sanitize_match_attendees_visibility' ),
				'show_in_rest'      => array(
					'schema' => $attendees_visibility_schema,
				),
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);
	}

	/**
	 * Limit meta writes in REST/editor to users who can edit the post.
	 *
	 * @param mixed $allowed  Prior decision (unused).
	 * @param mixed $meta_key Meta key (unused).
	 * @param mixed $post_id  Post ID the meta belongs to.
	 * @return bool
	 */
	public function meta_auth_edit_post( $allowed, $meta_key, $post_id ): bool {
		unset( $allowed, $meta_key );
		return current_user_can( 'edit_post', (int) $post_id );
	}

	/**
	 * Normalize scheduled datetime meta to GMT MySQL format.
	 *
	 * @param mixed $value Raw meta from REST or classic save.
	 * @return string Empty string or `Y-m-d H:i:s` in GMT.
	 */
	public function sanitize_scheduled_at( $value ): string {
		if ( null === $value || '' === $value ) {
			return '';
		}
		if ( is_numeric( $value ) ) {
			return gmdate( 'Y-m-d H:i:s', (int) $value );
		}
		$str = sanitize_text_field( (string) $value );
		$ts  = strtotime( $str );
		if ( false === $ts ) {
			return '';
		}

		return gmdate( 'Y-m-d H:i:s', $ts );
	}

	/**
	 * Restrict status meta to known slugs.
	 *
	 * @param mixed $value Raw status value.
	 * @return string One of the {@see STATUS_*} constants.
	 */
	public function sanitize_match_status( $value ): string {
		$value = sanitize_key( (string) $value );
		$keys  = array_keys( $this->get_status_choices() );

		return in_array( $value, $keys, true ) ? $value : self::STATUS_SCHEDULED;
	}

	/**
	 * Restrict visibility meta to known slugs.
	 *
	 * @param mixed $value Raw visibility value.
	 * @return string One of the {@see VISIBILITY_*} constants.
	 */
	public function sanitize_match_visibility( $value ): string {
		$value = sanitize_key( (string) $value );
		$allowed = array(
			self::VISIBILITY_PUBLIC,
			self::VISIBILITY_MEMBERS,
			self::VISIBILITY_TEAM_MEMBERS,
			self::VISIBILITY_TEAM_ADMINS,
		);

		return in_array( $value, $allowed, true ) ? $value : self::VISIBILITY_PUBLIC;
	}

	/**
	 * Restrict attendees visibility meta to known slugs.
	 *
	 * @param mixed $value Raw visibility value.
	 * @return string `public` or `hidden`.
	 */
	public function sanitize_match_attendees_visibility( $value ): string {
		$value   = sanitize_key( (string) $value );
		$allowed = array( 'public', 'hidden' );

		return in_array( $value, $allowed, true ) ? $value : 'hidden';
	}

	/**
	 * Insert custom columns after the title on the match list screen.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public function match_admin_columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['cp_match_teams']  = __( 'Teams', 'clanbite' );
				$new['cp_match_when']   = __( 'Scheduled', 'clanbite' );
				$new['cp_match_status'] = __( 'Match status', 'clanbite' );
				$new['cp_match_score']  = __( 'Score', 'clanbite' );
			}
		}

		return $new;
	}

	/**
	 * Output HTML for a single admin list column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Match post ID.
	 * @return void
	 */
	public function render_match_admin_column( string $column, int $post_id ): void {
		if ( 'cp_match_teams' === $column ) {
			$h = (int) get_post_meta( $post_id, 'cp_match_home_team_id', true );
			$a = (int) get_post_meta( $post_id, 'cp_match_away_team_id', true );
			$away_label = $a > 0
				? clanbite_matches_team_title( $a )
				: ( function_exists( 'clanbite_matches_resolve_away_team_title' )
					? clanbite_matches_resolve_away_team_title( $post_id )
					: clanbite_matches_team_title( $a ) );
			echo esc_html( clanbite_matches_team_title( $h ) . ' vs ' . $away_label );
			return;
		}
		if ( 'cp_match_when' === $column ) {
			$raw = (string) get_post_meta( $post_id, 'cp_match_scheduled_at', true );
			echo esc_html(
				clanbite_matches_format_datetime_local( $raw, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
			);
			return;
		}
		if ( 'cp_match_status' === $column ) {
			$s       = (string) get_post_meta( $post_id, 'cp_match_status', true );
			$choices = $this->get_status_choices();
			echo esc_html( $choices[ $s ] ?? $s );
			return;
		}
		if ( 'cp_match_score' === $column ) {
			$hs = (int) get_post_meta( $post_id, 'cp_match_home_score', true );
			$as = (int) get_post_meta( $post_id, 'cp_match_away_score', true );
			echo esc_html( "{$hs} – {$as}" );
		}
	}

	/**
	 * Strip invalid team IDs and ensure a default schedule time on save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function validate_match_on_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( 'clanbite_match' !== $post->post_type ) {
			return;
		}

		$home = (int) get_post_meta( $post_id, 'cp_match_home_team_id', true );
		$away = (int) get_post_meta( $post_id, 'cp_match_away_team_id', true );

		if ( $home > 0 ) {
			$h_post = get_post( $home );
			if ( ! $h_post || 'clanbite_team' !== $h_post->post_type ) {
				delete_post_meta( $post_id, 'cp_match_home_team_id' );
			}
		}
		if ( $away > 0 ) {
			$a_post = get_post( $away );
			if ( ! $a_post || 'clanbite_team' !== $a_post->post_type ) {
				delete_post_meta( $post_id, 'cp_match_away_team_id' );
				delete_post_meta( $post_id, 'cp_match_away_external_label' );
				delete_post_meta( $post_id, 'cp_match_away_external_logo_url' );
				delete_post_meta( $post_id, 'cp_match_away_external_profile_url' );
			} else {
				delete_post_meta( $post_id, 'cp_match_away_external_label' );
				delete_post_meta( $post_id, 'cp_match_away_external_logo_url' );
				delete_post_meta( $post_id, 'cp_match_away_external_profile_url' );
			}
		}

		$sched = (string) get_post_meta( $post_id, 'cp_match_scheduled_at', true );
		if ( '' === $sched ) {
			update_post_meta( $post_id, 'cp_match_scheduled_at', gmdate( 'Y-m-d H:i:s' ) );
		}

		/**
		 * Fires after Clanbite validates a match post on save.
		 *
		 * Use to trigger side-effects (e.g. optional activity-feed integrations) when a match is
		 * scheduled/updated and its visibility may affect who should see it.
		 *
		 * @param int     $post_id Match post ID.
		 * @param WP_Post $post    Match post object.
		 * @param Matches $extension Matches extension instance.
		 */
		do_action( 'clanbite_match_saved', $post_id, $post, $this );
	}

	/**
	 * Load Match list and Match card blocks from the plugin root `build/matches` bundle (metadata collection).
	 *
	 * @return void
	 */
	public function register_match_block_libraries(): void {
		$base = clanbite()->path . 'build/matches';
		$manifest = $base . '/blocks-manifest.php';

		if ( ! is_dir( $base ) || ! is_readable( $manifest ) ) {
			add_action( 'admin_notices', array( $this, 'render_notice_missing_match_assets' ) );
			return;
		}

		$this->register_extension_block_types_from_metadata_collection( 'build/matches' );
	}

	/**
	 * Admin notice when block build output is missing (run package `npm run build`).
	 *
	 * @return void
	 */
	public function render_notice_missing_match_assets(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}
		$relevant = false;
		if ( 'edit-cp_match' === $screen->id ) {
			$relevant = true;
		}
		if ( 'post' === $screen->base && 'clanbite_match' === $screen->post_type ) {
			$relevant = true;
		}
		if ( 'toplevel_page_clanbite' === $screen->id ) {
			$relevant = true;
		}
		if ( ! $relevant ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__(
				'Clanbite Matches: block assets are missing. From the plugin directory, run npm ci and npm run build:production (or npm run plugin-zip).',
				'clanbite'
			)
		);
	}

	/**
	 * Enqueue the block editor script that registers the Match details document sidebar.
	 *
	 * @return void
	 */
	public function enqueue_match_editor(): void {
		$path = clanbite()->path . 'build/cp-match-editor/index.asset.php';
		if ( ! is_readable( $path ) ) {
			return;
		}

		$asset = include $path;
		$deps  = isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array();
		$ver   = isset( $asset['version'] ) ? (string) $asset['version'] : '1.0.0';

		wp_enqueue_script(
			'clanbite-cp-match-editor',
			clanbite()->url . 'build/cp-match-editor/index.js',
			$deps,
			$ver,
			true
		);
	}

	/**
	 * Build a `meta_query` fragment for list blocks (optional team + status).
	 *
	 * @param int    $team_id `cp_team` post ID or 0 for any team.
	 * @param string $status  Status slug or empty for any status.
	 * @return array<int|string, mixed>
	 */
	protected function build_block_meta_query( int $team_id, string $status ): array {
		$parts = array();
		if ( $team_id > 0 ) {
			$parts[] = array(
				'relation' => 'OR',
				array(
					'key'   => 'cp_match_home_team_id',
					'value' => $team_id,
				),
				array(
					'key'   => 'cp_match_away_team_id',
					'value' => $team_id,
				),
			);
		}
		$allowed = array_keys( $this->get_status_choices() );
		if ( '' !== $status && in_array( $status, $allowed, true ) ) {
			$parts[] = array(
				'key'   => 'cp_match_status',
				'value' => $status,
			);
		}
		if ( count( $parts ) === 0 ) {
			return array();
		}
		if ( count( $parts ) === 1 ) {
			return $parts[0];
		}

		return array_merge( array( 'relation' => 'AND' ), $parts );
	}

	/**
	 * Collect referenced `clanbite_team` IDs from match posts for cache priming.
	 *
	 * @param array<int, \WP_Post> $match_posts Match posts (any status handled by callers).
	 * @return array<int, int>
	 */
	protected function collect_team_ids_from_match_posts( array $match_posts ): array {
		$ids = array();
		foreach ( $match_posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$ids[] = (int) get_post_meta( $post->ID, 'cp_match_home_team_id', true );
			$ids[] = (int) get_post_meta( $post->ID, 'cp_match_away_team_id', true );
		}

		return $ids;
	}

	/**
	 * Prime WordPress post cache for team rows referenced by matches (reduces per-row `get_post()` overhead).
	 *
	 * @param array<int, int|string> $team_ids Team post IDs (non-positive values ignored).
	 * @return void
	 */
	protected function prime_team_post_cache( array $team_ids ): void {
		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $team_ids ),
					static function ( int $id ): bool {
						return $id > 0;
					}
				)
			)
		);
		if ( array() === $ids ) {
			return;
		}

		get_posts(
			array(
				'post_type'              => 'clanbite_team',
				'post__in'               => $ids,
				'posts_per_page'         => count( $ids ),
				'post_status'            => 'any',
				'orderby'                => 'post__in',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);
	}

	/**
	 * Prime team post cache for all matches in a batch (SSR lists, REST collections).
	 *
	 * @param array<int, \WP_Post> $match_posts Match posts from `WP_Query`.
	 * @return void
	 */
	public function prime_team_post_cache_for_match_posts( array $match_posts ): void {
		$this->prime_team_post_cache( $this->collect_team_ids_from_match_posts( $match_posts ) );
	}

	/**
	 * HTML for the Match list block (`render.php` entry point).
	 *
	 * @param array<string, mixed> $attributes Block attributes (`teamId`, `limit`, `statusFilter`, `order`).
	 * @return string HTML (escaped internally).
	 */
	public function render_list_block_markup( array $attributes ): string {
		$team_id = (int) ( $attributes['teamId'] ?? 0 );
		$limit   = (int) ( $attributes['limit'] ?? 0 );
		if ( $limit < 1 ) {
			$limit = max( 1, (int) $this->admin->get( 'default_list_limit', 10 ) );
		}
		$limit = min( 50, max( 1, $limit ) );

		$status = sanitize_key( (string) ( $attributes['statusFilter'] ?? '' ) );
		$order  = strtolower( (string) ( $attributes['order'] ?? 'asc' ) ) === 'desc' ? 'DESC' : 'ASC';

		$show_scores = (bool) $this->admin->get( 'show_scores', true );
		$fmt         = (string) $this->admin->get( 'datetime_format', 'M j, Y g:i a' );

		// phpcs:disable WordPress.DB.SlowDBQuery -- Block list sorts by `cp_match_scheduled_at`; optional `meta_query` scopes by team/status.
		$args = array(
			'post_type'              => 'clanbite_match',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => 'meta_value',
			'meta_key'               => 'cp_match_scheduled_at',
			'meta_type'              => 'DATETIME',
			'order'                  => $order,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		);

		$meta_query = $this->build_block_meta_query( $team_id, $status );
		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$q = new \WP_Query( $args );
		// phpcs:enable WordPress.DB.SlowDBQuery
		$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;

		ob_start();
		if ( ! $q->have_posts() ) {
			echo '<div class="clanbite-match-list clanbite-match-list--empty"><p>' . esc_html__( 'No matches to show.', 'clanbite' ) . '</p></div>';
			return (string) ob_get_clean();
		}

		$this->prime_team_post_cache_for_match_posts( $q->posts );

		echo '<ul class="clanbite-match-list">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$pid = (int) get_the_ID();
			$post = get_post( $pid );
			if ( $post instanceof \WP_Post && ! $this->viewer_can_see_match( $post, $viewer_id ) ) {
				continue;
			}
			$h   = (int) get_post_meta( $pid, 'cp_match_home_team_id', true );
			$a   = (int) get_post_meta( $pid, 'cp_match_away_team_id', true );
			$away_title = function_exists( 'clanbite_matches_resolve_away_team_title' )
				? clanbite_matches_resolve_away_team_title( $pid )
				: clanbite_matches_team_title( $a );
			$st  = (string) get_post_meta( $pid, 'cp_match_status', true );
			$raw = (string) get_post_meta( $pid, 'cp_match_scheduled_at', true );
			$hs  = (int) get_post_meta( $pid, 'cp_match_home_score', true );
			$as  = (int) get_post_meta( $pid, 'cp_match_away_score', true );

			$choices = $this->get_status_choices();
			$st_lbl  = $choices[ $st ] ?? $st;

			echo '<li class="clanbite-match-list__item">';
			echo '<a class="clanbite-match-list__link" href="' . esc_url( get_permalink() ) . '">';
			echo '<span class="clanbite-match-list__teams">';
			echo esc_html( clanbite_matches_team_title( $h ) . ' vs ' . $away_title );
			echo '</span>';
			echo '<span class="clanbite-match-list__meta">';
			echo esc_html( clanbite_matches_format_datetime_local( $raw, $fmt ) );
			echo ' · ';
			echo esc_html( $st_lbl );
			if ( $show_scores ) {
				echo ' · ';
				echo esc_html( (string) $hs . ' – ' . (string) $as );
			}
			echo '</span>';
			echo '</a>';
			echo '</li>';
		}
		echo '</ul>';
		wp_reset_postdata();

		// If everything was filtered, show the empty state.
		$out = (string) ob_get_clean();
		if ( false !== strpos( $out, '<ul class="clanbite-match-list">' )
			&& false !== strpos( $out, '</ul>' )
			&& false === strpos( $out, 'clanbite-match-list__item' )
		) {
			return '<div class="clanbite-match-list clanbite-match-list--empty"><p>' . esc_html__( 'No matches to show.', 'clanbite' ) . '</p></div>';
		}

		return $out;
	}

	/**
	 * HTML for the Match card block (`render.php` entry point).
	 *
	 * @param array<string, mixed> $attributes Block attributes (`matchId`).
	 * @return string HTML (escaped internally).
	 */
	public function render_card_block_markup( array $attributes ): string {
		$match_id = (int) ( $attributes['matchId'] ?? 0 );
		if ( $match_id <= 0 ) {
			return '<div class="clanbite-match-card clanbite-match-card--placeholder"><p>' . esc_html__( 'Select a match in the block settings.', 'clanbite' ) . '</p></div>';
		}

		$post = get_post( $match_id );
		if ( ! $post || 'clanbite_match' !== $post->post_type || 'publish' !== $post->post_status ) {
			return '<div class="clanbite-match-card clanbite-match-card--missing"><p>' . esc_html__( 'Match not found.', 'clanbite' ) . '</p></div>';
		}

		$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;
		if ( ! $this->viewer_can_see_match( $post, $viewer_id ) ) {
			return '<div class="clanbite-match-card clanbite-match-card--forbidden"><p>' . esc_html__( 'This match is not available.', 'clanbite' ) . '</p></div>';
		}

		$this->prime_team_post_cache_for_match_posts( array( $post ) );

		$data        = $this->match_to_rest_array( $post );
		$show_scores = (bool) $this->admin->get( 'show_scores', true );
		$choices     = $this->get_status_choices();
		$st_lbl      = $choices[ $data['status'] ] ?? $data['status'];

		$away_team_title = isset( $data['awayTeamTitle'] ) ? trim( wp_strip_all_tags( (string) $data['awayTeamTitle'] ) ) : '';
		$away_logo_alt   = '' !== $away_team_title
			? sprintf(
				/* translators: %s: opposing team name. */
				__( 'Logo for %s', 'clanbite' ),
				$away_team_title
			)
			: __( 'Away team logo', 'clanbite' );

		ob_start();
		?>
		<article class="clanbite-match-card">
			<h3 class="clanbite-match-card__title">
				<a href="<?php echo esc_url( $data['link'] ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
			</h3>
			<p class="clanbite-match-card__teams">
				<span class="clanbite-match-card__home"><?php echo esc_html( $data['homeTeamTitle'] ); ?></span>
				<span class="clanbite-match-card__vs"> <?php esc_html_e( 'vs', 'clanbite' ); ?> </span>
				<span class="clanbite-match-card__away">
					<?php
					$ext = isset( $data['awayExternal'] ) && is_array( $data['awayExternal'] ) ? $data['awayExternal'] : array();
					$logo = isset( $ext['logoUrl'] ) ? esc_url( (string) $ext['logoUrl'] ) : '';
					if ( '' !== $logo ) :
						?>
					<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $away_logo_alt ); ?>" class="clanbite-match-card__away-logo" width="32" height="32" loading="lazy" decoding="async" />
					<?php endif; ?>
					<?php
					$away_link = isset( $ext['profileUrl'] ) ? esc_url( (string) $ext['profileUrl'] ) : '';
					if ( '' !== $away_link ) :
						?>
					<a href="<?php echo esc_url( $away_link ); ?>"><?php echo esc_html( $data['awayTeamTitle'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $data['awayTeamTitle'] ); ?>
					<?php endif; ?>
				</span>
			</p>
			<p class="clanbite-match-card__when"><?php echo esc_html( $data['scheduledLabel'] ); ?></p>
			<p class="clanbite-match-card__status"><?php echo esc_html( $st_lbl ); ?></p>
			<?php if ( $show_scores ) : ?>
				<p class="clanbite-match-card__score">
					<?php echo esc_html( (string) $data['homeScore'] . ' – ' . (string) $data['awayScore'] ); ?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $data['venue'] ) ) : ?>
				<p class="clanbite-match-card__venue"><?php echo esc_html( $data['venue'] ); ?></p>
			<?php endif; ?>
		</article>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Use the plugin single template for match permalinks when the theme has no override.
	 *
	 * @param string $template Path passed by WordPress.
	 * @return string Template path to load.
	 */
	public function maybe_single_match_template( string $template ): string {
		if ( ! is_singular( 'clanbite_match' ) ) {
			return $template;
		}

		$plugin = clanbite()->path . 'templates/matches/single-clanbite_match.php';
		if ( is_readable( $plugin ) ) {
			return $plugin;
		}

		return $template;
	}
}
