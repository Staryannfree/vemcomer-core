<?php
/**
 * Template da página inicial - Versão 2 (Design Elaborado)
 * 
 * Para usar este template, defina a constante VC_HOME_TEMPLATE_V2 como true
 * no wp-config.php ou use o filtro 'vemcomer_home_template_version'
 *
 * @package VemComer
 */

get_header();
?>

<style>
    /* Estilos específicos para a versão 2 da home */
    .home-v2 { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f8f8; }
    .home-v2 header { background: #222; color: #fff; padding: 20px 0; }
    .home-v2 .container { width: 93%; max-width: 1350px; margin: auto; }
    .home-v2 nav { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .home-v2 .logo { font-size: 2.5rem; font-weight: bold; }
    .home-v2 .menu { list-style: none; display: flex; gap: 28px; flex-wrap: wrap; }
    .home-v2 .menu li { display: inline; }
    .home-v2 .menu a { color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s; }
    .home-v2 .menu a:hover { color: #F4972E; }
    .home-v2 .account { display: flex; gap: 10px; }
    .home-v2 .account-btn { background: #F4972E; color: #fff; padding: 10px 26px; border-radius: 26px; text-decoration: none; font-weight: 500; border: none; transition: .2s; cursor: pointer; }
    .home-v2 .account-btn:hover { background: #c85d1b; }
    .home-v2 .hero { background: linear-gradient(0deg, rgba(38,38,38,0.55) 0%, rgba(38,38,38,0.27) 100%), url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat; padding: 105px 0 125px 0; color: #fff; text-align: center; }
    .home-v2 .hero h1 { font-size: 3.6rem; margin-bottom: 21px; }
    .home-v2 .hero p { font-size: 1.7rem; margin-bottom: 38px; }
    .home-v2 .search-bar { margin: auto; width: 54%; display: flex; background: #fff; border-radius: 54px; overflow: hidden; box-shadow: 0 4px 16px #0003; }
    .home-v2 .search-bar input { flex: 1; border: none; padding: 21px 28px; font-size: 1.24rem; }
    .home-v2 .search-bar button { background: #F4972E; color: #fff; border: none; padding: 0 36px; font-size: 1.29rem; cursor: pointer; }
    .home-v2 .search-bar button:hover { background: #c85d1b; }
    .home-v2 .quick-filters { display: flex; gap: 20px; justify-content: center; margin: 31px 0; flex-wrap: wrap; }
    .home-v2 .quick-filters button { background: #fff; color: #F4972E; border: 1px solid #F4972E; border-radius: 22px; padding: 10px 21px; cursor: pointer; transition: all 0.3s; }
    .home-v2 .quick-filters button:hover { background: #F4972E; color: #fff; }
    .home-v2 .section-title { font-size: 2.2rem; color: #222; margin: 39px 0 28px 0; text-align: center; letter-spacing: .8px; }
    .home-v2 section { margin: 38px 0; }
    .home-v2 .main-grid { display: grid; grid-template-columns: 60% 36%; gap: 4%; }
    .home-v2 .categories, .home-v2 .featured, .home-v2 .reviews, .home-v2 .faq, .home-v2 .partners, .home-v2 .blog, .home-v2 .app-promo, .home-v2 .newsletter, .home-v2 .destaques-do-dia, .home-v2 .delivery-stats, .home-v2 .user-actions, .home-v2 .admin-area, .home-v2 .map-area, .home-v2 .calendar, .home-v2 .benefits, .home-v2 .premium, .home-v2 .ranking, .home-v2 .plan, .home-v2 .responsive-feature { display: flex; flex-wrap: wrap; gap: 34px; justify-content: center; }
    .home-v2 .category-card, .home-v2 .restaurant-card, .home-v2 .review-card, .home-v2 .faq-card, .home-v2 .partner-card, .home-v2 .blog-card, .home-v2 .destaque-card, .home-v2 .stat-card, .home-v2 .user-card, .home-v2 .admin-card, .home-v2 .map-card, .home-v2 .premium-card, .home-v2 .ranking-card, .home-v2 .plan-card { background: #fff; border-radius: 13px; box-shadow: 0 2px 8px #0001; padding: 26px; width: 235px; text-align: center; }
    .home-v2 .category-card i { font-size: 2.6rem; margin-bottom: 12px; color: #F4972E; }
    .home-v2 .restaurant-img, .home-v2 .partner-img, .home-v2 .blog-img, .home-v2 .destaque-img, .home-v2 .map-img, .home-v2 .premium-img, .home-v2 .ranking-img { width: 100%; height: 138px; object-fit: cover; border-radius: 10px; }
    .home-v2 .restaurant-card h3 { margin-top: 11px; margin-bottom: 4px; }
    .home-v2 .restaurant-card p { font-size: 1.03rem; color: #555; }
    .home-v2 .restaurant-card .tags span, .home-v2 .destaque-card .tags span, .home-v2 .premium-card .tags span { background: #f6f6f6; color: #333; border-radius: 20px; padding: 4px 13px; font-size: .84rem; margin: 2px; display: inline-block; }
    .home-v2 .restaurant-card .rating, .home-v2 .destaque-card .rating { color: #F4972E; font-weight: 600; margin-top: 10px; }
    .home-v2 .review-card { width: 335px; }
    .home-v2 .avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; }
    .home-v2 .faq-card { background: #f8f8f8; width: 450px; font-size: 1.06rem; }
    .home-v2 .main-grid aside { display: flex; flex-direction: column; gap: 36px; }
    .home-v2 .partners { gap: 20px; }
    .home-v2 .partner-card { width: 190px; padding: 20px; }
    .home-v2 .premium { gap: 18px; }
    .home-v2 .premium-card { width: 260px; }
    .home-v2 .premium-card .tags { margin: 4px 0; }
    .home-v2 .premium-card .rating { color: #FFD700; }
    .home-v2 .plan { gap: 16px; }
    .home-v2 .plan-card { background: #F4972E; color: #fff; width: 235px; }
    .home-v2 .plan-card h4 { margin-bottom: 7px; }
    .home-v2 .ranking { gap: 14px; }
    .home-v2 .ranking-card { width: 200px; }
    .home-v2 .ranking-card .rating { color: #2e8540; }
    .home-v2 .responsive-feature { background: #ffe6d1; width: 97%; text-align: center; padding: 24px; font-size: 1.13rem; border-radius: 11px; }
    .home-v2 .benefits-list { display: flex; gap: 39px; justify-content: center; }
    .home-v2 .benefit-item { background: #F4972E; color: #fff; font-size: 1.3rem; border-radius: 65px; padding: 34px 24px; width: 192px; }
    .home-v2 .app-promo { margin-top: 16px; align-items: center; }
    .home-v2 .app-promo img { width: 155px; height: 155px; border-radius: 19px; }
    .home-v2 .newsletter { background: #222; color: #fff; border-radius: 13px; width: 96%; margin: auto; padding: 41px 0; text-align: center; }
    .home-v2 .newsletter input, .home-v2 .newsletter button { border: none; border-radius: 23px; padding: 14px 27px; margin: 7px; }
    .home-v2 .newsletter input { width: 330px; }
    .home-v2 .newsletter button { background: #F4972E; color: #fff; font-weight: 700; cursor: pointer; }
    .home-v2 .newsletter button:hover { background: #c85d1b; }
    .home-v2 .calendar { background: #fff; border-radius: 13px; width: 99%; margin: auto; box-shadow: 0 2px 8px #0001; padding: 26px; text-align: center; }
    .home-v2 .calendar-title { font-size: 1.38rem; color: #222; margin-bottom: 5px; }
    .home-v2 .calendar-list { margin-top: 21px; }
    .home-v2 .calendar-list li { list-style: none; padding: 11px 0; }
    .home-v2 .footer-social a { margin: 0 12px; color: #F4972E; text-decoration: none; font-size: 1.5rem; }
    .home-v2 footer { background: #222; color: #fff; text-align: center; padding: 39px 0 23px 0; font-size: 1.12rem; margin-top: 53px; }
    @media (max-width: 920px) { .home-v2 .container { width: 98%; } .home-v2 .search-bar { width: 100%; } }
    @media (max-width: 730px) { .home-v2 .container { width: 100%; } .home-v2 .main-grid { grid-template-columns: 100%; } .home-v2 .categories, .home-v2 .featured, .home-v2 .reviews, .home-v2 .faq, .home-v2 .partners, .home-v2 .blog, .home-v2 .app-promo, .home-v2 .newsletter, .home-v2 .destaques-do-dia, .home-v2 .delivery-stats, .home-v2 .user-actions, .home-v2 .admin-area, .home-v2 .map-area, .home-v2 .calendar, .home-v2 .benefits-list, .home-v2 .premium, .home-v2 .plan, .home-v2 .ranking, .home-v2 .responsive-feature { flex-direction: column; gap: 18px; } .home-v2 .faq-card, .home-v2 .blog-card, .home-v2 .calendar, .home-v2 .benefit-item { width: 100%; } }
</style>

<div class="home-v2">
    <section class="hero">
        <div class="container">
            <h1 id="hero-title"><?php echo esc_html__( 'Descubra, compare e peça dos melhores restaurantes, bares e deliverys!', 'vemcomer' ); ?></h1>
            <p id="hero-subtitle"><?php echo esc_html__( 'Delivery, reservas, cupons, ranking, premium, eventos, mapa, estatísticas e muito mais em um só lugar.', 'vemcomer' ); ?></p>
            <form class="search-bar" method="get" action="#restaurants-list" id="hero-search-form">
                <input type="text" name="s" id="hero-search-input" placeholder="<?php echo esc_attr__( 'Busque restaurante, prato, bairro, ranking, evento, premium...', 'vemcomer' ); ?>" value="<?php echo esc_attr( get_query_var( 's' ) ); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            <div class="quick-filters">
                <button data-filter="open_now"><i class="fas fa-clock"></i> <?php esc_html_e( 'Aberto agora', 'vemcomer' ); ?></button>
                <button data-filter="top_rated"><i class="fas fa-star"></i> <?php esc_html_e( 'Mais avaliados', 'vemcomer' ); ?></button>
                <button data-filter="free_delivery"><i class="fas fa-shipping-fast"></i> <?php esc_html_e( 'Frete grátis', 'vemcomer' ); ?></button>
                <button data-filter="promo"><i class="fas fa-percent"></i> <?php esc_html_e( 'Promoção', 'vemcomer' ); ?></button>
                <button data-filter="kids"><i class="fas fa-child"></i> <?php esc_html_e( 'Kids', 'vemcomer' ); ?></button>
                <button data-filter="events"><i class="fas fa-birthday-cake"></i> <?php esc_html_e( 'Eventos', 'vemcomer' ); ?></button>
                <button data-filter="healthy"><i class="fas fa-leaf"></i> <?php esc_html_e( 'Saudável', 'vemcomer' ); ?></button>
                <button data-filter="pet_friendly"><i class="fas fa-paw"></i> <?php esc_html_e( 'Pet Friendly', 'vemcomer' ); ?></button>
                <button data-filter="bars"><i class="fas fa-glass-cheers"></i> <?php esc_html_e( 'Bares', 'vemcomer' ); ?></button>
                <button data-filter="premium"><i class="fas fa-crown"></i> <?php esc_html_e( 'Premium', 'vemcomer' ); ?></button>
                <button data-filter="ranking"><i class="fas fa-chart-line"></i> <?php esc_html_e( 'Ranking', 'vemcomer' ); ?></button>
            </div>
            <div class="quick-filters" style="margin-top: 15px;">
                <button class="btn-geolocation" id="vc-use-location" type="button" style="background: rgba(255,255,255,0.2); border: 1px solid #fff; color: #fff;">
                    <span>📍</span> <?php esc_html_e( 'Usar minha localização', 'vemcomer' ); ?>
                </button>
            </div>
        </div>
    </section>

    <div class="container main-grid">
        <div>
            <?php
            // Seção: Categorias Populares
            if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
                $home_improvements = get_template_directory() . '/inc/home-improvements.php';
                if ( file_exists( $home_improvements ) && ! function_exists( 'vemcomer_home_popular_categories' ) ) {
                    require_once $home_improvements;
                }
                if ( function_exists( 'vemcomer_home_popular_categories' ) ) {
                    echo vemcomer_home_popular_categories(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                } else {
                    ?>
                    <section>
                        <div class="section-title"><?php echo esc_html__( 'Categorias Populares', 'vemcomer' ); ?></div>
                        <div class="categories">
                            <div class="category-card"><i class="fas fa-pizza-slice"></i><br><?php esc_html_e( 'Pizza', 'vemcomer' ); ?></div>
                            <div class="category-card"><i class="fas fa-hamburger"></i><br><?php esc_html_e( 'Lanches', 'vemcomer' ); ?></div>
                            <div class="category-card"><i class="fas fa-ice-cream"></i><br><?php esc_html_e( 'Sobremesas', 'vemcomer' ); ?></div>
                            <div class="category-card"><i class="fas fa-glass-cheers"></i><br><?php esc_html_e( 'Bares', 'vemcomer' ); ?></div>
                            <div class="category-card"><i class="fas fa-fish"></i><br><?php esc_html_e( 'Frutos do Mar', 'vemcomer' ); ?></div>
                            <div class="category-card"><i class="fas fa-utensil-spoon"></i><br><?php esc_html_e( 'Brasileira', 'vemcomer' ); ?></div>
                            <div class="category-card"><i class="fas fa-seedling"></i><br><?php esc_html_e( 'Vegetariana', 'vemcomer' ); ?></div>
                            <div class="category-card"><i class="fas fa-drumstick-bite"></i><br><?php esc_html_e( 'Churrasco', 'vemcomer' ); ?></div>
                        </div>
                    </section>
                    <?php
                }
            endif;
            ?>

            <?php
            // Seção: Destaques do Dia
            if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
                ?>
                <section>
                    <div class="section-title"><?php echo esc_html__( 'Destaques do Dia', 'vemcomer' ); ?></div>
                    <div class="destaques-do-dia">
                        <?php
                        // Buscar restaurantes em destaque
                        $featured_args = [
                            'post_type' => 'vc-restaurant',
                            'posts_per_page' => 2,
                            'meta_query' => [
                                [
                                    'key' => '_vc_featured',
                                    'value' => '1',
                                    'compare' => '=',
                                ],
                            ],
                        ];
                        $featured_query = new WP_Query( $featured_args );
                        if ( $featured_query->have_posts() ) :
                            while ( $featured_query->have_posts() ) :
                                $featured_query->the_post();
                                $restaurant_id = get_the_ID();
                                $image = get_the_post_thumbnail_url( $restaurant_id, 'vemcomer-card' ) ?: 'https://images.unsplash.com/photo-1519869472639-fb3019fd6191?auto=format&fit=crop&w=400&q=80';
                                $rating = get_post_meta( $restaurant_id, '_vc_rating', true ) ?: '4.5';
                                $address = get_post_meta( $restaurant_id, '_vc_address', true );
                                $restaurant_url = get_permalink( $restaurant_id );
                                ?>
                                <div class="destaque-card">
                                    <img src="<?php echo esc_url( $image ); ?>" class="destaque-img" alt="<?php echo esc_attr( get_the_title() ); ?>">
                                    <h3><?php the_title(); ?></h3>
                                    <div class="tags">
                                        <?php
                                        $categories = get_the_terms( $restaurant_id, 'vc-restaurant-category' );
                                        if ( $categories && ! is_wp_error( $categories ) ) {
                                            foreach ( array_slice( $categories, 0, 2 ) as $cat ) {
                                                echo '<span>' . esc_html( $cat->name ) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="rating"><i class="fas fa-star"></i> <?php echo esc_html( $rating ); ?></div>
                                    <p><?php echo esc_html( get_post_meta( $restaurant_id, '_vc_description', true ) ?: 'Restaurante em destaque!' ); ?></p>
                                    <a href="<?php echo esc_url( $restaurant_url ); ?>" class="account-btn"><?php esc_html_e( 'Ver Cardápio', 'vemcomer' ); ?></a>
                                </div>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                        else :
                            ?>
                            <div class="destaque-card">
                                <img src="https://images.unsplash.com/photo-1519869472639-fb3019fd6191?auto=format&fit=crop&w=400&q=80" class="destaque-img">
                                <h3><?php esc_html_e( 'Restaurante Exemplo', 'vemcomer' ); ?></h3>
                                <div class="tags"><span><?php esc_html_e( 'Italiana', 'vemcomer' ); ?></span><span><?php esc_html_e( 'Almoço', 'vemcomer' ); ?></span></div>
                                <div class="rating"><i class="fas fa-star"></i> 4.9</div>
                                <p><?php esc_html_e( 'Desconto de 15% até 17h!', 'vemcomer' ); ?></p>
                                <a href="#" class="account-btn"><?php esc_html_e( 'Ver Cardápio', 'vemcomer' ); ?></a>
                            </div>
                            <?php
                        endif;
                        ?>
                    </div>
                </section>
                <?php
            endif;
            ?>

            <?php
            // Seção: Restaurantes em Destaque
            if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
                ?>
                <section>
                    <div class="section-title"><?php echo esc_html__( 'Restaurantes em Destaque', 'vemcomer' ); ?></div>
                    <div class="featured">
                        <?php
                        $restaurants_args = [
                            'post_type' => 'vc-restaurant',
                            'posts_per_page' => 3,
                            'orderby' => 'meta_value_num',
                            'meta_key' => '_vc_rating',
                            'order' => 'DESC',
                        ];
                        $restaurants_query = new WP_Query( $restaurants_args );
                        if ( $restaurants_query->have_posts() ) :
                            while ( $restaurants_query->have_posts() ) :
                                $restaurants_query->the_post();
                                $restaurant_id = get_the_ID();
                                $image = get_the_post_thumbnail_url( $restaurant_id, 'vemcomer-card' ) ?: 'https://images.unsplash.com/photo-1519869472639-fb3019fd6191?auto=format&fit=crop&w=400&q=80';
                                $rating = get_post_meta( $restaurant_id, '_vc_rating', true ) ?: '4.5';
                                $restaurant_url = get_permalink( $restaurant_id );
                                ?>
                                <div class="restaurant-card">
                                    <img src="<?php echo esc_url( $image ); ?>" class="restaurant-img" alt="<?php echo esc_attr( get_the_title() ); ?>">
                                    <h3><?php the_title(); ?></h3>
                                    <p><?php echo esc_html( get_post_meta( $restaurant_id, '_vc_description', true ) ?: 'Restaurante de qualidade!' ); ?></p>
                                    <div class="tags">
                                        <?php
                                        $categories = get_the_terms( $restaurant_id, 'vc-restaurant-category' );
                                        if ( $categories && ! is_wp_error( $categories ) ) {
                                            foreach ( array_slice( $categories, 0, 3 ) as $cat ) {
                                                echo '<span>' . esc_html( $cat->name ) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="rating"><i class="fas fa-star"></i> <?php echo esc_html( $rating ); ?></div>
                                    <a href="<?php echo esc_url( $restaurant_url ); ?>" class="account-btn" style="margin-top: 10px; display: inline-block;"><?php esc_html_e( 'Ver Cardápio', 'vemcomer' ); ?></a>
                                </div>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>
                </section>
                <?php
            endif;
            ?>

            <?php
            // Seção: Listagem completa de restaurantes
            if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
                ?>
                <section id="restaurants-list">
                    <div class="section-title"><?php echo esc_html__( 'Todos os Restaurantes', 'vemcomer' ); ?></div>
                    <div id="restaurants-content">
                        <?php echo do_shortcode( '[vemcomer_restaurants]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                </section>
                <?php
            endif;
            ?>

            <?php
            // Seção: Mapa
            if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
                ?>
                <section>
                    <div class="section-title"><?php echo esc_html__( 'Mapa de Restaurantes e Entregas', 'vemcomer' ); ?></div>
                    <div class="map-area">
                        <div class="map-card">
                            <?php echo do_shortcode( '[vc_restaurants_map]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <p style="margin-top: 15px;"><i class="fas fa-map-marker-alt"></i> <?php esc_html_e( 'Veja restaurantes por região, delivery ou retirada', 'vemcomer' ); ?></p>
                        </div>
                    </div>
                </section>
                <?php
            endif;
            ?>
        </div>

        <aside>
            <?php
            // Sidebar: Estatísticas
            if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
                ?>
                <section>
                    <div class="section-title"><?php echo esc_html__( 'Estatísticas de Entregas', 'vemcomer' ); ?></div>
                    <div class="delivery-stats">
                        <div class="stat-card"><i class="fas fa-truck"></i> <span><?php esc_html_e( 'Entregas hoje', 'vemcomer' ); ?></span></div>
                        <div class="stat-card"><i class="fas fa-user-plus"></i> <span><?php esc_html_e( 'Novos clientes', 'vemcomer' ); ?></span></div>
                        <div class="stat-card"><i class="fas fa-coins"></i> <span><?php esc_html_e( 'Em pedidos', 'vemcomer' ); ?></span></div>
                        <div class="stat-card"><i class="fas fa-utensils"></i> <span><?php esc_html_e( 'Restaurantes ativos', 'vemcomer' ); ?></span></div>
                    </div>
                </section>
                <?php
            endif;
            ?>

            <?php
            // Sidebar: Ações do Usuário
            ?>
            <section>
                <div class="section-title"><?php echo esc_html__( 'Ações do Usuário', 'vemcomer' ); ?></div>
                <div class="user-actions">
                    <div class="user-card">
                        <h4><?php esc_html_e( 'Crie seu perfil', 'vemcomer' ); ?></h4>
                        <p><?php esc_html_e( 'Salve favoritos, acompanhe pedidos, ganhe recompensas!', 'vemcomer' ); ?></p>
                        <?php if ( is_user_logged_in() ) : ?>
                            <a href="<?php echo esc_url( home_url( '/minha-conta/' ) ); ?>" class="account-btn"><?php esc_html_e( 'Minha Conta', 'vemcomer' ); ?></a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( wp_login_url() ); ?>" class="account-btn"><?php esc_html_e( 'Login', 'vemcomer' ); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="user-card">
                        <h4><?php esc_html_e( 'Seja um Restaurante', 'vemcomer' ); ?></h4>
                        <p><?php esc_html_e( 'Cadastre seu estabelecimento agora.', 'vemcomer' ); ?></p>
                        <?php
                        $signup_page = get_posts([
                            'post_type' => 'page',
                            's' => 'cadastro restaurante',
                            'posts_per_page' => 1,
                        ]);
                        $signup_url = ! empty( $signup_page ) 
                            ? get_permalink( $signup_page[0]->ID ) 
                            : home_url( '/cadastre-seu-restaurante/' );
                        ?>
                        <a href="<?php echo esc_url( $signup_url ); ?>" class="account-btn"><?php esc_html_e( 'Sou Restaurante', 'vemcomer' ); ?></a>
                    </div>
                </div>
            </section>

            <?php
            // Sidebar: Newsletter
            ?>
            <section>
                <div class="section-title"><?php echo esc_html__( 'Newsletter', 'vemcomer' ); ?></div>
                <div class="newsletter">
                    <h3><?php esc_html_e( 'Receba novidades e ofertas!', 'vemcomer' ); ?></h3>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="vemcomer_subscribe_newsletter">
                        <input type="email" name="email" placeholder="<?php esc_attr_e( 'Seu e-mail...', 'vemcomer' ); ?>" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i> <?php esc_html_e( 'Receber', 'vemcomer' ); ?></button>
                    </form>
                </div>
            </section>

            <?php
            // Sidebar: FAQ
            ?>
            <section>
                <div class="section-title"><?php echo esc_html__( 'FAQ', 'vemcomer' ); ?></div>
                <div class="faq">
                    <div class="faq-card">
                        <b><?php esc_html_e( 'Como pedir?', 'vemcomer' ); ?></b><br>
                        <?php esc_html_e( 'Escolha o restaurante e finalize seu pedido.', 'vemcomer' ); ?>
                    </div>
                    <div class="faq-card">
                        <b><?php esc_html_e( 'Métodos de pagamento?', 'vemcomer' ); ?></b><br>
                        <?php esc_html_e( 'Cartão, Pix, carteiras digitais, cupons.', 'vemcomer' ); ?>
                    </div>
                    <div class="faq-card">
                        <b><?php esc_html_e( 'Reservas?', 'vemcomer' ); ?></b><br>
                        <?php esc_html_e( 'Restaurantes que aceitam reserva são sinalizados.', 'vemcomer' ); ?>
                    </div>
                    <div class="faq-card">
                        <b><?php esc_html_e( 'Pet friendly?', 'vemcomer' ); ?></b><br>
                        <?php esc_html_e( 'Utilize o filtro "Pet Friendly".', 'vemcomer' ); ?>
                    </div>
                </div>
            </section>
        </aside>
    </div>

    <?php
    // Seção: App Promo
    ?>
    <section>
        <div class="container app-promo" style="gap:30px; display: flex; align-items: center;">
            <img src="https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=155&q=80" alt="App">
            <div>
                <div class="section-title" style="text-align:left;"><?php echo esc_html__( 'Baixe o App!', 'vemcomer' ); ?></div>
                <p><?php esc_html_e( 'Pedidos rápidos e experiência personalizada! Notificações e cupons exclusivos.', 'vemcomer' ); ?></p>
                <a href="#" class="account-btn"><i class="fab fa-android"></i> Google Play</a>
                <a href="#" class="account-btn"><i class="fab fa-apple"></i> App Store</a>
            </div>
        </div>
    </section>
</div>

<?php
// Carregar Font Awesome se não estiver carregado
if ( ! wp_style_is( 'font-awesome', 'enqueued' ) ) {
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2' );
}

get_footer();
?>

