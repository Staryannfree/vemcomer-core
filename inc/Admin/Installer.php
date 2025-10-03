<?php
/**
 * Installer — Cria páginas com shortcodes via Admin
 * @package VemComerCore
 */

namespace VC\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Installer {
    const OPTION_PAGES = 'vemcomer_pages'; // ['restaurants'=>ID, 'menu'=>ID, 'checkout'=>ID]

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_post_vc_install_page', [ $this, 'handle' ] );
        add_action( 'admin_post_vc_install_all', [ $this, 'handle_all' ] );
    }

    public function menu(): void {
        add_submenu_page( 'vemcomer-root', __( 'Instalador', 'vemcomer' ), __( 'Instalador', 'vemcomer' ), 'manage_options', 'vemcomer-installer', [ $this, 'render' ] );
    }

    private function pages_map(): array {
        return [
            'restaurants' => [
                'title'   => __( 'Restaurantes', 'vemcomer' ),
                'content' => '[vemcomer_restaurants]\n\n[vemcomer_checkout]',
                'desc'    => __( 'Lista os restaurantes e inclui um checkout básico na mesma página.', 'vemcomer' ),
            ],
            'menu' => [
                'title'   => __( 'Cardápio', 'vemcomer' ),
                'content' => '[vemcomer_menu]\n\n[vemcomer_checkout]',
                'desc'    => __( 'Mostra o cardápio do restaurante selecionado (?restaurant_id=ID) com checkout.', 'vemcomer' ),
            ],
            'checkout' => [
                'title'   => __( 'Checkout', 'vemcomer' ),
                'content' => '[vemcomer_checkout]',
                'desc'    => __( 'Checkout isolado, caso queira usar em uma página própria.', 'vemcomer' ),
            ],
        ];
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Sem permissão.', 'vemcomer' ) ); }
        $map = $this->pages_map();
        $created = (array) get_option( self::OPTION_PAGES, [] );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Instalador de Páginas (VemComer)', 'vemcomer' ) . '</h1>';
        if ( isset( $_GET['vc_installed'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Páginas atualizadas/criadas com sucesso.', 'vemcomer' ) . '</p></div>';
        }

        echo '<p>' . esc_html__( 'Clique para criar automaticamente as páginas públicas com os shortcodes do VemComer.', 'vemcomer' ) . '</p>';

        echo '<table class="widefat striped" style="max-width:880px">';
        echo '<thead><tr><th>' . esc_html__( 'Página', 'vemcomer' ) . '</th><th>' . esc_html__( 'Descrição', 'vemcomer' ) . '</th><th>' . esc_html__( 'Ação', 'vemcomer' ) . '</th><th>' . esc_html__( 'Status', 'vemcomer' ) . '</th></tr></thead><tbody>';
        foreach ( $map as $key => $cfg ) {
            $id = (int) ( $created[ $key ] ?? 0 );
            echo '<tr>';
            echo '<td><strong>' . esc_html( $cfg['title'] ) . '</strong></td>';
            echo '<td>' . esc_html( $cfg['desc'] ) . '<div style="color:#666;margin-top:4px"><code>' . esc_html( $cfg['content'] ) . '</code></div></td>';
            echo '<td>';
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="vc_install_page" />';
            echo '<input type="hidden" name="type" value="' . esc_attr( $key ) . '" />';
            wp_nonce_field( 'vc_install_' . $key, 'vc_install_nonce' );
            submit_button( $id ? __( 'Recriar', 'vemcomer' ) : __( 'Criar', 'vemcomer' ), 'secondary', '', false );
            echo '</form>';
            echo '</td>';
            echo '<td>';
            if ( $id && get_post( $id ) ) {
                echo '<span class="dashicons dashicons-yes" style="color:#46b450"></span> ';
                echo '<a href="' . esc_url( get_permalink( $id ) ) . '" target="_blank">' . esc_html__( 'Ver página', 'vemcomer' ) . '</a>';
            } else {
                echo '<span class="dashicons dashicons-minus"></span> ' . esc_html__( 'Não criada', 'vemcomer' );
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        echo '<p style="margin-top:14px">';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block">';
        echo '<input type="hidden" name="action" value="vc_install_all" />';
        wp_nonce_field( 'vc_install_all', 'vc_install_all_nonce' );
        submit_button( __( 'Criar todas', 'vemcomer' ), 'primary', '', false );
        echo '</form> ';
        echo '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '">' . esc_html__( 'Ir para Páginas', 'vemcomer' ) . '</a>';
        echo '</p>';

        echo '</div>';
    }

    public function handle(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Sem permissão.', 'vemcomer' ) ); }
        $type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
        check_admin_referer( 'vc_install_' . $type, 'vc_install_nonce' );
        $cfg = $this->pages_map();
        if ( ! isset( $cfg[ $type ] ) ) { wp_redirect( admin_url( 'admin.php?page=vemcomer-installer' ) ); exit; }
        $this->create_or_update_page( $type, $cfg[ $type ]['title'], $cfg[ $type ]['content'] );
        wp_redirect( add_query_arg( 'vc_installed', '1', admin_url( 'admin.php?page=vemcomer-installer' ) ) );
        exit;
    }

    public function handle_all(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Sem permissão.', 'vemcomer' ) ); }
        check_admin_referer( 'vc_install_all', 'vc_install_all_nonce' );
        $cfg = $this->pages_map();
        foreach ( $cfg as $key => $c ) {
            $this->create_or_update_page( $key, $c['title'], $c['content'] );
        }
        wp_redirect( add_query_arg( 'vc_installed', '1', admin_url( 'admin.php?page=vemcomer-installer' ) ) );
        exit;
    }

    private function create_or_update_page( string $key, string $title, string $content ): int {
        $pages = (array) get_option( self::OPTION_PAGES, [] );
        $existing_id = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
        $postarr = [
            'post_type'   => 'page',
            'post_title'  => $title,
            'post_content'=> $content,
            'post_status' => 'publish',
        ];
        if ( $existing_id && get_post( $existing_id ) ) {
            $postarr['ID'] = $existing_id;
            $id = wp_update_post( $postarr, true );
        } else {
            $id = wp_insert_post( $postarr, true );
        }
        if ( ! is_wp_error( $id ) && $id ) {
            $pages[ $key ] = (int) $id;
            update_option( self::OPTION_PAGES, $pages );
            return (int) $id;
        }
        return 0;
    }
}
