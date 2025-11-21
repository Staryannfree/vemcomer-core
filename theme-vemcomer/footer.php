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
    // Popup de Primeira Visita
    if ( ! is_user_logged_in() ) :
        $welcome_seen = isset( $_COOKIE['vc_welcome_popup_seen'] ) && $_COOKIE['vc_welcome_popup_seen'] === '1';
        if ( ! $welcome_seen ) :
    ?>
    <div class="welcome-popup" id="welcome-popup">
        <div class="welcome-popup__dialog">
            <button class="welcome-popup__close" aria-label="<?php esc_attr_e( 'Fechar', 'vemcomer' ); ?>">&times;</button>
            <h2 class="welcome-popup__title"><?php esc_html_e( 'Bem-vindo ao VemComer!', 'vemcomer' ); ?></h2>
            <p class="welcome-popup__text">
                <?php esc_html_e( 'Ganhe 10% OFF no seu primeiro pedido! Cadastre seu e-mail para receber o código de desconto.', 'vemcomer' ); ?>
            </p>
            <form class="welcome-popup__form" id="welcome-popup-form">
                <input 
                    type="email" 
                    name="email" 
                    placeholder="<?php esc_attr_e( 'Seu melhor e-mail', 'vemcomer' ); ?>" 
                    class="welcome-popup__input"
                    required
                />
                <button type="submit" class="btn btn--primary btn--large">
                    <?php esc_html_e( 'Receber desconto', 'vemcomer' ); ?>
                </button>
            </form>
        </div>
    </div>
    <?php
        endif;
    endif;
    ?>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>

