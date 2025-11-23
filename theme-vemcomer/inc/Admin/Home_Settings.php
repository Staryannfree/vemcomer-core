<?php
/**
 * Home_Settings — Configurações da Home Page via Settings API
 * Sistema modular nativo sem dependências de page builders
 *
 * @package VemComer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VemComer_Home_Settings {

    const OPTION_NAME = 'vemcomer_home_options';

    /**
     * Inicializa a classe
     */
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Adiciona página de menu no admin
     */
    public function add_menu_page(): void {
        // Verificar se o menu raiz existe (do plugin)
        global $admin_page_hooks;
        
        // Se não existir, criar um menu temporário no tema
        if ( ! isset( $admin_page_hooks['vemcomer-root'] ) ) {
            add_menu_page(
                __( 'VemComer', 'vemcomer' ),
                __( 'VemComer', 'vemcomer' ),
                'manage_options',
                'vemcomer-root',
                '__return_null',
                'dashicons-store',
                25
            );
        }

        add_submenu_page(
            'vemcomer-root',
            __( 'Editar Home', 'vemcomer' ),
            __( 'Editar Home', 'vemcomer' ),
            'manage_options',
            'vemcomer-home-settings',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Registra as configurações usando Settings API
     */
    public function register_settings(): void {
        register_setting(
            'vemcomer_home_settings_group',
            self::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize_options' ],
                'default'           => $this->get_default_options(),
            ]
        );

        // Seção: Hero
        add_settings_section(
            'vemcomer_hero_section',
            __( 'Seção Hero (Banner Principal)', 'vemcomer' ),
            [ $this, 'render_hero_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'hero_ativo',
            __( 'Ativar Seção Hero', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_hero_section',
            [
                'option_key' => 'hero_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir seção hero na home', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'hero_titulo',
            __( 'Título', 'vemcomer' ),
            [ $this, 'render_text_field' ],
            'vemcomer_home_settings',
            'vemcomer_hero_section',
            [
                'option_key' => 'hero_section',
                'field_key'  => 'titulo',
                'default'    => __( 'Peça dos melhores restaurantes da sua cidade', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'hero_subtitulo',
            __( 'Subtítulo', 'vemcomer' ),
            [ $this, 'render_text_field' ],
            'vemcomer_home_settings',
            'vemcomer_hero_section',
            [
                'option_key' => 'hero_section',
                'field_key'  => 'subtitulo',
                'default'    => __( 'Entrega, retirada e cardápios atualizados em tempo real', 'vemcomer' ),
            ]
        );

        // Seção: Banners
        add_settings_section(
            'vemcomer_banners_section',
            __( 'Seção de Banners Promocionais', 'vemcomer' ),
            [ $this, 'render_banners_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'banners_ativo',
            __( 'Ativar Seção de Banners', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_banners_section',
            [
                'option_key' => 'banners_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir banners promocionais', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'banners_quantidade',
            __( 'Quantidade de Banners', 'vemcomer' ),
            [ $this, 'render_number_field' ],
            'vemcomer_home_settings',
            'vemcomer_banners_section',
            [
                'option_key' => 'banners_section',
                'field_key'  => 'quantidade',
                'default'    => 5,
                'min'        => 1,
                'max'        => 20,
            ]
        );

        // Seção: Categorias
        add_settings_section(
            'vemcomer_categories_section',
            __( 'Seção de Categorias Populares', 'vemcomer' ),
            [ $this, 'render_categories_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'categories_ativo',
            __( 'Ativar Seção de Categorias', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_categories_section',
            [
                'option_key' => 'categories_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir categorias populares', 'vemcomer' ),
            ]
        );

        // Seção: Restaurantes em Destaque
        add_settings_section(
            'vemcomer_featured_section',
            __( 'Restaurantes em Destaque', 'vemcomer' ),
            [ $this, 'render_featured_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'featured_ativo',
            __( 'Ativar Restaurantes em Destaque', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_featured_section',
            [
                'option_key' => 'featured_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir restaurantes em destaque', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'featured_titulo',
            __( 'Título da Seção', 'vemcomer' ),
            [ $this, 'render_text_field' ],
            'vemcomer_home_settings',
            'vemcomer_featured_section',
            [
                'option_key' => 'featured_section',
                'field_key'  => 'titulo',
                'default'    => __( 'Restaurantes em Destaque', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'featured_restaurants',
            __( 'Selecionar Restaurantes', 'vemcomer' ),
            [ $this, 'render_restaurants_selector' ],
            'vemcomer_home_settings',
            'vemcomer_featured_section',
            [
                'option_key' => 'featured_section',
                'field_key'  => 'restaurant_ids',
            ]
        );

        add_settings_field(
            'featured_quantidade',
            __( 'Quantidade Máxima de Restaurantes', 'vemcomer' ),
            [ $this, 'render_number_field' ],
            'vemcomer_home_settings',
            'vemcomer_featured_section',
            [
                'option_key' => 'featured_section',
                'field_key'  => 'quantidade',
                'default'    => 6,
                'min'        => 1,
                'max'        => 20,
            ]
        );

        // Seção: Listagem de Restaurantes
        add_settings_section(
            'vemcomer_restaurants_section',
            __( 'Listagem de Restaurantes', 'vemcomer' ),
            [ $this, 'render_restaurants_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'restaurants_ativo',
            __( 'Ativar Listagem de Restaurantes', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_restaurants_section',
            [
                'option_key' => 'restaurants_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir listagem de restaurantes', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'restaurants_titulo',
            __( 'Título da Seção', 'vemcomer' ),
            [ $this, 'render_text_field' ],
            'vemcomer_home_settings',
            'vemcomer_restaurants_section',
            [
                'option_key' => 'restaurants_section',
                'field_key'  => 'titulo',
                'default'    => __( 'Restaurantes', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'restaurants_quantidade',
            __( 'Quantidade de Restaurantes', 'vemcomer' ),
            [ $this, 'render_number_field' ],
            'vemcomer_home_settings',
            'vemcomer_restaurants_section',
            [
                'option_key' => 'restaurants_section',
                'field_key'  => 'quantidade',
                'default'    => 12,
                'min'        => 1,
                'max'        => 50,
            ]
        );

        add_settings_field(
            'restaurants_ordenar_por',
            __( 'Ordenar Por', 'vemcomer' ),
            [ $this, 'render_select_field' ],
            'vemcomer_home_settings',
            'vemcomer_restaurants_section',
            [
                'option_key' => 'restaurants_section',
                'field_key'  => 'ordenar_por',
                'default'    => 'date',
                'options'     => [
                    'date'       => __( 'Data de cadastro (mais recentes)', 'vemcomer' ),
                    'title'      => __( 'Nome (A-Z)', 'vemcomer' ),
                    'rating'     => __( 'Avaliação (maior primeiro)', 'vemcomer' ),
                    'menu_order' => __( 'Ordem personalizada', 'vemcomer' ),
                ],
            ]
        );

        // Seção: Destaques do Dia
        add_settings_section(
            'vemcomer_daily_highlights_section',
            __( 'Destaques do Dia', 'vemcomer' ),
            [ $this, 'render_daily_highlights_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'daily_highlights_ativo',
            __( 'Ativar Destaques do Dia', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_daily_highlights_section',
            [
                'option_key' => 'daily_highlights_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir seção de destaques do dia', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'daily_highlights_titulo',
            __( 'Título da Seção', 'vemcomer' ),
            [ $this, 'render_text_field' ],
            'vemcomer_home_settings',
            'vemcomer_daily_highlights_section',
            [
                'option_key' => 'daily_highlights_section',
                'field_key'  => 'titulo',
                'default'    => __( 'Destaques do Dia', 'vemcomer' ),
            ]
        );

        add_settings_field(
            'daily_highlights_items',
            __( 'Selecionar Produtos/Cardápios', 'vemcomer' ),
            [ $this, 'render_menu_items_selector' ],
            'vemcomer_home_settings',
            'vemcomer_daily_highlights_section',
            [
                'option_key' => 'daily_highlights_section',
                'field_key'  => 'menu_items',
            ]
        );

        add_settings_field(
            'daily_highlights_quantidade',
            __( 'Quantidade Máxima de Destaques', 'vemcomer' ),
            [ $this, 'render_number_field' ],
            'vemcomer_home_settings',
            'vemcomer_daily_highlights_section',
            [
                'option_key' => 'daily_highlights_section',
                'field_key'  => 'quantidade',
                'default'    => 6,
                'min'        => 1,
                'max'        => 20,
            ]
        );

        // Seção: Mapa
        add_settings_section(
            'vemcomer_map_section',
            __( 'Mapa de Restaurantes', 'vemcomer' ),
            [ $this, 'render_map_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'map_ativo',
            __( 'Ativar Mapa', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_map_section',
            [
                'option_key' => 'map_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir mapa de restaurantes', 'vemcomer' ),
            ]
        );

        // Seção: Para Você (usuários logados)
        add_settings_section(
            'vemcomer_for_you_section',
            __( 'Seção "Para Você"', 'vemcomer' ),
            [ $this, 'render_for_you_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'for_you_ativo',
            __( 'Ativar Seção "Para Você"', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_for_you_section',
            [
                'option_key' => 'for_you_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir seção para usuários logados (favoritos e pedidos)', 'vemcomer' ),
            ]
        );

        // Seção: CTA para Donos
        add_settings_section(
            'vemcomer_cta_section',
            __( 'CTA para Donos de Restaurantes', 'vemcomer' ),
            [ $this, 'render_cta_section_description' ],
            'vemcomer_home_settings'
        );

        add_settings_field(
            'cta_ativo',
            __( 'Ativar CTA', 'vemcomer' ),
            [ $this, 'render_checkbox_field' ],
            'vemcomer_home_settings',
            'vemcomer_cta_section',
            [
                'option_key' => 'cta_section',
                'field_key'  => 'ativo',
                'label'      => __( 'Exibir chamada para cadastro de restaurantes', 'vemcomer' ),
            ]
        );
    }

    /**
     * Retorna opções padrão
     */
    private function get_default_options(): array {
        return [
            'hero_section' => [
                'ativo'    => true,
                'titulo'   => __( 'Peça dos melhores restaurantes da sua cidade', 'vemcomer' ),
                'subtitulo' => __( 'Entrega, retirada e cardápios atualizados em tempo real', 'vemcomer' ),
            ],
            'banners_section' => [
                'ativo'     => true,
                'quantidade' => 5,
            ],
            'categories_section' => [
                'ativo' => true,
            ],
            'featured_section' => [
                'ativo'        => true,
                'titulo'       => __( 'Restaurantes em Destaque', 'vemcomer' ),
                'restaurant_ids' => [], // Array de IDs de restaurantes
                'quantidade'   => 6,
            ],
            'restaurants_section' => [
                'ativo'      => true,
                'titulo'     => __( 'Restaurantes', 'vemcomer' ),
                'quantidade' => 12,
                'ordenar_por' => 'date',
            ],
            'daily_highlights_section' => [
                'ativo'     => true,
                'titulo'    => __( 'Destaques do Dia', 'vemcomer' ),
                'menu_items' => [], // Array de IDs de menu items
                'quantidade' => 6,
            ],
            'map_section' => [
                'ativo' => true,
            ],
            'for_you_section' => [
                'ativo' => true,
            ],
            'cta_section' => [
                'ativo' => true,
            ],
        ];
    }

    /**
     * Sanitiza as opções
     */
    public function sanitize_options( $input ): array {
        $sanitized = [];
        $defaults  = $this->get_default_options();

        foreach ( $defaults as $section_key => $section_defaults ) {
            $sanitized[ $section_key ] = [];

            // Ativo (boolean)
            if ( isset( $input[ $section_key ]['ativo'] ) ) {
                $sanitized[ $section_key ]['ativo'] = (bool) $input[ $section_key ]['ativo'];
            } else {
                $sanitized[ $section_key ]['ativo'] = $section_defaults['ativo'] ?? false;
            }

            // Outros campos
            foreach ( $section_defaults as $field_key => $default_value ) {
                if ( $field_key === 'ativo' ) {
                    continue; // Já tratado acima
                }

                if ( isset( $input[ $section_key ][ $field_key ] ) ) {
                    if ( is_int( $default_value ) ) {
                        $sanitized[ $section_key ][ $field_key ] = absint( $input[ $section_key ][ $field_key ] );
                    } elseif ( is_string( $default_value ) ) {
                        $sanitized[ $section_key ][ $field_key ] = sanitize_text_field( $input[ $section_key ][ $field_key ] );
                    } elseif ( is_array( $default_value ) || $field_key === 'menu_items' || $field_key === 'restaurant_ids' ) {
                        // Tratar array de menu items ou restaurant_ids
                        $value = $input[ $section_key ][ $field_key ];
                        if ( is_string( $value ) && ! empty( $value ) ) {
                            // Se vier como string separada por vírgula
                            $ids = array_map( 'absint', explode( ',', $value ) );
                            $sanitized[ $section_key ][ $field_key ] = array_filter( $ids );
                        } elseif ( is_array( $value ) ) {
                            $sanitized[ $section_key ][ $field_key ] = array_map( 'absint', array_filter( $value ) );
                        } else {
                            $sanitized[ $section_key ][ $field_key ] = [];
                        }
                    } else {
                        $sanitized[ $section_key ][ $field_key ] = $input[ $section_key ][ $field_key ];
                    }
                } else {
                    $sanitized[ $section_key ][ $field_key ] = $default_value;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Renderiza a página de configurações
     */
    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não possui permissão para acessar esta página.', 'vemcomer' ) );
        }

        // Garantir que jQuery está carregado
        wp_enqueue_script( 'jquery' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Configure quais seções aparecem na página inicial e seus parâmetros.', 'vemcomer' ); ?>
            </p>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'vemcomer_home_settings_group' );
                do_settings_sections( 'vemcomer_home_settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza campo de texto
     */
    public function render_text_field( $args ): void {
        $options   = get_option( self::OPTION_NAME, $this->get_default_options() );
        $option_key = $args['option_key'];
        $field_key  = $args['field_key'];
        $default    = $args['default'] ?? '';
        $value      = $options[ $option_key ][ $field_key ] ?? $default;
        $name       = self::OPTION_NAME . '[' . $option_key . '][' . $field_key . ']';

        printf(
            '<input type="text" name="%s" value="%s" class="regular-text" />',
            esc_attr( $name ),
            esc_attr( $value )
        );
    }

    /**
     * Renderiza campo numérico
     */
    public function render_number_field( $args ): void {
        $options    = get_option( self::OPTION_NAME, $this->get_default_options() );
        $option_key = $args['option_key'];
        $field_key  = $args['field_key'];
        $default    = $args['default'] ?? 0;
        $min        = $args['min'] ?? 0;
        $max        = $args['max'] ?? 999;
        $value      = $options[ $option_key ][ $field_key ] ?? $default;
        $name       = self::OPTION_NAME . '[' . $option_key . '][' . $field_key . ']';

        printf(
            '<input type="number" name="%s" value="%d" min="%d" max="%d" class="small-text" />',
            esc_attr( $name ),
            esc_attr( $value ),
            esc_attr( $min ),
            esc_attr( $max )
        );
    }

    /**
     * Renderiza checkbox
     */
    public function render_checkbox_field( $args ): void {
        $options    = get_option( self::OPTION_NAME, $this->get_default_options() );
        $option_key = $args['option_key'];
        $field_key  = $args['field_key'];
        $label      = $args['label'] ?? '';
        $value      = $options[ $option_key ][ $field_key ] ?? false;
        $name       = self::OPTION_NAME . '[' . $option_key . '][' . $field_key . ']';

        printf(
            '<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
            esc_attr( $name ),
            checked( $value, true, false ),
            esc_html( $label )
        );
    }

    /**
     * Renderiza campo select
     */
    public function render_select_field( $args ): void {
        $options    = get_option( self::OPTION_NAME, $this->get_default_options() );
        $option_key = $args['option_key'];
        $field_key  = $args['field_key'];
        $default    = $args['default'] ?? '';
        $select_options = $args['options'] ?? [];
        $value      = $options[ $option_key ][ $field_key ] ?? $default;
        $name       = self::OPTION_NAME . '[' . $option_key . '][' . $field_key . ']';

        echo '<select name="' . esc_attr( $name ) . '">';
        foreach ( $select_options as $opt_value => $opt_label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $opt_value ),
                selected( $value, $opt_value, false ),
                esc_html( $opt_label )
            );
        }
        echo '</select>';
    }

    /**
     * Descrições das seções
     */
    public function render_hero_section_description(): void {
        echo '<p>' . esc_html__( 'Configure o banner principal da home com título, subtítulo e busca.', 'vemcomer' ) . '</p>';
    }

    public function render_banners_section_description(): void {
        echo '<p>' . esc_html__( 'Exibe banners promocionais dos restaurantes cadastrados.', 'vemcomer' ) . '</p>';
    }

    public function render_categories_section_description(): void {
        echo '<p>' . esc_html__( 'Mostra as categorias de comida mais populares.', 'vemcomer' ) . '</p>';
    }

    public function render_featured_section_description(): void {
        echo '<p>' . esc_html__( 'Exibe restaurantes marcados como destaque.', 'vemcomer' ) . '</p>';
    }

    public function render_restaurants_section_description(): void {
        echo '<p>' . esc_html__( 'Configura a listagem principal de restaurantes na home.', 'vemcomer' ) . '</p>';
    }

    public function render_map_section_description(): void {
        echo '<p>' . esc_html__( 'Exibe um mapa interativo com a localização dos restaurantes.', 'vemcomer' ) . '</p>';
    }

    public function render_for_you_section_description(): void {
        echo '<p>' . esc_html__( 'Seção personalizada para usuários logados (favoritos e histórico de pedidos).', 'vemcomer' ) . '</p>';
    }

    public function render_cta_section_description(): void {
        echo '<p>' . esc_html__( 'Chamada para ação incentivando donos de restaurantes a se cadastrarem.', 'vemcomer' ) . '</p>';
    }

    public function render_daily_highlights_section_description(): void {
        echo '<p>' . esc_html__( 'Selecione produtos/cardápios de qualquer restaurante para destacar na home. Você pode escolher quantos quiser e eles aparecerão em cards especiais.', 'vemcomer' ) . '</p>';
    }

    /**
     * Renderiza seletor de menu items (produtos/cardápios)
     */
    public function render_menu_items_selector( $args ): void {
        $options    = get_option( self::OPTION_NAME, $this->get_default_options() );
        $option_key = $args['option_key'];
        $field_key  = $args['field_key'];
        $selected_ids = $options[ $option_key ][ $field_key ] ?? [];
        if ( ! is_array( $selected_ids ) ) {
            $selected_ids = ! empty( $selected_ids ) ? explode( ',', $selected_ids ) : [];
        }
        $selected_ids = array_map( 'absint', $selected_ids );
        $selected_ids = array_filter( $selected_ids );

        // Buscar todos os menu items com seus restaurantes
        $all_items = get_posts( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        // Buscar items selecionados
        $selected_items = [];
        if ( ! empty( $selected_ids ) ) {
            $selected_items = get_posts( [
                'post_type'      => 'vc_menu_item',
                'post__in'       => $selected_ids,
                'posts_per_page' => -1,
                'orderby'        => 'post__in',
            ] );
        }

        $name = self::OPTION_NAME . '[' . $option_key . '][' . $field_key . ']';
        ?>
        <div id="vc-daily-highlights-selector" style="max-width: 800px;">
            <!-- Campo oculto para armazenar IDs -->
            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="vc-selected-menu-items" value="<?php echo esc_attr( implode( ',', $selected_ids ) ); ?>" />
            
            <!-- Busca -->
            <div style="margin-bottom: 15px;">
                <input 
                    type="text" 
                    id="vc-menu-item-search" 
                    placeholder="<?php esc_attr_e( 'Buscar produto/cardápio...', 'vemcomer' ); ?>" 
                    class="regular-text"
                    style="width: 100%; padding: 8px;"
                />
            </div>

            <!-- Lista de selecionados -->
            <div id="vc-selected-items-list" style="margin-bottom: 20px; min-height: 50px; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                <strong><?php esc_html_e( 'Produtos Selecionados:', 'vemcomer' ); ?></strong>
                <div id="vc-selected-items-container" style="margin-top: 10px;">
                    <?php if ( ! empty( $selected_items ) ) : ?>
                        <?php foreach ( $selected_items as $item ) : ?>
                            <?php
                            $restaurant_id = (int) get_post_meta( $item->ID, '_vc_restaurant_id', true );
                            $restaurant = $restaurant_id > 0 ? get_post( $restaurant_id ) : null;
                            $price = get_post_meta( $item->ID, '_vc_price', true );
                            ?>
                            <div class="vc-selected-item" data-id="<?php echo esc_attr( $item->ID ); ?>" style="display: inline-block; margin: 5px; padding: 8px 12px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
                                <strong><?php echo esc_html( $item->post_title ); ?></strong>
                                <?php if ( $restaurant ) : ?>
                                    <span style="color: #666; font-size: 0.9em;"> - <?php echo esc_html( $restaurant->post_title ); ?></span>
                                <?php endif; ?>
                                <?php if ( $price ) : ?>
                                    <span style="color: #2f9e44; font-weight: bold;"> - R$ <?php echo esc_html( $price ); ?></span>
                                <?php endif; ?>
                                <button type="button" class="vc-remove-item" data-id="<?php echo esc_attr( $item->ID ); ?>" style="margin-left: 8px; color: #dc3232; cursor: pointer; border: none; background: none;">✕</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p style="color: #999; font-style: italic;"><?php esc_html_e( 'Nenhum produto selecionado. Use a busca abaixo para adicionar.', 'vemcomer' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lista de resultados da busca -->
            <div id="vc-search-results" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; border-radius: 4px; display: none;">
                <div id="vc-search-results-list"></div>
            </div>
        </div>

        <script>
        (function($) {
            var selectedIds = <?php echo wp_json_encode( $selected_ids ); ?>;
            var allItems = <?php echo wp_json_encode( array_map( function( $item ) {
                $restaurant_id = (int) get_post_meta( $item->ID, '_vc_restaurant_id', true );
                $restaurant = $restaurant_id > 0 ? get_post( $restaurant_id ) : null;
                $price = get_post_meta( $item->ID, '_vc_price', true );
                return [
                    'id' => $item->ID,
                    'title' => $item->post_title,
                    'restaurant' => $restaurant ? $restaurant->post_title : '',
                    'price' => $price ? $price : '',
                ];
            }, $all_items ) ); ?>;

            function updateHiddenField() {
                $('#vc-selected-menu-items').val( selectedIds.join(',') );
            }

            function renderSelectedItems() {
                var container = $('#vc-selected-items-container');
                if (selectedIds.length === 0) {
                    container.html('<p style="color: #999; font-style: italic;"><?php esc_html_e( 'Nenhum produto selecionado. Use a busca abaixo para adicionar.', 'vemcomer' ); ?></p>');
                    return;
                }

                var html = '';
                allItems.forEach(function(item) {
                    if (selectedIds.indexOf(item.id) !== -1) {
                        html += '<div class="vc-selected-item" data-id="' + item.id + '" style="display: inline-block; margin: 5px; padding: 8px 12px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">';
                        html += '<strong>' + item.title + '</strong>';
                        if (item.restaurant) {
                            html += '<span style="color: #666; font-size: 0.9em;"> - ' + item.restaurant + '</span>';
                        }
                        if (item.price) {
                            html += '<span style="color: #2f9e44; font-weight: bold;"> - R$ ' + item.price + '</span>';
                        }
                        html += '<button type="button" class="vc-remove-item" data-id="' + item.id + '" style="margin-left: 8px; color: #dc3232; cursor: pointer; border: none; background: none;">✕</button>';
                        html += '</div>';
                    }
                });
                container.html(html);
            }

            // Busca
            $('#vc-menu-item-search').on('input', function() {
                var search = $(this).val().toLowerCase();
                if (search.length < 2) {
                    $('#vc-search-results').hide();
                    return;
                }

                var results = allItems.filter(function(item) {
                    return selectedIds.indexOf(item.id) === -1 && 
                           (item.title.toLowerCase().indexOf(search) !== -1 || 
                            item.restaurant.toLowerCase().indexOf(search) !== -1);
                });

                if (results.length === 0) {
                    $('#vc-search-results-list').html('<p style="color: #999; padding: 10px;"><?php esc_html_e( 'Nenhum resultado encontrado.', 'vemcomer' ); ?></p>');
                } else {
                    var html = '';
                    results.slice(0, 20).forEach(function(item) {
                        html += '<div class="vc-search-item" data-id="' + item.id + '" style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;">';
                        html += '<strong>' + item.title + '</strong>';
                        if (item.restaurant) {
                            html += '<span style="color: #666; font-size: 0.9em; margin-left: 10px;">(' + item.restaurant + ')</span>';
                        }
                        if (item.price) {
                            html += '<span style="color: #2f9e44; font-weight: bold; float: right;">R$ ' + item.price + '</span>';
                        }
                        html += '</div>';
                    });
                    $('#vc-search-results-list').html(html);
                }
                $('#vc-search-results').show();
            });

            // Adicionar item
            $(document).on('click', '.vc-search-item', function() {
                var id = parseInt($(this).data('id'));
                if (selectedIds.indexOf(id) === -1) {
                    selectedIds.push(id);
                    updateHiddenField();
                    renderSelectedItems();
                    $('#vc-menu-item-search').val('').trigger('input');
                }
            });

            // Remover item
            $(document).on('click', '.vc-remove-item', function() {
                var id = parseInt($(this).data('id'));
                selectedIds = selectedIds.filter(function(itemId) {
                    return itemId !== id;
                });
                updateHiddenField();
                renderSelectedItems();
            });

            // Hover nos resultados
            $(document).on('mouseenter', '.vc-search-item', function() {
                $(this).css('background', '#f0f0f0');
            }).on('mouseleave', '.vc-search-item', function() {
                $(this).css('background', '');
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Renderiza seletor de restaurantes
     */
    public function render_restaurants_selector( $args ): void {
        $options    = get_option( self::OPTION_NAME, $this->get_default_options() );
        $option_key = $args['option_key'];
        $field_key  = $args['field_key'];
        $selected_ids = $options[ $option_key ][ $field_key ] ?? [];
        if ( ! is_array( $selected_ids ) ) {
            $selected_ids = ! empty( $selected_ids ) ? explode( ',', $selected_ids ) : [];
        }
        $selected_ids = array_map( 'absint', $selected_ids );
        $selected_ids = array_filter( $selected_ids );

        // Buscar todos os restaurantes
        $all_restaurants = get_posts( [
            'post_type'      => 'vc_restaurant',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        // Buscar restaurantes selecionados
        $selected_restaurants = [];
        if ( ! empty( $selected_ids ) ) {
            $selected_restaurants = get_posts( [
                'post_type'      => 'vc_restaurant',
                'post__in'       => $selected_ids,
                'posts_per_page' => -1,
                'orderby'        => 'post__in',
            ] );
        }

        $name = self::OPTION_NAME . '[' . $option_key . '][' . $field_key . ']';
        ?>
        <div id="vc-featured-restaurants-selector" style="max-width: 800px;">
            <!-- Campo oculto para armazenar IDs -->
            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="vc-selected-restaurants" value="<?php echo esc_attr( implode( ',', $selected_ids ) ); ?>" />
            
            <!-- Busca -->
            <div style="margin-bottom: 15px;">
                <input 
                    type="text" 
                    id="vc-restaurant-search" 
                    placeholder="<?php esc_attr_e( 'Buscar restaurante...', 'vemcomer' ); ?>" 
                    class="regular-text"
                    style="width: 100%; padding: 8px;"
                />
            </div>

            <!-- Lista de selecionados -->
            <div id="vc-selected-restaurants-list" style="margin-bottom: 20px; min-height: 50px; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                <strong><?php esc_html_e( 'Restaurantes Selecionados:', 'vemcomer' ); ?></strong>
                <div id="vc-selected-restaurants-container" style="margin-top: 10px;">
                    <?php if ( ! empty( $selected_restaurants ) ) : ?>
                        <?php foreach ( $selected_restaurants as $restaurant ) : ?>
                            <?php
                            $rating = get_post_meta( $restaurant->ID, '_vc_restaurant_rating_avg', true );
                            $cuisine_terms = get_the_terms( $restaurant->ID, 'vc_cuisine' );
                            $cuisine = $cuisine_terms && ! is_wp_error( $cuisine_terms ) ? $cuisine_terms[0]->name : '';
                            ?>
                            <div class="vc-selected-restaurant" data-id="<?php echo esc_attr( $restaurant->ID ); ?>" style="display: inline-block; margin: 5px; padding: 8px 12px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
                                <strong><?php echo esc_html( $restaurant->post_title ); ?></strong>
                                <?php if ( $cuisine ) : ?>
                                    <span style="color: #666; font-size: 0.9em;"> - <?php echo esc_html( $cuisine ); ?></span>
                                <?php endif; ?>
                                <?php if ( $rating ) : ?>
                                    <span style="color: #f59e0b; font-weight: bold;"> - ⭐ <?php echo esc_html( number_format( (float) $rating, 1 ) ); ?></span>
                                <?php endif; ?>
                                <button type="button" class="vc-remove-restaurant" data-id="<?php echo esc_attr( $restaurant->ID ); ?>" style="margin-left: 8px; color: #dc3232; cursor: pointer; border: none; background: none;">✕</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p style="color: #999; font-style: italic;"><?php esc_html_e( 'Nenhum restaurante selecionado. Use a busca abaixo para adicionar.', 'vemcomer' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lista de resultados da busca -->
            <div id="vc-restaurant-search-results" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; border-radius: 4px; display: none;">
                <div id="vc-restaurant-search-results-list"></div>
            </div>
        </div>

        <script>
        (function($) {
            var selectedIds = <?php echo wp_json_encode( $selected_ids ); ?>;
            var allRestaurants = <?php echo wp_json_encode( array_map( function( $restaurant ) {
                $rating = get_post_meta( $restaurant->ID, '_vc_restaurant_rating_avg', true );
                $cuisine_terms = get_the_terms( $restaurant->ID, 'vc_cuisine' );
                $cuisine = $cuisine_terms && ! is_wp_error( $cuisine_terms ) ? $cuisine_terms[0]->name : '';
                return [
                    'id' => $restaurant->ID,
                    'title' => $restaurant->post_title,
                    'cuisine' => $cuisine ? $cuisine : '',
                    'rating' => $rating ? number_format( (float) $rating, 1 ) : '',
                ];
            }, $all_restaurants ) ); ?>;

            function updateRestaurantsHiddenField() {
                $('#vc-selected-restaurants').val( selectedIds.join(',') );
            }

            function renderSelectedRestaurants() {
                var container = $('#vc-selected-restaurants-container');
                if (selectedIds.length === 0) {
                    container.html('<p style="color: #999; font-style: italic;"><?php esc_html_e( 'Nenhum restaurante selecionado. Use a busca abaixo para adicionar.', 'vemcomer' ); ?></p>');
                    return;
                }

                var html = '';
                allRestaurants.forEach(function(restaurant) {
                    if (selectedIds.indexOf(restaurant.id) !== -1) {
                        html += '<div class="vc-selected-restaurant" data-id="' + restaurant.id + '" style="display: inline-block; margin: 5px; padding: 8px 12px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">';
                        html += '<strong>' + restaurant.title + '</strong>';
                        if (restaurant.cuisine) {
                            html += '<span style="color: #666; font-size: 0.9em;"> - ' + restaurant.cuisine + '</span>';
                        }
                        if (restaurant.rating) {
                            html += '<span style="color: #f59e0b; font-weight: bold;"> - ⭐ ' + restaurant.rating + '</span>';
                        }
                        html += '<button type="button" class="vc-remove-restaurant" data-id="' + restaurant.id + '" style="margin-left: 8px; color: #dc3232; cursor: pointer; border: none; background: none;">✕</button>';
                        html += '</div>';
                    }
                });
                container.html(html);
            }

            // Busca
            $('#vc-restaurant-search').on('input', function() {
                var search = $(this).val().toLowerCase();
                if (search.length < 2) {
                    $('#vc-restaurant-search-results').hide();
                    return;
                }

                var results = allRestaurants.filter(function(restaurant) {
                    return selectedIds.indexOf(restaurant.id) === -1 && 
                           (restaurant.title.toLowerCase().indexOf(search) !== -1 || 
                            restaurant.cuisine.toLowerCase().indexOf(search) !== -1);
                });

                if (results.length === 0) {
                    $('#vc-restaurant-search-results-list').html('<p style="color: #999; padding: 10px;"><?php esc_html_e( 'Nenhum resultado encontrado.', 'vemcomer' ); ?></p>');
                } else {
                    var html = '';
                    results.slice(0, 20).forEach(function(restaurant) {
                        html += '<div class="vc-search-restaurant" data-id="' + restaurant.id + '" style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;">';
                        html += '<strong>' + restaurant.title + '</strong>';
                        if (restaurant.cuisine) {
                            html += '<span style="color: #666; font-size: 0.9em; margin-left: 10px;">(' + restaurant.cuisine + ')</span>';
                        }
                        if (restaurant.rating) {
                            html += '<span style="color: #f59e0b; font-weight: bold; float: right;">⭐ ' + restaurant.rating + '</span>';
                        }
                        html += '</div>';
                    });
                    $('#vc-restaurant-search-results-list').html(html);
                }
                $('#vc-restaurant-search-results').show();
            });

            // Adicionar restaurante
            $(document).on('click', '.vc-search-restaurant', function() {
                var id = parseInt($(this).data('id'));
                if (selectedIds.indexOf(id) === -1) {
                    selectedIds.push(id);
                    updateRestaurantsHiddenField();
                    renderSelectedRestaurants();
                    $('#vc-restaurant-search').val('').trigger('input');
                }
            });

            // Remover restaurante
            $(document).on('click', '.vc-remove-restaurant', function() {
                var id = parseInt($(this).data('id'));
                selectedIds = selectedIds.filter(function(restId) {
                    return restId !== id;
                });
                updateRestaurantsHiddenField();
                renderSelectedRestaurants();
            });

            // Hover nos resultados
            $(document).on('mouseenter', '.vc-search-restaurant', function() {
                $(this).css('background', '#f0f0f0');
            }).on('mouseleave', '.vc-search-restaurant', function() {
                $(this).css('background', '');
            });
        })(jQuery);
        </script>
        <?php
    }
}

