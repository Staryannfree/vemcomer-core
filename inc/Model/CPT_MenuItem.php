<?php
/**
 * CPT_MenuItem — Custom Post Type "Menu Item" (Itens do Cardápio)
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
    }

    public function register_cpt(): void {
        $labels = [ 'name' => __( 'Itens do Cardápio', 'vemcomer' ), 'singular_name' => __( 'Item do Cardápio', 'vemcomer' ) ];
        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'supports' => [ 'title', 'editor', 'thumbnail' ],
            'has_archive' => false,
            'rewrite' => [ 'slug' => 'menu-item' ],
        ];
        register_post_type( self::SLUG, $args );
    }

    public function register_taxonomies(): void {
        $labels = [ 'name' => __( 'Categorias do Cardápio', 'vemcomer' ), 'singular_name' => __( 'Categoria do Cardápio', 'vemcomer' ) ];
        register_taxonomy( self::TAX_CATEGORY, [ self::SLUG ], [
            'labels' => $labels,
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_rest' => true,
        ] );
    }

    public function register_metaboxes(): void {
        add_meta_box( 'vc_menu_item_info', __( 'Informações do Item', 'vemcomer' ), [ $this, 'render_metabox' ], self::SLUG, 'normal', 'default' );
    }

    public function render_metabox( $post ): void {
        $fields = $this->get_fields( (int) $post->ID );
        wp_nonce_field( 'vc_menu_item_nonce', 'vc_menu_item_nonce_field' );
        echo '<table class="form-table">';
        // Relacionamento com Restaurante
        echo '<tr><th><label for="vc_restaurant_id">' . esc_html__( 'Restaurante', 'vemcomer' ) . '</label></th><td>';
        wp_dropdown_pages([
            'post_type'        => CPT_Restaurant::SLUG,
            'name'             => 'vc_restaurant_id',
            'id'               => 'vc_restaurant_id',
            'show_option_none' => __( '— Selecione —', 'vemcomer' ),
            'option_none_value'=> '',
            'selected'         => $fields['restaurant_id'],
        ]);
        echo '</td></tr>';
        // Preço
        $this->text_row( 'vc_price', __( 'Preço', 'vemcomer' ), $fields['price'] );
        // Tempo de preparo
        $this->text_row( 'vc_prep_time', __( 'Tempo de preparo (min)', 'vemcomer' ), $fields['prep_time'] );
        // Disponibilidade
        echo '<tr><th>' . esc_html__( 'Disponível', 'vemcomer' ) . '</th><td>';
        echo '<label><input type="checkbox" name="vc_is_available" value="1" ' . checked( $fields['is_available'], '1', false ) . ' /> ' . esc_html__( 'Ativo', 'vemcomer' ) . '</label>';
        echo '</td></tr>';
        echo '</table>';
    }

    private function text_row( string $name, string $label, string $value ): void {
        echo '<tr><th><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><input type="text" class="regular-text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" /></td></tr>';
    }

    private function get_fields( int $post_id ): array {
        return [
            'restaurant_id' => (int) get_post_meta( $post_id, '_vc_restaurant_id', true ),
            'price'         => (string) get_post_meta( $post_id, '_vc_price', true ),
            'prep_time'     => (string) get_post_meta( $post_id, '_vc_prep_time', true ),
            'is_available'  => (string) get_post_meta( $post_id, '_vc_is_available', true ),
        ];
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['vc_menu_item_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_menu_item_nonce_field'], 'vc_menu_item_nonce' ) ) { return; }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        $restaurant_id = isset( $_POST['vc_restaurant_id'] ) ? (int) $_POST['vc_restaurant_id'] : 0;
        $price         = isset( $_POST['vc_price'] ) ? preg_replace( '/[^0-9.,]/', '', (string) wp_unslash( $_POST['vc_price'] ) ) : '';
        $prep_time     = isset( $_POST['vc_prep_time'] ) ? preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['vc_prep_time'] ) ) : '';
        $is_available  = isset( $_POST['vc_is_available'] ) ? '1' : '';
        update_post_meta( $post_id, '_vc_restaurant_id', $restaurant_id );
        update_post_meta( $post_id, '_vc_price', $price );
        update_post_meta( $post_id, '_vc_prep_time', $prep_time );
        update_post_meta( $post_id, '_vc_is_available', $is_available );
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
}
