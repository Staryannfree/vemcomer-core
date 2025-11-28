<?php
/**
 * Template Name: Marketplace - Wizard Onboarding
 * Description: Versão dinâmica do layout estático templates/marketplace/wizard-onboarding.html.
 */

$vc_wizard_inline = defined('VC_WIZARD_INLINE') && VC_WIZARD_INLINE;

if (!defined('ABSPATH')) {
    exit;
}

$vc_marketplace_inline = defined('VC_MARKETPLACE_INLINE') && VC_MARKETPLACE_INLINE;

wp_enqueue_style(
    'vc-marketplace-wizard-font',
    'https://fonts.googleapis.com/css?family=Montserrat:700,500&display=swap',
    [],
    null
);

if (!$vc_wizard_inline && ! $vc_marketplace_inline) {
    get_header();
}
?>
<div id="onboardModal">
    <div class="onboard-wrap">
        <button class="onboard-close" onclick="fecharOnboarding()">×</button>
        <div class="onboard-content">
            <div class="onboard-step" id="onStep"></div>
            <div class="onboard-bar"><div class="onboard-bar-inner" id="onBar"></div></div>
            <div id="onMain"></div>
        </div>
        <div class="onboard-actions">
            <button class="onboard-btn" id="onBtnPrev" onclick="onPrev()" disabled>Anterior</button>
            <button class="onboard-btn" id="onBtnNext" onclick="onNext()">Avançar</button>
        </div>
    </div>
</div>

