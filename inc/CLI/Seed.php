<?php
/**
 * Seed — Comandos WP-CLI para popular dados de demo
 * @package VemComerCore
 */

namespace VC\CLI;

use VC\Model\CPT_Restaurant;
use VC\Model\CPT_MenuItem;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Seed {
    public function init(): void {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'vc seed', [ $this, 'cmd_seed' ] );
        }
    }

    /**
     * wp vc seed
     */
    public function cmd_seed( $args, $assoc_args ): void {
        $rid = wp_insert_post([
            'post_type'   => CPT_Restaurant::SLUG,
            'post_title'  => 'VemComer Demo Restaurant',
            'post_status' => 'publish',
        ]);
        update_post_meta( $rid, '_vc_address', 'Rua Exemplo, 123' );
        update_post_meta( $rid, '_vc_phone', '(11) 99999-9999' );
        update_post_meta( $rid, '_vc_min_order', '29,90' );
        update_post_meta( $rid, '_vc_is_open', '1' );

        for ( $i = 1; $i <= 5; $i++ ) {
            $mid = wp_insert_post([
                'post_type'   => CPT_MenuItem::SLUG,
                'post_title'  => 'Item ' . $i,
                'post_content'=> 'Descrição do item ' . $i,
                'post_status' => 'publish',
            ]);
            update_post_meta( $mid, '_vc_restaurant_id', $rid );
            update_post_meta( $mid, '_vc_price', (string) (10 * $i) . ',00' );
            update_post_meta( $mid, '_vc_prep_time', (string) (5 * $i) );
            update_post_meta( $mid, '_vc_is_available', '1' );
        }

        if ( class_exists( 'WP_CLI' ) ) { \WP_CLI::success( 'Seed concluído: restaurante + 5 itens.' ); }
    }
}
