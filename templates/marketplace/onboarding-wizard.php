<?php
/**
 * Template: Wizard de Onboarding para Lojistas
 * 
 * Este wizard guia o lojista através dos passos essenciais para configurar sua loja:
 * 1. Tipo de restaurante
 * 2. Dados básicos
 * 3. Endereço e horários
 * 4. Categorias do cardápio
 * 5. Primeiros produtos
 * 6. Adicionais (opcional)
 * 7. Revisão e ativação
 * 
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Carregar helpers se necessário
if ( ! function_exists( 'vc_marketplace_current_restaurant' ) ) {
    require_once __DIR__ . '/helpers.php';
}

$restaurant = vc_marketplace_current_restaurant();
if ( ! $restaurant ) {
    return;
}

$restaurant_data = vc_marketplace_collect_restaurant_data( $restaurant );
$onboarding_status = \VC\Utils\Onboarding_Helper::get_onboarding_status( $restaurant->ID );
$current_step = $onboarding_status['current_step'] ?? 1;

// Buscar categorias de cozinha disponíveis
// Separar em primárias e tags/estilo
$all_cuisines = get_terms( [
    'taxonomy'   => 'vc_cuisine',
    'hide_empty' => false,
] );

$cuisine_options_primary = [];
$cuisine_options_tags = [];
if ( ! is_wp_error( $all_cuisines ) && $all_cuisines ) {
    foreach ( $all_cuisines as $term ) {
        // Filtrar apenas termos filhos (não grupos pais)
        if ( $term->parent === 0 && str_starts_with( (string) $term->slug, 'grupo-' ) ) {
            continue;
        }
        
        $is_primary = get_term_meta( $term->term_id, '_vc_is_primary_cuisine', true );
        $cuisine_option = [
            'id'   => $term->term_id,
            'name' => $term->name,
        ];
        
        if ( $is_primary === '1' ) {
            $cuisine_options_primary[] = $cuisine_option;
        } else {
            $cuisine_options_tags[] = $cuisine_option;
        }
    }
}

// Combinar: primárias primeiro, depois tags
$cuisine_options = array_merge( $cuisine_options_primary, $cuisine_options_tags );

// Buscar categorias recomendadas de cardápio (pré-carregar no PHP)
$recommended_categories = [];
$has_primary_cuisine = false;

if ( $restaurant instanceof WP_Post ) {
    // Buscar categorias de restaurante (vc_cuisine) associadas ao restaurante
    // FILTRAR APENAS CATEGORIAS PRIMÁRIAS (não tags/estilo)
    $cuisine_terms = wp_get_post_terms( $restaurant->ID, 'vc_cuisine', [ 'fields' => 'all' ] );
    
    // Filtrar apenas categorias primárias (_vc_is_primary_cuisine = '1')
    $cuisine_ids = [];
    if ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) {
        foreach ( $cuisine_terms as $term ) {
            $is_primary = get_term_meta( $term->term_id, '_vc_is_primary_cuisine', true );
            // Se a meta não estiver definida, considerar como primária (fallback para termos antigos)
            if ( $is_primary === '' || $is_primary === '1' ) {
                $cuisine_ids[] = (int) $term->term_id;
                if ( $is_primary === '1' ) {
                    $has_primary_cuisine = true;
                }
            }
        }
    }
    
    // Buscar categorias já criadas pelo restaurante (para filtrar das recomendações)
    $user_categories = get_terms( [
        'taxonomy'   => 'vc_menu_category',
        'hide_empty' => false,
    ] );
    
    $user_category_names = [];
    if ( ! is_wp_error( $user_categories ) && ! empty( $user_categories ) ) {
        foreach ( $user_categories as $user_cat ) {
            $is_catalog = get_term_meta( $user_cat->term_id, '_vc_is_catalog_category', true );
            $cat_restaurant_id = (int) get_term_meta( $user_cat->term_id, '_vc_restaurant_id', true );
            
            // Se não é do catálogo E pertence ao restaurante atual, adicionar à lista
            if ( $is_catalog !== '1' && $cat_restaurant_id === $restaurant->ID ) {
                $user_category_names[] = strtolower( trim( $user_cat->name ) );
            }
        }
    }
    
    // Buscar todas as categorias de cardápio do catálogo
    $catalog_categories = get_terms( [
        'taxonomy'   => 'vc_menu_category',
        'hide_empty' => false,
        'meta_query' => [
            [
                'key'     => '_vc_is_catalog_category',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ] );
    
    if ( ! is_wp_error( $catalog_categories ) && ! empty( $catalog_categories ) ) {
        $recommended = [];
        $generic = []; // Categorias genéricas (sem vínculo específico)
        
        foreach ( $catalog_categories as $category ) {
            // Pular se o restaurante já criou uma categoria com o mesmo nome
            if ( in_array( strtolower( trim( $category->name ) ), $user_category_names, true ) ) {
                continue;
            }
            
            $recommended_for = get_term_meta( $category->term_id, '_vc_recommended_for_cuisines', true );
            
            if ( empty( $recommended_for ) ) {
                // Categoria genérica (sem vínculo específico) - sempre mostrar
                $generic[] = [
                    'id'    => $category->term_id,
                    'name'  => $category->name,
                    'slug'  => $category->slug,
                    'order' => (int) get_term_meta( $category->term_id, '_vc_category_order', true ),
                ];
            } else {
                $recommended_cuisine_ids = json_decode( $recommended_for, true );
                
                if ( is_array( $recommended_cuisine_ids ) && ! empty( $cuisine_ids ) ) {
                    // Verificar se alguma categoria do restaurante está na lista de recomendadas
                    $intersection = array_intersect( $cuisine_ids, $recommended_cuisine_ids );
                    
                    if ( ! empty( $intersection ) ) {
                        $recommended[] = [
                            'id'    => $category->term_id,
                            'name'  => $category->name,
                            'slug'  => $category->slug,
                            'order' => (int) get_term_meta( $category->term_id, '_vc_category_order', true ),
                        ];
                    }
                }
            }
        }
        
        // Ordenar por ordem
        usort( $recommended, function( $a, $b ) {
            return $a['order'] <=> $b['order'];
        } );
        
        usort( $generic, function( $a, $b ) {
            return $a['order'] <=> $b['order'];
        } );
        
        // Combinar: primeiro as recomendadas, depois as genéricas
        // Sempre mostrar genéricas mesmo se não houver primárias
        $recommended_categories = array_merge( $recommended, $generic );
    }
}

// Preparar dados do wizard
$schedule_json = get_post_meta( $restaurant->ID, '_vc_restaurant_schedule', true );
$schedule = [];
if ( $schedule_json ) {
    $schedule_decoded = json_decode( $schedule_json, true );
    if ( is_array( $schedule_decoded ) ) {
        $days_map = [
            'monday'    => 'seg',
            'tuesday'   => 'ter',
            'wednesday' => 'qua',
            'thursday'  => 'qui',
            'friday'    => 'sex',
            'saturday'  => 'sab',
            'sunday'    => 'dom',
        ];
        foreach ( $days_map as $meta_key => $slug ) {
            $day_data = $schedule_decoded[ $meta_key ] ?? [];
            $schedule[ $slug ] = [
                'enabled' => ! empty( $day_data['enabled'] ),
                'ranges'  => $day_data['periods'] ?? [ [ 'open' => '09:00', 'close' => '18:00' ] ],
            ];
        }
    }
}

// Buscar cuisine_ids salvos (passo 1)
$cuisine_ids = [];
$primary_cuisine = (int) get_post_meta( $restaurant->ID, '_vc_primary_cuisine', true );
if ( $primary_cuisine > 0 ) {
    $cuisine_ids[] = $primary_cuisine;
}
$secondary_cuisines_json = get_post_meta( $restaurant->ID, '_vc_secondary_cuisines', true );
if ( $secondary_cuisines_json ) {
    $secondary_cuisines = json_decode( $secondary_cuisines_json, true );
    if ( is_array( $secondary_cuisines ) ) {
        $cuisine_ids = array_merge( $cuisine_ids, array_map( 'intval', $secondary_cuisines ) );
    }
}
// Se não encontrou via meta, buscar da taxonomia
if ( empty( $cuisine_ids ) ) {
    $cuisine_terms = wp_get_post_terms( $restaurant->ID, 'vc_cuisine', [ 'fields' => 'ids' ] );
    if ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) {
        $cuisine_ids = array_map( 'intval', $cuisine_terms );
    }
}

// Buscar categorias já criadas
$category_names = [];
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
            $category_names[] = $cat->name;
        }
    }
}

// Buscar produtos já criados
$products = [];
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
foreach ( $menu_items as $item ) {
    $category_terms = wp_get_post_terms( $item->ID, 'vc_menu_category', [ 'fields' => 'names' ] );
    $category_name = ! is_wp_error( $category_terms ) && ! empty( $category_terms ) ? $category_terms[0] : '';
    
    $products[] = [
        'name'        => $item->post_title,
        'category'    => $category_name,
        'category_id' => 0,
        'price'       => (float) get_post_meta( $item->ID, '_vc_price', true ),
        'description' => $item->post_content,
    ];
}

// Preparar wizard_data para passar aos parciais
$wizard_data = [
    'restaurant_id'  => $restaurant->ID, // Adicionar ID do restaurante para o Passo 7 buscar dados reais
    'cuisine_ids'    => $cuisine_ids ?? [],
    'name'           => $restaurant->post_title,
    'whatsapp'       => $restaurant_data['whatsapp'] ?? '',
    'logo'           => $restaurant_data['logo'] ?? '',
    'address'        => $restaurant_data['endereco'] ?? '',
    'neighborhood'   => $restaurant_data['bairro'] ?? '',
    'city'           => '',
    'zipcode'        => '',
    'delivery'       => get_post_meta( $restaurant->ID, 'vc_restaurant_delivery', true ) === '1',
    'pickup'         => false,
    'schedule'       => $schedule,
    'category_names' => $category_names,
    'products'       => $products,
    'addon_groups'   => [],
];

$rest_nonce = wp_create_nonce( 'wp_rest' );
$rest_url   = rest_url( 'vemcomer/v1' );

// Enfileirar Leaflet para o mapa do passo 3
wp_enqueue_style(
    'leaflet-css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    [],
    '1.9.4'
);
wp_enqueue_script(
    'leaflet-js',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    [],
    '1.9.4',
    true
);

// Enfileirar script do wizard (depende do Leaflet)
wp_enqueue_script(
    'vemcomer-onboarding-wizard',
    VEMCOMER_CORE_URL . 'assets/js/onboarding-wizard.js',
    [ 'jquery', 'leaflet-js' ],
    VEMCOMER_CORE_VERSION,
    true
);

// Preparar wizard_data inicial com dados salvos
$wizard_data_initial = [
    'restaurant_id'  => $wizard_data['restaurant_id'], // CRÍTICO para o Passo 7
    'cuisine_ids'    => $wizard_data['cuisine_ids'],
    'name'           => $wizard_data['name'],
    'whatsapp'       => $wizard_data['whatsapp'],
    'logo'           => $wizard_data['logo'],
    'address'        => $wizard_data['address'],
    'neighborhood'   => $wizard_data['neighborhood'],
    'city'           => $wizard_data['city'],
    'zipcode'        => $wizard_data['zipcode'],
    'delivery'       => $wizard_data['delivery'],
    'pickup'         => $wizard_data['pickup'],
    'schedule'       => $wizard_data['schedule'],
    'category_names' => $wizard_data['category_names'],
    'products'       => $wizard_data['products'],
    'addon_groups'   => $wizard_data['addon_groups'],
];

// Passar dados para o JavaScript
wp_localize_script( 'vemcomer-onboarding-wizard', 'vcOnboardingWizard', [
    'restBase'              => $rest_url,
    'restNonce'              => $rest_nonce,
    'restaurantId'           => $restaurant->ID,
    'currentStep'            => $current_step,
    'cuisineOptions'         => $cuisine_options,
    'cuisineOptionsPrimary'  => $cuisine_options_primary,
    'cuisineOptionsTags'      => $cuisine_options_tags,
    'restaurantData'         => $restaurant_data,
    'recommendedCategories'   => $recommended_categories,
    'hasPrimaryCuisine'      => $has_primary_cuisine,
    'initialWizardData'     => $wizard_data_initial, // Dados salvos para inicializar
] );
?>

<div id="vcOnboardingWizard" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;max-width:800px;width:100%;max-height:90vh;overflow-y:auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
        <!-- Barra de Progresso -->
        <div style="position:sticky;top:0;background:#2d8659;color:#fff;padding:12px 24px;border-radius:16px 16px 0 0;z-index:10;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div id="vcWizardStepTitle" style="font-weight:700;font-size:16px;">Passo <?php echo esc_html( $current_step ); ?> de 7</div>
                <button onclick="vcCloseOnboardingWizard()" style="background:transparent;border:none;color:#fff;font-size:24px;cursor:pointer;padding:0;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='transparent'">×</button>
            </div>
            <div style="background:rgba(255,255,255,0.3);height:4px;border-radius:2px;overflow:hidden;">
                <div id="vcWizardProgressBar" style="background:#fff;height:100%;width:<?php echo esc_attr( ( $current_step / 7 ) * 100 ); ?>%;transition:width 0.3s;"></div>
            </div>
        </div>

        <!-- Conteúdo do Wizard -->
        <div id="vcWizardContentWrapper" style="padding:32px;">
            <div id="vcWizardContent">
                <?php
                // Incluir o parcial do passo atual
                $partial_path = __DIR__ . '/onboarding/onboarding-step-' . $current_step . '.php';
                if ( file_exists( $partial_path ) ) {
                    include $partial_path;
                } else {
                    echo '<div style="text-align:center;padding:40px;color:#999;">Passo não encontrado.</div>';
                }
                ?>
            </div>
        </div>

        <!-- Botões de Navegação -->
        <div id="vcWizardButtons" style="padding:20px 32px;border-top:2px solid #eaf8f1;display:flex;justify-content:space-between;gap:12px;background:#f9f9f9;border-radius:0 0 16px 16px;">
            <button id="vcWizardBtnPrev" onclick="vcWizardPrev()" style="background:#fff;color:#2d8659;border:2px solid #2d8659;padding:12px 24px;border-radius:8px;font-weight:700;cursor:pointer;display:<?php echo $current_step > 1 ? 'block' : 'none'; ?>;">Voltar</button>
            <div style="flex:1;"></div>
            <button id="vcWizardBtnNext" onclick="vcWizardNext()" style="background:#2d8659;color:#fff;border:none;padding:12px 32px;border-radius:8px;font-weight:700;cursor:pointer;min-width:120px;"><?php echo $current_step === 7 ? 'Ativar minha loja' : 'Continuar'; ?></button>
        </div>
    </div>
</div>

<style>
    #vcOnboardingWizard * { box-sizing:border-box; }
    #vcOnboardingWizard input[type="text"],
    #vcOnboardingWizard input[type="tel"],
    #vcOnboardingWizard input[type="email"],
    #vcOnboardingWizard textarea,
    #vcOnboardingWizard select {
        width:100%;padding:12px;border:2px solid #eaf8f1;border-radius:8px;font-size:15px;font-family:inherit;margin-bottom:16px;
    }
    #vcOnboardingWizard input:focus,
    #vcOnboardingWizard textarea:focus,
    #vcOnboardingWizard select:focus {
        outline:none;border-color:#2d8659;
    }
    #vcOnboardingWizard .wizard-title {
        font-size:24px;font-weight:900;color:#2d8659;margin-bottom:8px;
    }
    #vcOnboardingWizard .wizard-subtitle {
        font-size:15px;color:#6b7672;margin-bottom:24px;line-height:1.5;
    }
    #vcOnboardingWizard .cuisine-option {
        display:inline-block;padding:12px 20px;margin:6px;background:#f5f5f5;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;transition:all 0.2s;font-weight:600;
    }
    #vcOnboardingWizard .cuisine-option:hover {
        background:#eaf8f1;border-color:#2d8659;
    }
    #vcOnboardingWizard .cuisine-option.selected {
        background:#2d8659;color:#fff;border-color:#2d8659;
    }
    #vcOnboardingWizard .category-checkbox {
        display:flex;align-items:center;padding:12px;margin-bottom:8px;background:#f9f9f9;border-radius:8px;cursor:pointer;transition:background 0.2s;
    }
    #vcOnboardingWizard .category-checkbox:hover {
        background:#eaf8f1;
    }
    #vcOnboardingWizard .category-checkbox input[type="checkbox"] {
        width:20px;height:20px;margin-right:12px;cursor:pointer;
    }
    #vcOnboardingWizard .product-item {
        background:#f9f9f9;border-radius:8px;padding:16px;margin-bottom:16px;
    }
    #vcOnboardingWizard .schedule-day {
        display:flex;align-items:center;gap:12px;padding:12px;background:#f9f9f9;border-radius:8px;margin-bottom:8px;
    }
    #vcOnboardingWizard .schedule-day input[type="checkbox"] {
        width:20px;height:20px;
    }
    #vcOnboardingWizard .schedule-day input[type="time"] {
        width:120px;margin:0;
    }
    #vcOnboardingWizard .error-message {
        background:#ffe7e7;color:#ea5252;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:600;
    }
    
    /* Responsividade Mobile */
    @media (max-width: 768px) {
        #vcOnboardingWizard {
            padding: 0 !important;
            align-items: flex-start !important;
        }
        
        #vcOnboardingWizard > div {
            max-height: 100vh !important;
            height: 100vh !important;
            border-radius: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        #vcWizardContentWrapper {
            flex: 1 !important;
            overflow-y: auto !important;
            padding: 24px 20px 100px 20px !important; /* Ajuste de padding no mobile + espaço para botões */
            -webkit-overflow-scrolling: touch !important; /* Scroll suave no iOS */
        }
        
        /* Barra de progresso no mobile */
        #vcOnboardingWizard > div > div:first-child {
            position: sticky !important;
            top: 0 !important;
            z-index: 11 !important;
            border-radius: 0 !important;
        }
        
        #vcWizardButtons {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            padding: 16px 20px !important;
            border-radius: 0 !important;
            border-top: 2px solid #eaf8f1 !important;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.1) !important;
            z-index: 100000 !important;
        }
        
        #vcWizardBtnPrev,
        #vcWizardBtnNext {
            padding: 14px 20px !important;
            font-size: 15px !important;
            min-width: auto !important;
            flex: 1 !important;
        }
        
        #vcWizardButtons > div {
            display: none !important; /* Remove o espaçador no mobile */
        }
        
        #vcWizardButtons {
            gap: 12px !important;
        }
    }
    
    @media (max-width: 480px) {
        #vcWizardButtons {
            padding: 12px 16px !important;
        }
        
        #vcWizardBtnPrev,
        #vcWizardBtnNext {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        
        #vcWizardContentWrapper {
            padding: 20px 16px 100px 16px !important;
        }
        
        #vcWizardStepTitle {
            font-size: 14px !important;
        }
        
        .wizard-title {
            font-size: 20px !important;
        }
        
        .wizard-subtitle {
            font-size: 14px !important;
        }
        
        /* Mapa no mobile */
        #vcWizardMap {
            height: 250px !important;
            min-height: 250px !important;
        }
    }
    
    /* Estilos específicos para o mapa */
    #vcWizardMap {
        position: relative !important;
        z-index: 1 !important;
    }
    
    #vcWizardMap .leaflet-container {
        width: 100% !important;
        height: 100% !important;
        border-radius: 8px !important;
    }
    
    #vcWizardMapLoading {
        position: absolute;
        z-index: 1000;
        pointer-events: none;
    }
    
    @media (max-width: 768px) {
        #vcWizardMap {
            height: 250px !important;
            min-height: 250px !important;
            max-height: 250px !important;
        }
        
        #vcWizardUseLocation {
            padding: 14px 20px !important;
            font-size: 16px !important;
            -webkit-tap-highlight-color: rgba(45, 134, 89, 0.3);
        }
    }
</style>
