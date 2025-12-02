# Guia de Testes - Funcionalidades de Adicionais

Este documento descreve como testar cada funcionalidade implementada no sistema de adicionais.

## Pré-requisitos

1. Ter um usuário com role `lojista` logado
2. Ter um restaurante vinculado ao usuário
3. Ter pelo menos uma categoria de restaurante configurada
4. Ter pelo menos um produto cadastrado

---

## 1. 🎯 Wizard de Onboarding para Adicionais

### Como testar:

1. **Preparar ambiente:**
   - Acesse como lojista que ainda não configurou adicionais
   - OU delete todos os grupos de adicionais do restaurante (via admin WordPress)
   - OU delete o user meta `vc_addons_onboarding_completed` do usuário

2. **Acessar página:**
   - Vá para `/painel-restaurante/gestao-cardapio/` (ou a URL da gestão de cardápio)

3. **Verificar banner:**
   - Deve aparecer um banner verde no topo da página
   - Texto: "⭐ Configure seus primeiros adicionais!"
   - Botões: "Começar Configuração" e "Depois"

4. **Testar wizard:**
   - Clique em "Começar Configuração"
   - Modal deve abrir com lista de grupos básicos recomendados
   - Cada grupo deve ter um checkbox
   - Selecione alguns grupos (ex: "Adicionais de Hambúrguer", "Bebida do Combo")
   - Clique em "Configurar Grupos Selecionados"
   - Deve mostrar mensagem de sucesso
   - Página deve recarregar
   - Banner deve desaparecer

5. **Testar "Depois":**
   - Recarregue a página (ou delete o user meta novamente)
   - Clique em "Depois"
   - Banner deve desaparecer

### Endpoints testados:
- `GET /wp-json/vemcomer/v1/addon-catalog/needs-onboarding`
- `POST /wp-json/vemcomer/v1/addon-catalog/setup-onboarding`

---

## 2. ⭐ Salvar Grupo como Modelo

### Como testar:

1. **Preparar:**
   - Tenha pelo menos um produto com um grupo de adicionais vinculado

2. **Acessar:**
   - Vá para `/painel-restaurante/gestao-cardapio/`
   - Encontre um produto que tenha adicionais (badges verdes)

3. **Salvar como modelo:**
   - No badge do grupo, clique no ícone ⭐ (estrela)
   - Deve aparecer confirmação
   - Confirme
   - Deve mostrar mensagem: "Grupo salvo como modelo com sucesso!"

4. **Verificar modelo salvo:**
   - Clique em "+ Adicionais" em qualquer produto
   - Vá para a tab "Meus Modelos"
   - Deve aparecer o grupo salvo na lista
   - Deve ter botão "Usar" ao lado

5. **Usar modelo:**
   - Clique em "Usar" no modelo
   - Deve aplicar o grupo ao produto atual
   - Página deve recarregar mostrando o grupo vinculado

### Endpoints testados:
- `POST /wp-json/vemcomer/v1/addon-catalog/store-groups/{id}/save-as-template`
- `GET /wp-json/vemcomer/v1/addon-catalog/my-templates`

---

## 3. 🍔 Templates de Combo

### Como testar:

1. **No WordPress Admin:**
   - Vá para `wp-admin`
   - Menu: "Itens do Cardápio"
   - Crie um novo item OU edite um existente

2. **Definir tipo:**
   - Na sidebar direita, procure por "Tipo de Produto"
   - Deve ter duas opções:
     - ○ Produto Simples
     - ○ Combo
   - Selecione "Combo"
   - Salve o produto

3. **Verificar salvamento:**
   - Edite o produto novamente
   - O tipo "Combo" deve estar selecionado
   - Verifique no banco de dados:
     ```sql
     SELECT * FROM wp_postmeta WHERE post_id = [ID_DO_PRODUTO] AND meta_key = '_vc_product_type';
     ```
   - Deve retornar `combo`

4. **Usar no frontend:**
   - O tipo pode ser usado para lógica de exibição/validação
   - Exemplo: combos podem ter regras diferentes de adicionais

### Endpoints testados:
- Meta field `_vc_product_type` salvo via WordPress admin

---

## 4. 📋 Aplicar Grupo a Múltiplos Produtos

### Como testar:

1. **Preparar:**
   - Tenha pelo menos 3 produtos cadastrados
   - Tenha pelo menos um produto com um grupo de adicionais

2. **Acessar:**
   - Vá para `/painel-restaurante/gestao-cardapio/`
   - Encontre um produto com adicionais

