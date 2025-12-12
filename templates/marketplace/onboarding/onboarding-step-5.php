<?php
/**
 * Onboarding Step 5: Primeiros Produtos
 * 
 * @var array $wizard_data
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$products = $wizard_data['products'] ?? [];
?>
<div class="wizard-title">Cadastre seus primeiros produtos</div>
<div class="wizard-subtitle">Comece pelos seus campeões de venda. Recomendamos cadastrar pelo menos 3.</div>

<div id="wizardProductsList" style="margin-top:24px;margin-bottom:24px;">
    <?php if ( ! empty( $products ) ) : ?>
        <?php foreach ( $products as $idx => $product ) : ?>
            <div class="product-item">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <strong style="font-size:16px;"><?php echo esc_html( $product['name'] ?? 'Produto ' . ( $idx + 1 ) ); ?></strong>
                    <button onclick="vcRemoveProduct(<?php echo esc_js( $idx ); ?>)" style="background:#ffe7e7;color:#ea5252;border:none;padding:4px 12px;border-radius:6px;cursor:pointer;font-weight:600;">Remover</button>
                </div>
                <div style="color:#6b7672;font-size:14px;">
                    Categoria: <?php echo esc_html( $product['category'] ?? 'Sem categoria' ); ?> | 
                    Preço: R$ <?php echo number_format( (float) ( $product['price'] ?? 0 ), 2, ',', '.' ); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <div style="text-align:center;padding:20px;color:#999;">Nenhum produto cadastrado ainda.</div>
    <?php endif; ?>
</div>

<div style="background:#eaf8f1;padding:16px;border-radius:8px;margin-bottom:16px;">
    <div style="font-weight:700;margin-bottom:12px;color:#2d8659;">Adicionar Produto</div>
    <input type="text" id="wizardProductName" placeholder="Nome do produto *" style="margin-bottom:12px;">
    <select id="wizardProductCategory" style="margin-bottom:12px;">
        <option value="">Selecione a categoria</option>
    </select>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <input type="number" id="wizardProductPrice" placeholder="Preço (R$) *" step="0.01" min="0">
        <input type="file" id="wizardProductImage" accept="image/*">
    </div>
    <textarea id="wizardProductDescription" placeholder="Descrição (opcional)" rows="2" style="margin-bottom:12px;"></textarea>
    <button onclick="vcAddProduct()" style="background:#2d8659;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;width:100%;">Adicionar Produto</button>
</div>

<div style="background:#fffbe2;padding:12px;border-radius:8px;font-size:14px;color:#856404;">
    Você já cadastrou <strong><?php echo count( $products ); ?></strong> produto(s). Recomendamos pelo menos 3.
</div>

<div style="margin-top:16px;text-align:center;">
    <a href="#" onclick="vcWizardNext();return false;" style="color:#2d8659;text-decoration:underline;font-size:14px;">Pular (vou criar manualmente depois)</a>
</div>

