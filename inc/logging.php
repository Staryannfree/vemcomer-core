<?php
/**
 * Logging helper centralizado do VemComer.
 *
 * @package VemComerCore
 */

namespace VC\Logging;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normaliza uma linha de log e despacha para o destino apropriado.
 *
 * @param mixed       $message Mensagem do evento.
 * @param array|mixed $context Dados adicionais para debug.
 * @param string      $level   Nível textual (info|warning|error|debug, etc).
 */
function log_event( $message, $context = [], string $level = 'info' ): void {
    $normalized = [
        'timestamp' => current_time( 'mysql', true ),
        'level'     => strtolower( is_string( $level ) ? $level : 'info' ),
        'message'   => is_scalar( $message ) ? (string) $message : wp_json_encode( $message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
        'context'   => is_array( $context ) ? $context : ( null === $context ? [] : [ 'value' => $context ] ),
    ];

    $normalized['level'] = preg_replace( '/[^a-z]/', '', $normalized['level'] );
    if ( ! $normalized['level'] ) {
        $normalized['level'] = 'info';
    }

    /**
     * Permite que extensões filtrem ou interrompam o log.
     *
     * @param array $normalized { timestamp, level, message, context }
     */
    $normalized = apply_filters( 'vemcomer_log_event', $normalized );
    if ( empty( $normalized ) || ! is_array( $normalized ) ) {
        return;
    }

    $line = sprintf( '[%s] %s %s', $normalized['timestamp'], strtoupper( $normalized['level'] ), $normalized['message'] );
    if ( ! empty( $normalized['context'] ) ) {
        $line .= ' ' . wp_json_encode( $normalized['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }

    $written = false;
    if ( defined( 'VC_DEBUG' ) && VC_DEBUG ) {
        $uploads = wp_upload_dir();
        if ( ! empty( $uploads['basedir'] ) ) {
            $path = trailingslashit( $uploads['basedir'] ) . 'vemcomer-debug.log';
            if ( wp_mkdir_p( dirname( $path ) ) ) {
                $written = false !== @file_put_contents( $path, $line . PHP_EOL, FILE_APPEND ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
            }
        }
    }

    if ( ! $written ) {
        error_log( $line );
    }
}
