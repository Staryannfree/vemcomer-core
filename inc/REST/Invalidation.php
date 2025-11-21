<?php
/**
 * Invalidation — Invalidação simples do cache quando restaurantes/itens mudam
 * @package VemComerCore
 */

namespace VC\REST;

use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use VC\Cache\Cache_Manager;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Invalidation {
    public function init(): void {
        // Sempre que salvar restaurante ou item do cardápio, limpa transients relacionados
        add_action( 'save_post_vc_restaurant', [ $this, 'flush_all' ] );
        add_action( 'save_post_vc_menu_item', [ $this, 'flush_all' ] );
        add_action( 'deleted_post', [ $this, 'flush_all' ] );
        add_action( 'trashed_post', [ $this, 'flush_all' ] );

        // REST API de invalidação
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/cache/invalidate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'invalidate_cache' ],
            'permission_callback' => [ $this, 'check_admin_permission' ],
        ] );
    }

    public function check_admin_permission(): bool {
        return current_user_can( 'manage_options' );
    }

    public function invalidate_cache( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        Cache_Manager::invalidate_all();
        log_event( 'REST cache invalidated via API', [], 'info' );
        return new WP_REST_Response( [ 'success' => true, 'message' => __( 'Cache invalidado com sucesso.', 'vemcomer' ) ], 200 );
    }

    public function flush_all(): void {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vc_rest_cache_%' OR option_name LIKE '_transient_timeout_vc_rest_cache_%'" );
        log_event( 'REST cache invalidated', [], 'info' );
    }
}
