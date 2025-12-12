<?php
/**
 * Cuisine_Menu_Blueprints — Blueprints de categorias de cardápio por arquétipo
 * Define as categorias "vida real" sugeridas para cada tipo de restaurante
 * 
 * @package VemComerCore
 */

namespace VC\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cuisine_Menu_Blueprints {
    /**
     * Retorna todos os blueprints de categorias de cardápio por arquétipo
     * 
     * @return array Array associativo: archetype => ['label' => string, 'blueprint' => array]
     */
    public static function all(): array {
        return [
            'hamburgueria' => [
                'label' => 'Hamburgueria',
                'blueprint' => [
                    ['name' => 'Destaques da casa', 'order' => 10],
                    ['name' => 'Combos', 'order' => 20],
                    ['name' => 'Hambúrgueres clássicos', 'order' => 30],
                    ['name' => 'Hambúrgueres especiais', 'order' => 40],
                    ['name' => 'Monte seu burger', 'order' => 50],
                    ['name' => 'Acompanhamentos', 'order' => 60],
                    ['name' => 'Sobremesas', 'order' => 70],
                    ['name' => 'Bebidas', 'order' => 80],
                ],
            ],

            'pizzaria' => [
                'label' => 'Pizzaria',
                'blueprint' => [
                    ['name' => 'Entradas / Porções', 'order' => 10],
                    ['name' => 'Pizzas salgadas', 'order' => 20],
                    ['name' => 'Pizzas doces', 'order' => 30],
                    ['name' => 'Combos', 'order' => 40],
                    ['name' => 'Bebidas', 'order' => 50],
                    ['name' => 'Sobremesas', 'order' => 60],
                ],
            ],

            'massas_risotos' => [
                'label' => 'Massas & Risotos',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Massas', 'order' => 20],
                    ['name' => 'Risotos', 'order' => 30],
                    ['name' => 'Pratos principais', 'order' => 40],
                    ['name' => 'Sobremesas', 'order' => 50],
                    ['name' => 'Bebidas', 'order' => 60],
                ],
            ],

            'japones' => [
                'label' => 'Culinária Japonesa',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Sushis e sashimis', 'order' => 20],
                    ['name' => 'Temakis', 'order' => 30],
                    ['name' => 'Pratos quentes', 'order' => 40],
                    ['name' => 'Lámen / Ramen', 'order' => 50],
                    ['name' => 'Bebidas', 'order' => 60],
                ],
            ],

