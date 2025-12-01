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
                'key'   => '_vc_menu_item_restaurant',
                'value' => $restaurant->ID,
            ],
        ],
    ]);

    if ($items_query->have_posts()) {
        foreach ($items_query->posts as $item) {
            $thumb      = get_the_post_thumbnail_url($item, 'medium');
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

                $stats['total']++;
                $stats['categories'] = count($categories);
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

// Ordena categorias por nome para consistência
usort($categories, function ($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

if (empty($categories) && $restaurant instanceof WP_Post) {
    $categories[] = $default_category;
}

$stats['categories'] = count($categories);
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
    </style>

    <div class="menu-top">
        <div class="menu-title"><?php echo esc_html__('Gestão de Cardápio', 'vemcomer'); ?></div>
        <button class="menu-btn" onclick="window.location.href='<?php echo esc_url(admin_url('post-new.php?post_type=vc_menu_item')); ?>'">+ <?php echo esc_html__('Adicionar Produto', 'vemcomer'); ?></button>
        <button class="menu-btn secondary" onclick="window.location.href='<?php echo esc_url(admin_url('edit-tags.php?taxonomy=vc_menu_category&post_type=vc_menu_item')); ?>'">+ <?php echo esc_html__('Categoria', 'vemcomer'); ?></button>
    </div>

    <?php if (! $restaurant) : ?>
        <div class="empty-state"><?php echo esc_html__('Faça login como lojista para gerenciar o cardápio.', 'vemcomer'); ?></div>
    <?php elseif (empty($categories)) : ?>
        <div class="empty-state"><?php echo esc_html__('Nenhum item cadastrado ainda. Adicione produtos para começar.', 'vemcomer'); ?></div>
    <?php else : ?>
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
        <div class="tabs-cat">
            <?php foreach ($categories as $index => $cat) : ?>
                <button class="cat-tab-btn<?php echo 0 === $index ? ' active' : ''; ?>" data-target="cat-<?php echo esc_attr($cat['slug']); ?>"><?php echo esc_html($cat['name']); ?></button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($categories as $index => $cat) : ?>
            <div class="tab-content" id="cat-<?php echo esc_attr($cat['slug']); ?>" style="<?php echo 0 === $index ? '' : 'display:none;'; ?>">
                <div class="prod-list">
                    <?php if (empty($cat['items'])) : ?>
                        <div class="empty-state" style="width:100%;"><?php echo esc_html__('Nenhum item nesta categoria ainda.', 'vemcomer'); ?></div>
                    <?php else : ?>
                        <?php foreach ($cat['items'] as $item) : ?>
                            <div class="prod-card" data-item-id="<?php echo esc_attr($item['id']); ?>" data-available="<?php echo esc_attr('ativo' === $item['status'] ? '1' : '0'); ?>">
                                <div style="display:flex;align-items:center;">
                                    <?php if ($item['thumb']) : ?>
                                        <img src="<?php echo esc_url($item['thumb']); ?>" class="prod-img" alt="" />
                                    <?php else : ?>
                                        <img src="" class="prod-img" style="background:#ffe7e7;" alt="<?php echo esc_attr__('Sem foto', 'vemcomer'); ?>" />
                                    <?php endif; ?>
                                    <div class="prod-info">
                                        <div class="prod-nome">
                                            <?php echo esc_html($item['title']); ?>
                                            <?php if ('ativo' === $item['status']) : ?>
                                                <span class="prod-ativo"><?php echo esc_html__('Ativo', 'vemcomer'); ?></span>
                                            <?php else : ?>
                                                <span class="prod-pausado"><?php echo esc_html__('Pausado', 'vemcomer'); ?></span>
                                                <?php if (! $item['thumb']) : ?>
                                                    <span class="prod-alerta" title="<?php echo esc_attr__('Sem foto', 'vemcomer'); ?>">⚠️</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="prod-desc"><?php echo esc_html($item['description']); ?></div>
                                        <div class="prod-preco"><?php echo esc_html($item['price']); ?></div>
                                    </div>
                                </div>
                                <div class="prod-actions">
                                    <?php if ($item['edit_url']) : ?>
                                        <button class="pedit-btn" onclick="window.location.href='<?php echo esc_url($item['edit_url']); ?>'"><?php echo esc_html__('Editar', 'vemcomer'); ?></button>
                                    <?php endif; ?>
                                    <button class="pedit-btn pause js-toggle-availability"><?php echo esc_html__('Pausar/Ativar', 'vemcomer'); ?></button>
                                    <button class="pedit-btn del js-delete-item"><?php echo esc_html__('Deletar', 'vemcomer'); ?></button>
                                </div>
                                <div class="modif-box">
                                    <div class="modif-title"><?php echo esc_html__('Modificadores:', 'vemcomer'); ?></div>
                                    <div class="modif-list">
                                        <?php if (! empty($item['modifiers'])) : ?>
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

        const restBase = '<?php echo esc_js(rest_url('vemcomer/v1/menu-items/')); ?>';
        const restNonce = '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>';

        const updateStats = () => {
            const statNodes = {
                active: document.querySelector('[data-stat="active"]'),
                paused: document.querySelector('[data-stat="paused"]'),
                noThumb: document.querySelector('[data-stat="no-thumb"]'),
            };

            let active = 0; let paused = 0; let noThumb = 0;
            document.querySelectorAll('.prod-card').forEach(card => {
                const available = card.getAttribute('data-available') === '1';
                if (available) { active++; } else { paused++; }
                const img = card.querySelector('.prod-img');
                if (img && img.getAttribute('src') === '') { noThumb++; }
            });

            if (statNodes.active) statNodes.active.textContent = active;
            if (statNodes.paused) statNodes.paused.textContent = paused;
            if (statNodes.noThumb) statNodes.noThumb.textContent = noThumb;
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
                    const response = await fetch(`${restBase}${itemId}/toggle-availability`, {
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
                    const response = await fetch(`${restBase}${itemId}`, {
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
    });
</script>

<?php
if (! $vc_marketplace_inline) {
    get_footer();
}
