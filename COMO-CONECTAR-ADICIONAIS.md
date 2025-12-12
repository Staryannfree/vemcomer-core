# 🔗 Como Conectar Grupos de Adicionais às Categorias de Restaurantes

## ✅ O que foi criado

1. **Endpoint REST `/vemcomer/v1/seed/connect-addons`** - Reconecta todos os grupos às categorias corretas
2. **Endpoint REST `/vemcomer/v1/seed/verify-connections`** - Verifica o status das conexões
3. **Script PHP `scripts/connect-addons-to-categories.php`** - Executa tudo automaticamente

---

## 🚀 Como usar

### Opção 1: Via Script PHP (Recomendado)

Execute no terminal:

```bash
php scripts/connect-addons-to-categories.php
```

O script vai:
1. ✅ Verificar status atual
2. ✅ Conectar grupos às categorias
3. ✅ Verificar status final

### Opção 2: Via Navegador (REST API)

#### Verificar status:

Acesse no navegador (logado como admin):
```
http://pedevem-local.local/wp-json/vemcomer/v1/seed/verify-connections
```

Ou via console do navegador:
```javascript
fetch('/wp-json/vemcomer/v1/seed/verify-connections')
    .then(r => r.json())
    .then(console.log);
```

#### Conectar grupos:

Faça uma requisição POST (use um plugin REST Client ou o console):
```javascript
fetch('/wp-json/vemcomer/v1/seed/connect-addons', {
    method: 'POST',
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
.then(r => r.json())
.then(console.log);
```

---

## 📊 O que o script faz

### 1. Reconecta grupos existentes

- Busca todos os grupos de adicionais
- Compara com os dados do seeder
- Reconecta grupos que estão desconectados ou com conexões incorretas

### 2. Conecta grupos genéricos

Grupos genéricos (disponíveis para todas as categorias):
- `Molhos Extras`
- `Bebida do Combo`
- `Tamanho da Bebida`
- `Tamanhos`

Esses grupos são conectados automaticamente a todas as categorias que não têm grupos específicos.

### 3. Verifica cobertura

O script mostra:
- Quantos grupos estão conectados
- Quantos grupos não têm categorias
- Quantas categorias têm grupos
- Quantas categorias não têm grupos

---

## 📋 Resultado esperado

Após executar o script, você deve ver:

```
✅ TUDO CONECTADO COM SUCESSO!
```

Ou, se houver categorias sem grupos:

```
⚠️  AINDA HÁ CONEXÕES PENDENTES
```

Nesse caso, o script mostra quais categorias não têm grupos para que você possa criar grupos específicos depois.

---

## 🔍 Verificar manualmente

Para verificar se um grupo está conectado:

1. Vá para **Posts → Grupos de Adicionais** no WordPress
2. Abra um grupo
3. Veja a seção **Categorias de Restaurantes** (taxonomia `vc_cuisine`)
4. Deve mostrar as categorias conectadas

Para verificar se uma categoria tem grupos:

1. Vá para **Restaurantes → Categorias de Restaurantes**
2. Abra uma categoria
3. Veja quantos grupos estão conectados (na lista de posts relacionados)

---

## 🐛 Troubleshooting

### Erro: "Classe não encontrada"

Certifique-se de que o plugin está ativo e o `Seeder_Controller` está registrado em `vemcomer-core.php`.

### Grupos não conectam

Verifique se:
1. Os nomes das categorias no seeder correspondem exatamente aos nomes no banco
2. As categorias existem (execute o `Cuisine_Seeder` se necessário)
3. Os grupos existem (execute o `Addon_Catalog_Seeder` se necessário)

### Categorias sem grupos

Isso é normal para categorias muito específicas. O script conecta grupos genéricos automaticamente.

---

## 📝 Próximos passos

1. Execute o script de conexão
2. Verifique o resultado
3. Se necessário, crie grupos específicos para categorias que não têm
4. Teste no wizard de onboarding (Passo 6) para ver se os grupos aparecem

---

**Última atualização:** 2025-12-04