3. **Abrir modal:**
   - No badge do grupo, clique no ícone 📋 (clipboard)
   - Modal deve abrir: "Aplicar Grupo a Múltiplos Produtos"
   - Deve mostrar lista de todos os produtos com checkboxes

4. **Selecionar produtos:**
   - Marque 2-3 produtos diferentes
   - Clique em "Aplicar aos Selecionados"
   - Deve pedir confirmação
   - Confirme

5. **Verificar resultado:**
   - Deve mostrar mensagem de sucesso
   - Página deve recarregar
   - Os produtos selecionados devem ter o grupo vinculado
   - Verifique os badges de adicionais em cada produto

6. **Testar produto já vinculado:**
   - Tente aplicar o mesmo grupo a um produto que já tem
   - Deve pular esse produto (skipped_count)

### Endpoints testados:
- `POST /wp-json/vemcomer/v1/addon-catalog/apply-group-to-products`

---

## 5. 🔄 Duplicar Adicionais de Outro Produto

### Como testar:

1. **Preparar:**
   - Tenha pelo menos 2 produtos
   - Um produto deve ter grupos de adicionais configurados
   - Outro produto não deve ter adicionais (ou ter diferentes)

2. **Acessar:**
   - Vá para `/painel-restaurante/gestao-cardapio/`
   - Clique em "+ Adicionais" no produto que NÃO tem adicionais

3. **Copiar de outro produto:**
   - No modal, vá para a tab "Grupos Recomendados"
   - Role até a seção "Ou copiar de outro produto"
   - Deve ter um select com lista de produtos
   - Selecione o produto que TEM adicionais

4. **Executar cópia:**
   - Clique em "Copiar Adicionais"
   - Deve pedir confirmação
   - Confirme
   - Deve mostrar mensagem de sucesso

5. **Verificar resultado:**
   - Página deve recarregar
   - O produto deve ter os mesmos grupos de adicionais do produto origem
   - Verifique os badges de adicionais

6. **Testar duplicação:**
   - Tente copiar do mesmo produto novamente
   - Deve mostrar erro ou pular grupos já vinculados

### Endpoints testados:
- `POST /wp-json/vemcomer/v1/addon-catalog/products/{id}/copy-addons-from/{source_id}`

---

## 6. 🏷️ Tags Básico/Avançado

### Como testar:

1. **Verificar grupos básicos:**
   - Vá para `/painel-restaurante/gestao-cardapio/`
   - Clique em "+ Adicionais" em qualquer produto
   - Na tab "Grupos Recomendados"
   - Deve aparecer seção: "⭐ Grupos Básicos (Recomendados)"
   - Grupos básicos devem ter borda verde e fundo claro

2. **Verificar grupos avançados:**
   - Role a página
   - Deve ter botão: "⚙️ Ver grupos avançados (X)"
   - Clique no botão
   - Deve expandir mostrando grupos avançados
   - Grupos avançados devem ter borda cinza

3. **Testar toggle:**
   - Clique novamente no botão
   - Deve colapsar os grupos avançados
   - Texto deve mudar para "Ver grupos avançados"

4. **Verificar no admin WordPress:**
   - Vá para `wp-admin`
   - Menu: "Grupos de Adicionais (Catálogo)"
   - Edite um grupo
   - Deve ter campo: "Nível de Dificuldade"
   - Opções: "⭐ Básico" ou "⚙️ Avançado"
   - Altere e salve
   - Verifique na lista de grupos (coluna "Nível")

### Endpoints testados:
- `GET /wp-json/vemcomer/v1/addon-catalog/recommended-groups` (retorna `difficulty_level`)

---

## 7. ✏️ Editar Preços dos Itens (Funcionalidade Existente)

### Como testar:

1. **Preparar:**
   - Tenha um produto com grupo de adicionais vinculado

2. **Abrir modal de edição:**
   - No badge do grupo, clique no ícone ✏️ (lápis)
   - Modal deve abrir: "Editar Preços dos Itens"

3. **Editar preços:**
   - Deve mostrar lista de itens do grupo
   - Cada item deve ter campo de preço
   - Altere alguns preços
   - Clique em "Salvar Preços"

4. **Verificar salvamento:**
   - Deve mostrar mensagem de sucesso
   - Modal deve fechar
   - Página deve recarregar
   - Os preços devem estar atualizados

