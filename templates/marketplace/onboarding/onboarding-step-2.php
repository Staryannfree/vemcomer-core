<?php
/**
 * Onboarding Step 2: Dados Básicos
 * 
 * @var array $wizard_data
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$name = $wizard_data['name'] ?? '';
$whatsapp = $wizard_data['whatsapp'] ?? '';
$logo = $wizard_data['logo'] ?? '';
?>
<div class="wizard-title">Dados básicos da sua loja</div>
<div class="wizard-subtitle">Essas informações aparecem para os clientes. Você pode alterar depois quando quiser.</div>

<label style="display:block;font-weight:700;margin-bottom:8px;color:#232a2c;">Nome da loja *</label>
<input type="text" id="wizardName" value="<?php echo esc_attr( $name ); ?>" placeholder="Ex: Hamburgueria do João" required>

<label style="display:block;font-weight:700;margin-bottom:8px;color:#232a2c;">Telefone / WhatsApp *</label>
<input type="tel" id="wizardWhatsapp" value="<?php echo esc_attr( $whatsapp ); ?>" placeholder="(00) 00000-0000" required>

<label style="display:block;font-weight:700;margin-bottom:8px;color:#232a2c;">Logo (opcional)</label>
<div style="margin-bottom:16px;">
    <input type="file" id="wizardLogo" accept="image/*" onchange="vcHandleLogoUpload(event)" style="margin-bottom:8px;">
    <div id="wizardLogoPreview" style="margin-top:12px;">
        <?php if ( ! empty( $logo ) ) : ?>
            <img src="<?php echo esc_url( $logo ); ?>" style="max-width:200px;max-height:200px;border-radius:8px;margin-top:8px;">
        <?php endif; ?>
    </div>
</div>

