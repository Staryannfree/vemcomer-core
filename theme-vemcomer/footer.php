    </main><!-- #main -->

    <footer id="colophon" class="site-footer">
        <div class="site-footer__container">
            <div class="site-footer__widgets">
                <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
                    <div class="site-footer__widgets-grid">
                        <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
                            <div class="site-footer__widget-area">
                                <?php dynamic_sidebar( 'footer-1' ); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
                            <div class="site-footer__widget-area">
                                <?php dynamic_sidebar( 'footer-2' ); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
                            <div class="site-footer__widget-area">
                                <?php dynamic_sidebar( 'footer-3' ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="site-footer__bottom">
                <div class="site-footer__menu">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'footer',
                        'container'      => false,
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </div>
                
                <div class="site-footer__copyright">
                    <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Todos os direitos reservados.', 'vemcomer' ); ?></p>
                </div>
            </div>
        </div>
    </footer>
    
    <?php
    // Bottom Navigation Mobile
    ?>
    <nav class="bottom-nav" id="bottom-nav">
        <div class="bottom-nav__items">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="bottom-nav__item">
                <span class="bottom-nav__icon">🏠</span>
                <span class="bottom-nav__label"><?php esc_html_e( 'Início', 'vemcomer' ); ?></span>
            </a>
            <a href="<?php echo esc_url( home_url( '/restaurantes/' ) ); ?>" class="bottom-nav__item">
                <span class="bottom-nav__icon">🔍</span>
                <span class="bottom-nav__label"><?php esc_html_e( 'Buscar', 'vemcomer' ); ?></span>
            </a>
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( home_url( '/meus-favoritos/' ) ); ?>" class="bottom-nav__item">
                    <span class="bottom-nav__icon">❤️</span>
                    <span class="bottom-nav__label"><?php esc_html_e( 'Favoritos', 'vemcomer' ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/meus-pedidos/' ) ); ?>" class="bottom-nav__item">
                    <span class="bottom-nav__icon">📦</span>
                    <span class="bottom-nav__label"><?php esc_html_e( 'Pedidos', 'vemcomer' ); ?></span>
                </a>
            <?php endif; ?>
            <a href="<?php echo esc_url( is_user_logged_in() ? home_url( '/perfil/' ) : wp_login_url() ); ?>" class="bottom-nav__item">
                <span class="bottom-nav__icon">👤</span>
                <span class="bottom-nav__label"><?php esc_html_e( 'Perfil', 'vemcomer' ); ?></span>
            </a>
        </div>
    </nav>
    
    <?php
    // Popup de Boas-Vindas - Solicitar Localização
    // Por enquanto, mostrar sempre na home (remover verificação de cookie)
    if ( is_front_page() ) :
    ?>
    <div class="welcome-popup" id="welcome-popup">
        <div class="welcome-popup__dialog">
            <button class="welcome-popup__close" aria-label="<?php esc_attr_e( 'Fechar', 'vemcomer' ); ?>">&times;</button>
            <div class="welcome-popup__icon">👋</div>
            <h2 class="welcome-popup__title"><?php esc_html_e( 'Bem-vindo ao Pedevem!', 'vemcomer' ); ?></h2>
            <p class="welcome-popup__text">
                <?php esc_html_e( 'Descubra os melhores restaurantes perto de você!', 'vemcomer' ); ?>
            </p>
            <p class="welcome-popup__subtext">
                <?php esc_html_e( 'Clique no botão abaixo para ver restaurantes próximos à sua localização.', 'vemcomer' ); ?>
            </p>
            <div class="welcome-popup__actions">
                <button type="button" class="btn btn--primary btn--large" id="welcome-popup-location-btn">
                    <span class="btn-icon">📍</span>
                    <span><?php esc_html_e( 'Ver restaurantes perto de mim', 'vemcomer' ); ?></span>
                </button>
                <button type="button" class="btn btn--ghost" id="welcome-popup-skip-btn">
                    <?php esc_html_e( 'Pular por enquanto', 'vemcomer' ); ?>
                </button>
            </div>
            <p class="welcome-popup__privacy">
                <small><?php esc_html_e( '🔒 Sua privacidade é importante. Não compartilhamos sua localização com terceiros.', 'vemcomer' ); ?></small>
            </p>
        </div>
    </div>
    <?php
    endif; // is_front_page
    ?>
    
    <?php
    // Popup de Seleção de Cadastro
    if ( ! is_user_logged_in() ) :
        // Buscar URLs de cadastro
        $customer_signup_pages = get_posts([
            'post_type' => 'page',
            's' => 'cadastro cliente',
            'posts_per_page' => 1,
        ]);
        $restaurant_signup_pages = get_posts([
            'post_type' => 'page',
            's' => 'cadastro restaurante',
            'posts_per_page' => 1,
        ]);
        
        $customer_url = ! empty( $customer_signup_pages ) 
            ? get_permalink( $customer_signup_pages[0]->ID ) 
            : home_url( '/cadastro-cliente/' );
        $restaurant_url = ! empty( $restaurant_signup_pages ) 
            ? get_permalink( $restaurant_signup_pages[0]->ID ) 
            : home_url( '/cadastro-restaurante/' );
    ?>
    <div class="signup-popup" id="signup-popup">
        <div class="signup-popup__overlay"></div>
        <div class="signup-popup__dialog">
            <button class="signup-popup__close" aria-label="<?php esc_attr_e( 'Fechar', 'vemcomer' ); ?>">&times;</button>
            <h2 class="signup-popup__title"><?php esc_html_e( 'Você é cliente ou restaurante?', 'vemcomer' ); ?></h2>
            <div class="signup-popup__options">
                <a href="<?php echo esc_url( $customer_url ); ?>" class="signup-popup__option signup-popup__option--customer">
                    <div class="signup-popup__icon">👤</div>
                    <h3 class="signup-popup__option-title"><?php esc_html_e( 'Sou Cliente', 'vemcomer' ); ?></h3>
                    <p class="signup-popup__option-text"><?php esc_html_e( 'Quero pedir comida e descobrir restaurantes', 'vemcomer' ); ?></p>
                </a>
                <a href="<?php echo esc_url( $restaurant_url ); ?>" class="signup-popup__option signup-popup__option--restaurant">
                    <div class="signup-popup__icon">🍽️</div>
                    <h3 class="signup-popup__option-title"><?php esc_html_e( 'Sou Restaurante', 'vemcomer' ); ?></h3>
                    <p class="signup-popup__option-text"><?php esc_html_e( 'Quero vender meus pratos e receber pedidos', 'vemcomer' ); ?></p>
                </a>
            </div>
        </div>
    </div>
    <?php
    endif; // ! is_user_logged_in
    ?>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>

