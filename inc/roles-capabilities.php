<?php
/**
 * Mapeamento de capabilities para o CPT vc_restaurant
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function vc_get_restaurant_caps() : array {
    return [
        'edit_vc_restaurant',
        'read_vc_restaurant',
        'delete_vc_restaurant',
        'edit_vc_restaurants',
        'edit_others_vc_restaurants',
        'delete_vc_restaurants',
        'publish_vc_restaurants',
        'read_private_vc_restaurants',
    ];
}

/**
 * Capabilities necessárias para gerenciar itens do cardápio.
 */
function vc_get_menu_caps() : array {
    return [
        'edit_vc_menu_item',
        'read_vc_menu_item',
        'delete_vc_menu_item',
        'edit_vc_menu_items',
        'publish_vc_menu_items',
        'delete_vc_menu_items',
        'edit_published_vc_menu_items',
        'delete_published_vc_menu_items',
        'create_vc_menu_items',
    ];
}

/**
 * Atribui capabilities às roles padrão (admin, editor, author opcional, shop_manager se Woo existir)
 */
function vc_assign_caps_to_roles() : void {
    $roles = [ 'administrator', 'editor' ];

    // Adiciona shop_manager se WooCommerce estiver ativo
    if ( class_exists( 'WC_Role' ) || get_role( 'shop_manager' ) ) {
        $roles[] = 'shop_manager';
    }

    $caps = array_merge( vc_get_restaurant_caps(), vc_get_menu_caps() );
    foreach ( $roles as $role_name ) {
        $role = get_role( $role_name );
        if ( ! $role ) continue;
        foreach ( $caps as $cap ) {
            if ( ! $role->has_cap( $cap ) ) {
                $role->add_cap( $cap );
            }
        }
    }
}

/** Remove capabilities nas roles informadas */
function vc_remove_caps_from_roles() : void {
    $roles = [ 'administrator', 'editor', 'shop_manager' ];
    $caps  = vc_get_restaurant_caps();
    foreach ( $roles as $role_name ) {
        $role = get_role( $role_name );
        if ( ! $role ) continue;
        foreach ( $caps as $cap ) {
            if ( $role->has_cap( $cap ) ) {
                $role->remove_cap( $cap );
            }
        }
    }
}

// Activation/Deactivation hooks (precisam estar no arquivo principal, mas re-exportamos funções aqui)

add_action( 'init', 'vc_register_restaurant_owner_role' );

/**
 * Registra/atualiza a role "lojista" (donos de restaurante no painel).
 */
function vc_register_restaurant_owner_role(): void {
    $caps = array_merge(
        vc_get_restaurant_caps(),
        vc_get_menu_caps(),
        [ 'read' ]
    );

    $caps_map = [];
    foreach ( $caps as $cap ) {
        $caps_map[ $cap ] = true;
    }

    $role = get_role( 'lojista' );
    if ( ! $role ) {
        add_role( 'lojista', __( 'Lojista', 'vemcomer' ), $caps_map );
        return;
    }

    foreach ( array_keys( $caps_map ) as $cap ) {
        if ( ! $role->has_cap( $cap ) ) {
            $role->add_cap( $cap );
        }
    }
}
