<?php
/**
 * Menu_Restaurant — Submenus de Admin para Restaurantes e Cardápio
 * Fallback: cria o menu raiz "VemComer" (slug: vemcomer-root) se não existir —
 * assim os submenus aparecem mesmo sem o pacote 1 instalado.
 *
 * @package VemComerCore
 */

namespace VC\Admin;

use VC\Model\CPT_Restaurant;
use VC\Model\CPT_MenuItem;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Menu_Restaurant {
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
    }

    public function register_menu(): void {
        // Fallback: garante que o menu raiz exista
        global $menu, $admin_page_hooks;
        if ( ! isset( $admin_page_hooks['vemcomer-root'] ) ) {
            add_menu_page(
                __( 'VemComer', 'vemcomer' ),
                __( 'VemComer', 'vemcomer' ),
                'edit_posts',
                'vemcomer-root',
                '__return_null',
                'dashicons-store',
                25
            );
        }

        add_submenu_page(
            'vemcomer-root',
            __( 'Restaurantes', 'vemcomer' ),
            __( 'Restaurantes', 'vemcomer' ),
            'edit_posts',
            'edit.php?post_type=' . CPT_Restaurant::SLUG
        );

        add_submenu_page(
            'vemcomer-root',
            __( 'Cardápio', 'vemcomer' ),
            __( 'Cardápio', 'vemcomer' ),
            'edit_posts',
            'edit.php?post_type=' . CPT_MenuItem::SLUG
        );
    }
}
