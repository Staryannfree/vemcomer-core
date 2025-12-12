<?php
/**
 * Testes para Restaurant_Status_Service
 * 
 * @package VemComerCore
 */

use VC\Services\Restaurant_Status_Service;

class Restaurant_Status_Service_Test extends VemComer_TestCase {
    /**
     * Teste: Restaurante com 0 produtos → active = false
     */
    public function test_status_inactive_with_zero_products(): void {
        $restaurant_id = $this->create_test_restaurant();

        $status = Restaurant_Status_Service::get_status_for_restaurant( $restaurant_id );

        $this->assertFalse( $status['active'] );
        $this->assertEquals( 0, $status['products'] );
        $this->assertFalse( $status['checks']['min_products'] );
    }

    /**
     * Teste: Restaurante com 5+ produtos + dados → active = true
     */
    public function test_status_active_with_min_products(): void {
        $restaurant_id = $this->create_test_restaurant();

        // Criar 5 produtos
        for ( $i = 1; $i <= 5; $i++ ) {
            $this->create_test_product( $restaurant_id, [
                'post_title' => 'Produto ' . $i,
            ] );
        }

        // Adicionar dados básicos
        update_post_meta( $restaurant_id, 'vc_restaurant_whatsapp', '11999999999' );
        update_post_meta( $restaurant_id, 'vc_restaurant_address', 'Rua Teste, 123' );

        $status = Restaurant_Status_Service::get_status_for_restaurant( $restaurant_id );

        $this->assertTrue( $status['active'] );
        $this->assertEquals( 5, $status['products'] );
        $this->assertTrue( $status['checks']['min_products'] );
        $this->assertTrue( $status['checks']['has_whatsapp'] );
        $this->assertTrue( $status['checks']['has_address'] );
    }

    /**
     * Teste: Restaurante sem WhatsApp → active = false
     */
    public function test_status_inactive_missing_whatsapp(): void {
        $restaurant_id = $this->create_test_restaurant();

        // Criar 5 produtos
        for ( $i = 1; $i <= 5; $i++ ) {
            $this->create_test_product( $restaurant_id );
        }

        // Adicionar apenas endereço (sem WhatsApp)
        update_post_meta( $restaurant_id, 'vc_restaurant_address', 'Rua Teste, 123' );

        $status = Restaurant_Status_Service::get_status_for_restaurant( $restaurant_id );

        $this->assertFalse( $status['active'] );
        $this->assertTrue( $status['checks']['min_products'] );
        $this->assertFalse( $status['checks']['has_whatsapp'] );
        $this->assertTrue( $status['checks']['has_address'] );
    }
}

