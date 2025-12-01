<?php
/**
 * Template single do CPT vc_restaurant.
 *
 * Replica o layout do perfil-restaurante.html com dados dinâmicos do backend.
 *
 * @package VemComer\Core
 */

get_header();

while ( have_posts() ) :
the_post();

$cuisine_terms  = wp_get_post_terms( get_the_ID(), 'vc_cuisine', array( 'fields' => 'names' ) );
$location_terms = wp_get_post_terms( get_the_ID(), 'vc_location', array( 'fields' => 'names' ) );
$cuisine_list   = ! is_wp_error( $cuisine_terms ) ? $cuisine_terms : array();
$location_list  = ! is_wp_error( $location_terms ) ? $location_terms : array();
$site_url       = get_post_meta( get_the_ID(), 'vc_restaurant_site', true );
$delivery_raw   = get_post_meta( get_the_ID(), 'vc_restaurant_delivery', true );
$address        = get_post_meta( get_the_ID(), 'vc_restaurant_address', true );
$whatsapp       = get_post_meta( get_the_ID(), 'vc_restaurant_whatsapp', true );
$lat            = (float) get_post_meta( get_the_ID(), 'vc_restaurant_lat', true );
$lng            = (float) get_post_meta( get_the_ID(), 'vc_restaurant_lng', true );
$excerpt        = has_excerpt() ? get_the_excerpt() : '';
$wa_digits      = preg_replace( '/\D+/', '', (string) $whatsapp );
$title_letter   = get_the_title();
$title_letter   = strtoupper( (string) ( function_exists( 'mb_substr' ) ? mb_substr( $title_letter, 0, 1, 'UTF-8' ) : substr( $title_letter, 0, 1 ) ) );

$list_from_text = static function ( string $raw ): array {
$parts = preg_split( '/\r\n|\r|\n/', $raw );
$parts = is_array( $parts ) ? $parts : array();

return array_values( array_filter( array_map( 'trim', $parts ) ) );
};

$banner_urls      = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_banners', true ) );
$highlight_tags   = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_highlight_tags', true ) );
$menu_filters     = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_menu_filters', true ) );
$payment_methods  = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_payment_methods', true ) );
$facilities       = $list_from_text( (string) get_post_meta( get_the_ID(), 'vc_restaurant_facilities', true ) );
$observations     = get_post_meta( get_the_ID(), 'vc_restaurant_observations', true );
$instagram_handle = get_post_meta( get_the_ID(), 'vc_restaurant_instagram', true );

$faq_raw   = (string) get_post_meta( get_the_ID(), 'vc_restaurant_faq', true );
$faq_lines = $list_from_text( $faq_raw );
$faq_items = array();
foreach ( $faq_lines as $line ) {
$parts = array_map( 'trim', explode( '|', $line, 2 ) );
if ( ! empty( $parts[0] ) ) {
$faq_items[] = array(
'question' => $parts[0],
'answer'   => $parts[1] ?? '',
);
}
}

$rating_data   = function_exists( 'vc_restaurant_get_rating' ) ? vc_restaurant_get_rating( get_the_ID() ) : array( 'avg' => null, 'count' => 0 );
$is_open       = function_exists( 'vc_restaurant_is_open' ) ? vc_restaurant_is_open( get_the_ID() ) : null;
$orders_count  = (int) get_post_meta( get_the_ID(), 'vc_restaurant_orders_count', true );
$delivery_eta  = get_post_meta( get_the_ID(), 'vc_restaurant_delivery_eta', true );
$delivery_fee  = get_post_meta( get_the_ID(), 'vc_restaurant_delivery_fee', true );
$delivery_type = get_post_meta( get_the_ID(), 'vc_restaurant_delivery_type', true );
$free_shipping = get_post_meta( get_the_ID(), '_vc_delivery_free_above', true );

$reservation_enabled = '1' === get_post_meta( get_the_ID(), 'vc_restaurant_reservation_enabled', true );
$reservation_link    = get_post_meta( get_the_ID(), 'vc_restaurant_reservation_link', true );
$reservation_phone   = get_post_meta( get_the_ID(), 'vc_restaurant_reservation_phone', true );
$reservation_notes   = get_post_meta( get_the_ID(), 'vc_restaurant_reservation_notes', true );

