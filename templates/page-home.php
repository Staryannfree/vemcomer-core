<?php
/**
 * Template Name: Home Pedevem
 * 
 * Template para a página inicial do marketplace.
 * Usa shortcodes do plugin para montar a Home completa.
 * 
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main class="vc-home">
    
    <?php
    // Seção 1: Hero
    ?>
    <section class="vc-home-hero">
        <div class="vc-home-hero__content">
            <h1 class="vc-home-hero__title"><?php echo esc_html__( 'Peça dos melhores restaurantes da sua cidade', 'vemcomer' ); ?></h1>
            <p class="vc-home-hero__subtitle"><?php echo esc_html__( 'Entrega, retirada e cardápios atualizados em tempo real', 'vemcomer' ); ?></p>
            <div class="vc-home-hero__search">
                <form method="get" action="#vc-restaurants-list" class="vc-home-hero__search-form">
                    <input 
                        type="text" 
                        name="s" 
                        placeholder="<?php echo esc_attr__( 'Buscar restaurantes, pratos...', 'vemcomer' ); ?>" 
                        class="vc-home-hero__search-input"
                        value="<?php echo esc_attr( get_query_var( 's' ) ); ?>"
                    />
                    <button type="submit" class="vc-home-hero__search-btn">
                        <?php echo esc_html__( 'Buscar', 'vemcomer' ); ?>
                    </button>
                </form>
            </div>
            <a href="#vc-restaurants-list" class="vc-home-hero__cta">
                <?php echo esc_html__( 'Explorar restaurantes', 'vemcomer' ); ?>
            </a>
        </div>
    </section>

    <?php
    // Seção 2: Banners
    ?>
    <section class="vc-home-banners">
        <div class="vc-home-container">
            <h2 class="vc-home-section__title"><?php echo esc_html__( 'Promoções e destaques', 'vemcomer' ); ?></h2>
            <?php echo do_shortcode( '[vc_banners]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </section>

    <?php
    // Seção 3: Listagem de Restaurantes + Filtros
    ?>
    <section class="vc-home-restaurants" id="vc-restaurants-list">
        <div class="vc-home-container">
            <h2 class="vc-home-section__title"><?php echo esc_html__( 'Restaurantes', 'vemcomer' ); ?></h2>
            <?php echo do_shortcode( '[vemcomer_restaurants]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </section>

    <?php
    // Seção 4: Mapa
    ?>
    <section class="vc-home-map">
        <div class="vc-home-container">
            <h2 class="vc-home-section__title"><?php echo esc_html__( 'Veja restaurantes no mapa', 'vemcomer' ); ?></h2>
            <p class="vc-home-section__desc"><?php echo esc_html__( 'Encontre restaurantes próximos a você usando o mapa interativo.', 'vemcomer' ); ?></p>
            <?php echo do_shortcode( '[vc_restaurants_map]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </section>

    <?php
    // Seção 5: Para você (Favoritos + Histórico) - apenas para usuários logados
    if ( is_user_logged_in() ) :
    ?>
    <section class="vc-home-for-you">
        <div class="vc-home-container">
            <h2 class="vc-home-section__title"><?php echo esc_html__( 'Para você', 'vemcomer' ); ?></h2>
            
            <div class="vc-home-for-you__tabs">
                <button class="vc-home-tab vc-home-tab--active" data-tab="favorites">
                    <?php echo esc_html__( 'Meus Favoritos', 'vemcomer' ); ?>
                </button>
                <button class="vc-home-tab" data-tab="orders">
                    <?php echo esc_html__( 'Meus Pedidos', 'vemcomer' ); ?>
                </button>
            </div>

            <div class="vc-home-tab-content vc-home-tab-content--active" id="vc-tab-favorites">
                <?php echo do_shortcode( '[vc_favorites]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <div class="vc-home-tab-content" id="vc-tab-orders">
                <?php echo do_shortcode( '[vc_orders_history per_page="5"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    // Seção 6: CTA para donos de restaurante
    ?>
    <section class="vc-home-cta">
        <div class="vc-home-container">
            <div class="vc-home-cta__content">
                <h2 class="vc-home-cta__title"><?php echo esc_html__( 'Tem um restaurante? Venda pelo Pedevem', 'vemcomer' ); ?></h2>
                <p class="vc-home-cta__text">
                    <?php echo esc_html__( 'Cadastre seu restaurante e comece a receber pedidos hoje mesmo. Sem comissões por venda, apenas uma mensalidade fixa.', 'vemcomer' ); ?>
                </p>
                <?php
                // Buscar página de cadastro de restaurante
                $signup_page = get_posts([
                    'post_type' => 'page',
                    'meta_query' => [
                        [
                            'key' => '_wp_page_template',
                            'value' => 'page-restaurant-signup.php',
                        ],
                    ],
                    'posts_per_page' => 1,
                ]);
                
                // Ou buscar página que contenha o shortcode
                if ( empty( $signup_page ) ) {
                    $signup_pages = get_posts([
                        'post_type' => 'page',
                        's' => 'cadastro restaurante',
                        'posts_per_page' => 1,
                    ]);
                    if ( ! empty( $signup_pages ) ) {
                        $signup_page = $signup_pages;
                    }
                }

                $signup_url = ! empty( $signup_page ) 
                    ? get_permalink( $signup_page[0]->ID ) 
                    : home_url( '/cadastre-seu-restaurante/' );
                ?>
                <a href="<?php echo esc_url( $signup_url ); ?>" class="vc-home-cta__btn">
                    <?php echo esc_html__( 'Cadastrar meu restaurante', 'vemcomer' ); ?>
                </a>
            </div>
        </div>
    </section>

    <?php
    // Seção 7: Rodapé (opcional, se o tema não tiver rodapé próprio)
    ?>
    <footer class="vc-home-footer">
        <div class="vc-home-container">
            <div class="vc-home-footer__content">
                <div class="vc-home-footer__links">
                    <a href="<?php echo esc_url( home_url( '/como-funciona/' ) ); ?>">
                        <?php echo esc_html__( 'Como funciona', 'vemcomer' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $signup_url ); ?>">
                        <?php echo esc_html__( 'Cadastre seu restaurante', 'vemcomer' ); ?>
                    </a>
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( home_url( '/meu-restaurante/' ) ); ?>">
                            <?php echo esc_html__( 'Meu restaurante', 'vemcomer' ); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( home_url( '/politica-de-privacidade/' ) ); ?>">
                        <?php echo esc_html__( 'Política de Privacidade', 'vemcomer' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/termos-de-uso/' ) ); ?>">
                        <?php echo esc_html__( 'Termos de Uso', 'vemcomer' ); ?>
                    </a>
                </div>
                <p class="vc-home-footer__copyright">
                    &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php echo esc_html__( 'Todos os direitos reservados.', 'vemcomer' ); ?>
                </p>
            </div>
        </div>
    </footer>

</main>

<?php
get_footer();

