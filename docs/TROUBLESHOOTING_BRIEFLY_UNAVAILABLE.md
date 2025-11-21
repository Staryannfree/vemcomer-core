# 🔍 "Briefly Unavailable" - Diagnóstico e Solução

## O que é "Briefly Unavailable"?

"Briefly unavailable" é uma mensagem padrão do **WordPress** que aparece quando:
- Há um erro fatal no PHP
- Memória PHP esgotada
- Plugin de cache está ativo
- Timeout na execução
- Problema de hospedagem

**NÃO é necessariamente do VemComer Core ou do tema.**

---

## 🔍 Como Identificar a Causa

### 1. Verificar Logs de Erro

**No servidor:**
```bash
# Verificar log de erros do PHP
tail -f /var/log/php-errors.log

# Ou log do WordPress
tail -f wp-content/debug.log
```

**No WordPress:**
1. Ative o debug em `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. Verifique `wp-content/debug.log`

---

### 2. Verificar Plugins de Cache

**Plugins comuns que causam isso:**
- WP Super Cache
- W3 Total Cache
- WP Rocket
- LiteSpeed Cache
- Autoptimize

**Solução:**
1. Desative temporariamente o plugin de cache
2. Limpe todo o cache
3. Teste novamente

---

### 3. Verificar Memória PHP

**Adicione em `wp-config.php`:**
```php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

**Verifique no servidor:**
```bash
php -i | grep memory_limit
```

---

### 4. Verificar Timeout

**Adicione em `wp-config.php`:**
```php
set_time_limit(300); // 5 minutos
ini_set('max_execution_time', 300);
```

---

## 🛠️ Soluções por Causa

### Causa 1: Plugin de Cache

**Sintomas:**
- Mensagem aparece intermitentemente
- Aparece mais em mobile
- Desaparece ao limpar cache

**Solução:**
1. Desative o plugin de cache
2. Limpe cache do navegador
3. Teste novamente
4. Se funcionar, reative o cache e ajuste configurações

---

### Causa 2: Erro Fatal no PHP

**Sintomas:**
- Mensagem aparece sempre
- Log mostra erro fatal

**Solução:**
1. Verifique `wp-content/debug.log`
2. Procure por `Fatal error` ou `Parse error`
3. Corrija o erro ou desative plugin/tema problemático

**Erros comuns:**
- Classe não encontrada
- Função não definida
- Sintaxe incorreta

---

### Causa 3: Memória PHP Esgotada

**Sintomas:**
- Mensagem aparece em páginas com muitos dados
- Log mostra "Allowed memory size exhausted"

**Solução:**
1. Aumente `memory_limit` no `php.ini` ou `wp-config.php`
2. Otimize queries do banco de dados
3. Reduza número de plugins ativos

---

### Causa 4: Timeout

**Sintomas:**
- Mensagem aparece após alguns segundos
- Log mostra "Maximum execution time exceeded"

**Solução:**
1. Aumente `max_execution_time`
2. Otimize código lento
3. Use cache para reduzir processamento

---

### Causa 5: Problema de Hospedagem

**Sintomas:**
- Mensagem aparece aleatoriamente
- Outros sites na mesma hospedagem também têm problema

**Solução:**
1. Contate suporte da hospedagem
2. Verifique recursos do servidor (CPU, RAM, disco)
3. Considere upgrade de plano

---

## 🔧 Verificações Específicas do VemComer

### 1. Verificar se Tema está Ativo

```php
// Em wp-config.php, adicione temporariamente:
define('WP_DEBUG', true);
```

Se o tema VemComer estiver causando problema, você verá o erro no log.

---

### 2. Verificar Funções do Tema

**Arquivos que podem causar problema:**
- `theme-vemcomer/functions.php`
- `theme-vemcomer/inc/home-improvements.php`
- `theme-vemcomer/inc/restaurant-helpers.php`

**Teste:**
1. Desative o tema VemComer
2. Ative um tema padrão (Twenty Twenty-Four)
3. Se funcionar, o problema é do tema
4. Reative e verifique logs

---

### 3. Verificar Plugin VemComer Core

**Teste:**
1. Desative o plugin `vemcomer-core`
2. Teste o site
3. Se funcionar, o problema é do plugin
4. Reative e verifique logs

---

## 📋 Checklist de Diagnóstico

- [ ] Verificar `wp-content/debug.log`
- [ ] Desativar plugins de cache
- [ ] Desativar plugins um por um
- [ ] Trocar tema temporariamente
- [ ] Verificar memória PHP
- [ ] Verificar timeout PHP
- [ ] Verificar recursos do servidor
- [ ] Contatar suporte da hospedagem

---

## 🚨 Solução Rápida (Temporária)

Se precisar do site funcionando AGORA:

1. **Desative plugins de cache**
2. **Aumente memória PHP:**
```php
// wp-config.php
define('WP_MEMORY_LIMIT', '512M');
```
3. **Limpe todos os caches**
4. **Reinicie PHP/Apache**

---

## 📞 Quando Contatar Suporte

Contate suporte da hospedagem se:
- Erro persiste após todas as soluções
- Logs não mostram erro específico
- Outros sites na mesma hospedagem também têm problema
- Recursos do servidor estão no limite

---

## 💡 Prevenção

1. **Mantenha plugins atualizados**
2. **Use cache com moderação**
3. **Monitore uso de memória**
4. **Faça backups regulares**
5. **Teste em ambiente de desenvolvimento primeiro**

---

## 📝 Nota Importante

**"Briefly unavailable" NÃO é um erro do VemComer Core por padrão.**

É uma mensagem de segurança do WordPress que aparece quando há qualquer problema que impede o carregamento completo da página.

**Causas mais comuns:**
1. Plugin de cache (80% dos casos)
2. Memória PHP esgotada (15% dos casos)
3. Erro fatal em plugin/tema (5% dos casos)

