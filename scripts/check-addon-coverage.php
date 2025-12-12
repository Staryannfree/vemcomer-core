<?php
/**
 * Script para verificar quais categorias de restaurantes têm grupos de adicionais conectados
 * e quais não têm
 */

require_once __DIR__ . '/../wp-load.php';

// Todas as categorias de restaurantes (do Cuisine_Seeder)
$all_cuisines = [
    // Brasileira
    'Restaurante brasileiro caseiro',
    'Comida mineira',
    'Comida baiana',
    'Comida nordestina',
    'Comida gaúcha',
    'Comida amazônica',
    'Comida paraense',
    'Comida caiçara',
    'Comida pantaneira',
    'Feijoada',
    'Self-service / por quilo',
    'Marmitaria / Marmitex',
    'Prato feito (PF)',
    'Restaurante executivo',
    'Restaurante contemporâneo',
    'Restaurante de alta gastronomia / fine dining',
    'Bistrô',
    'Comida caseira',
    'Restaurante tropical / praiano',
    
    // Internacional
    'Culinária italiana',
    'Pizzaria tradicional',
    'Pizzaria napolitana',
    'Pizzaria rodízio',
    'Pizzaria delivery',
    'Massas & risotos',
    'Culinária francesa',
    'Culinária portuguesa',
    'Culinária espanhola',
    'Tapas',
    'Culinária mexicana',
    'Tex-Mex',
    'Culinária norte-americana',
    'Hamburgueria artesanal',
    'Hamburgueria smash',
    'Hot dog / Cachorro-quente',
    'Steakhouse',
    'Culinária argentina',
    'Culinária uruguaia',
    'Culinária peruana',
    'Cevicheria',
    'Culinária japonesa',
    'Sushi bar',
    'Temakeria',
    'Restaurante de lámen / ramen',
    'Izakaya',
    'Culinária chinesa',
    'Culinária tailandesa',
    'Culinária vietnamita',
    'Culinária coreana',
    'Churrasco coreano (K-BBQ)',
    'Culinária indiana',
    'Culinária árabe',
    'Culinária turca',
    'Culinária libanesa',
    'Culinária grega',
    'Culinária mediterrânea',
    'Culinária oriental (mista)',
    'Culinária africana',
    'Culinária marroquina',
    'Culinária fusion',
    
    // Especialidades
    'Churrascaria rodízio',
    'Churrascaria à la carte',
    'Espetinhos',
    'Grelhados',
    'Frutos do mar',
    'Peixes',
    'Galeteria',
    'Frango assado',
    'Frango frito estilo americano',
    'Assados & rotisserie',
    'Sopas & caldos',
    'Comida de boteco',
    'Petiscos e porções',
    'Pastelaria',
    'Esfiharia',
    'Creperia salgada',
    'Tapiocaria',
    'Panquecaria',
    'Omeleteria',
    'Comida fit / saudável',
    'Saladas & bowls',
    'Poke',
    'Açaíteria',
    'Refeições congeladas',
    
    // Lanches
    'Lanchonete',
    'Sanduíches & baguetes',
    'Wraps & tortillas',
    'Salgados variados',
    'Coxinha & frituras',
    'Kebab / shawarma',
    'Food truck',
    'Quiosque de praia',
    'Trailer de lanches',
    'Refeição rápida / fast-food',
    
    // Cafés
    'Cafeteria',
    'Coffee shop especializado',
    'Padaria tradicional',
    'Padaria gourmet',
    'Confeitaria',
    'Doceria',
    'Brigaderia',
    'Brownieria',
    'Loja de donuts',
    'Casa de bolos',
    'Chocolateria',
    'Bomboniere',
    'Gelateria',
    'Sorveteria',
    'Yogurteria',
    'Creperia doce',
    'Waffle house',
    'Casa de chá',
    
    // Bares
    'Bar',
    'Boteco',
    'Gastrobar',
    'Pub',
    'Sports bar / Bar esportivo',
    'Bar de vinhos / Wine bar',
    'Cervejaria artesanal',
    'Choperia',
    'Adega de bebidas',
    'Bar de drinks / Coquetelaria',
    'Bar de caipirinha',
    'Rooftop bar',
    'Lounge bar',
    'Karaokê bar',
    'Beach club',
    'Hookah / Narguilé bar',
    'Balada / Night club',
    
    // Saudável
    'Vegetariano',
    'Vegano',
    'Plant-based',
    'Sem glúten',
    'Sem lactose',
    'Orgânico',
    'Natural / saudável',
    'Comida funcional',
    'Low carb',
    'Marmita fitness',
    
    // Estilo
    'Restaurante familiar / kids friendly',
    'Restaurante romântico',
    'Restaurante temático',
    'Restaurante com música ao vivo',
    'Rodízio (geral)',
    'Buffet livre',
    'À la carte',
    'Delivery only / Dark kitchen',
    'Drive-thru',
    'Take-away / para levar',
    'Praça de alimentação / food court',
    
    // Outros
    'Mercado / mini mercado',
    'Empório',
    'Loja de produtos naturais',
    'Açougue gourmet',
    'Hortifruti',
    'Peixaria',
    'Loja de conveniência',
    'Loja de vinhos e destilados',
];

// Ler o arquivo Addon_Catalog_Seeder para extrair todas as categorias conectadas
$seeder_file = __DIR__ . '/../inc/Utils/Addon_Catalog_Seeder.php';
$seeder_content = file_get_contents($seeder_file);

// Extrair todas as categorias que aparecem nos grupos de adicionais
$connected_cuisines = [];
preg_match_all("/'categories'\s*=>\s*\[(.*?)\]/s", $seeder_content, $matches);

foreach ($matches[1] as $categories_block) {
    // Extrair nomes de categorias entre aspas simples
    preg_match_all("/'([^']+)'/", $categories_block, $category_matches);
    foreach ($category_matches[1] as $category) {
        if (!empty($category)) {
            $connected_cuisines[$category] = true;
        }
    }
}

// Verificar quais categorias NÃO têm grupos de adicionais
$missing_cuisines = [];
foreach ($all_cuisines as $cuisine) {
    if (!isset($connected_cuisines[$cuisine])) {
        $missing_cuisines[] = $cuisine;
    }
}

// Exibir resultados
echo "=== ANÁLISE DE COBERTURA DE ADICIONAIS ===\n\n";
echo "Total de categorias de restaurantes: " . count($all_cuisines) . "\n";
echo "Categorias com grupos de adicionais: " . count($connected_cuisines) . "\n";
echo "Categorias SEM grupos de adicionais: " . count($missing_cuisines) . "\n\n";

if (!empty($missing_cuisines)) {
    echo "=== CATEGORIAS SEM GRUPOS DE ADICIONAIS ===\n";
    foreach ($missing_cuisines as $cuisine) {
        echo "- $cuisine\n";
    }
} else {
    echo "✅ TODAS as categorias têm grupos de adicionais conectados!\n";
}

