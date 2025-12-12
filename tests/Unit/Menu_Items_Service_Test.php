<?php
/**
 * Testes para Menu_Items_Service
 * 
 * @package VemComerCore
 */

use VC\Services\Menu_Items_Service;

class Menu_Items_Service_Test extends VemComer_TestCase {
    /**
     * Teste: Cria produto com _vc_restaurant_id correto
     */
    public function test_create_with_valid_restaurant(): void {
        $restaurant_id = $this->create_test_restaurant();
        $service = new Menu_Items_Service();

        $data = [
            'title' => 'Produto Teste',
            'price' => '15.00',
        ];

        $product_id = $service->create( $data, $restaurant_id );

        $this->assertIsInt( $product_id );
        $this->assertGreaterThan( 0, $product_id );

        // Verificar que o produto tem o restaurante correto
        $product_restaurant_id = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
        $this->assertEquals( $restaurant_id, $product_restaurant_id );

        // Verificar que o produto foi criado
        $product = get_post( $product_id );
        $this->assertInstanceOf( 'WP_Post', $product );
        $this->assertEquals( 'vc_menu_item', $product->post_type );
    }

    /**
     * Teste: Rejeita criação sem restaurante válido
     */
    public function test_create_rejects_invalid_restaurant(): void {
        $service = new Menu_Items_Service();

        $data = [
            'title' => 'Produto Teste',
            'price' => '15.00',
        ];

        // Tentar criar com restaurante inválido
        $result = $service->create( $data, 0 );
        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertEquals( 'invalid_restaurant', $result->get_error_code() );

        // Tentar criar com restaurante inexistente
        $result = $service->create( $data, 99999 );
        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertEquals( 'restaurant_not_found', $result->get_error_code() );
    }
}

