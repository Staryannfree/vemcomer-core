<?php
/**
 * Menu_Restaurant — Submenus de Admin para Restaurantes e Cardápio
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
        // Dependemos do menu principal 'vemcomer-root' criado por VC_Admin_Menu do pacote 1
        add_submenu_page( 'vemcomer-root', __( 'Restaurantes', 'vemcomer' ), __( 'Restaurantes', 'vemcomer' ), 'edit_posts', 'edit.php?post_type=' . CPT_Restaurant::SLUG );
        add_submenu_page( 'vemcomer-root', __( 'Cardápio', 'vemcomer' ), __( 'Cardápio', 'vemcomer' ), 'edit_posts', 'edit.php?post_type=' . CPT_MenuItem::SLUG );
    }
}
