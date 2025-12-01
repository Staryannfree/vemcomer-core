<?php
/**
 * Facility_Seeder — cria termos padrão para facilidades/etiquetas de restaurantes
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Facility_Seeder {
    public static function seed(): void {
        if ( ! function_exists( 'register_taxonomy' ) || ! taxonomy_exists( 'vc_facility' ) ) {
            return;
        }

        // Evita rodar mais de uma vez em produção
        $already = get_option( 'vemcomer_facilities_seeded' );
        if ( $already ) {
            return;
        }

        $groups = [
            'estrutura-conforto' => [
                'label' => '🏠 Estrutura & conforto',
                'items' => [
                    'Wi-Fi grátis',
                    'Ar-condicionado',
                    'Mesas ao ar livre',
                    'Área interna climatizada',
                    'Ambiente fechado',
                    'Ambiente silencioso',
                    'Ambiente animado',
                    'Música ambiente',
                    'Música ao vivo',
                    'Televisão / telão',
                    'Transmite jogos de futebol',
                    'Tomadas para notebook/celular',
                    'Iluminação aconchegante',
                    'Sofás / poltronas',
                ],
            ],
            'localizacao-acesso' => [
                'label' => '🚗 Localização & acesso',
                'items' => [
                    'Estacionamento próprio',
                    'Estacionamento gratuito',
                    'Estacionamento conveniado',
                    'Estacionamento com manobrista',
                    'Fácil acesso de transporte público',
                    'Perto do metrô / trem',
                    'Perto de pontos turísticos',
                    'Drive-thru',
                    'Ponto de retirada rápido ("pegue e leve")',
                ],
            ],
            'familia-criancas-pets' => [
                'label' => '👨‍👩‍👧 Família, crianças & pets',
                'items' => [
                    'Ambiente familiar',
                    'Bom para crianças',
                    'Espaço kids',
                    'Brinquedoteca',
                    'Cadeiras para bebês',
                    'Trocador / fraldário',
                    'Pet friendly (aceita pets)',
                ],
            ],
            'acessibilidade' => [
                'label' => '♿ Acessibilidade',
                'items' => [
                    'Acessível para cadeirantes',
                    'Banheiro adaptado',
                    'Rampas de acesso',
                    'Mesas acessíveis',
                ],
            ],
            'servico-formato' => [
                'label' => '🍽️ Serviço & formato',
                'items' => [
                    'À la carte',
                    'Self-service / por quilo',
                    'Buffet livre',
                    'Rodízio',
                    'Prato feito (PF)',
                    'Marmita / marmitex',
                    'Refeição executiva',
                    'Café da manhã',
                    'Almoço',
                    'Jantar',
                    'Lanches rápidos',
                    'Comida para viagem (take-away)',
                    'Atendimento na mesa',
                    'Pedido pelo balcão',
                    'Pedido pelo app na mesa',
                    'Atendimento 24h',
                    'Abre até tarde',
                ],
            ],
            'delivery-retirada' => [
                'label' => '🛵 Delivery & retirada',
                'items' => [
                    'Entrega própria',
                    'Entrega por motoboy parceiro',
                    'Entrega rápida',
                    'Taxa de entrega barata',
                    'Retirada na loja',
                    'Retirada sem sair do carro',
                    'Embalagem reforçada',
                    'Lacre de segurança na embalagem',
                ],
            ],
            'pagamento' => [
                'label' => '💳 Pagamento',
                'items' => [
                    'Aceita dinheiro',
                    'Aceita cartão de débito',
                    'Aceita cartão de crédito',
                    'Aceita PIX',
                    'Aceita vale-refeição (VR)',
                    'Aceita vale-alimentação (VA)',
                    'Pagamento online pelo app',
                    'Pagamento na entrega',
                    'Divide conta no app',
                ],
            ],
            'dietas-opcoes' => [
                'label' => '🥗 Dietas & opções especiais',
                'items' => [
                    'Opções vegetarianas',
                    'Opções veganas',
                    'Opções sem glúten',
                    'Opções sem lactose',
                    'Opções low carb',
                    'Opções fit / saudáveis',
                    'Opções orgânicas',
                    'Opções infantis',
                    'Opções para diabéticos',
                    'Opções sem açúcar',
                    'Opções sem carne suína',
                ],
            ],
            'bebidas-extras' => [
                'label' => '🍷 Bebidas & extras',
                'items' => [
                    'Cervejas artesanais',
                    'Chopp gelado',
                    'Drinks autorais',
                    'Carta de vinhos',
                    'Sucos naturais',
                    'Café especial',
                    'Milk-shakes',
                    'Coquetéis sem álcool (mocktails)',
                ],
            ],
            'sustentabilidade-valores' => [
                'label' => '🌱 Sustentabilidade & valores',
                'items' => [
                    'Usa embalagens recicláveis',
                    'Evita plástico descartável',
                    'Canudos ecológicos',
                    'Descarte correto de óleo',
                    'Apoia produtores locais',
                    'Ingredientes frescos do dia',
                ],
            ],
        ];

        foreach ( $groups as $group_key => $group ) {
            $parent_term = null;

            // Cria um termo "pai" para organizar no admin (ex.: grupo: Estrutura & conforto)
            $parent = wp_insert_term(
                $group['label'],
                'vc_facility',
                [
                    'slug' => sanitize_title( 'grupo-' . $group_key ),
                ]
            );

            if ( ! is_wp_error( $parent ) && isset( $parent['term_id'] ) ) {
                $parent_term = (int) $parent['term_id'];
            } else {
                // Tenta recuperar se já existir
                $existing_parent = get_term_by( 'slug', sanitize_title( 'grupo-' . $group_key ), 'vc_facility' );
                if ( $existing_parent && ! is_wp_error( $existing_parent ) ) {
                    $parent_term = (int) $existing_parent->term_id;
                }
            }

            foreach ( $group['items'] as $label ) {
                $slug = sanitize_title( $label );

                if ( term_exists( $slug, 'vc_facility' ) ) {
                    continue;
                }

                wp_insert_term(
                    $label,
                    'vc_facility',
                    [
                        'slug'   => $slug,
                        'parent' => $parent_term ?: 0,
                    ]
                );
            }
        }

        update_option( 'vemcomer_facilities_seeded', 1 );
    }
}

