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
                'ativo' => true,
            ],
            'restaurants_section' => [
                'ativo'      => true,
                'titulo'     => __( 'Restaurantes', 'vemcomer' ),
                'quantidade' => 12,
                'ordenar_por' => 'date',
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
}

