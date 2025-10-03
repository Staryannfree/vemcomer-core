<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function vc_sanitize_text( $value ): string {
    return sanitize_text_field( (string) $value );
}

function vc_sanitize_money( $value ): string {
    // Mantém somente dígitos e separadores de decimal/com milhar comuns
    $value = preg_replace( '/[^0-9.,]/', '', (string) $value );
    return (string) $value;
}

function vc_esc_html( $value ): string {
    return esc_html( (string) $value );
}
