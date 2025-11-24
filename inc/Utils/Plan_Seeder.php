<?php
/**
 * Plan_Seeder — Cria os planos padrão do sistema
 * @package VemComerCore
 */

namespace VC\Utils;

use VC\Model\CPT_SubscriptionPlan;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Plan_Seeder {
    public static function seed(): void {
        if ( ! class_exists( '\\VC\\Model\\CPT_SubscriptionPlan' ) ) {
            return;
        }

        $plans = [
            [
                'title' => 'Plano Vitrine (Grátis)',
                'slug'  => 'plano-vitrine',
                'meta'  => [
                    '_vc_plan_monthly_price'          => 0,
                    '_vc_plan_max_menu_items'         => 20,
                    '_vc_plan_max_modifiers_per_item' => 0, // Bloqueado
                    '_vc_plan_advanced_analytics'     => '0',
                    '_vc_plan_priority_support'       => '0',
                    '_vc_plan_features'               => json_encode([
                        'whatsapp_rich' => false,
                        'description' => 'Cardápio simples, Link Zap texto puro, Indexação básica'
                    ]),
                    '_vc_plan_active'                 => '1',
                ]
            ],
            [
                'title' => 'Plano Delivery Pro',
                'slug'  => 'plano-delivery-pro',
                'meta'  => [
                    '_vc_plan_monthly_price'          => 49.90,
                    '_vc_plan_max_menu_items'         => 0, // Ilimitado
                    '_vc_plan_max_modifiers_per_item' => 1, // Liberado (ilimitado na prática do código atual, 1 significa bool true para permissão)
                    // Nota: O código Plan_Manager::can_use_modifiers checa se !== 0.
                    // Mas CPT_ProductModifier salva _vc_modifier_max.
                    // O Plan Manager checa PERMISSÃO de USO.
                    '_vc_plan_advanced_analytics'     => '0',
                    '_vc_plan_priority_support'       => '0',
                    '_vc_plan_features'               => json_encode([
                        'whatsapp_rich' => true,
                        'description' => 'Cardápio ilimitado, Modificadores, Zap Formatado, Gestão de Horários'
                    ]),
                    '_vc_plan_active'                 => '1',
                ]
            ],
            [
                'title' => 'Plano Gestão & Growth',
                'slug'  => 'plano-gestao-growth',
                'meta'  => [
                    '_vc_plan_monthly_price'          => 129.90,
                    '_vc_plan_max_menu_items'         => 0,
                    '_vc_plan_max_modifiers_per_item' => 1,
                    '_vc_plan_advanced_analytics'     => '1',
                    '_vc_plan_priority_support'       => '1',
                    '_vc_plan_features'               => json_encode([
                        'whatsapp_rich' => true,
                        'description' => 'Analytics Completo, Destaque na Busca, Selos, Banner Rotativo'
                    ]),
                    '_vc_plan_active'                 => '1',
                ]
            ]
        ];

        foreach ( $plans as $data ) {
            $existing = get_page_by_path( $data['slug'], OBJECT, CPT_SubscriptionPlan::SLUG );
            
            if ( $existing ) {
                // Atualizar meta se já existir
                foreach ( $data['meta'] as $key => $value ) {
                    update_post_meta( $existing->ID, $key, $value );
                }
                continue;
            }

            $post_id = wp_insert_post([
                'post_title'   => $data['title'],
                'post_name'    => $data['slug'],
                'post_type'    => CPT_SubscriptionPlan::SLUG,
                'post_status'  => 'publish',
                'post_content' => $data['meta']['_vc_plan_features'] // Descrição simples no content também
            ]);

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                foreach ( $data['meta'] as $key => $value ) {
                    update_post_meta( $post_id, $key, $value );
                }
            }
        }
    }
}

