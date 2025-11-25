<?php
/**
 * Front Page Template
 *
 * Conditionally renders the Mobile App Shell or the Desktop Site.
 */
// ==========================================================================
// 1. MOBILE APP SHELL (App Nativo Experience)
// ==========================================================================
if ( wp_is_mobile() ) {
    
    // Enfileira os assets específicos do Mobile Shell V2
    add_action('wp_head', function() {
        // CSS do App Shell
        echo '<link rel="stylesheet" id="vc-mobile-shell-css" href="' . get_template_directory_uri() . '/assets/css/mobile-shell-v2.css" media="all" />';
    });
    add_action('wp_footer', function() {
        // JS do App Shell (com dados hardcoded por enquanto)
        echo '<script src="' . get_template_directory_uri() . '/assets/js/mobile-shell-v2.js" id="vc-mobile-shell-js"></script>';
    });
    ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#ea1d2c">
    <title>Pedevem - Delivery e Reservas</title>
    <?php wp_head(); ?>
</head>
<body>
    <?php 
    // Carrega o HTML do App Shell
    get_template_part('template-parts/content', 'mobile-home'); 
    ?>
    
    <?php wp_footer(); ?>
</body>
</html>
    <?php
    exit; // ⚠️ IMPORTANTE: Encerra a execução aqui para não carregar o layout desktop
}

// ==========================================================================
// 2. DESKTOP SITE (Layout Padrão)
// ==========================================================================
get_header(); 
?>

<div class="desktop-container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
    <h1>Bem-vindo ao Pedevem (Desktop)</h1>
    <p>Use o seu celular para ver a experiência App Nativo.</p>
    
    <div class="woocommerce-products">
        <?php echo do_shortcode('[vemcomer_restaurants]'); ?>
    </div>
</div>

<?php
get_footer();
