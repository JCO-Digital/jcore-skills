<?php
/**
 * REST routes for the general settings.
 *
 * @package Jcore\{{ns}}
 */

namespace Jcore\{{ns}}\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes the `{{prefix}}_settings` option.
 */
final class Settings_Controller extends Controller {

	/**
	 * Option holding the settings array.
	 */
	public const OPTION = '{{prefix}}_settings';

	/**
	 * Default value of every setting. Only these keys are stored.
	 */
	private const DEFAULTS = array(
		'enabled' => false,
	);

	/**
	 * Registers the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				$this->route( WP_REST_Server::READABLE, 'get_settings' ),
				$this->route(
					WP_REST_Server::EDITABLE,
					'update_settings',
					array(
						'enabled' => array(
							'type'     => 'boolean',
							'required' => false,
						),
					)
				),
			)
		);
	}

	/**
	 * Returns the stored settings merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );

		return array_merge( self::DEFAULTS, is_array( $stored ) ? array_intersect_key( $stored, self::DEFAULTS ) : array() );
	}

	/**
	 * Handles GET /settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		return rest_ensure_response( self::get() );
	}

	/**
	 * Handles POST /settings. Only keys present in the request change.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings = self::get();

		if ( $request->has_param( 'enabled' ) ) {
			$settings['enabled'] = (bool) $request->get_param( 'enabled' );
		}

		update_option( self::OPTION, $settings, false );

		return rest_ensure_response( $settings );
	}
}
