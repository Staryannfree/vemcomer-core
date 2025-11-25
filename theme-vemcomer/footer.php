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
    // Bottom Navigation Mobile - Apenas 4 itens (estilo iFood)
    if ( wp_is_mobile() ) :
        $current_url = home_url( $_SERVER['REQUEST_URI'] );
        $is_home = is_front_page() || is_home();
        $is_search = is_search() || ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) || strpos( $current_url, '/busca' ) !== false || strpos( $current_url, '/restaurantes' ) !== false;
        $is_orders = strpos( $current_url, '/meus-pedidos' ) !== false || strpos( $current_url, '/pedidos' ) !== false;
        $is_profile = strpos( $current_url, '/perfil' ) !== false || strpos( $current_url, '/minha-conta' ) !== false || strpos( $current_url, '/wp-login' ) !== false;
        
        // URLs
        $home_url = home_url( '/' );
        $search_url = home_url( '/busca/' );
        $orders_url = is_user_logged_in() ? home_url( '/meus-pedidos/' ) : wp_login_url();
        $profile_url = is_user_logged_in() ? home_url( '/minha-conta/' ) : wp_login_url();
    ?>
    <nav class="bottom-nav" id="bottom-nav" role="navigation" aria-label="<?php esc_attr_e( 'Navegação principal', 'vemcomer' ); ?>">
        <div class="bottom-nav__items">
            <!-- 1. Início -->
            <a href="<?php echo esc_url( $home_url ); ?>" 
               class="bottom-nav__item <?php echo $is_home ? 'active' : ''; ?>" 
               aria-label="<?php esc_attr_e( 'Início', 'vemcomer' ); ?>"
               <?php echo $is_home ? 'aria-current="page"' : ''; ?>>
                <span class="bottom-nav__icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="bottom-nav__label"><?php esc_html_e( 'Início', 'vemcomer' ); ?></span>
            </a>
            
            <!-- 2. Buscar -->
            <a href="<?php echo esc_url( $search_url ); ?>" 
               class="bottom-nav__item <?php echo $is_search ? 'active' : ''; ?>" 
               aria-label="<?php esc_attr_e( 'Buscar', 'vemcomer' ); ?>"
               <?php echo $is_search ? 'aria-current="page"' : ''; ?>>
                <span class="bottom-nav__icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="bottom-nav__label"><?php esc_html_e( 'Buscar', 'vemcomer' ); ?></span>
            </a>
            
            <!-- 3. Pedidos -->
            <a href="<?php echo esc_url( $orders_url ); ?>" 
               class="bottom-nav__item <?php echo $is_orders ? 'active' : ''; ?>" 
               aria-label="<?php esc_attr_e( 'Pedidos', 'vemcomer' ); ?>"
               <?php echo $is_orders ? 'aria-current="page"' : ''; ?>>
                <span class="bottom-nav__icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="bottom-nav__label"><?php esc_html_e( 'Pedidos', 'vemcomer' ); ?></span>
            </a>
            
            <!-- 4. Perfil -->
            <a href="<?php echo esc_url( $profile_url ); ?>" 
               class="bottom-nav__item <?php echo $is_profile ? 'active' : ''; ?>" 
               aria-label="<?php esc_attr_e( 'Perfil', 'vemcomer' ); ?>"
               <?php echo $is_profile ? 'aria-current="page"' : ''; ?>>
                <span class="bottom-nav__icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="bottom-nav__label"><?php esc_html_e( 'Perfil', 'vemcomer' ); ?></span>
            </a>
        </div>
    </nav>
    <?php endif; ?>
    
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

