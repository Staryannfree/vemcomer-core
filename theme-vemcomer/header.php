<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <?php if ( wp_is_mobile() ) : ?>
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <?php endif; ?>
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php 
    $body_classes = get_body_class();
    if ( function_exists( 'vc_is_standalone_mode' ) && vc_is_standalone_mode() ) {
        $body_classes[] = 'vc-standalone-mode';
    }
    body_class( $body_classes );
?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Pular para o conteúdo', 'vemcomer' ); ?></a>

    <?php
    // Top Bar Minimalista (Mobile Only)
    if ( wp_is_mobile() ) :
        // Priorizar bairro sobre cidade
        $user_neighborhood = isset( $_COOKIE['vc_user_neighborhood'] ) ? sanitize_text_field( $_COOKIE['vc_user_neighborhood'] ) : '';
        $user_city = isset( $_COOKIE['vc_user_city'] ) ? sanitize_text_field( $_COOKIE['vc_user_city'] ) : '';
        $user_address = isset( $_COOKIE['vc_user_location'] ) ? json_decode( stripslashes( $_COOKIE['vc_user_location'] ), true ) : null;
        
        // Prioridade: bairro > cidade > endereço completo > texto padrão
        $address_text = ! empty( $user_neighborhood ) 
            ? $user_neighborhood 
            : ( ! empty( $user_city ) 
                ? $user_city 
                : ( ! empty( $user_address['address'] ) 
                    ? $user_address['address'] 
                    : __( 'Selecione um endereço', 'vemcomer' ) 
                ) 
            );
    ?>
    <div class="mobile-top-bar" id="mobile-top-bar">
        <div class="mobile-top-bar__content">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mobile-top-bar__logo">
                <?php bloginfo( 'name' ); ?>
            </a>
            <div class="mobile-top-bar__address" id="mobile-address-selector" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Selecionar endereço de entrega', 'vemcomer' ); ?>">
                <svg class="location-icon" viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: var(--primary-color);">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                <span class="mobile-top-bar__address-text" id="mobile-address-text"><?php echo esc_html( $address_text ); ?></span>
                <span class="mobile-top-bar__address-arrow">▾</span>
            </div>
            <button class="notification-btn" id="notificationBtn" aria-label="<?php esc_attr_e( 'Notificações', 'vemcomer' ); ?>">
                <svg class="notification-icon" viewBox="0 0 24 24">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>
                </svg>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <header id="masthead" class="site-header">
        <div class="site-header__container">
            <div class="site-header__branding">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <h1 class="site-title">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                            <?php bloginfo( 'name' ); ?>
                        </a>
                    </h1>
                <?php endif; ?>
            </div>

            <nav id="site-navigation" class="main-navigation">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="menu-toggle__icon"></span>
                    <span class="screen-reader-text"><?php esc_html_e( 'Menu Principal', 'vemcomer' ); ?></span>
                </button>
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                    'fallback_cb'    => 'vemcomer_default_menu',
                ]);

                $lojista_links = [];

                if ( is_user_logged_in() ) {
                    $current_user = wp_get_current_user();

                    if ( $current_user instanceof WP_User && in_array( 'lojista', (array) $current_user->roles, true ) ) {
                        $panel_page = vemcomer_get_marketplace_template_page( 'templates/marketplace/painel-lojista-plano-gratis.php' );
                        $panel_url  = $panel_page ? get_permalink( $panel_page ) : home_url( '/painel-restaurante/' );

                        $lojista_links = [
                            [ 'label' => __( 'Dashboard', 'vemcomer' ), 'href' => $panel_url ],
                            [ 'label' => __( 'Cardápio', 'vemcomer' ), 'href' => home_url( '/gestao-cardapio/' ) ],
                            [ 'label' => __( 'Configuração da Loja', 'vemcomer' ), 'href' => home_url( '/configuracao-loja/' ) ],
                            [ 'label' => __( 'Onboarding', 'vemcomer' ), 'href' => home_url( '/wizard-onboarding/' ) ],
                            [ 'label' => __( 'Marketing', 'vemcomer' ), 'href' => home_url( '/central-marketing/' ) ],
                            [ 'label' => __( 'Eventos', 'vemcomer' ), 'href' => home_url( '/gestor-eventos/' ) ],
                            [ 'label' => __( 'Analytics', 'vemcomer' ), 'href' => $panel_url . '#analytics' ],
                            [ 'label' => __( 'Planos', 'vemcomer' ), 'href' => $panel_url . '#planos' ],
                        ];
                    }
                }
                ?>

                <?php if ( ! empty( $lojista_links ) ) : ?>
                    <div class="lojista-mobile-menu" aria-label="<?php esc_attr_e( 'Menu do Lojista', 'vemcomer' ); ?>">
                        <div class="lojista-mobile-menu__title"><?php esc_html_e( 'Menu do Lojista', 'vemcomer' ); ?></div>
                        <ul class="lojista-mobile-menu__list">
                            <?php foreach ( $lojista_links as $item ) : ?>
                                <li><a href="<?php echo esc_url( $item['href'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="site-header__actions">
                <button class="dark-mode-toggle" id="dark-mode-toggle" aria-label="<?php esc_attr_e( 'Alternar modo escuro', 'vemcomer' ); ?>">
                    🌙
                </button>
                <?php if ( is_user_logged_in() ) : ?>
                    <?php
                    $current_user = wp_get_current_user();
                    $user_roles = $current_user->roles;
                    ?>
                    <div class="user-menu">
                        <button class="user-menu__toggle" aria-expanded="false">
                            <span class="user-menu__avatar">
                                <?php echo get_avatar( $current_user->ID, 32 ); ?>
                            </span>
                            <span class="user-menu__name"><?php echo esc_html( $current_user->display_name ); ?></span>
                        </button>
                        <ul class="user-menu__dropdown">
                            <?php if ( in_array( 'lojista', $user_roles, true ) ) : ?>
                                <li><a href="<?php echo esc_url( home_url( '/meu-restaurante/' ) ); ?>"><?php esc_html_e( 'Meu Restaurante', 'vemcomer' ); ?></a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo esc_url( home_url( '/meus-pedidos/' ) ); ?>"><?php esc_html_e( 'Meus Pedidos', 'vemcomer' ); ?></a></li>
                            <li><a href="<?php echo esc_url( home_url( '/meus-favoritos/' ) ); ?>"><?php esc_html_e( 'Meus Favoritos', 'vemcomer' ); ?></a></li>
                            <li><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><?php esc_html_e( 'Sair', 'vemcomer' ); ?></a></li>
                        </ul>
                    </div>
                <?php else : ?>
                    <a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn btn--ghost"><?php esc_html_e( 'Entrar', 'vemcomer' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/cadastro/' ) ); ?>" class="btn btn--primary" id="btn-cadastro"><?php esc_html_e( 'Cadastrar', 'vemcomer' ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main id="main" class="site-main">

