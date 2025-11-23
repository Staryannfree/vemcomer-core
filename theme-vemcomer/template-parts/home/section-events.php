<?php
/**
 * Template Part: Seção de Eventos Gastronômicos
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$args = isset( $args ) ? $args : [];
$titulo = $args['titulo'] ?? __( 'Eventos & Agenda Gastronômica', 'vemcomer' );
$quantidade = isset( $args['quantidade'] ) ? absint( $args['quantidade'] ) : 4;
$featured_only = isset( $args['featured_only'] ) ? (bool) $args['featured_only'] : false;

// Verificar se o post type existe
if ( ! post_type_exists( 'vc_event' ) ) {
    return;
}
?>

<section class="home-events" style="padding: 50px 0; background: #fff;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 40px;">
            <h2 class="section-title" style="font-size: 28px; font-weight: bold; margin-bottom: 10px; color: #222;">
                <?php echo esc_html( $titulo ); ?>
            </h2>
            <p class="section-description" style="color: #666; font-size: 16px;">
                <?php esc_html_e( 'Próximos eventos', 'vemcomer' ); ?>
            </p>
        </div>

        <?php
        // Usar shortcode para exibir eventos
        $shortcode_atts = [
            'per_page' => $quantidade,
            'status'   => 'upcoming',
        ];
        
        if ( $featured_only ) {
            $shortcode_atts['featured'] = 'true';
        }
        
        $shortcode = '[vc_events';
        foreach ( $shortcode_atts as $key => $value ) {
            $shortcode .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
        }
        $shortcode .= ']';
        
        echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
    </div>
</section>

<style>
.home-events .vc-events-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.home-events .vc-event-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.home-events .vc-event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.home-events .vc-event-card__image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    position: relative;
}

.home-events .vc-event-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.home-events .vc-event-card__badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #f59e0b;
    color: #fff;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.home-events .vc-event-card__content {
    padding: 20px;
}

.home-events .vc-event-card__title {
    margin: 0 0 10px;
    font-size: 20px;
    font-weight: 700;
}

.home-events .vc-event-card__title a {
    color: #222;
    text-decoration: none;
    transition: color 0.3s;
}

.home-events .vc-event-card__title a:hover {
    color: #2f9e44;
}

.home-events .vc-event-card__restaurant {
    margin-bottom: 12px;
}

.home-events .vc-event-card__restaurant a {
    color: #666;
    text-decoration: none;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.home-events .vc-event-card__restaurant a:hover {
    color: #2f9e44;
}

.home-events .vc-event-card__description {
    margin: 0 0 15px;
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
}

.home-events .vc-event-card__meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: #888;
}

.home-events .vc-event-card__meta > div {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.home-events .vc-event-card__icon {
    font-size: 1.1em;
}

.home-events .vc-event-card__time {
    margin-left: 5px;
    color: #666;
}

.home-events .vc-event-card__button {
    display: block;
    text-align: center;
    background: #2f9e44;
    color: #fff;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.3s;
}

.home-events .vc-event-card__button:hover {
    background: #1e7e34;
}

.home-events .vc-empty {
    text-align: center;
    color: #999;
    padding: 40px 20px;
    font-style: italic;
}

@media (max-width: 768px) {
    .home-events .vc-events-list {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .home-events .vc-event-card__image {
        height: 180px;
    }
}
</style>

