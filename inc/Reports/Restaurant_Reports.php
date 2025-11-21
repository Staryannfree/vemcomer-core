<?php
/**
 * Restaurant_Reports — Gerador de relatórios avançados para restaurantes
 * @package VemComerCore
 */

namespace VC\Reports;

use VC_CPT_Pedido;
use VC\Model\CPT_Restaurant;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Restaurant_Reports {
	/**
	 * Gera relatório de vendas.
	 *
	 * @param int    $restaurant_id ID do restaurante
	 * @param string $period Período: today, week, month, custom
	 * @param string|null $start_date Data de início (se custom)
	 * @param string|null $end_date Data de fim (se custom)
	 * @param string $grouping Agrupamento: day, week, month
	 * @return array
	 */
	public static function get_sales_report( int $restaurant_id, string $period = 'month', ?string $start_date = null, ?string $end_date = null, string $grouping = 'day' ): array {
		$now = current_time( 'timestamp' );
		$query_start = 0;
		$query_end = $now;

		switch ( $period ) {
			case 'today':
				$query_start = strtotime( 'today', $now );
				break;
			case 'week':
				$query_start = strtotime( '-7 days', $now );
				break;
			case 'month':
				$query_start = strtotime( '-30 days', $now );
				break;
			case 'custom':
				if ( $start_date ) {
					$query_start = strtotime( $start_date );
				}
				if ( $end_date ) {
					$query_end = strtotime( $end_date );
				}
				break;
		}

		// Buscar pedidos do restaurante no período
		$query = new WP_Query( [
			'post_type'      => VC_CPT_Pedido::SLUG,
			'posts_per_page' => -1,
			'post_status'    => [ 'vc-pending', 'vc-paid', 'vc-preparing', 'vc-delivering', 'vc-completed' ],
			'meta_query'     => [
				[
					'key'   => '_vc_restaurant_id',
					'value' => (string) $restaurant_id,
				],
			],
			'date_query'     => [
				[
					'after'  => date( 'Y-m-d H:i:s', $query_start ),
					'before' => date( 'Y-m-d H:i:s', $query_end ),
				],
			],
		] );

		$total_orders = 0;
		$total_revenue = 0.0;
		$orders_by_status = [];
		$orders_by_date = [];

		foreach ( $query->posts as $order ) {
			$status = get_post_status( $order );
			$total = (float) get_post_meta( $order->ID, '_vc_total', true );

			$total_orders++;
			$total_revenue += $total;

			$orders_by_status[ $status ] = ( $orders_by_status[ $status ] ?? 0 ) + 1;

			$order_date = date( 'Y-m-d', strtotime( $order->post_date ) );
			if ( ! isset( $orders_by_date[ $order_date ] ) ) {
				$orders_by_date[ $order_date ] = [ 'count' => 0, 'revenue' => 0.0 ];
			}
			$orders_by_date[ $order_date ]['count']++;
			$orders_by_date[ $order_date ]['revenue'] += $total;
		}

		$average_ticket = $total_orders > 0 ? $total_revenue / $total_orders : 0.0;

		return [
			'period'          => $period,
			'start_date'      => date( 'Y-m-d H:i:s', $query_start ),
			'end_date'        => date( 'Y-m-d H:i:s', $query_end ),
			'total_orders'    => $total_orders,
			'total_revenue'   => $total_revenue,
			'average_ticket'  => $average_ticket,
			'orders_by_status' => $orders_by_status,
			'orders_by_date'  => $orders_by_date,
		];
	}

	/**
	 * Gera relatório de analytics.
	 *
	 * @param int    $restaurant_id ID do restaurante
	 * @param string $period Período: today, week, month, custom
	 * @param string|null $start_date Data de início (se custom)
	 * @param string|null $end_date Data de fim (se custom)
	 * @return array
	 */
	public static function get_analytics_report( int $restaurant_id, string $period = 'month', ?string $start_date = null, ?string $end_date = null ): array {
		// Usar Analytics_Controller se disponível
		if ( class_exists( '\\VC\\Analytics\\Analytics_Controller' ) ) {
			$controller = new \VC\Analytics\Analytics_Controller();
			$request = new \WP_REST_Request( 'GET', '/wp-json/vemcomer/v1/restaurants/' . $restaurant_id . '/analytics' );
			$request->set_param( 'period', $period );
			if ( $start_date ) {
				$request->set_param( 'start_date', $start_date );
			}
			if ( $end_date ) {
				$request->set_param( 'end_date', $end_date );
			}

			$response = $controller->get_restaurant_analytics( $request );
			if ( ! is_wp_error( $response ) ) {
				return $response->get_data();
			}
		}

		return [
			'error' => __( 'Analytics não disponível.', 'vemcomer' ),
		];
	}

	/**
	 * Gera relatório de itens mais vendidos.
	 *
	 * @param int    $restaurant_id ID do restaurante
	 * @param string $period Período
	 * @return array
	 */
	public static function get_top_items( int $restaurant_id, string $period = 'month' ): array {
		// Esta funcionalidade requer análise dos pedidos
		// Por enquanto, retornar estrutura vazia
		return [
			'items' => [],
		];
	}
}

