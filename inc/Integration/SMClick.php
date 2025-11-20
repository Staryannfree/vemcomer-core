<?php
namespace VC\Integration;

use VC\Admin\Settings;
use WP_Post;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SMClick {
    public function init(): void {
        add_action( 'vemcomer/restaurant_registered', [ $this, 'notify_registration' ], 10, 1 );
        add_action( 'transition_post_status', [ $this, 'handle_status_transition' ], 10, 3 );
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

    private function dispatch_event( string $event, int $post_id ): void {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $post = get_post( $post_id );
        if ( ! $post || 'vc_restaurant' !== $post->post_type ) {
            return;
        }

        $payload = [
            'event'        => $event,
            'variant'      => $event,
            'triggered_at' => current_time( 'mysql', true ),
            'restaurant'   => $this->build_restaurant_payload( $post ),
            'source'       => 'vemcomer-core',
        ];

        $response = wp_remote_post(
            $this->get_webhook_url(),
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

    private function get_terms_names( int $post_id, string $taxonomy ): array {
        $terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'names' ] );
        if ( is_wp_error( $terms ) ) {
            return [];
        }

        return array_values( array_filter( array_map( 'sanitize_text_field', $terms ) ) );
    }

    private function is_enabled(): bool {
        $settings = $this->get_settings();
        $url      = $this->get_webhook_url();

        return ! empty( $settings['smclick_enabled'] ) && ! empty( $url );
    }

    private function get_settings(): array {
        return ( new Settings() )->get();
    }

    private function get_webhook_url(): string {
        $settings = $this->get_settings();
        $url      = isset( $settings['smclick_webhook_url'] ) ? (string) $settings['smclick_webhook_url'] : '';

        return (string) apply_filters( 'vemcomer/smclick_webhook_url', $url, $settings );
    }
}
