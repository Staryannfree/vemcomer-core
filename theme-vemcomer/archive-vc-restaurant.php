<?php
/**
 * Template para arquivo de restaurantes
 * 
 * Se o plugin vemcomer-core estiver ativo, usa o template do plugin.
 * Caso contrário, usa este template básico.
 *
 * @package VemComer
 */

get_header();

// Se o plugin tem template próprio, usar ele
$plugin_template = VEMCOMER_CORE_DIR . 'templates/archive-vc-restaurant.php';
if ( file_exists( $plugin_template ) && vemcomer_is_plugin_active() ) {
    include $plugin_template;
    get_footer();
    return;
}

// Template básico se plugin não estiver ativo
?>

<div class="content-area">
    <div class="container">
        <header class="page-header">
            <h1 class="page-title"><?php esc_html_e( 'Restaurantes', 'vemcomer' ); ?></h1>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="restaurants-grid">
                <?php
                while ( have_posts() ) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'restaurant-card' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="restaurant-card__thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="restaurant-card__content">
                            <h2 class="restaurant-card__title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            
                            <div class="restaurant-card__excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        </div>
                    </article>
                    <?php
                endwhile;
                ?>
            </div>

            <?php
            the_posts_pagination();
            ?>
        <?php else : ?>
            <p><?php esc_html_e( 'Nenhum restaurante encontrado.', 'vemcomer' ); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();

