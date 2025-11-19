<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class VC_Loader {
    public function init(): void {
        if ( function_exists( 'vc_register_front_assets' ) && function_exists( 'vc_register_admin_assets' ) ) {
            return;
        }

        add_action( 'init', [ $this, 'register_assets' ] );
    }

    public function register_assets(): void {
        // Admin
        wp_register_style( 'vemcomer-admin', VEMCOMER_CORE_URL . 'assets/css/admin.css', [], VEMCOMER_CORE_VERSION );
        wp_register_script( 'vemcomer-admin', VEMCOMER_CORE_URL . 'assets/js/admin.js', [], VEMCOMER_CORE_VERSION, true );

        // Frontend
        wp_register_style( 'vemcomer-front', VEMCOMER_CORE_URL . 'assets/css/frontend.css', [], VEMCOMER_CORE_VERSION );
        wp_register_script( 'vemcomer-front', VEMCOMER_CORE_URL . 'assets/js/frontend.js', [], VEMCOMER_CORE_VERSION, true );
    }
}
