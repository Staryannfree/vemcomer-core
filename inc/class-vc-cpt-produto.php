<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class VC_CPT_Produto {
    const SLUG = 'vc_produto';

    public function init(): void {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
        add_action( 'add_meta_boxes', [ $this, 'metaboxes' ] );
        add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
    }

    public function register_cpt(): void {
        $labels = [
            'name' => 'Produtos',
            'singular_name' => 'Produto',
        ];
        $args = [
            'labels' => $labels,
            'public' => true,
            'show_in_menu' => false,
            'supports' => [ 'title', 'editor', 'thumbnail' ],
            'show_in_rest' => true,
        ];
        register_post_type( self::SLUG, $args );
    }

    public function register_taxonomies(): void {
        register_taxonomy( 'vc_categoria', self::SLUG, [
            'label' => 'Categorias',
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ] );
    }

    public function metaboxes(): void {
        add_meta_box( 'vc_prod_preco', 'Preço', [ $this, 'render_preco' ], self::SLUG, 'side' );
    }

    public function render_preco( $post ): void {
        $value = get_post_meta( $post->ID, '_vc_preco', true );
        echo '<label for="vc_preco">Preço</label>';
        echo '<input type="text" id="vc_preco" name="vc_preco" value="' . esc_attr( $value ) . '" class="widefat" />';
        nonce_field( 'vc_preco_nonce', 'vc_preco_nonce_field' );
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['vc_preco_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_preco_nonce_field'], 'vc_preco_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

        $preco = isset( $_POST['vc_preco'] ) ? vc_sanitize_money( $_POST['vc_preco'] ) : '';
        update_post_meta( $post_id, '_vc_preco', $preco );
    }
}
