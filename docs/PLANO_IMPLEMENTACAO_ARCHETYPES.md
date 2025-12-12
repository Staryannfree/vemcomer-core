# Plano de Implementação: Sistema de Arquétipos de Restaurante

## Objetivo

Refatorar o sistema de categorias de cardápio para usar **arquétipos de restaurante** como camada intermediária entre `vc_cuisine` (tipos de restaurante) e `vc_menu_category` (categorias de cardápio), reduzindo complexidade de 130+ tipos para ~18-20 arquétipos.

---

## Arquétipos Finais (20 arquétipos principais)

### Arquétipos Essenciais (15)
1. `hamburgueria`
2. `pizzaria`
3. `massas_risotos`
4. `japones`
5. `chines`
6. `oriental_misto` (inclui tailandesa, vietnamita, coreana, indiana, africana, marroquina, fusion)
7. `arabe_mediterraneo` (inclui Kebab/Shawarma - CORRIGIDO)
8. `mexicano_texmex`
9. `latino_americano` (Argentina, Uruguai, Peru, Cevicheria)
10. `europeu_ocidental` (inclui alta gastronomia, contemporâneo, bistrô)
11. `brasileiro_caseiro` (inclui sopas & caldos)
12. `marmitaria_pf` (inclui refeições congeladas com flag)
13. `churrascaria`
14. `bar_boteco`
15. `cafeteria_padaria`

### Arquétipos Especializados (5)
16. `sorveteria_acaiteria`
17. `doces_sobremesas`
18. `lanches_fastfood`
19. `poke` (NOVO: arquétipo próprio)
20. `saudavel_vegetariano`

### Arquétipos Opcionais (podem ser consolidados)
21. `grelhados_espetinhos` (pode ser tag em churrascaria)
22. `frango_galeteria` (pode ser tag em grelhados ou próprio)
23. `frutos_mar_peixes` (pode ser tag em latino_americano ou próprio)
24. `mercado_emporio` (blueprint específico para mercados)

**Total recomendado: 18-20 arquétipos principais**

---

## Mapeamento Completo: vc_cuisine → Arquétipo

### Mapeamento por Slug (usar slug, não nome)

