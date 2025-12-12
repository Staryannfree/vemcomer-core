# Arquitetura do VemComer Core

## Princípios Fundamentais

### 1. Fonte Única de Verdade para Restaurantes

**Regra Obrigatória:** Para descobrir o restaurante de um usuário, use **sempre** `Restaurant_Helper::get_restaurant_for_user()`.

```php
use VC\Utils\Restaurant_Helper;

// ✅ CORRETO
$restaurant = Restaurant_Helper::get_restaurant_for_user();
$restaurant_id = Restaurant_Helper::get_restaurant_id_for_user();

// ❌ ERRADO - NÃO FAÇA ISSO
$restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );
```

**Ordem de busca do `Restaurant_Helper`:**
1. Filtro `vemcomer/restaurant_id_for_user` (permite override externo)
2. User meta `vc_restaurant_id` (fonte oficial)
3. Fallback: busca restaurante onde o usuário é `post_author` (e auto-corrige o meta)

**Arquivos que devem usar o helper:**
- Todos os controllers REST
- Todos os frontend classes
- Funções globais (`vc_marketplace_current_restaurant()`)

---

### 2. Meta Oficial para Produtos ↔ Restaurante

**Meta Oficial:** `_vc_restaurant_id`

**Meta Legado:** `_vc_menu_item_restaurant` (mantido em sincronia para compatibilidade, mas não deve ser usado diretamente)

**Regra:** Ao criar ou atualizar um produto, use **sempre** `Restaurant_Helper::attach_restaurant_to_product()`:

```php
use VC\Utils\Restaurant_Helper;

// ✅ CORRETO
Restaurant_Helper::attach_restaurant_to_product( $product_id, $restaurant_id );

// ❌ ERRADO - NÃO FAÇA ISSO
update_post_meta( $product_id, '_vc_restaurant_id', $restaurant_id );
update_post_meta( $product_id, '_vc_menu_item_restaurant', $restaurant_id );
```

**Queries de produtos por restaurante:**
```php
$args = [
    'post_type'      => 'vc_menu_item',
    'meta_query'     => [
        [
            'key'   => '_vc_restaurant_id',
            'value' => $restaurant_id,
        ],
    ],
];
```

---

### 3. Regras de Categorias de Cardápio

**Categoria de Catálogo Global:**
- Meta: `_vc_is_catalog_category = '1'`
- **Sem** `_vc_restaurant_id`
- Disponível para todos os restaurantes

**Categoria Específica do Restaurante:**
- Meta: `_vc_is_catalog_category` vazio ou `'0'`
- Meta: `_vc_restaurant_id = ID do restaurante`
- Disponível apenas para o restaurante dono

**Regra:** Use **sempre** `Category_Helper` para criar e buscar categorias:

```php
use VC\Utils\Category_Helper;

// ✅ CORRETO - Criar categoria de restaurante
$term_id = Category_Helper::create_restaurant_category( $restaurant_id, 'Nome da Categoria' );

// ✅ CORRETO - Buscar categorias do restaurante
$categories = Category_Helper::query_restaurant_categories( $restaurant_id );

// ✅ CORRETO - Verificar se é categoria de catálogo
$is_catalog = Category_Helper::is_catalog_category( $term_id );

// ✅ CORRETO - Verificar ownership
$belongs = Category_Helper::belongs_to_restaurant( $term_id, $restaurant_id );
```

**Queries de categorias por restaurante:**
```php
$categories = get_terms( [
    'taxonomy'   => 'vc_menu_category',
    'meta_query' => [
        'relation' => 'AND',
        [
            'key'     => '_vc_restaurant_id',
            'value'   => $restaurant_id,
        ],
        [
            'key'     => '_vc_is_catalog_category',
            'value'   => '1',
            'compare' => '!=',
        ],
    ],
] );
```

---

## Camada de Serviços

### Services Disponíveis

#### `Restaurant_Status_Service`
Valida status do restaurante (ativo/inativo, produtos, checks).

```php
use VC\Services\Restaurant_Status_Service;

$status = Restaurant_Status_Service::get_status_for_user();
// Retorna: ['active' => bool, 'products' => int, 'checks' => array, 'reason' => string]
```

#### `Menu_Items_Service`
Gerencia criação, atualização e deleção de produtos.

```php
use VC\Services\Menu_Items_Service;

$service = new Menu_Items_Service();
$product_id = $service->create( $data, $restaurant_id );
$service->update( $product_id, $data, $restaurant_id );
$service->delete( $product_id, $restaurant_id );
```

#### `Menu_Categories_Service`
Gerencia criação, atualização e deleção de categorias.

```php
use VC\Services\Menu_Categories_Service;

$service = new Menu_Categories_Service();
$term_id = $service->create( $data, $restaurant_id );
$service->update( $term_id, $data, $restaurant_id );
$service->delete( $term_id, $restaurant_id );
```

---

## Helpers Disponíveis

### `Restaurant_Helper`
Helper central para restaurantes.

**Métodos:**
- `get_restaurant_for_user( $user_id = 0 ): ?WP_Post` - Retorna o restaurante do usuário
- `get_restaurant_id_for_user( $user_id = 0 ): int` - Retorna apenas o ID
- `attach_restaurant_to_product( $product_id, $restaurant_id ): void` - Anexa restaurante a produto

