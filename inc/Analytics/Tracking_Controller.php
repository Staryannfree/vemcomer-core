<?php
/**
 * Tracking_Controller — Endpoint REST para tracking de eventos via JavaScript
 * @package VemComerCore
 */

namespace VC\Analytics;

use VC\Model\CPT_AnalyticsEvent;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Tracking_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		register_rest_route( 'vemcomer/v1', '/analytics/track', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'track_event' ],
			'permission_callback' => '__return_true', // Público, mas validamos no callback
			'args'                => [
				'event_type'    => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						$valid_types = [
							CPT_AnalyticsEvent::EVENT_VIEW_RESTAURANT,
							CPT_AnalyticsEvent::EVENT_VIEW_MENU,
							CPT_AnalyticsEvent::EVENT_CLICK_WHATSAPP,
							CPT_AnalyticsEvent::EVENT_ADD_TO_CART,
							CPT_AnalyticsEvent::EVENT_CHECKOUT_START,
						];
						return in_array( $param, $valid_types, true );
					},
					'sanitize_callback' => 'sanitize_text_field',
				],
				'restaurant_id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'customer_id'   => [
					'required'          => false,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'metadata'      => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	/**
	 * POST /wp-json/vemcomer/v1/analytics/track
	 * Recebe eventos de tracking via JavaScript
	 */
	public function track_event( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$event_type    = $request->get_param( 'event_type' );
		$restaurant_id = (int) $request->get_param( 'restaurant_id' );
		$customer_id   = (int) $request->get_param( 'customer_id' );
		$metadata_raw  = $request->get_param( 'metadata' );

		// Parse metadata se for string JSON
		$metadata = [];
		if ( ! empty( $metadata_raw ) ) {
			if ( is_string( $metadata_raw ) ) {
				$decoded = json_decode( $metadata_raw, true );
				$metadata = is_array( $decoded ) ? $decoded : [];
			} elseif ( is_array( $metadata_raw ) ) {
				$metadata = $metadata_raw;
			}
		}

		// Logar evento
		Event_Logger::log( $event_type, $restaurant_id, $customer_id, $metadata );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}
}

