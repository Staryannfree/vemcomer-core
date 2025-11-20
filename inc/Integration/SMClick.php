<?php
namespace VC\Integration;

use VC\Admin\Settings;
use WP_Error;
use WP_Post;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SMClick {
    private const ORDER_STATUSES = [
        'vc-pending'    => 'Pendente',
        'vc-paid'       => 'Pago',
        'vc-preparing'  => 'Preparando',
        'vc-delivering' => 'Em entrega',
        'vc-completed'  => 'Concluído',
        'vc-cancelled'  => 'Cancelado',
    ];

    public function init(): void {
        add_action( 'vemcomer/restaurant_registered', [ $this, 'notify_registration' ], 10, 1 );
        add_action( 'transition_post_status', [ $this, 'handle_status_transition' ], 10, 3 );
        add_action( 'vemcomer/order_status_changed', [ $this, 'notify_order_status' ], 10, 3 );
    }

    public function notify_registration( int $post_id ): void {
        $this->dispatch_event( 'restaurant_registered', $post_id );
    }

    public function handle_status_transition( string $new_status, string $old_status, WP_Post $post ): void {
        if ( 'vc_restaurant' !== $post->post_type ) {
            return;
        }

        if ( 'publish' === $new_status && 'publish' !== $old_status ) {
            $this->dispatch_event( 'restaurant_approved', (int) $post->ID );
        }
    }

    public function notify_order_status( int $order_id, string $new_status, string $old_status ): void {
        if ( ! isset( self::ORDER_STATUSES[ $new_status ] ) ) {
            return;
        }

        $this->dispatch_event( 'order_' . $new_status, $order_id );
    }

    private function dispatch_event( string $event, int $post_id ): void {
        if ( ! $this->can_dispatch( $event ) ) {
            return;
        }

        $definition = self::event_definitions()[ $event ] ?? null;
        if ( ! $definition ) {
            return;
        }

        $payload = $this->build_payload( $event, $post_id, $definition['type'] );
        if ( is_wp_error( $payload ) ) {
            log_event( 'SMClick webhook skipped', [ 'event' => $event, 'id' => $post_id, 'error' => $payload->get_error_message() ], 'warning' );
            return;
        }

        $response = wp_remote_post(
            $this->get_webhook_url( $event ),
            [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            log_event( 'SMClick webhook error', [ 'event' => $event, 'id' => $post_id, 'error' => $response->get_error_message() ], 'error' );
            return;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            log_event(
                'SMClick webhook non-2xx',
                [
                    'event'  => $event,
                    'id'     => $post_id,
                    'status' => $code,
                    'body'   => wp_remote_retrieve_body( $response ),
                ],
                'warning'
            );
            return;
        }

        log_event( 'SMClick webhook dispatched', [ 'event' => $event, 'id' => $post_id, 'status' => $code ], 'info' );
    }

    private function build_restaurant_payload( WP_Post $post ): array {
        $cuisines  = $this->get_terms_names( $post->ID, 'vc_cuisine' );
        $locations = $this->get_terms_names( $post->ID, 'vc_location' );

        return [
            'id'         => (int) $post->ID,
            'name'       => get_the_title( $post ),
            'status'     => $post->post_status,
            'cnpj'       => get_post_meta( $post->ID, 'vc_restaurant_cnpj', true ),
            'whatsapp'   => get_post_meta( $post->ID, 'vc_restaurant_whatsapp', true ),
            'site'       => get_post_meta( $post->ID, 'vc_restaurant_site', true ),
            'address'    => get_post_meta( $post->ID, 'vc_restaurant_address', true ),
            'open_hours' => get_post_meta( $post->ID, 'vc_restaurant_open_hours', true ),
            'delivery'   => '1' === get_post_meta( $post->ID, 'vc_restaurant_delivery', true ),
            'cuisines'   => $cuisines,
            'locations'  => $locations,
            'permalink'  => get_permalink( $post ),
            'created_at' => $post->post_date_gmt,
            'updated_at' => $post->post_modified_gmt,
        ];
    }

    private function build_order_payload( WP_Post $post ): array {
        $status       = get_post_status( $post );
        $restaurant   = (int) get_post_meta( $post->ID, '_vc_restaurant_id', true );
        $items        = (array) get_post_meta( $post->ID, '_vc_itens', true );

        return [
            'id'            => (int) $post->ID,
            'status'        => $status,
            'status_label'  => $this->status_label( $status ),
            'restaurant_id' => $restaurant,
            'subtotal'      => (string) get_post_meta( $post->ID, '_vc_subtotal', true ),
            'ship_total'    => (string) get_post_meta( $post->ID, '_vc_ship_total', true ),
            'total'         => (string) get_post_meta( $post->ID, '_vc_total', true ),
            'items'         => $items,
            'fulfillment'   => [
                'method' => (string) get_post_meta( $post->ID, '_vc_ship_method', true ),
                'label'  => (string) get_post_meta( $post->ID, '_vc_ship_label', true ),
                'eta'    => get_post_meta( $post->ID, '_vc_ship_eta', true ),
            ],
            'created_at'    => $post->post_date_gmt,
            'updated_at'    => $post->post_modified_gmt,
        ];
    }

    private function get_terms_names( int $post_id, string $taxonomy ): array {
        $terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'names' ] );
        if ( is_wp_error( $terms ) ) {
            return [];
        }

        return array_values( array_filter( array_map( 'sanitize_text_field', $terms ) ) );
    }

    private function can_dispatch( string $event ): bool {
        $settings = $this->get_settings();
        $url      = $this->get_webhook_url( $event );

        return ! empty( $settings['smclick_enabled'] ) && ! empty( $url );
    }

    private function get_settings(): array {
        return ( new Settings() )->get();
    }

    private function get_webhook_url( string $event ): string {
        $settings = $this->get_settings();
        $urls     = is_array( $settings['smclick_event_urls'] ?? null ) ? $settings['smclick_event_urls'] : [];

        $url = $urls[ $event ] ?? '';
        if ( '' === $url ) {
            $url = isset( $settings['smclick_webhook_url'] ) ? (string) $settings['smclick_webhook_url'] : '';
        }

        return (string) apply_filters( 'vemcomer/smclick_webhook_url', $url, $settings, $event );
    }

    private function build_payload( string $event, int $post_id, string $type ) {
        $post = get_post( $post_id );

        if ( 'restaurant' === $type ) {
            if ( ! $post || 'vc_restaurant' !== $post->post_type ) {
                return new WP_Error( 'vc_smclick_invalid_restaurant', __( 'Restaurante inválido para webhook.', 'vemcomer' ) );
            }

            return [
                'event'        => $event,
                'variant'      => $event,
                'triggered_at' => current_time( 'mysql', true ),
                'restaurant'   => $this->build_restaurant_payload( $post ),
                'source'       => 'vemcomer-core',
            ];
        }

        if ( 'order' === $type ) {
            if ( ! $post || 'vc_pedido' !== $post->post_type ) {
                return new WP_Error( 'vc_smclick_invalid_order', __( 'Pedido inválido para webhook.', 'vemcomer' ) );
            }

            return [
                'event'        => $event,
                'variant'      => $event,
                'triggered_at' => current_time( 'mysql', true ),
                'order'        => $this->build_order_payload( $post ),
                'source'       => 'vemcomer-core',
            ];
        }

        return new WP_Error( 'vc_smclick_invalid_type', __( 'Tipo de evento desconhecido.', 'vemcomer' ) );
    }

    private function status_label( string $status ): string {
        return self::ORDER_STATUSES[ $status ] ?? $status;
    }

    public static function default_event_urls(): array {
        $defaults = [
            'restaurant_registered' => 'https://api.smclick.com.br/integration/wordpress/892b64fa-3437-4430-a4bf-2bc9d3f69f1f/',
            'restaurant_approved'   => '',
        ];

        foreach ( array_keys( self::ORDER_STATUSES ) as $status ) {
            $defaults[ 'order_' . $status ] = '';
        }

        return $defaults;
    }

    public static function event_definitions(): array {
        $restaurant_placeholders = [ 'id', 'name', 'status', 'cnpj', 'whatsapp', 'site', 'address', 'open_hours', 'delivery', 'cuisines', 'locations', 'permalink', 'created_at', 'updated_at' ];
        $order_placeholders      = [ 'id', 'status', 'status_label', 'restaurant_id', 'subtotal', 'ship_total', 'total', 'items', 'fulfillment.method', 'fulfillment.label', 'fulfillment.eta', 'created_at', 'updated_at' ];

        $definitions = [
            'restaurant_registered' => [
                'label'          => __( 'Cadastro recebido (aguardando aprovação)', 'vemcomer' ),
                'type'           => 'restaurant',
                'description'    => __( 'Dispara quando um restaurante envia o formulário, status pendente.', 'vemcomer' ),
                'placeholders'   => $restaurant_placeholders,
                'placeholder_url' => 'https://api.smclick.com.br/integration/wordpress/<token>/pendente',
            ],
            'restaurant_approved'   => [
                'label'          => __( 'Restaurante aprovado', 'vemcomer' ),
                'type'           => 'restaurant',
                'description'    => __( 'Enviado quando o status do restaurante muda para publicado.', 'vemcomer' ),
                'placeholders'   => $restaurant_placeholders,
                'placeholder_url' => 'https://api.smclick.com.br/integration/wordpress/<token>/aprovado',
            ],
        ];

        foreach ( self::ORDER_STATUSES as $status => $label ) {
            $definitions[ 'order_' . $status ] = [
                'label'          => sprintf( __( 'Pedido: %s', 'vemcomer' ), $label ),
                'type'           => 'order',
                'description'    => __( 'Enviado quando o status do pedido muda.', 'vemcomer' ),
                'placeholders'   => $order_placeholders,
                'placeholder_url' => 'https://api.smclick.com.br/integration/wordpress/<token>/pedido',
            ];
        }

        return $definitions;
    }

    public static function sample_payload( string $event ) {
        $definitions = self::event_definitions();
        if ( ! isset( $definitions[ $event ] ) ) {
            return new WP_Error( 'vc_smclick_unknown_event', __( 'Evento SMClick desconhecido.', 'vemcomer' ) );
        }

        $base = [
            'event'        => $event,
            'variant'      => $event,
            'triggered_at' => current_time( 'mysql', true ),
            'source'       => 'vemcomer-core',
        ];

        if ( 'restaurant' === $definitions[ $event ]['type'] ) {
            $base['restaurant'] = [
                'id'         => 999,
                'name'       => 'Restaurante Exemplo',
                'status'     => 'pending',
                'cnpj'       => '12.345.678/0001-99',
                'whatsapp'   => '+55 11 90000-0000',
                'site'       => 'https://restaurante-exemplo.test',
                'address'    => 'Rua das Flores, 123, Centro',
                'open_hours' => 'Seg-Sex 11h-22h',
                'delivery'   => true,
                'cuisines'   => [ 'Pizza', 'Massas' ],
                'locations'  => [ 'São Paulo' ],
                'permalink'  => 'https://seusite.com/restaurantes/exemplo',
                'created_at' => current_time( 'mysql', true ),
                'updated_at' => current_time( 'mysql', true ),
            ];
        }

        if ( 'order' === $definitions[ $event ]['type'] ) {
            $base['order'] = [
                'id'            => 1234,
                'status'        => 'vc-preparing',
                'status_label'  => self::ORDER_STATUSES['vc-preparing'],
                'restaurant_id' => 55,
                'subtotal'      => '79,90',
                'ship_total'    => '10,00',
                'total'         => '89,90',
                'items'         => [
                    [ 'product_id' => 1, 'name' => 'Pizza Margherita', 'qty' => 1, 'price' => '39,90' ],
                    [ 'product_id' => 2, 'name' => 'Lasanha', 'qty' => 1, 'price' => '40,00' ],
                ],
                'fulfillment'   => [ 'method' => 'delivery', 'label' => 'Entrega padrão', 'eta' => '40min' ],
                'created_at'    => current_time( 'mysql', true ),
                'updated_at'    => current_time( 'mysql', true ),
            ];
        }

        return $base;
    }
}
