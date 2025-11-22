<?php
/**
 * Template Part: Seção Hero (Banner Principal) - Marketplace Completo
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$args = isset( $args ) ? $args : [];
$titulo = $args['titulo'] ?? __( 'Descubra, compare e peça dos melhores restaurantes, bares e deliverys da sua cidade', 'vemcomer' );
$subtitulo = $args['subtitulo'] ?? __( 'Delivery, reservas, avaliações, eventos, promoções, blog, mapa e muito mais!', 'vemcomer' );
?>

<style>
.home-hero-marketplace {
    background: linear-gradient(135deg, rgba(47, 158, 68, 0.85) 0%, rgba(30, 126, 52, 0.85) 100%), 
                url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat;
    background-blend-mode: overlay;
    padding: 80px 0 100px 0;
    color: #fff;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.home-hero-marketplace::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(2px);
}

.home-hero-marketplace .container {
    position: relative;
    z-index: 1;
}

.home-hero-marketplace__title {
    font-size: 3.2rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.home-hero-marketplace__subtitle {
    font-size: 1.5rem;
    margin-bottom: 40px;
    opacity: 0.95;
    text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
}

.home-hero-marketplace__search {
    max-width: 700px;
    margin: 0 auto 30px;
    position: relative;
}

.home-hero-marketplace__search-form {
    display: flex;
    background: #fff;
    border-radius: 50px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.home-hero-marketplace__search-input {
    flex: 1;
    border: none;
    padding: 18px 30px;
    font-size: 1.1rem;
    outline: none;
}

.home-hero-marketplace__search-input::placeholder {
    color: #999;
}

.home-hero-marketplace__search-btn {
    background: #2f9e44;
    color: #fff;
    border: none;
    padding: 0 35px;
    font-size: 1.2rem;
    cursor: pointer;
    transition: background 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.home-hero-marketplace__search-btn:hover {
    background: #1e7e34;
}

.home-hero-marketplace__search-autocomplete {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
    margin-top: 5px;
    display: none;
}

.home-hero-marketplace__quick-filters {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    margin: 30px 0 20px;
}

.home-hero-marketplace__filter-chip {
    background: #fff;
    color: #2f9e44;
    border: 1px solid #2f9e44;
    border-radius: 22px;
    padding: 8px 18px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.95rem;
    font-weight: 500;
}

.home-hero-marketplace__filter-chip:hover,
.home-hero-marketplace__filter-chip.is-active {
    background: #2f9e44;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(47, 158, 68, 0.3);
}

.home-hero-marketplace__location-btn {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 25px;
    padding: 10px 20px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 10px 5px;
    font-size: 0.95rem;
}

.home-hero-marketplace__location-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

@media (max-width: 768px) {
    .home-hero-marketplace__title {
        font-size: 2rem;
    }
    
    .home-hero-marketplace__subtitle {
        font-size: 1.2rem;
    }
    
    .home-hero-marketplace__search {
        margin: 0 20px 30px;
    }
    
    .home-hero-marketplace__quick-filters {
        padding: 0 20px;
    }
}
</style>

<section class="home-hero-marketplace">
    <div class="container">
        <h1 class="home-hero-marketplace__title" id="hero-title"><?php echo esc_html( $titulo ); ?></h1>
        <p class="home-hero-marketplace__subtitle" id="hero-subtitle"><?php echo esc_html( $subtitulo ); ?></p>
        
        <div class="home-hero-marketplace__search">
            <form method="get" action="#restaurants-list" class="home-hero-marketplace__search-form" id="hero-search-form">
                <input 
                    type="text" 
                    name="s" 
                    id="hero-search-input"
                    class="home-hero-marketplace__search-input"
                    placeholder="<?php echo esc_attr__( 'Busque por restaurante, prato, bairro, evento ou promo...', 'vemcomer' ); ?>" 
                    value="<?php echo esc_attr( get_query_var( 's' ) ); ?>"
                    autocomplete="off"
                />
                <button type="submit" class="home-hero-marketplace__search-btn">
                    <i class="fas fa-search"></i>
                </button>
                <div class="home-hero-marketplace__search-autocomplete" id="search-autocomplete" style="display: none;"></div>
            </form>
        </div>

        <div class="home-hero-marketplace__quick-filters" id="hero-quick-filters">
            <button class="home-hero-marketplace__filter-chip" data-filter="is_open_now">
                <span>🕐</span>
                <span><?php esc_html_e( 'Aberto agora', 'vemcomer' ); ?></span>
            </button>
            <button class="home-hero-marketplace__filter-chip" data-filter="min_rating" data-value="4.5">
                <span>⭐</span>
                <span><?php esc_html_e( 'Mais avaliados', 'vemcomer' ); ?></span>
            </button>
            <button class="home-hero-marketplace__filter-chip" data-filter="free_shipping">
                <span>🚚</span>
                <span><?php esc_html_e( 'Frete grátis', 'vemcomer' ); ?></span>
            </button>
            <button class="home-hero-marketplace__filter-chip" data-filter="has_promotion">
                <span>%</span>
                <span><?php esc_html_e( 'Promoção', 'vemcomer' ); ?></span>
            </button>
            <button class="home-hero-marketplace__filter-chip" data-filter="kids_friendly">
                <span>👶</span>
                <span><?php esc_html_e( 'Kids', 'vemcomer' ); ?></span>
            </button>
            <button class="home-hero-marketplace__filter-chip" data-filter="has_events">
                <span>🎂</span>
                <span><?php esc_html_e( 'Eventos', 'vemcomer' ); ?></span>
            </button>
            <button class="home-hero-marketplace__filter-chip" data-filter="healthy">
                <span>🥗</span>
                <span><?php esc_html_e( 'Saudável', 'vemcomer' ); ?></span>
            </button>
            <button class="home-hero-marketplace__filter-chip" data-filter="pet_friendly">
                <span>🐾</span>
                <span><?php esc_html_e( 'Pet Friendly', 'vemcomer' ); ?></span>
            </button>
            <button class="home-hero-marketplace__filter-chip" data-filter="bars">
                <span>🍺</span>
                <span><?php esc_html_e( 'Bares', 'vemcomer' ); ?></span>
            </button>
        </div>

        <div id="hero-location-actions">
            <button class="home-hero-marketplace__location-btn" id="vc-use-location" type="button">
                <span>📍</span>
                <span><?php esc_html_e( 'Usar minha localização', 'vemcomer' ); ?></span>
            </button>
        </div>
    </div>
</section>

<?php
// Carregar Font Awesome se necessário
if ( ! wp_style_is( 'font-awesome', 'enqueued' ) ) {
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2' );
}
?>

