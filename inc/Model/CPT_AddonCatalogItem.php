<?php
/**
 * CPT_AddonCatalogItem — Custom Post Type "Addon Catalog Item" (Itens de Adicionais do Catálogo)
 * Itens individuais que pertencem a um grupo de adicionais do catálogo
 * 
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_AddonCatalogItem {
    public const SLUG = 'vc_addon_catalog_item';

    public function init(): void {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
        add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
        add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
    }

    public function register_cpt(): void {
        $labels = [
            'name'                  => __( 'Itens de Adicionais (Catálogo)', 'vemcomer' ),
            'singular_name'         => __( 'Item de Adicional', 'vemcomer' ),
            'menu_name'             => __( 'Itens do Catálogo', 'vemcomer' ),
            'name_admin_bar'        => __( 'Item de Adicional', 'vemcomer' ),
            'add_new'               => __( 'Adicionar novo', 'vemcomer' ),
            'add_new_item'          => __( 'Adicionar novo item', 'vemcomer' ),
            'new_item'              => __( 'Novo item', 'vemcomer' ),
            'edit_item'             => __( 'Editar item', 'vemcomer' ),
            'view_item'             => __( 'Ver item', 'vemcomer' ),
            'all_items'             => __( 'Todos os itens', 'vemcomer' ),
            'search_items'          => __( 'Buscar itens', 'vemcomer' ),
            'not_found'             => __( 'Nenhum item encontrado.', 'vemcomer' ),
            'not_found_in_trash'    => __( 'Nenhum item na lixeira.', 'vemcomer' ),
        ];

        $args = [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_position'   => 26,
            'menu_icon'       => 'dashicons-admin-generic',
            'show_in_rest'    => true,
            'supports'        => [ 'title', 'editor' ],
            'capability_type' => 'post',
            'capabilities'    => [
                'edit_post'   => 'edit_vc_addon_catalog_item',
                'read_post'   => 'read_vc_addon_catalog_item',
                'delete_post' => 'delete_vc_addon_catalog_item',
                'edit_posts'  => 'edit_vc_addon_catalog_items',
                'edit_others_posts' => 'edit_others_vc_addon_catalog_items',
                'publish_posts' => 'publish_vc_addon_catalog_items',
                'read_private_posts' => 'read_private_vc_addon_catalog_items',
            ],
        ];
        register_post_type( self::SLUG, $args );
    }

    public function register_metaboxes(): void {
        add_meta_box(
            'vc_addon_catalog_item_meta',
            __( 'Configurações do Item', 'vemcomer' ),
            [ $this, 'metabox' ],
            self::SLUG,
            'normal',
            'high'
        );
    }

    public function metabox( $post ): void {
        wp_nonce_field( 'vc_addon_catalog_item_meta', 'vc_addon_catalog_item_nonce' );

        $group_id = get_post_meta( $post->ID, '_vc_group_id', true );
        $default_price = get_post_meta( $post->ID, '_vc_default_price', true ) ?: '0.00';
        $allow_quantity = get_post_meta( $post->ID, '_vc_allow_quantity', true ) === '1';
        $max_quantity = get_post_meta( $post->ID, '_vc_max_quantity', true ) ?: '1';
        $is_active = get_post_meta( $post->ID, '_vc_is_active', true ) !== '0';

        // Buscar grupos disponíveis
        $groups = get_posts( [
            'post_type'      => CPT_AddonCatalogGroup::SLUG,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        ?>
        <table class="form-table">
            <tr>
                <th><label for="vc_group_id"><?php _e( 'Grupo de Adicionais *', 'vemcomer' ); ?></label></th>
                <td>
                    <select id="vc_group_id" name="vc_group_id" required>
                        <option value=""><?php _e( 'Selecione um grupo', 'vemcomer' ); ?></option>
                        <?php foreach ( $groups as $group ) : ?>
                            <option value="<?php echo esc_attr( $group->ID ); ?>" <?php selected( $group_id, $group->ID ); ?>>
                                <?php echo esc_html( $group->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e( 'Grupo ao qual este item pertence.', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_default_price"><?php _e( 'Preço Padrão (R$)', 'vemcomer' ); ?></label></th>
                <td>
                    <input type="number" id="vc_default_price" name="vc_default_price" value="<?php echo esc_attr( $default_price ); ?>" step="0.01" min="0" />
                    <p class="description"><?php _e( 'Preço padrão que será sugerido quando lojistas copiarem este item para sua loja. Eles podem alterar depois.', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_allow_quantity"><?php _e( 'Permitir Quantidade', 'vemcomer' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="vc_allow_quantity" name="vc_allow_quantity" value="1" <?php checked( $allow_quantity ); ?> />
                        <?php _e( 'Cliente pode escolher a quantidade deste item (ex: 2x queijo extra)', 'vemcomer' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="vc_max_quantity"><?php _e( 'Quantidade Máxima', 'vemcomer' ); ?></label></th>
                <td>
                    <input type="number" id="vc_max_quantity" name="vc_max_quantity" value="<?php echo esc_attr( $max_quantity ); ?>" min="1" />
                    <p class="description"><?php _e( 'Quantidade máxima que o cliente pode selecionar (só aplica se "Permitir Quantidade" estiver marcado).', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_is_active"><?php _e( 'Ativo', 'vemcomer' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="vc_is_active" name="vc_is_active" value="1" <?php checked( $is_active ); ?> />
                        <?php _e( 'Este item está ativo e disponível para lojistas', 'vemcomer' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['vc_addon_catalog_item_nonce'] ) || 
             ! wp_verify_nonce( $_POST['vc_addon_catalog_item_nonce'], 'vc_addon_catalog_item_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Salvar campos
        if ( isset( $_POST['vc_group_id'] ) ) {
            update_post_meta( $post_id, '_vc_group_id', absint( $_POST['vc_group_id'] ) );
        }

        if ( isset( $_POST['vc_default_price'] ) ) {
            update_post_meta( $post_id, '_vc_default_price', sanitize_text_field( $_POST['vc_default_price'] ) );
        }

        update_post_meta( $post_id, '_vc_allow_quantity', isset( $_POST['vc_allow_quantity'] ) ? '1' : '0' );

        if ( isset( $_POST['vc_max_quantity'] ) ) {
            update_post_meta( $post_id, '_vc_max_quantity', absint( $_POST['vc_max_quantity'] ) );
        }

        update_post_meta( $post_id, '_vc_is_active', isset( $_POST['vc_is_active'] ) ? '1' : '0' );
    }

    public function admin_columns( array $columns ): array {
        $columns['group'] = __( 'Grupo', 'vemcomer' );
        $columns['price'] = __( 'Preço Padrão', 'vemcomer' );
        $columns['active'] = __( 'Ativo', 'vemcomer' );
        return $columns;
    }

    public function admin_column_values( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'group':
                $group_id = get_post_meta( $post_id, '_vc_group_id', true );
                if ( $group_id ) {
                    $group = get_post( $group_id );
                    if ( $group ) {
                        echo esc_html( $group->post_title );
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;
            case 'price':
                $price = get_post_meta( $post_id, '_vc_default_price', true ) ?: '0.00';
                echo 'R$ ' . number_format( (float) $price, 2, ',', '.' );
                break;
            case 'active':
                $active = get_post_meta( $post_id, '_vc_is_active', true ) !== '0';
                echo $active ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>';
                break;
        }
    }
}

