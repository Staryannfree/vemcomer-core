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
 * Verifica qual template da home usar
 */
function vemcomer_get_home_template() {
    // Verificar constante
    if ( defined( 'VC_HOME_TEMPLATE_V2' ) && VC_HOME_TEMPLATE_V2 ) {
        return 'front-page-v2.php';
    }
    
    // Verificar filtro
    $template = apply_filters( 'vemcomer_home_template_version', 'front-page.php' );
    if ( $template !== 'front-page.php' ) {
        return $template;
    }
    
    // Verificar opção do tema (se existir)
    $saved_template = get_option( 'vemcomer_home_template', 'default' );
    if ( $saved_template === 'v2' ) {
        return 'front-page-v2.php';
    }
    
    return 'front-page.php';
}

/**
 * Filtro para usar template alternativo da home
 */
function vemcomer_template_include( $template ) {
    if ( is_front_page() && ! is_home() ) {
        $home_template = vemcomer_get_home_template();
        $template_path = get_template_directory() . '/' . $home_template;
        if ( file_exists( $template_path ) ) {
            return $template_path;
        }
    }
    return $template;
}
add_filter( 'template_include', 'vemcomer_template_include', 99 );

/**
 * Enfileira estilos e scripts
 */
function vemcomer_theme_scripts() {
    $theme_version = wp_get_theme()->get( 'Version' );
    
    // Estilos
    wp_enqueue_style( 'vemcomer-theme-style', get_stylesheet_uri(), [], $theme_version );
    wp_enqueue_style( 'vemcomer-theme-main', get_template_directory_uri() . '/assets/css/main.css', [], $theme_version );
    if ( is_front_page() ) {
        $home_template = vemcomer_get_home_template();
        if ( $home_template === 'front-page-v2.php' ) {
            // Carregar Font Awesome para versão 2
            wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2' );
        } else {
            wp_enqueue_style( 'vemcomer-home-improvements', get_template_directory_uri() . '/assets/css/home-improvements.css', [], $theme_version );
        }
    }
    
    // Scripts
    wp_enqueue_script( 'vemcomer-theme-main', get_template_directory_uri() . '/assets/js/main.js', [], $theme_version, true );
    if ( is_front_page() ) {
        // Carregar scripts da home (funcionam em ambos os templates)
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
 * Popup de boas-vindas independente - Solução que funciona
 * Prioridade alta para carregar depois de tudo
 */
function popup_boas_vindas_independente() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <style>
        /* CSS FORÇADO - Mantido */
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
        /* Garante clique nos botões */
        .welcome-popup__dialog, .welcome-popup__dialog button {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 2147483648 !important;
        }
    </style>
    <style>
        /* Mensagem de localização */
        .location-message {
            position: fixed !important;
            top: 20px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            background: #2f9e44 !important;
            color: #fff !important;
            padding: 1rem 2rem !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            z-index: 2147483649 !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            animation: slideDown 0.3s ease-out !important;
            max-width: 90% !important;
            text-align: center !important;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        body.dark-mode .location-message {
            background: #1e7e34 !important;
        }
    </style>
    <script>
    // Função para mostrar mensagem de localização
    function showLocationMessage(message) {
        // Remove mensagem anterior se existir
        const existing = document.querySelector('.location-message');
        if (existing) {
            existing.remove();
        }
        
        // Cria nova mensagem
        const msgDiv = document.createElement('div');
        msgDiv.className = 'location-message';
        msgDiv.textContent = message;
        document.body.appendChild(msgDiv);
        
        // Remove após 3 segundos
        setTimeout(() => {
            msgDiv.style.animation = 'slideDown 0.3s ease-out reverse';
            setTimeout(() => {
                if (msgDiv.parentNode) {
                    msgDiv.remove();
                }
            }, 300);
        }, 3000);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const popup = document.getElementById('welcome-popup');
        if (!popup) return;

        // Verificar se deve mostrar o popup:
        // 1. Se já tem localização salva, não mostrar
        const savedLocation = localStorage.getItem('vc_user_location');
        if (savedLocation) {
            return; // Já tem localização, não precisa mostrar popup
        }

        // 2. Verificar se popup já foi visto (cookie)
        const cookies = document.cookie.split(';');
        const popupSeen = cookies.some(c => c.trim().startsWith('vc_welcome_popup_seen=1'));
        if (popupSeen) {
            return; // Já foi visto antes, não mostrar novamente
        }

        // 3. Se chegou aqui, mostrar popup (primeira visita e sem localização)
        setTimeout(() => {
            popup.classList.add('is-open');
        }, 1000);

        // 2. Listener de Clique (Javascript Puro)
        popup.addEventListener('click', function(e) {
            
            // >>> BOTÃO VERDE (LOCALIZAÇÃO) <<<
            if (e.target.id === 'welcome-popup-location-btn') {
                e.preventDefault();
                const btn = e.target;
                
                // Feedback visual
                btn.innerText = '📍 Obtendo GPS...';
                btn.style.opacity = '0.8';

                if (!navigator.geolocation) {
                    alert('Seu navegador não suporta geolocalização.');
                    return;
                }

                // Pega a posição (Independente do tema)
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        btn.innerText = '🔄 Atualizando...';

                        // Fecha o popup
                        document.cookie = "vc_welcome_popup_seen=1; path=/; max-age=3600";
                        popup.classList.remove('is-open');

                        // Obter nome da cidade via reverse geocoding
                        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&addressdetails=1')
                            .then(response => response.json())
                            .then(data => {
                                const cityName = data.address?.city || 
                                                data.address?.town || 
                                                data.address?.municipality || 
                                                data.address?.county || 
                                                data.display_name?.split(',')[0] || 
                                                'Localização desconhecida';
                                
                                // Atualizar título do hero
                                const heroTitle = document.getElementById('hero-title') || document.querySelector('.home-hero__title');
                                if (heroTitle) {
                                    heroTitle.textContent = 'Peça dos melhores restaurantes de ' + cityName;
                                }
                                
                                // Atualizar subtítulo com número de restaurantes
                                if (window.updateHeroSubtitleWithRestaurantCount) {
                                    window.updateHeroSubtitleWithRestaurantCount(cityName);
                                }
                                
                                // Mostrar mensagem na tela
                                showLocationMessage('Você está em: ' + cityName);
                                
                                // Salvar no localStorage
                                localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
                                localStorage.setItem('vc_user_city', cityName);
                                
                                // Fecha o popup
                                document.cookie = "vc_welcome_popup_seen=1; path=/; max-age=3600";
                                popup.classList.remove('is-open');
                                
                                // Filtrar restaurantes por cidade sem redirecionar
                                if (window.filterRestaurantsByCity) {
                                    window.filterRestaurantsByCity(cityName);
                                }
                            })
                            .catch(error => {
                                console.error('Erro ao obter nome da cidade:', error);
                                showLocationMessage('Você está em: Localização obtida (cidade não identificada)');
                                
                                // Mesmo sem cidade, salva e atualiza
                                localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
                                document.cookie = "vc_welcome_popup_seen=1; path=/; max-age=3600";
                                popup.classList.remove('is-open');
                                
                                // Tentar filtrar mesmo sem cidade identificada
                                if (window.filterRestaurantsByCity) {
                                    window.filterRestaurantsByCity('Localização obtida');
                                }
                            });
                    },
                    (error) => {
                        console.error('POPUP: Erro GPS', error);
                        let msg = 'Erro ao obter localização.';
                        if(error.code === 1) msg = 'Por favor, permita o acesso à sua localização no navegador.';
                        alert(msg);
                        btn.innerText = 'Tentar Novamente';
                    },
                    { timeout: 10000 }
                );
            }

            // Botões de Fechar
            if (e.target.closest('.welcome-popup__close') || e.target.id === 'welcome-popup-skip-btn') {
                e.preventDefault();
                popup.classList.remove('is-open');
                document.cookie = "vc_welcome_popup_seen=1; path=/; max-age=3600";
            }

            // Clicar fora
            if (e.target === popup) {
                popup.classList.remove('is-open');
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'popup_boas_vindas_independente', 9999);

/**
 * Padronização de modo escuro e popup de cadastro - Solução inline forçada
 * Prioridade alta para carregar depois de tudo
 */
function padronizacao_modo_escuro_e_cadastro() {
    ?>
    <style>
        /* ===== MODO ESCURO PADRONIZADO - FORÇADO ===== */
        body.dark-mode {
            --color-bg: #111827 !important;
            --color-bg-light: #1f2937 !important;
            --color-bg-dark: #0f172a !important;
            --color-text: #f9fafb !important;
            --color-text-light: #d1d5db !important;
            --color-border: #374151 !important;
            --color-primary: #2f9e44 !important;
            --color-primary-dark: #1e7e34 !important;
            --color-secondary: #f9fafb !important;
        }
        
        /* Header no modo escuro */
        body.dark-mode .site-header {
            background: var(--color-bg) !important;
            border-bottom-color: var(--color-border) !important;
        }
        
        /* Botões no modo escuro */
        body.dark-mode .btn--ghost {
            border-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }
        body.dark-mode .btn--ghost:hover {
            background: var(--color-bg-light) !important;
        }
        
        /* Menu mobile no modo escuro */
        body.dark-mode .main-navigation > ul {
            background: var(--color-bg) !important;
        }
        body.dark-mode .main-navigation a {
            color: var(--color-text) !important;
        }
        
        /* User menu no modo escuro */
        body.dark-mode .user-menu__dropdown {
            background: var(--color-bg) !important;
            border-color: var(--color-border) !important;
        }
        body.dark-mode .user-menu__dropdown a {
            color: var(--color-text) !important;
        }
        
        /* Cards no modo escuro */
        body.dark-mode .vc-card {
            background: var(--color-bg-light) !important;
            border-color: var(--color-border) !important;
        }
        body.dark-mode .vc-card__title,
        body.dark-mode .vc-title {
            color: var(--color-text) !important;
        }
        
        /* Títulos de seções no modo escuro */
        body.dark-mode .section-title {
            color: var(--color-text) !important;
        }
        
        /* Categorias no modo escuro */
        body.dark-mode .home-categories {
            background: var(--color-bg) !important;
        }
        body.dark-mode .category-card {
            background: var(--color-bg-light) !important;
            border-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }
        body.dark-mode .category-card__name {
            color: var(--color-text) !important;
        }
        body.dark-mode .category-card__count {
            color: var(--color-text-light) !important;
        }
        body.dark-mode .category-card:hover {
            background: var(--color-bg) !important;
            border-color: var(--color-primary) !important;
        }
        
        /* Seção de restaurantes no modo escuro */
        body.dark-mode .home-restaurants {
            background: var(--color-bg) !important;
        }
        body.dark-mode .home-featured {
            background: var(--color-bg) !important;
        }
        
        /* Título "Restaurantes" no modo escuro */
        body.dark-mode .section-title,
        body.dark-mode h2.section-title,
        body.dark-mode .home-restaurants h2,
        body.dark-mode .home-featured h2 {
            color: var(--color-text) !important;
        }
        
        /* Filtros rápidos no modo escuro - FORÇADO */
        body.dark-mode .home-quick-filters {
            background: var(--color-bg-light) !important;
            border-color: var(--color-border) !important;
        }
        body.dark-mode .home-quick-filters__count {
            color: var(--color-text-light) !important;
        }
        body.dark-mode .filter-chip {
            background: var(--color-bg) !important;
            border-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }
        body.dark-mode .filter-chip:hover {
            background: var(--color-bg-light) !important;
            border-color: var(--color-primary) !important;
        }
        body.dark-mode .filter-chip.is-active {
            background: var(--color-primary) !important;
            border-color: var(--color-primary) !important;
            color: #fff !important;
        }
        /* Labels e ícones dos filter-chip no modo escuro */
        body.dark-mode .filter-chip .filter-chip__label,
        body.dark-mode .filter-chip .filter-chip__icon {
            color: var(--color-text) !important;
        }
        body.dark-mode .filter-chip.is-active .filter-chip__label,
        body.dark-mode .filter-chip.is-active .filter-chip__icon {
            color: #fff !important;
        }
        body.dark-mode .filter-chip:hover .filter-chip__label,
        body.dark-mode .filter-chip:hover .filter-chip__icon {
            color: var(--color-text) !important;
        }
        
        /* Botão "Limpar filtros" no modo escuro */
        body.dark-mode .btn--ghost,
        body.dark-mode #clear-filters {
            background: var(--color-bg) !important;
            border-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }
        body.dark-mode .btn--ghost:hover,
        body.dark-mode #clear-filters:hover {
            background: var(--color-bg-light) !important;
            border-color: var(--color-primary) !important;
        }
        
        /* Autocomplete no modo escuro */
        body.dark-mode .search-autocomplete {
            background: var(--color-bg-light) !important;
            border-color: var(--color-border) !important;
        }
        body.dark-mode .search-autocomplete__item {
            border-bottom-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }
        body.dark-mode .search-autocomplete__item:hover,
        body.dark-mode .search-autocomplete__item.is-selected {
            background: var(--color-bg) !important;
        }
        body.dark-mode .search-autocomplete__title {
            color: var(--color-text) !important;
        }
        body.dark-mode .search-autocomplete__subtitle {
            color: var(--color-text-light) !important;
        }
        
        /* Skeleton loading no modo escuro */
        body.dark-mode .skeleton-card {
            background: var(--color-bg-light) !important;
            border-color: var(--color-border) !important;
        }
        body.dark-mode .skeleton-image,
        body.dark-mode .skeleton-title,
        body.dark-mode .skeleton-line {
            background: linear-gradient(90deg, var(--color-bg) 25%, var(--color-bg-light) 50%, var(--color-bg) 75%) !important;
        }
        
        /* Notificação no modo escuro */
        body.dark-mode .notification {
            background: var(--color-bg-light) !important;
            border-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }
        
        /* Dark mode toggle no modo escuro */
        body.dark-mode .dark-mode-toggle {
            color: var(--color-text) !important;
            border-color: var(--color-border) !important;
        }
        body.dark-mode .dark-mode-toggle:hover {
            background: var(--color-bg-light) !important;
        }
        
        /* ===== POPUP DE CADASTRO - FORÇADO ===== */
        #signup-popup {
            position: fixed !important;
            inset: 0 !important;
            z-index: 99998 !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.3s, visibility 0.3s !important;
            pointer-events: none !important;
        }
        
        #signup-popup.is-open {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }
        
        #signup-popup .signup-popup__overlay {
            position: absolute !important;
            inset: 0 !important;
            background: rgba(0, 0, 0, 0.6) !important;
            backdrop-filter: blur(4px) !important;
        }
        
        #signup-popup .signup-popup__dialog {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            background: #fff !important;
            border-radius: 16px !important;
            padding: 2rem !important;
            max-width: 600px !important;
            width: 90% !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
            z-index: 1 !important;
        }
        
        body.dark-mode #signup-popup .signup-popup__dialog {
            background: var(--color-bg) !important;
            color: var(--color-text) !important;
        }
        
        #signup-popup .signup-popup__close {
            position: absolute !important;
            top: 1rem !important;
            right: 1rem !important;
            background: none !important;
            border: none !important;
            font-size: 2rem !important;
            line-height: 1 !important;
            color: #6b7280 !important;
            cursor: pointer !important;
            padding: 0 !important;
            width: 32px !important;
            height: 32px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        body.dark-mode #signup-popup .signup-popup__close {
            color: var(--color-text-light) !important;
        }
        
        #signup-popup .signup-popup__title {
            font-size: 1.75rem !important;
            font-weight: 700 !important;
            margin: 0 0 1.5rem 0 !important;
            text-align: center !important;
            color: #111827 !important;
        }
        
        body.dark-mode #signup-popup .signup-popup__title {
            color: var(--color-text) !important;
        }
        
        #signup-popup .signup-popup__options {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
            gap: 1rem !important;
        }
        
        #signup-popup .signup-popup__option {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
            padding: 1.5rem !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            text-decoration: none !important;
            color: inherit !important;
            transition: all 0.3s !important;
            cursor: pointer !important;
            background: #fff !important;
        }
        
        body.dark-mode #signup-popup .signup-popup__option {
            background: var(--color-bg-light) !important;
            border-color: var(--color-border) !important;
            color: var(--color-text) !important;
        }
        
        #signup-popup .signup-popup__option:hover {
            border-color: #2f9e44 !important;
            background: #f9fafb !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }
        
        body.dark-mode #signup-popup .signup-popup__option:hover {
            background: var(--color-bg) !important;
            border-color: var(--color-primary) !important;
        }
        
        #signup-popup .signup-popup__icon {
            font-size: 3rem !important;
            margin-bottom: 1rem !important;
        }
        
        #signup-popup .signup-popup__option-title {
            font-size: 1.25rem !important;
            font-weight: 600 !important;
            margin: 0 0 0.5rem 0 !important;
            color: #111827 !important;
        }
        
        body.dark-mode #signup-popup .signup-popup__option-title {
            color: var(--color-text) !important;
        }
        
        #signup-popup .signup-popup__option-text {
            font-size: 0.95rem !important;
            color: #6b7280 !important;
            margin: 0 !important;
        }
        
        body.dark-mode #signup-popup .signup-popup__option-text {
            color: var(--color-text-light) !important;
        }
        
        @media (max-width: 768px) {
            #signup-popup .signup-popup__options {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    <script>
    (function() {
        'use strict';
        
        document.addEventListener('DOMContentLoaded', function() {
            // ===== POPUP DE CADASTRO =====
            const btnCadastro = document.getElementById('btn-cadastro');
            const btnCadastroHome = document.getElementById('btn-cadastro-home');
            const signupPopup = document.getElementById('signup-popup');
            
            function openSignupPopup(e) {
                if (e) {
                    e.preventDefault();
                }
                if (signupPopup) {
                    signupPopup.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                }
            }
            
            function closeSignupPopup() {
                if (signupPopup) {
                    signupPopup.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
            }
            
            // Botão do header
            if (btnCadastro) {
                btnCadastro.addEventListener('click', openSignupPopup);
            }
            
            // Botão da home
            if (btnCadastroHome) {
                btnCadastroHome.addEventListener('click', openSignupPopup);
            }
            
            // Fechar popup
            const signupClose = signupPopup?.querySelector('.signup-popup__close');
            const signupOverlay = signupPopup?.querySelector('.signup-popup__overlay');
            
            if (signupClose) {
                signupClose.addEventListener('click', closeSignupPopup);
            }
            
            if (signupOverlay) {
                signupOverlay.addEventListener('click', closeSignupPopup);
            }
            
            // Fechar com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && signupPopup?.classList.contains('is-open')) {
                    closeSignupPopup();
                }
            });
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'padronizacao_modo_escuro_e_cadastro', 9999);

/**
 * Mensagem de localização e botão da home - Solução inline forçada
 * Prioridade alta para carregar depois de tudo
 */
function mensagem_localizacao_botao_home() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <script>
    (function() {
        'use strict';
        
        // Função global para mostrar mensagem de localização
        window.showLocationMessage = function(message) {
            // Remove mensagem anterior se existir
            const existing = document.querySelector('.location-message');
            if (existing) {
                existing.remove();
            }
            
            // Cria nova mensagem
            const msgDiv = document.createElement('div');
            msgDiv.className = 'location-message';
            msgDiv.textContent = message;
            document.body.appendChild(msgDiv);
            
            // Remove após 3 segundos
            setTimeout(() => {
                msgDiv.style.animation = 'slideDown 0.3s ease-out reverse';
                setTimeout(() => {
                    if (msgDiv.parentNode) {
                        msgDiv.remove();
                    }
                }, 300);
            }, 3000);
        };
        
        // Função para obter nome da cidade
        function getCityName(lat, lng) {
            return fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&addressdetails=1')
                .then(response => response.json())
                .then(data => {
                    return data.address?.city || 
                           data.address?.town || 
                           data.address?.municipality || 
                           data.address?.county || 
                           data.display_name?.split(',')[0] || 
                           'Localização desconhecida';
                })
                .catch(error => {
                    console.error('Erro ao obter nome da cidade:', error);
                    return 'Localização obtida';
                });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Botão "Usar minha localização" da home
            const btnHomeLocation = document.getElementById('vc-use-location');
            
            if (btnHomeLocation) {
                btnHomeLocation.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (!navigator.geolocation) {
                        alert('Seu navegador não suporta geolocalização.');
                        return;
                    }
                    
                    // Feedback visual
                    const originalText = btnHomeLocation.innerHTML;
                    btnHomeLocation.innerHTML = '<span class="btn-geolocation__icon">📍</span><span class="btn-geolocation__text">Obtendo GPS...</span>';
                    btnHomeLocation.disabled = true;
                    
                    navigator.geolocation.getCurrentPosition(
                        async (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            
                            // Obter nome da cidade
                            const cityName = await getCityName(lat, lng);
                            
                            // Atualizar título do hero
                            const heroTitle = document.getElementById('hero-title') || document.querySelector('.home-hero__title');
                            if (heroTitle) {
                                heroTitle.textContent = 'Peça dos melhores restaurantes de ' + cityName;
                            }
                            
                            // Atualizar subtítulo com número de restaurantes
                            if (window.updateHeroSubtitleWithRestaurantCount) {
                                window.updateHeroSubtitleWithRestaurantCount(cityName);
                            }
                            
                            // Mostrar mensagem na tela
                            window.showLocationMessage('Você está em: ' + cityName);
                            
                            // Salvar no localStorage
                            localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
                            localStorage.setItem('vc_user_city', cityName);
                            
                            // Restaurar botão
                            btnHomeLocation.innerHTML = originalText;
                            btnHomeLocation.disabled = false;
                            btnHomeLocation.classList.add('is-active');
                            
                            // Filtrar restaurantes por cidade sem redirecionar
                            if (window.filterRestaurantsByCity) {
                                window.filterRestaurantsByCity(cityName);
                            } else if (window.loadRestaurantsWithLocation) {
                                window.loadRestaurantsWithLocation(lat, lng);
                            }
                        },
                        (error) => {
                            console.error('Erro GPS:', error);
                            let msg = 'Erro ao obter localização.';
                            if(error.code === 1) msg = 'Por favor, permita o acesso à sua localização no navegador.';
                            alert(msg);
                            btnHomeLocation.innerHTML = originalText;
                            btnHomeLocation.disabled = false;
                        },
                        { timeout: 10000, enableHighAccuracy: true }
                    );
                });
            }
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'mensagem_localizacao_botao_home', 9999);

/**
 * Popup de login/cadastro para ações que requerem autenticação
 * Prioridade alta para carregar depois de tudo
 */
function popup_login_cadastro_acoes() {
    if ( is_user_logged_in() ) {
        return; // Não precisa mostrar se já está logado
    }
    
    // Buscar URLs de cadastro
    $customer_signup_pages = get_posts([
        'post_type' => 'page',
        's' => 'cadastro cliente',
        'posts_per_page' => 1,
    ]);
    $customer_url = ! empty( $customer_signup_pages ) 
        ? get_permalink( $customer_signup_pages[0]->ID ) 
        : home_url( '/cadastro-cliente/' );
    $login_url = wp_login_url( get_permalink() );
    ?>
    <div class="login-required-popup" id="login-required-popup">
        <div class="login-required-popup__overlay"></div>
        <div class="login-required-popup__dialog">
            <button class="login-required-popup__close" aria-label="<?php esc_attr_e( 'Fechar', 'vemcomer' ); ?>">&times;</button>
            <div class="login-required-popup__icon">🔒</div>
            <h2 class="login-required-popup__title"><?php esc_html_e( 'Login necessário', 'vemcomer' ); ?></h2>
            <p class="login-required-popup__text">
                <?php esc_html_e( 'Você precisa estar logado para realizar esta ação.', 'vemcomer' ); ?>
            </p>
            <div class="login-required-popup__actions">
                <a href="<?php echo esc_url( $login_url ); ?>" class="btn btn--primary btn--large">
                    <?php esc_html_e( 'Fazer Login', 'vemcomer' ); ?>
                </a>
                <a href="<?php echo esc_url( $customer_url ); ?>" class="btn btn--ghost">
                    <?php esc_html_e( 'Criar Conta', 'vemcomer' ); ?>
                </a>
            </div>
        </div>
    </div>
    <style>
        /* Popup de Login/Cadastro - FORÇADO */
        #login-required-popup {
            position: fixed !important;
            inset: 0 !important;
            z-index: 99997 !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.3s, visibility 0.3s !important;
            pointer-events: none !important;
        }
        
        #login-required-popup.is-open {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }
        
        #login-required-popup .login-required-popup__overlay {
            position: absolute !important;
            inset: 0 !important;
            background: rgba(0, 0, 0, 0.6) !important;
            backdrop-filter: blur(4px) !important;
        }
        
        #login-required-popup .login-required-popup__dialog {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            background: #fff !important;
            border-radius: 16px !important;
            padding: 2rem !important;
            max-width: 450px !important;
            width: 90% !important;
            text-align: center !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
            z-index: 1 !important;
        }
        
        body.dark-mode #login-required-popup .login-required-popup__dialog {
            background: var(--color-bg) !important;
            color: var(--color-text) !important;
        }
        
        #login-required-popup .login-required-popup__close {
            position: absolute !important;
            top: 1rem !important;
            right: 1rem !important;
            background: none !important;
            border: none !important;
            font-size: 2rem !important;
            line-height: 1 !important;
            color: #6b7280 !important;
            cursor: pointer !important;
            padding: 0 !important;
            width: 32px !important;
            height: 32px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        body.dark-mode #login-required-popup .login-required-popup__close {
            color: var(--color-text-light) !important;
        }
        
        #login-required-popup .login-required-popup__icon {
            font-size: 4rem !important;
            margin-bottom: 1rem !important;
        }
        
        #login-required-popup .login-required-popup__title {
            font-size: 1.75rem !important;
            font-weight: 700 !important;
            margin: 0 0 1rem 0 !important;
            color: #111827 !important;
        }
        
        body.dark-mode #login-required-popup .login-required-popup__title {
            color: var(--color-text) !important;
        }
        
        #login-required-popup .login-required-popup__text {
            font-size: 1rem !important;
            color: #6b7280 !important;
            margin: 0 0 1.5rem 0 !important;
            line-height: 1.5 !important;
        }
        
        body.dark-mode #login-required-popup .login-required-popup__text {
            color: var(--color-text-light) !important;
        }
        
        #login-required-popup .login-required-popup__actions {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
        }
        
        #login-required-popup .login-required-popup__actions .btn {
            width: 100% !important;
            text-align: center !important;
        }
    </style>
    <script>
    (function() {
        'use strict';
        
        // Função global para mostrar popup de login
        window.showLoginRequiredPopup = function() {
            const popup = document.getElementById('login-required-popup');
            if (popup) {
                popup.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }
        };
        
        // Função para fechar popup
        function closeLoginPopup() {
            const popup = document.getElementById('login-required-popup');
            if (popup) {
                popup.classList.remove('is-open');
                document.body.style.overflow = '';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('login-required-popup');
            if (!popup) return;
            
            // Verificar se usuário está logado
            const isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
            if (isLoggedIn) return;
            
            // Fechar popup
            const closeBtn = popup.querySelector('.login-required-popup__close');
            const overlay = popup.querySelector('.login-required-popup__overlay');
            
            if (closeBtn) {
                closeBtn.addEventListener('click', closeLoginPopup);
            }
            
            if (overlay) {
                overlay.addEventListener('click', closeLoginPopup);
            }
            
            // Fechar com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && popup.classList.contains('is-open')) {
                    closeLoginPopup();
                }
            });
            
            // Interceptar cliques em favoritos
            document.addEventListener('click', function(e) {
                const favoriteBtn = e.target.closest('.vc-favorite-btn');
                if (favoriteBtn && !isLoggedIn) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.showLoginRequiredPopup();
                }
            }, true); // Capture phase para pegar antes de outros handlers
            
            // Interceptar adicionar ao carrinho
            document.addEventListener('click', function(e) {
                const addBtn = e.target.closest('.vc-add, .vc-menu-item__add, [data-action="add-to-cart"]');
                if (addBtn && !isLoggedIn) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.showLoginRequiredPopup();
                }
            }, true); // Capture phase
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'popup_login_cadastro_acoes', 9999);

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

