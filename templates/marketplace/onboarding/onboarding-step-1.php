<?php
/**
 * Onboarding Step 1: Tipo de Restaurante
 * 
 * @var array $cuisine_options_primary
 * @var array $cuisine_options_tags
 * @var array $wizard_data
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wizard-title">Bem-vindo ao PedeVem!</div>
<div class="wizard-subtitle">Vamos colocar sua loja no ar em poucos minutos. Primeiro, diga que tipo de negócio você tem.</div>
<div id="wizardStep1Warning" style="display:none;background:#fffbe2;padding:12px;border-radius:8px;margin-bottom:24px;font-size:14px;color:#856404;border-left:4px solid #facb32;">
    <strong>⚠️ Importante:</strong> Escolha pelo menos 1 tipo de cozinha principal (ex.: Hamburgueria, Pizzaria, Japonesa) para receber recomendações de categorias de cardápio.
</div>
<div style="margin-top:24px;">
    <div style="font-weight:700;font-size:14px;color:#2d8659;margin-bottom:16px;">Tipo de cozinha principal *</div>
    <div id="wizardPrimaryCuisines" style="display:flex;flex-wrap:wrap;gap:10px;">
        <?php foreach ( $cuisine_options_primary as $cuisine ) : ?>
            <div class="cuisine-option" data-id="<?php echo esc_attr( $cuisine['id'] ); ?>" data-is-primary="1" onclick="vcToggleCuisine(<?php echo esc_js( $cuisine['id'] ); ?>)">
                <?php echo esc_html( $cuisine['name'] ); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php if ( ! empty( $cuisine_options_tags ) ) : ?>
    <div style="margin-top:32px;padding-top:24px;border-top:2px solid #e0e0e0;">
        <div style="font-weight:700;font-size:14px;color:#6b7672;margin-bottom:16px;">Estilo e formato (opcional)</div>
        <div id="wizardTagsCuisines" style="display:flex;flex-wrap:wrap;gap:10px;">
            <?php foreach ( $cuisine_options_tags as $cuisine ) : ?>
                <div class="cuisine-option" data-id="<?php echo esc_attr( $cuisine['id'] ); ?>" data-is-primary="0" onclick="vcToggleCuisine(<?php echo esc_js( $cuisine['id'] ); ?>)">
                    <?php echo esc_html( $cuisine['name'] ); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
<div style="margin-top:16px;font-size:13px;color:#6b7672;">Você pode selecionar até 3 tipos de restaurante.</div>

