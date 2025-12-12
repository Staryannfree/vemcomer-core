<?php
/**
 * Browser_Debug_Controller — Recebe logs do navegador
 * @package VemComerCore
 */

namespace VC\REST;

use function VC\Logging\log_event;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Browser_Debug_Controller extends WP_REST_Controller {
    
    protected $namespace = 'vemcomer/v1';
    protected $rest_base = 'debug';

    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/debug/browser-logs', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'receive_log' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/browser-logs/batch', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'receive_logs_batch' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/browser-logs/export', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'export_logs' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );
    }

    public function can_access_debug( WP_REST_Request $request ): bool {
        // Apenas em ambiente local ou para administradores
        if ( defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE === 'local' ) {
            return true;
        }
        
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        return user_can( $user, 'manage_options' ) || in_array( 'administrator', $user->roles, true );
    }

    public function receive_log( WP_REST_Request $request ): WP_REST_Response {
        $log_data = $request->get_json_params();

        if ( empty( $log_data ) ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => 'Dados inválidos' ], 400 );
        }

        // Salvar no arquivo de log
        $this->save_log_to_file( $log_data );

        // Também salvar no log do VemComer
        if ( isset( $log_data['type'] ) && isset( $log_data['data'] ) ) {
            $level = 'info';
            if ( strpos( $log_data['type'], 'error' ) !== false ) {
                $level = 'error';
            } elseif ( strpos( $log_data['type'], 'warn' ) !== false ) {
                $level = 'warning';
            }

            log_event( 'Browser: ' . $log_data['type'], $log_data['data'], $level );
        }

        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    public function receive_logs_batch( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();

        if ( empty( $data ) || ! isset( $data['logs'] ) ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => 'Dados inválidos' ], 400 );
        }

        $saved = 0;
        foreach ( $data['logs'] as $log_data ) {
            $this->save_log_to_file( $log_data );
            $saved++;
        }

        // Salvar requisições de rede separadamente
        if ( ! empty( $data['networkRequests'] ) ) {
            $this->save_network_requests( $data['networkRequests'] );
        }

        // Salvar métricas de performance
        if ( ! empty( $data['performanceMetrics'] ) ) {
            $this->save_performance_metrics( $data['performanceMetrics'] );
        }

        return new WP_REST_Response( [
            'success' => true,
            'saved' => $saved,
        ], 200 );
    }

    private function save_log_to_file( array $log_data ): void {
        $uploads = wp_upload_dir();
        if ( empty( $uploads['basedir'] ) ) {
            return;
        }

        $log_dir = trailingslashit( $uploads['basedir'] ) . 'vemcomer-browser-debug';
        if ( ! wp_mkdir_p( $log_dir ) ) {
            return;
        }

        $log_file = trailingslashit( $log_dir ) . 'browser-logs-' . date( 'Y-m-d' ) . '.json';

        // Ler logs existentes
        $existing_logs = [];
        if ( file_exists( $log_file ) ) {
            $content = file_get_contents( $log_file );
            if ( $content ) {
                $existing_logs = json_decode( $content, true ) ?: [];
            }
        }

        // Adicionar novo log
        $existing_logs[] = $log_data;

        // Limitar a 1000 logs por arquivo
        if ( count( $existing_logs ) > 1000 ) {
            $existing_logs = array_slice( $existing_logs, -1000 );
        }

        // Salvar
        file_put_contents( $log_file, wp_json_encode( $existing_logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    }

    private function save_network_requests( array $requests ): void {
        $uploads = wp_upload_dir();
        if ( empty( $uploads['basedir'] ) ) {
            return;
        }

        $log_dir = trailingslashit( $uploads['basedir'] ) . 'vemcomer-browser-debug';
        if ( ! wp_mkdir_p( $log_dir ) ) {
            return;
        }

        $log_file = trailingslashit( $log_dir ) . 'network-requests-' . date( 'Y-m-d' ) . '.json';

        // Ler requisições existentes
        $existing_requests = [];
        if ( file_exists( $log_file ) ) {
            $content = file_get_contents( $log_file );
            if ( $content ) {
                $existing_requests = json_decode( $content, true ) ?: [];
            }
        }

        // Adicionar novas requisições
        $existing_requests = array_merge( $existing_requests, $requests );

        // Limitar a 500 requisições por arquivo
        if ( count( $existing_requests ) > 500 ) {
            $existing_requests = array_slice( $existing_requests, -500 );
        }

        // Salvar
        file_put_contents( $log_file, wp_json_encode( $existing_requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    }

    private function save_performance_metrics( array $metrics ): void {
        $uploads = wp_upload_dir();
        if ( empty( $uploads['basedir'] ) ) {
            return;
        }

        $log_dir = trailingslashit( $uploads['basedir'] ) . 'vemcomer-browser-debug';
        if ( ! wp_mkdir_p( $log_dir ) ) {
            return;
        }

        $log_file = trailingslashit( $log_dir ) . 'performance-' . date( 'Y-m-d' ) . '.json';

        // Ler métricas existentes
        $existing_metrics = [];
        if ( file_exists( $log_file ) ) {
            $content = file_get_contents( $log_file );
            if ( $content ) {
                $existing_metrics = json_decode( $content, true ) ?: [];
            }
        }

        // Adicionar novas métricas
        $existing_metrics = array_merge( $existing_metrics, $metrics );

        // Limitar a 100 métricas por arquivo
        if ( count( $existing_metrics ) > 100 ) {
            $existing_metrics = array_slice( $existing_metrics, -100 );
        }

        // Salvar
        file_put_contents( $log_file, wp_json_encode( $existing_metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    }

    public function export_logs( WP_REST_Request $request ): WP_REST_Response {
        $date = $request->get_param( 'date' ) ?: date( 'Y-m-d' );
        $uploads = wp_upload_dir();
        $log_dir = trailingslashit( $uploads['basedir'] ) . 'vemcomer-browser-debug';

        $logs_file = trailingslashit( $log_dir ) . 'browser-logs-' . $date . '.json';
        $network_file = trailingslashit( $log_dir ) . 'network-requests-' . $date . '.json';
        $performance_file = trailingslashit( $log_dir ) . 'performance-' . $date . '.json';

        $export = [
            'date' => $date,
            'timestamp' => current_time( 'mysql', true ),
            'logs' => file_exists( $logs_file ) ? json_decode( file_get_contents( $logs_file ), true ) : [],
            'networkRequests' => file_exists( $network_file ) ? json_decode( file_get_contents( $network_file ), true ) : [],
            'performanceMetrics' => file_exists( $performance_file ) ? json_decode( file_get_contents( $performance_file ), true ) : [],
        ];

        return new WP_REST_Response( $export, 200 );
    }
}

