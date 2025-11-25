# 🚀 Guia de Desenvolvimento e Preview

## 📋 Índice

1. [Preview Local](#preview-local)
2. [Deploy Rápido](#deploy-rápido)
3. [Teste em Produção](#teste-em-produção)
4. [Workflow Recomendado](#workflow-recomendado)

---

## 🖥️ Preview Local

### Opção 1: XAMPP Local (Recomendado)

**Vantagens:**
- ✅ Testa tudo localmente antes de subir
- ✅ Não afeta o site em produção
- ✅ Mais rápido para iterar

**Setup:**

1. **Instalar XAMPP:**
   ```bash
   # Baixe em: https://www.apachefriends.org/
   # Instale em C:\xampp ou D:\xampp
   ```

2. **Configurar WordPress Local:**
   ```bash
   # Execute o script de setup
   .\dev-setup.bat
   
   # Baixe WordPress em: C:\xampp\htdocs\vemcomer-dev
   # Configure banco: vemcomer_dev
   ```

3. **Fazer Deploy Local:**
   ```bash
   # Edite deploy.bat e ajuste WP_PATH
   # Execute:
   .\deploy.bat
   ```

4. **Acessar:**
   ```
   http://localhost/vemcomer-dev
   ```

### Opção 2: Watch Mode (Deploy Automático)

**Para desenvolvimento ativo:**

```bash
# Execute e deixe rodando
.\watch-and-deploy.bat

# Agora, qualquer mudança nos arquivos .php
# será automaticamente copiada para o WordPress local
```

**Como funciona:**
- Monitora mudanças em `inc/` e `theme-vemcomer/`
- Copia automaticamente para o WordPress local
- Atualiza a cada 5 segundos

---

## 🚀 Deploy Rápido

### Método 1: Script Automático (Local)

```bash
# 1. Edite deploy.bat e ajuste WP_PATH
# 2. Execute:
.\deploy.bat
```

**O que faz:**
- Copia plugin para `wp-content/plugins/vemcomer-core/`
- Copia tema para `wp-content/themes/theme-vemcomer/`
- Mantém estrutura de pastas

### Método 2: SFTP Manual (Produção)

**Via FileZilla/WinSCP:**

1. Conecte no servidor
2. Navegue até:
   - Plugin: `/wp-content/plugins/vemcomer-core/`
   - Tema: `/wp-content/themes/theme-vemcomer/`
3. Faça upload dos arquivos modificados

### Método 3: Git + WP Pusher (Recomendado para Produção)

**Se você usa WP Pusher:**

```bash
# 1. Commit suas mudanças
git add .
git commit -m "Descrição das mudanças"
git push origin main

# 2. WP Pusher sincroniza automaticamente
# (se configurado com auto-deploy)
```

---

## 🧪 Teste em Produção

### Preview Staging (Recomendado)

**Criar ambiente de staging:**

1. **Subdomínio de teste:**
   ```
   staging.seusite.com
   ```

2. **Deploy apenas para staging:**
   ```bash
   # Use deploy.bat com caminho do staging
   # Ou crie deploy-staging.bat
   ```

3. **Teste completo antes de produção**

### Preview Direto (Cuidado!)

**Para ver mudanças rapidamente:**

1. **Via Gerenciador de Arquivos (Hostinger):**
   - hPanel → Arquivos → Editar arquivo
   - Salve e recarregue (Ctrl+F5)

2. **Via SFTP:**
   - Edite localmente
   - Faça upload
   - Limpe cache do WordPress

3. **Cache Busting:**
   ```php
   // Em vemcomer-core.php, mude a versão:
   define('VEMCOMER_CORE_VERSION', '0.8.1-dev');
   ```

---

## 🔄 Workflow Recomendado

### Para Desenvolvimento Diário:

```
1. Desenvolver localmente (XAMPP)
   ↓
2. Testar em localhost
   ↓
3. Commit no Git
   ↓
4. Push para GitHub
   ↓
5. Deploy para staging (se tiver)
   ↓
6. Testar em staging
   ↓
7. Deploy para produção
```

### Para Mudanças Rápidas:

```
1. Editar arquivo diretamente no servidor (via hPanel)
   ↓
2. Testar no site
   ↓
3. Se funcionar, fazer commit do mesmo arquivo
   ↓
4. Push para GitHub
```

---

## 🛠️ Ferramentas Úteis

### 1. Browser DevTools

**Para ver mudanças CSS/JS:**
- F12 → Console/Network
- Ctrl+Shift+R (hard refresh)
- Desabilitar cache no DevTools

### 2. WordPress Debug

**Ativar em `wp-config.php`:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Ver logs:**
```
wp-content/debug.log
```

### 3. Versionamento Automático

**Script para atualizar versão:**
```bash
# Em vemcomer-core.php, linha 15:
define('VEMCOMER_CORE_VERSION', date('Y.m.d.His'));
```

---

## 📝 Checklist de Deploy

Antes de fazer deploy:

- [ ] Testei localmente
- [ ] Verifiquei erros no console (F12)
- [ ] Testei em diferentes navegadores
- [ ] Verifiquei responsividade (mobile)
- [ ] Limpei cache do WordPress
- [ ] Fiz backup (se produção)
- [ ] Commitei no Git
- [ ] Documentei as mudanças

---

## 🚨 Troubleshooting

### Mudanças não aparecem?

1. **Limpe cache:**
   - WordPress: Plugins → Cache → Limpar
   - Navegador: Ctrl+Shift+Delete

2. **Verifique versão:**
   ```php
   // Mude VEMCOMER_CORE_VERSION em vemcomer-core.php
   ```

3. **Hard refresh:**
   - Ctrl+Shift+R (Chrome/Firefox)
   - Ctrl+F5 (Edge)

### Erro 500?

1. **Ative debug:**
   ```php
   define('WP_DEBUG', true);
   ```

2. **Verifique logs:**
   ```
   wp-content/debug.log
   ```

3. **Verifique permissões:**
   - Arquivos: 644
   - Diretórios: 755

---

## 💡 Dicas

1. **Use Git para versionamento:**
   - Cada feature = 1 branch
   - Merge apenas após testes

2. **Mantenha staging atualizado:**
   - Teste sempre em staging primeiro

3. **Documente mudanças:**
   - Commit messages claras
   - README atualizado

4. **Backup antes de produção:**
   - Sempre faça backup antes de deploy em produção

---

## 📞 Suporte

Se tiver problemas:
1. Verifique os logs (`wp-content/debug.log`)
2. Ative `WP_DEBUG`
3. Verifique permissões de arquivos
4. Limpe cache do WordPress e navegador


