<?php
/**
 * Registro do Custom Post Type: Restaurantes
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function() {
    $labels = [
        'name'                  => __( 'Restaurantes', 'vemcomer' ),
        'singular_name'         => __( 'Restaurante', 'vemcomer' ),
        'menu_name'             => __( 'Restaurantes', 'vemcomer' ),
        'name_admin_bar'        => __( 'Restaurante', 'vemcomer' ),
        'add_new'               => __( 'Adicionar novo', 'vemcomer' ),
        'add_new_item'          => __( 'Adicionar novo restaurante', 'vemcomer' ),
        'new_item'              => __( 'Novo restaurante', 'vemcomer' ),
        'edit_item'             => __( 'Editar restaurante', 'vemcomer' ),
        'view_item'             => __( 'Ver restaurante', 'vemcomer' ),
        'all_items'             => __( 'Todos os restaurantes', 'vemcomer' ),
        'search_items'          => __( 'Buscar restaurantes', 'vemcomer' ),
        'parent_item_colon'     => __( 'Restaurante pai:', 'vemcomer' ),
        'not_found'             => __( 'Nenhum restaurante encontrado.', 'vemcomer' ),
        'not_found_in_trash'    => __( 'Nenhum restaurante na lixeira.', 'vemcomer' ),
        'featured_image'        => __( 'Imagem destacada', 'vemcomer' ),
        'set_featured_image'    => __( 'Definir imagem destacada', 'vemcomer' ),
        'remove_featured_image' => __( 'Remover imagem destacada', 'vemcomer' ),
        'use_featured_image'    => __( 'Usar como imagem destacada', 'vemcomer' ),
        'archives'              => __( 'Arquivo de restaurantes', 'vemcomer' ),
        'insert_into_item'      => __( 'Inserir no restaurante', 'vemcomer' ),
        'uploaded_to_this_item' => __( 'Enviado para este restaurante', 'vemcomer' ),
        'filter_items_list'     => __( 'Filtrar lista de restaurantes', 'vemcomer' ),
        'items_list_navigation' => __( 'Navegação de lista de restaurantes', 'vemcomer' ),
        'items_list'            => __( 'Lista de restaurantes', 'vemcomer' ),
    ];

    $capabilities = [
        'edit_post'          => 'edit_vc_restaurant',
        'read_post'          => 'read_vc_restaurant',
        'delete_post'        => 'delete_vc_restaurant',
        'edit_posts'         => 'edit_vc_restaurants',
        'edit_others_posts'  => 'edit_others_vc_restaurants',
        'delete_posts'       => 'delete_vc_restaurants',
        'publish_posts'      => 'publish_vc_restaurants',
        'read_private_posts' => 'read_private_vc_restaurants',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-store',
        'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'has_archive'        => true,
        'rewrite'            => [ 'slug' => 'restaurantes' ],
        'capability_type'    => [ 'vc_restaurant', 'vc_restaurants' ],
        'map_meta_cap'       => true,
        'capabilities'       => $capabilities,
        'show_in_rest'       => true,
    ];

    register_post_type( 'vc_restaurant', $args );
});
