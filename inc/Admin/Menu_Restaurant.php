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
use VC\Model\CPT_ProductModifier;
use VC\Model\CPT_Event;

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
                __( 'Pedevem', 'vemcomer' ),
                __( 'Pedevem', 'vemcomer' ),
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

        add_submenu_page(
            'vemcomer-root',
            __( 'Modificadores', 'vemcomer' ),
            __( 'Modificadores', 'vemcomer' ),
            'edit_posts',
            'edit.php?post_type=' . CPT_ProductModifier::SLUG
        );

        if ( class_exists( '\\VC\\Model\\CPT_Review' ) ) {
            add_submenu_page(
                'vemcomer-root',
                __( 'Avaliações', 'vemcomer' ),
                __( 'Avaliações', 'vemcomer' ),
                'edit_posts',
                'edit.php?post_type=' . \VC\Model\CPT_Review::SLUG
            );
        }

        if ( class_exists( '\\VC\\Model\\CPT_Banner' ) ) {
            add_submenu_page(
                'vemcomer-root',
                __( 'Banners', 'vemcomer' ),
                __( 'Banners', 'vemcomer' ),
                'edit_posts',
                'edit.php?post_type=' . \VC\Model\CPT_Banner::SLUG
            );
        }

        if ( class_exists( '\\VC\\Model\\CPT_Event' ) ) {
            add_submenu_page(
                'vemcomer-root',
                __( 'Eventos', 'vemcomer' ),
                __( 'Eventos', 'vemcomer' ),
                'edit_posts',
                'edit.php?post_type=' . CPT_Event::SLUG
            );
        }

        if ( class_exists( '\\VC\\Model\\CPT_SubscriptionPlan' ) ) {
            add_submenu_page(
                'vemcomer-root',
                __( 'Planos de Assinatura', 'vemcomer' ),
                __( 'Planos', 'vemcomer' ),
                'manage_options', // Apenas admin pode gerenciar planos
                'edit.php?post_type=' . \VC\Model\CPT_SubscriptionPlan::SLUG
            );
        }
    }
}
