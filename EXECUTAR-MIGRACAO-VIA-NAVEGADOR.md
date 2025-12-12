# 🔄 Executar Migração via Navegador

## ⚠️ Problema com Hook

O hook ainda está interceptando comandos do terminal, então vamos usar o endpoint REST.

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

**IMPORTANTE:** Você precisa estar logado como administrador no WordPress.

Abra o console (F12) e execute:

```javascript
// Método 1: Com nonce (se disponível)
const nonce = typeof wpApiSettings !== 'undefined' 
    ? wpApiSettings.nonce 
    : document.querySelector('meta[name="wp-api-nonce"]')?.content 
    || '';

fetch('/wp-json/vemcomer/v1/seed/migrate-addon-groups', {
    method: 'POST',
    credentials: 'same-origin', // Importante: envia cookies de autenticação
    headers: {
        'Content-Type': 'application/json',
        ...(nonce && { 'X-WP-Nonce': nonce })
    }
})
.then(r => {
    if (!r.ok) {
        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
    }
    return r.json();
})
.then(data => {
    console.log('✅ Migração concluída!');
    console.log('Migrados:', data.migrated);
    console.log('Pulados:', data.skipped);
    console.log('Erros:', data.errors);
    console.log('Total:', data.total);
    console.log('Detalhes:', data.details);
    
    // Mostrar resumo
    if (data.details && data.details.length > 0) {
        console.table(data.details);
    }
})
.catch(error => {
    console.error('❌ Erro:', error);
    console.log('💡 Dica: Certifique-se de estar logado como administrador');
});
```

**Nota:** Se você estiver logado como admin, o WordPress autentica via cookie automaticamente, então o nonce pode ser opcional.

---

## 📊 O que o endpoint faz

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
        {
            "group": "Bebida do Combo",
            "status": "skipped",
            "reason": "Já tem meta"
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

**Última atualização:** 2025-12-04


