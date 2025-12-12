# ============================================================================
# EXPORTAR ESTADO DO BANCO DE DADOS
# ============================================================================
# Gera queries SQL para verificar o estado do banco
# Execute: .\scripts\export-database-state.ps1
# ============================================================================

$outputDir = Join-Path $PSScriptRoot ".." "debug-reports"
$timestamp = Get-Date -Format 'yyyy-MM-dd-HHmmss'
$outputFile = Join-Path $outputDir "database-state-$timestamp.sql"

if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

Write-Host "Gerando queries SQL para verificar estado do banco..." -ForegroundColor Green

$sqlQueries = @"
-- ============================================================================
-- QUERIES SQL PARA VERIFICAR ESTADO DO BANCO - VemComer Core
-- ============================================================================
-- Execute estas queries no Adminer/phpMyAdmin do Local
-- ============================================================================

-- 1. CONTAGENS GERAIS
-- ============================================================================
SELECT 'Restaurantes' as tipo, COUNT(*) as total 
FROM wp_posts 
WHERE post_type = 'vc_restaurant' AND post_status != 'trash';

SELECT 'Produtos (Menu Items)' as tipo, COUNT(*) as total 
FROM wp_posts 
WHERE post_type = 'vc_menu_item' AND post_status != 'trash';

SELECT 'Pedidos' as tipo, COUNT(*) as total 
FROM wp_posts 
WHERE post_type = 'vc_pedido' AND post_status != 'trash';

SELECT 'Categorias de Cardápio' as tipo, COUNT(*) as total 
FROM wp_terms t
INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
WHERE tt.taxonomy = 'vc_menu_category';

SELECT 'Categorias de Restaurante (Cuisine)' as tipo, COUNT(*) as total 
FROM wp_terms t
INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
WHERE tt.taxonomy = 'vc_cuisine';

SELECT 'Grupos de Adicionais (Catálogo)' as tipo, COUNT(*) as total 
FROM wp_posts 
WHERE post_type = 'vc_addon_group' AND post_status != 'trash';

SELECT 'Itens de Adicionais (Catálogo)' as tipo, COUNT(*) as total 
FROM wp_posts 
WHERE post_type = 'vc_addon_item' AND post_status != 'trash';

SELECT 'Grupos de Adicionais (Loja)' as tipo, COUNT(*) as total 
FROM wp_posts 
WHERE post_type = 'vc_product_modifier' 
AND post_status != 'trash'
AND meta_value = '0' 
AND post_id IN (
    SELECT post_id FROM wp_postmeta WHERE meta_key = '_vc_group_id'
);

-- 2. VERIFICAR ONBOARDING
-- ============================================================================
SELECT 
    p.ID,
    p.post_title as restaurante,
    pm1.meta_value as onboarding_completed,
    pm2.meta_value as onboarding_step,
    pm3.meta_value as onboarding_completed_steps
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_vc_onboarding_completed'
LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_vc_onboarding_step'
LEFT JOIN wp_postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_vc_onboarding_completed_steps'
WHERE p.post_type = 'vc_restaurant'
LIMIT 10;

-- 3. VERIFICAR CATEGORIAS DE RESTAURANTE (CUISINE)
-- ============================================================================
SELECT 
    p.ID,
    p.post_title as restaurante,
    pm1.meta_value as primary_cuisine_id,
    pm2.meta_value as secondary_cuisines,
    GROUP_CONCAT(t.name SEPARATOR ', ') as cuisine_terms
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_vc_primary_cuisine'
LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_vc_secondary_cuisines'
LEFT JOIN wp_term_relationships tr ON p.ID = tr.object_id
LEFT JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'vc_cuisine'
LEFT JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.post_type = 'vc_restaurant'
GROUP BY p.ID
LIMIT 10;

-- 4. VERIFICAR CATEGORIAS DE CARDÁPIO
-- ============================================================================
SELECT 
    t.term_id,
    t.name as categoria,
    tm1.meta_value as restaurant_id,
    tm2.meta_value as is_catalog_category,
    tm3.meta_value as recommended_for_cuisines,
    COUNT(p.ID) as produtos_count
