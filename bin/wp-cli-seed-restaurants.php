<?php
/**
 * WP-CLI: Preenchedor de Restaurantes (compatível com Admin Preenchedor)
 * Uso:
 *   wp vemcomer seed-restaurants --count=5 [--force]
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }

require_once dirname( __DIR__ ) . '/inc/seed/Seeder.php';

class VC_CLI_Seed_Restaurants {
    /**
     * Executa o seed.
     *
     * ## OPTIONS
     * [--count=<n>]  Quantidade (default 5)
     * [--force]      Remove existentes antes
     */
    public function __invoke( $args, $assoc_args ) : void {
        $count = isset( $assoc_args['count'] ) ? max( 1, (int) $assoc_args['count'] ) : 5;
        $force = isset( $assoc_args['force'] );

        if ( ! post_type_exists( 'vc_restaurant' ) ) {
            \WP_CLI::error( 'CPT vc_restaurant não está registrado. Ative o módulo correspondente.' );
            return;
        }

        $seeder = new VC_Seeder();
        $ids    = $seeder->seed_restaurants( $count, $force );

        if ( empty( $ids ) ) {
            \WP_CLI::warning( 'Nenhum restaurante criado.' );
            return;
        }

        foreach ( $ids as $pid ) {
            \WP_CLI::log( 'Criado restaurante ID ' . $pid );
        }
        \WP_CLI::success( 'Seed finalizado: ' . count( $ids ) . ' restaurantes.' );
    }
}

\WP_CLI::add_command( 'vemcomer seed-restaurants', new VC_CLI_Seed_Restaurants() );
