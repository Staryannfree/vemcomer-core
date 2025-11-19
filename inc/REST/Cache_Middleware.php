<?php
/**
 * Cache_Middleware — Cache de endpoints públicos via transients
 * Aplica cache a requisições GET em rotas:
 *  - /vemcomer/v1/restaurants
 *  - /vemcomer/v1/restaurants/{id}/menu-items
 * TTL padrão: 60s (filtro: vemcomer/rest_cache_ttl)
 * @package VemComerCore
 */

namespace VC\REST;

use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cache_Middleware {
    public function init(): void {
        add_filter( 'rest_request_before_callbacks', [ $this, 'maybe_serve_cache' ], 10, 3 );
        add_filter( 'rest_request_after_callbacks',  [ $this, 'maybe_store_cache' ], 10, 3 );
    }

    private function is_cacheable_route( string $route ): bool {
        return (bool) preg_match( '#^/vemcomer/v1/restaurants(?:/\d+/menu-items)?$#', $route );
    }

    private function key_for( WP_REST_Request $request ): string {
        $route = $request->get_route();
        $params = $request->get_params();
        ksort( $params );
        return 'vc_rest_cache_' . md5( $route . '|' . wp_json_encode( $params ) );
    }

    public function maybe_serve_cache( $response, array $handler, WP_REST_Request $request ) {
        if ( 'GET' !== $request->get_method() ) { return $response; }
        $route = $request->get_route();
        if ( ! $this->is_cacheable_route( $route ) ) { return $response; }
        $key = $this->key_for( $request );
        $cached = get_transient( $key );
        if ( false !== $cached ) {
            log_event( 'REST cache hit', [ 'route' => $route, 'key' => $key ], 'debug' );
            return new WP_REST_Response( $cached, 200 );
        }
        return $response; // segue para os callbacks normais
    }

    public function maybe_store_cache( $response, array $handler, WP_REST_Request $request ) {
        if ( 'GET' !== $request->get_method() ) { return $response; }
        $route = $request->get_route();
        if ( ! $this->is_cacheable_route( $route ) ) { return $response; }
        if ( is_wp_error( $response ) ) { return $response; }

        $ttl = (int) apply_filters( 'vemcomer/rest_cache_ttl', 60, $route, $request );
        $key = $this->key_for( $request );

        // Extrai os dados do response
        $data = ( $response instanceof WP_REST_Response ) ? $response->get_data() : $response;
        set_transient( $key, $data, $ttl );
        log_event( 'REST cache stored', [ 'route' => $route, 'key' => $key, 'ttl' => $ttl ], 'debug' );
        return $response;
    }
}