### `Category_Helper`
Helper para categorias de cardápio.

**Métodos:**
- `create_restaurant_category( $restaurant_id, $name, $slug = '' ): int|WP_Error`
- `query_restaurant_categories( $restaurant_id ): array`
- `is_catalog_category( $term_id ): bool`
- `belongs_to_restaurant( $term_id, $restaurant_id ): bool`

### `Migration_Helper`
Helper para migrações de dados legados.

**Métodos:**
- `migrate_legacy_product_restaurant_meta(): array` - Migra produtos antigos
- `count_products_needing_migration(): int` - Conta produtos que precisam migração

---

## Endpoints de Debug

### `/wp-json/vemcomer/v1/debug/restaurant-status`
Retorna status completo do restaurante do usuário logado.

**Resposta:**
```json
{
  "active": false,
  "products": 3,
  "checks": {
    "min_products": false,
    "has_whatsapp": true,
    "has_address": true,
    "has_hours": false
  },
  "restaurant_id": 123,
  "reason": "Cadastre pelo menos 5 produtos..."
}
```

### `/wp-json/vemcomer/v1/debug/restaurant-relations`
Retorna todas as relações do restaurante (produtos, categorias, contagens).

**Resposta:**
```json
{
  "restaurant_id": 123,
  "restaurant_title": "Nome do Restaurante",
  "products_total": 10,
  "categories_total": 3,
  "categories": [...],
  "products_by_category": {...},
  "status": {...}
}
```

---

## Migração de Dados Legados

### Produtos Antigos

Para migrar produtos que usam `_vc_menu_item_restaurant` para `_vc_restaurant_id`:

```php
use VC\Utils\Migration_Helper;

$stats = Migration_Helper::migrate_legacy_product_restaurant_meta();
// Retorna: ['migrated' => int, 'skipped' => int, 'errors' => int]

$count = Migration_Helper::count_products_needing_migration();
```

**Uso via WP-CLI (futuro):**
```bash
wp vemcomer migrate-products
```

---

## Boas Práticas

### 1. Sempre use Helpers
Não acesse metas diretamente. Use os helpers que garantem consistência.

### 2. Validação de Ownership
Sempre valide que um produto/categoria pertence ao restaurante antes de editar/deletar.

### 3. Services para Lógica de Negócio
Controllers devem apenas validar input, chamar services e formatar resposta JSON.

### 4. Não Crie Chamadas REST Internas
Ao invés de criar `WP_REST_Request` internamente, chame os services diretamente.

---

## Estrutura de Arquivos

```
inc/
├── Utils/
│   ├── Restaurant_Helper.php      # Helper central de restaurantes
│   ├── Category_Helper.php        # Helper de categorias
│   └── Migration_Helper.php      # Helper de migração
├── Services/
│   ├── Restaurant_Status_Service.php  # Status do restaurante
│   ├── Menu_Items_Service.php          # Serviço de produtos
│   └── Menu_Categories_Service.php    # Serviço de categorias
└── REST/
    ├── Menu_Items_Controller.php      # Usa Menu_Items_Service
    ├── Menu_Categories_Controller.php # Usa Menu_Categories_Service
    └── Debug_Controller.php          # Endpoints de debug
```

---

## Checklist de Implementação

Ao criar novos controllers ou funcionalidades:

- [ ] Usa `Restaurant_Helper::get_restaurant_for_user()` para obter restaurante
- [ ] Usa `Restaurant_Helper::attach_restaurant_to_product()` ao criar produtos
- [ ] Usa `Category_Helper` para criar/buscar categorias
- [ ] Valida ownership antes de editar/deletar
- [ ] Usa services para lógica de negócio (não lógica direta no controller)
- [ ] Não cria chamadas REST internas (usa services diretamente)

---

## Exemplos de Uso

### Criar Produto
```php
use VC\Services\Menu_Items_Service;
use VC\Utils\Restaurant_Helper;

$restaurant_id = Restaurant_Helper::get_restaurant_id_for_user();
$service = new Menu_Items_Service();
$product_id = $service->create( [
    'title' => 'Pizza Margherita',
    'price' => '29.90',
    'category_id' => 5,
], $restaurant_id );
```

### Criar Categoria
```php
use VC\Services\Menu_Categories_Service;
use VC\Utils\Restaurant_Helper;

$restaurant_id = Restaurant_Helper::get_restaurant_id_for_user();
$service = new Menu_Categories_Service();
$term_id = $service->create( [
    'name' => 'Pizzas',
    'order' => 1,
], $restaurant_id );
```

### Verificar Status da Loja
```php
use VC\Services\Restaurant_Status_Service;

$status = Restaurant_Status_Service::get_status_for_user();
if ( ! $status['active'] ) {
    echo $status['reason']; // "Cadastre pelo menos 5 produtos..."
}
```

---

## Changelog

### v0.61+ - Padronização Completa
- ✅ Unificação de `Restaurant_Helper` em todos os controllers
- ✅ Padronização de `_vc_restaurant_id` para produtos
- ✅ Criação de `Category_Helper` para categorias
- ✅ Criação de camada de services
- ✅ Endpoints de debug adicionados
- ✅ Documentação de arquitetura criada

