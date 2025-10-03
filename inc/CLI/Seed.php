<?php
/**
 * Seed — Comandos WP-CLI para popular dados de demo
 *
 * Comandos:
 *   wp vc seed --count=5
 *   wp vemcomer seed-restaurants --count=5
 *
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
            // Alias solicitado no checklist
            \WP_CLI::add_command( 'vemcomer seed-restaurants', [ $this, 'cmd_seed' ] );
        }
    }

    /**
     * wp vc seed --count=5
     * wp vemcomer seed-restaurants --count=5
     */
    public function cmd_seed( $args, $assoc_args ): void {
        $count = isset( $assoc_args['count'] ) ? max( 1, (int) $assoc_args['count'] ) : 5;

        $cuisines = [ 'pizza', 'sushi', 'hamburguer', 'massa', 'brasileira' ];

        for ( $r = 1; $r <= $count; $r++ ) {
            $rid = wp_insert_post([
                'post_type'   => CPT_Restaurant::SLUG,
                'post_title'  => 'Restaurante Demo ' . $r,
                'post_status' => 'publish',
            ]);

            if ( is_wp_error( $rid ) || ! $rid ) {
                if ( class_exists( 'WP_CLI' ) ) { \WP_CLI::warning( 'Falha ao criar restaurante #' . $r ); }
                continue;
            }

            // Metas
            update_post_meta( $rid, '_vc_address', 'Rua Exemplo, ' . (100 + $r) );
            update_post_meta( $rid, '_vc_phone', '(11) 9000' . str_pad((string) $r, 4, '0', STR_PAD_LEFT) );
            update_post_meta( $rid, '_vc_min_order', (string) (rand(20, 60)) . ',90' );
            update_post_meta( $rid, '_vc_is_open', (string) (rand(0,1)) );
            update_post_meta( $rid, '_vc_has_delivery', (string) (rand(0,1)) );

            // Taxonomia: cozinha
            $term = $cuisines[ array_rand( $cuisines ) ];
            wp_set_object_terms( $rid, [ $term ], CPT_Restaurant::TAX_CUISINE, true );

            // 3–6 itens por restaurante
            $items = rand(3,6);
            for ( $i = 1; $i <= $items; $i++ ) {
                $mid = wp_insert_post([
                    'post_type'   => CPT_MenuItem::SLUG,
                    'post_title'  => 'Item ' . $r . '-' . $i,
                    'post_content'=> 'Descrição do item ' . $i,
                    'post_status' => 'publish',
                ]);

                if ( $mid ) {
                    update_post_meta( $mid, '_vc_restaurant_id', $rid );
                    update_post_meta( $mid, '_vc_price', (string) (10 * $i) . ',00' );
                    update_post_meta( $mid, '_vc_prep_time', (string) (5 * $i) );
                    update_post_meta( $mid, '_vc_is_available', '1' );
                }
            }
        }

        if ( class_exists( 'WP_CLI' ) ) { \WP_CLI::success( 'Seed concluído: ' . $count . ' restaurantes + itens.' ); }
    }
}
