<?php
/**
 * VemComer Theme Functions
 * 
 * @package VemComer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Configurações do tema
 */
function vemcomer_theme_setup() {
    // Suporte a título automático
    add_theme_support( 'title-tag' );
    
    // Suporte a imagens destacadas
    add_theme_support( 'post-thumbnails' );
    
    // Suporte a HTML5
    add_theme_support( 'html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ] );
    
    // Registrar menus
    register_nav_menus([
        'primary' => __( 'Menu Principal', 'vemcomer' ),
        'footer'  => __( 'Menu Rodapé', 'vemcomer' ),
    ]);
    
    // Tamanhos de imagem customizados
    add_image_size( 'vemcomer-hero', 1920, 600, true );
    add_image_size( 'vemcomer-card', 400, 300, true );
    add_image_size( 'vemcomer-thumb', 150, 150, true );
}
add_action( 'after_setup_theme', 'vemcomer_theme_setup' );

/**
 * Enfileira estilos e scripts
 */
function vemcomer_theme_scripts() {
    $theme_version = wp_get_theme()->get( 'Version' );
    
    // Estilos
    wp_enqueue_style( 'vemcomer-theme-style', get_stylesheet_uri(), [], $theme_version );
    wp_enqueue_style( 'vemcomer-theme-main', get_template_directory_uri() . '/assets/css/main.css', [], $theme_version );
    if ( is_front_page() ) {
        wp_enqueue_style( 'vemcomer-home-improvements', get_template_directory_uri() . '/assets/css/home-improvements.css', [], $theme_version );
    }
    
    // Scripts
    wp_enqueue_script( 'vemcomer-theme-main', get_template_directory_uri() . '/assets/js/main.js', [], $theme_version, true );
    if ( is_front_page() ) {
        wp_enqueue_script( 'vemcomer-home-improvements', get_template_directory_uri() . '/assets/js/home-improvements.js', ['vemcomer-theme-main'], $theme_version, true );
    }
    
    // Localizar script com dados do REST API
    wp_localize_script( 'vemcomer-theme-main', 'vemcomerTheme', [
        'restUrl' => rest_url( 'vemcomer/v1/' ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
        'isLoggedIn' => is_user_logged_in(),
        'homeUrl' => home_url( '/' ),
    ] );
    
    // Localizar script de melhorias da home
    if ( is_front_page() ) {
        wp_localize_script( 'vemcomer-home-improvements', 'vemcomerTheme', [
            'restUrl' => rest_url( 'vemcomer/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'isLoggedIn' => is_user_logged_in(),
            'homeUrl' => home_url( '/' ),
        ] );
    }
    
    // Carregar helpers de restaurante
    if ( file_exists( get_template_directory() . '/inc/restaurant-helpers.php' ) ) {
        require_once get_template_directory() . '/inc/restaurant-helpers.php';
    }
    
    // Carregar helpers de restaurante (com verificação de erro)
    $restaurant_helpers = get_template_directory() . '/inc/restaurant-helpers.php';
    if ( file_exists( $restaurant_helpers ) ) {
        require_once $restaurant_helpers;
    }
    
    // Carregar melhorias de cards (com verificação de erro)
    $enhance_cards = get_template_directory() . '/inc/enhance-restaurant-cards.php';
    if ( file_exists( $enhance_cards ) ) {
        require_once $enhance_cards;
    }
    
    // Carregar melhorias de SEO (com verificação de erro)
    $seo_improvements = get_template_directory() . '/inc/seo-improvements.php';
    if ( file_exists( $seo_improvements ) ) {
        require_once $seo_improvements;
    }
    
    // Se o plugin vemcomer-core estiver ativo, carregar seus assets também
    if ( class_exists( 'VC\Frontend\Shortcodes' ) ) {
        // Os assets do plugin serão carregados automaticamente pelos shortcodes
    }
}
add_action( 'wp_enqueue_scripts', 'vemcomer_theme_scripts' );

/**
 * Adiciona CSS e JavaScript inline no footer para garantir funcionamento
 * Prioridade alta para carregar depois de tudo
 */
function vemcomer_force_popup_and_cards() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <style id="vemcomer-force-styles">
        /* CSS FORÇADO - Popup */
        #welcome-popup {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 2147483647 !important;
            transition: opacity 0.3s ease;
        }
        #welcome-popup:not(.is-open) {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
        #welcome-popup.is-open {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            background-color: rgba(0,0,0,0.6) !important;
        }
        .welcome-popup__dialog, .welcome-popup__dialog button {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 2147483648 !important;
        }
        
        /* CSS FORÇADO - Cards clicáveis */
        .vc-card__link {
            display: block !important;
            position: relative !important;
            z-index: 1 !important;
            pointer-events: auto !important;
            cursor: pointer !important;
            text-decoration: none !important;
            color: inherit !important;
        }
        .vc-card__link * {
            pointer-events: none !important;
        }
        .vc-card__link:hover {
            opacity: 0.95 !important;
        }
        .vc-btn--menu {
            position: relative !important;
            z-index: 15 !important;
            pointer-events: auto !important;
        }
        .vc-card__favorite {
            z-index: 20 !important;
            pointer-events: auto !important;
        }
    </style>
    <script id="vemcomer-force-scripts">
    (function() {
        'use strict';
        
        console.log('🔧 VemComer Force Scripts carregado!');
        
        // Função para inicializar popup
        function initPopup() {
            const popup = document.getElementById('welcome-popup');
            if (!popup) {
                console.warn('Popup não encontrado');
                return;
            }
            
            console.log('✅ Popup encontrado, inicializando...');
            
            // Abrir popup após 1s
            setTimeout(() => {
                const popupSeen = document.cookie.split(';').some(c => c.trim().startsWith('vc_welcome_popup_seen=1'));
                if (!popupSeen) {
                    popup.classList.add('is-open');
                    console.log('✅ Popup aberto');
                }
            }, 1000);
            
            // Listener de cliques
            popup.addEventListener('click', function(e) {
                // Botão de localização
                if (e.target.id === 'welcome-popup-location-btn' || e.target.closest('#welcome-popup-location-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const btn = e.target.closest('#welcome-popup-location-btn') || e.target;
                    
                    btn.innerText = '📍 Obtendo GPS...';
                    btn.style.opacity = '0.8';
                    btn.disabled = true;
                    
                    console.log('📍 Solicitando GPS...');
                    
                    if (!navigator.geolocation) {
                        alert('Seu navegador não suporta geolocalização.');
                        btn.disabled = false;
                        return;
                    }
                    
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            
                            console.log('✅ GPS obtido:', lat, lng);
                            
                            // Salvar no localStorage
                            localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
                            localStorage.setItem('vc_location_accepted', 'true');
                            
                            // Fechar popup
                            document.cookie = "vc_welcome_popup_seen=1; path=/; max-age=" + (30 * 24 * 60 * 60);
                            popup.classList.remove('is-open');
                            
                            // Redirecionar com coordenadas
                            const separator = window.location.href.includes('?') ? '&' : '?';
                            window.location.href = window.location.pathname + separator + 'lat=' + lat.toFixed(6) + '&lng=' + lng.toFixed(6);
                        },
                        (error) => {
                            console.error('❌ Erro GPS:', error);
                            let msg = 'Erro ao obter localização.';
                            if(error.code === 1) msg = 'Por favor, permita o acesso à sua localização no navegador.';
                            alert(msg);
                            btn.innerText = '📍 Ver restaurantes perto de mim';
                            btn.disabled = false;
                            btn.style.opacity = '1';
                        },
                        { timeout: 10000, enableHighAccuracy: true }
                    );
                    return;
                }
                
                // Botões de fechar
                if (e.target.closest('.welcome-popup__close') || e.target.id === 'welcome-popup-skip-btn' || e.target.closest('#welcome-popup-skip-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    popup.classList.remove('is-open');
                    document.cookie = "vc_welcome_popup_seen=1; path=/; max-age=" + (30 * 24 * 60 * 60);
                    return;
                }
                
                // Clicar fora
                if (e.target === popup) {
                    popup.classList.remove('is-open');
                    document.cookie = "vc_welcome_popup_seen=1; path=/; max-age=" + (30 * 24 * 60 * 60);
                }
            });
        }
        
        // Função para garantir cliques nos cards
        function initCards() {
            const cards = document.querySelectorAll('.vc-card');
            cards.forEach(card => {
                const link = card.querySelector('.vc-card__link');
                if (link) {
                    // Garantir que o link funcione
                    link.style.pointerEvents = 'auto';
                    link.style.cursor = 'pointer';
                    
                    // Adicionar listener de clique no card inteiro (exceto botões)
                    card.addEventListener('click', function(e) {
                        // Se clicou em botão, não fazer nada
                        if (e.target.closest('.vc-btn--menu') || e.target.closest('.vc-favorite-btn')) {
                            return;
                        }
                        
                        // Se clicou no link ou dentro dele, seguir o link
                        if (e.target.closest('.vc-card__link')) {
                            const href = link.getAttribute('href');
                            if (href) {
                                window.location.href = href;
                            }
                        }
                    });
                }
            });
        }
        
        // Inicializar quando DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                console.log('📄 DOMContentLoaded - inicializando...');
                initPopup();
                initCards();
            });
        } else {
            console.log('📄 DOM já pronto - inicializando...');
            initPopup();
            initCards();
        }
        
        // Também tentar após window.load
        window.addEventListener('load', function() {
            console.log('🪟 Window load - verificando novamente...');
            setTimeout(() => {
                initPopup();
                initCards();
            }, 500);
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'vemcomer_force_popup_and_cards', 9999 );

/**
 * Registra áreas de widgets
 */
function vemcomer_theme_widgets_init() {
    register_sidebar([
        'name'          => __( 'Sidebar Principal', 'vemcomer' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Widgets que aparecem na sidebar.', 'vemcomer' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ]);
    
    register_sidebar([
        'name'          => __( 'Rodapé 1', 'vemcomer' ),
        'id'            => 'footer-1',
        'description'   => __( 'Primeira coluna do rodapé.', 'vemcomer' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);
    
    register_sidebar([
        'name'          => __( 'Rodapé 2', 'vemcomer' ),
        'id'            => 'footer-2',
        'description'   => __( 'Segunda coluna do rodapé.', 'vemcomer' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);
    
    register_sidebar([
        'name'          => __( 'Rodapé 3', 'vemcomer' ),
        'id'            => 'footer-3',
        'description'   => __( 'Terceira coluna do rodapé.', 'vemcomer' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);
}
add_action( 'widgets_init', 'vemcomer_theme_widgets_init' );

/**
 * Adiciona classes ao body
 */
function vemcomer_theme_body_classes( $classes ) {
    if ( is_front_page() ) {
        $classes[] = 'is-home';
    }
    
    if ( is_user_logged_in() ) {
        $classes[] = 'user-logged-in';
    }
    
    return $classes;
}
add_filter( 'body_class', 'vemcomer_theme_body_classes' );

/**
 * Helper para verificar se plugin está ativo
 */
function vemcomer_is_plugin_active() {
    return class_exists( 'VC\Frontend\Shortcodes' ) || function_exists( 'vc_sc_mark_used' );
}

/**
 * Helper para obter URL do template
 */
function vemcomer_get_template_url( $path = '' ) {
    return get_template_directory_uri() . ( $path ? '/' . ltrim( $path, '/' ) : '' );
}

/**
 * Helper para obter caminho do template
 */
function vemcomer_get_template_path( $path = '' ) {
    return get_template_directory() . ( $path ? '/' . ltrim( $path, '/' ) : '' );
}

/**
 * Menu padrão quando nenhum menu está atribuído
 */
function vemcomer_default_menu() {
    echo '<ul id="primary-menu" class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Início', 'vemcomer' ) . '</a></li>';
    if ( vemcomer_is_plugin_active() ) {
        echo '<li><a href="' . esc_url( home_url( '/restaurantes/' ) ) . '">' . esc_html__( 'Restaurantes', 'vemcomer' ) . '</a></li>';
    }
    echo '</ul>';
}

