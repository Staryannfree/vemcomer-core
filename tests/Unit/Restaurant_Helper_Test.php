<?php
/**
 * Testes para Restaurant_Helper
 * 
 * @package VemComerCore
 */

use VC\Utils\Restaurant_Helper;

class Restaurant_Helper_Test extends VemComer_TestCase {
    /**
     * Teste: Usuário com vc_restaurant_id no meta
     */
    public function test_get_restaurant_for_user_with_meta(): void {
        $user_id = $this->create_test_user();
        $restaurant_id = $this->create_test_restaurant( [ 'post_author' => $user_id ] );

        // Definir meta
        update_user_meta( $user_id, 'vc_restaurant_id', $restaurant_id );

        $restaurant = Restaurant_Helper::get_restaurant_for_user( $user_id );

        $this->assertInstanceOf( 'WP_Post', $restaurant );
        $this->assertEquals( $restaurant_id, $restaurant->ID );
        $this->assertEquals( 'vc_restaurant', $restaurant->post_type );
    }

    /**
     * Teste: Usuário sem meta mas autor de restaurante (fallback)
     */
    public function test_get_restaurant_for_user_fallback_author(): void {
        $user_id = $this->create_test_user();
        $restaurant_id = $this->create_test_restaurant( [ 'post_author' => $user_id ] );

        // Não definir meta - deve usar fallback por author
        $restaurant = Restaurant_Helper::get_restaurant_for_user( $user_id );

        $this->assertInstanceOf( 'WP_Post', $restaurant );
        $this->assertEquals( $restaurant_id, $restaurant->ID );
    }

    /**
     * Teste: Auto-correção de meta quando encontra por author
     */
    public function test_get_restaurant_for_user_auto_correct(): void {
        $user_id = $this->create_test_user();
        $restaurant_id = $this->create_test_restaurant( [ 'post_author' => $user_id ] );

        // Não definir meta inicialmente
        $restaurant = Restaurant_Helper::get_restaurant_for_user( $user_id );
        $this->assertInstanceOf( 'WP_Post', $restaurant );

        // Verificar se o meta foi auto-corrigido
        $meta_id = get_user_meta( $user_id, 'vc_restaurant_id', true );
        $this->assertEquals( $restaurant_id, (int) $meta_id );
    }

    /**
     * Teste: Usuário sem restaurante retorna null
     */
    public function test_get_restaurant_for_user_not_found(): void {
        $user_id = $this->create_test_user();

        // Não criar restaurante nem definir meta
        $restaurant = Restaurant_Helper::get_restaurant_for_user( $user_id );

        $this->assertNull( $restaurant );
    }
}

