<?php
/**
 * Shipping_Controller — Endpoint de cotação de frete
 * @route GET /wp-json/vemcomer/v1/shipping/quote?restaurant_id=ID&subtotal=99.90
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Frontend\Shipping;
use WP_REST_Request;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shipping_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/shipping/quote', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'quote' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'restaurant_id' => [ 'required' => true, 'validate_callback' => 'is_numeric' ],
                'subtotal'      => [ 'required' => true ],
            ],
        ] );
    }

    public function quote( WP_REST_Request $req ) {
        $rid = (int) $req->get_param( 'restaurant_id' );
        $sub = (float) str_replace( ',', '.', (string) $req->get_param( 'subtotal' ) );
        if ( $rid <= 0 || $sub < 0 ) {
            return new WP_Error( 'vc_bad_params', __( 'Parâmetros inválidos.', 'vemcomer' ), [ 'status' => 400 ] );
        }
        return rest_ensure_response( Shipping::quote( $rid, $sub ) );
    }
}
