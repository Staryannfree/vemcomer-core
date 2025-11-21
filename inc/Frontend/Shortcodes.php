<?php
/**
 * Shortcodes públicos do VemComer
 * @package VemComerCore
 */

namespace VC\Frontend;

use VC\Model\CPT_Restaurant;
use VC\Model\CPT_MenuItem;
use VC\Utils\Rating_Helper;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shortcodes {
    private bool $assets_enqueued = false;

    public function init(): void {
        add_shortcode( 'vemcomer_restaurants', [ $this, 'sc_restaurants' ] );
        add_shortcode( 'vemcomer_menu', [ $this, 'sc_menu' ] );
        add_shortcode( 'vemcomer_checkout', [ $this, 'sc_checkout' ] );
    }

    private function ensure_assets(): void {
        if ( $this->assets_enqueued ) {
            return;
        }

        $this->assets_enqueued = true;
        wp_enqueue_style( 'vemcomer-front' );
        wp_enqueue_script( 'vemcomer-front' );
        wp_enqueue_style( 'vemcomer-product-modal' );
        wp_enqueue_script( 'vemcomer-product-modal' );
        wp_localize_script( 'vemcomer-front', 'VemComer', [
            'rest'  => [ 'base' => esc_url_raw( rest_url( 'vemcomer/v1' ) ) ],
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    /** Lista de restaurantes com link para menu */
    public function sc_restaurants( $atts = [] ): string {
        $this->ensure_assets();
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
            $rating = Rating_Helper::get_rating( $rid );
            echo '<div class="vc-card">';
            echo get_the_post_thumbnail( $rid, 'medium', [ 'class' => 'vc-thumb' ] );
            echo '<h3 class="vc-title">' . esc_html( get_the_title() ) . '</h3>';
            if ( $rating['count'] > 0 ) {
                $avg_rounded = round( $rating['avg'] * 2 ) / 2;
                $full_stars = floor( $avg_rounded );
                $half_star = ( $avg_rounded - $full_stars ) >= 0.5;
                $empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );
                echo '<div class="vc-card__rating">';
                echo '<div class="vc-rating-stars" aria-label="' . esc_attr( sprintf( __( 'Avaliação: %.1f de 5 estrelas', 'vemcomer' ), $rating['avg'] ) ) . '">';
                for ( $i = 0; $i < $full_stars; $i++ ) {
                    echo '<span class="vc-star vc-star--full">★</span>';
                }
                if ( $half_star ) {
                    echo '<span class="vc-star vc-star--half">★</span>';
                }
                for ( $i = 0; $i < $empty_stars; $i++ ) {
                    echo '<span class="vc-star vc-star--empty">☆</span>';
                }
                echo '</div>';
                echo '<span class="vc-rating-text">' . esc_html( $rating['formatted'] ) . '</span>';
                echo '</div>';
            }
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
        $this->ensure_assets();
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
            $desc = wp_strip_all_tags( get_post_field( 'post_content', $mid ) );
            $image_url = '';
            if ( has_post_thumbnail( $mid ) ) {
                $image_id = get_post_thumbnail_id( $mid );
                $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
            }
            echo '<div class="vc-line"><span class="vc-price">' . esc_html( $price ) . '</span>';
            echo '<button class="vc-btn vc-add" 
                data-item-id="' . esc_attr( (string) $mid ) . '" 
                data-item-title="' . esc_attr( get_the_title() ) . '" 
                data-item-price="' . esc_attr( $price ) . '" 
                data-item-description="' . esc_attr( $desc ) . '"
                data-restaurant-id="' . esc_attr( (string) $rid ) . '"';
            if ( $image_url ) {
                echo ' data-item-image="' . esc_url( $image_url ) . '"';
            }
            echo '>' . esc_html__( 'Adicionar', 'vemcomer' ) . '</button></div>';
            echo '<div class="vc-meta">' . esc_html( sprintf( __( 'Preparo: %s min', 'vemcomer' ), $ptime ?: '—' ) ) . '</div>';
            echo '</div>';
        }
        echo '</div>';
        \wp_reset_postdata();
        return ob_get_clean();
    }

    /** Checkout simples */
    public function sc_checkout( $atts = [] ): string {
        $this->ensure_assets();
        $rid = isset( $_GET['restaurant_id'] ) ? (int) $_GET['restaurant_id'] : 0;
        
        // Obter dados do usuário logado se disponível
        $current_user = wp_get_current_user();
        $user_name = $current_user->exists() ? $current_user->display_name : '';
        $user_email = $current_user->exists() ? $current_user->user_email : '';
        
        ob_start();
        ?>
        <div class="vc-checkout" data-restaurant="<?php echo esc_attr( (string) $rid ); ?>" data-single-seller="1">
            <h3><?php echo esc_html__( 'Checkout', 'vemcomer' ); ?></h3>
            <div class="vc-cart"></div>
            
            <div class="vc-customer-info" style="margin-top: 20px; padding: 16px; background: #f9fafb; border-radius: 12px;">
                <h4 style="margin: 0 0 12px; font-size: 1rem;"><?php echo esc_html__( 'Dados para entrega', 'vemcomer' ); ?></h4>
                <div style="display: grid; gap: 12px;">
                    <label>
                        <?php echo esc_html__( 'Nome completo', 'vemcomer' ); ?>
                        <input type="text" class="vc-customer-name" placeholder="<?php echo esc_attr__( 'Seu nome', 'vemcomer' ); ?>" value="<?php echo esc_attr( $user_name ); ?>" />
                    </label>
                    <label>
                        <?php echo esc_html__( 'Telefone', 'vemcomer' ); ?>
                        <input type="tel" class="vc-customer-phone" placeholder="(00) 00000-0000" />
                    </label>
                </div>
            </div>
            
            <div class="vc-shipping" style="margin-top: 20px;">
                <h4 style="margin: 0 0 12px; font-size: 1rem;"><?php echo esc_html__( 'Endereço de entrega', 'vemcomer' ); ?></h4>
                <div style="display: grid; gap: 12px;">
                    <label>
                        <?php echo esc_html__( 'CEP', 'vemcomer' ); ?>
                        <input type="text" class="vc-zip" placeholder="00000-000" />
                    </label>
                    <label>
                        <?php echo esc_html__( 'Endereço completo', 'vemcomer' ); ?>
                        <input type="text" class="vc-customer-address" placeholder="<?php echo esc_attr__( 'Rua, número, complemento', 'vemcomer' ); ?>" />
                    </label>
                </div>
                <button class="vc-btn vc-quote" style="margin-top: 12px;"><?php echo esc_html__( 'Calcular frete', 'vemcomer' ); ?></button>
                <div class="vc-quote-result"></div>
            </div>
            
            <div class="vc-coupon-section" style="margin-top: 20px; padding: 16px; background: #fff7ed; border-radius: 12px;">
                <label>
                    <?php echo esc_html__( 'Cupom de desconto', 'vemcomer' ); ?>
                    <input type="text" class="vc-coupon" placeholder="<?php echo esc_attr__( 'Digite o código', 'vemcomer' ); ?>" style="margin-top: 8px;" />
                </label>
            </div>
            
            <div class="vc-summary" style="margin-top: 20px; padding: 16px; background: #f0fdf4; border-radius: 12px;">
                <div class="vc-subtotal"></div>
                <div class="vc-freight"></div>
                <div class="vc-discount"></div>
                <div class="vc-total" style="font-size: 1.25rem; font-weight: 700; margin-top: 8px;"></div>
                <div class="vc-eta"></div>
            </div>
            <button class="vc-btn vc-place-order" disabled style="margin-top: 20px; width: 100%;"><?php echo esc_html__( 'Finalizar pedido no WhatsApp', 'vemcomer' ); ?></button>
            <div class="vc-order-result"></div>
            <p class="vc-tip" style="margin-top: 16px; font-size: 0.9rem; color: #6b7280;"><?php echo esc_html__( 'O carrinho aceita itens de um único restaurante para garantir cálculo correto de frete.', 'vemcomer' ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
