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

    <div class="dash-title">Visão Geral da Loja</div>

    <div class="dash-row">
        <div class="dash-card">
            <div class="card-label">Vendas Hoje</div>
            <div class="card-value">R$ 325,00</div>
            <div class="card-sub">8 pedidos</div>
        </div>
        <div class="dash-card">
            <div class="card-label">Na Semana</div>
            <div class="card-value">R$ 2.112,40</div>
            <div class="card-sub">41 pedidos</div>
        </div>
        <div class="dash-card">
            <div class="card-label">No Mês</div>
            <div class="card-value">R$ 6.871,30</div>
            <div class="card-sub">131 pedidos</div>
        </div>
    </div>

    <div class="dash-row">
        <div class="dash-card">
            <div class="card-label">Status da Loja</div>
            <div class="card-status st-aberto" id="lojaStatusTxt">ABERTO</div>
            <button class="card-btn toggle" onclick="toggleLojaStatus()">Fechar Agora</button>
        </div>
        <div class="dash-card" style="flex:2;">
            <div class="card-label">Limite de Itens do Plano</div>
            <div class="card-bar">
                <div class="card-bar-inner" id="planoBar" style="width:70%"></div>
                <span class="bar-text">14/20 (70%)</span>
            </div>
            <div class="plan-info" id="planoInfo">
                Você utiliza o plano <span class="plan-type">Vitrine</span>.
                <a href="#" style="color:#2d8659;font-weight:700;text-decoration:underline;">Upgradar para PRO</a>
            </div>
        </div>
    </div>

    <div class="analytics-wrap">
        <div class="analytics-title">Analytics Express</div>
        <div class="analytics-row">
            <div class="analytics-metric">
                <div class="metric-main" style="color:#3176da;">725</div>
                <div class="metric-label">Visualizações</div>
            </div>
            <div class="analytics-metric">
                <div class="metric-main">82</div>
                <div class="metric-label">Cliques no WhatsApp</div>
            </div>
            <div class="analytics-metric">
                <div class="metric-main" style="color:#ea5252;">11,3%</div>
                <div class="metric-label">Conversão</div>
                <div class="metric-sub">Whats/Visualizações</div>
            </div>
        </div>
    </div>

    <div class="plan-widget">
        <div class="plan-type">Plano Atual: <b>Vitrine</b></div>
        <div class="plan-bar">
            <div class="plan-bar-inner" style="width:70%;"></div>
            <span class="bar-text">14/20 itens</span>
        </div>
        <div class="plan-info">Seu plano permite até 20 itens no cardápio.<br>
            Atualize para <b>PRO</b> e desbloqueie funcionalidades avançadas!</div>
        <button class="card-btn" style="background:#facb32;color:#232a2c;font-weight:800;margin-top:7px;">Ver Planos</button>
    </div>
</div>
<script>
    let aberto = true;
    function toggleLojaStatus() {
        aberto = !aberto;
        document.getElementById('lojaStatusTxt').textContent = aberto ? 'ABERTO' : 'FECHADO';
        document.getElementById('lojaStatusTxt').className = aberto ? 'card-status st-aberto' : 'card-status st-fechado';
        document.querySelector('.card-btn.toggle').textContent = aberto ? 'Fechar Agora' : 'Abrir Agora';
    }
</script>
<?php
$current_user = wp_get_current_user();

if ( in_array( 'lojista', (array) $current_user->roles, true ) ) {
    if ( ! defined( 'VC_WIZARD_INLINE' ) ) {
        define( 'VC_WIZARD_INLINE', true );
    }

    locate_template( 'templates/marketplace/wizard-onboarding.php', true, false );
}

if (! $vc_marketplace_inline) {
    get_footer();
}
