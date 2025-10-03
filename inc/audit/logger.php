<?php
/**
 * Logger de auditoria – cria posts do CPT vc_audit.
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Registra um evento de auditoria.
 *
 * @param string $action  Ação executada.
 * @param array  $context Dados extras para contexto.
 *
 * @return int ID do post criado.
 */
function vc_audit_log( string $action, array $context = array() ): int {
        $user    = wp_get_current_user();
        $title   = sprintf( '%s – %s', strtoupper( $action ), current_time( 'mysql' ) );
        $content = '';
        $content .= 'User: ' . ( $user && $user->ID ? ( $user->user_login . " (#{$user->ID})" ) : 'guest' ) . "\n";
        $content .= 'IP: ' . ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' ) . "\n"; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitizado acima.
        $content .= 'Context: ' . wp_json_encode( $context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

        $pid = wp_insert_post(
                array(
                        'post_type'   => 'vc_audit',
                        'post_status' => 'publish',
                        'post_title'  => $title,
                        'post_content'=> $content,
                )
        );

        return (int) $pid;
}
