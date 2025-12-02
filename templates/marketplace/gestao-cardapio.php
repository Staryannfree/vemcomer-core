<?php
/**
 * Template Name: Marketplace - Gestao Cardapio
 * Description: Versão dinâmica do layout templates/marketplace/gestao-cardapio.html, exibindo itens reais do cardápio.
 */

if (!defined('ABSPATH')) {
    exit;
}

$vc_marketplace_inline = defined('VC_MARKETPLACE_INLINE') && VC_MARKETPLACE_INLINE;

wp_enqueue_style(
    'vc-marketplace-gestao-font',
    'https://fonts.googleapis.com/css?family=Montserrat:700,600,500&display=swap',
    [],
    null
);

if (! $vc_marketplace_inline) {
    get_header();
}

/**
 * Localiza o restaurante associado ao usuário logado.
 */
if (! function_exists('vc_marketplace_get_restaurant_for_user')) {
    function vc_marketplace_get_restaurant_for_user(): ?WP_Post
    {
        if (! is_user_logged_in()) {
            return null;
        }

        $current_user = wp_get_current_user();

        if (! $current_user instanceof WP_User || ! $current_user->ID) {
            return null;
        }

        $filtered = (int) apply_filters('vemcomer/restaurant_id_for_user', 0, $current_user);
        if ($filtered > 0) {
            $candidate = get_post($filtered);
            if ($candidate instanceof WP_Post && 'vc_restaurant' === $candidate->post_type) {
                return $candidate;
            }
        }

        $meta_id = (int) get_user_meta($current_user->ID, 'vc_restaurant_id', true);
        if ($meta_id) {
            $candidate = get_post($meta_id);
            if ($candidate instanceof WP_Post && 'vc_restaurant' === $candidate->post_type) {
                return $candidate;
            }
        }

        $q = new WP_Query([
            'post_type'      => 'vc_restaurant',
            'author'         => $current_user->ID,
            'posts_per_page' => 1,
            'post_status'    => ['publish', 'pending', 'draft'],
            'no_found_rows'  => true,
        ]);

        if ($q->have_posts()) {
            $restaurant = $q->posts[0];
            wp_reset_postdata();
            return $restaurant;
        }

        wp_reset_postdata();
        return null;
    }
}

$restaurant = vc_marketplace_get_restaurant_for_user();

// Inicializar variáveis sempre
$categories = [];
$default_category = [
    'id'    => 'sem-categoria',
    'name'  => __('Sem categoria', 'vemcomer'),
    'slug'  => 'sem-categoria',
    'items' => [],
];
$stats = [
    'total'       => 0,
    'active'      => 0,
    'paused'      => 0,
    'no_thumb'    => 0,
    'categories'  => 0,
];

if ($restaurant instanceof WP_Post) {
    $items_query = new WP_Query([
        'post_type'      => 'vc_menu_item',
        'post_status'    => ['publish', 'draft', 'pending'],
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'   => '_vc_restaurant_id',
                'value' => $restaurant->ID,
            ],
        ],
    ]);

    if ($items_query->have_posts()) {
        // Debug: verificar quantos posts foram encontrados
        error_log('VC Debug: Encontrados ' . count($items_query->posts) . ' itens do cardápio para RestID: ' . $restaurant->ID);
        
        foreach ($items_query->posts as $item) {
            // Debug item
            // error_log('VC Debug: Processando item: ' . $item->ID . ' - ' . $item->post_title);

            // Obtém a imagem destacada do produto
            $thumb = get_the_post_thumbnail_url($item->ID, 'medium');
            // Se não houver, tenta obter via attachment ID
            if (!$thumb) {
                $thumb_id = get_post_thumbnail_id($item->ID);
                if ($thumb_id) {
                    $thumb = wp_get_attachment_image_url($thumb_id, 'medium');
                }
            }
            $price_raw  = get_post_meta($item->ID, '_vc_price', true);
            $price      = $price_raw !== '' ? $price_raw : __('Sem preço', 'vemcomer');
            $prep_time  = get_post_meta($item->ID, '_vc_prep_time', true);
            $available  = (bool) get_post_meta($item->ID, '_vc_is_available', true);
            $excerpt    = has_excerpt($item) ? wp_strip_all_tags(get_the_excerpt($item)) : wp_trim_words(wp_strip_all_tags($item->post_content), 18, '...');
            $modifiers  = get_post_meta($item->ID, '_vc_menu_item_modifiers', true);
            $modifier_titles = [];

            if (is_array($modifiers) && ! empty($modifiers)) {
                $modifier_posts = get_posts([
                    'post_type'      => 'vc_product_modifier',
                    'post__in'       => array_map('absint', $modifiers),
                    'posts_per_page' => -1,
                ]);

                foreach ($modifier_posts as $mod) {
                    $modifier_titles[] = $mod->post_title;
                }
            }

            $terms = get_the_terms($item, 'vc_menu_category');
            if (! $terms || is_wp_error($terms)) {
                $terms = [(object) $default_category];
            }

            foreach ($terms as $term) {
                $term_id = $term->term_id ?? $term->id ?? 'sem-categoria';
                $slug    = $term->slug ?? 'sem-categoria';
                $name    = $term->name ?? __('Sem categoria', 'vemcomer');

                if (! isset($categories[$term_id])) {
                    // error_log('VC Debug: Criando categoria no array: ' . $term_id . ' (' . $name . ')');
                    $categories[$term_id] = [
                        'id'    => $term_id,
                        'slug'  => sanitize_title($slug),
                        'name'  => $name,
                        'items' => [],
                    ];
                }

                $item_payload = [
                    'id'         => $item->ID,
                    'title'      => $item->post_title,
                    'status'     => $available ? 'ativo' : 'pausado',
                    'description'=> $excerpt,
                    'price'      => $price,
                    'prep_time'  => $prep_time ? (int) $prep_time : 0,
                    'thumb'      => $thumb,
                    'edit_url'   => get_edit_post_link($item->ID),
                    'modifiers'  => $modifier_titles,
                ];

                $categories[$term_id]['items'][] = $item_payload;
                // error_log('VC Debug: Adicionado item ' . $item->ID . ' na categoria ' . $term_id . '. Total itens agora: ' . count($categories[$term_id]['items']));

                $stats['total']++;
                if ($available) {
                    $stats['active']++;
                } else {
                    $stats['paused']++;
                }
                if (! $thumb) {
                    $stats['no_thumb']++;
                }
            }
        }
    }

    wp_reset_postdata();
}

$categories_for_view = [];

// Converte array associativo para indexado e ordena categorias por nome (mantendo os itens intactos)
if (!empty($categories) && is_array($categories)) {
    $categories_for_view = array_values($categories);

    usort($categories_for_view, function ($a, $b) {
        $name_a = isset($a['name']) ? $a['name'] : '';
        $name_b = isset($b['name']) ? $b['name'] : '';
        return strcasecmp($name_a, $name_b);
    });

    // Debug final
    /*
    error_log('VC Debug: Categories for view preparado. Total cats: ' . count($categories_for_view));
    foreach ($categories_for_view as $c) {
        error_log('VC Debug: Cat ' . $c['name'] . ' tem ' . (isset($c['items']) ? count($c['items']) : 0) . ' itens.');
    }
    */
}

// Se não houver categorias mas houver restaurante, adiciona categoria padrão
if (empty($categories_for_view) && $restaurant instanceof WP_Post) {
    $categories_for_view[] = $default_category;
}

