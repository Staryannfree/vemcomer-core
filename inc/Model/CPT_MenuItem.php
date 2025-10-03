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
