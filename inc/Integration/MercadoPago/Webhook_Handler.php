<?php
namespace VC\Integration\MercadoPago;

use VC\Admin\Settings;
use VC\Logging;
use VC_CPT_Pedido;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler responsável por receber notificações do Mercado Pago
 * e repassar para o webhook interno do VemComer.
 */
class Webhook_Handler {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
        add_action( 'vemcomer_webhook_payment_processed', [ $this, 'handle_processed_hook' ], 10, 2 );
    }

    public function register_routes(): void {
        register_rest_route( 'vemcomer/v1', '/mercadopago/webhook', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_webhook' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function handle_webhook( WP_REST_Request $request ) {
        $settings = ( new Settings() )->get();
        $provider = strtolower( (string) ( $settings['payment_provider'] ?? '' ) );
        if ( 'mercadopago' !== $provider ) {
            return new WP_Error( 'vc_mp_disabled', __( 'Mercado Pago não está configurado como gateway padrão.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $secret = (string) ( $settings['payment_secret'] ?? '' );
        if ( empty( $secret ) ) {
            return new WP_Error( 'vc_mp_missing_secret', __( 'Configure o segredo do webhook em VemComer > Configurações.', 'vemcomer' ), [ 'status' => 500 ] );
        }

        $access_token = (string) ( $settings['payment_mp_access_token'] ?? '' );
        if ( empty( $access_token ) ) {
            return new WP_Error( 'vc_mp_missing_token', __( 'Token do Mercado Pago ausente.', 'vemcomer' ), [ 'status' => 500 ] );
        }

        if ( ! class_exists( '\\MercadoPago\\SDK' ) || ! class_exists( '\\MercadoPago\\Resources\\Payment' ) ) {
            return new WP_Error( 'vc_mp_sdk_missing', __( 'SDK do Mercado Pago não encontrado. Execute "composer require mercadopago/dx-php".', 'vemcomer' ), [ 'status' => 500 ] );
        }

        $notification_id = $this->extract_payment_id( $request );
        if ( ! $notification_id ) {
            Logging\log_event( 'Mercado Pago webhook sem payment id', [ 'body' => $request->get_body() ], 'warning' );
            return new WP_Error( 'vc_mp_missing_id', __( 'Notificação sem identificador do pagamento.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $payment = $this->fetch_payment( (int) $notification_id, $access_token );
        if ( is_wp_error( $payment ) ) {
            return $payment;
        }

        $order_id = $this->resolve_order_id( $payment );
        if ( ! $order_id ) {
            Logging\log_event( 'Mercado Pago sem order_id associado', [ 'payment_id' => $payment->id ?? '', 'external_reference' => $payment->external_reference ?? null ], 'error' );
            return new WP_Error( 'vc_mp_missing_order', __( 'Não foi possível associar o pagamento a um pedido do VemComer.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        $payload = $this->build_payload( $order_id, $payment );
        $result  = $this->dispatch_payment_webhook( $payload, $secret );

        if ( is_wp_error( $result ) ) {
            Logging\log_event( 'Mercado Pago webhook falhou ao reenviar', [ 'order_id' => $order_id, 'error' => $result->get_error_message() ], 'error' );
            return $result;
        }

        $this->store_payment_meta( $order_id, $payment );

        return new WP_REST_Response( [ 'ok' => true, 'forwarded' => true ] );
    }

    private function extract_payment_id( WP_REST_Request $request ): int {
        $json = $request->get_json_params();
        $id   = $json['data']['id'] ?? $json['data_id'] ?? $json['id'] ?? $request->get_param( 'id' );
        if ( empty( $id ) && ! empty( $json['resource'] ) ) {
            $parts = explode( '/', (string) $json['resource'] );
            $id    = end( $parts );
        }
        return is_numeric( $id ) ? (int) $id : 0;
    }

    private function fetch_payment( int $payment_id, string $token ) {
        try {
            \MercadoPago\SDK::setAccessToken( $token );
            $payment = \MercadoPago\Resources\Payment::find_by_id( $payment_id );
        } catch ( \Throwable $e ) {
            Logging\log_event( 'Erro ao consultar pagamento Mercado Pago', [ 'payment_id' => $payment_id, 'message' => $e->getMessage() ], 'error' );
            return new WP_Error( 'vc_mp_payment_fetch_error', __( 'Falha ao validar o pagamento junto ao Mercado Pago.', 'vemcomer' ), [ 'status' => 500 ] );
        }

        if ( empty( $payment ) ) {
            return new WP_Error( 'vc_mp_payment_not_found', __( 'Pagamento não encontrado no Mercado Pago.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        return $payment;
    }

    private function resolve_order_id( $payment ): int {
        $external_reference = isset( $payment->external_reference ) ? (string) $payment->external_reference : '';
        if ( $external_reference && ctype_digit( $external_reference ) ) {
            $maybe_order = (int) $external_reference;
            if ( $this->is_valid_order( $maybe_order ) ) {
                return $maybe_order;
            }
        }

        if ( ! empty( $payment->metadata->vemcomer_order_id ) && is_numeric( $payment->metadata->vemcomer_order_id ) ) {
            $meta_order = (int) $payment->metadata->vemcomer_order_id;
            if ( $this->is_valid_order( $meta_order ) ) {
                return $meta_order;
            }
        }

        if ( $external_reference ) {
            $orders = get_posts( [
                'post_type'      => VC_CPT_Pedido::SLUG,
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => '_vc_mp_external_reference',
                'meta_value'     => $external_reference,
            ] );
            if ( ! empty( $orders ) ) {
                return (int) $orders[0];
            }
        }

        return 0;
    }

    private function is_valid_order( int $order_id ): bool {
        if ( $order_id <= 0 ) {
            return false;
        }
        $post = get_post( $order_id );
        return $post instanceof WP_Post && VC_CPT_Pedido::SLUG === $post->post_type;
    }

    private function build_payload( int $order_id, $payment ): array {
        $status = strtolower( (string) ( $payment->status ?? '' ) );
        $mapped = match ( $status ) {
            'approved', 'in_process', 'in_mediation' => 'paid',
            'refunded', 'charged_back', 'cancelled'   => 'refunded',
            'rejected'                               => 'failed',
            default                                  => 'failed',
        };

        $amount     = isset( $payment->transaction_amount ) ? (float) $payment->transaction_amount : 0.0;
        $timestamp  = isset( $payment->date_approved ) ? strtotime( (string) $payment->date_approved ) : null;
        $timestamp  = $timestamp ?: time();
        $formatted  = number_format( $amount, 2, ',', '.' );

        return [
            'order_id'       => $order_id,
            'status'         => $mapped,
            'amount'         => $formatted,
            'ts'             => $timestamp,
            'provider'       => 'mercadopago',
            'mp_payment_id'  => isset( $payment->id ) ? (string) $payment->id : '',
            'mp_status'      => $status,
            'external_reference' => isset( $payment->external_reference ) ? (string) $payment->external_reference : '',
        ];
    }

    private function dispatch_payment_webhook( array $payload, string $secret ) {
        $body = wp_json_encode( $payload );
        if ( false === $body ) {
            return new WP_Error( 'vc_mp_payload_encode', __( 'Falha ao preparar payload do webhook.', 'vemcomer' ) );
        }

        $signature = hash_hmac( 'sha256', $body, $secret );
        $response  = wp_remote_post( rest_url( 'vemcomer/v1/webhook/payment' ), [
            'headers' => [
                'Content-Type'          => 'application/json',
                'X-VemComer-Signature'  => 'sha256=' . $signature,
            ],
            'body'    => $body,
            'timeout' => 20,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 400 ) {
            $message = wp_remote_retrieve_body( $response );
            return new WP_Error( 'vc_mp_webhook_error', sprintf( __( 'Webhook interno retornou HTTP %d', 'vemcomer' ), $code ), [ 'body' => $message ] );
        }

        return true;
    }

    private function store_payment_meta( int $order_id, $payment ): void {
        if ( isset( $payment->id ) ) {
            update_post_meta( $order_id, '_vc_mp_payment_id', (string) $payment->id );
        }
        if ( isset( $payment->status ) ) {
            update_post_meta( $order_id, '_vc_mp_status', (string) $payment->status );
        }
        if ( isset( $payment->external_reference ) ) {
            update_post_meta( $order_id, '_vc_mp_external_reference', (string) $payment->external_reference );
        }
    }

    public function handle_processed_hook( int $order_id, array $payload ): void {
        if ( empty( $payload['provider'] ) || 'mercadopago' !== $payload['provider'] ) {
            return;
        }

        do_action( 'vemcomer_mercadopago_payment_processed', $order_id, $payload );
        Logging\log_event( 'Mercado Pago pagamento conciliado', [ 'order_id' => $order_id, 'status' => $payload['status'] ?? '' ], 'info' );
    }
}