### Endpoints testados:
- `GET /wp-json/vemcomer/v1/addon-catalog/store-groups/{id}/items`
- `PUT /wp-json/vemcomer/v1/addon-catalog/store-groups/{id}/items/prices`

---

## 8. ❌ Remover Grupo de Produto (Funcionalidade Existente)

### Como testar:

1. **Preparar:**
   - Tenha um produto com grupo de adicionais vinculado

2. **Remover:**
   - No badge do grupo, clique no ícone × (X)
   - Deve pedir confirmação
   - Confirme

3. **Verificar:**
   - Grupo deve desaparecer do produto
   - Página deve atualizar
   - O grupo não deve mais aparecer nos badges

### Endpoints testados:
- `DELETE /wp-json/vemcomer/v1/addon-catalog/unlink-group-from-product`

---

## Checklist de Testes Rápidos

Use este checklist para garantir que tudo está funcionando:

- [ ] Banner de onboarding aparece quando não há grupos
- [ ] Wizard de onboarding cria grupos automaticamente
- [ ] Grupos básicos aparecem destacados em verde
- [ ] Grupos avançados estão colapsáveis
- [ ] Ícone ⭐ salva grupo como modelo
- [ ] Tab "Meus Modelos" lista grupos salvos
- [ ] Ícone 📋 abre modal para aplicar a múltiplos produtos
- [ ] Select "Copiar de outro produto" lista produtos
- [ ] Cópia de adicionais funciona corretamente
- [ ] Ícone ✏️ abre modal de edição de preços
- [ ] Ícone × remove grupo do produto
- [ ] Tipo de produto (Combo) salva no admin WordPress

---

## Troubleshooting

### Banner de onboarding não aparece:
- Verifique se há grupos configurados: `SELECT * FROM wp_posts WHERE post_type = 'vc_product_modifier' AND post_author = [USER_ID]`
- Verifique user meta: `SELECT * FROM wp_usermeta WHERE user_id = [USER_ID] AND meta_key = 'vc_addons_onboarding_completed'`

### Grupos não aparecem:
- Verifique se o restaurante tem categorias vinculadas
- Verifique se os grupos do catálogo estão ativos (`_vc_is_active = '1'`)
- Verifique se os grupos estão vinculados às categorias corretas

### Erro 403 ao salvar:
- Verifique se o usuário tem role `lojista`
- Verifique se o restaurante está vinculado ao usuário (`vc_restaurant_id`)

### Preços não salvam:
- Verifique permissões do usuário
- Verifique se o grupo pertence ao restaurante do usuário
- Verifique logs do WordPress para erros PHP

---

## Testes via API (Postman/Insomnia)

### 1. Verificar necessidade de onboarding:
```
GET /wp-json/vemcomer/v1/addon-catalog/needs-onboarding
Headers:
  X-WP-Nonce: [nonce]
```

### 2. Setup onboarding:
```
POST /wp-json/vemcomer/v1/addon-catalog/setup-onboarding
Headers:
  Content-Type: application/json
  X-WP-Nonce: [nonce]
Body:
{
  "group_ids": [123, 456, 789]
}
```

### 3. Salvar como modelo:
```
POST /wp-json/vemcomer/v1/addon-catalog/store-groups/123/save-as-template
Headers:
  X-WP-Nonce: [nonce]
```

### 4. Listar modelos:
```
GET /wp-json/vemcomer/v1/addon-catalog/my-templates
Headers:
  X-WP-Nonce: [nonce]
```

### 5. Aplicar a múltiplos produtos:
```
POST /wp-json/vemcomer/v1/addon-catalog/apply-group-to-products
Headers:
  Content-Type: application/json
  X-WP-Nonce: [nonce]
Body:
{
  "group_id": 123,
  "product_ids": [10, 20, 30]
}
```

### 6. Copiar de outro produto:
```
POST /wp-json/vemcomer/v1/addon-catalog/products/10/copy-addons-from/20
Headers:
  X-WP-Nonce: [nonce]
```

---

## Notas Importantes

1. **Nonce**: Todos os endpoints REST requerem `X-WP-Nonce` no header. Obtenha via:
   ```javascript
   const nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
   ```

2. **Permissões**: Apenas usuários `lojista` com restaurante vinculado podem usar essas funcionalidades.

3. **Cache**: Se algo não aparecer, limpe o cache do navegador (Ctrl+F5).

4. **Logs**: Verifique logs do WordPress em caso de erros:
   - `wp-content/debug.log`
   - Console do navegador (F12)