            'chines' => [
                'label' => 'Culinária Chinesa',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Pratos principais', 'order' => 20],
                    ['name' => 'Yakissoba / Macarrão', 'order' => 30],
                    ['name' => 'Combos', 'order' => 40],
                    ['name' => 'Bebidas', 'order' => 50],
                ],
            ],

            'oriental_misto' => [
                'label' => 'Culinária Oriental',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Pratos principais', 'order' => 20],
                    ['name' => 'Curries / Woks', 'order' => 30],
                    ['name' => 'Acompanhamentos', 'order' => 40],
                    ['name' => 'Sobremesas', 'order' => 50],
                    ['name' => 'Bebidas', 'order' => 60],
                ],
            ],

            'arabe_mediterraneo' => [
                'label' => 'Culinária Árabe / Mediterrânea',
                'blueprint' => [
                    ['name' => 'Entradas / Mezze', 'order' => 10],
                    ['name' => 'Esfihas / Pães', 'order' => 20],
                    ['name' => 'Pratos principais', 'order' => 30],
                    ['name' => 'Kebab / Shawarma', 'order' => 40],
                    ['name' => 'Sobremesas', 'order' => 50],
                    ['name' => 'Bebidas', 'order' => 60],
                ],
            ],

            'mexicano_texmex' => [
                'label' => 'Culinária Mexicana / Tex-Mex',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Tacos / Burritos', 'order' => 20],
                    ['name' => 'Pratos principais', 'order' => 30],
                    ['name' => 'Molhos e acompanhamentos', 'order' => 40],
                    ['name' => 'Bebidas', 'order' => 50],
                ],
            ],

            'latino_americano' => [
                'label' => 'Culinária Latino-Americana',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Empanadas', 'order' => 20],
                    ['name' => 'Carnes', 'order' => 30],
                    ['name' => 'Ceviches', 'order' => 40],
                    ['name' => 'Acompanhamentos', 'order' => 50],
                    ['name' => 'Sobremesas', 'order' => 60],
                    ['name' => 'Bebidas', 'order' => 70],
                ],
            ],

            'europeu_ocidental' => [
                'label' => 'Culinária Europeia',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Pratos principais', 'order' => 20],
                    ['name' => 'Tapas / Petiscos', 'order' => 30],
                    ['name' => 'Sobremesas', 'order' => 40],
                    ['name' => 'Vinhos e bebidas', 'order' => 50],
                ],
            ],

            'brasileiro_caseiro' => [
                'label' => 'Culinária Brasileira',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Pratos principais', 'order' => 20],
                    ['name' => 'Sopas & Caldos', 'order' => 30],
                    ['name' => 'Acompanhamentos', 'order' => 40],
                    ['name' => 'Sobremesas', 'order' => 50],
                    ['name' => 'Bebidas', 'order' => 60],
                ],
            ],

            'marmitaria_pf' => [
                'label' => 'Marmitaria / Prato Feito',
                'blueprint' => [
                    ['name' => 'Marmitas do dia', 'order' => 10],
                    ['name' => 'Pratos executivos', 'order' => 20],
                    ['name' => 'Marmitas fitness', 'order' => 30],
                    ['name' => 'Acompanhamentos', 'order' => 40],
                    ['name' => 'Sobremesas', 'order' => 50],
                    ['name' => 'Bebidas', 'order' => 60],
                ],
            ],

            'churrascaria' => [
                'label' => 'Churrascaria',
                'blueprint' => [
                    ['name' => 'Carnes', 'order' => 10],
                    ['name' => 'Acompanhamentos', 'order' => 20],
                    ['name' => 'Saladas', 'order' => 30],
                    ['name' => 'Sobremesas', 'order' => 40],
                    ['name' => 'Bebidas', 'order' => 50],
                ],
            ],

            'grelhados_espetinhos' => [
                'label' => 'Grelhados & Espetinhos',
                'blueprint' => [
                    ['name' => 'Espetinhos', 'order' => 10],
                    ['name' => 'Grelhados', 'order' => 20],
                    ['name' => 'Acompanhamentos', 'order' => 30],
                    ['name' => 'Saladas', 'order' => 40],
                    ['name' => 'Bebidas', 'order' => 50],
                ],
            ],

            'frango_galeteria' => [
                'label' => 'Galeteria / Frango',
                'blueprint' => [
                    ['name' => 'Frango assado', 'order' => 10],
                    ['name' => 'Frango frito', 'order' => 20],
                    ['name' => 'Combos', 'order' => 30],
                    ['name' => 'Acompanhamentos', 'order' => 40],
                    ['name' => 'Bebidas', 'order' => 50],
                ],
            ],

            'frutos_mar_peixes' => [
                'label' => 'Frutos do Mar & Peixes',
                'blueprint' => [
                    ['name' => 'Entradas', 'order' => 10],
                    ['name' => 'Peixes', 'order' => 20],
                    ['name' => 'Frutos do mar', 'order' => 30],
                    ['name' => 'Acompanhamentos', 'order' => 40],
                    ['name' => 'Bebidas', 'order' => 50],
                ],
            ],

            'bar_boteco' => [
                'label' => 'Bar / Boteco',
                'blueprint' => [
                    ['name' => 'Petiscos e porções', 'order' => 10],
                    ['name' => 'Pratos principais', 'order' => 20],
                    ['name' => 'Bebidas', 'order' => 30],
                ],
            ],

            'cafeteria_padaria' => [
                'label' => 'Cafeteria / Padaria',
                'blueprint' => [
                    ['name' => 'Cafés', 'order' => 10],
                    ['name' => 'Bebidas quentes', 'order' => 20],
                    ['name' => 'Bebidas geladas', 'order' => 30],
                    ['name' => 'Salgados', 'order' => 40],
                    ['name' => 'Doces e bolos', 'order' => 50],
                    ['name' => 'Combos café + lanche', 'order' => 60],
                ],
            ],

            'sorveteria_acaiteria' => [
                'label' => 'Sorveteria / Açaíteria',
                'blueprint' => [
                    ['name' => 'Monte seu açaí / sorvete', 'order' => 10],
                    ['name' => 'Combos', 'order' => 20],
                    ['name' => 'Adicionais', 'order' => 30],
                    ['name' => 'Bebidas', 'order' => 40],
                ],
            ],

            'doces_sobremesas' => [
                'label' => 'Doces & Sobremesas',
                'blueprint' => [
                    ['name' => 'Doces', 'order' => 10],
                    ['name' => 'Bolos', 'order' => 20],
                    ['name' => 'Sobremesas especiais', 'order' => 30],
                    ['name' => 'Bebidas', 'order' => 40],
                ],
            ],

            'lanches_fastfood' => [
                'label' => 'Lanches & Fast Food',
                'blueprint' => [
                    ['name' => 'Sanduíches', 'order' => 10],
                    ['name' => 'Hot dogs', 'order' => 20],
                    ['name' => 'Salgados', 'order' => 30],
                    ['name' => 'Combos', 'order' => 40],
                    ['name' => 'Bebidas', 'order' => 50],
                ],
            ],

            'poke' => [
                'label' => 'Poke',
                'blueprint' => [
                    ['name' => 'Pokes prontos', 'order' => 10],
                    ['name' => 'Monte seu poke', 'order' => 20],
                    ['name' => 'Bases', 'order' => 30],
                    ['name' => 'Proteínas', 'order' => 40],
                    ['name' => 'Toppings', 'order' => 50],
                    ['name' => 'Molhos', 'order' => 60],
                    ['name' => 'Bebidas', 'order' => 70],
                ],
            ],

            'saudavel_vegetariano' => [
                'label' => 'Saudável / Vegetariano',
                'blueprint' => [
                    ['name' => 'Saladas', 'order' => 10],
                    ['name' => 'Bowls', 'order' => 20],
                    ['name' => 'Pratos principais', 'order' => 30],
                    ['name' => 'Sucos e bebidas', 'order' => 40],
                    ['name' => 'Sobremesas', 'order' => 50],
                ],
            ],

            'mercado_emporio' => [
                'label' => 'Mercado / Empório',
                'blueprint' => [
                    ['name' => 'Produtos frescos', 'order' => 10],
                    ['name' => 'Carnes & frios', 'order' => 20],
                    ['name' => 'Prontos & congelados', 'order' => 30],
                    ['name' => 'Bebidas', 'order' => 40],
                    ['name' => 'Vinhos & especiais', 'order' => 50],
                ],
            ],
        ];
    }

    /**
     * Retorna o blueprint de um arquétipo específico
     * 
     * @param string $archetype Slug do arquétipo
     * @return array|null Blueprint ou null se não encontrado
     */
    public static function get( string $archetype ): ?array {
        $all = self::all();
        return $all[ $archetype ] ?? null;
    }

    /**
     * Retorna apenas as categorias (blueprint) de um arquétipo
     * 
     * @param string $archetype Slug do arquétipo
     * @return array Array de categorias ou array vazio
     */
    public static function get_categories( string $archetype ): array {
        $blueprint = self::get( $archetype );
        return $blueprint['blueprint'] ?? [];
    }
}

