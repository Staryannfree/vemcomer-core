<?php
/**
 * Rate limiting simples por IP + rota (transients).
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Permite N requisições por janela (segundos) por IP em uma chave específica.
 *
 * @param string $key    Chave da operação.
 * @param int    $limit  Quantidade máxima.
 * @param int    $window Janela (em segundos).
 *
 * @return bool True se a requisição está autorizada.
 */
function vc_rate_limit_allow( string $key, int $limit = 60, int $window = 60 ): bool {
        $ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
        $bucket = sprintf( 'vc_rl_%s_%s', md5( $key ), md5( $ip ) );

        $data = get_transient( $bucket );
        if ( ! is_array( $data ) ) {
                $data = array(
                        'count' => 0,
                        'start' => time(),
                );
        }

        $elapsed = time() - (int) $data['start'];
        if ( $elapsed >= $window ) {
                $data = array(
                        'count' => 0,
                        'start' => time(),
                );
        }

        if ( $data['count'] >= $limit ) {
                return false;
        }

        $data['count']++;
        set_transient( $bucket, $data, $window );
        return true;
}
