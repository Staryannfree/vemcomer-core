<?php
/**
 * WP-CLI Seeder: Popula restaurantes de exemplo
 *
 * Uso:
 *   wp vemcomer seed-restaurants --count=5 --force
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }

class VC_CLI_Seed_Restaurants {
    /** @var array */
    private $cuisines = [ 'pizza', 'japonesa', 'brasileira', 'hamburgueria', 'vegana' ];
    /** @var array */
    private $locations = [ 'centro', 'zona-sul', 'zona-norte', 'zona-leste', 'zona-oeste' ];

    /**
     * Cria termos base caso não existam
     */
    private function ensure_terms() : void {
        foreach ( $this->cuisines as $slug ) {
            if ( ! term_exists( $slug, 'vc_cuisine' ) ) {
                wp_insert_term( ucfirst( $slug ), 'vc_cuisine', [ 'slug' => $slug ] );
            }
        }
        foreach ( $this->locations as $slug ) {
            if ( ! term_exists( $slug, 'vc_location' ) ) {
                wp_insert_term( ucwords( str_replace('-', ' ', $slug) ), 'vc_location', [ 'slug' => $slug ] );
            }
        }
    }

    /**
     * Comando: wp vemcomer seed-restaurants
     *
     * ## OPTIONS
     * [--count=<n>]     Quantidade de restaurantes (padrão 5)
     * [--force]         Recria mesmo se já houver posts
     *
     * ## EXAMPLES
     *   wp vemcomer seed-restaurants --count=8
     */
    public function __invoke( $args, $assoc_args ) : void {
        $count = isset( $assoc_args['count'] ) ? max( 1, (int) $assoc_args['count'] ) : 5;
        $force = isset( $assoc_args['force'] );

        // Garante CPT carregado
        if ( ! post_type_exists( 'vc_restaurant' ) ) {
            WP_CLI::error( 'CPT vc_restaurant não está registrado. Ative o plugin/core.' );
            return;
        }

        $this->ensure_terms();

        if ( ! $force ) {
            $existing = (new WP_Query([
                'post_type'      => 'vc_restaurant',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ]))->posts;
            if ( ! empty( $existing ) ) {
                WP_CLI::warning( 'Já existem restaurantes. Use --force para recriar.' );
                return;
            }
        }

        // Remove existentes quando --force
        if ( $force ) {
            $to_delete = (new WP_Query([
                'post_type'      => 'vc_restaurant',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]))->posts;
            foreach ( $to_delete as $pid ) {
                wp_delete_post( $pid, true );
            }
        }

        $faker_names = [ 'Cantina da Praça', 'Sushi do Bairro', 'Hamburgueria Fênix', 'Veg & Co', 'Pizzaria La Nonna', 'Sabores do Brasil' ];

        for ( $i = 0; $i < $count; $i++ ) {
            $title = $faker_names[ $i % count( $faker_names ) ] . ' #' . ($i+1);
            $pid = wp_insert_post([
                'post_type'   => 'vc_restaurant',
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_content'=> 'Restaurante de exemplo criado via seeder.'
            ]);

            if ( is_wp_error( $pid ) ) {
                WP_CLI::warning( 'Falha ao criar restaurante: ' . $title );
                continue;
            }

            // Termos aleatórios
            $cuisine  = $this->cuisines[ array_rand( $this->cuisines ) ];
            $location = $this->locations[ array_rand( $this->locations ) ];
            wp_set_object_terms( $pid, $cuisine, 'vc_cuisine', false );
            wp_set_object_terms( $pid, $location, 'vc_location', false );

            // Metas
            update_post_meta( $pid, 'vc_restaurant_cnpj', sprintf('00.000.000/%05d-00', rand( 100, 999 ) ) );
            update_post_meta( $pid, 'vc_restaurant_whatsapp', '+55 11 9' . rand(1000,9999) . '-' . rand(1000,9999) );
            update_post_meta( $pid, 'vc_restaurant_site', 'https://exemplo-' . ($i+1) . '.vemcomer.test' );
            update_post_meta( $pid, 'vc_restaurant_open_hours', 'Seg-Dom 11:00–23:00' );
            update_post_meta( $pid, 'vc_restaurant_delivery', rand(0,1) ? '1' : '0' );
            update_post_meta( $pid, 'vc_restaurant_address', 'Rua Exemplo, ' . rand(10,999) . ' – ' . ucwords( str_replace('-', ' ', $location) ) );

            WP_CLI::log( "Criado: {$title} (ID {$pid}) [{$cuisine} | {$location}]" );
        }

        WP_CLI::success( 'Seed finalizado.' );
    }
}

WP_CLI::add_command( 'vemcomer seed-restaurants', new VC_CLI_Seed_Restaurants() );
