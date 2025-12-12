<?php
/**
 * Archetypes_Manager — Página admin para gerenciar arquétipos e categorias
 * 
 * @package VemComerCore
 */

namespace VC\Admin;

use VC\Utils\Cuisine_Helper;
use VC\Utils\Menu_Category_Catalog_Seeder;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Archetypes_Manager {
    
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_vc_migrate_archetypes', [ $this, 'ajax_migrate_archetypes' ] );
        add_action( 'wp_ajax_vc_reseed_categories', [ $this, 'ajax_reseed_categories' ] );
    }

    public function register_menu(): void {
        add_submenu_page(
            'vemcomer-root',
            __( 'Arquétipos e Categorias', 'vemcomer' ),
            __( 'Arquétipos', 'vemcomer' ),
            'manage_options',
            'vemcomer-archetypes',
            [ $this, 'render_page' ]
        );
    }

    public function enqueue_scripts( string $hook ): void {
        if ( 'vemcomer_page_vemcomer-archetypes' !== $hook ) {
            return;
        }
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Você não tem permissão para acessar esta página.', 'vemcomer' ) );
        }

        // Estatísticas
        $total_cuisines = 0;
        $mapped_cuisines = 0;
        $unmapped_cuisines = 0;
        
        if ( taxonomy_exists( 'vc_cuisine' ) ) {
            $all_cuisines = get_terms( [
                'taxonomy'   => 'vc_cuisine',
                'hide_empty' => false,
            ] );
            
            if ( ! is_wp_error( $all_cuisines ) && ! empty( $all_cuisines ) ) {
                foreach ( $all_cuisines as $term ) {
                    if ( $term->parent === 0 && str_starts_with( $term->slug, 'grupo-' ) ) {
                        continue;
                    }
                    $total_cuisines++;
                    $archetype = get_term_meta( $term->term_id, '_vc_cuisine_archetype', true );
                    if ( ! empty( $archetype ) ) {
                        $mapped_cuisines++;
                    } else {
                        $unmapped_cuisines++;
                    }
                }
            }
        }

        $total_catalog_categories = 0;
        if ( taxonomy_exists( 'vc_menu_category' ) ) {
            $catalog_cats = get_terms( [
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
            $total_catalog_categories = ! is_wp_error( $catalog_cats ) ? count( $catalog_cats ) : 0;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Gerenciar Arquétipos e Categorias', 'vemcomer' ); ?></h1>
            
            <div class="vc-archetypes-dashboard" style="margin-top: 20px;">
                
                <!-- Estatísticas -->
                <div class="vc-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="vc-stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: 600;">Total de Cuisines</h3>
                        <div style="font-size: 32px; font-weight: 700; color: #2d8659;"><?php echo esc_html( $total_cuisines ); ?></div>
                    </div>
                    <div class="vc-stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: 600;">Cuisines Mapeadas</h3>
                        <div style="font-size: 32px; font-weight: 700; color: #2d8659;"><?php echo esc_html( $mapped_cuisines ); ?></div>
                    </div>
                    <div class="vc-stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: 600;">Cuisines sem Mapeamento</h3>
                        <div style="font-size: 32px; font-weight: 700; color: <?php echo $unmapped_cuisines > 0 ? '#d32f2f' : '#2d8659'; ?>;"><?php echo esc_html( $unmapped_cuisines ); ?></div>
                    </div>
                    <div class="vc-stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: 600;">Categorias de Catálogo</h3>
                        <div style="font-size: 32px; font-weight: 700; color: #2d8659;"><?php echo esc_html( $total_catalog_categories ); ?></div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="vc-actions-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    
                    <!-- Migração de Arquétipos -->
                    <div class="vc-action-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h2 style="margin: 0 0 12px 0; font-size: 18px; color: #232a2c;">1️⃣ Migrar Cuisines para Arquétipos</h2>
                        <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                            Mapeia todos os tipos de restaurante (cuisines) para os 24 arquétipos definidos. 
                            Isso permite que o sistema recomende categorias de cardápio corretas.
                        </p>
                        <button 
                            id="vcMigrateArchetypesBtn" 
                            class="button button-primary button-large" 
                            style="width: 100%; padding: 12px; font-size: 16px; font-weight: 600;"
                        >
                            🚀 Executar Migração
                        </button>
                        <div id="vcMigrateArchetypesResult" style="margin-top: 15px; display: none;"></div>
                    </div>

                    <!-- Re-seed de Categorias -->
                    <div class="vc-action-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h2 style="margin: 0 0 12px 0; font-size: 18px; color: #232a2c;">2️⃣ Re-seed do Catálogo de Categorias</h2>
                        <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                            Limpa e recria todas as categorias de cardápio baseadas nos blueprints dos 24 arquétipos. 
                            Remove categorias antigas e cria novas vinculadas corretamente aos arquétipos.
                        </p>
                        <button 
                            id="vcReseedCategoriesBtn" 
                            class="button button-primary button-large" 
                            style="width: 100%; padding: 12px; font-size: 16px; font-weight: 600; background: #facb32; border-color: #facb32; color: #232a2c;"
                        >
                            🔄 Executar Re-seed
                        </button>
                        <div id="vcReseedCategoriesResult" style="margin-top: 15px; display: none;"></div>
                    </div>

                </div>

                <!-- Informações -->
                <div class="vc-info-box" style="background: #eaf8f1; border-left: 4px solid #2d8659; padding: 20px; margin-top: 30px; border-radius: 4px;">
                    <h3 style="margin: 0 0 10px 0; color: #2d8659; font-size: 16px;">ℹ️ Informações</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #333; line-height: 1.8;">
                        <li><strong>Arquétipos:</strong> Existem 24 arquétipos definidos (Hamburgueria, Pizzaria, Japonesa, etc.)</li>
                        <li><strong>Migração:</strong> Deve ser executada primeiro para mapear todas as cuisines aos arquétipos</li>
                        <li><strong>Re-seed:</strong> Deve ser executado após a migração para criar/atualizar as categorias de catálogo</li>
                        <li><strong>Cache:</strong> O cache do WordPress será limpo automaticamente após cada operação</li>
                    </ul>
                </div>

            </div>
        </div>

        <script>
        (function($) {
            $('#vcMigrateArchetypesBtn').on('click', function() {
                const $btn = $(this);
                const $result = $('#vcMigrateArchetypesResult');
                
                $btn.prop('disabled', true).text('⏳ Executando...');
                $result.hide().html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vc_migrate_archetypes',
                        nonce: '<?php echo wp_create_nonce( 'vc_migrate_archetypes' ); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('🚀 Executar Migração');
                        
                        if (response.success) {
                            $result.html(
                                '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px;">' +
                                '<strong>✅ Sucesso!</strong><br>' +
                                'Mapeados: ' + response.data.mapped + '<br>' +
                                'Já mapeados: ' + response.data.already_mapped + '<br>' +
                                'Tags de estilo: ' + response.data.style_tags + '<br>' +
                                'Sem mapeamento: ' + response.data.not_found +
                                '</div>'
                            ).show();
                            
                            // Recarregar página após 2 segundos para atualizar estatísticas
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html(
                                '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px;">' +
                                '<strong>❌ Erro:</strong> ' + (response.data || 'Erro desconhecido') +
                                '</div>'
                            ).show();
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('🚀 Executar Migração');
                        $result.html(
                            '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px;">' +
                            '<strong>❌ Erro:</strong> Falha na comunicação com o servidor' +
                            '</div>'
                        ).show();
                    }
                });
            });

            $('#vcReseedCategoriesBtn').on('click', function() {
                const $btn = $(this);
                const $result = $('#vcReseedCategoriesResult');
                
                $btn.prop('disabled', true).text('⏳ Executando...');
                $result.hide().html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vc_reseed_categories',
                        nonce: '<?php echo wp_create_nonce( 'vc_reseed_categories' ); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('🔄 Executar Re-seed');
                        
                        if (response.success) {
                            let html = '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px;">' +
                                '<strong>✅ Sucesso!</strong><br>' +
                                'Categorias deletadas: ' + response.data.deleted + '<br>' +
                                'Categorias mantidas (com produtos): ' + response.data.kept + '<br>' +
                                'Categorias criadas/atualizadas: ' + response.data.created;
                            
                            if (response.data.categories_sample && response.data.categories_sample.length > 0) {
                                html += '<br><br><strong>Exemplos de categorias criadas:</strong><ul style="margin: 10px 0 0 20px;">';
                                response.data.categories_sample.slice(0, 5).forEach(function(cat) {
                                    const archetypes = cat.archetypes && cat.archetypes.length > 0 
                                        ? cat.archetypes.join(', ') 
                                        : '(genérica)';
                                    html += '<li>' + cat.name + ' → ' + archetypes + '</li>';
                                });
                                html += '</ul>';
                            }
                            
                            html += '</div>';
                            $result.html(html).show();
                            
                            // Recarregar página após 3 segundos para atualizar estatísticas
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            $result.html(
                                '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px;">' +
                                '<strong>❌ Erro:</strong> ' + (response.data || 'Erro desconhecido') +
                                '</div>'
                            ).show();
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('🔄 Executar Re-seed');
                        $result.html(
                            '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px;">' +
                            '<strong>❌ Erro:</strong> Falha na comunicação com o servidor' +
                            '</div>'
                        ).show();
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * AJAX: Executar migração de arquétipos
     */
    public function ajax_migrate_archetypes(): void {
        check_ajax_referer( 'vc_migrate_archetypes', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sem permissão' );
            return;
        }

        if ( ! taxonomy_exists( 'vc_cuisine' ) ) {
            wp_send_json_error( 'Taxonomia vc_cuisine não existe.' );
            return;
        }

        $terms = get_terms( [
            'taxonomy'   => 'vc_cuisine',
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            wp_send_json_error( 'Nenhum termo vc_cuisine encontrado.' );
            return;
        }

        $stats = [
            'mapped'        => 0,
            'already_mapped' => 0,
            'style_tags'    => 0,
            'not_found'     => 0,
            'errors'        => 0,
        ];

        $map = Cuisine_Helper::get_slug_archetype_mapping();
        $style_tags_map = Cuisine_Helper::get_style_tags_mapping();

        foreach ( $terms as $term ) {
            if ( $term->parent === 0 && str_starts_with( $term->slug, 'grupo-' ) ) {
                continue;
            }

            $slug = $term->slug;
            $existing_archetype = get_term_meta( $term->term_id, '_vc_cuisine_archetype', true );
            
            if ( ! empty( $existing_archetype ) ) {
                $stats['already_mapped']++;
                continue;
            }

            if ( isset( $map[ $slug ] ) ) {
                $archetype = $map[ $slug ];
                if ( Cuisine_Helper::set_archetype_for_cuisine( $term->term_id, $archetype ) ) {
                    $stats['mapped']++;
                } else {
                    $stats['errors']++;
                }
            } elseif ( isset( $style_tags_map[ $slug ] ) ) {
                $tags = $style_tags_map[ $slug ];
                foreach ( $tags as $meta_key => $meta_value ) {
                    update_term_meta( $term->term_id, $meta_key, $meta_value );
                }
                $stats['style_tags']++;
            } else {
                $stats['not_found']++;
            }
        }

        // Limpar cache
        clean_term_cache( null, 'vc_cuisine' );
        wp_cache_flush();

        wp_send_json_success( $stats );
    }

    /**
     * AJAX: Executar re-seed de categorias
     */
    public function ajax_reseed_categories(): void {
        check_ajax_referer( 'vc_reseed_categories', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sem permissão' );
            return;
        }

        if ( ! taxonomy_exists( 'vc_menu_category' ) || ! taxonomy_exists( 'vc_cuisine' ) ) {
            wp_send_json_error( 'Taxonomias necessárias não existem.' );
            return;
        }

        // Buscar categorias de catálogo existentes
        $existing_catalog = get_terms( [
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

        $deleted_count = 0;
        $kept_count = 0;
        if ( ! is_wp_error( $existing_catalog ) && ! empty( $existing_catalog ) ) {
            foreach ( $existing_catalog as $term ) {
                delete_term_meta( $term->term_id, '_vc_is_catalog_category' );
                delete_term_meta( $term->term_id, '_vc_recommended_for_cuisines' );
                delete_term_meta( $term->term_id, '_vc_recommended_for_archetypes' );
                delete_term_meta( $term->term_id, '_vc_category_order' );
                
                if ( $term->count === 0 ) {
                    wp_delete_term( $term->term_id, 'vc_menu_category' );
                    $deleted_count++;
                } else {
                    $kept_count++;
                }
            }
        }

        // Limpar cache
        clean_term_cache( null, 'vc_menu_category' );
        delete_option( 'vemcomer_menu_categories_seeded' );
        wp_cache_flush();

        // Executar seed novamente
        Menu_Category_Catalog_Seeder::seed( true );

        // Verificar resultado
        $new_catalog = get_terms( [
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

        $categories_info = [];
        if ( ! is_wp_error( $new_catalog ) && ! empty( $new_catalog ) ) {
            foreach ( array_slice( $new_catalog, 0, 10 ) as $cat ) {
                $archetypes = get_term_meta( $cat->term_id, '_vc_recommended_for_archetypes', true );
                $archetype_list = ! empty( $archetypes ) ? json_decode( $archetypes, true ) : [];
                $categories_info[] = [
                    'name' => $cat->name,
                    'archetypes' => $archetype_list,
                ];
            }
        }

        wp_send_json_success( [
            'deleted' => $deleted_count,
            'kept' => $kept_count,
            'created' => ! is_wp_error( $new_catalog ) ? count( $new_catalog ) : 0,
            'categories_sample' => $categories_info,
        ] );
    }
}

