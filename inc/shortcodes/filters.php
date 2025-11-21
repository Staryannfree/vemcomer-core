<?php
/**
 * [vc_filters]
 * Renderiza um formulário GET de filtros para trabalhar junto do [vc_restaurants].
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_filters', function( $atts = [] ) {
    vc_sc_mark_used();

    $cuisine  = isset( $_GET['cuisine'] ) ? sanitize_title( wp_unslash( $_GET['cuisine'] ) ) : '';
    $location = isset( $_GET['location'] ) ? sanitize_title( wp_unslash( $_GET['location'] ) ) : '';
    $delivery = isset( $_GET['delivery'] ) ? (string) $_GET['delivery'] : '';
    $s        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
    $min_rating = isset( $_GET['min_rating'] ) ? (float) $_GET['min_rating'] : '';
    $is_open_now = isset( $_GET['is_open_now'] ) ? (bool) filter_var( $_GET['is_open_now'], FILTER_VALIDATE_BOOLEAN ) : false;
    $has_delivery = isset( $_GET['has_delivery'] ) ? (bool) filter_var( $_GET['has_delivery'], FILTER_VALIDATE_BOOLEAN ) : false;
    $price_min = isset( $_GET['price_min'] ) ? (float) $_GET['price_min'] : '';
    $price_max = isset( $_GET['price_max'] ) ? (float) $_GET['price_max'] : '';

    ob_start();
    ?>
    <form method="get" class="vc-filters vc-filters--advanced">
      <div class="vc-filters__row">
        <input type="text" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="<?php echo esc_attr__( 'Buscar restaurantes, pratos...', 'vemcomer' ); ?>" class="vc-filters__search" />
      </div>
      <div class="vc-filters__row">
        <select name="cuisine" class="vc-filters__select">
          <option value=""><?php echo esc_html__( 'Tipo de cozinha', 'vemcomer' ); ?></option>
          <?php foreach ( get_terms([ 'taxonomy' => 'vc_cuisine', 'hide_empty' => false ]) as $t ) : ?>
            <option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $cuisine, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="location" class="vc-filters__select">
          <option value=""><?php echo esc_html__( 'Bairro', 'vemcomer' ); ?></option>
          <?php foreach ( get_terms([ 'taxonomy' => 'vc_location', 'hide_empty' => false ]) as $t ) : ?>
            <option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $location, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="has_delivery" class="vc-filters__select">
          <option value=""><?php echo esc_html__( 'Delivery', 'vemcomer' ); ?></option>
          <option value="1" <?php selected( $has_delivery, true ); ?>><?php echo esc_html__( 'Com delivery', 'vemcomer' ); ?></option>
          <option value="0" <?php selected( $has_delivery === false && isset( $_GET['has_delivery'] ), true ); ?>><?php echo esc_html__( 'Sem delivery', 'vemcomer' ); ?></option>
        </select>
      </div>
      <div class="vc-filters__row">
        <label class="vc-filters__checkbox">
          <input type="checkbox" name="is_open_now" value="1" <?php checked( $is_open_now, true ); ?> />
          <span><?php echo esc_html__( 'Apenas restaurantes abertos', 'vemcomer' ); ?></span>
        </label>
        <select name="min_rating" class="vc-filters__select">
          <option value=""><?php echo esc_html__( 'Avaliação mínima', 'vemcomer' ); ?></option>
          <option value="4.5" <?php selected( $min_rating, 4.5 ); ?>>4.5+ ⭐</option>
          <option value="4.0" <?php selected( $min_rating, 4.0 ); ?>>4.0+ ⭐</option>
          <option value="3.5" <?php selected( $min_rating, 3.5 ); ?>>3.5+ ⭐</option>
          <option value="3.0" <?php selected( $min_rating, 3.0 ); ?>>3.0+ ⭐</option>
        </select>
        <div class="vc-filters__price-range">
          <label><?php echo esc_html__( 'Preço médio:', 'vemcomer' ); ?></label>
          <input type="number" name="price_min" value="<?php echo esc_attr( $price_min ); ?>" placeholder="Min" step="0.01" min="0" class="vc-filters__price-input" />
          <span>até</span>
          <input type="number" name="price_max" value="<?php echo esc_attr( $price_max ); ?>" placeholder="Max" step="0.01" min="0" class="vc-filters__price-input" />
        </div>
      </div>
      <div class="vc-filters__actions">
        <button type="submit" class="vc-btn"><?php echo esc_html__( 'Filtrar', 'vemcomer' ); ?></button>
        <a href="<?php echo esc_url( remove_query_arg( [ 's', 'cuisine', 'location', 'delivery', 'min_rating', 'is_open_now', 'has_delivery', 'price_min', 'price_max' ] ) ); ?>" class="vc-btn vc-btn--ghost"><?php echo esc_html__( 'Limpar', 'vemcomer' ); ?></a>
      </div>
    </form>
    <?php
    return ob_get_clean();
});
