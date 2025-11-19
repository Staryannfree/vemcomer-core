#!/usr/bin/env php
<?php
/**
 * Pequeno utilitário para aplicar o patch de compatibilidade do WP Pusher em ambientes PHP 8.2+.
 */

$root = $argv[1] ?? getcwd();
$pluginDir = rtrim($root, DIRECTORY_SEPARATOR) . '/wp-content/plugins/wppusher';

if (! is_dir($pluginDir)) {
    fwrite(STDERR, "Diretório do plugin WP Pusher não encontrado em {$pluginDir}\n");
    exit(1);
}

$loggerProperty = <<<'PROP'
    /**
     * Compatibilidade com PHP 8.2+: propriedade antes dinâmica.
     *
     * @var string
     */
    protected string $file = '';
PROP;

$dashboardProperty = <<<'PROP'
    /**
     * Compatibilidade com PHP 8.2+: propriedade antes dinâmica.
     *
     * @var mixed
     */
    protected $pusher = null;
PROP;

$targets = [
    [
        'file'      => $pluginDir . '/Pusher/Log/Logger.php',
        'class'     => 'Logger',
        'property'  => $loggerProperty,
        'needle'    => '$file',
    ],
    [
        'file'      => $pluginDir . '/Pusher/Dashboard.php',
        'class'     => 'Dashboard',
        'property'  => $dashboardProperty,
        'needle'    => '$pusher',
    ],
];

foreach ($targets as $target) {
    $file = $target['file'];
    if (! file_exists($file)) {
        fwrite(STDERR, "Arquivo {$file} não encontrado.\n");
        continue;
    }

    $source = file_get_contents($file);
    if (false === $source) {
        fwrite(STDERR, "Não foi possível ler {$file}.\n");
        continue;
    }

    $updated = $source;

    if (! str_contains($updated, '#[\\AllowDynamicProperties]')) {
        $updated = preg_replace(
            '/(class\s+' . $target['class'] . '\b)/',
            "#[\\AllowDynamicProperties]\n$1",
            $updated,
            1,
            $count
        );
        if (0 === $count) {
            fwrite(STDERR, "Não foi possível localizar a declaração da classe {$target['class']} em {$file}.\n");
        }
    }

    if (! str_contains($updated, $target['needle'])) {
        $updated = preg_replace(
            '/(class\s+' . $target['class'] . '[^{]*\{)/',
            "$1\n" . rtrim($target['property']) . "\n",
            $updated,
            1,
            $count
        );
        if (0 === $count) {
            fwrite(STDERR, "Não foi possível inserir a propriedade em {$file}.\n");
        }
    }

    if ($updated !== $source) {
        if (false === file_put_contents($file, $updated)) {
            fwrite(STDERR, "Falha ao gravar {$file}.\n");
            continue;
        }
        echo "Arquivo {$file} atualizado com sucesso.\n";
    } else {
        echo "Arquivo {$file} já estava ajustado.\n";
    }
}
