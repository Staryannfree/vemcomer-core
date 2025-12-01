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
        .prod-card {background:#fff;border-radius:13px;box-shadow:0 1px 14px #2d865914;min-width:295px;max-width:309px;flex:1 1 315px;position:relative;padding:16px 12px 13px 16px;margin-bottom:9px;display:flex;flex-direction:column;align-items:flex-start;}
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
        @media (max-width:720px){.vc-form-row{grid-template-columns:1fr;}.vc-modal-content{max-width:95vw;}}
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
                <?php $safe_slug = sanitize_title($cat['slug'] ? $cat['slug'] : 'cat-' . $index); ?>
                <button class="cat-tab-btn<?php echo 0 === $index ? ' active' : ''; ?>" data-target="cat-<?php echo esc_attr($safe_slug); ?>">
                    <?php echo esc_html($cat['name']); ?> 
                    <span style="font-size:0.8em;opacity:0.7;">(<?php echo count($cat['items'] ?? []); ?>)</span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($categories_for_view as $index => $cat) : ?>
            <?php
            $safe_slug = sanitize_title($cat['slug'] ? $cat['slug'] : 'cat-' . $index);
            
            $cat_items = [];
            if (array_key_exists('items', $cat) && is_array($cat['items'])) {
                $cat_items = $cat['items'];
            }
            ?>
            <!-- Debug: Iniciando renderização da categoria "<?php echo esc_html($cat['name']); ?>" (Slug: <?php echo $safe_slug; ?>) - Itens: <?php echo count($cat_items); ?> -->
            
            <div class="tab-content" id="cat-<?php echo esc_attr($safe_slug); ?>" style="<?php echo 0 === $index ? '' : 'display:none;'; ?>">
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
                                    <?php if (isset($item['edit_url']) && $item['edit_url']) : ?>
                                        <button class="pedit-btn" onclick="window.location.href='<?php echo esc_url($item['edit_url']); ?>'"><?php echo esc_html__('Editar', 'vemcomer'); ?></button>
                                    <?php endif; ?>
                                    <button class="pedit-btn pause js-toggle-availability"><?php echo esc_html__('Pausar/Ativar', 'vemcomer'); ?></button>
                                    <button class="pedit-btn del js-delete-item"><?php echo esc_html__('Deletar', 'vemcomer'); ?></button>
                                </div>
                                <div class="modif-box">
                                    <div class="modif-title"><?php echo esc_html__('Modificadores:', 'vemcomer'); ?></div>
                                    <div class="modif-list">
                                        <?php if (isset($item['modifiers']) && ! empty($item['modifiers']) && is_array($item['modifiers'])) : ?>
                                            <?php foreach ($item['modifiers'] as $mod_title) : ?>
                                                <div class="modif-badge"><?php echo esc_html($mod_title); ?></div>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <div class="modif-badge" style="background:#fff;color:#6b7672;border:1px dashed #cbdad1;"><?php echo esc_html__('Nenhum modificador', 'vemcomer'); ?></div>
                                        <?php endif; ?>
                                        <div class="modif-edit" onclick="window.location.href='<?php echo esc_url(admin_url('edit.php?post_type=vc_product_modifier')); ?>'">+ <?php echo esc_html__('Modificadores', 'vemcomer'); ?></div>
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
            <h2 class="vc-modal-title"><?php echo esc_html__('Adicionar Novo Produto', 'vemcomer'); ?></h2>
            <button class="vc-modal-close" onclick="closeAddProductModal()" aria-label="<?php echo esc_attr__('Fechar', 'vemcomer'); ?>">×</button>
        </div>
        <form id="vcAddProductForm" onsubmit="saveNewProduct(event)">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.cat-tab-btn');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-target');
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.style.display = tab.id === target ? '' : 'none';
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
                        },
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        alert(data?.message || '<?php echo esc_js(__('Não foi possível atualizar o status.', 'vemcomer')); ?>');
                        return;
                    }

                    const statusSpan = card.querySelector('.prod-ativo, .prod-pausado');
                    const statusContainer = card.querySelector('.prod-nome');
                    if (data.available) {
                        card.setAttribute('data-available', '1');
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
                        },
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
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
            productImageData = null;
            document.getElementById('vcImagePreview').classList.remove('show');
            document.getElementById('vcImageUploadText').style.display = 'block';
        };

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
                const response = await fetch(restBase, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    alert(data?.message || '<?php echo esc_js(__('Não foi possível criar o produto.', 'vemcomer')); ?>');
                    return;
                }

                alert('<?php echo esc_js(__('Produto criado com sucesso!', 'vemcomer')); ?>');
                closeAddProductModal();
                // Recarregar a página para mostrar o novo produto
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
    });
</script>

<?php
if (! $vc_marketplace_inline) {
    get_footer();
}
