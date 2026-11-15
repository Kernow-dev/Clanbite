<?php
/**
 * REST routes behind the Licence tab.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Updates;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * `clanbite/v1/admin/license` (read), `…/activate`, `…/deactivate` and `…/refresh`, for administrators only (same capability as the
 * other `clanbite/v1/admin/*` routes, filter `clanbite_admin_rest_manage_capability`). The licence key is accepted on activate and is
 * never returned.
 */
final class License_Rest {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		$permission = array( self::class, 'can_manage' );
		register_rest_route(
			'clanbite/v1',
			'/admin/license',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static fn() => new WP_REST_Response( License_Client::summary(), 200 ),
				'permission_callback' => $permission,
			)
		);
		register_rest_route(
			'clanbite/v1',
			'/admin/license/activate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'activate' ),
				'permission_callback' => $permission,
				'args'                => array(
					'license_key' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
		register_rest_route(
			'clanbite/v1',
			'/admin/license/deactivate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => static fn() => self::respond( License_Client::deactivate() ),
				'permission_callback' => $permission,
			)
		);
		register_rest_route(
			'clanbite/v1',
			'/admin/license/refresh',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => static fn() => self::respond( License_Client::refresh() ),
				'permission_callback' => $permission,
			)
		);
	}

	/**
	 * Permission callback.
	 *
	 * @return bool
	 */
	public static function can_manage(): bool {
		$capability = apply_filters( 'clanbite_admin_rest_manage_capability', 'manage_options' );

		return current_user_can( is_string( $capability ) && '' !== $capability ? $capability : 'manage_options' );
	}

	/**
	 * `POST activate`.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public static function activate( WP_REST_Request $request ) {
		return self::respond( License_Client::activate( sanitize_text_field( (string) $request->get_param( 'license_key' ) ) ) );
	}

	/**
	 * Wrap a client result.
	 *
	 * @param array<string, mixed>|\WP_Error $result Summary or error.
	 * @return WP_REST_Response|\WP_Error
	 */
	private static function respond( $result ) {
		if ( ! is_wp_error( $result ) ) {
			return new WP_REST_Response( $result, 200 );
		}
		$data   = $result->get_error_data();
		$status = is_array( $data ) && ! empty( $data['status'] ) ? (int) $data['status'] : 400;
		if ( 'clanbite_license_unreachable' === $result->get_error_code() ) {
			$status = 502;
		}

		return new \WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => $status >= 400 ? $status : 400 ) );
	}
}
