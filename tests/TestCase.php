<?php
/**
 * Classe base para testes
 * 
 * @package VemComerCore
 */

use PHPUnit\Framework\TestCase as PHPUnit_TestCase;

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( dirname( __FILE__ ) ) . '/' );
}

/**
 * Classe base para todos os testes
 */
abstract class VemComer_TestCase extends PHPUnit_TestCase {
    /**
     * Setup antes de cada teste
     */
    public function setUp(): void {
        parent::setUp();
        
        // Limpar cache
        wp_cache_flush();
        
        // Limpar queries globais
        global $wpdb;
        $wpdb->queries = [];
    }

    /**
     * Teardown após cada teste
     */
    public function tearDown(): void {
        parent::tearDown();
        
        // Limpar cache
        wp_cache_flush();
    }

    /**
     * Helper para criar um restaurante de teste
     * 
     * @param array $args Argumentos para wp_insert_post
     * @return int ID do restaurante criado
     */
    protected function create_test_restaurant( array $args = [] ): int {
        $defaults = [
            'post_type'   => 'vc_restaurant',
            'post_title'  => 'Restaurante Teste',
            'post_status' => 'publish',
            'post_author' => 1,
        ];

        $post_id = wp_insert_post( array_merge( $defaults, $args ) );
        
        if ( is_wp_error( $post_id ) ) {
            $this->fail( 'Falha ao criar restaurante de teste: ' . $post_id->get_error_message() );
        }

        return $post_id;
    }

    /**
     * Helper para criar um produto de teste
     * 
     * @param int   $restaurant_id ID do restaurante
     * @param array $args Argumentos adicionais
     * @return int ID do produto criado
     */
    protected function create_test_product( int $restaurant_id, array $args = [] ): int {
        $defaults = [
            'post_type'   => 'vc_menu_item',
            'post_title'  => 'Produto Teste',
            'post_status' => 'publish',
        ];

        $post_id = wp_insert_post( array_merge( $defaults, $args ) );
        
        if ( is_wp_error( $post_id ) ) {
            $this->fail( 'Falha ao criar produto de teste: ' . $post_id->get_error_message() );
        }

        // Anexar restaurante
        update_post_meta( $post_id, '_vc_restaurant_id', $restaurant_id );
        update_post_meta( $post_id, '_vc_price', '10.00' );

        return $post_id;
    }

    /**
     * Helper para criar um usuário de teste
     * 
     * @param array $args Argumentos para wp_create_user
     * @return int ID do usuário criado
     */
    protected function create_test_user( array $args = [] ): int {
        $defaults = [
            'user_login' => 'test_user_' . uniqid(),
            'user_email' => 'test_' . uniqid() . '@example.com',
            'user_pass'  => 'test_password',
        ];

        $user_id = wp_create_user(
            $defaults['user_login'],
            $defaults['user_pass'],
            $defaults['user_email']
        );

        if ( is_wp_error( $user_id ) ) {
            $this->fail( 'Falha ao criar usuário de teste: ' . $user_id->get_error_message() );
        }

        return $user_id;
    }
}

