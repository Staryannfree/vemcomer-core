<?php
/**
 * Webhooks_Controller — Endpoints para webhooks (pagamento)
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Admin\Settings;
use VC_CPT_Pedido;
use WP_Error;
use WP_REST_Request;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Webhooks_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/webhook/payment', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_payment' ],
            'permission_callback' => '__return_true', // verificação é por assinatura
        ] );
    }

    /**
     * Espera JSON: { "order_id": int, "status": "paid|failed|refunded", "amount": "99,90", "ts": 1690000000 }
     * Header: X-VemComer-Signature: sha256=HMAC_HEX( body, payment_secret )
     */
    public function handle_payment( WP_REST_Request $request ) {
        $raw = $request->get_body();
        $settings = ( new Settings() )->get();
        $secret = (string) ( $settings['payment_secret'] ?? '' );
        if ( empty( $secret ) ) {
            log_event( 'Webhook secret missing', [], 'error' );
            return new WP_Error( 'vc_no_secret', __( 'Segredo não configurado.', 'vemcomer' ), [ 'status' => 500 ] );
        }

        $sig = $request->get_header( 'X-VemComer-Signature' );
        if ( ! $this->verify_signature( $raw, $secret, (string) $sig ) ) {
            log_event( 'Webhook signature mismatch', [ 'order_id' => (int) $request->get_param( 'order_id' ) ], 'error' );
            return new WP_Error( 'vc_bad_signature', __( 'Assinatura inválida.', 'vemcomer' ), [ 'status' => 401 ] );
        }

        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) || empty( $data['order_id'] ) ) {
            log_event( 'Webhook payload inválido', [ 'raw' => $raw ], 'error' );
            return new WP_Error( 'vc_bad_payload', __( 'Payload inválido.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $order_id = (int) $data['order_id'];
        $status   = (string) ( $data['status'] ?? '' );

        if ( 'paid' === $status ) {
            // Marca como pago
            $this->set_status( $order_id, 'vc-paid' );
        } elseif ( 'refunded' === $status ) {
            $this->set_status( $order_id, 'vc-cancelled' );
        }

        log_event( 'Webhook processed', [ 'order_id' => $order_id, 'status' => $status ], 'info' );
        do_action( 'vemcomer_webhook_payment_processed', $order_id, $data );

        return rest_ensure_response( [ 'ok' => true ] );
    }

    private function verify_signature( string $raw, string $secret, string $header ): bool {
        if ( empty( $header ) ) { return false; }
        // Suporta formato "sha256=HEX"
        $parts = explode( '=', $header );
        $provided = end( $parts );
        $calc = hash_hmac( 'sha256', $raw, $secret );
        return hash_equals( $calc, $provided );
    }

    private function set_status( int $order_id, string $status ): void {
        global $wpdb;
        $wpdb->update( $wpdb->posts, [ 'post_status' => $status ], [ 'ID' => $order_id ] );
        clean_post_cache( $order_id );
        log_event( 'Webhook set status', [ 'order_id' => $order_id, 'status' => $status ], 'info' );
    }
}
