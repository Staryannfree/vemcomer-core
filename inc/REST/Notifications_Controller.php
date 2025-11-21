<?php
/**
 * Notifications_Controller — REST endpoints para notificações
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Notifications\Notification_Manager;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Notifications_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista notificações do usuário
		register_rest_route( 'vemcomer/v1', '/notifications', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_notifications' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'unread_only' => [
					'required'          => false,
					'default'           => false,
					'sanitize_callback' => 'rest_sanitize_boolean',
				],
			],
		] );

		// POST: Marcar notificação como lida
		register_rest_route( 'vemcomer/v1', '/notifications/(?P<id>[a-zA-Z0-9_]+)/read', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'mark_as_read' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		// POST: Marcar todas como lidas
		register_rest_route( 'vemcomer/v1', '/notifications/read-all', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'mark_all_as_read' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
		] );
	}

	public function check_authenticated(): bool {
		return is_user_logged_in();
	}

	/**
	 * GET /wp-json/vemcomer/v1/notifications
	 * Lista notificações do usuário autenticado
	 */
	public function get_notifications( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$unread_only = $request->get_param( 'unread_only' );

		if ( $unread_only ) {
			$notifications = Notification_Manager::get_unread( $user_id );
		} else {
			$notifications = Notification_Manager::get_all( $user_id );
		}

		$unread_count = count( Notification_Manager::get_unread( $user_id ) );

		return new WP_REST_Response( [
			'notifications' => array_values( $notifications ),
			'total'         => count( $notifications ),
			'unread_count' => $unread_count,
		], 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/notifications/{id}/read
	 * Marca notificação como lida
	 */
	public function mark_as_read( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		$notification_id = $request->get_param( 'id' );

		$success = Notification_Manager::mark_as_read( $user_id, $notification_id );

		if ( ! $success ) {
			return new WP_Error( 'vc_notification_not_found', __( 'Notificação não encontrada.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/notifications/read-all
	 * Marca todas as notificações como lidas
	 */
	public function mark_all_as_read( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		Notification_Manager::mark_all_as_read( $user_id );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}
}