FROM wp_terms t
INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
LEFT JOIN wp_termmeta tm1 ON t.term_id = tm1.term_id AND tm1.meta_key = '_vc_restaurant_id'
LEFT JOIN wp_termmeta tm2 ON t.term_id = tm2.term_id AND tm2.meta_key = '_vc_is_catalog_category'
LEFT JOIN wp_termmeta tm3 ON t.term_id = tm3.term_id AND tm3.meta_key = '_vc_recommended_for_cuisines'
LEFT JOIN wp_term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
LEFT JOIN wp_posts p ON tr.object_id = p.ID AND p.post_type = 'vc_menu_item'
WHERE tt.taxonomy = 'vc_menu_category'
GROUP BY t.term_id
ORDER BY produtos_count DESC
LIMIT 20;

-- 5. VERIFICAR PRODUTOS E SUAS CATEGORIAS
-- ============================================================================
SELECT 
    p.ID,
    p.post_title as produto,
    p.post_status,
    pm1.meta_value as restaurant_id,
    GROUP_CONCAT(t.name SEPARATOR ', ') as categorias
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_vc_restaurant_id'
LEFT JOIN wp_term_relationships tr ON p.ID = tr.object_id
LEFT JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'vc_menu_category'
LEFT JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.post_type = 'vc_menu_item'
GROUP BY p.ID
ORDER BY p.post_date DESC
LIMIT 20;

-- 6. VERIFICAR ADICIONAIS (MODIFIERS)
-- ============================================================================
SELECT 
    p.ID,
    p.post_title as grupo_item,
    p.post_type,
    pm1.meta_value as restaurant_id,
    pm2.meta_value as group_id,
    pm3.meta_value as catalog_group_id,
    pm4.meta_value as catalog_item_id
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_vc_restaurant_id'
LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_vc_group_id'
LEFT JOIN wp_postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_vc_catalog_group_id'
LEFT JOIN wp_postmeta pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_vc_catalog_item_id'
WHERE p.post_type IN ('vc_product_modifier', 'vc_addon_group', 'vc_addon_item')
ORDER BY p.post_date DESC
LIMIT 30;

-- 7. VERIFICAR META DE ONBOARDING POR RESTAURANTE
-- ============================================================================
SELECT 
    p.ID as restaurant_id,
    p.post_title,
    MAX(CASE WHEN pm.meta_key = '_vc_onboarding_completed' THEN pm.meta_value END) as onboarding_completed,
    MAX(CASE WHEN pm.meta_key = '_vc_onboarding_step' THEN pm.meta_value END) as onboarding_step,
    MAX(CASE WHEN pm.meta_key = '_vc_onboarding_completed_steps' THEN pm.meta_value END) as completed_steps,
    MAX(CASE WHEN pm.meta_key = '_vc_primary_cuisine' THEN pm.meta_value END) as primary_cuisine,
    MAX(CASE WHEN pm.meta_key = '_vc_secondary_cuisines' THEN pm.meta_value END) as secondary_cuisines
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id 
    AND pm.meta_key IN (
        '_vc_onboarding_completed',
        '_vc_onboarding_step',
        '_vc_onboarding_completed_steps',
        '_vc_primary_cuisine',
        '_vc_secondary_cuisines'
    )
WHERE p.post_type = 'vc_restaurant'
GROUP BY p.ID
LIMIT 10;

-- 8. VERIFICAR USUÁRIOS LOJISTA
-- ============================================================================
SELECT 
    u.ID,
    u.user_login,
    u.user_email,
    um1.meta_value as restaurant_id,
    GROUP_CONCAT(um2.meta_value SEPARATOR ', ') as roles
FROM wp_users u
LEFT JOIN wp_usermeta um1 ON u.ID = um1.user_id AND um1.meta_key = 'vc_restaurant_id'
LEFT JOIN wp_usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = 'wp_capabilities'
WHERE um2.meta_value LIKE '%lojista%' OR um2.meta_value LIKE '%administrator%'
GROUP BY u.ID
LIMIT 10;
"@

$sqlQueries | Out-File -FilePath $outputFile -Encoding UTF8

Write-Host "✅ Queries SQL salvas em: $outputFile" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Próximos passos:" -ForegroundColor Yellow
Write-Host "   1. No Local, clique com botão direito no site → Database → Open Adminer" -ForegroundColor White
Write-Host "   2. Selecione o banco de dados 'local'" -ForegroundColor White
Write-Host "   3. Vá na aba 'SQL command'" -ForegroundColor White
Write-Host "   4. Cole e execute as queries deste arquivo" -ForegroundColor White
Write-Host "   5. Copie os resultados e compartilhe comigo" -ForegroundColor White
Write-Host ""

$open = Read-Host "Deseja abrir o arquivo agora? (S/N)"
if ($open -eq "S" -or $open -eq "s") {
    notepad $outputFile
}

