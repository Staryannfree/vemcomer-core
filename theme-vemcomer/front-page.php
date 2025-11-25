<?php
/**
 * Front Page Template
 *
 * @package VemComer
 */

// Lógica de Detecção: É mobile OU temos o parâmetro ?mode=app na URL?
$is_mobile_app = wp_is_mobile() || ( isset( $_GET['mode'] ) && $_GET['mode'] === 'app' );

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
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="theme-color" content="#ea1d2c">
        <title>VemComer</title>
        <?php wp_head(); ?>
    </head>
    <body>
        <?php 
        if ( locate_template('template-parts/content-mobile-home.php') ) {
            get_template_part('template-parts/content', 'mobile-home'); 
        } else {
            echo '<h1 style="padding:50px; text-align:center">ERRO: template-parts/content-mobile-home.php não encontrado!</h1>';
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
