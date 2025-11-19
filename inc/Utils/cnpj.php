<?php
namespace VC\Utils;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Validate a Brazilian CNPJ using DV algorithm and optional ReceitaWS lookup.
 *
 * @param string $cnpj         Raw CNPJ (digits or formatted).
 * @param bool   $force_remote Whether to always run the ReceitaWS lookup.
 *
 * @return array{input:string,normalized:string,remote?:array}|WP_Error
 */
function validate_cnpj( string $cnpj, bool $force_remote = false ) {
    $original  = $cnpj;
    $normalized = preg_replace( '/\D+/', '', $cnpj );

    if ( '' === $normalized ) {
        return new WP_Error( 'vc_restaurant_cnpj_empty', __( 'Informe o CNPJ do restaurante.', 'vemcomer' ) );
    }

    if ( 14 !== strlen( $normalized ) ) {
        return new WP_Error( 'vc_restaurant_cnpj_length', __( 'O CNPJ deve conter 14 dígitos.', 'vemcomer' ) );
    }

    if ( preg_match( '/^(\d)\1{13}$/', $normalized ) ) {
        return new WP_Error( 'vc_restaurant_cnpj_repeated', __( 'CNPJ inválido: não pode conter todos os dígitos iguais.', 'vemcomer' ) );
    }

    $digits = array_map( 'intval', str_split( $normalized ) );

    $first_digit  = calculate_digit( $digits, 12 );
    $second_digit = calculate_digit( $digits, 13 );

    if ( $digits[12] !== $first_digit || $digits[13] !== $second_digit ) {
        return new WP_Error( 'vc_restaurant_cnpj_dv', __( 'CNPJ inválido: dígitos verificadores não conferem.', 'vemcomer' ) );
    }

    $result = [
        'input'      => $original,
        'normalized' => $normalized,
    ];

    $should_remote = $force_remote;
    if ( ! $should_remote && function_exists( 'apply_filters' ) ) {
        $should_remote = (bool) apply_filters( 'vc_validate_cnpj_use_receitaws', false, $normalized );
    }

    if ( $should_remote ) {
        if ( ! function_exists( 'wp_remote_get' ) ) {
            return new WP_Error( 'vc_restaurant_cnpj_remote_unavailable', __( 'Validação externa indisponível.', 'vemcomer' ) );
        }

        $endpoint = sprintf( 'https://www.receitaws.com.br/v1/cnpj/%s', $normalized );
        $response = wp_remote_get( $endpoint, [
            'timeout' => 10,
            'headers' => [ 'Accept' => 'application/json' ],
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'vc_restaurant_cnpj_remote_error', __( 'Não foi possível consultar a ReceitaWS.', 'vemcomer' ), $response->get_error_messages() );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );

        if ( 200 !== $code || ! is_array( $json ) ) {
            return new WP_Error( 'vc_restaurant_cnpj_remote_invalid', __( 'Resposta inválida da ReceitaWS.', 'vemcomer' ) );
        }

        $status = isset( $json['status'] ) ? strtoupper( (string) $json['status'] ) : '';
        if ( 'ERROR' === $status ) {
            $message = isset( $json['message'] ) ? (string) $json['message'] : __( 'CNPJ não encontrado.', 'vemcomer' );
            return new WP_Error( 'vc_restaurant_cnpj_remote_not_found', $message );
        }

        $result['remote'] = $json;
    }

    return $result;
}

/**
 * Calculate the verifying digit for a CNPJ sequence.
 *
 * @param array<int,int> $digits Digits of the CNPJ.
 * @param int            $length Length to use in the weight calculation.
 */
function calculate_digit( array $digits, int $length ): int {
    $weights = [ 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2, 9, 8, 7 ];

    if ( 13 === $length ) {
        array_unshift( $weights, 6 );
    }

    $sum = 0;
    for ( $i = 0; $i < $length; $i++ ) {
        $sum += $digits[ $i ] * $weights[ $i ];
    }

    $remainder = $sum % 11;
    return ( $remainder < 2 ) ? 0 : 11 - $remainder;
}
