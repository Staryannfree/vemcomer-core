<?php
/**
 * CPT_MenuItem — Custom Post Type "Menu Item" (Itens do Cardápio)
 * + Capabilities customizadas e concessão por role (grant_caps).
 *
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_MenuItem {
    public const SLUG = 'vc_menu_item';
    public const TAX_CATEGORY = 'vc_menu_category';

    public function init(): void {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
        add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
        add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
        add_action( 'init', [ $this, 'grant_caps' ], 5 );
        // Validação de limite de itens
        add_filter( 'wp_insert_post_data', [ $this, 'check_limit_on_save' ], 10, 2 );
    }

    /**
     * Impede a publicação de novos itens se o limite do plano for atingido.
     */
    public function check_limit_on_save( $data, $postarr ) {
        // Apenas para este CPT
        if ( $data['post_type'] !== self::SLUG ) {
            return $data;
        }

        // Se não for publicação, permite (rascunhos são livres, ou não?)
        // Vamos bloquear apenas status 'publish'
        if ( $data['post_status'] !== 'publish' ) {
            return $data;
        }

        // Se for update de post já publicado, permite
        if ( ! empty( $postarr['ID'] ) ) {
            $old_status = get_post_status( $postarr['ID'] );
            if ( $old_status === 'publish' ) {
                return $data;
            }
        }

        // Descobrir o restaurante (user_id do autor)
        $author_id = (int) $data['post_author'];
        
        // Busca restaurante(s) deste autor
        $restaurants = get_posts([
            'post_type' => 'vc_restaurant',
            'author'    => $author_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);

        if ( empty( $restaurants ) ) {
            return $data; // Sem restaurante, sem limite (ou erro de lógica)
        }

        $restaurant_id = $restaurants[0];
        $max_items = \VC\Subscription\Plan_Manager::get_max_menu_items( $restaurant_id );

        if ( $max_items <= 0 ) {
            return $data; // Ilimitado
        }

        // Conta itens PUBLICADOS deste restaurante
        // Nota: Precisamos filtrar por restaurante, mas itens são do autor.
        // Se o sistema vincula via meta _vc_restaurant_id, melhor contar via meta.
        // Mas aqui no insert ainda pode não ter o meta salvo. Vamos contar pelo autor.
        $count = (int) (new \WP_Query([
            'post_type' => self::SLUG,
            'post_status' => 'publish',
            'author' => $author_id,
            'fields' => 'ids', // Performance
            'no_found_rows' => false, // Precisamos do found_posts? Sim
        ]))->found_posts;

        if ( $count >= $max_items ) {
            // Bloqueia definindo como 'draft'
            $data['post_status'] = 'draft';
            // Opcional: Adicionar aviso ao admin (não funciona bem dentro do filtro)
        }

        return $data;
    }

    private function capabilities(): array {
        return [
            'edit_post'              => 'edit_vc_menu_item',
            'read_post'              => 'read_vc_menu_item',
            'delete_post'            => 'delete_vc_menu_item',
            'edit_posts'             => 'edit_vc_menu_items',
            'edit_others_posts'      => 'edit_others_vc_menu_items',
            'publish_posts'          => 'publish_vc_menu_items',
            'read_private_posts'     => 'read_private_vc_menu_items',
            'delete_posts'           => 'delete_vc_menu_items',
            'delete_private_posts'   => 'delete_private_vc_menu_items',
            'delete_published_posts' => 'delete_pc_menu_items',
            'delete_others_posts'    => 'delete_others_vc_menu_items',
            'edit_private_posts'     => 'edit_private_vc_menu_items',
            'edit_published_posts'   => 'edit_published_vc_menu_items',
            'create_posts'           => 'create_vc_menu_items',
        ];
    }

    public function register_cpt(): void {
        $labels = [ 'name' => __( 'Itens do Cardápio', 'vemcomer' ), 'singular_name' => __( 'Item do Cardápio', 'vemcomer' ) ];
        $args = [
            'labels'       => $labels,
            'public'       => true,
            'show_ui'      => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'supports'     => [ 'title', 'editor', 'thumbnail' ],
            'capability_type' => [ 'vc_menu_item', 'vc_menu_items' ],
            'map_meta_cap'    => true,
            'capabilities'    => $this->capabilities(),
        ];
        register_post_type( self::SLUG, $args );
    }

    public function register_taxonomies(): void {
        register_taxonomy( self::TAX_CATEGORY, self::SLUG, [
            'label'        => __( 'Categoria do Cardápio', 'vemcomer' ),
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ] );

        // Adicionar campos customizados à taxonomia
        add_action( self::TAX_CATEGORY . '_add_form_fields', [ $this, 'add_category_fields' ] );
        add_action( self::TAX_CATEGORY . '_edit_form_fields', [ $this, 'edit_category_fields' ] );
        add_action( 'created_' . self::TAX_CATEGORY, [ $this, 'save_category_fields' ] );
        add_action( 'edited_' . self::TAX_CATEGORY, [ $this, 'save_category_fields' ] );
    }

    /**
     * Adiciona campos ao formulário de nova categoria.
     */
    public function add_category_fields(): void {
        ?>
        <div class="form-field">
            <label for="vc_category_order"><?php echo esc_html__( 'Ordem', 'vemcomer' ); ?></label>
            <input type="number" id="vc_category_order" name="vc_category_order" value="0" min="0" />
            <p class="description"><?php echo esc_html__( 'Ordem de exibição (menor número aparece primeiro).', 'vemcomer' ); ?></p>
        </div>
        <div class="form-field">
            <label for="vc_category_image"><?php echo esc_html__( 'Imagem da Categoria', 'vemcomer' ); ?></label>
            <input type="hidden" id="vc_category_image" name="vc_category_image" value="" />
            <button type="button" class="button vc-upload-image-button"><?php echo esc_html__( 'Selecionar Imagem', 'vemcomer' ); ?></button>
            <div id="vc_category_image_preview" style="margin-top: 10px;"></div>
        </div>
        <?php
    }

    /**
     * Adiciona campos ao formulário de edição de categoria.
     */
    public function edit_category_fields( $term ): void {
        $order = (int) get_term_meta( $term->term_id, '_vc_category_order', true );
        $image_id = (int) get_term_meta( $term->term_id, '_vc_category_image', true );
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="vc_category_order"><?php echo esc_html__( 'Ordem', 'vemcomer' ); ?></label>
            </th>
            <td>
                <input type="number" id="vc_category_order" name="vc_category_order" value="<?php echo esc_attr( $order ); ?>" min="0" />
                <p class="description"><?php echo esc_html__( 'Ordem de exibição (menor número aparece primeiro).', 'vemcomer' ); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="vc_category_image"><?php echo esc_html__( 'Imagem da Categoria', 'vemcomer' ); ?></label>
            </th>
            <td>
                <input type="hidden" id="vc_category_image" name="vc_category_image" value="<?php echo esc_attr( $image_id ); ?>" />
                <button type="button" class="button vc-upload-image-button"><?php echo esc_html__( 'Selecionar Imagem', 'vemcomer' ); ?></button>
                <button type="button" class="button vc-remove-image-button" style="<?php echo $image_id ? '' : 'display:none;'; ?>"><?php echo esc_html__( 'Remover Imagem', 'vemcomer' ); ?></button>
                <div id="vc_category_image_preview" style="margin-top: 10px;">
                    <?php if ( $image_id ) : ?>
                        <?php echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Salva campos customizados da categoria.
     */
    public function save_category_fields( int $term_id ): void {
        if ( isset( $_POST['vc_category_order'] ) ) {
            $order = (int) $_POST['vc_category_order'];
            update_term_meta( $term_id, '_vc_category_order', $order );
        }

        if ( isset( $_POST['vc_category_image'] ) ) {
            $image_id = (int) $_POST['vc_category_image'];
            if ( $image_id > 0 ) {
                update_term_meta( $term_id, '_vc_category_image', $image_id );
            } else {
                delete_term_meta( $term_id, '_vc_category_image' );
            }
        }
    }

    public function register_metaboxes(): void {
        add_meta_box( 'vc_menu_item_meta', __( 'Dados do Item', 'vemcomer' ), [ $this, 'metabox' ], self::SLUG, 'normal', 'high' );
    }

    public function metabox( $post ): void {
        echo '<p><label>' . esc_html__( 'Preço (R$)', 'vemcomer' ) . '</label><br />';
        echo '<input type="text" name="_vc_price" value="' . esc_attr( (string) get_post_meta( $post->ID, '_vc_price', true ) ) . '" class="widefat" /></p>';
        echo '<p><label>' . esc_html__( 'Tempo de preparo (min)', 'vemcomer' ) . '</label><br />';
        echo '<input type="number" name="_vc_prep_time" value="' . esc_attr( (string) get_post_meta( $post->ID, '_vc_prep_time', true ) ) . '" class="small-text" /></p>';
        echo '<p><label><input type="checkbox" name="_vc_is_available" value="1" ' . checked( (bool) get_post_meta( $post->ID, '_vc_is_available', true ), true, false ) . ' /> ' . esc_html__( 'Disponível', 'vemcomer' ) . '</label></p>';
    }

    public function save_meta( int $post_id ): void {
        $map = [ '_vc_price', '_vc_prep_time', '_vc_is_available' ];
        foreach ( $map as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                $value = $_POST[ $key ];
                if ( '_vc_is_available' === $key ) { $value = '1'; }
                update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( (string) $value ) ) );
            } else if ( '_vc_is_available' === $key ) {
                delete_post_meta( $post_id, $key );
            }
        }
    }

    public function admin_columns( array $columns ): array {
        $before = [ 'cb' => $columns['cb'] ?? '', 'title' => $columns['title'] ?? __( 'Título', 'vemcomer' ) ];
        $extra  = [ 'vc_restaurant' => __( 'Restaurante', 'vemcomer' ), 'vc_price' => __( 'Preço', 'vemcomer' ), 'vc_is_available' => __( 'Disponível', 'vemcomer' ) ];
        $rest   = $columns; unset( $rest['cb'], $rest['title'] );
        return array_merge( $before, $extra, $rest );
    }

    public function admin_column_values( string $column, int $post_id ): void {
        if ( 'vc_restaurant' === $column ) {
            $rid = (int) get_post_meta( $post_id, '_vc_restaurant_id', true );
            echo esc_html( $rid ? get_the_title( $rid ) : '—' );
            return;
        }
        if ( 'vc_price' === $column ) {
            echo esc_html( (string) get_post_meta( $post_id, '_vc_price', true ) );
            return;
        }
        if ( 'vc_is_available' === $column ) {
            $v = (string) get_post_meta( $post_id, '_vc_is_available', true );
            echo esc_html( $v ? __( 'Sim', 'vemcomer' ) : __( 'Não', 'vemcomer' ) );
        }
    }

    public function grant_caps(): void {
        if ( ! function_exists( 'get_role' ) ) { return; }
        $all = array_values( $this->capabilities() );

        $admins = get_role( 'administrator' );
        $editor = get_role( 'editor' );
        $author = get_role( 'author' );
        $contrib= get_role( 'contributor' );

        foreach ( $all as $cap ) {
            if ( $admins && ! $admins->has_cap( $cap ) ) { $admins->add_cap( $cap ); }
            if ( $editor && ! $editor->has_cap( $cap ) ) { $editor->add_cap( $cap ); }
        }
        $author_caps = [ 'edit_vc_menu_item', 'edit_vc_menu_items', 'publish_vc_menu_items', 'delete_vc_menu_item', 'delete_vc_menu_items', 'edit_published_vc_menu_items', 'delete_published_vc_menu_items', 'create_vc_menu_items' ];
        if ( $author ) { foreach ( $author_caps as $c ) { if ( ! $author->has_cap( $c ) ) { $author->add_cap( $c ); } } }
        $contrib_caps = [ 'edit_vc_menu_item', 'edit_vc_menu_items', 'create_vc_menu_items' ];
        if ( $contrib ) { foreach ( $contrib_caps as $c ) { if ( ! $contrib->has_cap( $c ) ) { $contrib->add_cap( $c ); } } }
    }
}
