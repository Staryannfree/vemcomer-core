# Estrutura do Banco de Dados de Adicionais (Catálogo)

## Visão Geral

Este sistema permite criar um catálogo global de grupos de adicionais que são recomendados automaticamente para lojistas baseado nas categorias do seu restaurante.

## Estrutura de Dados

### 1. Grupos de Adicionais do Catálogo (`vc_addon_catalog_group`)

**Custom Post Type:** `vc_addon_catalog_group`

**Campos:**
- `post_title` - Nome do grupo (ex: "Adicionais de hambúrguer")
- `post_content` - Descrição do grupo
- `_vc_selection_type` - Tipo de seleção: `'single'` ou `'multiple'`
- `_vc_min_select` - Seleção mínima (número)
- `_vc_max_select` - Seleção máxima (número, 0 = ilimitado)
- `_vc_is_required` - Se é obrigatório (`'1'` ou `'0'`)
- `_vc_is_active` - Se está ativo (`'1'` ou `'0'`)

**Taxonomia:**
- `vc_cuisine` - Vincula o grupo às categorias de restaurantes

**Exemplo:**
- Grupo: "Adicionais de hambúrguer"
- Vinculado às categorias: "Hamburgueria artesanal", "Hamburgueria smash", "Lanchonete"
- Tipo: Múltipla seleção
- Min: 0, Max: 0 (ilimitado)

### 2. Itens de Adicionais do Catálogo (`vc_addon_catalog_item`)

**Custom Post Type:** `vc_addon_catalog_item`

**Campos:**
- `post_title` - Nome do item (ex: "Queijo extra")
- `post_content` - Descrição do item
- `_vc_group_id` - ID do grupo ao qual pertence (FK para `vc_addon_catalog_group`)
- `_vc_default_price` - Preço padrão sugerido (ex: "5.00")
- `_vc_allow_quantity` - Se permite quantidade (`'1'` ou `'0'`)
- `_vc_max_quantity` - Quantidade máxima (número)
- `_vc_is_active` - Se está ativo (`'1'` ou `'0'`)

**Exemplo:**
- Item: "Queijo extra"
- Grupo: "Adicionais de hambúrguer"
- Preço padrão: R$ 5,00
- Permite quantidade: Sim (máx: 3)

### 3. Relacionamento com Categorias

Os grupos são vinculados às categorias de restaurantes (`vc_cuisine`) através da taxonomia do WordPress. Quando um lojista tem um restaurante com categorias específicas, os grupos vinculados a essas categorias aparecem como recomendações.

**Fluxo:**
1. Lojista cadastra restaurante e escolhe categorias: "Hamburgueria artesanal", "Bar"
2. Sistema busca grupos do catálogo vinculados a essas categorias
3. Lojista vê grupos recomendados ao criar produtos
4. Lojista pode copiar grupos do catálogo para sua loja

## Endpoints REST API

### GET `/wp-json/vemcomer/v1/addon-catalog/recommended-groups`

Retorna grupos recomendados para o restaurante do lojista logado.

**Resposta:**
```json
{
  "success": true,
  "groups": [
    {
      "id": 123,
      "name": "Adicionais de hambúrguer",
      "description": "Adicione extras ao seu hambúrguer",
      "selection_type": "multiple",
      "min_select": 0,
      "max_select": 0,
      "is_required": false
    }
  ]
}
```

### GET `/wp-json/vemcomer/v1/addon-catalog/groups/{id}/items`

Retorna os itens de um grupo do catálogo.

**Resposta:**
```json
{
  "success": true,
  "group": {
    "id": 123,
    "name": "Adicionais de hambúrguer",
    "selection_type": "multiple",
    "min_select": 0,
    "max_select": 0
  },
  "items": [
    {
      "id": 456,
      "name": "Queijo extra",
      "description": "Adicione mais queijo",
      "default_price": 5.00,
      "allow_quantity": true,
      "max_quantity": 3
    }
  ]
}
```

### POST `/wp-json/vemcomer/v1/addon-catalog/groups/{id}/copy-to-store`

Copia um grupo do catálogo para a loja do lojista.

**Resposta:**
```json
{
  "success": true,
  "message": "Grupo copiado com sucesso para sua loja.",
  "group_id": 789,
  "items_count": 5
}
```

## Como Usar

### 1. Criar Grupos no Catálogo (Admin)

1. Acesse **Catálogo de Adicionais** no menu do WordPress
2. Crie um novo grupo (ex: "Adicionais de hambúrguer")
3. Configure:
   - Tipo de seleção (única ou múltipla)
   - Seleção mínima/máxima
   - Se é obrigatório
4. Na seção **Categorias**, selecione as categorias de restaurantes para as quais este grupo é recomendado
5. Publique o grupo

### 2. Criar Itens no Catálogo (Admin)

1. Acesse **Itens do Catálogo** no menu do WordPress
2. Crie um novo item (ex: "Queijo extra")
3. Selecione o grupo ao qual pertence
4. Configure:
   - Preço padrão
   - Se permite quantidade
   - Quantidade máxima
5. Publique o item

### 3. Lojista Usa Grupos Recomendados

1. Ao criar um produto, o lojista clica em "+ Adicionais"
2. O sistema busca grupos recomendados baseados nas categorias do restaurante
3. Lojista vê grupos sugeridos e pode:
   - Copiar um grupo completo (cria cópia na loja)
   - Criar grupos personalizados
   - Editar grupos copiados

## Integração com Sistema Existente

Os grupos copiados para a loja são salvos como `vc_product_modifier` (o sistema existente de modificadores), mantendo compatibilidade com o código atual.

**Meta fields adicionais:**
- `_vc_catalog_group_id` - ID do grupo original do catálogo
- `_vc_catalog_item_id` - ID do item original do catálogo (para itens)

## Próximos Passos

1. Criar interface no frontend para lojistas verem grupos recomendados
2. Adicionar funcionalidade de "copiar grupo" no modal de adicionais
3. Permitir que lojistas editem grupos copiados
4. Adicionar seeders com grupos pré-configurados para categorias comuns

