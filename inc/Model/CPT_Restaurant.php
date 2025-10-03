<?php
/**
 * CPT_Restaurant — Custom Post Type "Restaurant" (Restaurantes)
 * + Capabilities customizadas e concessão por role (grant_caps).
 *
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
        // Concede capabilities nas roles padrão
        add_action( 'init', [ $this, 'grant_caps' ], 5 );
    }

    private function capabilities(): array {
        return [
            'edit_post'              => 'edit_vc_restaurant',
            'read_post'              => 'read_vc_restaurant',
            'delete_post'            => 'delete_vc_restaurant',
            'edit_posts'             => 'edit_vc_restaurants',
            'edit_others_posts'      => 'edit_others_vc_restaurants',
            'publish_posts'          => 'publish_vc_restaurants',
            'read_private_posts'     => 'read_private_vc_restaurants',
            'delete_posts'           => 'delete_vc_restaurants',
            'delete_private_posts'   => 'delete_private_vc_restaurants',
            'delete_published_posts' => 'delete_published_vc_restaurants',
            'delete_others_posts'    => 'delete_others_vc_restaurants',
            'edit_private_posts'     => 'edit_private_vc_restaurants',
            'edit_published_posts'   => 'edit_published_vc_restaurants',
            'create_posts'           => 'create_vc_restaurants',
        ];
    }

    public function register_cpt(): void {
        $labels = [ 'name' => __( 'Restaurantes', 'vemcomer' ), 'singular_name' => __( 'Restaurante', 'vemcomer' ) ];
        $args = [
            'labels'       => $labels,
            'public'       => true,
            'show_ui'      => true,
            'show_in_menu' => false, // usamos submenus sob vemcomer-root
            'show_in_rest' => true,
            'supports'     => [ 'title', 'editor', 'thumbnail' ],
            'has_archive'  => false,
            'rewrite'      => [ 'slug' => 'restaurant' ],
            'capability_type' => [ 'vc_restaurant', 'vc_restaurants' ],
            'map_meta_cap'    => true,
            'capabilities'    => $this->capabilities(),
        ];
        register_post_type( self::SLUG, $args );
    }

    public function register_taxonomies(): void {
        register_taxonomy( self::TAX_CUISINE, self::SLUG, [
            'label'        => __( 'Cozinha', 'vemcomer' ),
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ] );
    }

    public function register_metaboxes(): void {
        add_meta_box( 'vc_restaurant_meta', __( 'Dados do Restaurante', 'vemcomer' ), [ $this, 'metabox' ], self::SLUG, 'normal', 'high' );
    }

    public function metabox( $post ): void {
        echo '<p><label>' . esc_html__( 'Endereço', 'vemcomer' ) . '</label><br />';
        echo '<input type="text" name="_vc_address" value="' . esc_attr( (string) get_post_meta( $post->ID, '_vc_address', true ) ) . '" class="widefat" /></p>';
        echo '<p><label>' . esc_html__( 'Telefone', 'vemcomer' ) . '</label><br />';
        echo '<input type="text" name="_vc_phone" value="' . esc_attr( (string) get_post_meta( $post->ID, '_vc_phone', true ) ) . '" class="widefat" /></p>';
        echo '<p><label>' . esc_html__( 'Pedido mínimo', 'vemcomer' ) . '</label><br />';
        echo '<input type="text" name="_vc_min_order" value="' . esc_attr( (string) get_post_meta( $post->ID, '_vc_min_order', true ) ) . '" class="widefat" /></p>';
        echo '<p><label><input type="checkbox" name="_vc_has_delivery" value="1" ' . checked( (bool) get_post_meta( $post->ID, '_vc_has_delivery', true ), true, false ) . ' /> ' . esc_html__( 'Possui delivery', 'vemcomer' ) . '</label></p>';
        echo '<p><label><input type="checkbox" name="_vc_is_open" value="1" ' . checked( (bool) get_post_meta( $post->ID, '_vc_is_open', true ), true, false ) . ' /> ' . esc_html__( 'Aberto agora', 'vemcomer' ) . '</label></p>';
    }

    public function save_meta( int $post_id ): void {
        $map = [ '_vc_address', '_vc_phone', '_vc_min_order', '_vc_has_delivery', '_vc_is_open' ];
        foreach ( $map as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                $value = $_POST[ $key ];
                if ( in_array( $key, [ '_vc_has_delivery', '_vc_is_open' ], true ) ) { $value = '1'; }
                update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( (string) $value ) ) );
            } else if ( in_array( $key, [ '_vc_has_delivery', '_vc_is_open' ], true ) ) {
                delete_post_meta( $post_id, $key );
            }
        }
    }

    public function admin_columns( array $columns ): array {
        $before = [ 'cb' => $columns['cb'] ?? '', 'title' => $columns['title'] ?? __( 'Título', 'vemcomer' ) ];
        $extra  = [ 'vc_address' => __( 'Endereço', 'vemcomer' ), 'vc_phone' => __( 'Telefone', 'vemcomer' ), 'vc_min_order' => __( 'Pedido mínimo', 'vemcomer' ), 'vc_has_delivery' => __( 'Delivery', 'vemcomer' ), 'vc_is_open' => __( 'Aberto', 'vemcomer' ) ];
        $rest   = $columns; unset( $rest['cb'], $rest['title'] );
        return array_merge( $before, $extra, $rest );
    }

    public function admin_column_values( string $column, int $post_id ): void {
        $map = [ 'vc_address' => '_vc_address', 'vc_phone' => '_vc_phone', 'vc_min_order' => '_vc_min_order', 'vc_has_delivery' => '_vc_has_delivery', 'vc_is_open' => '_vc_is_open' ];
        if ( isset( $map[ $column ] ) ) {
            $value = get_post_meta( $post_id, $map[ $column ], true );
            echo esc_html( in_array( $column, [ 'vc_has_delivery', 'vc_is_open' ], true ) ? ( $value ? __( 'Sim', 'vemcomer' ) : __( 'Não', 'vemcomer' ) ) : (string) $value );
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
        // Autores: sem "others" e sem deletar de outros
        $author_caps = [ 'edit_vc_restaurant', 'edit_vc_restaurants', 'publish_vc_restaurants', 'delete_vc_restaurant', 'delete_vc_restaurants', 'edit_published_vc_restaurants', 'delete_published_vc_restaurants', 'create_vc_restaurants' ];
        if ( $author ) { foreach ( $author_caps as $c ) { if ( ! $author->has_cap( $c ) ) { $author->add_cap( $c ); } } }
        // Contribuidores: criar/editar não-publicado (sem publicar)
        $contrib_caps = [ 'edit_vc_restaurant', 'edit_vc_restaurants', 'create_vc_restaurants' ];
        if ( $contrib ) { foreach ( $contrib_caps as $c ) { if ( ! $contrib->has_cap( $c ) ) { $contrib->add_cap( $c ); } } }
    }
}