$hours_formatted = '';
if ( class_exists( '\\VC\\Utils\\Schedule_Helper' ) ) {
$schedule     = \VC\Utils\Schedule_Helper::get_schedule( get_the_ID() );
$day_names_pt = array(
'monday'    => __( 'Segunda', 'vemcomer' ),
'tuesday'   => __( 'Terça', 'vemcomer' ),
'wednesday' => __( 'Quarta', 'vemcomer' ),
'thursday'  => __( 'Quinta', 'vemcomer' ),
'friday'    => __( 'Sexta', 'vemcomer' ),
'saturday'  => __( 'Sábado', 'vemcomer' ),
'sunday'    => __( 'Domingo', 'vemcomer' ),
);
if ( ! empty( $schedule ) ) {
$lines = array();
foreach ( $schedule as $day => $day_data ) {
if ( empty( $day_data['enabled'] ) || empty( $day_data['periods'] ) ) {
continue;
}
$periods_str = array();
foreach ( $day_data['periods'] as $period ) {
$open  = $period['open'] ?? '';
$close = $period['close'] ?? '';
if ( $open && $close ) {
$periods_str[] = $open . ' - ' . $close;
}
}
if ( $periods_str ) {
$lines[] = ( $day_names_pt[ $day ] ?? ucfirst( $day ) ) . ': ' . implode( ', ', $periods_str );
}
}
$hours_formatted = implode( "\n", $lines );
}
}

if ( ! $hours_formatted ) {
$hours_formatted = get_post_meta( get_the_ID(), 'vc_restaurant_open_hours', true );
}

$has_coordinates = $lat && $lng;

