<?php
/**
 * Template Partial: Home Mobile UI
 * Design moderno estilo iFood para mobile
 * HTML completo baseado no design fornecido
 * 
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Buscar banners ativos
$banners_query = new WP_Query([
    'post_type' => 'vc_banner',
    'posts_per_page' => 5,
    'meta_query' => [
        [
            'key' => '_vc_banner_active',
            'value' => '1',
        ],
    ],
    'orderby' => 'menu_order',
    'order' => 'ASC',
]);

// Buscar restaurantes em destaque
$featured_query = new WP_Query([
    'post_type' => 'vc_restaurant',
    'posts_per_page' => 3,
    'meta_query' => [
        [
            'key' => '_vc_restaurant_featured',
            'value' => '1',
        ],
    ],
]);

// Buscar todos os restaurantes
$restaurants_query = new WP_Query([
    'post_type' => 'vc_restaurant',
    'posts_per_page' => 12,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// Buscar pratos do dia (menu items destacados)
$dishes_query = new WP_Query([
    'post_type' => 'vc_menu_item',
    'posts_per_page' => 6,
    'meta_query' => [
        [
            'key' => '_vc_menu_item_featured',
            'value' => '1',
        ],
    ],
]);

// Obter localização do usuário
$user_neighborhood = isset( $_COOKIE['vc_user_neighborhood'] ) ? sanitize_text_field( $_COOKIE['vc_user_neighborhood'] ) : '';
$user_city = isset( $_COOKIE['vc_user_city'] ) ? sanitize_text_field( $_COOKIE['vc_user_city'] ) : '';
$address_text = ! empty( $user_neighborhood ) ? $user_neighborhood : ( ! empty( $user_city ) ? $user_city : __( 'Selecione um endereço', 'vemcomer' ) );
?>

<!-- HERO BANNER CAROUSEL -->
<section class="hero-banner-section">
    <div class="banner-carousel" id="bannerCarousel">
        <?php if ( $banners_query->have_posts() ) : ?>
            <?php $banner_index = 0; ?>
            <?php while ( $banners_query->have_posts() ) : $banners_query->the_post(); ?>
                <?php
                $banner_image = get_post_meta( get_the_ID(), '_vc_banner_image', true );
                $banner_title = get_post_meta( get_the_ID(), '_vc_banner_title', true ) ?: get_the_title();
                $banner_subtitle = get_post_meta( get_the_ID(), '_vc_banner_subtitle', true );
                $banner_link = get_post_meta( get_the_ID(), '_vc_banner_link', true );
                $image_url = $banner_image ? wp_get_attachment_image_url( $banner_image, 'large' ) : get_the_post_thumbnail_url( get_the_ID(), 'large' );
                ?>
                <div class="banner-slide" data-index="<?php echo esc_attr( $banner_index ); ?>" <?php echo $banner_link ? 'onclick="window.location.href=\'' . esc_js( $banner_link ) . '\'"' : ''; ?>>
                    <?php if ( $image_url ) : ?>
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $banner_title ); ?>" class="banner-image" loading="lazy">
                    <?php endif; ?>
                    <div class="banner-overlay">
                        <div class="banner-title"><?php echo esc_html( $banner_title ); ?></div>
                        <?php if ( $banner_subtitle ) : ?>
                            <div class="banner-subtitle"><?php echo esc_html( $banner_subtitle ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php $banner_index++; ?>
            <?php endwhile; ?>
        <?php else : ?>
            <!-- Banner padrão se não houver banners -->
            <div class="banner-slide" data-index="0">
                <div class="banner-overlay">
                    <div class="banner-title">🎉 <?php esc_html_e( 'Bem-vindo ao Pedevem!', 'vemcomer' ); ?></div>
                    <div class="banner-subtitle"><?php esc_html_e( 'Os melhores restaurantes da sua cidade', 'vemcomer' ); ?></div>
                </div>
            </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
    <?php if ( $banners_query->found_posts > 1 ) : ?>
        <div class="banner-dots" id="bannerDots">
            <?php for ( $i = 0; $i < $banners_query->found_posts; $i++ ) : ?>
                <span class="banner-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo esc_attr( $i ); ?>"></span>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>

<!-- STORIES SECTION -->
<section class="stories-section">
    <div class="stories-scroll" id="storiesScroll">
        <!-- Stories serão carregados via JavaScript/API -->
    </div>
</section>

<!-- STORY VIEWER MODAL -->
<div class="story-overlay" id="storyViewer">
    <div class="story-progress story-progress-bars" id="storyProgressBars">
        <div class="progress-bar story-progress-bar">
            <div class="progress-fill story-progress-fill" id="storyProgress0"></div>
        </div>
    </div>
    <button class="story-close-btn" id="storyCloseBtn">×</button>
    <div class="story-header">
        <img src="" alt="Logo restaurante" class="story-avatar story-header-avatar" id="storyHeaderAvatar">
        <div class="story-header-info">
            <div class="story-header-name" id="storyHeaderName"></div>
            <div class="story-header-time" id="storyHeaderTime"></div>
        </div>
    </div>
    <div class="story-content" id="storyContent">
        <img src="" alt="Story do restaurante" class="story-media" id="storyMedia">
        <button class="story-cta story-cta-btn" id="storyCtaBtn"></button>
        <div class="nav-zone nav-left story-tap-left" id="storyTapLeft" title="Voltar"></div>
        <div class="nav-zone nav-right story-tap-right" id="storyTapRight" title="Próxima"></div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="quick-actions">
    <a href="<?php echo esc_url( home_url( '/restaurantes/' ) ); ?>" class="quick-action-btn">
        <svg class="quick-action-icon" viewBox="0 0 24 24">
            <path d="M18 18.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5-1.5.67-1.5 1.5.67 1.5 1.5 1.5zm1.5-9H17V12h4.46L19.5 9.5zM6 18.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5-1.5.67-1.5 1.5.67 1.5 1.5 1.5zM20 8l3 4v5h-2c0 1.66-1.34 3-3 3s-3-1.34-3-3H9c0 1.66-1.34 3-3 3s-3-1.34-3-3H1V6c0-1.11.89-2 2-2h14v4h3zM3 6v9h.76c.55-.61 1.35-1 2.24-1s1.69.39 2.24 1H15V6H3z"/>
        </svg>
        <span class="quick-action-label"><?php esc_html_e( 'Delivery', 'vemcomer' ); ?></span>
    </a>
    <a href="<?php echo esc_url( home_url( '/reservas/' ) ); ?>" class="quick-action-btn">
        <svg class="quick-action-icon" viewBox="0 0 24 24">
            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zM7 11h5v5H7z"/>
        </svg>
        <span class="quick-action-label"><?php esc_html_e( 'Reservas', 'vemcomer' ); ?></span>
    </a>
    <a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" class="quick-action-btn">
        <svg class="quick-action-icon" viewBox="0 0 24 24">
            <path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/>
        </svg>
        <span class="quick-action-label"><?php esc_html_e( 'Eventos', 'vemcomer' ); ?></span>
    </a>
    <a href="<?php echo esc_url( home_url( '/promocoes/' ) ); ?>" class="quick-action-btn">
        <svg class="quick-action-icon" viewBox="0 0 24 24">
            <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
        </svg>
        <span class="quick-action-label"><?php esc_html_e( 'Promoções', 'vemcomer' ); ?></span>
    </a>
</div>

<!-- SEARCH BAR -->
<div class="search-container">
    <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24">
            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
        <form method="get" action="<?php echo esc_url( home_url( '/restaurantes/' ) ); ?>">
            <input type="search" class="search-input" placeholder="<?php esc_attr_e( 'Buscar restaurantes, pratos ou eventos', 'vemcomer' ); ?>" name="s" id="searchInput" value="<?php echo esc_attr( get_query_var( 's' ) ); ?>">
        </form>
        <button class="filter-btn" id="filterBtn" aria-label="<?php esc_attr_e( 'Filtros', 'vemcomer' ); ?>">
            <svg class="filter-icon" viewBox="0 0 24 24">
                <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
            </svg>
        </button>
    </div>
</div>

<!-- MAIN CONTENT -->
<main class="content">
    <!-- PRATOS DO DIA -->
    <?php if ( $dishes_query->have_posts() ) : ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg class="section-icon" viewBox="0 0 24 24">
                    <path d="M8.1 13.34l2.83-2.83L3.91 3.5c-1.56 1.56-1.56 4.09 0 5.66l4.19 4.18zm6.78-1.81c1.53.71 3.68.21 5.27-1.38 1.91-1.91 2.28-4.65.81-6.12-1.46-1.46-4.2-1.1-6.12.81-1.59 1.59-2.09 3.74-1.38 5.27L3.7 19.87l1.41 1.41L12 14.41l6.88 6.88 1.41-1.41L13.41 13l1.47-1.47z"/>
                </svg>
                <?php esc_html_e( 'Pratos do Dia', 'vemcomer' ); ?>
            </h2>
            <a href="<?php echo esc_url( home_url( '/pratos-do-dia/' ) ); ?>" class="section-link">
                <?php esc_html_e( 'Ver todos', 'vemcomer' ); ?>
                <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 24 24">
                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                </svg>
            </a>
        </div>
        <div class="dishes-scroll" id="dishesScroll">
            <?php while ( $dishes_query->have_posts() ) : $dishes_query->the_post(); ?>
                <?php
                $restaurant_id = get_post_meta( get_the_ID(), '_vc_menu_item_restaurant', true );
                $restaurant = $restaurant_id ? get_post( $restaurant_id ) : null;
                $price = get_post_meta( get_the_ID(), '_vc_menu_item_price', true );
                $image_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ?: ( $restaurant ? get_the_post_thumbnail_url( $restaurant_id, 'medium' ) : '' );
                ?>
                <div class="dish-card" onclick="window.location.href='<?php echo esc_url( get_permalink() ); ?>'">
                    <div class="dish-image-wrapper">
                        <?php if ( $image_url ) : ?>
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="dish-image" loading="lazy">
                        <?php endif; ?>
                        <div class="dish-badge"><?php esc_html_e( 'DESTAQUE', 'vemcomer' ); ?></div>
                        <?php if ( $price ) : ?>
                            <div class="dish-price-badge"><?php echo esc_html( 'R$ ' . number_format( $price, 2, ',', '.' ) ); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="dish-content">
                        <?php if ( $restaurant ) : ?>
                            <div class="dish-restaurant"><?php echo esc_html( $restaurant->post_title ); ?></div>
                        <?php endif; ?>
                        <div class="dish-name"><?php the_title(); ?></div>
                        <div class="dish-description"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 15 ) ); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>

    <!-- RESTAURANTES EM DESTAQUE -->
    <?php if ( $featured_query->have_posts() ) : ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg class="section-icon" viewBox="0 0 24 24">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                </svg>
                <?php esc_html_e( 'Restaurantes em Destaque', 'vemcomer' ); ?>
            </h2>
            <a href="<?php echo esc_url( home_url( '/destaques/' ) ); ?>" class="section-link">
                <?php esc_html_e( 'Ver todos', 'vemcomer' ); ?>
                <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 24 24">
                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                </svg>
            </a>
        </div>
        <div class="featured-grid" id="featuredGrid">
            <?php while ( $featured_query->have_posts() ) : $featured_query->the_post(); ?>
                <?php
                $rating = get_post_meta( get_the_ID(), '_vc_restaurant_rating', true ) ?: '4.5';
                $cuisines = get_the_terms( get_the_ID(), 'vc_cuisine' );
                $image_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                ?>
                <div class="featured-card" onclick="window.location.href='<?php echo esc_url( get_permalink() ); ?>'">
                    <?php if ( $image_url ) : ?>
                        <div class="featured-image-wrapper">
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="featured-image" loading="lazy">
                            <div class="featured-badge">⭐ <?php esc_html_e( 'DESTAQUE', 'vemcomer' ); ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="featured-content">
                        <div class="featured-header">
                            <div class="featured-name"><?php the_title(); ?></div>
                            <div class="featured-rating">
                                <svg class="star-icon" viewBox="0 0 24 24">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                                <?php echo esc_html( $rating ); ?>
                            </div>
                        </div>
                        <?php if ( $cuisines && ! is_wp_error( $cuisines ) ) : ?>
                            <div class="featured-tags">
                                <?php foreach ( array_slice( $cuisines, 0, 2 ) as $cuisine ) : ?>
                                    <span class="featured-tag"><?php echo esc_html( $cuisine->name ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <button class="featured-reserve-btn" onclick="event.stopPropagation(); window.location.href='<?php echo esc_url( get_permalink() ); ?>#reservar';">
                            <svg class="reserve-icon" viewBox="0 0 24 24">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zM7 11h5v5H7z"/>
                            </svg>
                            <?php esc_html_e( 'Reservar Mesa', 'vemcomer' ); ?>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>

    <!-- TODOS OS RESTAURANTES -->
    <?php if ( $restaurants_query->have_posts() ) : ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg class="section-icon" viewBox="0 0 24 24">
                    <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
                </svg>
                <?php esc_html_e( 'Todos os Restaurantes', 'vemcomer' ); ?>
            </h2>
        </div>
        <div class="restaurants-grid" id="restaurantsGrid">
            <?php while ( $restaurants_query->have_posts() ) : $restaurants_query->the_post(); ?>
                <?php
                $rating = get_post_meta( get_the_ID(), '_vc_restaurant_rating', true ) ?: '4.5';
                $is_open = get_post_meta( get_the_ID(), '_vc_restaurant_is_open', true );
                $image_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                ?>
                <div class="restaurant-card" onclick="window.location.href='<?php echo esc_url( get_permalink() ); ?>'">
                    <div class="card-image-wrapper">
                        <?php if ( $image_url ) : ?>
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="card-image" loading="lazy">
                        <?php endif; ?>
                        <div class="card-badges">
                            <div class="card-badge <?php echo $is_open ? 'badge-open' : 'badge-closed'; ?>">
                                <?php echo $is_open ? '• ' . esc_html__( 'Aberto', 'vemcomer' ) : esc_html__( 'Fechado', 'vemcomer' ); ?>
                            </div>
                        </div>
                        <button class="favorite-btn" onclick="event.stopPropagation(); toggleFavorite(<?php echo esc_js( get_the_ID() ); ?>);">
                            <svg class="favorite-icon" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="card-header">
                            <div class="card-title"><?php the_title(); ?></div>
                            <div class="card-rating">
                                <svg class="star-icon" viewBox="0 0 24 24">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                                <?php echo esc_html( $rating ); ?>
                            </div>
                        </div>
                        <div class="card-info">
                            <span><?php esc_html_e( '30-45 min', 'vemcomer' ); ?></span>
                            <span class="info-dot"></span>
                            <span><?php esc_html_e( 'R$ 5,00', 'vemcomer' ); ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>
</main>

<!-- CART BUTTON -->
<button class="cart-btn" id="cartBtn">
    <svg class="cart-icon" viewBox="0 0 24 24">
        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
    </svg>
    <span class="cart-badge" id="cartBadge">0</span>
</button>

<script>
// ============ CONFIGURAÇÃO ============
const API_BASE = '/wp-json/vemcomer/v1';

// ============ DADOS DOS STORIES ============
const storiesData = [
    // Stories serão carregados via API
];

// ============ RENDER STORIES ============
function renderStories() {
    const container = document.getElementById('storiesScroll');
    if (!container) return;
    
    // TODO: Carregar stories da API
    container.innerHTML = ''; // Será preenchido via API
}

// ============ STORY VIEWER ============
let currentStoryGroup = null;
let currentStoryIndex = 0;
let storyTimer = null;
let progressInterval = null;

function openStory(groupId) {
    const storyGroup = storiesData.find(s => s.id === groupId);
    if (!storyGroup) return;

    currentStoryGroup = storyGroup;
    currentStoryIndex = 0;
    
    const viewer = document.getElementById('storyViewer');
    if (viewer) {
        viewer.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        renderStoryProgressBars(storyGroup.stories.length);
        showStory(0);
        
        // Marcar como visto
        storyGroup.viewed = true;
        renderStories();
    }
}

function renderStoryProgressBars(count) {
    const container = document.getElementById('storyProgressBars');
    if (!container) return;
    
    container.innerHTML = Array(count).fill(0).map((_, i) => `
        <div class="story-progress-bar">
            <div class="story-progress-fill" id="storyProgress${i}"></div>
        </div>
    `).join('');
}

function showStory(index) {
    if (!currentStoryGroup || index >= currentStoryGroup.stories.length) {
        closeStoryViewer();
        return;
    }

    const story = currentStoryGroup.stories[index];
    currentStoryIndex = index;

    // Update header
    const avatarEl = document.getElementById('storyHeaderAvatar');
    const nameEl = document.getElementById('storyHeaderName');
    const timeEl = document.getElementById('storyHeaderTime');
    
    if (avatarEl) avatarEl.src = currentStoryGroup.restaurant.avatar;
    if (nameEl) nameEl.textContent = currentStoryGroup.restaurant.name;
    if (timeEl) timeEl.textContent = `há ${story.timestamp}`;

    // Update media
    const media = document.getElementById('storyMedia');
    if (media) media.src = story.url;

    // Reset all progress bars
    for (let i = 0; i < currentStoryGroup.stories.length; i++) {
        const progress = document.getElementById(`storyProgress${i}`);
        if (progress) {
            if (i < index) {
                progress.style.width = '100%';
            } else if (i === index) {
                progress.style.width = '0%';
            } else {
                progress.style.width = '0%';
            }
        }
    }

    // Start progress animation
    startStoryProgress(index, story.duration);
}

function startStoryProgress(index, duration) {
    clearInterval(progressInterval);
    clearTimeout(storyTimer);

    const progress = document.getElementById(`storyProgress${index}`);
    if (!progress) return;
    
    let elapsed = 0;
    const interval = 50;

    progressInterval = setInterval(() => {
        elapsed += interval;
        const percentage = (elapsed / duration) * 100;
        progress.style.width = `${Math.min(percentage, 100)}%`;
    }, interval);

    storyTimer = setTimeout(() => {
        nextStory();
    }, duration);
}

function nextStory() {
    if (currentStoryIndex < currentStoryGroup.stories.length - 1) {
        showStory(currentStoryIndex + 1);
    } else {
        // Go to next restaurant's stories
        const currentGroupIndex = storiesData.findIndex(s => s.id === currentStoryGroup.id);
        if (currentGroupIndex < storiesData.length - 1) {
            const nextGroup = storiesData[currentGroupIndex + 1];
            openStory(nextGroup.id);
        } else {
            closeStoryViewer();
        }
    }
}

function previousStory() {
    if (currentStoryIndex > 0) {
        showStory(currentStoryIndex - 1);
    } else {
        // Go to previous restaurant's stories
        const currentGroupIndex = storiesData.findIndex(s => s.id === currentStoryGroup.id);
        if (currentGroupIndex > 0) {
            const prevGroup = storiesData[currentGroupIndex - 1];
            currentStoryGroup = prevGroup;
            showStory(prevGroup.stories.length - 1);
        }
    }
}

function closeStoryViewer() {
    clearInterval(progressInterval);
    clearTimeout(storyTimer);
    
    const viewer = document.getElementById('storyViewer');
    if (viewer) {
        viewer.classList.remove('active');
    }
    document.body.style.overflow = '';
    
    currentStoryGroup = null;
    currentStoryIndex = 0;
}

// Story viewer event listeners
document.addEventListener('DOMContentLoaded', () => {
    const closeBtn = document.getElementById('storyCloseBtn');
    const tapLeft = document.getElementById('storyTapLeft');
    const tapRight = document.getElementById('storyTapRight');
    const content = document.getElementById('storyContent');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeStoryViewer);
    }
    if (tapLeft) {
        tapLeft.addEventListener('click', previousStory);
    }
    if (tapRight) {
        tapRight.addEventListener('click', nextStory);
    }

    // Pause on hold
    if (content) {
        content.addEventListener('touchstart', () => {
            clearTimeout(storyTimer);
            clearInterval(progressInterval);
        });

        content.addEventListener('touchend', () => {
            if (currentStoryGroup) {
                const story = currentStoryGroup.stories[currentStoryIndex];
                const progress = document.getElementById(`storyProgress${currentStoryIndex}`);
                if (progress) {
                    const currentWidth = parseFloat(progress.style.width);
                    const remaining = story.duration * (1 - currentWidth / 100);
                    startStoryProgress(currentStoryIndex, remaining);
                }
            }
        });
    }
    
    // Render stories
    renderStories();
});

// ============ EVENT HANDLERS ============
function openDish(id) {
    window.location.href = `/prato/${id}`;
}

function openEvent(id) {
    window.location.href = `/evento/${id}`;
}

function openRestaurant(id) {
    window.location.href = `/restaurante/${id}`;
}

function openReservation(id, event) {
    if (event) event.stopPropagation();
    window.location.href = `/reservar/${id}`;
}

function toggleFavorite(event, id) {
    if (event) event.stopPropagation();
    // TODO: Implementar favoritos via API
    console.log('Toggle favorite:', id);
}
</script>
