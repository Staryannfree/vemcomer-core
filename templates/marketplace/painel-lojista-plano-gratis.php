<?php
/**
 * Template Name: Marketplace - Painel Lojista Plano Gratis
 * Description: Versão dinâmica do layout estatico templates/marketplace/painel-lojista-plano-gratis.html.
 */

if (!defined('ABSPATH')) {
    exit;
}

$vc_marketplace_inline = defined('VC_MARKETPLACE_INLINE') && VC_MARKETPLACE_INLINE;

wp_enqueue_style(
    'vc-marketplace-dashboard-font',
    'https://fonts.googleapis.com/css?family=Montserrat:700,500&display=swap',
    [],
    null
);

if (! $vc_marketplace_inline) {
    get_header();
}
?>
<div class="dash-container">
    <style>
        body { background: #f6f9f6; font-family: 'Montserrat', Arial, sans-serif; margin: 0; color: #232a2c; }
        .dash-container { max-width: 474px; margin: 0 auto; padding: 17px 10px 38px 10px; }
        .dash-quick { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 18px; }
        .dash-quick a,
        .dash-quick button { background:#ffffff; border:1px solid #e2e8e4; border-radius:12px; padding:11px 12px; font-weight:700; color:#2d8659; text-decoration:none; text-align:left; box-shadow:0 2px 12px #2d865910; cursor:pointer; transition:.14s; font-size:.98em; }
        .dash-quick a:hover,
        .dash-quick button:hover { box-shadow:0 3px 18px #2d865915; transform:translateY(-1px); }
        .dash-quick a:active,
        .dash-quick button:active { transform:translateY(0); box-shadow:0 1px 8px #2d86590d; }
        .dash-quick .dash-cta-primary { background:#2d8659; color:#ffffff; border-color:#2d8659; text-align:center; }
        .dash-title { font-size: 1.18em; font-weight: 700; color: #2d8659; margin-bottom: 18px; }
        .dash-row { display: flex; flex-wrap: wrap; gap: 18px; margin-bottom: 19px; }
        .dash-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 18px #2d865910; padding: 15px 15px 13px 15px; min-width: 0; flex: 1 1 137px; display: flex; flex-direction: column; align-items: flex-start; }
        .card-label { color: #6b7672; font-size: .98em; margin-bottom: 5px; }
        .card-value { font-size: 1.33em; font-weight: 700; color: #2d8659; }
        .card-sub { font-size: 1em; color: #b5c6ae; margin-top: 2px; }
        .card-btn { background: #2d8659; color: #fff; border: none; border-radius: 8px; font-size: .97em; padding: 7px 13px; cursor: pointer; font-weight: 700; margin-top: 8px; }
        .card-btn.toggle { background: #facb32; color: #232a2c; }
        .card-status { margin-top: 8px; font-weight: 700; font-size: .99em; }
        .st-aberto { color: #2d8659; }
        .st-fechado { color: #ea5252; }
        .card-bar { background: #eaf8f1; height: 10px; width: 100%; border-radius: 9px; margin: 8px 0 1px 0; position: relative; }
        .card-bar-inner { height: 100%; background: #2d8659; border-radius: 9px; transition: width .21s; }
        .bar-text { font-size: .96em; color: #333; position: absolute; top: 10px; left: 2px; }
        .analytics-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 18px #2d865916; padding: 14px 12px 10px 12px; margin-bottom: 19px; }
        .analytics-title { color: #6b7672; font-weight: 700; font-size: 1.03em; margin-bottom: 7px; }
        .analytics-row { display: flex; gap: 18px; }
        .analytics-metric { flex: 1; text-align: center; }
        .metric-main { font-size: 1.22em; font-weight: 700; color: #2d8659; }
        .metric-label { color: #6b7672; font-size: .95em; }
        .metric-sub { font-size: .97em; color: #3176da; margin-top: 2px; }
        .plan-widget { background: #fff; border-radius: 13px; box-shadow: 0 2px 18px #2d86591b; padding: 15px 14px 10px 14px; }
        .plan-type { font-weight: 700; color: #2d8659; font-size: 1em; margin-bottom: 2px; }
        .plan-bar { background: #ffeecd; height: 9px; width: 100%; border-radius: 6px; margin: 7px 0 1px 0; position: relative; }
        .plan-bar-inner { height: 100%; background: #facb32; border-radius: 6px; transition: width .18s; }
        .plan-info { font-size: .98em; color: #777; margin-top: 3px; }
        @media (max-width: 490px) { .dash-row { flex-direction: column; gap: 8px; } }
    </style>

<?php
$current_user = wp_get_current_user();
$restaurant   = null;
$public_url   = home_url( '/restaurant/' );

$stats_defaults = [
    'today'  => [ 'revenue' => 'R$ 325,00', 'orders' => 8 ],
    'week'   => [ 'revenue' => 'R$ 2.112,40', 'orders' => 41 ],
    'month'  => [ 'revenue' => 'R$ 6.871,30', 'orders' => 131 ],
];

$analytics_defaults = [
    'views'      => 725,
    'whatsapp'   => 82,
    'conversion' => '11,3%',
];

$plan_name  = 'Vitrine';
$plan_limit = 20;
$plan_used  = 14;

$store_status = [
    'is_open' => true,
    'label'   => 'ABERTO',
];

$quick_links = [
    'onboarding' => home_url( '/wizard-onboarding/' ),
    'config'     => home_url( '/configuracao-loja/' ),
    'menu'       => home_url( '/gestao-cardapio/' ),
    'marketing'  => home_url( '/central-marketing/' ),
    'events'     => home_url( '/gestor-eventos/' ),
];

if ( $current_user instanceof WP_User && $current_user->ID ) {
    $filtered = (int) apply_filters( 'vemcomer/restaurant_id_for_user', 0, $current_user );
    if ( $filtered > 0 ) {
        $candidate = get_post( $filtered );
        if ( $candidate instanceof WP_Post && 'vc_restaurant' === $candidate->post_type ) {
            $restaurant = $candidate;
        }
    }

    if ( ! $restaurant ) {
        $meta_id = (int) get_user_meta( $current_user->ID, 'vc_restaurant_id', true );
        if ( $meta_id ) {
            $candidate = get_post( $meta_id );
            if ( $candidate instanceof WP_Post && 'vc_restaurant' === $candidate->post_type ) {
                $restaurant = $candidate;
            }
        }
    }

    if ( ! $restaurant ) {
        $q = new WP_Query([
            'post_type'      => 'vc_restaurant',
            'author'         => $current_user->ID,
            'posts_per_page' => 1,
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'no_found_rows'  => true,
        ]);

        if ( $q->have_posts() ) {
            $restaurant = $q->posts[0];
        }

        wp_reset_postdata();
    }

    if ( $restaurant ) {
        $public_url = get_permalink( $restaurant );

        $stats_defaults = apply_filters( 'vemcomer/dashboard_stats', $stats_defaults, $restaurant );
        $analytics_defaults = apply_filters( 'vemcomer/dashboard_analytics', $analytics_defaults, $restaurant );

        $plan_name  = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['plan_name'], true ) ?: $plan_name;
        $plan_limit = (int) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['plan_limit'], true ) ?: $plan_limit;
        $plan_used  = (int) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['plan_used'], true ) ?: $plan_used;

        $store_status = apply_filters( 'vemcomer/dashboard_store_status', $store_status, $restaurant );
    }
}

$plan_limit = max( 0, $plan_limit );
$plan_used  = max( 0, $plan_used );
$plan_percent = $plan_limit > 0 ? min( 100, round( ( $plan_used / $plan_limit ) * 100 ) ) : 0;
$plan_bar_label = $plan_limit > 0 ? sprintf( '%d/%d (%d%%)', $plan_used, $plan_limit, $plan_percent ) : sprintf( '%d itens', $plan_used );
$plan_items_label = $plan_limit > 0 ? sprintf( '%d/%d itens', $plan_used, $plan_limit ) : sprintf( '%d itens', $plan_used );

$is_open = ! empty( $store_status['is_open'] );
$status_label = ! empty( $store_status['label'] ) ? $store_status['label'] : ( $is_open ? 'ABERTO' : 'FECHADO' );
?>

    <div class="dash-quick">
        <button type="button" class="dash-cta-primary" onclick="vcOpenOnboardingWizard()">⚡ Configuração Rápida</button>
        <a href="<?php echo esc_url( $quick_links['config'] ); ?>">Editar dados</a>
        <a href="<?php echo esc_url( $quick_links['menu'] ); ?>">Gerenciar cardápio</a>
        <a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener">Ver página pública</a>
        <a href="<?php echo esc_url( $quick_links['marketing'] ); ?>">Marketing</a>
        <a href="<?php echo esc_url( $quick_links['events'] ); ?>">Eventos</a>
    </div>

    <div class="dash-title">Visão Geral da Loja</div>

    <div class="dash-row">
        <div class="dash-card">
            <div class="card-label">Vendas Hoje</div>
            <div class="card-value"><?php echo esc_html( $stats_defaults['today']['revenue'] ); ?></div>
            <div class="card-sub"><?php echo esc_html( $stats_defaults['today']['orders'] ); ?> pedidos</div>
        </div>
        <div class="dash-card">
            <div class="card-label">Na Semana</div>
            <div class="card-value"><?php echo esc_html( $stats_defaults['week']['revenue'] ); ?></div>
            <div class="card-sub"><?php echo esc_html( $stats_defaults['week']['orders'] ); ?> pedidos</div>
        </div>
        <div class="dash-card">
            <div class="card-label">No Mês</div>
            <div class="card-value"><?php echo esc_html( $stats_defaults['month']['revenue'] ); ?></div>
            <div class="card-sub"><?php echo esc_html( $stats_defaults['month']['orders'] ); ?> pedidos</div>
        </div>
    </div>

    <div class="dash-row">
        <div class="dash-card">
            <div class="card-label">Status da Loja</div>
            <div class="card-status <?php echo $is_open ? 'st-aberto' : 'st-fechado'; ?>" id="lojaStatusTxt"><?php echo esc_html( $status_label ); ?></div>
            <button class="card-btn toggle" onclick="toggleLojaStatus()" id="lojaStatusBtn"><?php echo $is_open ? 'Fechar Agora' : 'Abrir Agora'; ?></button>
        </div>
        <div class="dash-card" style="flex:2;">
            <div class="card-label">Limite de Itens do Plano</div>
            <div class="card-bar">
                <div class="card-bar-inner" id="planoBar" style="width:<?php echo esc_attr( $plan_percent ); ?>%"></div>
                <span class="bar-text"><?php echo esc_html( $plan_bar_label ); ?></span>
            </div>
            <div class="plan-info" id="planoInfo">
                Você utiliza o plano <span class="plan-type"><?php echo esc_html( $plan_name ); ?></span>.
                <a href="#" style="color:#2d8659;font-weight:700;text-decoration:underline;">Upgradar para PRO</a>
            </div>
        </div>
    </div>

    <div class="analytics-wrap">
        <div class="analytics-title">Analytics Express</div>
        <div class="analytics-row">
            <div class="analytics-metric">
                <div class="metric-main" style="color:#3176da;"><?php echo esc_html( $analytics_defaults['views'] ); ?></div>
                <div class="metric-label">Visualizações</div>
            </div>
            <div class="analytics-metric">
                <div class="metric-main"><?php echo esc_html( $analytics_defaults['whatsapp'] ); ?></div>
                <div class="metric-label">Cliques no WhatsApp</div>
            </div>
            <div class="analytics-metric">
                <div class="metric-main" style="color:#ea5252;"><?php echo esc_html( $analytics_defaults['conversion'] ); ?></div>
                <div class="metric-label">Conversão</div>
                <div class="metric-sub">Whats/Visualizações</div>
            </div>
        </div>
    </div>

    <div class="plan-widget">
        <div class="plan-type">Plano Atual: <b><?php echo esc_html( $plan_name ); ?></b></div>
        <div class="plan-bar">
            <div class="plan-bar-inner" style="width:<?php echo esc_attr( $plan_percent ); ?>%;"></div>
            <span class="bar-text"><?php echo esc_html( $plan_items_label ); ?></span>
        </div>
        <div class="plan-info">Seu plano permite até <?php echo esc_html( $plan_limit ); ?> itens no cardápio.<br>
            Atualize para <b>PRO</b> e desbloqueie funcionalidades avançadas!</div>
        <button class="card-btn" style="background:#facb32;color:#232a2c;font-weight:800;margin-top:7px;">Ver Planos</button>
    </div>
</div>
<script>
    let aberto = <?php echo $is_open ? 'true' : 'false'; ?>;
    function toggleLojaStatus() {
        aberto = !aberto;
        var statusTxt = document.getElementById('lojaStatusTxt');
        var statusBtn = document.getElementById('lojaStatusBtn');
        if (!statusTxt || !statusBtn) return;
        statusTxt.textContent = aberto ? 'ABERTO' : 'FECHADO';
        statusTxt.className = aberto ? 'card-status st-aberto' : 'card-status st-fechado';
        statusBtn.textContent = aberto ? 'Fechar Agora' : 'Abrir Agora';
    }

    function vcOpenOnboardingWizard() {
        if (typeof window.abrirOnboarding === 'function') {
            window.abrirOnboarding();
            return;
        }

        var modal = document.getElementById('onboardModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.classList.add('onboard-locked');
            return;
        }

        console.warn('Onboarding modal não disponível nesta página.');
    }
</script>
<?php
$current_user = wp_get_current_user();

if ( ! defined( 'VC_WIZARD_INLINE' ) ) {
    define( 'VC_WIZARD_INLINE', true );
}

$wizard_path = VEMCOMER_CORE_DIR . 'templates/marketplace/wizard-onboarding.php';

if ( file_exists( $wizard_path ) ) {
    include $wizard_path;
}

if (! $vc_marketplace_inline) {
    get_footer();
}
