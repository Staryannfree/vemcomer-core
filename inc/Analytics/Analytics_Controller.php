<?php
/**
 * Analytics_Controller — REST endpoints para analytics de restaurantes
 * @package VemComerCore
 */

namespace VC\Analytics;

use VC\Model\CPT_AnalyticsEvent;
use VC\Model\CPT_Restaurant;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Analytics_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/analytics', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_analytics' ],
			'permission_callback' => [ $this, 'check_restaurant_access' ],
			'args'                => [
				'id'        => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'period'    => [
					'required'          => false,
					'default'           => 'week',
					'validate_callback' => function( $param ) {
						return in_array( $param, [ 'today', 'week', 'month', 'custom' ], true );
					},
					'sanitize_callback' => 'sanitize_text_field',
				],
				'date_from' => [
					'required'          => false,
					'validate_callback' => function( $param ) {
						return empty( $param ) || strtotime( $param ) !== false;
					},
					'sanitize_callback' => 'sanitize_text_field',
				],
				'date_to'   => [
					'required'          => false,
					'validate_callback' => function( $param ) {
						return empty( $param ) || strtotime( $param ) !== false;
					},
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	/**
	 * Verifica se o usuário pode acessar analytics do restaurante.
	 */
	public function check_restaurant_access( WP_REST_Request $request ): bool {
		// Admins podem ver todos
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Verificar se é dono do restaurante
		$restaurant_id = (int) $request->get_param( 'id' );
		$user_id       = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		// Verificar se o usuário é dono do restaurante
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return false;
		}

		// Verificar se o usuário é autor do restaurante ou tem permissão
		return (int) $restaurant->post_author === $user_id || current_user_can( 'edit_post', $restaurant_id );
	}

	/**
	 * GET /wp-json/vemcomer/v1/restaurants/{id}/analytics
	 * Retorna métricas de analytics do restaurante
	 */
	public function get_analytics( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$restaurant_id = (int) $request->get_param( 'id' );
		$period        = $request->get_param( 'period' );
		$date_from     = $request->get_param( 'date_from' );
		$date_to       = $request->get_param( 'date_to' );

		// Verificar se restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error(
				'vc_restaurant_not_found',
				__( 'Restaurante não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		// Calcular período
		$date_range = $this->calculate_date_range( $period, $date_from, $date_to );

		// Buscar eventos
		$events = $this->get_events( $restaurant_id, $date_range['from'], $date_range['to'] );

		// Calcular métricas
		$metrics = $this->calculate_metrics( $events );

		$response = [
			'restaurant_id' => $restaurant_id,
			'period'        => $period,
			'date_from'     => $date_range['from'],
			'date_to'       => $date_range['to'],
			'metrics'       => $metrics,
		];

		log_event( 'REST analytics fetched', [
			'restaurant_id' => $restaurant_id,
			'period'        => $period,
		], 'debug' );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Calcula o range de datas baseado no período.
	 */
	private function calculate_date_range( string $period, ?string $date_from, ?string $date_to ): array {
		$now = time();

		switch ( $period ) {
			case 'today':
				return [
					'from' => date( 'Y-m-d 00:00:00', $now ),
					'to'   => date( 'Y-m-d 23:59:59', $now ),
				];

			case 'week':
				return [
					'from' => date( 'Y-m-d 00:00:00', strtotime( '-7 days', $now ) ),
					'to'   => date( 'Y-m-d 23:59:59', $now ),
				];

			case 'month':
				return [
					'from' => date( 'Y-m-d 00:00:00', strtotime( '-30 days', $now ) ),
					'to'   => date( 'Y-m-d 23:59:59', $now ),
				];

			case 'custom':
				return [
					'from' => $date_from ? date( 'Y-m-d 00:00:00', strtotime( $date_from ) ) : date( 'Y-m-d 00:00:00', $now ),
					'to'   => $date_to ? date( 'Y-m-d 23:59:59', strtotime( $date_to ) ) : date( 'Y-m-d 23:59:59', $now ),
				];

			default:
				return [
					'from' => date( 'Y-m-d 00:00:00', strtotime( '-7 days', $now ) ),
					'to'   => date( 'Y-m-d 23:59:59', $now ),
				];
		}
	}

	/**
	 * Busca eventos do restaurante no período.
	 */
	private function get_events( int $restaurant_id, string $date_from, string $date_to ): array {
		$query = new WP_Query( [
			'post_type'      => CPT_AnalyticsEvent::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'   => '_vc_restaurant_id',
					'value' => (string) $restaurant_id,
				],
			],
			'date_query'     => [
				[
					'after'  => $date_from,
					'before' => $date_to,
					'inclusive' => true,
				],
			],
			'orderby'        => 'date',
			'order'          => 'ASC',
		] );

		$events = [];
		foreach ( $query->posts as $post ) {
			$events[] = [
				'id'           => $post->ID,
				'event_type'   => get_post_meta( $post->ID, '_vc_event_type', true ),
				'customer_id'  => (int) get_post_meta( $post->ID, '_vc_customer_id', true ),
				'metadata'     => json_decode( get_post_meta( $post->ID, '_vc_event_metadata', true ), true ) ?: [],
				'timestamp'    => get_post_time( 'U', false, $post ),
			];
		}

		return $events;
	}

	/**
	 * Calcula métricas a partir dos eventos.
	 */
	private function calculate_metrics( array $events ): array {
		$metrics = [
			'views_restaurant' => 0,
			'views_menu'       => 0,
			'clicks_whatsapp'  => 0,
			'adds_to_cart'     => 0,
			'checkouts_start'  => 0,
			'conversion_rate'  => 0.0,
			'unique_customers' => 0,
		];

		$customers = [];

		foreach ( $events as $event ) {
			$event_type = $event['event_type'];

			switch ( $event_type ) {
				case CPT_AnalyticsEvent::EVENT_VIEW_RESTAURANT:
					$metrics['views_restaurant']++;
					break;
				case CPT_AnalyticsEvent::EVENT_VIEW_MENU:
					$metrics['views_menu']++;
					break;
				case CPT_AnalyticsEvent::EVENT_CLICK_WHATSAPP:
					$metrics['clicks_whatsapp']++;
					break;
				case CPT_AnalyticsEvent::EVENT_ADD_TO_CART:
					$metrics['adds_to_cart']++;
					break;
				case CPT_AnalyticsEvent::EVENT_CHECKOUT_START:
					$metrics['checkouts_start']++;
					break;
			}

			// Contar clientes únicos
			if ( $event['customer_id'] > 0 ) {
				$customers[ $event['customer_id'] ] = true;
			}
		}

		$metrics['unique_customers'] = count( $customers );

		// Calcular taxa de conversão (cliques WhatsApp / visualizações de restaurante)
		if ( $metrics['views_restaurant'] > 0 ) {
			$metrics['conversion_rate'] = round( ( $metrics['clicks_whatsapp'] / $metrics['views_restaurant'] ) * 100, 2 );
		}

		// Itens mais vistos (do metadata)
		$menu_item_views = [];
		foreach ( $events as $event ) {
			if ( isset( $event['metadata']['menu_item_id'] ) ) {
				$item_id = (int) $event['metadata']['menu_item_id'];
				if ( ! isset( $menu_item_views[ $item_id ] ) ) {
					$menu_item_views[ $item_id ] = 0;
				}
				$menu_item_views[ $item_id ]++;
			}
		}
		arsort( $menu_item_views );
		$metrics['top_menu_items'] = array_slice( array_keys( $menu_item_views ), 0, 10, true );

		return $metrics;
	}
}

