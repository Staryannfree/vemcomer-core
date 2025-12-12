<?php
/**
 * Testes para Category_Helper
 * 
 * @package VemComerCore
 */

use VC\Utils\Category_Helper;

class Category_Helper_Test extends VemComer_TestCase {
    /**
     * Teste: Categoria de restaurante tem _vc_restaurant_id
     */
    public function test_create_restaurant_category_has_meta(): void {
        $restaurant_id = $this->create_test_restaurant();

        $term_id = Category_Helper::create_restaurant_category( $restaurant_id, 'Categoria Teste' );

        $this->assertIsInt( $term_id );
        $this->assertGreaterThan( 0, $term_id );

        // Verificar que tem _vc_restaurant_id
        $cat_restaurant_id = (int) get_term_meta( $term_id, '_vc_restaurant_id', true );
        $this->assertEquals( $restaurant_id, $cat_restaurant_id );
    }

    /**
     * Teste: Categoria de restaurante não tem _vc_is_catalog_category
     */
    public function test_create_restaurant_category_not_catalog(): void {
        $restaurant_id = $this->create_test_restaurant();

        $term_id = Category_Helper::create_restaurant_category( $restaurant_id, 'Categoria Teste' );

        // Verificar que não é categoria de catálogo
        $is_catalog = get_term_meta( $term_id, '_vc_is_catalog_category', true );
        $this->assertEmpty( $is_catalog );

        // Verificar usando o helper
        $this->assertFalse( Category_Helper::is_catalog_category( $term_id ) );
    }

    /**
     * Teste: Verifica categoria de catálogo corretamente
     */
    public function test_is_catalog_category(): void {
        // Criar categoria de catálogo manualmente
        $result = wp_insert_term( 'Categoria Catálogo', 'vc_menu_category' );
        $term_id = (int) $result['term_id'];
        update_term_meta( $term_id, '_vc_is_catalog_category', '1' );

        // Verificar que é categoria de catálogo
        $this->assertTrue( Category_Helper::is_catalog_category( $term_id ) );

        // Criar categoria de restaurante
        $restaurant_id = $this->create_test_restaurant();
        $restaurant_term_id = Category_Helper::create_restaurant_category( $restaurant_id, 'Categoria Restaurante' );

        // Verificar que não é categoria de catálogo
        $this->assertFalse( Category_Helper::is_catalog_category( $restaurant_term_id ) );
    }
}

