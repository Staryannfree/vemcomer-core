<?php
/**
 * Event_Logger — Sistema de logging assíncrono de eventos de analytics
 * @package VemComerCore
 */

namespace VC\Analytics;

use VC\Model\CPT_AnalyticsEvent;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Event_Logger {
	/**
	 * Loga um evento de forma assíncrona (não bloqueia a requisição).
	 *
	 * @param string $event_type Tipo do evento (view_restaurant, click_whatsapp, etc.)
	 * @param int    $restaurant_id ID do restaurante
	 * @param int    $customer_id ID do cliente (opcional, 0 se não autenticado)
	 * @param array  $metadata Dados adicionais (opcional)
	 * @return int|false ID do evento criado ou false em caso de erro
	 */
	public static function log( string $event_type, int $restaurant_id, int $customer_id = 0, array $metadata = [] ): int|false {
		// Validar tipo de evento
		$valid_types = [
			CPT_AnalyticsEvent::EVENT_VIEW_RESTAURANT,
			CPT_AnalyticsEvent::EVENT_VIEW_MENU,
			CPT_AnalyticsEvent::EVENT_CLICK_WHATSAPP,
			CPT_AnalyticsEvent::EVENT_ADD_TO_CART,
			CPT_AnalyticsEvent::EVENT_CHECKOUT_START,
		];

		if ( ! in_array( $event_type, $valid_types, true ) ) {
			log_event( 'Invalid analytics event type', [ 'type' => $event_type ], 'warning' );
			return false;
		}

		// Usar shutdown hook para não bloquear a requisição
		// Isso garante que o evento seja logado após a resposta ser enviada
		$event_data = [
			'event_type'    => $event_type,
			'restaurant_id' => $restaurant_id,
			'customer_id'   => $customer_id,
			'metadata'      => $metadata,
			'timestamp'     => time(),
		];

		// Adicionar à fila de eventos para processamento assíncrono
		add_action( 'shutdown', function() use ( $event_data ) {
			self::create_event( $event_data );
		}, 20 ); // Prioridade 20 para executar depois de outras ações

		return true; // Retorna true imediatamente (não espera criação)
	}

	/**
	 * Cria o evento no banco de dados.
	 * Chamado via shutdown hook para não bloquear a requisição.
	 *
	 * @param array $event_data Dados do evento
	 * @return int|false ID do evento criado
	 */
	private static function create_event( array $event_data ): int|false {
		$post_id = wp_insert_post( [
			'post_type'   => CPT_AnalyticsEvent::SLUG,
			'post_title'  => sprintf( '%s - Restaurant #%d', $event_data['event_type'], $event_data['restaurant_id'] ),
			'post_status' => 'publish',
			'post_date'   => date( 'Y-m-d H:i:s', $event_data['timestamp'] ),
		], true );

		if ( is_wp_error( $post_id ) ) {
			log_event( 'Failed to create analytics event', [
				'error' => $post_id->get_error_message(),
				'data'  => $event_data,
			], 'error' );
			return false;
		}

		// Salvar meta fields
		update_post_meta( $post_id, '_vc_event_type', $event_data['event_type'] );
		update_post_meta( $post_id, '_vc_restaurant_id', $event_data['restaurant_id'] );
		if ( $event_data['customer_id'] > 0 ) {
			update_post_meta( $post_id, '_vc_customer_id', $event_data['customer_id'] );
		}
		if ( ! empty( $event_data['metadata'] ) ) {
			update_post_meta( $post_id, '_vc_event_metadata', wp_json_encode( $event_data['metadata'] ) );
		}
		update_post_meta( $post_id, '_vc_event_timestamp', $event_data['timestamp'] );

		return $post_id;
	}

	/**
	 * Loga visualização de restaurante.
	 */
	public static function log_view_restaurant( int $restaurant_id, int $customer_id = 0 ): void {
		self::log( CPT_AnalyticsEvent::EVENT_VIEW_RESTAURANT, $restaurant_id, $customer_id );
	}

	/**
	 * Loga visualização de cardápio.
	 */
	public static function log_view_menu( int $restaurant_id, int $customer_id = 0 ): void {
		self::log( CPT_AnalyticsEvent::EVENT_VIEW_MENU, $restaurant_id, $customer_id );
	}

	/**
	 * Loga clique no botão WhatsApp.
	 */
	public static function log_click_whatsapp( int $restaurant_id, int $customer_id = 0, array $metadata = [] ): void {
		self::log( CPT_AnalyticsEvent::EVENT_CLICK_WHATSAPP, $restaurant_id, $customer_id, $metadata );
	}

	/**
	 * Loga adição ao carrinho.
	 */
	public static function log_add_to_cart( int $restaurant_id, int $customer_id = 0, array $metadata = [] ): void {
		self::log( CPT_AnalyticsEvent::EVENT_ADD_TO_CART, $restaurant_id, $customer_id, $metadata );
	}

	/**
	 * Loga início de checkout.
	 */
	public static function log_checkout_start( int $restaurant_id, int $customer_id = 0, array $metadata = [] ): void {
		self::log( CPT_AnalyticsEvent::EVENT_CHECKOUT_START, $restaurant_id, $customer_id, $metadata );
	}
}