```php
'hamburgueria-artesanal' => 'hamburgueria',
'hamburgueria-smash' => 'hamburgueria',
'lanchonete' => 'hamburgueria',
'food-truck' => 'hamburgueria',
'culinaria-norte-americana' => 'hamburgueria',

'pizzaria-tradicional' => 'pizzaria',
'pizzaria-napolitana' => 'pizzaria',
'pizzaria-rodizio' => 'pizzaria',
'pizzaria-delivery' => 'pizzaria',
'culinaria-italiana' => 'pizzaria', // quando não for massas

'massas-risotos' => 'massas_risotos',

'culinaria-japonesa' => 'japones',
'sushi-bar' => 'japones',
'temakeria' => 'japones',
'restaurante-de-lamen-ramen' => 'japones',
'izakaya' => 'japones',

'culinaria-chinesa' => 'chines',

'culinaria-tailandesa' => 'oriental_misto',
'culinaria-vietnamita' => 'oriental_misto',
'culinaria-coreana' => 'oriental_misto',
'churrasco-coreano-k-bbq' => 'oriental_misto',
'culinaria-oriental-mista' => 'oriental_misto',
'culinaria-indiana' => 'oriental_misto',
'culinaria-africana' => 'oriental_misto',
'culinaria-marroquina' => 'oriental_misto',
'culinaria-fusion' => 'oriental_misto',

'culinaria-arabe' => 'arabe_mediterraneo',
'culinaria-turca' => 'arabe_mediterraneo',
'culinaria-libanesa' => 'arabe_mediterraneo',
'culinaria-grega' => 'arabe_mediterraneo',
'culinaria-mediterranea' => 'arabe_mediterraneo',
'esfiharia' => 'arabe_mediterraneo',
'kebab-shawarma' => 'arabe_mediterraneo', // CORRIGIDO

'culinaria-mexicana' => 'mexicano_texmex',
'tex-mex' => 'mexicano_texmex',

'culinaria-argentina' => 'latino_americano',
'culinaria-uruguaia' => 'latino_americano',
'culinaria-peruana' => 'latino_americano',
'cevicheria' => 'latino_americano', // também pode usar frutos_mar_peixes

'culinaria-francesa' => 'europeu_ocidental',
'culinaria-portuguesa' => 'europeu_ocidental',
'culinaria-espanhola' => 'europeu_ocidental',
'tapas' => 'europeu_ocidental',
'restaurante-de-alta-gastronomia-fine-dining' => 'europeu_ocidental',
'restaurante-contemporaneo' => 'europeu_ocidental',
'bistro' => 'europeu_ocidental',

'restaurante-brasileiro-caseiro' => 'brasileiro_caseiro',
'comida-mineira' => 'brasileiro_caseiro',
'comida-baiana' => 'brasileiro_caseiro',
'comida-nordestina' => 'brasileiro_caseiro',
'comida-gaucha' => 'brasileiro_caseiro',
'comida-amazonica' => 'brasileiro_caseiro',
'comida-paraense' => 'brasileiro_caseiro',
'comida-caicara' => 'brasileiro_caseiro',
'comida-pantaneira' => 'brasileiro_caseiro',
'comida-caseira' => 'brasileiro_caseiro',
'feijoada' => 'brasileiro_caseiro',
'sopas-caldos' => 'brasileiro_caseiro', // ANEXADO
'restaurante-tropical-praiano' => 'brasileiro_caseiro',

'marmitaria-marmitex' => 'marmitaria_pf',
'prato-feito-pf' => 'marmitaria_pf',
'self-service-por-quilo' => 'marmitaria_pf',
'restaurante-executivo' => 'marmitaria_pf',
'marmita-fitness' => 'marmitaria_pf',
'refeicoes-congeladas' => 'marmitaria_pf', // com flag _vc_service = 'frozen'

'churrascaria-rodizio' => 'churrascaria',
'churrascaria-a-la-carte' => 'churrascaria',
'steakhouse' => 'churrascaria',

'espetinhos' => 'grelhados_espetinhos',
'grelhados' => 'grelhados_espetinhos',
'assados-rotisserie' => 'grelhados_espetinhos',

'galeteria' => 'frango_galeteria',
'frango-assado' => 'frango_galeteria',
'frango-frito-estilo-americano' => 'frango_galeteria',

'frutos-do-mar' => 'frutos_mar_peixes',
'peixes' => 'frutos_mar_peixes',
'peixaria' => 'frutos_mar_peixes',

'bar' => 'bar_boteco',
'boteco' => 'bar_boteco',
'gastrobar' => 'bar_boteco',
'pub' => 'bar_boteco',
'sports-bar-bar-esportivo' => 'bar_boteco',
'bar-de-vinhos-wine-bar' => 'bar_boteco',
'cervejaria-artesanal' => 'bar_boteco',
'choperia' => 'bar_boteco',
'adega-de-bebidas' => 'bar_boteco',
'bar-de-drinks-coquetelaria' => 'bar_boteco',
'bar-de-caipirinha' => 'bar_boteco',
'rooftop-bar' => 'bar_boteco',
'lounge-bar' => 'bar_boteco',
'karaoke-bar' => 'bar_boteco',
'beach-club' => 'bar_boteco',
'hookah-narguile-bar' => 'bar_boteco',
'balada-night-club' => 'bar_boteco',
'comida-de-boteco' => 'bar_boteco',
'petiscos-e-porcoes' => 'bar_boteco',

'cafeteria' => 'cafeteria_padaria',
'coffee-shop-especializado' => 'cafeteria_padaria',
'padaria-tradicional' => 'cafeteria_padaria',
'padaria-gourmet' => 'cafeteria_padaria',
'casa-de-cha' => 'cafeteria_padaria',

'sorveteria' => 'sorveteria_acaiteria',
'gelateria' => 'sorveteria_acaiteria',
'acaieteria' => 'sorveteria_acaiteria',
'yogurteria' => 'sorveteria_acaiteria',

'confeitaria' => 'doces_sobremesas',
'doceria' => 'doces_sobremesas',
'brigaderia' => 'doces_sobremesas',
'brownieria' => 'doces_sobremesas',
'loja-de-donuts' => 'doces_sobremesas',
'casa-de-bolos' => 'doces_sobremesas',
'chocolateria' => 'doces_sobremesas',
'bomboniere' => 'doces_sobremesas',
'creperia-doce' => 'doces_sobremesas',
'waffle-house' => 'doces_sobremesas',

'hot-dog-cachorro-quente' => 'lanches_fastfood',
'sanduiches-baguetes' => 'lanches_fastfood',
'wraps-tortillas' => 'lanches_fastfood',
'salgados-variados' => 'lanches_fastfood',
'coxinha-frituras' => 'lanches_fastfood',
'pastelaria' => 'lanches_fastfood',
'creperia-salgada' => 'lanches_fastfood',
'tapiocaria' => 'lanches_fastfood',
'panquecaria' => 'lanches_fastfood',
'omeleteria' => 'lanches_fastfood',
'quiosque-de-praia' => 'lanches_fastfood',
'trailer-de-lanches' => 'lanches_fastfood',
'refeicao-rapida-fast-food' => 'lanches_fastfood',

'poke' => 'poke', // NOVO

'vegetariano' => 'saudavel_vegetariano',
'vegano' => 'saudavel_vegetariano',
'plant-based' => 'saudavel_vegetariano',
'sem-gluten' => 'saudavel_vegetariano',
'sem-lactose' => 'saudavel_vegetariano',
'organico' => 'saudavel_vegetariano',
'natural-saudavel' => 'saudavel_vegetariano',
'comida-funcional' => 'saudavel_vegetariano',
'low-carb' => 'saudavel_vegetariano',
'comida-fit-saudavel' => 'saudavel_vegetariano',
'saladas-bowls' => 'saudavel_vegetariano',

'mercado-mini-mercado' => 'mercado_emporio',
'emporio' => 'mercado_emporio',
'loja-de-produtos-naturais' => 'mercado_emporio',
'acougue-gourmet' => 'mercado_emporio',
'hortifruti' => 'mercado_emporio',
'loja-de-conveniencia' => 'mercado_emporio',
'loja-de-vinhos-e-destilados' => 'mercado_emporio',
```

