<?php
namespace VemComer\Core\REST;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Routes {
	public static function register(): void {
		\add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
	}

	public static function routes(): void {
		\register_rest_route(
			'vemcomer/v1',
			'/ping',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'ping' ],
				'permission_callback' => [ __CLASS__, 'can_ping' ],
			]
		);
	}

	public static function can_ping( \WP_REST_Request $req ): bool {
		return \current_user_can( 'read' );
	}

	public static function ping( \WP_REST_Request $req ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'status'  => 'ok',
				'version' => \defined('VMC_VERSION') ? \VMC_VERSION : 'dev',
			],
			200
		);
	}
}
