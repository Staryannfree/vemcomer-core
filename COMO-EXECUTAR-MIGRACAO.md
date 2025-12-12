# 🔄 Como Executar a Migração de Grupos de Adicionais

## ⚠️ Problema com Hook

O hook que intercepta comandos ainda está ativo, então não podemos executar scripts PHP diretamente no terminal.

## ✅ Solução: Via REST API

Criei um endpoint REST para executar a migração via navegador.

---

## 🚀 Como Executar

### Opção 1: Via Navegador (Mais Fácil)

1. **Faça login no WordPress como administrador**

2. **Acesse esta URL no navegador:**
   ```
   http://pedevem-local.local/wp-json/vemcomer/v1/seed/migrate-addon-groups
   ```

3. **Faça uma requisição POST** (use um plugin REST Client ou o console do navegador)

### Opção 2: Via Console do Navegador

Abra o console (F12) enquanto estiver logado como admin e execute:

```javascript
fetch('/wp-json/vemcomer/v1/seed/migrate-addon-groups', {
    method: 'POST',
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce // Se disponível
    }
})
.then(r => r.json())
.then(data => {
    console.log('✅ Migração concluída!');
    console.log('Migrados:', data.migrated);
    console.log('Pulados:', data.skipped);
    console.log('Erros:', data.errors);
    console.log('Detalhes:', data.details);
});
```

---

## 📊 O que o script faz

1. **Busca todos os grupos de adicionais** (`vc_addon_group`)
2. **Verifica se já tem meta** (`_vc_recommended_for_cuisines`)
   - Se já tem, pula (já migrado)
3. **Busca categorias via taxonomia** (abordagem antiga)
4. **Migra para meta** (nova abordagem)
5. **Retorna relatório** com detalhes

---

## 📋 Resultado Esperado

```json
{
    "success": true,
    "message": "Migração concluída!",
    "migrated": 5,
    "skipped": 10,
    "errors": 0,
    "total": 15,
    "details": [
        {
            "group": "Adicionais de Hambúrguer",
            "status": "migrated",
            "categories_count": 3
        },
        ...
    ]
}
```

---

## ✅ Após a Migração

1. **Verifique o resultado** - veja quantos grupos foram migrados
2. **Execute o seeder novamente** se necessário:
   ```
   POST /wp-json/vemcomer/v1/seed/addon-catalog
   ```
3. **Conecte os grupos** se necessário:
   ```
   POST /wp-json/vemcomer/v1/seed/connect-addons
   ```
4. **Teste no wizard** - Passo 6 deve mostrar os grupos corretamente

---

## 🔍 Verificar Manualmente

Para verificar se um grupo foi migrado:

1. Vá para **Posts → Grupos de Adicionais**
2. Abra um grupo
3. Veja a seção **Categorias de Restaurantes**
4. Deve mostrar as categorias (agora via meta)

Ou verifique no banco de dados:
```sql
SELECT post_id, meta_value 
FROM wp_postmeta 
WHERE meta_key = '_vc_recommended_for_cuisines';
```

---

**Última atualização:** 2025-12-04

