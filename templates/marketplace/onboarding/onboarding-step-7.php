<?php
/**
 * Onboarding Step 7: Revisão e Ativação
 * 
 * @var array $wizard_data
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Buscar dados reais do banco de dados (não depender apenas do wizard_data que pode estar desatualizado)
$restaurant = null;
if ( ! empty( $wizard_data['restaurant_id'] ?? null ) ) {
    $restaurant = get_post( (int) $wizard_data['restaurant_id'] );
} else {
    // Tentar buscar restaurante do usuário atual
    $user_id = get_current_user_id();
    if ( $user_id > 0 ) {
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );
        if ( $restaurant_id > 0 ) {
            $restaurant = get_post( $restaurant_id );
        }
    }
}

// Buscar categorias reais do banco
$category_count = 0;
if ( $restaurant ) {
    $user_categories = get_terms( [
        'taxonomy'   => 'vc_menu_category',
        'hide_empty' => false,
        'meta_query' => [
            [
                'key'   => '_vc_restaurant_id',
                'value' => $restaurant->ID,
            ],
        ],
    ] );
    if ( ! is_wp_error( $user_categories ) && ! empty( $user_categories ) ) {
        foreach ( $user_categories as $cat ) {
            $is_catalog = get_term_meta( $cat->term_id, '_vc_is_catalog_category', true );
            if ( $is_catalog !== '1' ) {
                $category_count++;
            }
        }
    }
}

// Buscar produtos reais do banco
$product_count = 0;
$real_products = [];
if ( $restaurant ) {
    // Debug temporário
    $current_user_id = get_current_user_id();
    $user_restaurant_id = (int) get_user_meta( $current_user_id, 'vc_restaurant_id', true );
    error_log( sprintf( 'Passo 7: Buscando produtos para restaurante ID: %d (wizard_data restaurant_id: %s, user meta restaurant_id: %d, user_id: %d)', 
        $restaurant->ID, 
        $wizard_data['restaurant_id'] ?? 'não definido',
        $user_restaurant_id,
        $current_user_id
    ) );
    
    // Buscar TODOS os produtos do restaurante (sem filtro de status para debug)
    $menu_items = get_posts( [
        'post_type'      => 'vc_menu_item',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_query'     => [
            [
                'key'   => '_vc_restaurant_id',
                'value' => $restaurant->ID,
            ],
        ],
    ] );
    
    // Debug temporário - verificar também sem meta_query
    $all_menu_items = get_posts( [
        'post_type'      => 'vc_menu_item',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ] );
    error_log( sprintf( 'Passo 7: Total de produtos no sistema: %d, Produtos do restaurante %d: %d', 
        count( $all_menu_items ), 
        $restaurant->ID, 
        count( $menu_items ) 
    ) );
    
    // Verificar produtos sem filtro de restaurante para debug
    if ( ! empty( $all_menu_items ) && empty( $menu_items ) ) {
        error_log( 'Passo 7: ATENÇÃO - Existem produtos no sistema mas nenhum para este restaurante!' );
        foreach ( array_slice( $all_menu_items, 0, 5 ) as $item ) {
            $restaurant_id_meta = get_post_meta( $item->ID, '_vc_restaurant_id', true );
            error_log( sprintf( 'Passo 7: Produto exemplo "%s" (ID: %d) tem restaurant_id: %s', 
                $item->post_title, 
                $item->ID, 
                $restaurant_id_meta 
            ) );
        }
    }
    
    $product_count = count( $menu_items );
    $real_products = $menu_items;
} else {
    error_log( 'Passo 7: Restaurante não encontrado. wizard_data: ' . print_r( $wizard_data, true ) );
}

// Buscar adicionais reais
$has_addons = false;
if ( $restaurant ) {
    $store_groups = get_posts( [
        'post_type'      => 'vc_product_modifier',
        'posts_per_page' => 1,
        'post_status'    => 'any',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'   => '_vc_restaurant_id',
                'value' => $restaurant->ID,
            ],
            [
                'key'   => '_vc_group_id',
                'value' => '0',
            ],
            [
                'key'     => '_vc_catalog_group_id',
                'compare' => 'EXISTS',
            ],
        ],
    ] );
    $has_addons = ! empty( $store_groups );
}
?>
<div class="wizard-title">Seu restaurante está quase pronto!</div>
<div style="margin-top:24px;">
    <div style="display:flex;align-items:center;padding:12px;background:#eaf8f1;border-radius:8px;margin-bottom:8px;">
        <span style="font-size:24px;margin-right:12px;">✔</span>
        <span style="font-weight:600;">Dados básicos da loja</span>
    </div>
    <div style="display:flex;align-items:center;padding:12px;background:#eaf8f1;border-radius:8px;margin-bottom:8px;">
        <span style="font-size:24px;margin-right:12px;">✔</span>
        <span style="font-weight:600;">Endereço e horários</span>
    </div>
    <div style="display:flex;align-items:center;padding:12px;background:#eaf8f1;border-radius:8px;margin-bottom:8px;">
        <span style="font-size:24px;margin-right:12px;">✔</span>
        <span style="font-weight:600;">Categorias do cardápio (<?php echo esc_html( $category_count ); ?> criadas)</span>
    </div>
    <div style="display:flex;align-items:center;padding:12px;background:#eaf8f1;border-radius:8px;margin-bottom:8px;">
        <span style="font-size:24px;margin-right:12px;">✔</span>
        <span style="font-weight:600;">Produtos cadastrados (<?php echo esc_html( $product_count ); ?> produtos)</span>
    </div>
    <div style="display:flex;align-items:center;padding:12px;background:<?php echo $has_addons ? '#eaf8f1' : '#fffbe2'; ?>;border-radius:8px;margin-bottom:8px;">
        <span style="font-size:24px;margin-right:12px;"><?php echo $has_addons ? '✔' : '⭕'; ?></span>
        <span style="font-weight:600;">
            <?php if ( $has_addons ) : ?>
                Adicionais básicos configurados para seus primeiros produtos.
            <?php else : ?>
                Você ainda não configurou adicionais (opcional, mas recomendado).
            <?php endif; ?>
        </span>
    </div>
</div>

<div style="margin-top:32px;padding:20px;background:#f9f9f9;border-radius:8px;">
    <div style="font-weight:700;margin-bottom:12px;color:#2d8659;">Resumo da sua loja</div>
    <div style="margin-bottom:8px;"><strong>Nome:</strong> <?php echo esc_html( $wizard_data['name'] ?? '' ); ?></div>
    <div style="margin-bottom:8px;"><strong>WhatsApp:</strong> <?php echo esc_html( $wizard_data['whatsapp'] ?? '' ); ?></div>
    <div style="margin-bottom:8px;"><strong>Endereço:</strong> <?php echo esc_html( $wizard_data['address'] ?? '' ); ?></div>
    <div style="margin-top:16px;">
        <strong>Produtos:</strong>
        <?php if ( ! empty( $real_products ) ) : ?>
            <ul style="margin:8px 0 0 20px;padding:0;">
                <?php 
                $products_to_show = array_slice( $real_products, 0, 3 );
                foreach ( $products_to_show as $product ) : 
                    $price = (float) get_post_meta( $product->ID, '_vc_price', true );
                ?>
                    <li><?php echo esc_html( $product->post_title ); ?> - R$ <?php echo number_format( $price, 2, ',', '.' ); ?></li>
                <?php endforeach; ?>
                <?php if ( $product_count > 3 ) : ?>
                    <li style="color:#6b7672;font-style:italic;">... e mais <?php echo esc_html( $product_count - 3 ); ?> produto(s)</li>
                <?php endif; ?>
            </ul>
        <?php else : ?>
            <p style="color:#6b7672;margin:8px 0 0 0;">Nenhum produto cadastrado ainda.</p>
        <?php endif; ?>
    </div>
</div>

