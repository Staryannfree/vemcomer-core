<?php
/**
 * Front Page Template
 *
 * @package VemComer
 */

// DEBUG: Verificar se este arquivo está sendo carregado
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'FRONT-PAGE.PHP: Arquivo carregado. GET[mode]=' . ( isset( $_GET['mode'] ) ? $_GET['mode'] : 'não definido' ) );
    error_log( 'FRONT-PAGE.PHP: wp_is_mobile()=' . ( wp_is_mobile() ? 'true' : 'false' ) );
}

// Lógica de Detecção: É mobile OU temos o parâmetro ?mode=app na URL?
// IMPORTANTE: Verificar ?mode=app PRIMEIRO para garantir que funciona no desktop
$is_mobile_app = ( isset( $_GET['mode'] ) && $_GET['mode'] === 'app' ) || wp_is_mobile();

// DEBUG: Log da decisão
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'FRONT-PAGE.PHP: is_mobile_app=' . ( $is_mobile_app ? 'true' : 'false' ) );
}

if ( $is_mobile_app ) {
    
    // Desativa o Admin Bar no mobile para parecer App
    add_filter('show_admin_bar', '__return_false');

    // Enfileira os assets Específicos (com timestamp para evitar cache)
    add_action('wp_head', function() {
        $css_ver = file_exists( get_template_directory() . '/assets/css/mobile-shell-v2.css' ) 
            ? filemtime( get_template_directory() . '/assets/css/mobile-shell-v2.css' ) 
            : '1.0.0';
            
        echo '<link rel="stylesheet" id="vc-mobile-shell-css" href="' . get_template_directory_uri() . '/assets/css/mobile-shell-v2.css?v=' . $css_ver . '" media="all" />';
        
        // Meta tags essenciais para App-like feel
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">';
        echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    }, 1);

    // Enfileira o JS no final
    add_action('wp_footer', function() {
        $js_ver = file_exists( get_template_directory() . '/assets/js/mobile-shell-v2.js' ) 
            ? filemtime( get_template_directory() . '/assets/js/mobile-shell-v2.js' ) 
            : '1.0.0';
            
        echo '<script src="' . get_template_directory_uri() . '/assets/js/mobile-shell-v2.js?v=' . $js_ver . '" id="vc-mobile-shell-js"></script>';
    }, 9999);

    // Renderiza o App Shell
    // DEBUG: Log antes de renderizar
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'FRONT-PAGE.PHP: Renderizando App Shell mobile' );
    }
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="theme-color" content="#ea1d2c">
        <title>VemComer - App</title>
        <?php wp_head(); ?>
    </head>
    <body>
        <?php 
        $template_path = locate_template('template-parts/content-mobile-home.php');
        if ( $template_path ) {
            get_template_part('template-parts/content', 'mobile-home'); 
        } else {
            echo '<h1 style="padding:50px; text-align:center; color:red;">ERRO: template-parts/content-mobile-home.php não encontrado!</h1>';
            echo '<p style="padding:20px; text-align:center;">Caminho procurado: ' . esc_html( $template_path ) . '</p>';
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'FRONT-PAGE.PHP: Template não encontrado. Caminho: ' . $template_path );
            }
        }
        ?>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
    exit; // Encerra execução para não carregar header/footer do tema pai
}

// ==========================================================================
// FALLBACK DESKTOP
// ==========================================================================
get_header(); 
?>

<div class="container" style="max-width: 1100px; margin: 0 auto; padding: 40px;">
    <h1>Versão Desktop</h1>
    <p>Acesse pelo celular ou use <strong>?mode=app</strong> na URL para ver a versão App.</p>
    <?php echo do_shortcode('[vemcomer_restaurants]'); ?>
</div>

<?php
get_footer();
