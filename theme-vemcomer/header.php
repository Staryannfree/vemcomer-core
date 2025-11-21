<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Pular para o conteúdo', 'vemcomer' ); ?></a>

    <?php
    // Barra de Promoção Fixa
    $promo_bar_dismissed = isset( $_COOKIE['vc_promo_bar_dismissed'] ) && $_COOKIE['vc_promo_bar_dismissed'] === '1';
    if ( ! $promo_bar_dismissed ) :
    ?>
    <div class="promo-bar" id="promo-bar">
        <div class="container">
            <p class="promo-bar__text">
                🎉 <?php esc_html_e( 'Frete grátis acima de R$ 50 em pedidos selecionados!', 'vemcomer' ); ?>
            </p>
            <button class="promo-bar__close" aria-label="<?php esc_attr_e( 'Fechar', 'vemcomer' ); ?>">&times;</button>
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
                ?>
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
                    <a href="<?php echo esc_url( home_url( '/cadastro/' ) ); ?>" class="btn btn--primary"><?php esc_html_e( 'Cadastrar', 'vemcomer' ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main id="main" class="site-main">

