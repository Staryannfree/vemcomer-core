<?php
/**
 * CPT_AddonCatalogGroup — Custom Post Type "Addon Catalog Group" (Grupos de Adicionais do Catálogo)
 * Grupos de adicionais que podem ser vinculados às categorias de restaurantes (vc_cuisine)
 * 
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_AddonCatalogGroup {
    public const SLUG = 'vc_addon_group';

    public function init(): void {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'init', [ $this, 'register_taxonomy' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
        add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
        add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
    }

    public function register_cpt(): void {
        $labels = [
            'name'                  => __( 'Grupos de Adicionais (Catálogo)', 'vemcomer' ),
            'singular_name'         => __( 'Grupo de Adicionais', 'vemcomer' ),
            'menu_name'             => __( 'Catálogo de Adicionais', 'vemcomer' ),
            'name_admin_bar'        => __( 'Grupo de Adicionais', 'vemcomer' ),
            'add_new'               => __( 'Adicionar novo', 'vemcomer' ),
            'add_new_item'          => __( 'Adicionar novo grupo', 'vemcomer' ),
            'new_item'              => __( 'Novo grupo', 'vemcomer' ),
            'edit_item'             => __( 'Editar grupo', 'vemcomer' ),
            'view_item'             => __( 'Ver grupo', 'vemcomer' ),
            'all_items'             => __( 'Todos os grupos', 'vemcomer' ),
            'search_items'          => __( 'Buscar grupos', 'vemcomer' ),
            'not_found'             => __( 'Nenhum grupo encontrado.', 'vemcomer' ),
            'not_found_in_trash'    => __( 'Nenhum grupo na lixeira.', 'vemcomer' ),
        ];

        $args = [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_position'   => 25,
            'menu_icon'       => 'dashicons-list-view',
            'show_in_rest'    => true,
            'supports'        => [ 'title', 'editor' ],
            'capability_type' => 'post',
            'capabilities'    => [
                'edit_post'   => 'edit_vc_addon_catalog_group',
                'read_post'   => 'read_vc_addon_catalog_group',
                'delete_post' => 'delete_vc_addon_catalog_group',
                'edit_posts'  => 'edit_vc_addon_catalog_groups',
                'edit_others_posts' => 'edit_others_vc_addon_catalog_groups',
                'publish_posts' => 'publish_vc_addon_catalog_groups',
                'read_private_posts' => 'read_private_vc_addon_catalog_groups',
            ],
        ];
        register_post_type( self::SLUG, $args );
    }

    /**
     * Registra taxonomia para vincular grupos às categorias de restaurantes
     * Usa a mesma taxonomia vc_cuisine que já existe
     */
    public function register_taxonomy(): void {
        // Vincula grupos às categorias de restaurantes (vc_cuisine)
        register_taxonomy_for_object_type( 'vc_cuisine', self::SLUG );
    }

    public function register_metaboxes(): void {
        add_meta_box(
            'vc_addon_catalog_group_meta',
            __( 'Configurações do Grupo', 'vemcomer' ),
            [ $this, 'metabox' ],
            self::SLUG,
            'normal',
            'high'
        );
    }

    public function metabox( $post ): void {
        wp_nonce_field( 'vc_addon_catalog_group_meta', 'vc_addon_catalog_group_nonce' );

        $selection_type = get_post_meta( $post->ID, '_vc_selection_type', true ) ?: 'multiple';
        $min_select = get_post_meta( $post->ID, '_vc_min_select', true ) ?: '0';
        $max_select = get_post_meta( $post->ID, '_vc_max_select', true ) ?: '0';
        $is_required = get_post_meta( $post->ID, '_vc_is_required', true ) === '1';
        $is_active = get_post_meta( $post->ID, '_vc_is_active', true ) !== '0';

        ?>
        <table class="form-table">
            <tr>
                <th><label for="vc_selection_type"><?php _e( 'Tipo de Seleção', 'vemcomer' ); ?></label></th>
                <td>
                    <select id="vc_selection_type" name="vc_selection_type">
                        <option value="single" <?php selected( $selection_type, 'single' ); ?>><?php _e( 'Seleção única', 'vemcomer' ); ?></option>
                        <option value="multiple" <?php selected( $selection_type, 'multiple' ); ?>><?php _e( 'Múltipla seleção', 'vemcomer' ); ?></option>
                    </select>
                    <p class="description"><?php _e( 'Seleção única: cliente escolhe apenas 1 opção. Múltipla: pode escolher várias.', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_min_select"><?php _e( 'Seleção Mínima', 'vemcomer' ); ?></label></th>
                <td>
                    <input type="number" id="vc_min_select" name="vc_min_select" value="<?php echo esc_attr( $min_select ); ?>" min="0" />
                    <p class="description"><?php _e( 'Número mínimo de itens que o cliente deve selecionar (0 = opcional).', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_max_select"><?php _e( 'Seleção Máxima', 'vemcomer' ); ?></label></th>
                <td>
                    <input type="number" id="vc_max_select" name="vc_max_select" value="<?php echo esc_attr( $max_select ); ?>" min="0" />
                    <p class="description"><?php _e( 'Número máximo de itens que o cliente pode selecionar (0 = ilimitado).', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_is_required"><?php _e( 'Obrigatório', 'vemcomer' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="vc_is_required" name="vc_is_required" value="1" <?php checked( $is_required ); ?> />
                        <?php _e( 'Este grupo é obrigatório (cliente deve selecionar pelo menos uma opção)', 'vemcomer' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="vc_is_active"><?php _e( 'Ativo', 'vemcomer' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="vc_is_active" name="vc_is_active" value="1" <?php checked( $is_active ); ?> />
                        <?php _e( 'Este grupo está ativo e disponível para lojistas', 'vemcomer' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="vc_difficulty_level"><?php _e( 'Nível de Dificuldade', 'vemcomer' ); ?></label></th>
                <td>
                    <select id="vc_difficulty_level" name="vc_difficulty_level">
                        <option value="basic" <?php selected( get_post_meta( $post->ID, '_vc_difficulty_level', true ), 'basic' ); ?>>
                            <?php _e( '⭐ Básico', 'vemcomer' ); ?>
                        </option>
                        <option value="advanced" <?php selected( get_post_meta( $post->ID, '_vc_difficulty_level', true ), 'advanced' ); ?>>
                            <?php _e( '⚙️ Avançado', 'vemcomer' ); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e( 'Básico: grupos simples e comuns. Avançado: grupos com configurações mais complexas.', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Categorias de Restaurantes', 'vemcomer' ); ?></label></th>
                <td>
                    <p class="description"><?php _e( 'Selecione as categorias de restaurantes para as quais este grupo de adicionais é recomendado. Lojistas com essas categorias verão este grupo como sugestão ao criar produtos.', 'vemcomer' ); ?></p>
                    <?php
                    // Mostrar as categorias já selecionadas
                    $selected_categories = wp_get_object_terms( $post->ID, 'vc_cuisine', [ 'fields' => 'ids' ] );
                    if ( ! empty( $selected_categories ) ) {
                        $terms = get_terms( [
                            'taxonomy' => 'vc_cuisine',
                            'include'  => $selected_categories,
                            'hide_empty' => false,
                        ] );
                        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                            echo '<ul>';
                            foreach ( $terms as $term ) {
                                echo '<li>' . esc_html( $term->name ) . '</li>';
                            }
                            echo '</ul>';
                            echo '<p class="description">' . __( 'Edite as categorias na seção "Categorias" ao lado.', 'vemcomer' ) . '</p>';
                        }
                    } else {
                        echo '<p class="description">' . __( 'Nenhuma categoria selecionada. Selecione na seção "Categorias" ao lado.', 'vemcomer' ) . '</p>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['vc_addon_catalog_group_nonce'] ) || 
             ! wp_verify_nonce( $_POST['vc_addon_catalog_group_nonce'], 'vc_addon_catalog_group_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Salvar campos
        if ( isset( $_POST['vc_selection_type'] ) ) {
            update_post_meta( $post_id, '_vc_selection_type', sanitize_text_field( $_POST['vc_selection_type'] ) );
        }

        if ( isset( $_POST['vc_min_select'] ) ) {
            update_post_meta( $post_id, '_vc_min_select', absint( $_POST['vc_min_select'] ) );
        }

        if ( isset( $_POST['vc_max_select'] ) ) {
            update_post_meta( $post_id, '_vc_max_select', absint( $_POST['vc_max_select'] ) );
        }

        update_post_meta( $post_id, '_vc_is_required', isset( $_POST['vc_is_required'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_vc_is_active', isset( $_POST['vc_is_active'] ) ? '1' : '0' );

        if ( isset( $_POST['vc_difficulty_level'] ) ) {
            $level = sanitize_text_field( $_POST['vc_difficulty_level'] );
            if ( in_array( $level, [ 'basic', 'advanced' ], true ) ) {
                update_post_meta( $post_id, '_vc_difficulty_level', $level );
            }
        }
    }

    public function admin_columns( array $columns ): array {
        $columns['selection_type'] = __( 'Tipo', 'vemcomer' );
        $columns['difficulty'] = __( 'Nível', 'vemcomer' );
        $columns['categories'] = __( 'Categorias', 'vemcomer' );
        $columns['active'] = __( 'Ativo', 'vemcomer' );
        return $columns;
    }

    public function admin_column_values( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'selection_type':
                $type = get_post_meta( $post_id, '_vc_selection_type', true ) ?: 'multiple';
                echo $type === 'single' ? __( 'Única', 'vemcomer' ) : __( 'Múltipla', 'vemcomer' );
                break;
            case 'difficulty':
                $level = get_post_meta( $post_id, '_vc_difficulty_level', true ) ?: 'basic';
                echo $level === 'basic' ? '⭐ ' . __( 'Básico', 'vemcomer' ) : '⚙️ ' . __( 'Avançado', 'vemcomer' );
                break;
            case 'categories':
                $terms = wp_get_object_terms( $post_id, 'vc_cuisine', [ 'fields' => 'names' ] );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    echo esc_html( implode( ', ', $terms ) );
                } else {
                    echo '—';
                }
                break;
            case 'active':
                $active = get_post_meta( $post_id, '_vc_is_active', true ) !== '0';
                echo $active ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>';
                break;
        }
    }
}

