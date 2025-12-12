# Setup do Sistema de Arquétipos

## Passos para Configurar

### 1. Rodar Migração de Arquétipos

Execute o comando WP-CLI para mapear todos os tipos de restaurante para arquétipos:

```bash
wp vemcomer migrate-cuisine-archetypes
```

**OU** execute o script PHP diretamente:

```bash
php scripts/setup-archetypes.php
```

Este comando vai:
- Mapear todos os termos `vc_cuisine` para arquétipos
- Salvar tags de estilo/formato onde aplicável
- Mostrar estatísticas do que foi mapeado

### 2. Re-seed do Catálogo de Categorias

Execute o comando para limpar e recriar o catálogo usando os novos blueprints:

```bash
wp vemcomer reseed-menu-categories
```

**OU** o script `setup-archetypes.php` já faz isso automaticamente.

Este comando vai:
- Limpar categorias de catálogo existentes (apenas as que não têm produtos)
- Recriar todas as categorias baseadas nos blueprints de arquétipos
- Vincular categorias aos arquétipos via `_vc_recommended_for_archetypes`
- Manter compatibilidade com `_vc_recommended_for_cuisines`

### 3. Testar o Onboarding

1. Acesse o onboarding como lojista
2. No **passo 1**, selecione um tipo de restaurante (ex: "Hamburgueria artesanal")
3. Avance até o **passo 4** (categorias)
4. Verifique se as categorias recomendadas aparecem corretamente

### 4. Debug (se necessário)

Se algumas categorias não aparecerem, use o endpoint de debug:

```
GET /wp-json/vemcomer/v1/debug/archetypes?restaurant_id=123
```

Este endpoint mostra:
- Cuisines do restaurante e seus arquétipos
- Arquétipos resolvidos do restaurante
- Categorias de catálogo e para quais arquétipos são recomendadas
- Se há match entre arquétipos do restaurante e categorias

## Problemas Comuns

### Categorias não aparecem no passo 4

**Causa:** O tipo de restaurante selecionado não tem arquétipo mapeado ou não foi migrado ainda.

**Solução:**
1. Verifique se a migração foi executada: `wp vemcomer migrate-cuisine-archetypes`
2. Verifique o slug do tipo de restaurante no debug endpoint
3. Se o slug não estiver no mapeamento, adicione em `Cuisine_Helper::get_slug_to_archetype_map()`

### Categorias aparecem mas estão erradas

**Causa:** O seeder não foi executado com os novos blueprints.

**Solução:**
1. Execute o re-seed: `wp vemcomer reseed-menu-categories`
2. Limpe o cache do WordPress
3. Teste novamente

## Estrutura de Dados

### Arquétipos salvos em:
- Term meta: `_vc_cuisine_archetype` (ex: `'hamburgueria'`)

### Categorias vinculadas via:
- Term meta: `_vc_recommended_for_archetypes` (JSON array: `['hamburgueria', 'pizzaria']`)
- Term meta: `_vc_recommended_for_cuisines` (JSON array de IDs - compatibilidade)

### Tags de estilo salvos em:
- Term meta: `_vc_style`, `_vc_service`, `_vc_audience`, `_vc_occasion`

