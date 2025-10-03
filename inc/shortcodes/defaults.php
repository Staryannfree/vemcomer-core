<?php
/**
 * Defaults de atributos para shortcodes do VemComer.
 *
 * Permite fallback via query string (?restaurant_id=123).
 *
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retorna um inteiro positivo da query string.
 *
 * @param string $key Chave na query string.
 * @return string Inteiro positivo como string ou vazio.
 */
function vc_sc_query_positive_int( string $key ): string {
    if ( ! isset( $_GET[ $key ] ) ) {
        return '';
    }

    $value = absint( wp_unslash( $_GET[ $key ] ) );

    return $value > 0 ? (string) $value : '';
}

/**
 * Fallback para [vc_restaurant id=""].
 *
 * @param array  $out       Valores atuais.
 * @param array  $pairs     Pares padrões.
 * @param array  $atts      Atributos informados.
 * @param string $shortcode Nome do shortcode.
 * @return array
 */
function vc_sc_defaults_restaurant( array $out, array $pairs, array $atts, string $shortcode ): array {
    if ( empty( $out['id'] ) ) {
        $rid = vc_sc_query_positive_int( 'restaurant_id' );

        if ( '' !== $rid ) {
            $out['id'] = $rid;
        }
    }

    return $out;
}
add_filter( 'shortcode_atts_vc_restaurant', 'vc_sc_defaults_restaurant', 10, 4 );

/**
 * Fallback para [vc_menu_items restaurant_id=""].
 *
 * @param array  $out       Valores atuais.
 * @param array  $pairs     Pares padrões.
 * @param array  $atts      Atributos informados.
 * @param string $shortcode Nome do shortcode.
 * @return array
 */
function vc_sc_defaults_menu_items( array $out, array $pairs, array $atts, string $shortcode ): array {
    if ( empty( $out['restaurant_id'] ) ) {
        $rid = vc_sc_query_positive_int( 'restaurant_id' );

        if ( '' !== $rid ) {
            $out['restaurant_id'] = $rid;
        }
    }

    return $out;
}
add_filter( 'shortcode_atts_vc_menu_items', 'vc_sc_defaults_menu_items', 10, 4 );