### Tags de Estilo/Formato (NÃO são arquétipos)

Estes tipos recebem apenas tags, não blueprint próprio:

```php
// Tags de Formato de Serviço
'delivery-only-dark-kitchen' => ['_vc_style' => 'dark_kitchen'],
'drive-thru' => ['_vc_style' => 'drive_thru'],
'take-away-para-levar' => ['_vc_style' => 'take_away'],
'praca-de-alimentacao-food-court' => ['_vc_style' => 'food_court'],
'rodizio-geral' => ['_vc_service' => 'rodizio'],
'buffet-livre' => ['_vc_service' => 'buffet'],
'a-la-carte' => ['_vc_service' => 'a_la_carte'],

// Tags de Experiência/Audiência
'restaurante-familiar-kids-friendly' => ['_vc_audience' => 'family'],
'restaurante-romantico' => ['_vc_occasion' => 'romantic'],
'restaurante-tematico' => ['_vc_occasion' => 'themed'],
'restaurante-com-musica-ao-vivo' => ['_vc_occasion' => 'live_music'],
```

---

## Estrutura de Implementação

### 1. Criar `Cuisine_Helper`

**Arquivo:** `inc/Utils/Cuisine_Helper.php`

**Métodos:**
- `get_archetype_for_cuisine( int $term_id ): ?string` - Retorna arquétipo do termo
- `set_archetype_for_cuisine( int $term_id, string $archetype ): void` - Define arquétipo
- `get_archetypes_for_restaurant( int $restaurant_id ): array` - Retorna arquétipos do restaurante
- `get_style_tags_for_cuisine( int $term_id ): array` - Retorna tags de estilo

**Mapeamento interno:** Array associativo `slug => archetype` usando o mapeamento completo acima.

### 2. Criar `Cuisine_Menu_Blueprints`

**Arquivo:** `inc/Config/Cuisine_Menu_Blueprints.php`

**Estrutura:**
```php
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
    // ... todos os 18-20 arquétipos
];
```

**Blueprints para os 20 arquétipos principais** com categorias "vida real".

### 3. Script de Migração

**Arquivo:** `inc/CLI/Migrate_Cuisine_Archetypes.php`

**Comando WP-CLI:** `wp vemcomer migrate-cuisine-archetypes`

**Funcionalidade:**
- Para cada termo `vc_cuisine` existente:
  - Busca slug do termo
  - Mapeia para arquétipo usando `Cuisine_Helper`
  - Salva `_vc_cuisine_archetype` no term meta
  - Para tipos de estilo/formato, salva tags (`_vc_style`, `_vc_service`, `_vc_audience`, `_vc_occasion`)
- Loga estatísticas: quantos mapeados, quantos com tags, quantos sem mapeamento

### 4. Refatorar `Menu_Category_Catalog_Seeder`

**Arquivo:** `inc/Utils/Menu_Category_Catalog_Seeder.php`

