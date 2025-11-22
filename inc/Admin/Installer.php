<?php
/**
 * Installer — Cria páginas com shortcodes via Admin
 * Versão simplificada (sem placeholders nem regex), estável em PHP 7.4+.
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
        
        add_submenu_page(
            'vemcomer-root',
            __( 'Importar Páginas', 'vemcomer' ),
            __( 'Importar Páginas', 'vemcomer' ),
            'manage_options',
            'vemcomer-import-pages',
            [ $this, 'render_import' ]
        );
    }

    private function pages_map(): array {
        return [
            'vc_restaurants' => [
                'title'     => __( 'Lista de Restaurantes (VC)', 'vemcomer' ),
                'desc'      => __( 'Lista todos os restaurantes com filtros.', 'vemcomer' ),
                'shortcode' => "[vc_filters]\n\n[vc_restaurants]",
                'needs'     => [],
            ],
            'vc_restaurant' => [
                'title'     => __( 'Página do Restaurante (VC)', 'vemcomer' ),
                'desc'      => __( 'Exibe o cartão de um restaurante (aceita ?restaurant_id=ID).', 'vemcomer' ),
                'shortcode' => '', // construído dinamicamente
                'needs'     => [ 'restaurant_id' ],
            ],
            'vc_menu_items' => [
                'title'     => __( 'Cardápio por Restaurante (VC)', 'vemcomer' ),
                'desc'      => __( 'Lista os itens do cardápio (aceita ?restaurant_id=ID).', 'vemcomer' ),
                'shortcode' => '', // construído dinamicamente
                'needs'     => [ 'restaurant_id' ],
            ],
            'vc_filters' => [
                'title'     => __( 'Filtros (VC)', 'vemcomer' ),
                'desc'      => __( 'Renderiza apenas os filtros de restaurantes.', 'vemcomer' ),
                'shortcode' => '[vc_filters]',
                'needs'     => [],
            ],
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
            'vemcomer_restaurant_panel' => [
                'title'     => __( 'Painel do Restaurante (VemComer)', 'vemcomer' ),
                'desc'      => __( 'Área logada para restaurantes gerenciarem dados e pedidos.', 'vemcomer' ),
                'shortcode' => '[vemcomer_restaurant_panel]',
                'needs'     => [],
            ],
            'vemcomer_restaurant_signup' => [
                'title'     => __( 'Cadastro de Restaurantes (VemComer)', 'vemcomer' ),
                'desc'      => __( 'Formulário público para restaurantes enviarem seus dados.', 'vemcomer' ),
                'shortcode' => '[vemcomer_restaurant_signup]',
                'needs'     => [],
            ],
            'vemcomer_customer_signup' => [
                'title'     => __( 'Cadastro de Clientes (VemComer)', 'vemcomer' ),
                'desc'      => __( 'Formulário para consumidores criarem contas no marketplace.', 'vemcomer' ),
                'shortcode' => '[vemcomer_customer_signup]',
                'needs'     => [],
            ],
            'vc_restaurants_map' => [
                'title'     => __( 'Mapa de Restaurantes (VC)', 'vemcomer' ),
                'desc'      => __( 'Mapa público com todos os restaurantes e botão "Perto de mim".', 'vemcomer' ),
                'shortcode' => '[vc_restaurants_map]',
                'needs'     => [],
            ],
            'vc_reviews' => [
                'title'     => __( 'Avaliações e Reviews (VC)', 'vemcomer' ),
                'desc'      => __( 'Exibe avaliações de um restaurante e permite criar nova avaliação (aceita ?restaurant_id=ID).', 'vemcomer' ),
                'shortcode' => '', // construído dinamicamente
                'needs'     => [ 'restaurant_id' ],
            ],
            'vc_favorites' => [
                'title'     => __( 'Meus Favoritos (VC)', 'vemcomer' ),
                'desc'      => __( 'Lista restaurantes e itens favoritos do usuário logado.', 'vemcomer' ),
                'shortcode' => '[vc_favorites]',
                'needs'     => [],
            ],
            'vc_banners' => [
                'title'     => __( 'Banners da Home (VC)', 'vemcomer' ),
                'desc'      => __( 'Exibe banners ativos da plataforma.', 'vemcomer' ),
                'shortcode' => '[vc_banners]',
                'needs'     => [],
            ],
            'vc_notifications' => [
                'title'     => __( 'Notificações (VC)', 'vemcomer' ),
                'desc'      => __( 'Lista notificações do usuário logado.', 'vemcomer' ),
                'shortcode' => '[vc_notifications]',
                'needs'     => [],
            ],
            'vc_orders_history' => [
                'title'     => __( 'Histórico de Pedidos (VC)', 'vemcomer' ),
                'desc'      => __( 'Lista histórico de pedidos do usuário logado.', 'vemcomer' ),
                'shortcode' => '[vc_orders_history]',
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
            $sc    = $cfg['shortcode'];
            $needs = $cfg['needs'];

            if ( $key === 'vc_restaurant' ) {
                $sc = '[vc_restaurant id="123"] (ou sem id, usando ?restaurant_id=)';
            } elseif ( $key === 'vc_menu_items' ) {
                $sc = '[vc_menu_items restaurant="123"] (ou sem restaurant, usando ?restaurant_id=)';
            } elseif ( $key === 'vc_reviews' ) {
                $sc = '[vc_reviews restaurant_id="123"] (ou sem restaurant_id, usando ?restaurant_id=)';
            }

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
                if ( $key === 'vc_reviews' ) {
                    echo '<p style="margin:4px 0 0;font-size:12px;color:#555">' . esc_html__( 'Se vazio, a página funcionará com ?restaurant_id= na URL ou no contexto da página do restaurante.', 'vemcomer' ) . '</p>';
                } else {
                    echo '<p style="margin:4px 0 0;font-size:12px;color:#555">' . esc_html__( 'Se vazio, a página funcionará com ?restaurant_id= na URL.', 'vemcomer' ) . '</p>';
                }
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

        $restaurant_id = isset( $_POST['restaurant_id'] ) ? absint( $_POST['restaurant_id'] ) : 0;
        $content = $this->build_content( $type, $restaurant_id );

        $this->create_or_update_page( $type, (string) $map[ $type ]['title'], $content );

        wp_redirect( add_query_arg( 'vc_installed', '1', admin_url( 'admin.php?page=vemcomer-installer' ) ) );
        exit;
    }

    public function handle_all(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Sem permissão.', 'vemcomer' ) ); }
        check_admin_referer( 'vc_install_all', 'vc_install_all_nonce' );

        $this->install_defaults();

        wp_redirect( add_query_arg( 'vc_installed', '1', admin_url( 'admin.php?page=vemcomer-installer' ) ) );
        exit;
    }

    /**
     * Cria/atualiza todas as páginas que não dependem de parâmetros.
     */
    public function install_defaults(): void {
        $map = $this->pages_map();
        foreach ( $map as $key => $cfg ) {
            $needs = $cfg['needs'];
            if ( ! empty( $needs ) ) {
                continue;
            }

            $content = $this->build_content( $key, 0 );
            $this->create_or_update_page( $key, (string) $cfg['title'], $content );
        }
    }

    /**
     * Constrói o conteúdo do shortcode sem usar placeholders.
     */
    private function build_content( string $type, int $restaurant_id ): string {
        switch ( $type ) {
            case 'vc_restaurant':
                return $restaurant_id > 0
                    ? '[vc_restaurant id="' . $restaurant_id . '"]'
                    : '[vc_restaurant]';
            case 'vc_menu_items':
                return $restaurant_id > 0
                    ? '[vc_menu_items restaurant="' . $restaurant_id . '"]'
                    : '[vc_menu_items]';
            case 'vc_reviews':
                return $restaurant_id > 0
                    ? '[vc_reviews restaurant_id="' . $restaurant_id . '"]'
                    : '[vc_reviews]';
            case 'vc_restaurants':
                return "[vc_filters]\n\n[vc_restaurants]";
            case 'vc_filters':
                return '[vc_filters]';
            case 'vc_restaurants_map':
                return '[vc_restaurants_map]';
            case 'vc_favorites':
                return '[vc_favorites]';
            case 'vc_banners':
                return '[vc_banners]';
            case 'vc_notifications':
                return '[vc_notifications]';
            case 'vc_orders_history':
                return '[vc_orders_history]';
            case 'vemcomer_restaurants':
                return '[vemcomer_restaurants]';
            case 'vemcomer_menu':
                return '[vemcomer_menu]';
            case 'vemcomer_checkout':
                return '[vemcomer_checkout]';
            case 'vemcomer_restaurant_panel':
                return '[vemcomer_restaurant_panel]';
            case 'vemcomer_restaurant_signup':
                return '[vemcomer_restaurant_signup]';
            case 'vemcomer_customer_signup':
                return '[vemcomer_customer_signup]';
            default:
                return '';
        }
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
    
    /**
     * Renderiza a página de importação de templates
     */
    public function render_import(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Sem permissão.', 'vemcomer' ) );
        }
        
        // Processar importação
        if ( isset( $_POST['vc_import_pages'] ) && check_admin_referer( 'vc_import_pages', 'vc_import_pages_nonce' ) ) {
            $templates_to_import = isset( $_POST['templates'] ) ? array_map( 'sanitize_key', (array) $_POST['templates'] ) : [];
            
            if ( ! empty( $templates_to_import ) ) {
                require_once VEMCOMER_CORE_DIR . 'inc/Admin/Page_Templates.php';
                $results = \VC\Admin\Page_Templates::import_pages( $templates_to_import );
                
                $success_count = count( array_filter( $results, fn( $r ) => $r['success'] ) );
                $error_count = count( $results ) - $success_count;
                
                echo '<div class="notice notice-success is-dismissible"><p>';
                printf(
                    esc_html__( '%d página(s) importada(s) com sucesso. %d erro(s).', 'vemcomer' ),
                    $success_count,
                    $error_count
                );
                echo '</p></div>';
            }
        }
        
        require_once VEMCOMER_CORE_DIR . 'inc/Admin/Page_Templates.php';
        $templates = \VC\Admin\Page_Templates::get_templates();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Importar Páginas Pré-configuradas', 'vemcomer' ); ?></h1>
            <p class="description">
                <?php echo esc_html__( 'Selecione as páginas que deseja importar. Páginas já existentes serão atualizadas.', 'vemcomer' ); ?>
            </p>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'vc_import_pages', 'vc_import_pages_nonce' ); ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="select-all-templates" />
                            </th>
                            <th><?php echo esc_html__( 'Página', 'vemcomer' ); ?></th>
                            <th><?php echo esc_html__( 'Descrição', 'vemcomer' ); ?></th>
                            <th><?php echo esc_html__( 'Slug', 'vemcomer' ); ?></th>
                            <th><?php echo esc_html__( 'Status', 'vemcomer' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $templates as $key => $template ) : ?>
                            <?php
                            $existing = get_page_by_path( $template['slug'] );
                            $is_featured = ! empty( $template['featured'] );
                            ?>
                            <tr class="<?php echo $is_featured ? 'featured' : ''; ?>">
                                <td>
                                    <input 
                                        type="checkbox" 
                                        name="templates[]" 
                                        value="<?php echo esc_attr( $key ); ?>"
                                        class="template-checkbox"
                                        <?php checked( $is_featured ); ?>
                                    />
                                </td>
                                <td>
                                    <strong><?php echo esc_html( $template['title'] ); ?></strong>
                                    <?php if ( $is_featured ) : ?>
                                        <span class="dashicons dashicons-star-filled" style="color: #f0b849; margin-left: 5px;" title="<?php esc_attr_e( 'Recomendado', 'vemcomer' ); ?>"></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $template['description'] ); ?></td>
                                <td><code><?php echo esc_html( $template['slug'] ); ?></code></td>
                                <td>
                                    <?php if ( $existing ) : ?>
                                        <span class="dashicons dashicons-yes" style="color:#46b450"></span>
                                        <a href="<?php echo esc_url( get_permalink( $existing->ID ) ); ?>" target="_blank">
                                            <?php echo esc_html__( 'Ver página', 'vemcomer' ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-minus"></span>
                                        <?php echo esc_html__( 'Não criada', 'vemcomer' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 20px;">
                    <button type="button" class="button" id="select-featured"><?php echo esc_html__( 'Selecionar Recomendadas', 'vemcomer' ); ?></button>
                    <button type="button" class="button" id="deselect-all"><?php echo esc_html__( 'Desmarcar Todas', 'vemcomer' ); ?></button>
                </p>
                
                <p style="margin-top: 20px;">
                    <?php submit_button( __( 'Importar Páginas Selecionadas', 'vemcomer' ), 'primary', 'vc_import_pages', false ); ?>
                    <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>">
                        <?php echo esc_html__( 'Ir para Páginas', 'vemcomer' ); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <style>
            .wp-list-table .featured {
                background-color: #fff9e5;
            }
            .wp-list-table .featured:hover {
                background-color: #fff4cc;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#select-all-templates').on('change', function() {
                $('.template-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            $('#select-featured').on('click', function() {
                $('.template-checkbox').prop('checked', false);
                $('.featured .template-checkbox').prop('checked', true);
            });
            
            $('#deselect-all').on('click', function() {
                $('.template-checkbox').prop('checked', false);
                $('#select-all-templates').prop('checked', false);
            });
        });
        </script>
        <?php
    }
}
