<?php
/**
 * Reports_Controller — REST endpoints para relatórios
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_Restaurant;
use VC\Reports\Restaurant_Reports;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Reports_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Relatório de vendas
		register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/reports/sales', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_sales_report' ],
			'permission_callback' => [ $this, 'check_restaurant_owner_or_admin' ],
			'args'                => [
				'id'         => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'period'     => [
					'required'          => false,
					'default'           => 'month',
					'sanitize_callback' => 'sanitize_key',
				],
				'start_date' => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'end_date'   => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'grouping'   => [
					'required'          => false,
					'default'           => 'day',
					'sanitize_callback' => 'sanitize_key',
				],
			],
		] );

		// GET: Relatório de analytics
		register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/reports/analytics', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_analytics_report' ],
			'permission_callback' => [ $this, 'check_restaurant_owner_or_admin' ],
			'args'                => [
				'id'         => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'period'     => [
					'required'          => false,
					'default'           => 'month',
					'sanitize_callback' => 'sanitize_key',
				],
				'start_date' => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'end_date'   => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	public function check_restaurant_owner_or_admin( WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$restaurant_id = (int) $request->get_param( 'id' );
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$restaurant_owner_id = (int) get_post_meta( $restaurant_id, '_vc_restaurant_owner_id', true );
		return $user_id === $restaurant_owner_id;
	}

	public function get_sales_report( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$restaurant_id = (int) $request->get_param( 'id' );
		$period = $request->get_param( 'period' );
		$start_date = $request->get_param( 'start_date' );
		$end_date = $request->get_param( 'end_date' );
		$grouping = $request->get_param( 'grouping' );

		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error( 'vc_restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$report = Restaurant_Reports::get_sales_report( $restaurant_id, $period, $start_date, $end_date, $grouping );

		return new WP_REST_Response( $report, 200 );
	}

	public function get_analytics_report( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$restaurant_id = (int) $request->get_param( 'id' );
		$period = $request->get_param( 'period' );
		$start_date = $request->get_param( 'start_date' );
		$end_date = $request->get_param( 'end_date' );

		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error( 'vc_restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$report = Restaurant_Reports::get_analytics_report( $restaurant_id, $period, $start_date, $end_date );

		return new WP_REST_Response( $report, 200 );
	}
}

