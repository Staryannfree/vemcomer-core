<?php
/**
 * Correção do WP Pusher para PHP 8.2+ - Versão Web
 * 
 * Acesse este arquivo via navegador: https://seusite.com/wp-content/plugins/vemcomer-core/fix-wppusher-web.php
 * 
 * ATENÇÃO: Delete este arquivo após usar por segurança!
 */

// Carregar WordPress
$wp_load = dirname( __FILE__ ) . '/../../wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    die( 'Erro: wp-load.php não encontrado. Certifique-se de que este arquivo está em wp-content/plugins/vemcomer-core/' );
}

require_once $wp_load;

// Verificar se é admin
if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Erro: Você precisa ser administrador para executar este script.' );
}

$wp_root = ABSPATH;
$plugin_dir = $wp_root . 'wp-content/plugins/wppusher';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Correção WP Pusher PHP 8.2+</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔧 Correção WP Pusher para PHP 8.2+</h1>
    
    <?php
    if ( ! is_dir( $plugin_dir ) ) {
        echo '<div class="error">❌ Diretório do WP Pusher não encontrado em: <code>' . esc_html( $plugin_dir ) . '</code></div>';
        echo '<div class="info">💡 Certifique-se de que o plugin WP Pusher está instalado.</div>';
        exit;
    }
    
    $targets = [
        [
            'file' => $plugin_dir . '/Pusher/Log/Logger.php',
            'class' => 'Logger',
            'property' => 'protected string $file = \'\';',
        ],
        [
            'file' => $plugin_dir . '/Pusher/Dashboard.php',
            'class' => 'Dashboard',
            'property' => 'protected $pusher = null;',
        ],
    ];
    
    $results = [];
    $all_fixed = true;
    
    foreach ( $targets as $target ) {
        $file = $target['file'];
        $result = [
            'file' => $file,
            'exists' => file_exists( $file ),
            'fixed' => false,
            'error' => null,
        ];
        
        if ( ! $result['exists'] ) {
            $result['error'] = 'Arquivo não encontrado';
            $all_fixed = false;
            $results[] = $result;
            continue;
        }
        
        $content = file_get_contents( $file );
        if ( false === $content ) {
            $result['error'] = 'Não foi possível ler o arquivo';
            $all_fixed = false;
            $results[] = $result;
            continue;
        }
        
        $updated = $content;
        $changed = false;
        
        // Adicionar #[\AllowDynamicProperties] se não existir
        if ( ! str_contains( $updated, '#[\\AllowDynamicProperties]' ) ) {
            $updated = preg_replace(
                '/(class\s+' . $target['class'] . '\b)/',
                "#[\\AllowDynamicProperties]\n$1",
                $updated,
                1
            );
            $changed = true;
        }
        
        // Adicionar propriedade se não existir
        $needle = '$' . explode( ' ', $target['property'] )[1];
        if ( ! str_contains( $updated, $target['property'] ) && ! str_contains( $updated, $needle ) ) {
            $updated = preg_replace(
                '/(class\s+' . $target['class'] . '[^{]*\{)/',
                "$1\n    " . $target['property'] . "\n",
                $updated,
                1
            );
            $changed = true;
        }
        
        if ( $changed ) {
            if ( file_put_contents( $file, $updated ) !== false ) {
                $result['fixed'] = true;
            } else {
                $result['error'] = 'Não foi possível escrever no arquivo (verifique permissões)';
                $all_fixed = false;
            }
        } else {
            $result['fixed'] = true; // Já estava corrigido
        }
        
        $results[] = $result;
    }
    
    // Exibir resultados
    foreach ( $results as $result ) {
        if ( ! $result['exists'] ) {
            echo '<div class="error">❌ Arquivo não encontrado: <code>' . esc_html( basename( $result['file'] ) ) . '</code></div>';
        } elseif ( $result['error'] ) {
            echo '<div class="error">❌ Erro em <code>' . esc_html( basename( $result['file'] ) ) . '</code>: ' . esc_html( $result['error'] ) . '</div>';
        } elseif ( $result['fixed'] ) {
            echo '<div class="success">✅ Arquivo corrigido: <code>' . esc_html( basename( $result['file'] ) ) . '</code></div>';
        }
    }
    
    if ( $all_fixed ) {
        echo '<div class="success"><strong>✅ Correção concluída com sucesso!</strong></div>';
        echo '<div class="info">💡 Agora você pode:</div>';
        echo '<ol>';
        echo '<li>Limpar o cache do PHP (se usar cache de opcode)</li>';
        echo '<li>Recarregar a página do admin do WordPress</li>';
        echo '<li>Tentar ativar o VemComer Core novamente</li>';
        echo '<li><strong>DELETAR este arquivo por segurança!</strong></li>';
        echo '</ol>';
    } else {
        echo '<div class="warning"><strong>⚠️ Alguns arquivos não puderam ser corrigidos automaticamente.</strong></div>';
        echo '<div class="info">💡 Tente corrigir manualmente seguindo o guia em: <code>docs/TROUBLESHOOTING_WP_PUSHER.md</code></div>';
    }
    ?>
    
    <hr>
    <p><small>🔒 <strong>IMPORTANTE:</strong> Delete este arquivo após usar por segurança!</small></p>
</body>
</html>