**Mudanças:**
- Substituir `get_categories_data()` que usa `cuisine_types` (nomes)
- Nova lógica:
  1. Carrega blueprints de `Cuisine_Menu_Blueprints::all()`
  2. Para cada arquétipo:
     - Cria/atualiza termos `vc_menu_category`
     - Marca `_vc_is_catalog_category = '1'`
     - Salva `_vc_recommended_for_archetypes = ['hamburgueria']` (JSON array)
  3. Opcionalmente, ainda gera `_vc_recommended_for_cuisines` para compatibilidade:
     - Para cada categoria de catálogo, busca todas as `vc_cuisine` com aquele arquétipo
     - Salva lista de IDs no meta

**Método novo:** `get_categories_data_from_blueprints(): array`

### 5. Atualizar Onboarding

**Arquivos:**
- `templates/marketplace/onboarding-wizard.php`
- `inc/REST/Onboarding_Controller.php`
- `inc/REST/Menu_Categories_Controller.php`

**Mudanças:**
- No passo 1 (tipo de restaurante): ao selecionar cuisines, já resolve para arquétipos
- No passo 4 (categorias): 
  - Busca arquétipos do restaurante via `Cuisine_Helper::get_archetypes_for_restaurant()`
  - Para cada arquétipo, busca categorias recomendadas:
    - Via `_vc_recommended_for_archetypes` OU
    - Direto do blueprint em `Cuisine_Menu_Blueprints`
  - Exibe categorias sugeridas baseadas nos arquétipos

**Endpoint:** `Menu_Categories_Controller::get_recommended_categories()`
- Atualizar para usar arquétipos em vez de `_vc_recommended_for_cuisines`

---

## Blueprints de Exemplo (5 principais)

### hamburgueria
```php
[
    ['name' => 'Destaques da casa', 'order' => 10],
    ['name' => 'Combos', 'order' => 20],
    ['name' => 'Hambúrgueres clássicos', 'order' => 30],
    ['name' => 'Hambúrgueres especiais', 'order' => 40],
    ['name' => 'Monte seu burger', 'order' => 50],
    ['name' => 'Acompanhamentos', 'order' => 60],
    ['name' => 'Sobremesas', 'order' => 70],
    ['name' => 'Bebidas', 'order' => 80],
]
```

### pizzaria
```php
[
    ['name' => 'Entradas / Porções', 'order' => 10],
    ['name' => 'Pizzas salgadas', 'order' => 20],
    ['name' => 'Pizzas doces', 'order' => 30],
    ['name' => 'Combos', 'order' => 40],
    ['name' => 'Bebidas', 'order' => 50],
    ['name' => 'Sobremesas', 'order' => 60],
]
```

### japones
```php
[
    ['name' => 'Entradas', 'order' => 10],
    ['name' => 'Sushis e sashimis', 'order' => 20],
    ['name' => 'Temakis', 'order' => 30],
    ['name' => 'Pratos quentes', 'order' => 40],
    ['name' => 'Lámen / Ramen', 'order' => 50],
    ['name' => 'Bebidas', 'order' => 60],
]
```

### marmitaria_pf
```php
[
    ['name' => 'Marmitas do dia', 'order' => 10],
    ['name' => 'Pratos executivos', 'order' => 20],
    ['name' => 'Marmitas fitness', 'order' => 30],
    ['name' => 'Acompanhamentos', 'order' => 40],
    ['name' => 'Sobremesas', 'order' => 50],
    ['name' => 'Bebidas', 'order' => 60],
]
```

### poke
```php
[
    ['name' => 'Pokes prontos', 'order' => 10],
    ['name' => 'Monte seu poke', 'order' => 20],
    ['name' => 'Bases', 'order' => 30],
    ['name' => 'Proteínas', 'order' => 40],
    ['name' => 'Toppings', 'order' => 50],
    ['name' => 'Molhos', 'order' => 60],
    ['name' => 'Bebidas', 'order' => 70],
]
```

---

## Ordem de Implementação

1. **Criar `Cuisine_Helper`** com mapeamento completo
2. **Criar `Cuisine_Menu_Blueprints`** com todos os 20 blueprints
3. **Criar script de migração** para popular `_vc_cuisine_archetype`
4. **Refatorar `Menu_Category_Catalog_Seeder`** para usar blueprints
5. **Atualizar onboarding** para usar arquétipos
6. **Testar e validar** com dados reais

---

## Compatibilidade

- **Manter `_vc_recommended_for_cuisines`** por enquanto (compatibilidade)
- **Adicionar `_vc_recommended_for_archetypes`** (novo padrão)
- Migração gradual: sistema funciona com ambos, depois remove o legado

