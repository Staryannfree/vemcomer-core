<?php
/**
 * Template Part: Seção de Banners Promocionais
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$args = isset( $args ) ? $args : [];
$quantidade = isset( $args['quantidade'] ) ? absint( $args['quantidade'] ) : 5;
?>

<?php
// Carregar CSS de banners se existir
if ( file_exists( get_template_directory() . '/../assets/css/banners.css' ) ) {
    wp_enqueue_style( 'vemcomer-banners', get_template_directory_uri() . '/../assets/css/banners.css', [], '1.0.0' );
}
?>

<section class="home-banners" style="padding: 40px 0; background: #fff;">
    <div class="container">
        <h2 class="section-title" style="margin-bottom: 30px; font-size: 24px; font-weight: bold; text-align: center; color: #222;">
            <?php echo esc_html__( 'Promoções e destaques', 'vemcomer' ); ?>
        </h2>
        <?php 
        // Tentar usar o shortcode de banners
        if ( shortcode_exists( 'vc_banners' ) ) {
            echo do_shortcode( '[vc_banners limit="' . esc_attr( $quantidade ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            // Fallback: buscar banners manualmente se o shortcode não existir
            $banners_query = new WP_Query([
                'post_type'      => 'vc_banner',
                'posts_per_page' => $quantidade,
                'post_status'    => 'publish',
                'meta_query'     => [
                    [
                        'key'   => '_vc_banner_active',
                        'value' => '1',
                    ],
                ],
                'orderby'        => 'meta_value_num',
                'meta_key'       => '_vc_banner_order',
                'order'          => 'ASC',
            ]);
            
            if ( $banners_query->have_posts() ) :
                ?>
                <div class="vc-banners" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 24px 0;">
                    <?php while ( $banners_query->have_posts() ) : $banners_query->the_post(); ?>
                        <?php
                        $banner_id = get_the_ID();
                        $image_id = get_post_thumbnail_id( $banner_id );
                        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
                        $link = get_post_meta( $banner_id, '_vc_banner_link', true );
                        $title = get_the_title();
                        $size = (string) get_post_meta( $banner_id, '_vc_banner_size', true );
                        if ( empty( $size ) ) {
                            $size = 'medium';
                        }
                        $size_class = 'vc-banner-item--' . esc_attr( $size );
                        ?>
                        <div class="vc-banner-item <?php echo esc_attr( $size_class ); ?>" style="position: relative; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); transition: transform 0.2s ease, box-shadow 0.2s ease; background: #f3f4f6; min-height: 200px;">
                            <?php if ( $link ) : ?>
                                <a href="<?php echo esc_url( $link ); ?>" class="vc-banner-item__link" style="display: block; text-decoration: none; color: inherit;">
                            <?php endif; ?>
                            <?php if ( $image_url ) : ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="vc-banner-item__image" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy" />
                            <?php endif; ?>
                            <?php if ( $title ) : ?>
                                <div class="vc-banner-item__overlay" style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent); padding: 20px; color: #fff;">
                                    <h3 class="vc-banner-item__title" style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #fff; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);"><?php echo esc_html( $title ); ?></h3>
                                </div>
                            <?php endif; ?>
                            <?php if ( $link ) : ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php
                wp_reset_postdata();
            else :
                // Mensagem quando não há banners
                ?>
                <p style="text-align: center; color: #888; padding: 40px 0;">
                    <?php echo esc_html__( 'Nenhuma promoção disponível no momento.', 'vemcomer' ); ?>
                </p>
                <?php
            endif;
        }
        ?>
    </div>
</section>

<style>
/* Estilos adicionais para banners na home - Controle de tamanho */
.home-banners .vc-banners {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
    gap: 20px !important;
    margin: 24px 0 !important;
}

.home-banners .vc-banner-item {
    max-height: 300px !important; /* Limita altura máxima */
    height: 100% !important;
    overflow: hidden !important;
}

.home-banners .vc-banner-item--small {
    max-height: 200px !important;
    grid-column: span 1 !important;
}

.home-banners .vc-banner-item--medium {
    max-height: 250px !important;
    grid-column: span 1 !important;
}

.home-banners .vc-banner-item--large {
    max-height: 300px !important;
    grid-column: span 2 !important;
}

.home-banners .vc-banner-item--full {
    max-height: 350px !important;
    grid-column: 1 / -1 !important;
}

.home-banners .vc-banner-item__image {
    width: 100% !important;
    height: 100% !important;
    max-height: inherit !important;
    object-fit: cover !important;
    display: block !important;
}

.home-banners .vc-banner-item:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
}

@media (max-width: 768px) {
    .home-banners .vc-banners {
        grid-template-columns: 1fr !important;
    }
    
    .home-banners .vc-banner-item--small,
    .home-banners .vc-banner-item--medium,
    .home-banners .vc-banner-item--large,
    .home-banners .vc-banner-item--full {
        grid-column: 1 / -1 !important;
        max-height: 250px !important; /* Altura uniforme no mobile */
    }
}
</style>