// Atualiza estatística de categorias (sempre após processar tudo)
$stats['categories'] = is_array($categories_for_view) ? count($categories_for_view) : 0;
?>
<div class="menu-gestao-container">
    <style>
        body { background: #f6f9f6; font-family: 'Montserrat', Arial, sans-serif; margin:0; color: #232a2c;}
        .menu-gestao-container {max-width: 1000px; margin:0 auto; padding:22px 7px 42px 7px;}
        .menu-top {display:flex;align-items:center;gap:17px;margin-bottom:9px;}
        .menu-title {font-size:1.18em;font-weight:900;color:#2d8659;}
        .menu-btn {background:#2d8659;color:#fff;font-weight:800;border:none;border-radius:8px;padding:10px 18px;font-size:1em;cursor:pointer;box-shadow:0 2px 8px #2d865918;}
        .menu-btn.secondary {background:#facb32;color:#232a2c;}
        .tabs-cat {display:flex;gap:5px;border-bottom:2px solid #eaf8f1;margin:12px 0 19px 0;}
        .cat-tab-btn {background:none;border:none;color:#6b7672;padding:10px 19px 7px 19px;font-size:1em;font-weight:700;cursor:pointer;transition:color .12s;}
        .cat-tab-btn.active {color:#2d8659;border-bottom:3px solid #2d8659;background:#eaf8f1;}
        .tab-content {margin-bottom:24px;}
        .prod-list {display:flex;flex-wrap:wrap;gap:20px;}
        .prod-card {background:#fff;border-radius:13px;box-shadow:0 1px 14px #2d865914;min-width:295px;max-width:309px;flex:1 1 315px;position:relative;padding:16px 12px 13px 16px;margin-bottom:9px;display:flex;flex-direction:column;align-items:flex-start;transition:opacity 0.3s;}
        .prod-card[data-available="0"] {opacity:0.5;background:#f5f5f5;}
        .prod-card[data-available="0"] .prod-img {opacity:0.5;filter:grayscale(100%);}
        .prod-card[data-available="0"] .prod-info {opacity:0.6;}
        .prod-card[data-available="0"] .prod-nome {color:#999;}
        .prod-card[data-available="0"] .prod-desc {color:#999;}
        .prod-card[data-available="0"] .prod-preco {color:#999;}
        .prod-card[data-available="0"] .modif-box {opacity:0.6;}
        .prod-img {width:63px;height:63px;object-fit:cover;border-radius:9px;background:#f5f6f4;box-shadow:0 2px 8px #bbb1;}
        .prod-info {margin-left:14px;min-width:0;flex:1;}
        .prod-nome {font-weight:900;color:#232a2c;font-size:1.08em;}
        .prod-ativo {background:#cdf9e0;color:#23863b;padding:4px 12px;border-radius:9px;font-size:.92em;margin-left:9px;font-weight:700;}
        .prod-pausado {background:#ffe7e7;color:#ea5252;padding:4px 12px;border-radius:9px;font-size:.92em;margin-left:9px;font-weight:700;}
        .prod-preco {color:#2d8659;font-size:1.03em;font-weight:900;margin-top:2px;}
        .prod-desc {color:#6b7672;margin:2px 0 2px 0;font-size:.98em;}
        .prod-actions {display:flex;gap:9px;margin-top:7px;}
        .pedit-btn {background:#fff;color:#2d8659;border:1.5px solid #cdf9e0;font-weight:700;padding:6px 12px;border-radius:7px;font-size:.96em;cursor:pointer;}
        .pedit-btn.pause {background:#facb32;color:#232a2c;}
        .pedit-btn.del {background:#ffe7e7;color:#ea5252;border-color:#ffe7e7;}
        .prod-alerta {color:#e99326;margin-left:8px;font-size:1.05em;}
        .modif-box {background:#eaf8f1;border-radius:8px;padding:6px 10px;font-size:.98em;margin-top:11px;width:100%;}
        .modif-title {font-weight:700;color:#3176da;font-size:.96em;margin-bottom:3px;}
        .modif-list {margin:0;padding:0;display:flex;gap:9px;flex-wrap:wrap;}
        .modif-badge {background:#fffbe2;color:#fa7e1e;border-radius:7px;padding:3px 9px;margin:0 0 4px 0; font-weight:700;font-size:.94em;}
        .modif-edit {margin-left:11px;color:#2d8659;text-decoration:underline;cursor:pointer;font-size:.97em;}
        .empty-state {background:#fff;border-radius:12px;padding:18px 16px;box-shadow:0 2px 12px #2d865910;color:#6b7672;font-weight:600;}
        .menu-stats {display:flex;gap:10px;flex-wrap:wrap;margin:6px 0 14px 0;}
        .stat-card {background:#fff;border-radius:12px;box-shadow:0 2px 12px #2d865910;padding:12px 14px;min-width:150px;flex:1 1 160px;}
        .stat-label {font-size:.95em;color:#6b7672;font-weight:700;}
        .stat-value {font-size:1.4em;font-weight:900;color:#2d8659;}
        @media (max-width:720px){.prod-list{flex-direction:column}.prod-card{min-width:96vw;max-width:98vw;}}
        
        /* Modal de adicionar produto */
        .vc-modal-overlay {display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:10000;align-items:center;justify-content:center;padding:20px;}
        .vc-modal-overlay.active {display:flex;}
        .vc-modal-content {background:#fff;border-radius:16px;max-width:600px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,0.3);}
        .vc-modal-header {padding:20px 24px;border-bottom:2px solid #eaf8f1;display:flex;justify-content:space-between;align-items:center;}
        .vc-modal-title {font-size:1.3em;font-weight:900;color:#2d8659;margin:0;}
        .vc-modal-close {background:none;border:none;font-size:1.8em;color:#6b7672;cursor:pointer;padding:0;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background 0.2s;}
        .vc-modal-close:hover {background:#f0f9f5;color:#2d8659;}
        .vc-modal-body {padding:24px;}
        .vc-form-group {margin-bottom:20px;}
        .vc-form-label {display:block;font-weight:700;color:#232a2c;margin-bottom:8px;font-size:0.95em;}
        .vc-form-input, .vc-form-textarea, .vc-form-select {width:100%;padding:12px;border:2px solid #eaf8f1;border-radius:8px;font-size:1em;font-family:inherit;transition:border-color 0.2s;}
        .vc-form-input:focus, .vc-form-textarea:focus, .vc-form-select:focus {outline:none;border-color:#2d8659;}
        .vc-form-textarea {min-height:100px;resize:vertical;}
        .vc-form-row {display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .vc-form-checkbox-group {display:flex;align-items:center;gap:8px;}
        .vc-form-checkbox {width:20px;height:20px;cursor:pointer;}
        .vc-image-upload {border:2px dashed #cbdad1;border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:border-color 0.2s;}
        .vc-image-upload:hover {border-color:#2d8659;}
        .vc-image-preview {max-width:100%;max-height:200px;border-radius:8px;margin-top:12px;display:none;}
        .vc-image-preview.show {display:block;}
        .vc-modal-footer {padding:20px 24px;border-top:2px solid #eaf8f1;display:flex;gap:12px;justify-content:flex-end;}
        .vc-btn-primary {background:#2d8659;color:#fff;border:none;padding:12px 24px;border-radius:8px;font-weight:700;cursor:pointer;font-size:1em;transition:background 0.2s;}
        .vc-btn-primary:hover {background:#23863b;}
        .vc-btn-primary:disabled {background:#cbdad1;cursor:not-allowed;}
        .vc-btn-secondary {background:#fff;color:#6b7672;border:2px solid #eaf8f1;padding:12px 24px;border-radius:8px;font-weight:700;cursor:pointer;font-size:1em;}
        .vc-btn-secondary:hover {border-color:#cbdad1;}
        
        /* Modal de Adicionais - Tabs */
        .vc-addons-tabs {display:flex;gap:10px;margin-bottom:20px;border-bottom:2px solid #e0e0e0;}
        .vc-addons-tab-btn {background:none;border:none;padding:12px 20px;font-weight:700;color:#6b7672;cursor:pointer;border-bottom:3px solid transparent;transition:all 0.2s;}
        .vc-addons-tab-btn:hover {color:#2d8659;}
        .vc-addons-tab-btn.active {color:#2d8659;border-bottom-color:#2d8659;}
        .vc-addons-tab-content {min-height:200px;}
        .vc-addon-group-card {margin-bottom:15px;}
        @media (max-width:720px){.vc-form-row{grid-template-columns:1fr;}.vc-modal-content{max-width:95vw;}.vc-addons-tabs{flex-direction:column;}.vc-addons-tab-btn{border-bottom:none;border-left:3px solid transparent;padding-left:15px;}.vc-addons-tab-btn.active{border-left-color:#2d8659;border-bottom-color:transparent;}}
    </style>

    <?php
    // Buscar categorias disponíveis para o select
    $menu_categories = get_terms( [
        'taxonomy'   => 'vc_menu_category',
        'hide_empty' => false,
    ] );
    ?>

    <div class="menu-top">
        <div class="menu-title"><?php echo esc_html__('Gestão de Cardápio', 'vemcomer'); ?></div>
        <button class="menu-btn" onclick="openAddProductModal()">+ <?php echo esc_html__('Adicionar Produto', 'vemcomer'); ?></button>
        <button class="menu-btn secondary" onclick="openAddCategoryModal()">+ <?php echo esc_html__('Categoria', 'vemcomer'); ?></button>
    </div>

    <?php if (! $restaurant) : ?>
        <div class="empty-state"><?php echo esc_html__('Faça login como lojista para gerenciar o cardápio.', 'vemcomer'); ?></div>
    <?php else : ?>
        <!-- Estatísticas sempre visíveis quando há restaurante -->
        <div class="menu-stats" aria-label="<?php echo esc_attr__('Resumo do cardápio', 'vemcomer'); ?>">
            <div class="stat-card">
                <div class="stat-label"><?php echo esc_html__('Itens ativos', 'vemcomer'); ?></div>
                <div class="stat-value" data-stat="active"><?php echo esc_html($stats['active']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><?php echo esc_html__('Pausados', 'vemcomer'); ?></div>
                <div class="stat-value" data-stat="paused"><?php echo esc_html($stats['paused']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><?php echo esc_html__('Sem foto', 'vemcomer'); ?></div>
                <div class="stat-value" data-stat="no-thumb"><?php echo esc_html($stats['no_thumb']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><?php echo esc_html__('Categorias', 'vemcomer'); ?></div>
                <div class="stat-value" data-stat="categories"><?php echo esc_html($stats['categories']); ?></div>
            </div>
        </div>
        
        <?php if (empty($categories_for_view)) : ?>
            <div class="empty-state" style="margin-top:20px;"><?php echo esc_html__('Nenhum item cadastrado ainda. Adicione produtos para começar.', 'vemcomer'); ?></div>
        <?php else : ?>
        
        <!-- Debug: Total de categorias para visualização: <?php echo count($categories_for_view); ?> -->
        
        <div class="tabs-cat">
            <?php foreach ($categories_for_view as $index => $cat) : ?>
                <button class="cat-tab-btn<?php echo 0 === $index ? ' active' : ''; ?>" data-target="cat-index-<?php echo $index; ?>">
                    <?php echo esc_html($cat['name']); ?> 
                    <span style="font-size:0.8em;opacity:0.7;">(<?php echo count($cat['items'] ?? []); ?>)</span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($categories_for_view as $index => $cat) : ?>
            <?php
            $cat_items = [];
            if (array_key_exists('items', $cat) && is_array($cat['items'])) {
                $cat_items = $cat['items'];
            }
            ?>
            <!-- Debug: Renderizando conteúdo para índice <?php echo $index; ?> -->
            <div class="tab-content" id="cat-index-<?php echo $index; ?>" style="<?php echo 0 === $index ? 'display:block;' : 'display:none;'; ?>">
                <div class="prod-list">
                    <?php if (empty($cat_items)) : ?>
                        <!-- Debug: Categoria vazia -->
                        <div class="empty-state" style="width:100%;"><?php echo esc_html__('Nenhum item nesta categoria ainda.', 'vemcomer'); ?></div>
                    <?php else : ?>
                        <!-- Debug: Loop de itens iniciando -->
                        <?php foreach ($cat_items as $item) : ?>
                            <!-- Debug: Renderizando item ID <?php echo $item['id'] ?? 'N/A'; ?> -->
                            <?php
                            // Garantir que os dados existem e não estão vazios
                            $item_id = isset($item['id']) && !empty($item['id']) ? (int) $item['id'] : 0;
                            $item_title = isset($item['title']) && !empty($item['title']) ? (string) $item['title'] : '';
                            
                            // Fallback para título se vazio
                            if (empty($item_title) && $item_id > 0) {
                                $post_obj = get_post($item_id);
                                if ($post_obj) {
                                    $item_title = $post_obj->post_title;
                                } else {
                                    $item_title = __('Produto sem nome', 'vemcomer');
                                }
                            }
                            
                            // ... resto das variáveis ...
                            $item_thumb = isset($item['thumb']) ? (string) $item['thumb'] : '';
                            $item_status = isset($item['status']) ? (string) $item['status'] : 'pausado';
                            $item_price = isset($item['price']) ? (string) $item['price'] : '';
                            $item_desc = isset($item['description']) ? (string) $item['description'] : '';
                            $item_prep_time = isset($item['prep_time']) ? (int) $item['prep_time'] : 0;
                            ?>
                            <div class="prod-card" data-item-id="<?php echo esc_attr($item_id); ?>" data-available="<?php echo esc_attr('ativo' === $item_status ? '1' : '0'); ?>">
                                <div style="display:flex;align-items:center;">
                                    <?php if (!empty($item_thumb)) : ?>
                                        <img src="<?php echo esc_url($item_thumb); ?>" class="prod-img" alt="<?php echo esc_attr($item_title); ?>" />
                                    <?php else : ?>
                                        <img src="" class="prod-img" style="background:#ffe7e7;" alt="<?php echo esc_attr__('Sem foto', 'vemcomer'); ?>" />
                                    <?php endif; ?>
                                    <div class="prod-info">
                                        <div class="prod-nome">
                                            <?php echo esc_html($item_title); ?>
                                            <?php if ('ativo' === $item_status) : ?>
                                                <span class="prod-ativo"><?php echo esc_html__('Ativo', 'vemcomer'); ?></span>
                                            <?php else : ?>
                                                <span class="prod-pausado"><?php echo esc_html__('Pausado', 'vemcomer'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="prod-desc"><?php echo esc_html($item_desc); ?></div>
                                        <div class="prod-preco"><?php echo esc_html($item_price); ?></div>
                                    </div>
                                </div>
                                <div class="prod-actions">
                                    <button class="pedit-btn js-edit-product" data-item-id="<?php echo esc_attr($item_id); ?>" data-item-title="<?php echo esc_attr($item_title); ?>" data-item-desc="<?php echo esc_attr($item_desc); ?>" data-item-price="<?php echo esc_attr(str_replace(['R$', ' '], '', $item_price)); ?>" data-item-prep-time="<?php echo esc_attr($item_prep_time); ?>" data-item-thumb="<?php echo esc_attr($item_thumb); ?>" data-item-status="<?php echo esc_attr($item_status); ?>" data-item-category="<?php echo esc_attr($cat['id']); ?>"><?php echo esc_html__('Editar', 'vemcomer'); ?></button>
                                    <button class="pedit-btn pause js-toggle-availability" data-is-active="<?php echo esc_attr('ativo' === $item_status ? '1' : '0'); ?>">
                                        <?php echo 'ativo' === $item_status ? esc_html__('Pausar', 'vemcomer') : esc_html__('Ativar', 'vemcomer'); ?>
                                    </button>
                                    <button class="pedit-btn del js-delete-item"><?php echo esc_html__('Deletar', 'vemcomer'); ?></button>
                                </div>
                                <div class="modif-box">
                                    <div class="modif-title"><?php echo esc_html__('Adicionais:', 'vemcomer'); ?></div>
                                    <div class="modif-list">
                                        <?php if (isset($item['modifiers']) && ! empty($item['modifiers']) && is_array($item['modifiers'])) : ?>
                                            <?php foreach ($item['modifiers'] as $mod_title) : ?>
                                                <div class="modif-badge"><?php echo esc_html($mod_title); ?></div>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <div class="modif-badge" style="background:#fff;color:#6b7672;border:1px dashed #cbdad1;"><?php echo esc_html__('Nenhum adicional', 'vemcomer'); ?></div>
                                        <?php endif; ?>
                                        <div class="modif-edit" onclick="openAddonsModal(<?php echo esc_attr($item_id); ?>)">+ <?php echo esc_html__('Adicionais', 'vemcomer'); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal de Adicionar Categoria -->
<div id="vcAddCategoryModal" class="vc-modal-overlay">
    <div class="vc-modal-content">
        <div class="vc-modal-header">
            <h2 class="vc-modal-title"><?php echo esc_html__('Adicionar Nova Categoria', 'vemcomer'); ?></h2>
            <button class="vc-modal-close" onclick="closeAddCategoryModal()" aria-label="<?php echo esc_attr__('Fechar', 'vemcomer'); ?>">×</button>
        </div>
        <form id="vcAddCategoryForm" onsubmit="saveNewCategory(event)">
            <div class="vc-modal-body">
                <div class="vc-form-group">
                    <label class="vc-form-label"><?php echo esc_html__('Nome da Categoria *', 'vemcomer'); ?></label>
                    <input type="text" id="vcCategoryName" class="vc-form-input" required placeholder="<?php echo esc_attr__('Ex: Entradas, Pratos Principais, Bebidas', 'vemcomer'); ?>" />
                </div>
                
                <div class="vc-form-group">
                    <label class="vc-form-label"><?php echo esc_html__('Ordem de Exibição', 'vemcomer'); ?></label>
                    <input type="number" id="vcCategoryOrder" class="vc-form-input" min="0" value="0" placeholder="0" />
                    <p style="font-size:0.85em;color:#6b7672;margin-top:4px;"><?php echo esc_html__('Menor número aparece primeiro', 'vemcomer'); ?></p>
                </div>
                
                <div class="vc-form-group">
                    <label class="vc-form-label"><?php echo esc_html__('Imagem da Categoria (opcional)', 'vemcomer'); ?></label>
                    <div class="vc-image-upload" onclick="document.getElementById('vcCategoryImageInput').click()">
                        <input type="file" id="vcCategoryImageInput" accept="image/*" style="display:none;" onchange="handleCategoryImageUpload(event)" />
                        <div id="vcCategoryImageUploadText"><?php echo esc_html__('Clique para adicionar imagem', 'vemcomer'); ?></div>
                        <img id="vcCategoryImagePreview" class="vc-image-preview" alt="" />
                    </div>
                </div>
            </div>
            <div class="vc-modal-footer">
                <button type="button" class="vc-btn-secondary" onclick="closeAddCategoryModal()"><?php echo esc_html__('Cancelar', 'vemcomer'); ?></button>
                <button type="submit" class="vc-btn-primary" id="vcSaveCategoryBtn"><?php echo esc_html__('Salvar Categoria', 'vemcomer'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Adicionar Produto -->
<div id="vcAddProductModal" class="vc-modal-overlay">
    <div class="vc-modal-content">
        <div class="vc-modal-header">
            <h2 class="vc-modal-title" id="vcModalTitle"><?php echo esc_html__('Adicionar Novo Produto', 'vemcomer'); ?></h2>
            <button class="vc-modal-close" onclick="closeAddProductModal()" aria-label="<?php echo esc_attr__('Fechar', 'vemcomer'); ?>">×</button>
        </div>
        <form id="vcAddProductForm" onsubmit="saveNewProduct(event)">
            <input type="hidden" id="vcProductId" value="" />
            <div class="vc-modal-body">
                <div class="vc-form-group">
                    <label class="vc-form-label"><?php echo esc_html__('Nome do Produto *', 'vemcomer'); ?></label>
                    <input type="text" id="vcProductTitle" class="vc-form-input" required placeholder="<?php echo esc_attr__('Ex: Hambúrguer Artesanal', 'vemcomer'); ?>" />
                </div>
                
                <div class="vc-form-group">
                    <label class="vc-form-label"><?php echo esc_html__('Descrição', 'vemcomer'); ?></label>
                    <textarea id="vcProductDescription" class="vc-form-textarea" placeholder="<?php echo esc_attr__('Descreva o produto...', 'vemcomer'); ?>"></textarea>
                </div>
                
                <div class="vc-form-row">
                    <div class="vc-form-group">
                        <label class="vc-form-label"><?php echo esc_html__('Preço (R$)', 'vemcomer'); ?></label>
                        <input type="number" id="vcProductPrice" class="vc-form-input" step="0.01" min="0" placeholder="0.00" />
                    </div>
                    <div class="vc-form-group">
                        <label class="vc-form-label"><?php echo esc_html__('Tempo de Preparo (min)', 'vemcomer'); ?></label>
                        <input type="number" id="vcProductPrepTime" class="vc-form-input" min="0" placeholder="0" />
                    </div>
                </div>
                
                <div class="vc-form-group">
                    <label class="vc-form-label"><?php echo esc_html__('Categoria', 'vemcomer'); ?></label>
                    <select id="vcProductCategory" class="vc-form-select">
                        <option value=""><?php echo esc_html__('Selecione uma categoria', 'vemcomer'); ?></option>
                        <?php if ( ! is_wp_error( $menu_categories ) && $menu_categories ) : ?>
                            <?php foreach ( $menu_categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="vc-form-group">
                    <label class="vc-form-label"><?php echo esc_html__('Imagem do Produto', 'vemcomer'); ?></label>
                    <div class="vc-image-upload" onclick="document.getElementById('vcProductImageInput').click()">
                        <input type="file" id="vcProductImageInput" accept="image/*" style="display:none;" onchange="handleImageUpload(event)" />
                        <div id="vcImageUploadText"><?php echo esc_html__('Clique para adicionar imagem', 'vemcomer'); ?></div>
                        <img id="vcImagePreview" class="vc-image-preview" alt="" />
                    </div>
                </div>
                
                <div class="vc-form-group">
                    <div class="vc-form-checkbox-group">
                        <input type="checkbox" id="vcProductAvailable" class="vc-form-checkbox" checked />
                        <label for="vcProductAvailable" class="vc-form-label" style="margin:0;cursor:pointer;"><?php echo esc_html__('Produto disponível', 'vemcomer'); ?></label>
                    </div>
                </div>
                
                <div class="vc-form-group">
                    <div class="vc-form-checkbox-group">
                        <input type="checkbox" id="vcProductFeatured" class="vc-form-checkbox" />
                        <label for="vcProductFeatured" class="vc-form-label" style="margin:0;cursor:pointer;"><?php echo esc_html__('⭐ Prato do Dia (Destaque)', 'vemcomer'); ?></label>
                    </div>
                </div>
            </div>
            <div class="vc-modal-footer">
                <button type="button" class="vc-btn-secondary" onclick="closeAddProductModal()"><?php echo esc_html__('Cancelar', 'vemcomer'); ?></button>
                <button type="submit" class="vc-btn-primary" id="vcSaveProductBtn"><?php echo esc_html__('Salvar Produto', 'vemcomer'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Gerenciar Adicionais -->
<div id="vcAddonsModal" class="vc-modal-overlay" style="display:none;">
    <div class="vc-modal-content" style="max-width:800px;">
        <div class="vc-modal-header">
            <h2 class="vc-modal-title"><?php echo esc_html__('Gerenciar Adicionais', 'vemcomer'); ?></h2>
            <button class="vc-modal-close" onclick="closeAddonsModal()" aria-label="<?php echo esc_attr__('Fechar', 'vemcomer'); ?>">×</button>
        </div>
        <div class="vc-modal-body">
            <input type="hidden" id="vcAddonsProductId" value="" />
            
            <!-- Tabs -->
            <div class="vc-addons-tabs" style="display:flex;gap:10px;margin-bottom:20px;border-bottom:2px solid #e0e0e0;">
                <button class="vc-addons-tab-btn active" data-tab="recommended" onclick="switchAddonsTab('recommended')">
                    <?php echo esc_html__('Grupos Recomendados', 'vemcomer'); ?>
                </button>
                <button class="vc-addons-tab-btn" data-tab="custom" onclick="switchAddonsTab('custom')">
                    <?php echo esc_html__('Criar Grupo Personalizado', 'vemcomer'); ?>
                </button>
                <button class="vc-addons-tab-btn" data-tab="current" onclick="switchAddonsTab('current')">
                    <?php echo esc_html__('Adicionais Atuais', 'vemcomer'); ?>
                </button>
            </div>

            <!-- Tab: Grupos Recomendados -->
            <div id="vcAddonsTabRecommended" class="vc-addons-tab-content">
                <p style="color:#666;margin-bottom:15px;"><?php echo esc_html__('Grupos sugeridos baseados nas categorias do seu restaurante:', 'vemcomer'); ?></p>
                <div id="vcRecommendedGroups" style="display:grid;gap:15px;">
                    <div style="text-align:center;padding:40px;color:#999;">
                        <p><?php echo esc_html__('Carregando grupos recomendados...', 'vemcomer'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tab: Criar Grupo Personalizado -->
            <div id="vcAddonsTabCustom" class="vc-addons-tab-content" style="display:none;">
                <form id="vcCustomAddonForm" onsubmit="saveCustomAddonGroup(event)">
                    <div class="vc-form-group">
                        <label class="vc-form-label"><?php echo esc_html__('Nome do Grupo', 'vemcomer'); ?></label>
                        <input type="text" id="vcCustomGroupName" class="vc-form-input" required placeholder="<?php echo esc_attr__('Ex: Molhos extras', 'vemcomer'); ?>" />
                    </div>
                    <div class="vc-form-group">
                        <label class="vc-form-label"><?php echo esc_html__('Tipo de Seleção', 'vemcomer'); ?></label>
                        <select id="vcCustomSelectionType" class="vc-form-select">
                            <option value="single"><?php echo esc_html__('Seleção única', 'vemcomer'); ?></option>
                            <option value="multiple" selected><?php echo esc_html__('Múltipla seleção', 'vemcomer'); ?></option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                        <div class="vc-form-group">
                            <label class="vc-form-label"><?php echo esc_html__('Seleção Mínima', 'vemcomer'); ?></label>
                            <input type="number" id="vcCustomMinSelect" class="vc-form-input" min="0" value="0" />
                        </div>
                        <div class="vc-form-group">
                            <label class="vc-form-label"><?php echo esc_html__('Seleção Máxima', 'vemcomer'); ?></label>
                            <input type="number" id="vcCustomMaxSelect" class="vc-form-input" min="0" value="0" />
                            <small style="color:#666;"><?php echo esc_html__('0 = ilimitado', 'vemcomer'); ?></small>
                        </div>
                    </div>
                    <div class="vc-form-group">
                        <div class="vc-form-checkbox-group">
                            <input type="checkbox" id="vcCustomIsRequired" class="vc-form-checkbox" />
                            <label for="vcCustomIsRequired" class="vc-form-label" style="margin:0;cursor:pointer;"><?php echo esc_html__('Obrigatório (cliente deve selecionar pelo menos uma opção)', 'vemcomer'); ?></label>
                        </div>
                    </div>
                    <div id="vcCustomAddonItems" style="margin-top:20px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <label class="vc-form-label"><?php echo esc_html__('Itens do Grupo', 'vemcomer'); ?></label>
                            <button type="button" class="vc-btn-secondary" onclick="addCustomAddonItem()" style="padding:5px 15px;font-size:14px;">+ <?php echo esc_html__('Adicionar Item', 'vemcomer'); ?></button>
                        </div>
                        <div id="vcCustomItemsList"></div>
                    </div>
                    <div class="vc-modal-footer" style="margin-top:20px;padding-top:20px;border-top:1px solid #e0e0e0;">
                        <button type="button" class="vc-btn-secondary" onclick="closeAddonsModal()"><?php echo esc_html__('Cancelar', 'vemcomer'); ?></button>
                        <button type="submit" class="vc-btn-primary"><?php echo esc_html__('Salvar Grupo', 'vemcomer'); ?></button>
                    </div>
                </form>
            </div>

            <!-- Tab: Adicionais Atuais -->
            <div id="vcAddonsTabCurrent" class="vc-addons-tab-content" style="display:none;">
                <div id="vcCurrentAddons">
                    <p style="color:#666;"><?php echo esc_html__('Carregando adicionais vinculados a este produto...', 'vemcomer'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.cat-tab-btn');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-target');
                // Debug JS
                // console.log('Clicou na aba. Alvo:', target);
                
                document.querySelectorAll('.tab-content').forEach(tab => {
                    if (tab.id === target) {
                        tab.style.display = 'block';
                        // console.log('Mostrando:', tab.id);
                    } else {
                        tab.style.display = 'none';
                    }
                });
                tabButtons.forEach(b => b.classList.toggle('active', b === btn));
            });
        });

        const restBase = '<?php echo esc_js(rest_url('vemcomer/v1/menu-items')); ?>';
        const restNonce = '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>';

        const updateStats = () => {
            const statNodes = {
                active: document.querySelector('[data-stat="active"]'),
                paused: document.querySelector('[data-stat="paused"]'),
                noThumb: document.querySelector('[data-stat="no-thumb"]'),
                categories: document.querySelector('[data-stat="categories"]'),
            };

            let active = 0; let paused = 0; let noThumb = 0;
            document.querySelectorAll('.prod-card').forEach(card => {
                const available = card.getAttribute('data-available') === '1';
                if (available) { active++; } else { paused++; }
                const img = card.querySelector('.prod-img');
                if (img && (!img.getAttribute('src') || img.getAttribute('src') === '')) { noThumb++; }
            });

            // Contar categorias únicas (pelas tabs)
            const categoryCount = document.querySelectorAll('.cat-tab-btn').length;

            if (statNodes.active) statNodes.active.textContent = active;
            if (statNodes.paused) statNodes.paused.textContent = paused;
            if (statNodes.noThumb) statNodes.noThumb.textContent = noThumb;
            if (statNodes.categories) statNodes.categories.textContent = categoryCount;
        };

        const toggleButtons = document.querySelectorAll('.js-toggle-availability');
        toggleButtons.forEach(btn => {
            btn.addEventListener('click', async (event) => {
                const card = event.target.closest('.prod-card');
                if (!card) return;
                const itemId = card.getAttribute('data-item-id');
                if (!itemId) return;

                btn.disabled = true;
                try {
                    const response = await fetch(`${restBase}/${itemId}/toggle-availability`, {
                        method: 'POST',
                        headers: {
                            'X-WP-Nonce': restNonce,
                            'Content-Type': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ message: 'Erro desconhecido' }));
                        alert(errorData?.message || `Erro ${response.status}: ${response.statusText}`);
                        return;
                    }

                    const data = await response.json();
                    if (!data.success) {
                        alert(data?.message || '<?php echo esc_js(__('Não foi possível atualizar o status.', 'vemcomer')); ?>');
                        return;
                    }

                    const statusSpan = card.querySelector('.prod-ativo, .prod-pausado');
                    const statusContainer = card.querySelector('.prod-nome');
                    const toggleBtn = card.querySelector('.js-toggle-availability');
                    
                    if (data.available) {
                        card.setAttribute('data-available', '1');
                        if (toggleBtn) {
                            toggleBtn.setAttribute('data-is-active', '1');
                            toggleBtn.textContent = '<?php echo esc_js(__('Pausar', 'vemcomer')); ?>';
                        }
                        if (statusSpan) {
                            statusSpan.className = 'prod-ativo';
                            statusSpan.textContent = '<?php echo esc_js(__('Ativo', 'vemcomer')); ?>';
                        } else if (statusContainer) {
                            const span = document.createElement('span');
                            span.className = 'prod-ativo';
                            span.textContent = '<?php echo esc_js(__('Ativo', 'vemcomer')); ?>';
                            statusContainer.appendChild(span);
                        }
                    } else {
                        card.setAttribute('data-available', '0');
                        if (toggleBtn) {
                            toggleBtn.setAttribute('data-is-active', '0');
                            toggleBtn.textContent = '<?php echo esc_js(__('Ativar', 'vemcomer')); ?>';
                        }
                        if (statusSpan) {
                            statusSpan.className = 'prod-pausado';
                            statusSpan.textContent = '<?php echo esc_js(__('Pausado', 'vemcomer')); ?>';
                        } else if (statusContainer) {
                            const span = document.createElement('span');
                            span.className = 'prod-pausado';
                            span.textContent = '<?php echo esc_js(__('Pausado', 'vemcomer')); ?>';
                            statusContainer.appendChild(span);
                        }
                    }
                    // O CSS já aplica o estilo cinza automaticamente via [data-available="0"]

                    updateStats();
                } catch (e) {
                    alert('<?php echo esc_js(__('Erro ao conectar com o servidor.', 'vemcomer')); ?>');
                } finally {
                    btn.disabled = false;
                }
            });
        });

        const deleteButtons = document.querySelectorAll('.js-delete-item');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', async (event) => {
                const card = event.target.closest('.prod-card');
                if (!card) return;
                const itemId = card.getAttribute('data-item-id');
                if (!itemId) return;

                if (!confirm('<?php echo esc_js(__('Deseja mover este item para a lixeira?', 'vemcomer')); ?>')) {
                    return;
                }

                btn.disabled = true;
                try {
                    const response = await fetch(`${restBase}/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-WP-Nonce': restNonce,
                            'Content-Type': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ message: 'Erro desconhecido' }));
                        alert(errorData?.message || `Erro ${response.status}: ${response.statusText}`);
                        return;
                    }

                    const data = await response.json();
                    if (!data.success) {
                        alert(data?.message || '<?php echo esc_js(__('Não foi possível deletar este item.', 'vemcomer')); ?>');
                        return;
                    }

                    card.remove();
                    updateStats();
                } catch (e) {
                    alert('<?php echo esc_js(__('Erro ao conectar com o servidor.', 'vemcomer')); ?>');
                } finally {
                    btn.disabled = false;
                }
            });
        });

        // Modal de adicionar produto
        let productImageData = null;

        window.openAddProductModal = function() {
            document.getElementById('vcAddProductModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        window.closeAddProductModal = function() {
            document.getElementById('vcAddProductModal').classList.remove('active');
            document.body.style.overflow = '';
            // Resetar formulário
            document.getElementById('vcAddProductForm').reset();
            document.getElementById('vcProductId').value = '';
            document.getElementById('vcModalTitle').textContent = '<?php echo esc_js(__('Adicionar Novo Produto', 'vemcomer')); ?>';
            document.getElementById('vcSaveProductBtn').textContent = '<?php echo esc_js(__('Salvar Produto', 'vemcomer')); ?>';
            productImageData = null;
            document.getElementById('vcImagePreview').classList.remove('show');
            document.getElementById('vcImageUploadText').style.display = 'block';
        };

        // Abrir modal de edição com dados preenchidos
        const editButtons = document.querySelectorAll('.js-edit-product');
        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                const itemTitle = this.getAttribute('data-item-title');
                const itemDesc = this.getAttribute('data-item-desc');
                const itemPrice = this.getAttribute('data-item-price');
                const itemPrepTime = this.getAttribute('data-item-prep-time');
                const itemThumb = this.getAttribute('data-item-thumb');
                const itemStatus = this.getAttribute('data-item-status');
                const itemCategory = this.getAttribute('data-item-category');

                // Preencher campos do formulário
                document.getElementById('vcProductId').value = itemId;
                document.getElementById('vcProductTitle').value = itemTitle || '';
                document.getElementById('vcProductDescription').value = itemDesc || '';
                document.getElementById('vcProductPrice').value = itemPrice || '';
                document.getElementById('vcProductPrepTime').value = itemPrepTime || 0;
                document.getElementById('vcProductCategory').value = itemCategory || '';
                document.getElementById('vcProductAvailable').checked = itemStatus === 'ativo';
                
                // Preencher imagem se existir
                if (itemThumb) {
                    productImageData = itemThumb;
                    const preview = document.getElementById('vcImagePreview');
                    preview.src = itemThumb;
                    preview.classList.add('show');
                    document.getElementById('vcImageUploadText').style.display = 'none';
                } else {
                    productImageData = null;
                    document.getElementById('vcImagePreview').classList.remove('show');
                    document.getElementById('vcImageUploadText').style.display = 'block';
                }

                // Atualizar título do modal e botão
                document.getElementById('vcModalTitle').textContent = '<?php echo esc_js(__('Editar Produto', 'vemcomer')); ?>';
                document.getElementById('vcSaveProductBtn').textContent = '<?php echo esc_js(__('Atualizar Produto', 'vemcomer')); ?>';

                // Abrir modal
                document.getElementById('vcAddProductModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });

        // Fechar modal ao clicar fora
        document.getElementById('vcAddProductModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddProductModal();
            }
        });

        window.handleImageUpload = function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                productImageData = e.target.result;
                const preview = document.getElementById('vcImagePreview');
                preview.src = productImageData;
                preview.classList.add('show');
                document.getElementById('vcImageUploadText').style.display = 'none';
            };
            reader.readAsDataURL(file);
        };

        window.saveNewProduct = async function(event) {
            event.preventDefault();
            
            const btn = document.getElementById('vcSaveProductBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '<?php echo esc_js(__('Salvando...', 'vemcomer')); ?>';

            const productId = document.getElementById('vcProductId').value;
            const isEdit = productId && productId !== '';

            const payload = {
                title: document.getElementById('vcProductTitle').value.trim(),
                description: document.getElementById('vcProductDescription').value.trim(),
                price: document.getElementById('vcProductPrice').value || '',
                prep_time: document.getElementById('vcProductPrepTime').value || 0,
                category_id: document.getElementById('vcProductCategory').value || null,
                is_available: document.getElementById('vcProductAvailable').checked,
                is_featured: document.getElementById('vcProductFeatured').checked,
            };

            if (productImageData) {
                payload.image = productImageData;
            }

            try {
                const url = isEdit ? `${restBase}/${productId}` : restBase;
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    alert(data?.message || (isEdit ? '<?php echo esc_js(__('Não foi possível atualizar o produto.', 'vemcomer')); ?>' : '<?php echo esc_js(__('Não foi possível criar o produto.', 'vemcomer')); ?>'));
                    return;
                }

                alert(isEdit ? '<?php echo esc_js(__('Produto atualizado com sucesso!', 'vemcomer')); ?>' : '<?php echo esc_js(__('Produto criado com sucesso!', 'vemcomer')); ?>');
                closeAddProductModal();
                // Recarregar a página para mostrar as mudanças
                window.location.reload();
            } catch (e) {
                console.error(e);
                alert('<?php echo esc_js(__('Erro ao conectar com o servidor.', 'vemcomer')); ?>');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        };

        // Modal de adicionar categoria
        let categoryImageData = null;

        window.openAddCategoryModal = function() {
            document.getElementById('vcAddCategoryModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        window.closeAddCategoryModal = function() {
            document.getElementById('vcAddCategoryModal').classList.remove('active');
            document.body.style.overflow = '';
            // Resetar formulário
            document.getElementById('vcAddCategoryForm').reset();
            categoryImageData = null;
            document.getElementById('vcCategoryImagePreview').classList.remove('show');
            document.getElementById('vcCategoryImageUploadText').style.display = 'block';
        };

        // Fechar modal ao clicar fora
        document.getElementById('vcAddCategoryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddCategoryModal();
            }
        });

        window.handleCategoryImageUpload = function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                categoryImageData = e.target.result;
                const preview = document.getElementById('vcCategoryImagePreview');
                preview.src = categoryImageData;
                preview.classList.add('show');
                document.getElementById('vcCategoryImageUploadText').style.display = 'none';
            };
            reader.readAsDataURL(file);
        };

        window.saveNewCategory = async function(event) {
            event.preventDefault();
            
            const btn = document.getElementById('vcSaveCategoryBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '<?php echo esc_js(__('Salvando...', 'vemcomer')); ?>';

            const payload = {
                name: document.getElementById('vcCategoryName').value.trim(),
                order: document.getElementById('vcCategoryOrder').value || 0,
            };

            if (categoryImageData) {
                payload.image = categoryImageData;
            }

            try {
                const response = await fetch('<?php echo esc_js(rest_url('vemcomer/v1/menu-categories')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    alert(data?.message || data?.data?.message || '<?php echo esc_js(__('Não foi possível criar a categoria.', 'vemcomer')); ?>');
                    return;
                }

                alert('<?php echo esc_js(__('Categoria criada com sucesso!', 'vemcomer')); ?>');
                closeAddCategoryModal();
                // Recarregar a página para mostrar a nova categoria
                window.location.reload();
            } catch (e) {
                console.error(e);
                alert('<?php echo esc_js(__('Erro ao conectar com o servidor.', 'vemcomer')); ?>');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        };

        // Modal de Adicionais
        const addonCatalogBase = '<?php echo esc_js(rest_url('vemcomer/v1/addon-catalog')); ?>';
        let currentProductIdForAddons = null;
        let customAddonItemCount = 0;

        window.openAddonsModal = function(productId) {
            currentProductIdForAddons = productId;
            document.getElementById('vcAddonsProductId').value = productId;
            document.getElementById('vcAddonsModal').style.display = 'flex';
            switchAddonsTab('recommended');
            loadRecommendedGroups();
        };

        window.closeAddonsModal = function() {
            document.getElementById('vcAddonsModal').style.display = 'none';
            currentProductIdForAddons = null;
            customAddonItemCount = 0;
            document.getElementById('vcCustomItemsList').innerHTML = '';
        };

        window.switchAddonsTab = function(tab) {
            // Esconder todos os tabs
            document.querySelectorAll('.vc-addons-tab-content').forEach(content => {
                content.style.display = 'none';
            });
            document.querySelectorAll('.vc-addons-tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Mostrar tab selecionado
            const tabId = 'vcAddonsTab' + tab.charAt(0).toUpperCase() + tab.slice(1);
            document.getElementById(tabId).style.display = 'block';
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');

            if (tab === 'recommended') {
                loadRecommendedGroups();
            } else if (tab === 'current') {
                loadCurrentAddons();
            }
        };

        async function loadRecommendedGroups() {
            const container = document.getElementById('vcRecommendedGroups');
            container.innerHTML = '<div style="text-align:center;padding:40px;color:#999;"><p><?php echo esc_js(__('Carregando grupos recomendados...', 'vemcomer')); ?></p></div>';

            try {
                const response = await fetch(`${addonCatalogBase}/recommended-groups`, {
                    headers: {
                        'X-WP-Nonce': restNonce,
                    },
                });

                const data = await response.json();
                if (!data.success || !data.groups || data.groups.length === 0) {
                    container.innerHTML = '<div style="text-align:center;padding:40px;color:#999;"><p><?php echo esc_js(__('Nenhum grupo recomendado encontrado para seu restaurante.', 'vemcomer')); ?></p></div>';
                    return;
                }

                container.innerHTML = '';
                
                // Buscar itens de cada grupo e exibir
                for (const group of data.groups) {
                    const groupCard = document.createElement('div');
                    groupCard.className = 'vc-addon-group-card';
                    groupCard.style.cssText = 'border:1px solid #e0e0e0;border-radius:8px;padding:15px;background:#fff;margin-bottom:15px;';
                    
                    // Renderizar estrutura básica primeiro
                    groupCard.innerHTML = `
                        <h3 style="margin:0 0 10px 0;font-size:16px;color:#2d8659;">${escapeHtml(group.name)}</h3>
                        ${group.description ? `<p style="color:#666;font-size:14px;margin:0 0 15px 0;">${escapeHtml(group.description)}</p>` : ''}
                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">
                            <span style="font-size:12px;color:#999;">Tipo: <strong>${group.selection_type === 'single' ? 'Seleção única' : 'Múltipla seleção'}</strong></span>
                            ${group.is_required ? '<span style="font-size:12px;color:#d32f2f;background:#ffe7e7;padding:2px 8px;border-radius:4px;">Obrigatório</span>' : ''}
                        </div>
                        <div class="vc-group-items-${group.id}" style="margin-top:10px;padding-top:10px;border-top:1px solid #e0e0e0;">
                            <p style="font-size:12px;color:#666;margin:0 0 8px 0;font-weight:700;">Itens do grupo:</p>
                            <div style="text-align:center;padding:10px;color:#999;">Carregando itens...</div>
                        </div>
                        <button class="vc-btn-copy-group vc-btn-primary" data-group-id="${group.id}" style="width:100%;padding:10px;margin-top:15px;">
                            <?php echo esc_js(__('Usar este grupo', 'vemcomer')); ?>
                        </button>
                    `;
                    container.appendChild(groupCard);
                    
                    // Buscar itens do grupo de forma assíncrona
                    (async function(groupId, groupCardElement) {
                        const itemsContainer = groupCardElement.querySelector(`.vc-group-items-${groupId}`);
                        if (!itemsContainer) return;
                        
                        try {
                            const itemsResponse = await fetch(`${addonCatalogBase}/groups/${groupId}/items`, {
                                headers: {
                                    'X-WP-Nonce': restNonce,
                                },
                            });
                            
                            if (!itemsResponse.ok) {
                                throw new Error(`HTTP ${itemsResponse.status}`);
                            }
                            
                            const itemsData = await itemsResponse.json();
                            console.log('Items data for group', groupId, ':', itemsData);
                            
                            if (itemsData.success && itemsData.items && Array.isArray(itemsData.items) && itemsData.items.length > 0) {
                                let itemsList = '<ul style="margin:0;padding-left:20px;font-size:13px;color:#666;list-style:disc;">';
                                itemsData.items.forEach(item => {
                                    itemsList += `<li style="margin-bottom:4px;">${escapeHtml(item.name)}</li>`;
                                });
                                itemsList += '</ul>';
                                itemsContainer.innerHTML = '<p style="font-size:12px;color:#666;margin:0 0 8px 0;font-weight:700;">Itens do grupo:</p>' + itemsList;
                            } else {
                                itemsContainer.innerHTML = '<p style="font-size:12px;color:#666;margin:0 0 8px 0;font-weight:700;">Itens do grupo:</p><ul style="margin:0;padding-left:20px;font-size:13px;color:#999;font-style:italic;"><li>Nenhum item disponível</li></ul>';
                            }
                        } catch (e) {
                            console.error('Erro ao carregar itens do grupo:', groupId, e);
                            itemsContainer.innerHTML = '<p style="font-size:12px;color:#666;margin:0 0 8px 0;font-weight:700;">Itens do grupo:</p><ul style="margin:0;padding-left:20px;font-size:13px;color:#d32f2f;"><li>Erro ao carregar itens. Verifique o console.</li></ul>';
                        }
                    })(group.id, groupCard);
                }
                
                // Adicionar event listeners aos botões
                container.querySelectorAll('.vc-btn-copy-group').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const groupId = parseInt(this.getAttribute('data-group-id'));
                        copyGroupToStore(groupId, e);
                    });
                });
            } catch (e) {
                console.error(e);
                container.innerHTML = '<div style="text-align:center;padding:40px;color:#d32f2f;"><p><?php echo esc_js(__('Erro ao carregar grupos recomendados.', 'vemcomer')); ?></p></div>';
            }
        }

        async function copyGroupToStore(groupId, evt) {
            const productId = currentProductIdForAddons;
            if (!productId) {
                alert('<?php echo esc_js(__('Produto não identificado.', 'vemcomer')); ?>');
                return;
            }

            if (!confirm('<?php echo esc_js(__('Deseja copiar este grupo para sua loja e vinculá-lo a este produto?', 'vemcomer')); ?>')) {
                return;
            }

            const btn = evt ? evt.target : (event ? event.target : null);
            if (btn) {
                const originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = '<?php echo esc_js(__('Copiando...', 'vemcomer')); ?>';
            }

            try {
                // 1. Copiar grupo do catálogo para a loja
                const copyResponse = await fetch(`${addonCatalogBase}/groups/${groupId}/copy-to-store`, {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': restNonce,
                    },
                });

                const copyData = await copyResponse.json();
                if (!copyData.success) {
                    alert(copyData?.message || '<?php echo esc_js(__('Erro ao copiar grupo.', 'vemcomer')); ?>');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                    return;
                }

                const storeGroupId = copyData.group_id;

                // 2. Vincular grupo ao produto
                const linkResponse = await fetch(`${addonCatalogBase}/link-group-to-product`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce,
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        group_id: storeGroupId,
                    }),
                });

                const linkData = await linkResponse.json();
                if (!linkData.success) {
                    console.error('Erro ao vincular grupo:', linkData);
                    alert(linkData?.message || '<?php echo esc_js(__('Grupo copiado, mas erro ao vincular ao produto.', 'vemcomer')); ?>');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                    return;
                }

                console.log('Grupo vinculado com sucesso:', linkData);
                alert('<?php echo esc_js(__('Grupo copiado e vinculado ao produto com sucesso!', 'vemcomer')); ?>');
                closeAddonsModal();
                window.location.reload(); // Recarregar para mostrar as mudanças
            } catch (e) {
                console.error('Erro ao copiar grupo:', e);
                alert('<?php echo esc_js(__('Erro ao conectar com o servidor.', 'vemcomer')); ?>');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            }
        }

        window.addCustomAddonItem = function() {
            customAddonItemCount++;
            const itemDiv = document.createElement('div');
            itemDiv.className = 'vc-custom-addon-item';
            itemDiv.style.cssText = 'display:grid;grid-template-columns:2fr 1fr auto;gap:10px;align-items:end;margin-bottom:10px;padding:15px;background:#f5f5f5;border-radius:6px;';
            itemDiv.innerHTML = `
                <div>
                    <label class="vc-form-label" style="font-size:12px;"><?php echo esc_js(__('Nome do Item', 'vemcomer')); ?></label>
                    <input type="text" class="vc-form-input vc-custom-item-name" placeholder="<?php echo esc_attr__('Ex: Queijo extra', 'vemcomer'); ?>" required />
                </div>
                <div>
                    <label class="vc-form-label" style="font-size:12px;"><?php echo esc_js(__('Preço (R$)', 'vemcomer')); ?></label>
                    <input type="number" class="vc-form-input vc-custom-item-price" step="0.01" min="0" value="0.00" required />
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="padding:8px 12px;background:#d32f2f;color:#fff;border:none;border-radius:4px;cursor:pointer;">×</button>
            `;
            document.getElementById('vcCustomItemsList').appendChild(itemDiv);
        };

        window.saveCustomAddonGroup = async function(e) {
            e.preventDefault();
            const productId = currentProductIdForAddons;
            if (!productId) {
                alert('<?php echo esc_js(__('Produto não identificado.', 'vemcomer')); ?>');
                return;
            }

            const groupName = document.getElementById('vcCustomGroupName').value.trim();
            if (!groupName) {
                alert('<?php echo esc_js(__('Digite o nome do grupo.', 'vemcomer')); ?>');
                return;
            }

            const items = [];
            document.querySelectorAll('.vc-custom-addon-item').forEach(itemDiv => {
                const name = itemDiv.querySelector('.vc-custom-item-name').value.trim();
                const price = parseFloat(itemDiv.querySelector('.vc-custom-item-price').value) || 0;
                if (name) {
                    items.push({ name, price });
                }
            });

            if (items.length === 0) {
                alert('<?php echo esc_js(__('Adicione pelo menos um item ao grupo.', 'vemcomer')); ?>');
                return;
            }

            // TODO: Implementar criação de grupo personalizado via REST API
            alert('<?php echo esc_js(__('Funcionalidade de criar grupo personalizado será implementada em breve.', 'vemcomer')); ?>');
        };

        async function loadCurrentAddons() {
            const container = document.getElementById('vcCurrentAddons');
            container.innerHTML = '<div style="text-align:center;padding:40px;color:#999;"><p><?php echo esc_js(__('Carregando adicionais...', 'vemcomer')); ?></p></div>';

            // TODO: Buscar adicionais vinculados ao produto via REST API
            setTimeout(() => {
                container.innerHTML = '<div style="text-align:center;padding:40px;color:#999;"><p><?php echo esc_js(__('Nenhum adicional vinculado a este produto ainda.', 'vemcomer')); ?></p></div>';
            }, 500);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
</script>

<?php
if (! $vc_marketplace_inline) {
    get_footer();
}
