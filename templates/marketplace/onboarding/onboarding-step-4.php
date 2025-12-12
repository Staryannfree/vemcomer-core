<?php
/**
 * Onboarding Step 4: Categorias do Cardápio
 * 
 * @var array $recommended_categories
 * @var bool  $has_primary_cuisine
 * @var array $wizard_data
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$category_names = $wizard_data['category_names'] ?? [];
?>
<div class="wizard-title">Categorias do seu cardápio</div>
<div class="wizard-subtitle">Sugerimos algumas categorias de cardápio para o tipo de restaurante que você escolheu. Você pode editar depois.</div>
<?php if ( ! $has_primary_cuisine ) : ?>
    <div style="background:#fffbe2;padding:12px;border-radius:8px;margin-bottom:24px;font-size:14px;color:#856404;border-left:4px solid #facb32;">
        <strong>⚠️ Aviso:</strong> Você não selecionou um tipo de cozinha principal. Mostrando apenas categorias genéricas. Para receber recomendações específicas, volte ao passo 1 e escolha pelo menos 1 tipo de cozinha principal (ex.: Hamburgueria, Pizzaria, Japonesa).
    </div>
<?php endif; ?>
<div id="wizardRecommendedCategories" style="margin-top:24px;">
    <?php if ( ! empty( $recommended_categories ) ) : ?>
        <?php foreach ( $recommended_categories as $cat ) : 
            $is_selected = in_array( $cat['name'], $category_names, true );
        ?>
            <label class="category-checkbox">
                <input type="checkbox" value="<?php echo esc_attr( $cat['name'] ); ?>" <?php checked( $is_selected, true ); ?> onchange="vcToggleCategory('<?php echo esc_js( $cat['name'] ); ?>')">
                <span style="font-weight:600;"><?php echo esc_html( $cat['name'] ); ?></span>
            </label>
        <?php endforeach; ?>
    <?php else : ?>
        <div style="text-align:center;padding:20px;color:#999;">
            Nenhuma categoria recomendada encontrada. 
            <a href="#" onclick="wizardStep=1;renderStep();return false;" style="color:#2d8659;text-decoration:underline;">Volte ao passo 1</a> e escolha um tipo de cozinha principal.
        </div>
    <?php endif; ?>
</div>
<div style="margin-top:16px;">
    <a href="#" onclick="vcSkipCategories();return false;" style="color:#2d8659;text-decoration:underline;font-size:14px;">Pular (vou criar manualmente depois)</a>
</div>