if ( $has_coordinates ) {
wp_enqueue_style( 'leaflet' );
wp_enqueue_script( 'leaflet' );
wp_enqueue_script( 'vemcomer-restaurant-map' );
wp_localize_script(
'vemcomer-restaurant-map',
'VC_RESTAURANT_MAP',
array(
'lat'   => $lat,
'lng'   => $lng,
'title' => get_the_title(),
'tiles' => function_exists( 'vc_tiles_url' ) ? vc_tiles_url() : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
)
);
}
?>
<style>
:root { --primary: #2d8659; --primary-light: #eaf8f1; --accent: #45c676; --gray: #f6f9f6; --danger: #ea5252; --meta: #6b7672; --text: #232a2c; --star: #facb32; }
body.single-vc_restaurant { background: var(--gray); font-family: 'Montserrat', Arial, sans-serif; color: var(--text); }
.vc-profile__wrap { max-width: 680px; margin: 0 auto; padding: 0 4vw 96px; }
.vc-header-capa { width: 100vw; max-width: 680px; min-height: 130px; height: 33vw; max-height: 190px; border-radius: 0 0 18px 18px; position: relative; margin: 0 auto; overflow: hidden; box-shadow: 0 3px 14px #2d865914; background-size: cover; background-position: center; background-repeat: no-repeat; }
.vc-rest-logo { position: absolute; left: 50%; bottom: -33px; transform: translateX(-50%); width: 90px; height: 90px; background: #fff; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 9px #2d865930; overflow: hidden; z-index: 3; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary); }
.vc-rest-logo img { width: 82px; height: 82px; object-fit: cover; border-radius: 50%; }
.vc-header-content { margin-top: 70px; text-align: center; }
.vc-nome { font-size: 1.25rem; font-weight: 700; margin: 0 0 6px 0; letter-spacing: .01em; display: flex; gap: 7px; justify-content: center; align-items: center; flex-wrap: wrap; }
.vc-badge-selo { background: var(--primary-light); color: var(--primary); font-size: .81em; border-radius: 11px; padding: 3px 13px; font-weight: 600; border: 1px solid #b9eacd; }
.vc-favorito { background: none; border: none; color: var(--primary); font-size: 1.33em; margin-left: 4px; cursor: pointer; transition: color .17s; }
.vc-favorito.active { color: var(--accent); }
.vc-banners { display: flex; gap: 9px; margin: 12px 0 9px 0; justify-content: center; flex-wrap: wrap; }
.vc-banner-img { width: 90px; height: 36px; object-fit: cover; border-radius: 10px; box-shadow: 0 1px 8px #2d865914; }
.vc-restaurant-info { display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; font-size: 1.04em; margin-top: 6px; color: var(--meta); }
.vc-info-attr { display: flex; align-items: center; gap: 4px; }
.vc-star { color: var(--star); }
.vc-tag-status { font-size: .98em; padding: 2.5px 14px; border-radius: 13px; font-weight: 650; background: var(--primary-light); color: var(--primary); margin-left: 11px; box-shadow: 0 1px 4px #2d865912; border: 1px solid #caf4de; }
.vc-tag-status.is-closed { background: #ffe9ea; color: var(--danger); border-color: #ffd8da; }
.vc-horario { font-size: .94em; color: var(--meta); margin: 4px 0 6px 0; }
.vc-badges { display: flex; gap: 10px; justify-content: center; margin: 6px 0 5px 0; flex-wrap: wrap; }
.vc-badge-pill { background: var(--primary); color: #fff; font-size: .85em; border-radius: 12px; padding: 2.5px 13px; font-weight: 700; box-shadow: 0 1px 4px #2d865914; }
.vc-badge-pill.is-accent { background: var(--accent); }
.vc-destaques { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-bottom: 6px; }
.vc-destaque-tag { background: var(--primary-light); color: var(--primary); border-radius: 8px; font-size: .92em; padding: 2px 11px; font-weight: 600; border: 1.2px solid #eaf8f1; }
.vc-tabs { display: flex; gap: 2px; margin: 22px 0 16px 0; border-bottom: 2px solid #dde4de; background: #fff; position: sticky; top: 0; z-index: 10; }
.vc-tab-btn { flex: 1; padding: 13px 0 10px 0; background: none; border: none; font-size: 1.06rem; font-weight: 700; color: var(--meta); border-bottom: 3px solid transparent; cursor: pointer; transition: color .17s, border .19s, background .18s; }
.vc-tab-btn .vc-tab-indicador { background: var(--primary-light); color: var(--primary); font-weight: 700; border-radius: 8px; font-size: .83em; padding: 1px 7px 0 7px; margin-left: 2px; }
.vc-tab-btn.active { color: var(--primary); border-bottom: 3px solid var(--primary); background: var(--primary-light); }
.vc-aba { margin-bottom: 28px; margin-top: 8px; }
.vc-busca-categoria { width: 100%; margin-bottom: 13px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.vc-busca-categoria input[type="search"] { flex: 1; min-width: 200px; padding: 8px 11px; border-radius: 7px; border: 1.1px solid #cdeee2; font-size: 1em; background: #fff; }
.vc-filtro-alim { margin-left: 3px; color: var(--primary); display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.vc-reserva-bloco { background: var(--primary-light); border-radius: 12px; padding: 14px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.vc-reserva-bloco .vc-item-btn { margin-left: 13px; font-size: 1em; }
.vc-menu-wrapper { background: #fff; border-radius: 12px; padding: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); border: 1px solid #e9f7ef; }
.vc-aval-filtros { margin-bottom: 9px; display: flex; gap: 7px; flex-wrap: wrap; }
.vc-filtro-aval { background: var(--primary-light); color: var(--primary); border-radius: 6px; padding: 3px 11px; border: none; cursor: pointer; font-weight: 600; }
.vc-filtro-aval.active { background: var(--primary); color: #fff; }
.vc-infos { padding: 11px 8px; background: #fff; border-radius: 10px; margin-bottom: 10px; font-size: .99em; color: var(--meta); line-height: 1.59; box-shadow: 0 1px 5px #2d86590b; border: 1px solid #eff6f1; }
.vc-infos strong { color: var(--primary); }
.vc-infos-tags { margin-top: 7px; }
.vc-infos-tag { display: inline-block; background: var(--primary-light); color: var(--primary); border-radius: 8px; padding: 2.5px 10px; margin: 2px 3px 2px 0; font-size: 0.93em; }
.vc-map-iframe { width: 100%; height: 180px; border: 0; border-radius: 9px; margin: 5px 0 10px 0; }
#vc-modalReserva { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #0005; z-index: 999; align-items: center; justify-content: center; padding: 16px; }
#vc-modalReserva .vc-modal-wrap { background: #fff; border-radius: 15px; max-width: 360px; width: 92vw; padding: 21px 15px; box-shadow: 0 10px 28px #2d865958; position: relative; }
#vc-modalReserva .close-modal { position: absolute; right: 11px; top: 11px; background: none; border: none; font-size: 1.25em; color: var(--primary); cursor: pointer; }
#vc-modalReserva input, #vc-modalReserva select, #vc-modalReserva textarea { width: 100%; padding: 8px 11px; border-radius: 6px; border: 1.12px solid #d7e6df; font-size: .98em; margin-bottom: 3px; }
#vc-modalReserva form { display: flex; flex-direction: column; gap: 7px; }
#vc-modalReserva .reserva-opcoes { display: flex; gap: 7px; }
#vc-modalReserva .reserva-opcoes label { flex: 1; }
#vc-reservaOk { text-align: center; margin-top: 11px; color: var(--primary); font-weight: 600; display: none; }
.vc-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; height: 70px; background: #fff; box-shadow: 0 -2px 8px rgba(0,0,0,0.08); z-index: 1000; padding-bottom: env(safe-area-inset-bottom,0px); }
.vc-bottom-nav__inner { display: flex; height: 70px; justify-content: space-around; align-items: center; }
.vc-bottom-nav a { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; text-decoration: none; color: #717171; font-size: 11px; font-weight: 500; }
.vc-bottom-nav svg { width: 24px; height: 24px; fill: currentColor; }
@media (max-width: 430px) {
.vc-profile__wrap { padding: 9px 2vw 96px; }
.vc-header-capa { min-height: 70px; }
.vc-rest-logo { width: 58px; height: 58px; bottom: -18px; }
.vc-rest-logo img { width: 52px; height: 52px; }
.vc-header-content { margin-top: 32px; }
#vc-modalReserva .vc-modal-wrap { padding: 10px 2vw; }
}
</style>
<main class="vc-profile__wrap" id="vc-profile">
<?php
// Tenta obter a foto de perfil: primeiro featured image, depois meta field logo.
$profile_image_url = '';
$profile_image_id  = 0;

if ( has_post_thumbnail() ) {
    $profile_image_id  = get_post_thumbnail_id( get_the_ID() );
    $profile_image_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
} else {
    // Fallback: usa o meta field vc_restaurant_logo.
    $logo_url = get_post_meta( get_the_ID(), VC_META_RESTAURANT_FIELDS['logo'], true );
    if ( $logo_url ) {
        $profile_image_url = $logo_url;
        // Tenta encontrar o attachment ID pela URL.
        $profile_image_id = attachment_url_to_postid( $logo_url );
    }
}

// Banner / capa do topo: prioriza meta cover, depois lista de banners, depois a própria foto de perfil.
$cover_url = (string) get_post_meta( get_the_ID(), VC_META_RESTAURANT_FIELDS['cover'], true );
$header_bg = $cover_url ?: ( $banner_urls[0] ?? $profile_image_url );
?>
<div class="vc-header-capa" style="background-image: url('<?php echo esc_url( $header_bg ?: '' ); ?>');">
<div class="vc-rest-logo">
<?php if ( $profile_image_url ) : ?>
    <?php if ( $profile_image_id ) : ?>
        <?php echo wp_get_attachment_image( $profile_image_id, 'thumbnail', false, array( 'class' => 'vc-rest-logo__img' ) ); ?>
    <?php else : ?>
        <img src="<?php echo esc_url( $profile_image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="vc-rest-logo__img" />
    <?php endif; ?>
<?php else : ?>
    <?php echo esc_html( $title_letter ); ?>
<?php endif; ?>
</div>
</div>
<div class="vc-header-content">
<div class="vc-nome">
<?php the_title(); ?>
<?php if ( $highlight_tags ) : ?>
<span class="vc-badge-selo"><?php echo esc_html( $highlight_tags[0] ); ?></span>
<?php endif; ?>
<button title="<?php echo esc_attr__( 'Favoritar', 'vemcomer' ); ?>" class="vc-favorito" type="button" aria-pressed="false">♥</button>
</div>
<?php if ( $banner_urls ) : ?>
<div class="vc-banners">
<?php foreach ( $banner_urls as $banner_url ) : ?>
<img src="<?php echo esc_url( $banner_url ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Banner de %s', 'vemcomer' ), get_the_title() ) ); ?>" class="vc-banner-img" loading="lazy" />
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="vc-restaurant-info">
<?php if ( ! empty( $rating_data['avg'] ) ) : ?>
<span class="vc-info-attr" title="<?php echo esc_attr__( 'Nota dos clientes', 'vemcomer' ); ?>"><span class="vc-star">★</span> <?php echo esc_html( number_format_i18n( (float) $rating_data['avg'], 1 ) ); ?></span>
<?php endif; ?>
<?php if ( $orders_count > 0 ) : ?>
<span class="vc-info-attr" title="<?php echo esc_attr__( 'Total de pedidos', 'vemcomer' ); ?>"><?php echo esc_html( sprintf( __( '%s pedidos', 'vemcomer' ), number_format_i18n( $orders_count ) ) ); ?></span>
<?php endif; ?>
<?php if ( $delivery_eta ) : ?>
<span class="vc-info-attr" title="<?php echo esc_attr__( 'Tempo de entrega', 'vemcomer' ); ?>">⏱️ <?php echo esc_html( $delivery_eta ); ?></span>
<?php endif; ?>
<?php if ( $delivery_fee ) : ?>
<span class="vc-info-attr" title="<?php echo esc_attr__( 'Taxa de entrega', 'vemcomer' ); ?>"><?php echo esc_html( $delivery_fee ); ?></span>
<?php endif; ?>
<?php if ( null !== $is_open ) : ?>
<span class="vc-tag-status <?php echo $is_open ? '' : 'is-closed'; ?>"><?php echo $is_open ? esc_html__( 'Aberto', 'vemcomer' ) : esc_html__( 'Fechado', 'vemcomer' ); ?></span>
<?php endif; ?>
</div>
<?php if ( $hours_formatted ) : ?>
<div class="vc-horario">
<?php echo nl2br( esc_html( $hours_formatted ) ); ?>
<?php if ( $delivery_raw ) : ?>
<?php echo esc_html__( ' | Delivery e Retirada', 'vemcomer' ); ?>
<?php endif; ?>
</div>
<?php endif; ?>
<div class="vc-badges">
<?php if ( 'own' === $delivery_type ) : ?>
<span class="vc-badge-pill"><?php echo esc_html__( 'Entrega Própria', 'vemcomer' ); ?></span>
<?php elseif ( 'marketplace' === $delivery_type ) : ?>
<span class="vc-badge-pill"><?php echo esc_html__( 'Parceiro de entrega', 'vemcomer' ); ?></span>
<?php elseif ( 'pickup' === $delivery_type ) : ?>
<span class="vc-badge-pill"><?php echo esc_html__( 'Retirada no local', 'vemcomer' ); ?></span>
<?php endif; ?>
<?php if ( $free_shipping ) : ?>
<?php $free_shipping_label = function_exists( 'wc_price' ) ? wc_price( (float) $free_shipping ) : sprintf( 'R$ %s', number_format_i18n( (float) $free_shipping, 2 ) ); ?>
<span class="vc-badge-pill is-accent"><?php echo esc_html( sprintf( __( 'Entrega Grátis acima de %s', 'vemcomer' ), $free_shipping_label ) ); ?></span>
<?php endif; ?>
</div>
<?php if ( $highlight_tags || $cuisine_list || $location_list ) : ?>
<div class="vc-destaques">
<?php foreach ( $highlight_tags as $tag ) : ?>
<span class="vc-destaque-tag"><?php echo esc_html( $tag ); ?></span>
<?php endforeach; ?>
<?php foreach ( $cuisine_list as $cuisine ) : ?>
<span class="vc-destaque-tag"><?php echo esc_html( $cuisine ); ?></span>
<?php endforeach; ?>
<?php foreach ( $location_list as $location_name ) : ?>
<span class="vc-destaque-tag"><?php echo esc_html( $location_name ); ?></span>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="vc-tabs">
<button class="vc-tab-btn active" data-tab="vc-aba-cardapio"><?php echo esc_html__( 'Cardápio', 'vemcomer' ); ?></button>
<button class="vc-tab-btn" data-tab="vc-aba-avaliacoes"><?php echo esc_html__( 'Avaliações', 'vemcomer' ); ?><?php if ( ! empty( $rating_data['count'] ) ) : ?> <span class="vc-tab-indicador"><?php echo esc_html( number_format_i18n( (int) $rating_data['count'] ) ); ?></span><?php endif; ?></button>
<button class="vc-tab-btn" data-tab="vc-aba-info"><?php echo esc_html__( 'Informações', 'vemcomer' ); ?></button>
</div>

<div class="vc-aba" id="vc-aba-cardapio">
<div class="vc-busca-categoria">
<input type="search" placeholder="<?php echo esc_attr__( 'Buscar prato ou bebida...', 'vemcomer' ); ?>" aria-label="<?php echo esc_attr__( 'Buscar no cardápio', 'vemcomer' ); ?>" />
<?php if ( $menu_filters ) : ?>
<span class="vc-filtro-alim">
<?php foreach ( $menu_filters as $filter_label ) : ?>
<label><input type="checkbox" /><?php echo esc_html( $filter_label ); ?></label>
<?php endforeach; ?>
</span>
<?php endif; ?>
</div>
<?php if ( $reservation_enabled ) : ?>
<div class="vc-reserva-bloco">
<div>
<div style="font-weight:700;font-size:1.07em;color:var(--primary);margin-bottom:2px;"><?php echo esc_html__( 'Reservar Mesa', 'vemcomer' ); ?></div>
<div style="color:var(--meta);font-size:.97em;">
<?php echo $reservation_notes ? wp_kses_post( $reservation_notes ) : esc_html__( 'Reserve online para aniversário, reunião ou grupos.', 'vemcomer' ); ?>
</div>
</div>
<button class="vc-badge-pill" type="button" id="vc-open-reservation" data-target="#vc-modalReserva" style="border:none; cursor:pointer;">
<?php echo esc_html__( 'Reservar', 'vemcomer' ); ?>
</button>
</div>
<?php endif; ?>
<div class="vc-menu-wrapper" id="vc-menu">
<?php echo do_shortcode( '[vc_menu_items]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
</div>

<div class="vc-aba" id="vc-aba-avaliacoes" style="display:none;">
<div class="vc-aval-filtros">
<button class="vc-filtro-aval active" type="button"><?php echo esc_html__( 'Mais recentes', 'vemcomer' ); ?></button>
<button class="vc-filtro-aval" type="button"><?php echo esc_html__( '5 estrelas', 'vemcomer' ); ?></button>
<button class="vc-filtro-aval" type="button"><?php echo esc_html__( 'Ruins', 'vemcomer' ); ?></button>
<button class="vc-filtro-aval" type="button"><?php echo esc_html__( 'Com resposta', 'vemcomer' ); ?></button>
</div>
<?php echo do_shortcode( '[vc_reviews restaurant_id="' . get_the_ID() . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>

<div class="vc-aba" id="vc-aba-info" style="display:none;">
<div class="vc-infos">
<?php if ( $address ) : ?>
<strong><?php echo esc_html__( 'Endereço:', 'vemcomer' ); ?></strong> <?php echo esc_html( $address ); ?><br />
<?php endif; ?>
<?php if ( $whatsapp ) : ?>
<strong><?php echo esc_html__( 'Contato:', 'vemcomer' ); ?></strong> <?php echo esc_html( $whatsapp ); ?><br />
<?php endif; ?>
<?php if ( $delivery_raw ) : ?>
<strong><?php echo esc_html__( 'Entrega:', 'vemcomer' ); ?></strong> <?php echo esc_html__( 'Delivery e retirada disponíveis', 'vemcomer' ); ?><br />
<?php endif; ?>
<?php if ( $hours_formatted ) : ?>
<strong><?php echo esc_html__( 'Horário de funcionamento:', 'vemcomer' ); ?></strong>
<ul style="padding-left:17px;line-height:1.3;">
<?php foreach ( explode( "\n", $hours_formatted ) as $line ) : ?>
<li><?php echo esc_html( $line ); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php if ( $payment_methods ) : ?>
<strong><?php echo esc_html__( 'Formas de pagamento:', 'vemcomer' ); ?></strong> <?php echo esc_html( implode( ', ', $payment_methods ) ); ?>.<br />
<?php endif; ?>
<?php if ( $instagram_handle ) : ?>
<strong><?php echo esc_html__( 'Instagram:', 'vemcomer' ); ?></strong> <a href="<?php echo esc_url( ( false === strpos( $instagram_handle, 'http' ) ? 'https://instagram.com/' . ltrim( $instagram_handle, '@' ) : $instagram_handle ) ); ?>" target="_blank" rel="noopener">@<?php echo esc_html( ltrim( str_replace( 'https://instagram.com/', '', $instagram_handle ), '@' ) ); ?></a>
<?php endif; ?>
<?php if ( $facilities ) : ?>
<div class="vc-infos-tags">
<?php foreach ( $facilities as $facility ) : ?>
<span class="vc-infos-tag"><?php echo esc_html( $facility ); ?></span>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<?php if ( $has_coordinates ) : ?>
<iframe class="vc-map-iframe" title="<?php echo esc_attr__( 'Mapa', 'vemcomer' ); ?>" loading="lazy" allowfullscreen src="https://www.openstreetmap.org/export/embed.html?bbox=<?php echo esc_attr( $lng - 0.01 ); ?>,<?php echo esc_attr( $lat - 0.01 ); ?>,<?php echo esc_attr( $lng + 0.01 ); ?>,<?php echo esc_attr( $lat + 0.01 ); ?>&layer=mapnik"></iframe>
<?php endif; ?>

<?php if ( $observations ) : ?>
<div class="vc-infos">
<strong><?php echo esc_html__( 'Observações:', 'vemcomer' ); ?></strong> <?php echo wp_kses_post( wpautop( $observations ) ); ?>
</div>
<?php endif; ?>

<?php if ( $faq_items ) : ?>
<div class="vc-infos">
<strong><?php echo esc_html__( 'FAQ:', 'vemcomer' ); ?></strong><br />
<?php foreach ( $faq_items as $faq ) : ?>
<p><b><?php echo esc_html( $faq['question'] ); ?></b> <?php echo esc_html( $faq['answer'] ); ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</main>

<?php if ( $reservation_enabled ) : ?>
<div id="vc-modalReserva">
<div class="vc-modal-wrap">
<button class="close-modal" type="button">×</button>
<div style="font-weight:700;font-size:1.1em;color:var(--primary);margin-bottom:8px;"><?php echo esc_html__( 'Reservar Mesa', 'vemcomer' ); ?></div>
<form id="vc-formReserva">
<input type="text" name="name" required placeholder="<?php echo esc_attr__( 'Nome completo', 'vemcomer' ); ?>" />
<input type="tel" name="phone" required placeholder="<?php echo esc_attr__( 'Celular', 'vemcomer' ); ?>" />
<div style="display:flex; gap:8px;">
<input type="date" name="date" required />
<input type="time" name="time" required />
</div>
<select name="people" required>
<option value=""><?php echo esc_html__( 'Nº de pessoas', 'vemcomer' ); ?></option>
<option>1</option>
<option>2</option>
<option>3</option>
<option>4</option>
<option>5</option>
<option>6+</option>
</select>
<textarea name="note" placeholder="<?php echo esc_attr__( 'Comentário especial (opcional)', 'vemcomer' ); ?>"></textarea>
<button type="submit" class="vc-badge-pill" style="width:100%;margin-top:5px;border:none;cursor:pointer;">
<?php echo esc_html__( 'Confirmar Reserva', 'vemcomer' ); ?>
</button>
</form>
<div id="vc-reservaOk"><?php echo esc_html__( 'Reserva enviada! Você será redirecionado.', 'vemcomer' ); ?></div>
</div>
</div>
<?php endif; ?>

<nav class="vc-bottom-nav" aria-label="<?php echo esc_attr__( 'Navegação inferior', 'vemcomer' ); ?>">
<div class="vc-bottom-nav__inner">
<a href="<?php echo esc_url( home_url( '/?mode=app' ) ); ?>">
<svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
<span><?php echo esc_html__( 'Início', 'vemcomer' ); ?></span>
</a>
<a href="<?php echo esc_url( home_url( '/busca/' ) ); ?>">
<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
<span><?php echo esc_html__( 'Buscar', 'vemcomer' ); ?></span>
</a>
<a href="<?php echo esc_url( home_url( '/categorias/' ) ); ?>">
<svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zM7 11h5v5H7z"/></svg>
<span><?php echo esc_html__( 'Categorias', 'vemcomer' ); ?></span>
</a>
<a href="<?php echo esc_url( home_url( '/meus-pedidos/' ) ); ?>">
<svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
<span><?php echo esc_html__( 'Pedidos', 'vemcomer' ); ?></span>
</a>
<a href="<?php echo esc_url( home_url( '/minha-conta/' ) ); ?>">
<svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
<span><?php echo esc_html__( 'Perfil', 'vemcomer' ); ?></span>
</a>
</div>
</nav>

<?php if ( $reservation_enabled ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
const tabs = document.querySelectorAll('.vc-tab-btn');
const sections = {
'vc-aba-cardapio': document.getElementById('vc-aba-cardapio'),
'vc-aba-avaliacoes': document.getElementById('vc-aba-avaliacoes'),
'vc-aba-info': document.getElementById('vc-aba-info')
};

tabs.forEach((tab) => {
tab.addEventListener('click', () => {
tabs.forEach((t) => t.classList.remove('active'));
tab.classList.add('active');
Object.keys(sections).forEach((key) => {
if ( sections[key] ) {
sections[key].style.display = tab.dataset.tab === key ? '' : 'none';
}
});
window.scrollTo({ top: 160, behavior: 'smooth' });
});
});

const modal = document.getElementById('vc-modalReserva');
const trigger = document.getElementById('vc-open-reservation');
const closeBtn = modal ? modal.querySelector('.close-modal') : null;
const form = document.getElementById('vc-formReserva');
const feedback = document.getElementById('vc-reservaOk');
const reservationLink = <?php echo wp_json_encode( $reservation_link ); ?>;
const reservationPhone = <?php echo wp_json_encode( preg_replace( '/\D+/', '', (string) $reservation_phone ) ); ?>;

const closeModal = () => { if ( modal ) { modal.style.display = 'none'; } };

if ( trigger && modal ) {
trigger.addEventListener('click', () => { modal.style.display = 'flex'; if ( feedback ) { feedback.style.display = 'none'; } });
}
if ( closeBtn ) { closeBtn.addEventListener('click', closeModal); }
if ( form ) {
form.addEventListener('submit', function(event){
event.preventDefault();
const formData = new FormData(form);
const message = [
'<?php echo esc_js( __( 'Reserva solicitada:', 'vemcomer' ) ); ?>',
`Nome: ${ formData.get('name') || '' }`,
`Celular: ${ formData.get('phone') || '' }`,
`Data: ${ formData.get('date') || '' }`,
`Hora: ${ formData.get('time') || '' }`,
`Pessoas: ${ formData.get('people') || '' }`,
formData.get('note') ? `Observações: ${ formData.get('note') }` : ''
].filter(Boolean).join('\n');

if ( reservationLink ) {
window.open(reservationLink, '_blank');
} else if ( reservationPhone ) {
const waUrl = `https://wa.me/${ reservationPhone }?text=${ encodeURIComponent(message) }`;
window.open(waUrl, '_blank');
}

if ( feedback ) { feedback.style.display = 'block'; }
setTimeout(() => { closeModal(); form.reset(); if ( feedback ) { feedback.style.display = 'none'; } }, 1800);
});
}
});
</script>
<?php else : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
const tabs = document.querySelectorAll('.vc-tab-btn');
const sections = {
'vc-aba-cardapio': document.getElementById('vc-aba-cardapio'),
'vc-aba-avaliacoes': document.getElementById('vc-aba-avaliacoes'),
'vc-aba-info': document.getElementById('vc-aba-info')
};
tabs.forEach((tab) => {
tab.addEventListener('click', () => {
tabs.forEach((t) => t.classList.remove('active'));
tab.classList.add('active');
Object.keys(sections).forEach((key) => {
if ( sections[key] ) {
sections[key].style.display = tab.dataset.tab === key ? '' : 'none';
}
});
window.scrollTo({ top: 160, behavior: 'smooth' });
});
});
});
</script>
<?php endif; ?>
<?php
endwhile;
get_footer();
