<?php
/**
 * [vc_reviews]
 * Atributos:
 *  - restaurant_id (obrigatório)
 *  - per_page (opcional, default 10)
 *  - show_form (opcional, default true) - Mostrar formulário para criar review
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_reviews', function( $atts = [] ) {
    vc_sc_mark_used();

    // Enfileirar assets
    wp_enqueue_style( 'vemcomer-front' );
    wp_enqueue_script( 'vemcomer-front' );
    wp_enqueue_style( 'vemcomer-reviews' );
    wp_enqueue_script( 'vemcomer-reviews' );

    $a = shortcode_atts([
        'restaurant_id' => '',
        'per_page'      => '10',
        'show_form'     => 'true',
    ], $atts, 'vc_reviews' );

    $rid = $a['restaurant_id'] ? (int) $a['restaurant_id'] : ( get_post_type() === 'vc_restaurant' ? get_the_ID() : 0 );
    if ( ! $rid ) {
        return '<p class="vc-empty">' . esc_html__( 'Defina um restaurante.', 'vemcomer' ) . '</p>';
    }

    $per_page = max( 1, min( 50, (int) $a['per_page'] ) );
    $show_form = filter_var( $a['show_form'], FILTER_VALIDATE_BOOLEAN );

    // Obter rating agregado
    $rating = [];
    if ( class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
        $rating = \VC\Utils\Rating_Helper::get_rating( $rid );
    }

    ob_start();
    ?>
    <div class="vc-reviews" data-restaurant-id="<?php echo esc_attr( (string) $rid ); ?>" data-per-page="<?php echo esc_attr( (string) $per_page ); ?>">
        <div class="vc-reviews__header">
            <h2 class="vc-reviews__title"><?php echo esc_html__( 'Avaliações', 'vemcomer' ); ?></h2>
            <?php if ( ! empty( $rating ) && $rating['count'] > 0 ) : ?>
                <div class="vc-reviews__rating-summary">
                    <div class="vc-rating-stars vc-rating-stars--large" aria-label="<?php echo esc_attr( sprintf( __( 'Avaliação média: %.1f de 5 estrelas', 'vemcomer' ), $rating['avg'] ) ); ?>">
                        <?php
                        $avg_rounded = round( $rating['avg'] * 2 ) / 2;
                        $full_stars = floor( $avg_rounded );
                        $half_star = ( $avg_rounded - $full_stars ) >= 0.5;
                        $empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );
                        
                        for ( $i = 0; $i < $full_stars; $i++ ) {
                            echo '<span class="vc-star vc-star--full">★</span>';
                        }
                        if ( $half_star ) {
                            echo '<span class="vc-star vc-star--half">★</span>';
                        }
                        for ( $i = 0; $i < $empty_stars; $i++ ) {
                            echo '<span class="vc-star vc-star--empty">☆</span>';
                        }
                        ?>
                    </div>
                    <div class="vc-reviews__rating-text">
                        <strong><?php echo esc_html( number_format( $rating['avg'], 1, ',', '.' ) ); ?></strong>
                        <span><?php echo esc_html( sprintf( _n( '(%d avaliação)', '(%d avaliações)', $rating['count'], 'vemcomer' ), $rating['count'] ) ); ?></span>
                    </div>
                </div>
            <?php else : ?>
                <p class="vc-reviews__no-rating"><?php echo esc_html__( 'Ainda não há avaliações para este restaurante.', 'vemcomer' ); ?></p>
            <?php endif; ?>
        </div>

        <?php if ( $show_form && is_user_logged_in() ) : ?>
            <div class="vc-reviews__form-wrapper">
                <h3 class="vc-reviews__form-title"><?php echo esc_html__( 'Deixe sua avaliação', 'vemcomer' ); ?></h3>
                <form class="vc-reviews__form" id="vc-review-form">
                    <div class="vc-reviews__form-rating">
                        <label><?php echo esc_html__( 'Avaliação:', 'vemcomer' ); ?></label>
                        <div class="vc-rating-input">
                            <?php for ( $i = 5; $i >= 1; $i-- ) : ?>
                                <input type="radio" name="rating" id="rating-<?php echo esc_attr( (string) $i ); ?>" value="<?php echo esc_attr( (string) $i ); ?>" required />
                                <label for="rating-<?php echo esc_attr( (string) $i ); ?>" class="vc-star-input">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="vc-reviews__form-comment">
                        <label for="review-comment"><?php echo esc_html__( 'Comentário (opcional):', 'vemcomer' ); ?></label>
                        <textarea id="review-comment" name="comment" rows="4" placeholder="<?php echo esc_attr__( 'Conte sua experiência...', 'vemcomer' ); ?>"></textarea>
                    </div>
                    <button type="submit" class="vc-btn"><?php echo esc_html__( 'Enviar avaliação', 'vemcomer' ); ?></button>
                    <div class="vc-reviews__form-message"></div>
                </form>
            </div>
        <?php elseif ( $show_form && ! is_user_logged_in() ) : ?>
            <p class="vc-reviews__login-required">
                <?php echo esc_html__( 'Faça login para deixar uma avaliação.', 'vemcomer' ); ?>
                <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php echo esc_html__( 'Entrar', 'vemcomer' ); ?></a>
            </p>
        <?php endif; ?>

        <div class="vc-reviews__list" id="vc-reviews-list">
            <p class="vc-reviews__loading"><?php echo esc_html__( 'Carregando avaliações...', 'vemcomer' ); ?></p>
        </div>

        <div class="vc-reviews__pagination" id="vc-reviews-pagination"></div>
    </div>
    <?php
    return ob_get_clean();
});

