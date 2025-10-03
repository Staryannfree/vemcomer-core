<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class VC_Admin_Menu {
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
    }

    public function register_menu(): void {
        add_menu_page(
            'VemComer',
            'VemComer',
            'manage_options',
            'vemcomer-root',
            [ $this, 'render_root' ],
            'dashicons-store'
        );

        add_submenu_page( 'vemcomer-root', 'Produtos', 'Produtos', 'edit_posts', 'edit.php?post_type=' . VC_CPT_Produto::SLUG );
        add_submenu_page( 'vemcomer-root', 'Pedidos', 'Pedidos', 'edit_posts', 'edit.php?post_type=' . VC_CPT_Pedido::SLUG );
        add_submenu_page( 'vemcomer-root', 'Configurações', 'Configurações', 'manage_options', 'vemcomer-settings', [ $this, 'render_settings' ] );
    }

    public function render_root(): void {
        echo '<div class="wrap"><h1>VemComer</h1><p>Bem-vindo ao core do marketplace.</p></div>';
    }

    public function render_settings(): void {
        wp_enqueue_style( 'vemcomer-admin' );
        wp_enqueue_script( 'vemcomer-admin' );
        echo '<div class="wrap"><h1>Configurações</h1><p>Em breve: chaves de API, loja, etc.</p></div>';
    }
}