<style>
    body { background: #f5faf7; font-family: 'Montserrat', Arial, sans-serif; color: #232a2c; margin: 0; }
    #onboardModal { display:flex; align-items:center; justify-content:center; position:fixed; top:0;left:0;width:100vw;height:100vh;background:#0007;z-index:9999; }
    .onboard-wrap { background:#fff; border-radius:18px; max-width:418px; width:97vw; box-shadow:0 12px 54px #2d865929; padding:0 0 24px 0; position:relative; }
    .onboard-close {position:absolute;right:14px;top:15px;background:none;border:none;font-size:1.34em;color:#bbb;cursor:pointer;}
    .onboard-content {padding:28px 22px 0 22px;}
    .onboard-step {margin-bottom:8px;font-weight:700;color:#2d8659;font-size:1em;}
    .onboard-title {font-size:1.15em;font-weight:700;color:#232a2c;margin-bottom:11px;margin-top:5px;}
    .onboard-desc {color:#444;margin-bottom:19px; font-size:.99em;}
    .onboard-bar {background:#eaf8f1;height:8px;border-radius:5px;width:100%;margin-top:14px;margin-bottom:13px;}
    .onboard-bar-inner {height:100%;background:#2d8659;border-radius:5px;transition:width .3s;}
    .onboard-actions {margin-top:21px;display:flex;gap:10px;}
    .onboard-btn {background:#2d8659;color:#fff;padding:11px 0;flex:1;border:none;border-radius:8px;font-weight:700;font-size:1em;cursor:pointer;transition:background .14s;}
    .onboard-btn:disabled {background:#aee1cf;color:#ffffff;}
    .onboard-hl {color:#45c676;}
    .onboard-input, .onboard-select {width:100%;padding:8px 12px;margin-bottom:13px;border-radius:7px;border:1.1px solid #cdf9e0;font-size:.98em;font-family:inherit;}
    .onboard-help {font-size:.94em; color:#6b7672; background:#eaf8f1;border-radius:7px;padding:7px 11px;margin-bottom:11px;}
    .onboard-error {background:#ffd7d7;color:#ea5252;font-size:.98em;border-radius:7px;padding:7px 12px;margin-bottom:11px;}
    .perfil-foto-box{display:flex;align-items:center;gap:13px;margin-bottom:15px;}
    .perfil-foto-preview{width:69px;height:69px;border-radius:50%;object-fit:cover;background:#eaf8f1;border:2.5px solid #b7eacd;}
    .perfil-foto-upload label{background:#eaf8f1;color:#2d8659;font-weight:700;padding:7px 13px;border-radius:8px;cursor:pointer;font-size:.97em;}
    .perfil-foto-upload input{display:none;}
    .on-togglegroup {display:flex;gap:10px;margin-bottom:11px;}
    .on-togglegroup label {flex:1;display:flex;align-items:center;justify-content:center;padding:7px 2px;background:#eaf8f1;color:#2d8659;border-radius:7px;cursor:pointer;font-weight:600;border:2px solid #eaf8f1;transition:.14s;font-size:.99em;}
    .on-togglegroup input { display:none;}
    .on-togglegroup input:checked+span,.on-checkbox.active  {background:#2d8659!important;color:#fff!important;font-weight:700!important;border:2px solid #2d8659!important;}
    .on-checkbox-list {display:flex;flex-wrap:wrap;gap:5px 9px;margin-bottom:15px;}
    .on-checkbox {background:#eaf8f1;color:#2d8659;border-radius:8px;border:2px solid #eaf8f1;padding:7px 15px;cursor:pointer;font-weight:600;font-size:.98em;user-select:none;transition:.13s;}
    .on-checkbox:active {box-shadow:0 2px 4px #2d865910;}
    .on-checkbox.selected {background:#2d8659;color:#fff;border:2px solid #2d8659;}
    .bairrofav{background:#fffae1;color:#b09d2a;padding:4px 9px;border-radius:10px;margin-left:9px;font-size:.93em;font-weight:700;}
    .preview-box{background:#f7fcf9;border-radius:11px;padding:13px 13px 13px 13px; }
    .preview-title{font-weight:700;color:#2d8659;font-size:1.03em;margin-bottom:2px;}
    @media (max-width:480px) {.onboard-content{padding:15px 4vw 0 4vw;}}
</style>

<script>
  let step = 0, errorMsg = '';
  const dados = {
    foto: '', nome:'', bairro:'', cidade:'', endereco:'',
    whatsapp:'', instagram:'', descricao:'', favbairro:false, reserva:false,
    horarios: {seg:"10:00-22:00", ter:"10:00-22:00", qua:"10:00-22:00", qui:"10:00-22:00", sex:"10:00-22:00", sab:"11:00-21:00", dom:""},
    metodos: {delivery:true, retirada:true, local:false, reserva:false},
    pagamentos: [],
    tipos: []
  };
  const opcoespag = ['Dinheiro','Cartão','Pix','VR/VA','Mercado Pago'];
  const opcoestipos = ['Africana','Brasileira','Vegana','Japonesa','Lanches','Pizzaria','Doces','Sem Glúten','Árabe','Saladas','Bar','Pastelaria'];
  const opcoesdias = ['seg','ter','qua','qui','sex','sab','dom'];
  const opcoesdiasLabel = {seg:'Segunda', ter:'Terça', qua:'Quarta', qui:'Quinta', sex:'Sexta', sab:'Sábado', dom:'Domingo'};
  const steps = [
    {
      titulo: "Informações Gerais",
      conteudo: () => `
        <div class="onboard-help">Preencha os dados essenciais para montar sua página de restaurante.</div>
        ${errorMsg ? `<div class="onboard-error">${errorMsg}</div>` : ""}
        <div class="perfil-foto-box">
          <img src="${dados.foto || 'https://images.unsplash.com/photo-1528605248644-14dd04022da1?auto=format&fit=crop&w=96&q=80'}" class="perfil-foto-preview" id="fotoPreview" alt="Prévia logo"/>
          <span class="perfil-foto-upload">
            <label for="fotoInput">Escolher Foto
              <input type="file" accept="image/*" id="fotoInput" onchange="setFoto(event)">
            </label>
          </span>
        </div>
        <input class="onboard-input" placeholder="Nome do restaurante" id="inputNome" value="${dados.nome||''}">
        <div style="margin-bottom:8px;display:flex;gap:7px;">
          <input class="onboard-input" style="flex:2;" placeholder="Endereço" id="inputEndereco" value="${dados.endereco||''}">
          <input class="onboard-input" style="flex:1;" placeholder="Bairro" id="inputBairro" value="${dados.bairro||''}">
        </div>
        <input class="onboard-input" placeholder="Cidade" id="inputCidade" value="${dados.cidade||''}">
        <input class="onboard-input" placeholder="WhatsApp para clientes" id="inputWhats" value="${dados.whatsapp||''}">
        <input class="onboard-input" placeholder="Instagram (ex: @meurestaurante)" id="inputInsta" value="${dados.instagram||''}">
        <input class="onboard-input" placeholder="Resumo/descrição breve" id="inputDesc" value="${dados.descricao||''}">
        <label><input type="checkbox" id="favBairro" ${dados.favbairro?"checked":""}> Restaurante favorito do bairro!</label>
      `
    },
    {
      titulo: "Canais de Atendimento",
      conteudo: () => `
        <div class="onboard-help">Marque tudo que você oferece:<br><i>(Delivery, Retirada, Consumo no local, Reserva de mesa)</i></div>
        <div class="on-togglegroup">
          <label>
            <input type="checkbox" id="checkDelivery" ${dados.metodos.delivery?"checked":""} onchange="dados.metodos.delivery = this.checked;">
            <span class="${dados.metodos.delivery?'on-checkbox active':''}" onclick="document.getElementById('checkDelivery').click()">Delivery</span>
          </label>
          <label>
            <input type="checkbox" id="checkRetirada" ${dados.metodos.retirada?"checked":""} onchange="dados.metodos.retirada = this.checked;">
            <span class="${dados.metodos.retirada?'on-checkbox active':''}" onclick="document.getElementById('checkRetirada').click()">Retirada</span>
          </label>
          <label>
            <input type="checkbox" id="checkLocal" ${dados.metodos.local?"checked":""} onchange="dados.metodos.local = this.checked;">
            <span class="${dados.metodos.local?'on-checkbox active':''}" onclick="document.getElementById('checkLocal').click()">Consumo Local</span>
          </label>
          <label>
            <input type="checkbox" id="checkReserva" ${dados.metodos.reserva?"checked":""} onchange="dados.metodos.reserva = this.checked;">
            <span class="${dados.metodos.reserva?'on-checkbox active':''}" onclick="document.getElementById('checkReserva').click()">Reserva Mesa</span>
          </label>
        </div>
        <label><input type="checkbox" id="checkPoliticaReserva" ${dados.reserva?"checked":""}> Aceito reservas pelo app/WhatsApp</label>
      `
    },
    {
      titulo: "Tipo de Cozinha e Pagamento",
      conteudo: () => `
        <div class="onboard-help">Defina como o cliente te encontra:</div>
        <div style="margin-bottom:7px;font-weight:700;color:#2d8659;">Tipos de cozinha:</div>
        <div class="on-checkbox-list" id="tiposCozi">
          ${opcoestipos.map(t=>`<span class="on-checkbox ${dados.tipos.includes(t)?'selected':''}" onclick="toggleSeleciona('tipos', '${t}')">${t}</span>`).join('')}
        </div>
        <div style="margin-bottom:7px;font-weight:700;color:#2d8659;">Formas de pagamento:</div>
        <div class="on-checkbox-list" id="formasPag">
          ${opcoespag.map(t=>`<span class="on-checkbox ${dados.pagamentos.includes(t)?'selected':''}" onclick="toggleSeleciona('pagamentos', '${t}')">${t}</span>`).join('')}
        </div>
      `
    },
    {
      titulo: "Horário de Funcionamento",
      conteudo: () => `
        <div class="onboard-help">Configure o horário para cada dia da semana:</div>
        ${opcoesdias.map(d=>
          `<div style="display:flex;align-items:center;margin-bottom:7px;">
            <label style="width:93px;">${opcoesdiasLabel[d]}:</label>
            <input type="text" class="onboard-input" style="width:140px;" id="horario_${d}" placeholder="ex: 10h-22h" value="${dados.horarios[d]||''}">
          </div>`).join('')}
      `
    },
    {
      titulo: "Pré-visualização",
      conteudo: () => `
        <div class="preview-box">
          <div class="preview-title">${dados.nome||'Restaurante Exemplo'} <span class="bairrofav">${dados.favbairro?'Favorito do bairro':'Novo'}</span></div>
          <div style="color:#444;margin-bottom:6px;">${dados.descricao||'Descrição breve do seu restaurante aparecerá aqui.'}</div>
          <div style="margin-bottom:5px;"><b>Endereço:</b> ${dados.endereco||'Rua Exemplo, 123'} - ${dados.bairro||'Bairro'} - ${dados.cidade||'Cidade'}</div>
          <div style="margin-bottom:5px;"><b>WhatsApp:</b> ${dados.whatsapp||'(00) 00000-0000'} | <b>Instagram:</b> ${dados.instagram||'@meurestaurante'}</div>
          <div style="margin-bottom:5px;"><b>Canais:</b> ${renderCanais()}</div>
          <div style="margin-bottom:5px;"><b>Tipos de cozinha:</b> ${dados.tipos.length?dados.tipos.join(', '):'—'}</div>
          <div style="margin-bottom:5px;"><b>Pagamentos:</b> ${dados.pagamentos.length?dados.pagamentos.join(', '):'—'}</div>
          <div style="margin-bottom:3px;"><b>Horários:</b></div>
          ${opcoesdias.map(d=>`<div style="font-size:.96em;color:#555;">${opcoesdiasLabel[d]}: ${dados.horarios[d]||'—'}</div>`).join('')}
        </div>
      `
    }
  ];

  function renderCanais(){
    const ativos = [];
    if(dados.metodos.delivery) ativos.push('Delivery');
    if(dados.metodos.retirada) ativos.push('Retirada');
    if(dados.metodos.local) ativos.push('Consumo local');
    if(dados.metodos.reserva) ativos.push('Reserva de mesa');
    return ativos.length? ativos.join(', ') : '—';
  }

  function setFoto(ev){
    const file = ev.target.files[0];
    if(!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      dados.foto = e.target.result;
      document.getElementById('fotoPreview').src = dados.foto;
    };
    reader.readAsDataURL(file);
  }

  function toggleSeleciona(campo, valor){
    const lista = dados[campo];
    if(lista.includes(valor)){
      dados[campo] = lista.filter(i=>i!==valor);
    } else {
      dados[campo] = [...lista, valor];
    }
    renderStep();
  }

  function validarStep(){
    errorMsg = '';
    if(step===0){
      if(!document.getElementById('inputNome').value.trim()) errorMsg = 'Informe o nome do restaurante.';
      dados.nome = document.getElementById('inputNome').value;
      dados.endereco = document.getElementById('inputEndereco').value;
      dados.bairro = document.getElementById('inputBairro').value;
      dados.cidade = document.getElementById('inputCidade').value;
      dados.whatsapp = document.getElementById('inputWhats').value;
      dados.instagram = document.getElementById('inputInsta').value;
      dados.descricao = document.getElementById('inputDesc').value;
      dados.favbairro = document.getElementById('favBairro').checked;
      if(errorMsg) return false;
    }
    if(step===1){
      dados.metodos.delivery = document.getElementById('checkDelivery').checked;
      dados.metodos.retirada = document.getElementById('checkRetirada').checked;
      dados.metodos.local = document.getElementById('checkLocal').checked;
      dados.metodos.reserva = document.getElementById('checkReserva').checked;
      dados.reserva = document.getElementById('checkPoliticaReserva').checked;
    }
    if(step===2){
      // seleção já sincronizada pelo toggleSeleciona
    }
    if(step===3){
      opcoesdias.forEach(d=>{
        dados.horarios[d] = document.getElementById('horario_'+d).value;
      });
    }
    return true;
  }

  function onNext(){
    if(!validarStep()) { renderStep(); return; }
    if(step < steps.length-1){ step++; renderStep(); }
  }
  function onPrev(){ if(step>0){ step--; renderStep(); } }
  function fecharOnboarding(){ document.getElementById('onboardModal').style.display='none'; }

  function renderStep(){
    const current = steps[step];
    document.getElementById('onStep').innerHTML = `Passo ${step+1} de ${steps.length}`;
    document.getElementById('onMain').innerHTML = `
      <div class="onboard-title">${current.titulo}</div>
      ${current.conteudo()}
    `;
    document.getElementById('onBtnPrev').disabled = step===0;
    document.getElementById('onBtnNext').innerText = step===steps.length-1 ? 'Finalizar' : 'Avançar';
    document.getElementById('onBar').style.width = `${((step+1)/steps.length)*100}%`;
  }
  document.addEventListener('DOMContentLoaded', renderStep);
</script>
<?php
if (!$vc_wizard_inline && ! $vc_marketplace_inline) {
    get_footer();
}
