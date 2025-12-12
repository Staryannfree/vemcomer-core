<?php
/**
 * Melhorias no sistema de logging para debug completo
 * 
 * Este arquivo adiciona hooks para capturar mais informações durante o debug
 * É carregado automaticamente pelo vemcomer-core.php quando VC_DEBUG está ativo
 * NÃO inclua este arquivo manualmente no wp-config.php (causará erro fatal)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================================
// HOOKS PARA CAPTURAR ERROS E WARNINGS
// ============================================================================

// Capturar todos os erros PHP
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
    if ( ! ( error_reporting() & $errno ) ) {
        return false;
    }
    
    $error_types = [
        E_ERROR             => 'ERROR',
        E_WARNING           => 'WARNING',
        E_PARSE             => 'PARSE',
        E_NOTICE            => 'NOTICE',
        E_CORE_ERROR        => 'CORE_ERROR',
        E_CORE_WARNING      => 'CORE_WARNING',
        E_COMPILE_ERROR     => 'COMPILE_ERROR',
        E_COMPILE_WARNING   => 'COMPILE_WARNING',
        E_USER_ERROR        => 'USER_ERROR',
        E_USER_WARNING      => 'USER_WARNING',
        E_USER_NOTICE       => 'USER_NOTICE',
        E_STRICT            => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED        => 'DEPRECATED',
        E_USER_DEPRECATED   => 'USER_DEPRECATED',
    ];
    
    $error_type = $error_types[ $errno ] ?? 'UNKNOWN';
    $message = sprintf(
        '[PHP %s] %s in %s on line %d',
        $error_type,
        $errstr,
        $errfile,
        $errline
    );
    
    if ( function_exists( 'VC\Logging\log_event' ) ) {
        \VC\Logging\log_event( $message, [
            'errno'   => $errno,
            'errfile' => $errfile,
            'errline' => $errline,
        ], 'error' );
    } else {
        error_log( $message );
    }
    
    return false; // Continuar com o handler padrão
}, E_ALL );

// ============================================================================
// CAPTURAR REQUISIÇÕES REST API
// ============================================================================

add_action( 'rest_api_init', function() {
    if ( ! defined( 'VC_DEBUG' ) || ! VC_DEBUG ) {
        return;
    }
    
    add_filter( 'rest_pre_serve_request', function( $served, $result, $request, $server ) {
        if ( function_exists( 'VC\Logging\log_event' ) ) {
            $route = $request->get_route();
            $method = $request->get_method();
            
            // Logar apenas rotas do VemComer
            if ( strpos( $route, '/vemcomer/v1/' ) !== false ) {
                \VC\Logging\log_event( 'REST API Request', [
                    'route'  => $route,
                    'method' => $method,
                    'params' => $request->get_params(),
                ], 'debug' );
                
                // Logar resposta se houver erro
                if ( is_wp_error( $result ) ) {
                    \VC\Logging\log_event( 'REST API Error', [
                        'route'  => $route,
                        'method' => $method,
                        'error'  => $result->get_error_message(),
                        'code'   => $result->get_error_code(),
                    ], 'error' );
                }
            }
        }
        
        return $served;
    }, 10, 4 );
}, 1 );

// ============================================================================
// CAPTURAR QUERIES SQL LENTAS OU COM ERRO
// ============================================================================

if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
    add_action( 'shutdown', function() {
        global $wpdb;
        
        if ( ! defined( 'VC_DEBUG' ) || ! VC_DEBUG ) {
            return;
        }
        
        if ( ! empty( $wpdb->queries ) && function_exists( 'VC\Logging\log_event' ) ) {
            $slow_queries = array_filter( $wpdb->queries, function( $query ) {
                return isset( $query[1] ) && $query[1] > 0.1; // Queries > 100ms
            } );
            
            if ( ! empty( $slow_queries ) ) {
                \VC\Logging\log_event( 'Slow SQL Queries', [
                    'count'   => count( $slow_queries ),
                    'queries' => array_slice( $slow_queries, 0, 5 ), // Primeiras 5
                ], 'warning' );
            }
        }
    }, 999 );
}

// ============================================================================
// CAPTURAR HOOKS DO WORDPRESS
// ============================================================================

if ( defined( 'VC_DEBUG' ) && VC_DEBUG && defined( 'VC_DEBUG_HOOKS' ) && VC_DEBUG_HOOKS ) {
    add_action( 'all', function( $hook ) {
        if ( strpos( $hook, 'vemcomer' ) !== false || strpos( $hook, 'vc_' ) !== false ) {
            if ( function_exists( 'VC\Logging\log_event' ) ) {
                \VC\Logging\log_event( 'WordPress Hook Fired', [
                    'hook' => $hook,
                ], 'debug' );
            }
        }
    }, 1 );
}

