<?php
/**
 * Installer — Cria páginas com shortcodes via Admin
 * Compatível com todos os shortcodes do repo.
 * Aceita ID manual e, se vazio, gera shortcode SEM atributo; os handlers pegam da query string via filtros.
 *
 * @package VemComerCore
 */

namespace VC\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Installer {
    const OPTION_PAGES = 'vemcomer_pages';

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_post_vc_install_page', [ $this, 'handle' ] );
        add_action( 'admin_post_vc_install_all', [ $this, 'handle_all' ] );
    }

    public function menu(): void {
        add_submenu_page(
            'vemcomer-root',
            __( 'Instalador', 'vemcomer' ),
            __( 'Instalador', 'vemcomer' ),
            'manage_options',
            'vemcomer-installer',
            [ $this, 'render' ]
        );
    }

    private function pages_map(): array {
        return [
            // --- Shortcodes "vc_*" ---
            'vc_restaurants' => [
                'title'     => __( 'Lista de Restaurantes (VC)', 'vemcomer' ),
                'desc'      => __( 'Lista todos os restaurantes com filtros.', 'vemcomer' ),
                'shortcode' => "[vc_filters]\n\n[vc_restaurants]",
                'needs'     => [],
            ],
            'vc_restaurant' => [
                'title'     => __( 'Página do Restaurante (VC)', 'vemcomer' ),
                'desc'      => __( 'Exibe o cartão de um restaurante específico (aceita ?restaurant_id=ID na URL).', 'vemcomer' ),
                'shortcode' => '[vc_restaurant id="{{restaurant_id}}"]',
                'needs'     => [ 'restaurant_id' ],
            ],
            'vc_menu_items' => [
                'title'     => __( 'Cardápio por Restaurante (VC)', 'vemcomer' ),
                'desc'      => __( 'Lista os itens do cardápio (aceita ?restaurant_id=ID na URL).', 'vemcomer' ),
                'shortcode' => '[vc_menu_items restaurant="{{restaurant_id}}"]',
                'needs'     => [ 'restaurant_id' ],
            ],
            'vc_filters' => [
                'title'     => __( 'Filtros (VC)', 'vemcomer' ),
                'desc'      => __( 'Renderiza apenas os filtros de restaurantes.', 'vemcomer' ),
                'shortcode' => '[vc_filters]',
                'needs'     => [],
            ],

            // --- Shortcodes "vemcomer_*" ---
            'vemcomer_restaurants' => [
                'title'     => __( 'Lista de Restaurantes (VemComer)', 'vemcomer' ),
                'desc'      => __( 'Lista restaurantes usando o conjunto de shortcodes VemComer.', 'vemcomer' ),
                'shortcode' => '[vemcomer_restaurants]',
                'needs'     => [],
            ],
            'vemcomer_menu' => [
                'title'     => __( 'Cardápio (VemComer)', 'vemcomer' ),
                'desc'      => __( 'Mostra o cardápio do restaurante corrente (pode usar ?restaurant_id=ID).', 'vemcomer' ),
                'shortcode' => '[vemcomer_menu]',
                'needs'     => [],
            ],
            'vemcomer_checkout' => [
                'title'     => __( 'Checkout (VemComer)', 'vemcomer' ),
                'desc'      => __( 'Renderiza o checkout isolado.', 'vemcomer' ),
                'shortcode' => '[vemcomer_checkout]',
                'needs'     => [],
            ],
        ];
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Sem permissão.', 'vemcomer' ) ); }

        $map     = $this->pages_map();
        $created = (array) get_option( self::OPTION_PAGES, [] );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Instalador de Páginas (VemComer)', 'vemcomer' ) . '</h1>';
        if ( isset( $_GET['vc_installed'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Páginas atualizadas/criadas com sucesso.', 'vemcomer' ) . '</p></div>';
        }

        echo '<p>' . esc_html__( 'Crie automaticamente páginas públicas contendo os shortcodes do VemComer.', 'vemcomer' ) . '</p>';

        echo '<table class="widefat striped" style="max-width:980px">';
        echo '<thead><tr>'
            . '<th>' . esc_html__( 'Página', 'vemcomer' ) . '</th>'
            . '<th>' . esc_html__( 'Descrição', 'vemcomer' ) . '</th>'
            . '<th>' . esc_html__( 'Shortcode', 'vemcomer' ) . '</th>'
            . '<th>' . esc_html__( 'Ação', 'vemcomer' ) . '</th>'
            . '<th>' . esc_html__( 'Status', 'vemcomer' ) . '</th>'
            . '</tr></thead><tbody>';

        foreach ( $map as $key => $cfg ) {
            $id    = isset( $created[ $key ] ) ? (int) $created[ $key ] : 0;
            $sc    = $cfg['shortcode'] ?? '';
            $needs = $cfg['needs'] ?? [];

            echo '<tr>';
            echo '<td><strong>' . esc_html( $cfg['title'] ) . '</strong></td>';
            echo '<td>' . esc_html( $cfg['desc'] ) . '</td>';
            echo '<td><code>' . esc_html( $sc ) . '</code></td>';
            echo '<td>';

            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">';
            echo '<input type="hidden" name="action" value="vc_install_page" />';
            echo '<input type="hidden" name="type" value="' . esc_attr( $key ) . '" />';

            if ( in_array( 'restaurant_id', $needs, true ) ) {
                echo '<label>' . esc_html__( 'ID do restaurante (opcional)', 'vemcomer' ) . ' ';
                echo '<input type="number" name="restaurant_id" min="1" style="width:120px" placeholder="123" />';
                echo '</label>';
                echo '<p style="margin:4px 0 0;font-size:12px;color:#555">' . esc_html__( 'Se vazio, a página funcionará com ?restaurant_id= na URL.', 'vemcomer' ) . '</p>';
            }

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
        submit_button( __( 'Criar todas (quando possível)', 'vemcomer' ), 'primary', '', false );
        echo '</form> ';
        echo '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '">' . esc_html__( 'Ir para Páginas', 'vemcomer' ) . '</a>';
        echo '</p>';

        echo '</div>';
    }

    public function handle(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Sem permissão.', 'vemcomer' ) ); }

        $type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
        check_admin_referer( 'vc_install_' . $type, 'vc_install_nonce' );

        $map = $this->pages_map();
        if ( ! isset( $map[ $type ] ) ) {
            wp_redirect( admin_url( 'admin.php?page=vemcomer-installer' ) );
            exit;
        }

        $tpl   = (string) $map[ $type ]['shortcode'];
        $needs = (array) ( $map[ $type ]['needs'] ?? [] );

        $params = [];
        if ( in_array( 'restaurant_id', $needs, true ) ) {
            $params['restaurant_id'] = isset( $_POST['restaurant_id'] ) ? absint( $_POST['restaurant_id'] ) : 0;
        }

        $content = $this->resolve_placeholders( $tpl, $params );
        $this->create_or_update_page( $type, (string) $map[ $type ]['title'], $content );

        wp_redirect( add_query_arg( 'vc_installed', '1', admin_url( 'admin.php?page=vemcomer-installer' ) ) );
        exit;
    }

    public function handle_all(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Sem permissão.', 'vemcomer' ) ); }
        check_admin_referer( 'vc_install_all', 'vc_install_all_nonce' );

        $map = $this->pages_map();
        foreach ( $map as $key => $cfg ) {
            $needs = $cfg['needs'] ?? [];
            if ( ! empty( $needs ) ) { continue; }
            $this->create_or_update_page( $key, (string) $cfg['title'], (string) $cfg['shortcode'] );
        }

        wp_redirect( add_query_arg( 'vc_installed', '1', admin_url( 'admin.php?page=vemcomer-installer' ) ) );
        exit;
    }

    /**
     * Substitui placeholders e remove atributos não resolvidos.
     */
    private function resolve_placeholders( string $template, array $params ): string {
        $out = $template;
        foreach ( $params as $k => $v ) {
            if ( $v !== '' && $v !== null ) {
                $out = str_replace( '{{' . $k . '}}', (string) $v, $out );
            }
        }
        // Remove atributos que ficaram com {{placeholder}}
        $out = preg_replace( '/\s+[\w\-]+="\{\{[\w\-]+\}\}"/', '', $out );
        return (string) $out;
    }

    private function create_or_update_page( string $key, string $title, string $content ): int {
        $pages       = (array) get_option( self::OPTION_PAGES, [] );
        $existing_id = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;

        $postarr = [
            'post_type'    => 'page',
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
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
