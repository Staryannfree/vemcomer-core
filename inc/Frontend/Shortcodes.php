<?php
/**
 * Shortcodes públicos do VemComer
 * @package VemComerCore
 */

namespace VC\Frontend;

use VC\Model\CPT_Restaurant;
use VC\Model\CPT_MenuItem;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shortcodes {
    public function init(): void {
        add_shortcode( 'vemcomer_restaurants', [ $this, 'sc_restaurants' ] );
        add_shortcode( 'vemcomer_menu', [ $this, 'sc_menu' ] );
        add_shortcode( 'vemcomer_checkout', [ $this, 'sc_checkout' ] );

        add_action( 'wp_enqueue_scripts', function () {
            wp_enqueue_style( 'vemcomer-front' );
            wp_enqueue_script( 'vemcomer-front' );
            wp_localize_script( 'vemcomer-front', 'VemComer', [
                'rest' => [ 'base' => esc_url_raw( rest_url( 'vemcomer/v1' ) ) ],
                'nonce' => wp_create_nonce( 'wp_rest' ),
            ] );
        } );
    }

    /** Lista de restaurantes com link para menu */
    public function sc_restaurants( $atts = [] ): string {
        $q = new \WP_Query([
            'post_type'      => CPT_Restaurant::SLUG,
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ]);
        if ( ! $q->have_posts() ) {
            return '<div class="vc-empty">' . esc_html__( 'Sem restaurantes no momento.', 'vemcomer' ) . '</div>';
        }
        ob_start();
        echo '<div class="vc-grid vc-restaurants">';
        while ( $q->have_posts() ) { $q->the_post();
            $rid = get_the_ID();
            $addr = get_post_meta( $rid, '_vc_address', true );
            echo '<div class="vc-card">';
            echo get_the_post_thumbnail( $rid, 'medium', [ 'class' => 'vc-thumb' ] );
            echo '<h3 class="vc-title">' . esc_html( get_the_title() ) . '</h3>';
            echo '<div class="vc-meta">' . esc_html( $addr ) . '</div>';
            echo '<a class="vc-btn" href="' . esc_url( add_query_arg( [ 'restaurant_id' => $rid ], get_permalink() ) ) . '#vc-menu">' . esc_html__( 'Ver cardápio', 'vemcomer' ) . '</a>';
            echo '</div>';
        }
        echo '</div>';
        \wp_reset_postdata();
        return ob_get_clean();
    }

    /** Lista itens do cardápio de um restaurante */
    public function sc_menu( $atts = [] ): string {
        $rid = isset( $_GET['restaurant_id'] ) ? (int) $_GET['restaurant_id'] : 0; // permite via URL
        $atts = shortcode_atts( [ 'restaurant_id' => $rid ], $atts, 'vemcomer_menu' );
        $rid  = (int) $atts['restaurant_id'];
        if ( ! $rid ) {
            return '<div class="vc-empty">' . esc_html__( 'Selecione um restaurante.', 'vemcomer' ) . '</div>';
        }
        $q = new \WP_Query([
            'post_type'      => CPT_MenuItem::SLUG,
            'posts_per_page' => 200,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
            'meta_query'     => [ [ 'key' => '_vc_restaurant_id', 'value' => $rid, 'compare' => '=' ] ],
        ]);
        if ( ! $q->have_posts() ) {
            return '<div class="vc-empty">' . esc_html__( 'Cardápio vazio.', 'vemcomer' ) . '</div>';
        }
        ob_start();
        echo '<div id="vc-menu" class="vc-grid vc-menu">';
        while ( $q->have_posts() ) { $q->the_post();
            $mid    = get_the_ID();
            $price  = (string) get_post_meta( $mid, '_vc_price', true );
            $ptime  = (string) get_post_meta( $mid, '_vc_prep_time', true );
            echo '<div class="vc-card">';
            echo get_the_post_thumbnail( $mid, 'medium', [ 'class' => 'vc-thumb' ] );
            echo '<h4 class="vc-title">' . esc_html( get_the_title() ) . '</h4>';
            echo '<div class="vc-desc">' . esc_html( wp_strip_all_tags( get_post_field( 'post_content', $mid ) ) ) . '</div>';
            echo '<div class="vc-line"><span class="vc-price">' . esc_html( $price ) . '</span>';
            echo '<button class="vc-btn vc-add" data-id="' . esc_attr( (string) $mid ) . '" data-title="' . esc_attr( get_the_title() ) . '" data-price="' . esc_attr( $price ) . '" data-restaurant="' . esc_attr( (string) $rid ) . '">' . esc_html__( 'Adicionar', 'vemcomer' ) . '</button></div>';
            echo '<div class="vc-meta">' . esc_html( sprintf( __( 'Preparo: %s min', 'vemcomer' ), $ptime ?: '—' ) ) . '</div>';
            echo '</div>';
        }
        echo '</div>';
        \wp_reset_postdata();
        return ob_get_clean();
    }

    /** Checkout simples */
    public function sc_checkout( $atts = [] ): string {
        $rid = isset( $_GET['restaurant_id'] ) ? (int) $_GET['restaurant_id'] : 0;
        ob_start();
        ?>
        <div class="vc-checkout" data-restaurant="<?php echo esc_attr( (string) $rid ); ?>" data-single-seller="1">
            <h3><?php echo esc_html__( 'Checkout', 'vemcomer' ); ?></h3>
            <div class="vc-cart"></div>
            <div class="vc-shipping">
                <label>
                    <?php echo esc_html__( 'CEP para entrega', 'vemcomer' ); ?>
                    <input type="text" class="vc-zip" placeholder="00000-000" />
                </label>
                <button class="vc-btn vc-quote"><?php echo esc_html__( 'Calcular frete', 'vemcomer' ); ?></button>
                <div class="vc-quote-result"></div>
            </div>
            <div class="vc-summary">
                <div class="vc-subtotal"></div>
                <div class="vc-freight"></div>
                <div class="vc-discount"></div>
                <div class="vc-total"></div>
                <div class="vc-eta"></div>
            </div>
            <button class="vc-btn vc-place-order" disabled><?php echo esc_html__( 'Finalizar pedido', 'vemcomer' ); ?></button>
            <div class="vc-order-result"></div>
            <p class="vc-tip"><?php echo esc_html__( 'O carrinho aceita itens de um único restaurante para garantir cálculo correto de frete.', 'vemcomer' ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
