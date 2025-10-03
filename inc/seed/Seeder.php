<?php
/**
 * VemComer Core – Seeder centralizado
 * Gera Restaurantes e (se existir) Itens de Cardápio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class VC_Seeder {
    /** Taxonomias default para seed */
    private array $cuisines  = [ 'pizza', 'japonesa', 'brasileira', 'hamburgueria', 'vegana' ];
    private array $locations = [ 'centro', 'zona-sul', 'zona-norte', 'zona-leste', 'zona-oeste' ];

    /**
     * Garante termos básicos de taxonomias do CPT de restaurantes.
     */
    public function ensure_terms(): void {
        if ( taxonomy_exists( 'vc_cuisine' ) ) {
            foreach ( $this->cuisines as $slug ) {
                if ( ! term_exists( $slug, 'vc_cuisine' ) ) {
                    wp_insert_term( ucfirst( $slug ), 'vc_cuisine', [ 'slug' => $slug ] );
                }
            }
        }
        if ( taxonomy_exists( 'vc_location' ) ) {
            foreach ( $this->locations as $slug ) {
                if ( ! term_exists( $slug, 'vc_location' ) ) {
                    wp_insert_term( ucwords( str_replace( '-', ' ', $slug ) ), 'vc_location', [ 'slug' => $slug ] );
                }
            }
        }
    }

    /**
     * Cria N restaurantes com metadados e termos. Retorna array de IDs criados.
     *
     * @param int  $count Quantidade de restaurantes a gerar.
     * @param bool $force Limpa existentes antes de criar.
     *
     * @return int[] IDs criados.
     */
    public function seed_restaurants( int $count = 5, bool $force = false ): array {
        if ( ! post_type_exists( 'vc_restaurant' ) ) {
            return [];
        }

        $this->ensure_terms();

        if ( $force ) {
            $existing = get_posts([
                'post_type'   => 'vc_restaurant',
                'numberposts' => -1,
                'fields'      => 'ids',
            ]);
            foreach ( $existing as $pid ) {
                wp_delete_post( $pid, true );
            }
        }

        $names   = [ 'Cantina da Praça', 'Sushi do Bairro', 'Hamburgueria Fênix', 'Veg & Co', 'Pizzaria La Nonna', 'Sabores do Brasil' ];
        $created = [];

        for ( $i = 0; $i < $count; $i++ ) {
            $title = $names[ $i % count( $names ) ] . ' #' . ( $i + 1 );
            $pid   = wp_insert_post([
                'post_type'   => 'vc_restaurant',
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_content'=> 'Restaurante de exemplo criado pelo Preenchedor.',
            ]);
            if ( is_wp_error( $pid ) || ! $pid ) {
                continue;
            }

            // Termos
            if ( taxonomy_exists( 'vc_cuisine' ) ) {
                $cuisine = $this->cuisines[ array_rand( $this->cuisines ) ];
                wp_set_object_terms( $pid, $cuisine, 'vc_cuisine', false );
            }
            if ( taxonomy_exists( 'vc_location' ) ) {
                $location = $this->locations[ array_rand( $this->locations ) ];
                wp_set_object_terms( $pid, $location, 'vc_location', false );
            }

            // Metas (compatível com o metabox atual)
            update_post_meta( $pid, 'vc_restaurant_cnpj', sprintf( '00.000.000/%05d-00', rand( 100, 999 ) ) );
            update_post_meta( $pid, 'vc_restaurant_whatsapp', '+55 11 9' . rand( 1000, 9999 ) . '-' . rand( 1000, 9999 ) );
            update_post_meta( $pid, 'vc_restaurant_site', 'https://demo' . ( $i + 1 ) . '.vemcomer.test' );
            update_post_meta( $pid, 'vc_restaurant_open_hours', 'Seg-Dom 11:00–23:00' );
            update_post_meta( $pid, 'vc_restaurant_delivery', rand( 0, 1 ) ? '1' : '0' );
            update_post_meta( $pid, 'vc_restaurant_address', 'Rua Exemplo, ' . rand( 10, 999 ) );

            $created[] = (int) $pid;
        }

        return $created;
    }

    /**
     * Cria itens de cardápio se o CPT existir (opcional).
     *
     * @param int $restaurant_id ID do restaurante.
     * @param int $min           Mínimo de itens.
     * @param int $max           Máximo de itens.
     *
     * @return int[] IDs criados.
     */
    public function seed_menu_items_for( int $restaurant_id, int $min = 3, int $max = 6 ): array {
        if ( ! post_type_exists( 'vc_menu_item' ) ) {
            return [];
        }

        $quantity = max( $min, rand( $min, $max ) );
        $ids      = [];

        for ( $i = 1; $i <= $quantity; $i++ ) {
            $mid = wp_insert_post([
                'post_type'   => 'vc_menu_item',
                'post_title'  => 'Item ' . $restaurant_id . '-' . $i,
                'post_content'=> 'Descrição do item ' . $i,
                'post_status' => 'publish',
            ]);
            if ( $mid ) {
                update_post_meta( $mid, '_vc_restaurant_id', $restaurant_id );
                update_post_meta( $mid, '_vc_price', (string) ( 10 * $i ) . ',00' );
                update_post_meta( $mid, '_vc_prep_time', (string) ( 5 * $i ) );
                update_post_meta( $mid, '_vc_is_available', '1' );
                $ids[] = (int) $mid;
            }
        }

        return $ids;
    }
}
