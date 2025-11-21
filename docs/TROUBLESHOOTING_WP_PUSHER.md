# 🔧 Correção Rápida: Erro Elementor/WP Pusher PHP 8.2+

## ⚠️ Erro
```
PHP Fatal error: Cannot declare class Elementor\Element_Column, because the name is already in use
```

**Causa:** WP Pusher (versões antigas) não é compatível com PHP 8.2+.

---

## ✅ Solução Rápida (3 minutos)

### Opção 1: Via SSH/Terminal (Recomendado)

1. **Conecte-se ao servidor via SSH** ou abra o terminal no diretório do WordPress

2. **Execute o script de correção:**
   ```bash
   php wp-content/plugins/vemcomer-core/bin/fix-wppusher-php82.php
   ```
   
   Ou se estiver em outro diretório:
   ```bash
   php /caminho/completo/para/wordpress/wp-content/plugins/vemcomer-core/bin/fix-wppusher-php82.php /caminho/completo/para/wordpress
   ```

3. **Limpe o cache do PHP:**
   ```bash
   # Se usar PHP-FPM:
   sudo service php-fpm restart
   
   # Ou se usar Apache:
   sudo service apache2 restart
   ```

4. **Recarregue o WordPress** - O erro deve desaparecer!

---

### Opção 2: Correção Manual (Sem SSH)

Se não tiver acesso SSH, edite os arquivos diretamente:

#### Arquivo 1: `wp-content/plugins/wppusher/Pusher/Log/Logger.php`

**Localize a classe `Logger`** (geralmente linha 15-20) e altere de:
```php
namespace Pusher\Log;

class Logger {
    // ...
```

**Para:**
```php
namespace Pusher\Log;

#[\AllowDynamicProperties]
class Logger {
    protected string $file = '';
    
    // ... resto do código ...
```

#### Arquivo 2: `wp-content/plugins/wppusher/Pusher/Dashboard.php`

**Localize a classe `Dashboard`** e altere de:
```php
namespace Pusher;

class Dashboard {
    // ...
```

**Para:**
```php
namespace Pusher;

#[\AllowDynamicProperties]
class Dashboard {
    protected $pusher = null;
    
    // ... resto do código ...
```

#### Depois de editar:

1. **Salve os arquivos**
2. **Limpe o cache** (se usar cache de opcode)
3. **Recarregue o WordPress**

---

### Opção 3: Atualizar WP Pusher (Se disponível)

Se houver uma versão mais recente do WP Pusher (3.0+), atualize:

1. Vá em **Plugins ▸ Plugins Instalados**
2. Procure por **WP Pusher**
3. Se houver atualização disponível, clique em **Atualizar agora**

---

## 🔍 Verificação

Após aplicar a correção:

1. **Limpe o cache do WordPress** (se usar plugin de cache)
2. **Recarregue a página do admin**
3. **Verifique o `debug.log`** - não deve mais aparecer o erro

---

## 📝 Notas Importantes

- **Este erro NÃO é do VemComer Core** - é um problema de compatibilidade do WP Pusher
- O VemComer Core **não depende do Elementor** - a mensagem de erro é enganosa
- O problema ocorre porque o WP Pusher interrompe o carregamento dos plugins, causando duplicação de classes

---

## 🆘 Ainda com problemas?

1. **Desative temporariamente o WP Pusher:**
   - Vá em **Plugins ▸ Plugins Instalados**
   - Desative **WP Pusher**
   - Ative o **VemComer Core**
   - Depois corrija o WP Pusher e reative

2. **Verifique se há múltiplas cópias do Elementor:**
   - Procure por `elementor` em `wp-content/plugins/`
   - Mantenha apenas uma versão

3. **Limpe todos os caches:**
   - Cache do WordPress
   - Cache de opcode do PHP
   - Cache do navegador

---

## 📚 Documentação Completa

Para mais detalhes, consulte: [`docs/troubleshooting/wp-pusher.md`](troubleshooting/wp-pusher.md)

