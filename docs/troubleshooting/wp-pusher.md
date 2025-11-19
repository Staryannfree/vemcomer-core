# WP Pusher — compatibilidade com PHP 8.2

Ao ativar o plugin através do WP Pusher em ambientes com PHP 8.2+, alguns sites exibem:

```
PHP Deprecated:  Creation of dynamic property Pusher\Log\Logger::$file is deprecated
PHP Deprecated:  Creation of dynamic property Pusher\Dashboard::$pusher is deprecated
PHP Fatal error:  Cannot declare class Elementor\Element_Column, because the name is already in use
```

Os dois primeiros avisos são o motivo do erro crítico: o WP Pusher (versões < 3.0) cria propriedades dinâmicas e o PHP 8.2 passou a bloquear esse padrão. Quando o carregamento é interrompido no meio, outros plugins podem ser incluídos duas vezes e gerar o `Cannot declare class ... already in use`. Mesmo que a mensagem cite `Elementor\Element_Column`, o VemComer Core não depende do Elementor — trata-se apenas de um efeito colateral do autoloader interrompido.

## Solução rápida (script incluso no VemComer Core)

1. Faça SSH no servidor ou abra o terminal local apontando para o diretório do WordPress.
2. Execute o utilitário do próprio plugin:
   ```bash
   php wp-content/plugins/vemcomer-core/bin/fix-wppusher-php82.php /caminho/para/seu/wordpress
   ```
   - O segundo argumento é opcional; por padrão o script usa o diretório atual.
   - O script procura `wp-content/plugins/wppusher/Pusher/Log/Logger.php` e `Pusher/Dashboard.php`.
3. Caso os arquivos sejam encontrados, o script:
   - adiciona o atributo `#[\AllowDynamicProperties]` às classes `Pusher\Log\Logger` e `Pusher\Dashboard`; e
   - cria as propriedades tipadas `$file` e `$pusher` para evitar novas criações dinâmicas.
4. Limpe o cache de opcode (reinicie o PHP-FPM/Apache ou execute `touch` nos arquivos).
5. Recarregue o WP Admin e ative novamente o WP Pusher antes de ativar o VemComer Core.

## Solução manual (caso não possa executar scripts)

1. Edite `wp-content/plugins/wppusher/Pusher/Log/Logger.php` e altere o início da classe para:
   ```php
   namespace Pusher\Log;

   #[\AllowDynamicProperties]
   class Logger {
       protected string $file = '';
       // ... restante do arquivo ...
   }
   ```
2. Repita o procedimento em `wp-content/plugins/wppusher/Pusher/Dashboard.php`:
   ```php
   namespace Pusher;

   #[\AllowDynamicProperties]
   class Dashboard {
       protected $pusher = null;
       // ... restante do arquivo ...
   }
   ```
3. Salve, limpe o cache de opcode e reative o WP Pusher.

## Checklist pós-correção

- Ativar o WP Pusher sem warnings/fatals no `debug.log`.
- Em seguida ativar o `vemcomer-core` normalmente.
- Se algum plugin continuar acusando `Cannot declare class`, remova caches e garanta que só exista uma única cópia desse plugin no servidor.
