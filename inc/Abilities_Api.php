<?php
/**
 * WordPress Abilities API integration (WordPress 6.9+).
 *
 * Registers discoverable, schema-backed abilities aligned with {@see Public_Rest} discovery
 * and public team metadata. No-ops on older WordPress versions where the API is unavailable.
 *
 * @package clanbite
 * @link https://developer.wordpress.org/apis/abilities-api/
 */

namespace Kernowdev\Clanbite;

use Kernowdev\Clanbite\Extensions\Loader;
use Kernowdev\Clanbite\Extensions\Skeleton;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Clanbite abilities and category when core supports them.
 *
 * Official companion plugins add abilities on `wp_abilities_api_init` (priority 20) in the
 * same category: forums (`clanbite-forums/*`), social (`clanbite-social/*`),
 * points (`clanbite-points/*`), and ranks (`clanbite-ranks/*`).
 */
final class Abilities_Api {

	public const CATEGORY = 'clanbite';

	/**
	 * Wire Abilities API hooks when available.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		if ( ! function_exists( 'wp_register_ability' ) || ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( self::class, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( self::class, 'register_abilities' ) );
	}

	/**
	 * @return void
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Clanbite', 'clanbite' ),
				'description' => __( 'Community discovery and read-only metadata exposed by the Clanbite plugin.', 'clanbite' ),
			)
		);
	}

	/**
	 * @return void
	 */
	public static function register_abilities(): void {
		wp_register_ability(
			'clanbite/discovery',
			array(
				'label'               => __( 'Clanbite discovery', 'clanbite' ),
				'description'         => __( 'Returns whether this site runs Clanbite, the plugin version, and optional cross-site match sync hints.', 'clanbite' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'        => 'object',
					'description' => __( 'Discovery payload.', 'clanbite' ),
					'properties'  => array(
						'clanbite' => array(
							'type'        => 'boolean',
							'description' => __( 'Always true when Clanbite is active.', 'clanbite' ),
						),
						'name'       => array(
							'type' => 'string',
						),
						'version'    => array(
							'type'        => 'string',
							'description' => __( 'Clanbite plugin version.', 'clanbite' ),
						),
						'match_sync' => array(
							'type'        => 'object',
							'description' => __( 'Present when Libsodium signing is available.', 'clanbite' ),
						),
					),
					'required'   => array( 'clanbite', 'name', 'version' ),
				),
				'execute_callback'    => array( self::class, 'execute_discovery' ),
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'clanbite/public-team',
			array(
				'label'               => __( 'Public team metadata', 'clanbite' ),
				'description'         => __( 'Returns public metadata for a published team given its slug or an absolute team profile URL.', 'clanbite' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'slug' => array(
							'type'        => 'string',
							'description' => __( 'Team post slug (`cp_team` post_name).', 'clanbite' ),
						),
						'url'  => array(
							'type'        => 'string',
							'description' => __( 'Full URL to a team profile page on this site.', 'clanbite' ),
						),
					),
				),
				'output_schema'       => array(
					'type'        => 'object',
					'description' => __( 'Public team fields.', 'clanbite' ),
					'properties'  => array(
						'id'          => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'permalink'   => array( 'type' => 'string' ),
						'logoUrl'     => array( 'type' => 'string' ),
						'motto'       => array( 'type' => 'string' ),
						'country'     => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'id', 'title', 'slug' ),
				),
				'execute_callback'    => array( self::class, 'execute_public_team' ),
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'clanbite/active-extensions',
			array(
				'label'               => __( 'Active Clanbite extensions', 'clanbite' ),
				'description'         => __( 'Lists extensions currently loaded by Clanbite (slug, name, version).', 'clanbite' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'extensions' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'slug'        => array( 'type' => 'string' ),
									'name'        => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'version'     => array( 'type' => 'string' ),
								),
							),
						),
					),
					'required'   => array( 'extensions' ),
				),
				'execute_callback'    => array( self::class, 'execute_active_extensions' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);

		/**
		 * Fires after Clanbite registers core abilities (WordPress 6.9+).
		 *
		 * Companion plugins may call `wp_register_ability()` here to add more abilities
		 * in the same category using slug `clanbite` via {@see Abilities_Api::CATEGORY}.
		 */
		do_action( 'clanbite_abilities_registered' );
	}

	/**
	 * @param mixed $input Ability input (unused).
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute_discovery( $input ) {
		unset( $input );

		return Public_Rest::build_discovery_payload();
	}

	/**
	 * @param mixed $input Associative input with optional `slug` and `url`.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute_public_team( $input ) {
		$args = is_array( $input ) ? $input : array();
		$slug = isset( $args['slug'] ) ? (string) $args['slug'] : '';
		$url  = isset( $args['url'] ) ? esc_url_raw( (string) $args['url'] ) : '';

		return Public_Rest::get_public_team_data_by_slug_or_url( $slug, $url );
	}

	/**
	 * @param mixed $input Ability input (unused).
	 * @return array<string, mixed>
	 */
	public static function execute_active_extensions( $input ) {
		unset( $input );

		$loader = Loader::instance();
		$list   = array();

		foreach ( $loader->get_extensions() as $extension ) {
			if ( ! $extension instanceof Skeleton ) {
				continue;
			}
			$list[] = array(
				'slug'        => (string) $extension->slug,
				'name'        => (string) $extension->name,
				'description' => (string) $extension->description,
				'version'     => (string) $extension->version,
			);
		}

		/**
		 * Filter the payload returned by the `clanbite/active-extensions` ability.
		 *
		 * @param list<array{slug: string, name: string, description: string, version: string}> $list Extensions.
		 */
		$list = (array) apply_filters( 'clanbite_abilities_active_extensions_list', $list );

		return array( 'extensions' => $list );
	}
}
