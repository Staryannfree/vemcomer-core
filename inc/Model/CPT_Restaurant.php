<?php
/**
 * CPT_Restaurant — Custom Post Type "Restaurant" (Restaurantes)
 * @package VemComerCore
 */

namespace VC\Model;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CPT_Restaurant {
    public const SLUG = 'vc_restaurant';
    public const TAX_CUISINE = 'vc_cuisine';

    public function init(): void {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
        add_filter( 'manage_' . self::SLUG . '_posts_columns', [ $this, 'admin_columns' ] );
        add_action( 'manage_' . self::SLUG . '_posts_custom_column', [ $this, 'admin_column_values' ], 10, 2 );
    }

    public function register_cpt(): void {
        $labels = [
            'name' => __( 'Restaurantes', 'vemcomer' ),
            'singular_name' => __( 'Restaurante', 'vemcomer' ),
        ];
        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'supports' => [ 'title', 'editor', 'thumbnail' ],
            'has_archive' => false,
            'rewrite' => [ 'slug' => 'restaurant' ],
        ];
        register_post_type( self::SLUG, $args );
    }

    public function register_taxonomies(): void {
        $labels = [ 'name' => __( 'Cozinhas', 'vemcomer' ), 'singular_name' => __( 'Cozinha', 'vemcomer' ) ];
        register_taxonomy( self::TAX_CUISINE, [ self::SLUG ], [
            'labels' => $labels,
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_rest' => true,
        ] );
    }

    public function register_metaboxes(): void {
        add_meta_box( 'vc_restaurant_info', __( 'Informações do Restaurante', 'vemcomer' ), [ $this, 'render_metabox' ], self::SLUG, 'normal', 'high' );
    }

    public function render_metabox( $post ): void {
        $fields = $this->get_fields( (int) $post->ID );
        wp_nonce_field( 'vc_restaurant_nonce', 'vc_restaurant_nonce_field' );
        echo '<table class="form-table">';
        $this->text_row( 'vc_address', __( 'Endereço', 'vemcomer' ), $fields['address'] );
        $this->text_row( 'vc_phone', __( 'Telefone', 'vemcomer' ), $fields['phone'] );
        $this->text_row( 'vc_whatsapp', __( 'WhatsApp', 'vemcomer' ), $fields['whatsapp'] );
        $this->text_row( 'vc_min_order', __( 'Pedido mínimo', 'vemcomer' ), $fields['min_order'] );
        $this->text_row( 'vc_delivery_time', __( 'Tempo de entrega (min)', 'vemcomer' ), $fields['delivery_time'] );
        $this->text_row( 'vc_opening_hours', __( 'Horário de funcionamento', 'vemcomer' ), $fields['opening_hours'] );
        $this->checkbox_row( 'vc_is_open', __( 'Aberto agora', 'vemcomer' ), $fields['is_open'] );
        echo '</table>';
    }

    private function text_row( string $name, string $label, string $value ): void {
        echo '<tr><th><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><input type="text" class="regular-text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" /></td></tr>';
    }

    private function checkbox_row( string $name, string $label, string $checked ): void {
        $check = $checked ? 'checked' : '';
        echo '<tr><th>' . esc_html( $label ) . '</th>';
        echo '<td><label><input type="checkbox" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="1" ' . $check . ' /> ' . esc_html__( 'Ativo', 'vemcomer' ) . '</label></td></tr>';
    }

    private function get_fields( int $post_id ): array {
        return [
            'address'       => (string) get_post_meta( $post_id, '_vc_address', true ),
            'phone'         => (string) get_post_meta( $post_id, '_vc_phone', true ),
            'whatsapp'      => (string) get_post_meta( $post_id, '_vc_whatsapp', true ),
            'min_order'     => (string) get_post_meta( $post_id, '_vc_min_order', true ),
            'delivery_time' => (string) get_post_meta( $post_id, '_vc_delivery_time', true ),
            'opening_hours' => (string) get_post_meta( $post_id, '_vc_opening_hours', true ),
            'is_open'       => (string) get_post_meta( $post_id, '_vc_is_open', true ),
        ];
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['vc_restaurant_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_restaurant_nonce_field'], 'vc_restaurant_nonce' ) ) { return; }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        $address       = isset( $_POST['vc_address'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_address'] ) ) : '';
        $phone         = isset( $_POST['vc_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_phone'] ) ) : '';
        $whatsapp      = isset( $_POST['vc_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_whatsapp'] ) ) : '';
        $min_order     = isset( $_POST['vc_min_order'] ) ? preg_replace( '/[^0-9.,]/', '', (string) wp_unslash( $_POST['vc_min_order'] ) ) : '';
        $delivery_time = isset( $_POST['vc_delivery_time'] ) ? preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['vc_delivery_time'] ) ) : '';
        $opening_hours = isset( $_POST['vc_opening_hours'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_opening_hours'] ) ) : '';
        $is_open       = isset( $_POST['vc_is_open'] ) ? '1' : '';
        update_post_meta( $post_id, '_vc_address', $address );
        update_post_meta( $post_id, '_vc_phone', $phone );
        update_post_meta( $post_id, '_vc_whatsapp', $whatsapp );
        update_post_meta( $post_id, '_vc_min_order', $min_order );
        update_post_meta( $post_id, '_vc_delivery_time', $delivery_time );
        update_post_meta( $post_id, '_vc_opening_hours', $opening_hours );
        update_post_meta( $post_id, '_vc_is_open', $is_open );
    }

    public function admin_columns( array $columns ): array {
        $before = [ 'cb' => $columns['cb'] ?? '', 'title' => $columns['title'] ?? __( 'Título', 'vemcomer' ) ];
        $extra  = [ 'vc_address' => __( 'Endereço', 'vemcomer' ), 'vc_phone' => __( 'Telefone', 'vemcomer' ), 'vc_min_order' => __( 'Pedido mínimo', 'vemcomer' ), 'vc_is_open' => __( 'Aberto', 'vemcomer' ) ];
        $rest   = $columns; unset( $rest['cb'], $rest['title'] );
        return array_merge( $before, $extra, $rest );
    }

    public function admin_column_values( string $column, int $post_id ): void {
        $map = [ 'vc_address' => '_vc_address', 'vc_phone' => '_vc_phone', 'vc_min_order' => '_vc_min_order', 'vc_is_open' => '_vc_is_open' ];
        if ( isset( $map[ $column ] ) ) {
            $value = get_post_meta( $post_id, $map[ $column ], true );
            echo esc_html( $column === 'vc_is_open' ? ( $value ? __( 'Sim', 'vemcomer' ) : __( 'Não', 'vemcomer' ) ) : (string) $value );
        }
    }
}
