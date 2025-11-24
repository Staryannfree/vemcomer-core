<?php
/**
 * Template Part: CTA para Donos de Restaurantes
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Buscar página de cadastro
$signup_page = get_posts([
    'post_type' => 'page',
    's' => 'cadastro restaurante',
    'posts_per_page' => 1,
]);
$signup_url = ! empty( $signup_page ) 
    ? get_permalink( $signup_page[0]->ID ) 
    : home_url( '/cadastre-seu-restaurante/' );
?>

<section class="home-cta">
    <div class="container">
        <div class="home-cta__content">
            <h2 class="home-cta__title"><?php echo esc_html__( 'Tem um restaurante? Venda pelo Pedevem', 'vemcomer' ); ?></h2>
            <p class="home-cta__text">
                <?php echo esc_html__( 'Cadastre seu restaurante e comece a receber pedidos hoje mesmo. Sem comissões por venda, apenas uma mensalidade fixa.', 'vemcomer' ); ?>
            </p>
            <a href="<?php echo esc_url( $signup_url ); ?>" class="btn btn--primary btn--large" id="btn-cadastro-home">
                <?php echo esc_html__( 'Cadastrar meu restaurante', 'vemcomer' ); ?>
            </a>
        </div>
    </div>
</section>

