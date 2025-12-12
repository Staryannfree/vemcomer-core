<?php
/**
 * Onboarding Step 6: Adicionais (Opcional)
 * 
 * @var array $wizard_data
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wizard-title">Quer oferecer adicionais?</div>
<div class="wizard-subtitle">Adicionais como queijos extras, bebidas do combo e molhos podem aumentar o valor de cada pedido.</div>
<div id="wizardRecommendedAddons" style="margin-top:24px;">
    <div style="text-align:center;padding:40px;color:#999;">Carregando grupos recomendados...</div>
</div>
<div style="margin-top:24px;text-align:center;">
    <button onclick="vcSkipAddons()" style="background:transparent;color:#6b7672;border:2px solid #e0e0e0;padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;">Pular por enquanto</button>
</div>

