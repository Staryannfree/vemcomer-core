# 🚨 Erro Crítico: Elementor\Element_Column

## ⚡ Solução Rápida (2 minutos)

Este erro **NÃO é do VemComer Core** - é do **WP Pusher** com PHP 8.2+.

### ✅ Correção Automática (Recomendado)

Execute no terminal/SSH:
```bash
php wp-content/plugins/vemcomer-core/bin/fix-wppusher-php82.php
```

Depois reinicie o PHP:
```bash
sudo service php-fpm restart
# ou
sudo service apache2 restart
```

### ✅ Correção Manual

Edite estes 2 arquivos do WP Pusher:

**1. `wp-content/plugins/wppusher/Pusher/Log/Logger.php`**
```php
#[\AllowDynamicProperties]
class Logger {
    protected string $file = '';
```

**2. `wp-content/plugins/wppusher/Pusher/Dashboard.php`**
```php
#[\AllowDynamicProperties]
class Dashboard {
    protected $pusher = null;
```

### 📖 Guia Completo

Veja: [`docs/TROUBLESHOOTING_WP_PUSHER.md`](docs/TROUBLESHOOTING_WP_PUSHER.md)

