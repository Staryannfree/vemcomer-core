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

    ob_start();
    ?>
    <form method="get" class="vc-filters">
      <input type="text" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="<?php echo esc_attr__( 'Buscar…', 'vemcomer' ); ?>" />
      <select name="cuisine">
        <option value=""><?php echo esc_html__( 'Tipo de cozinha', 'vemcomer' ); ?></option>
        <?php foreach ( get_terms([ 'taxonomy' => 'vc_cuisine', 'hide_empty' => false ]) as $t ) : ?>
          <option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $cuisine, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="location">
        <option value=""><?php echo esc_html__( 'Bairro', 'vemcomer' ); ?></option>
        <?php foreach ( get_terms([ 'taxonomy' => 'vc_location', 'hide_empty' => false ]) as $t ) : ?>
          <option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $location, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="delivery">
        <option value=""><?php echo esc_html__( 'Delivery', 'vemcomer' ); ?></option>
        <option value="1" <?php selected( $delivery, '1' ); ?>><?php echo esc_html__( 'Com delivery', 'vemcomer' ); ?></option>
        <option value="0" <?php selected( $delivery, '0' ); ?>><?php echo esc_html__( 'Sem delivery', 'vemcomer' ); ?></option>
      </select>
      <button type="submit"><?php echo esc_html__( 'Filtrar', 'vemcomer' ); ?></button>
    </form>
    <?php
    return ob_get_clean();
});
