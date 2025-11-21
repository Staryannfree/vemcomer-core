<?php
/**
 * Template para página individual de restaurante
 * 
 * Se o plugin vemcomer-core estiver ativo, usa o template do plugin.
 * Caso contrário, usa este template básico.
 *
 * @package VemComer
 */

get_header();

// Se o plugin tem template próprio, usar ele
$plugin_template = VEMCOMER_CORE_DIR . 'templates/single-vc-restaurant.php';
if ( file_exists( $plugin_template ) && vemcomer_is_plugin_active() ) {
    include $plugin_template;
    get_footer();
    return;
}

// Template básico se plugin não estiver ativo
?>

<div class="content-area">
    <div class="container">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="entry-thumbnail">
                            <?php the_post_thumbnail( 'large' ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        endwhile;
        ?>
    </div>
</div>

<?php
get_footer();

