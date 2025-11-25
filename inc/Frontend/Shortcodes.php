<?php
/**
 * Shortcodes públicos do VemComer
 * @package VemComerCore
 */

namespace VC\Frontend;

use VC\Model\CPT_Restaurant;
use VC\Model\CPT_MenuItem;
use VC\Utils\Rating_Helper;
use VC\Utils\Schedule_Helper;
use VC\Subscription\Plan_Manager;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shortcodes {
    private bool $assets_enqueued = false;

    public function init(): void {
        add_shortcode( 'vemcomer_restaurants', [ $this, 'sc_restaurants' ] );
        add_shortcode( 'vemcomer_menu', [ $this, 'sc_menu' ] );
        add_shortcode( 'vemcomer_checkout', [ $this, 'sc_checkout' ] );
        add_shortcode( 'vc_categories', [ $this, 'sc_categories' ] );
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
        wp_enqueue_style( 'vemcomer-favorites' );
        wp_enqueue_script( 'vemcomer-favorites' );
        wp_enqueue_style( 'vemcomer-addresses' );
        wp_enqueue_script( 'vemcomer-addresses' );
        wp_enqueue_script( 'vemcomer-checkout-addresses' );
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
            $is_open = \VC\Utils\Schedule_Helper::is_open( $rid );
            $next_open_time = $is_open ? null : \VC\Utils\Schedule_Helper::get_next_open_time( $rid );
            echo '<div class="vc-card" style="position: relative;">';
            echo '<div class="vc-card__favorite">';
            echo '<button class="vc-favorite-btn" data-restaurant-id="' . esc_attr( (string) $rid ) . '" aria-label="' . esc_attr__( 'Adicionar aos favoritos', 'vemcomer' ) . '">🤍</button>';
            echo '</div>';
            // Link envolvendo todo o card para abrir a página do restaurante
            echo '<a class="vc-card__link" href="' . esc_url( get_permalink( $rid ) ) . '" style="display: block; text-decoration: none; color: inherit;">';
            echo get_the_post_thumbnail( $rid, 'medium', [ 'class' => 'vc-thumb' ] );
            echo '<h3 class="vc-title">' . esc_html( get_the_title() ) . '</h3>';
            echo '<div class="vc-card__status">';
            if ( $is_open ) {
                echo '<span class="vc-badge vc-badge--open"><span class="vc-status-dot vc-status-dot--open"></span>' . esc_html__( 'Aberto', 'vemcomer' ) . '</span>';
            } else {
                echo '<span class="vc-badge vc-badge--closed"><span class="vc-status-dot vc-status-dot--closed"></span>' . esc_html__( 'Fechado', 'vemcomer' ) . '</span>';
                if ( $next_open_time && isset( $next_open_time['time'] ) ) {
                    echo '<span class="vc-next-open">' . esc_html( sprintf( __( 'Abre às %s', 'vemcomer' ), $next_open_time['time'] ) ) . '</span>';
                }
            }
            echo '</div>';
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
            // Fechar link do card antes do botão
            echo '</a>';
            // Botão "Ver cardápio" FORA do link, com z-index maior para ficar acima
            echo '<div style="padding: 8px 12px 12px; position: relative; z-index: 15;">';
            echo '<a class="vc-btn vc-btn--menu" href="' . esc_url( add_query_arg( [ 'restaurant_id' => $rid ], get_permalink( $rid ) ) ) . '#vc-menu" style="position: relative; z-index: 15; display: inline-block; pointer-events: auto;">' . esc_html__( 'Ver cardápio', 'vemcomer' ) . '</a>';
            echo '</div>';
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

        // Verificar Plano Básico
        $has_modifiers = true;
        $is_basic = false;
        if ( class_exists( '\\VC\\Subscription\\Plan_Manager' ) ) {
            $has_modifiers = Plan_Manager::can_use_modifiers( $rid );
            // Se não tem modificadores, assumimos layout básico
            if ( ! $has_modifiers ) {
                $is_basic = true;
                wp_enqueue_style( 'vemcomer-front-basic', plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/frontend-basic-plan.css', [], '1.0' );
            }
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
        $body_class = $is_basic ? 'vc-layout-basic' : '';
        // Hack para adicionar classe ao body via JS se necessário, ou envolver em wrapper
        echo '<div id="vc-menu" class="vc-grid vc-menu ' . esc_attr( $body_class ) . '">';
        
        // Injetar script inline para adicionar classe ao body se for básico
        if ( $is_basic ) {
            echo '<script>document.body.classList.add("vc-layout-basic");</script>';
        }

        while ( $q->have_posts() ) { $q->the_post();
            $mid    = get_the_ID();
            $price  = (string) get_post_meta( $mid, '_vc_price', true );
            $ptime  = (string) get_post_meta( $mid, '_vc_prep_time', true );
            
            $card_class = $is_basic ? 'vc-menu-item-card' : 'vc-card';
            
            echo '<div class="' . $card_class . '">';
            
            // Imagem
            if ( $is_basic ) {
                echo get_the_post_thumbnail( $mid, 'thumbnail', [ 'class' => 'vc-menu-item-card__image' ] );
                echo '<div class="vc-menu-item-card__content">';
            } else {
                echo get_the_post_thumbnail( $mid, 'medium', [ 'class' => 'vc-thumb' ] );
            }

            echo '<h4 class="vc-title">' . esc_html( get_the_title() ) . '</h4>';
            echo '<div class="vc-desc">' . esc_html( wp_strip_all_tags( get_post_field( 'post_content', $mid ) ) ) . '</div>';
            $desc = wp_strip_all_tags( get_post_field( 'post_content', $mid ) );
            $image_url = '';
            if ( has_post_thumbnail( $mid ) ) {
                $image_id = get_post_thumbnail_id( $mid );
                $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
            }
            
            if ( $is_basic ) {
                echo '<div class="vc-menu-item-card__actions">';
                echo '<span class="vc-price" style="margin-right:10px; font-weight:bold;">' . esc_html( $price ) . '</span>';
            } else {
                echo '<div class="vc-line"><span class="vc-price">' . esc_html( $price ) . '</span>';
            }

            $btn_class = $is_basic ? 'vc-btn-add-simple' : 'vc-btn vc-add';
            
            echo '<button class="' . $btn_class . '" 
                data-item-id="' . esc_attr( (string) $mid ) . '" 
                data-item-title="' . esc_attr( get_the_title() ) . '" 
                data-item-price="' . esc_attr( $price ) . '" 
                data-item-description="' . esc_attr( $desc ) . '"
                data-restaurant-id="' . esc_attr( (string) $rid ) . '"';
            if ( $image_url ) {
                echo ' data-item-image="' . esc_url( $image_url ) . '"';
            }
            if ( $is_basic ) {
                echo ' data-simple="1"'; // Sinaliza adição direta
            }
            echo '>';
            if ( $is_basic ) echo '🛒 ';
            echo esc_html__( 'Adicionar', 'vemcomer' ) . '</button>';
            
            if ( $is_basic ) {
                echo '</div>'; // Fecha actions
                echo '</div>'; // Fecha content
            } else {
                echo '</div>'; // Fecha vc-line
            }

            echo '<div class="vc-meta">' . esc_html( sprintf( __( 'Preparo: %s min', 'vemcomer' ), $ptime ?: '—' ) ) . '</div>';
            echo '</div>'; // Fecha card
        }
        echo '</div>';
        
        if ( $is_basic ) {
            echo '<div class="vc-powered-by">';
            echo 'Tecnologia <a href="' . esc_url( home_url() ) . '" target="_blank">Pedevem</a> • Crie sua loja grátis';
            echo '</div>';
        }

        \wp_reset_postdata();
        return ob_get_clean();
    }

    /** Checkout simples */
    public function sc_checkout( $atts = [] ): string {
        $this->ensure_assets();
        // Carregar reverse geocoding no checkout
        wp_enqueue_script('vemcomer-reverse-geocoding');
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
                <?php if ( is_user_logged_in() ) : ?>
                    <div class="vc-addresses-selector" style="margin-bottom: 16px;">
                        <button class="vc-btn vc-btn--ghost vc-btn--small" id="vc-load-addresses"><?php echo esc_html__( 'Usar endereço salvo', 'vemcomer' ); ?></button>
                        <button class="vc-btn vc-btn--ghost vc-btn--small" id="vc-add-address"><?php echo esc_html__( 'Adicionar novo endereço', 'vemcomer' ); ?></button>
                        <div class="vc-addresses-list-container" style="margin-top: 12px; display: none;"></div>
                    </div>
                <?php endif; ?>
                <div style="display: grid; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <button type="button" class="vc-btn vc-btn--ghost vc-btn--small" id="vc-use-location-checkout" style="display: flex; align-items: center; gap: 6px;">
                            <span>📍</span>
                            <span><?php echo esc_html__( 'Usar minha localização', 'vemcomer' ); ?></span>
                        </button>
                    </div>
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

    /**
     * Lista todas as categorias de restaurantes (tipos de cozinha)
     * Shortcode: [vc_categories]
     */
    public function sc_categories( $atts = [] ): string {
        $this->ensure_assets();
        
        // Buscar todas as categorias (taxonomia vc_cuisine)
        $categories = get_terms( [
            'taxonomy'   => 'vc_cuisine',
            'hide_empty' => true, // Apenas categorias com restaurantes
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );
        
        if ( is_wp_error( $categories ) || empty( $categories ) ) {
            return '<div class="vc-empty">' . esc_html__( 'Nenhuma categoria disponível no momento.', 'vemcomer' ) . '</div>';
        }
        
        // Ícones padrão para categorias (pode ser expandido)
        $category_icons = [
            'pizza' => '🍕',
            'brasileira' => '🇧🇷',
            'lanches' => '🍔',
            'sushi' => '🍣',
            'bares' => '🍺',
            'doces' => '🍰',
            'japonesa' => '🍱',
            'chinesa' => '🥢',
            'italiana' => '🍝',
            'mexicana' => '🌮',
            'vegetariana' => '🥗',
            'churrasco' => '🥩',
        ];
        
        ob_start();
        ?>
        <div class="vc-categories-page">
            <h2 class="vc-page-title"><?php esc_html_e( 'Categorias de Restaurantes', 'vemcomer' ); ?></h2>
            <p class="vc-page-subtitle"><?php esc_html_e( 'Escolha uma categoria para ver os restaurantes disponíveis', 'vemcomer' ); ?></p>
            
            <div class="vc-categories-grid">
                <?php foreach ( $categories as $category ) : 
                    $term_id = $category->term_id;
                    $name = $category->name;
                    $slug = $category->slug;
                    $description = $category->description;
                    $count = $category->count;
                    
                    // URL para filtrar restaurantes por categoria
                    $category_url = add_query_arg( 
                        [ 'cuisine' => $slug ], 
                        home_url( '/restaurantes/' ) 
                    );
                    
                    // Obter ícone (verificar slug ou usar padrão)
                    $icon = $category_icons[ $slug ] ?? '🍽️';
                    
                    // Tentar obter imagem da categoria (se houver meta)
                    $image_id = get_term_meta( $term_id, '_vc_category_image', true );
                    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : null;
                ?>
                    <a href="<?php echo esc_url( $category_url ); ?>" class="vc-category-card">
                        <?php if ( $image_url ) : ?>
                            <div class="vc-category-card__image">
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" />
                            </div>
                        <?php else : ?>
                            <div class="vc-category-card__icon">
                                <?php echo esc_html( $icon ); ?>
                            </div>
                        <?php endif; ?>
                        <div class="vc-category-card__content">
                            <h3 class="vc-category-card__name"><?php echo esc_html( $name ); ?></h3>
                            <?php if ( $description ) : ?>
                                <p class="vc-category-card__description"><?php echo esc_html( wp_trim_words( $description, 15 ) ); ?></p>
                            <?php endif; ?>
                            <p class="vc-category-card__count">
                                <?php 
                                echo esc_html( sprintf( 
                                    _n( '%d restaurante', '%d restaurantes', $count, 'vemcomer' ), 
                                    $count 
                                ) ); 
                                ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .vc-categories-page {
            padding: 24px 16px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .vc-page-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #111827;
        }
        
        .vc-page-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 32px;
        }
        
        .vc-categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .vc-categories-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 16px;
            }
        }
        
        .vc-category-card {
            display: block;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .vc-category-card:hover,
        .vc-category-card:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: #ea1d2c;
        }
        
        .vc-category-card__image {
            width: 100%;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 16px;
            background: #f3f4f6;
        }
        
        .vc-category-card__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .vc-category-card__icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 16px;
            line-height: 1;
        }
        
        .vc-category-card__content {
            text-align: center;
        }
        
        .vc-category-card__name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #111827;
        }
        
        .vc-category-card__description {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .vc-category-card__count {
            font-size: 14px;
            color: #ea1d2c;
            font-weight: 500;
            margin: 0;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
