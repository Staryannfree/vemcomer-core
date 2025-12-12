<?php
/**
 * Onboarding Step 3: Endereço e Horários
 * 
 * @var array $wizard_data
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$address = $wizard_data['address'] ?? '';
$neighborhood = $wizard_data['neighborhood'] ?? '';
$city = $wizard_data['city'] ?? '';
$zipcode = $wizard_data['zipcode'] ?? '';
$delivery = $wizard_data['delivery'] ?? true;
$pickup = $wizard_data['pickup'] ?? false;
$schedule = $wizard_data['schedule'] ?? [];

$days = [
    [ 'key' => 'seg', 'name' => 'Segunda-feira' ],
    [ 'key' => 'ter', 'name' => 'Terça-feira' ],
    [ 'key' => 'qua', 'name' => 'Quarta-feira' ],
    [ 'key' => 'qui', 'name' => 'Quinta-feira' ],
    [ 'key' => 'sex', 'name' => 'Sexta-feira' ],
    [ 'key' => 'sab', 'name' => 'Sábado' ],
    [ 'key' => 'dom', 'name' => 'Domingo' ],
];
?>
<div class="wizard-title">Endereço e horários</div>
<div class="wizard-subtitle">Precisamos saber onde sua loja fica e quando ela está aberta para aceitar pedidos.</div>
<div style="background:#fffbe2;padding:12px;border-radius:8px;margin-bottom:24px;font-size:14px;color:#856404;border-left:4px solid #facb32;">
    <strong>⚠️ Importante:</strong> Se você marcar apenas "Entrega própria" e não marcar "Apenas retirada no local", sua loja <strong>não aparecerá</strong> para os clientes no marketplace. Para aparecer, você precisa marcar pelo menos "Apenas retirada no local" ou ambos os métodos.
</div>

<h3 style="font-size:18px;font-weight:700;margin:24px 0 12px 0;color:#2d8659;">Endereço</h3>
<div style="margin-bottom:16px;">
    <div style="background:#eaf8f1;padding:10px;border-radius:8px;margin-bottom:12px;font-size:13px;color:#2d8659;">
        <strong>💡 Dica:</strong> Clique no mapa para selecionar sua localização ou use o botão abaixo para detectar automaticamente.
    </div>
    <div id="vcWizardMap" style="width:100%;height:300px;min-height:300px;border-radius:8px;border:2px solid #eaf8f1;margin-bottom:12px;position:relative;background:#f5f5f5;display:flex;align-items:center;justify-content:center;">
        <div id="vcWizardMapLoading" style="color:#6b7672;font-size:14px;">Carregando mapa...</div>
    </div>
    <button type="button" id="vcWizardUseLocation" style="background:#2d8659;color:#fff;border:none;padding:12px 20px;border-radius:8px;font-weight:600;cursor:pointer;width:100%;font-size:15px;touch-action:manipulation;">📍 Usar minha localização</button>
    <div id="vcWizardMapError" style="display:none;background:#fffbe2;border-left:4px solid #facb32;color:#856404;padding:12px;border-radius:8px;margin-top:12px;font-size:14px;line-height:1.5;">
        <strong>⚠️</strong> <span id="vcWizardMapErrorText"></span>
    </div>
    <div id="vcWizardMapSuccess" style="display:none;background:#d1fae5;border-left:4px solid #2d8659;color:#065f46;padding:12px;border-radius:8px;margin-top:12px;font-size:14px;">
        <strong>✓</strong> Localização selecionada! Os campos abaixo foram preenchidos automaticamente.
    </div>
</div>
<input type="hidden" id="wizardLat" value="">
<input type="hidden" id="wizardLng" value="">
<input type="text" id="wizardAddress" value="<?php echo esc_attr( $address ); ?>" placeholder="Endereço completo" required>
<input type="text" id="wizardNeighborhood" value="<?php echo esc_attr( $neighborhood ); ?>" placeholder="Bairro">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;">
    <input type="text" id="wizardCity" value="<?php echo esc_attr( $city ); ?>" placeholder="Cidade">
    <input type="text" id="wizardZipcode" value="<?php echo esc_attr( $zipcode ); ?>" placeholder="CEP">
</div>

<h3 style="font-size:18px;font-weight:700;margin:24px 0 12px 0;color:#2d8659;">Método de Atendimento</h3>
<label style="display:flex;align-items:center;padding:12px;background:#f9f9f9;border-radius:8px;margin-bottom:8px;cursor:pointer;">
    <input type="checkbox" id="wizardDelivery" <?php checked( $delivery, true ); ?> style="width:20px;height:20px;margin-right:12px;">
    <span style="font-weight:600;">Entrega própria</span>
</label>
<label style="display:flex;align-items:center;padding:12px;background:#f9f9f9;border-radius:8px;margin-bottom:8px;cursor:pointer;">
    <input type="checkbox" id="wizardPickup" <?php checked( $pickup, true ); ?> style="width:20px;height:20px;margin-right:12px;">
    <span style="font-weight:600;">Apenas retirada no local</span>
</label>

<h3 style="font-size:18px;font-weight:700;margin:24px 0 12px 0;color:#2d8659;">Horários de Funcionamento</h3>
<div style="margin-bottom:12px;">
    <button type="button" onclick="vcCopyScheduleToAll()" style="background:#facb32;color:#232a2c;border:none;padding:8px 16px;border-radius:6px;font-weight:600;cursor:pointer;margin-bottom:12px;">Copiar para todos os dias</button>
</div>
<?php foreach ( $days as $day ) : 
    $day_data = $schedule[ $day['key'] ] ?? [ 'enabled' => false, 'ranges' => [ [ 'open' => '09:00', 'close' => '18:00' ] ] ];
    $enabled = ! empty( $day_data['enabled'] );
    $open = $day_data['ranges'][0]['open'] ?? '09:00';
    $close = $day_data['ranges'][0]['close'] ?? '18:00';
?>
    <div class="schedule-day">
        <input type="checkbox" id="schedule_<?php echo esc_attr( $day['key'] ); ?>" <?php checked( $enabled, true ); ?> onchange="vcToggleScheduleDay('<?php echo esc_js( $day['key'] ); ?>')">
        <label for="schedule_<?php echo esc_attr( $day['key'] ); ?>" style="flex:1;font-weight:600;"><?php echo esc_html( $day['name'] ); ?></label>
        <input type="time" id="schedule_<?php echo esc_attr( $day['key'] ); ?>_open" value="<?php echo esc_attr( $open ); ?>" onchange="vcUpdateSchedule('<?php echo esc_js( $day['key'] ); ?>')" <?php disabled( ! $enabled ); ?>>
        <span>até</span>
        <input type="time" id="schedule_<?php echo esc_attr( $day['key'] ); ?>_close" value="<?php echo esc_attr( $close ); ?>" onchange="vcUpdateSchedule('<?php echo esc_js( $day['key'] ); ?>')" <?php disabled( ! $enabled ); ?>>
    </div>
<?php endforeach; ?>

