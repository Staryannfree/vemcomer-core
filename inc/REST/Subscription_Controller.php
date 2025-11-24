<?php
/**
 * Subscription_Controller — REST endpoints para planos de assinatura
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Admin\Settings;
use VC\Model\CPT_Restaurant;
use VC\Model\CPT_SubscriptionPlan;
use VC\Subscription\Plan_Manager;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Subscription_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista planos disponíveis (público)
		register_rest_route( 'vemcomer/v1', '/subscription/plans', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_plans' ],
			'permission_callback' => '__return_true',
		] );

		// GET: Plano atual do restaurante (requer autenticação)
		register_rest_route( 'vemcomer/v1', '/subscription/current', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_current_plan' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'restaurant_id' => [
					'required'          => false,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// POST: Upgrade de plano (admin)
		register_rest_route( 'vemcomer/v1', '/subscription/upgrade', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'upgrade_plan' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'restaurant_id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'plan_id'       => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'status'        => [
					'required'          => false,
					'default'           => 'active',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'expires_at'    => [
					'required'          => false,
					'validate_callback' => function( $param ) {
						return empty( $param ) || strtotime( $param ) !== false;
					},
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		// POST: Webhook do Mercado Pago para assinaturas pagas
		register_rest_route( 'vemcomer/v1', '/subscription/webhook/mercadopago', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_mercadopago_webhook' ],
			'permission_callback' => '__return_true', // Verificação é por HMAC
		] );
	}

	public function check_authenticated(): bool {
		return is_user_logged_in();
	}

	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /wp-json/vemcomer/v1/subscription/plans
	 * Lista planos disponíveis
	 */
	public function get_plans( WP_REST_Request $request ): WP_REST_Response {
		$query = new WP_Query( [
			'post_type'      => CPT_SubscriptionPlan::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'   => '_vc_plan_active',
					'value' => '1',
				],
			],
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_vc_plan_monthly_price',
			'order'          => 'ASC',
		] );

		$plans = [];
		foreach ( $query->posts as $post ) {
			$plans[] = [
				'id'                        => $post->ID,
				'name'                      => get_the_title( $post ),
				'description'               => $post->post_content,
				'monthly_price'             => (float) get_post_meta( $post->ID, '_vc_plan_monthly_price', true ),
				'max_menu_items'            => (int) get_post_meta( $post->ID, '_vc_plan_max_menu_items', true ),
				'max_modifiers_per_item'    => (int) get_post_meta( $post->ID, '_vc_plan_max_modifiers_per_item', true ),
				'advanced_analytics'        => (bool) get_post_meta( $post->ID, '_vc_plan_advanced_analytics', true ),
				'priority_support'          => (bool) get_post_meta( $post->ID, '_vc_plan_priority_support', true ),
				'features'                  => json_decode( get_post_meta( $post->ID, '_vc_plan_features', true ), true ) ?: [],
			];
		}

		return new WP_REST_Response( $plans, 200 );
	}

	/**
	 * GET /wp-json/vemcomer/v1/subscription/current
	 * Retorna plano atual do restaurante
	 */
	public function get_current_plan( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'vc_unauthorized', __( 'Você precisa estar autenticado.', 'vemcomer' ), [ 'status' => 401 ] );
		}

		$restaurant_id = $request->get_param( 'restaurant_id' );
		if ( ! $restaurant_id ) {
			// Tentar obter restaurante do usuário
			$restaurant = get_posts( [
				'post_type'      => CPT_Restaurant::SLUG,
				'posts_per_page' => 1,
				'author'         => $user_id,
				'post_status'    => 'any',
			] );

			if ( empty( $restaurant ) ) {
				return new WP_Error( 'vc_no_restaurant', __( 'Nenhum restaurante encontrado para este usuário.', 'vemcomer' ), [ 'status' => 404 ] );
			}

			$restaurant_id = $restaurant[0]->ID;
		}

		$plan = Plan_Manager::get_restaurant_plan( (int) $restaurant_id );

		if ( ! $plan ) {
			return new WP_REST_Response( [
				'has_plan' => false,
				'plan'     => null,
			], 200 );
		}

		return new WP_REST_Response( [
			'has_plan' => true,
			'plan'     => $plan,
		], 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/subscription/upgrade
	 * Atribui/atualiza plano de um restaurante (admin)
	 */
	public function upgrade_plan( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$restaurant_id = (int) $request->get_param( 'restaurant_id' );
		$plan_id       = (int) $request->get_param( 'plan_id' );
		$status        = $request->get_param( 'status' );
		$expires_at    = $request->get_param( 'expires_at' );

		// Verificar se restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error( 'vc_restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		// Verificar se plano existe e está ativo
		$plan = get_post( $plan_id );
		if ( ! $plan || CPT_SubscriptionPlan::SLUG !== $plan->post_type ) {
			return new WP_Error( 'vc_plan_not_found', __( 'Plano não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$plan_active = (bool) get_post_meta( $plan_id, '_vc_plan_active', true );
		if ( ! $plan_active ) {
			return new WP_Error( 'vc_plan_inactive', __( 'Plano não está ativo.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Atribuir plano
		$success = Plan_Manager::assign_plan( $restaurant_id, $plan_id, $status, $expires_at );

		if ( ! $success ) {
			return new WP_Error( 'vc_upgrade_failed', __( 'Erro ao atribuir plano.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		$plan_data = Plan_Manager::get_restaurant_plan( $restaurant_id );

		log_event( 'Subscription upgraded', [
			'restaurant_id' => $restaurant_id,
			'plan_id'       => $plan_id,
			'status'        => $status,
		], 'info' );

		return new WP_REST_Response( [
			'success' => true,
			'plan'    => $plan_data,
		], 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/subscription/webhook/mercadopago
	 * Recebe webhook do Mercado Pago quando uma assinatura é paga
	 * 
	 * Espera JSON do Mercado Pago com estrutura:
	 * {
	 *   "type": "subscription",
	 *   "action": "payment.created" | "payment.updated",
	 *   "data": {
	 *     "id": "subscription_id",
	 *     "external_reference": "restaurant_id:plan_id" ou apenas "restaurant_id",
	 *     "metadata": {
	 *       "restaurant_id": 123,
	 *       "plan_id": 456
	 *     }
	 *   }
	 * }
	 * 
	 * Header: X-VemComer-Signature: sha256=HMAC_HEX(body, payment_secret)
	 */
	public function handle_mercadopago_webhook( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$raw = $request->get_body();
		
		// Verificar configuração do Mercado Pago
		$settings = ( new Settings() )->get();
		$provider = strtolower( (string) ( $settings['payment_provider'] ?? '' ) );
		if ( 'mercadopago' !== $provider ) {
			log_event( 'Mercado Pago webhook recebido mas provider não configurado', [ 'provider' => $provider ], 'warning' );
			return new WP_Error( 'vc_mp_disabled', __( 'Mercado Pago não está configurado como gateway padrão.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Obter secret para verificação HMAC
		$secret = (string) ( $settings['payment_secret'] ?? '' );
		if ( empty( $secret ) ) {
			log_event( 'Mercado Pago webhook secret missing', [], 'error' );
			return new WP_Error( 'vc_mp_missing_secret', __( 'Segredo do webhook não configurado.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		// Verificar assinatura HMAC
		$sig = $request->get_header( 'X-VemComer-Signature' );
		if ( ! $this->verify_signature( $raw, $secret, (string) $sig ) ) {
			log_event( 'Mercado Pago subscription webhook signature mismatch', [], 'error' );
			return new WP_Error( 'vc_bad_signature', __( 'Assinatura inválida.', 'vemcomer' ), [ 'status' => 401 ] );
		}

		// Decodificar payload
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			log_event( 'Mercado Pago subscription webhook payload inválido', [ 'raw' => $raw ], 'error' );
			return new WP_Error( 'vc_bad_payload', __( 'Payload inválido.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Extrair dados do webhook
		$type = (string) ( $data['type'] ?? '' );
		$action = (string) ( $data['action'] ?? '' );
		$webhook_data = $data['data'] ?? [];

		// Verificar se é um webhook de assinatura
		if ( 'subscription' !== $type ) {
			log_event( 'Mercado Pago webhook não é de subscription', [ 'type' => $type ], 'warning' );
			return new WP_Error( 'vc_wrong_type', __( 'Webhook não é de assinatura.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Processar apenas eventos de pagamento criado/atualizado
		if ( ! in_array( $action, [ 'payment.created', 'payment.updated' ], true ) ) {
			log_event( 'Mercado Pago subscription webhook action ignorada', [ 'action' => $action ], 'debug' );
			return new WP_REST_Response( [ 'ok' => true, 'ignored' => true, 'action' => $action ], 200 );
		}

		// Extrair restaurant_id e plan_id
		$restaurant_id = $this->extract_restaurant_id( $webhook_data );
		$plan_id = $this->extract_plan_id( $webhook_data );

		if ( ! $restaurant_id || ! $plan_id ) {
			log_event( 'Mercado Pago subscription webhook sem restaurant_id ou plan_id', [ 'data' => $webhook_data ], 'error' );
			return new WP_Error( 'vc_missing_ids', __( 'Não foi possível identificar restaurante ou plano.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Verificar se restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			log_event( 'Mercado Pago subscription webhook restaurante não encontrado', [ 'restaurant_id' => $restaurant_id ], 'error' );
			return new WP_Error( 'vc_restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		// Verificar se plano existe e está ativo
		$plan = get_post( $plan_id );
		if ( ! $plan || CPT_SubscriptionPlan::SLUG !== $plan->post_type ) {
			log_event( 'Mercado Pago subscription webhook plano não encontrado', [ 'plan_id' => $plan_id ], 'error' );
			return new WP_Error( 'vc_plan_not_found', __( 'Plano não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$plan_active = (bool) get_post_meta( $plan_id, '_vc_plan_active', true );
		if ( ! $plan_active ) {
			log_event( 'Mercado Pago subscription webhook plano inativo', [ 'plan_id' => $plan_id ], 'warning' );
			return new WP_Error( 'vc_plan_inactive', __( 'Plano não está ativo.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Determinar status da assinatura baseado no pagamento
		$status = $this->determine_subscription_status( $webhook_data, $action );
		
		// Calcular data de expiração (1 mês a partir de agora)
		$expires_at = date( 'Y-m-d H:i:s', strtotime( '+1 month' ) );

		// Atualizar plano do restaurante
		$success = Plan_Manager::assign_plan( $restaurant_id, $plan_id, $status, $expires_at );

		if ( ! $success ) {
			log_event( 'Mercado Pago subscription webhook falhou ao atribuir plano', [
				'restaurant_id' => $restaurant_id,
				'plan_id'       => $plan_id,
			], 'error' );
			return new WP_Error( 'vc_assign_failed', __( 'Erro ao atribuir plano.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		// Atualizar também no post meta do restaurante (para consistência)
		update_post_meta( $restaurant_id, '_vc_subscription_plan_id', $plan_id );

		// Salvar metadados do webhook para auditoria
		$user_id = (int) $restaurant->post_author;
		update_user_meta( $user_id, '_vc_mp_subscription_id', (string) ( $webhook_data['id'] ?? '' ) );
		update_user_meta( $user_id, '_vc_mp_subscription_last_webhook', current_time( 'mysql' ) );

		log_event( 'Mercado Pago subscription webhook processado', [
			'restaurant_id' => $restaurant_id,
			'plan_id'       => $plan_id,
			'status'        => $status,
			'action'        => $action,
		], 'info' );

		return new WP_REST_Response( [
			'ok'            => true,
			'restaurant_id' => $restaurant_id,
			'plan_id'       => $plan_id,
			'status'        => $status,
		], 200 );
	}

	/**
	 * Verifica assinatura HMAC do webhook.
	 * 
	 * @param string $raw Corpo bruto da requisição
	 * @param string $secret Secret configurado
	 * @param string $header Header X-VemComer-Signature
	 * @return bool
	 */
	private function verify_signature( string $raw, string $secret, string $header ): bool {
		if ( empty( $header ) ) {
			return false;
		}

		// Suporta formato "sha256=HEX"
		$parts = explode( '=', $header );
		$provided = end( $parts );
		
		// Calcular HMAC
		$calculated = hash_hmac( 'sha256', $raw, $secret );
		
		// Comparação segura contra timing attacks
		return hash_equals( $calculated, $provided );
	}

	/**
	 * Extrai restaurant_id do payload do webhook.
	 * 
	 * Tenta obter de:
	 * 1. metadata.restaurant_id
	 * 2. external_reference (formato "restaurant_id" ou "restaurant_id:plan_id")
	 * 
	 * @param array $webhook_data Dados do webhook
	 * @return int|null
	 */
	private function extract_restaurant_id( array $webhook_data ): ?int {
		// Tentar metadata primeiro
		$metadata = $webhook_data['metadata'] ?? [];
		if ( ! empty( $metadata['restaurant_id'] ) && is_numeric( $metadata['restaurant_id'] ) ) {
			return (int) $metadata['restaurant_id'];
		}

		// Tentar external_reference
		$external_ref = (string) ( $webhook_data['external_reference'] ?? '' );
		if ( ! empty( $external_ref ) ) {
			// Formato pode ser "restaurant_id" ou "restaurant_id:plan_id"
			$parts = explode( ':', $external_ref );
			$maybe_id = trim( $parts[0] );
			if ( is_numeric( $maybe_id ) ) {
				return (int) $maybe_id;
			}
		}

		return null;
	}

	/**
	 * Extrai plan_id do payload do webhook.
	 * 
	 * Tenta obter de:
	 * 1. metadata.plan_id
	 * 2. external_reference (formato "restaurant_id:plan_id")
	 * 
	 * @param array $webhook_data Dados do webhook
	 * @return int|null
	 */
	private function extract_plan_id( array $webhook_data ): ?int {
		// Tentar metadata primeiro
		$metadata = $webhook_data['metadata'] ?? [];
		if ( ! empty( $metadata['plan_id'] ) && is_numeric( $metadata['plan_id'] ) ) {
			return (int) $metadata['plan_id'];
		}

		// Tentar external_reference (formato "restaurant_id:plan_id")
		$external_ref = (string) ( $webhook_data['external_reference'] ?? '' );
		if ( ! empty( $external_ref ) ) {
			$parts = explode( ':', $external_ref );
			if ( count( $parts ) >= 2 ) {
				$maybe_id = trim( $parts[1] );
				if ( is_numeric( $maybe_id ) ) {
					return (int) $maybe_id;
				}
			}
		}

		return null;
	}

	/**
	 * Determina o status da assinatura baseado no webhook.
	 * 
	 * @param array $webhook_data Dados do webhook
	 * @param string $action Ação do webhook (payment.created, payment.updated)
	 * @return string Status: 'active', 'cancelled', 'expired', 'past_due'
	 */
	private function determine_subscription_status( array $webhook_data, string $action ): string {
		// Verificar status do pagamento se disponível
		$payment_status = (string) ( $webhook_data['status'] ?? '' );
		
		// Mapear status do Mercado Pago para status interno
		$status_map = [
			'approved'     => 'active',
			'authorized'   => 'active',
			'in_process'   => 'active',
			'pending'      => 'past_due',
			'rejected'     => 'cancelled',
			'cancelled'    => 'cancelled',
			'refunded'     => 'cancelled',
			'charged_back' => 'cancelled',
		];

		if ( ! empty( $payment_status ) && isset( $status_map[ $payment_status ] ) ) {
			return $status_map[ $payment_status ];
		}

		// Default: se é payment.created ou payment.updated, assumir ativo
		return 'active';
	}
}

