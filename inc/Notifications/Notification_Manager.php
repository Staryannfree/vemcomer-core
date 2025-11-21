<?php
/**
 * Notification_Manager — Gerenciador de notificações
 * @package VemComerCore
 */

namespace VC\Notifications;

use VC\Model\CPT_Restaurant;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Notification_Manager {
	// Tipos de notificação
	public const TYPE_NEW_ORDER = 'new_order';
	public const TYPE_ORDER_STATUS_UPDATED = 'order_status_updated';
	public const TYPE_FAVORITE_RESTAURANT_OPENED = 'favorite_restaurant_opened';
	public const TYPE_PROMOTION_AVAILABLE = 'promotion_available';

	/**
	 * Meta key para notificações do usuário
	 */
	private const META_NOTIFICATIONS = 'vc_notifications';

	/**
	 * Cria uma notificação para um usuário.
	 *
	 * @param int    $user_id ID do usuário
	 * @param string $type Tipo da notificação
	 * @param string $title Título da notificação
	 * @param string $message Mensagem da notificação
	 * @param array  $data Dados adicionais (opcional)
	 * @return string ID da notificação
	 */
	public static function create( int $user_id, string $type, string $title, string $message, array $data = [] ): string {
		$notifications = self::get_all( $user_id );

		$notification_id = uniqid( 'notif_', true );
		$notification = [
			'id'        => $notification_id,
			'type'      => $type,
			'title'     => $title,
			'message'   => $message,
			'data'      => $data,
			'read'      => false,
			'created_at' => time(),
		];

		$notifications[] = $notification;

		// Manter apenas as últimas 100 notificações
		if ( count( $notifications ) > 100 ) {
			$notifications = array_slice( $notifications, -100 );
		}

		update_user_meta( $user_id, self::META_NOTIFICATIONS, $notifications );

		log_event( 'Notification created', [
			'user_id' => $user_id,
			'type'    => $type,
		], 'info' );

		return $notification_id;
	}

	/**
	 * Notifica restaurante sobre novo pedido.
	 */
	public static function notify_new_order( int $restaurant_id, int $order_id ): void {
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant ) {
			return;
		}

		$user_id = (int) $restaurant->post_author;
		if ( ! $user_id ) {
			return;
		}

		self::create(
			$user_id,
			self::TYPE_NEW_ORDER,
			__( 'Novo Pedido Recebido', 'vemcomer' ),
			sprintf( __( 'Você recebeu um novo pedido #%d', 'vemcomer' ), $order_id ),
			[ 'order_id' => $order_id, 'restaurant_id' => $restaurant_id ]
		);
	}

	/**
	 * Notifica cliente sobre atualização de status do pedido.
	 */
	public static function notify_order_status_updated( int $customer_id, int $order_id, string $status ): void {
		$status_labels = [
			'vc-pending'    => __( 'Pendente', 'vemcomer' ),
			'vc-paid'       => __( 'Pago', 'vemcomer' ),
			'vc-preparing'  => __( 'Preparando', 'vemcomer' ),
			'vc-delivering' => __( 'Em entrega', 'vemcomer' ),
			'vc-completed'  => __( 'Concluído', 'vemcomer' ),
			'vc-cancelled'  => __( 'Cancelado', 'vemcomer' ),
		];

		$status_label = $status_labels[ $status ] ?? $status;

		self::create(
			$customer_id,
			self::TYPE_ORDER_STATUS_UPDATED,
			__( 'Status do Pedido Atualizado', 'vemcomer' ),
			sprintf( __( 'Seu pedido #%d está agora: %s', 'vemcomer' ), $order_id, $status_label ),
			[ 'order_id' => $order_id, 'status' => $status ]
		);
	}

	/**
	 * Notifica clientes quando restaurante favorito abre.
	 */
	public static function notify_favorite_restaurant_opened( int $restaurant_id ): void {
		// Buscar usuários que têm este restaurante nos favoritos
		$users = get_users( [
			'meta_query' => [
				[
					'key'     => 'vc_favorite_restaurants',
					'value'   => (string) $restaurant_id,
					'compare' => 'LIKE',
				],
			],
		] );

		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant ) {
			return;
		}

		foreach ( $users as $user ) {
			self::create(
				$user->ID,
				self::TYPE_FAVORITE_RESTAURANT_OPENED,
				__( 'Restaurante Favorito Abriu', 'vemcomer' ),
				sprintf( __( '%s está aberto agora!', 'vemcomer' ), get_the_title( $restaurant ) ),
				[ 'restaurant_id' => $restaurant_id ]
			);
		}
	}

	/**
	 * Obtém todas as notificações do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @return array Array de notificações
	 */
	public static function get_all( int $user_id ): array {
		$notifications = get_user_meta( $user_id, self::META_NOTIFICATIONS, true );
		if ( ! is_array( $notifications ) ) {
			return [];
		}
		// Ordenar por data (mais recentes primeiro)
		usort( $notifications, function( $a, $b ) {
			return ( $b['created_at'] ?? 0 ) <=> ( $a['created_at'] ?? 0 );
		} );
		return $notifications;
	}

	/**
	 * Obtém notificações não lidas do usuário.
	 *
	 * @param int $user_id ID do usuário
	 * @return array Array de notificações não lidas
	 */
	public static function get_unread( int $user_id ): array {
		$all = self::get_all( $user_id );
		return array_filter( $all, function( $notif ) {
			return ! ( $notif['read'] ?? false );
		} );
	}

	/**
	 * Marca notificação como lida.
	 *
	 * @param int    $user_id ID do usuário
	 * @param string $notification_id ID da notificação
	 * @return bool True se marcada, false se não encontrada
	 */
	public static function mark_as_read( int $user_id, string $notification_id ): bool {
		$notifications = self::get_all( $user_id );

		foreach ( $notifications as &$notif ) {
			if ( isset( $notif['id'] ) && $notif['id'] === $notification_id ) {
				$notif['read'] = true;
				update_user_meta( $user_id, self::META_NOTIFICATIONS, $notifications );
				return true;
			}
		}

		return false;
	}

	/**
	 * Marca todas as notificações como lidas.
	 *
	 * @param int $user_id ID do usuário
	 */
	public static function mark_all_as_read( int $user_id ): void {
		$notifications = self::get_all( $user_id );

		foreach ( $notifications as &$notif ) {
			$notif['read'] = true;
		}

		update_user_meta( $user_id, self::META_NOTIFICATIONS, $notifications );
	}
}

