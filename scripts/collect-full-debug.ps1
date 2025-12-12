# ============================================================================
# SISTEMA COMPLETO DE COLETA DE DEBUG - VemComer Core
# ============================================================================
# Este script coleta TODAS as informações necessárias para debug completo
# Execute: .\scripts\collect-full-debug.ps1
# ============================================================================

param(
    [switch]$IncludeDatabase = $false,
    [switch]$IncludeCache = $false,
    [int]$LogLines = 500
)

$ErrorActionPreference = "Continue"
$wpPath = "C:\Users\Adm-Sup\Local Sites\pedevem-local\app\public"
$wpContentPath = Join-Path $wpPath "wp-content"
$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
$outputDir = Join-Path $pluginPath "debug-reports"
$timestamp = Get-Date -Format 'yyyy-MM-dd-HHmmss'
$outputFile = Join-Path $outputDir "full-debug-$timestamp.txt"

# Criar diretório de saída
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "COLETANDO DEBUG COMPLETO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$report = @()

# ============================================================================
# 1. INFORMAÇÕES DO SISTEMA
# ============================================================================
Write-Host "[1/10] Coletando informações do sistema..." -ForegroundColor Yellow
$report += "========================================"
$report += "INFORMAÇÕES DO SISTEMA"
$report += "========================================"
$report += "Data/Hora: $(Get-Date)"
$report += "Sistema: $(Get-ComputerInfo | Select-Object -ExpandProperty WindowsProductName)"
$report += "PowerShell: $($PSVersionTable.PSVersion)"
$report += "WordPress Path: $wpPath"
$report += "Plugin Path: $pluginPath"
$report += ""

# ============================================================================
# 2. CONFIGURAÇÕES DO WORDPRESS
# ============================================================================
Write-Host "[2/10] Coletando configurações do WordPress..." -ForegroundColor Yellow
$wpConfig = Join-Path $wpPath "wp-config.php"
if (Test-Path $wpConfig) {
    $configContent = Get-Content $wpConfig -Raw
    $report += "========================================"
    $report += "WP-CONFIG.PHP (resumido)"
    $report += "========================================"
    
    # Extrair apenas configurações relevantes (sem secrets)
    $configLines = $configContent -split "`n"
    foreach ($line in $configLines) {
        if ($line -match "define\s*\(\s*['""](DB_NAME|DB_USER|DB_HOST|WP_DEBUG|WP_DEBUG_LOG|WP_DEBUG_DISPLAY|WP_ENVIRONMENT_TYPE)['""]" -and $line -notmatch "PASSWORD|KEY|SALT") {
            $report += $line.Trim()
        }
    }
    $report += ""
}

# ============================================================================
# 3. VERSÃO DO WORDPRESS E PLUGINS
# ============================================================================
Write-Host "[3/10] Coletando versões e plugins..." -ForegroundColor Yellow
$report += "========================================"
$report += "VERSÕES E PLUGINS"
$report += "========================================"

# Verificar versão do WordPress
$wpVersionFile = Join-Path $wpPath "wp-includes\version.php"
if (Test-Path $wpVersionFile) {
    $wpVersionContent = Get-Content $wpVersionFile -Raw
    if ($wpVersionContent -match "\$wp_version\s*=\s*['""]([^'""]+)['""]") {
        $report += "WordPress Version: $($matches[1])"
    }
}

# Verificar versão do plugin
$pluginMainFile = Join-Path $pluginPath "vemcomer-core.php"
if (Test-Path $pluginMainFile) {
    $pluginContent = Get-Content $pluginMainFile -Raw
    if ($pluginContent -match "Version:\s*([0-9.]+)") {
        $report += "VemComer Core Version: $($matches[1])"
    }
}

# Listar plugins ativos (via wp-cli se disponível, senão via arquivos)
$pluginsPath = Join-Path $wpContentPath "plugins"
if (Test-Path $pluginsPath) {
    $plugins = Get-ChildItem -Path $pluginsPath -Directory | Where-Object { $_.Name -ne "index.php" }
    $report += ""
    $report += "Plugins instalados:"
    foreach ($plugin in $plugins) {
        $report += "  - $($plugin.Name)"
    }
}
$report += ""

# ============================================================================
# 4. LOGS DO WORDPRESS
# ============================================================================
Write-Host "[4/10] Coletando logs do WordPress..." -ForegroundColor Yellow
$debugLog = Join-Path $wpContentPath "debug.log"
if (Test-Path $debugLog) {
    $report += "========================================"
    $report += "WORDPRESS DEBUG.LOG (últimas $LogLines linhas)"
    $report += "========================================"
    $logContent = Get-Content $debugLog -Tail $LogLines -ErrorAction SilentlyContinue
    $report += $logContent
    $report += ""
}

# ============================================================================
# 5. LOGS DO VEMCOMER
# ============================================================================
Write-Host "[5/10] Coletando logs do VemComer..." -ForegroundColor Yellow
$vemcomerLog = Join-Path $wpContentPath "uploads\vemcomer-debug.log"
if (Test-Path $vemcomerLog) {
    $report += "========================================"
    $report += "VEMCOMER DEBUG.LOG (últimas $LogLines linhas)"
    $report += "========================================"
    $logContent = Get-Content $vemcomerLog -Tail $LogLines -ErrorAction SilentlyContinue
    $report += $logContent
    $report += ""
}

# ============================================================================
# 6. ESTADO DO BANCO DE DADOS (queries SQL)
# ============================================================================
if ($IncludeDatabase) {
    Write-Host "[6/10] Coletando estado do banco de dados..." -ForegroundColor Yellow
    $report += "========================================"
    $report += "ESTADO DO BANCO DE DADOS"
    $report += "========================================"
    
    # Queries SQL para coletar informações importantes
    $sqlQueries = @"
-- Contar restaurantes
SELECT COUNT(*) as total_restaurants FROM wp_posts WHERE post_type = 'vc_restaurant' AND post_status != 'trash';

-- Contar produtos
SELECT COUNT(*) as total_products FROM wp_posts WHERE post_type = 'vc_menu_item' AND post_status != 'trash';

-- Contar categorias de cardápio
SELECT COUNT(*) as total_menu_categories FROM wp_terms WHERE term_id IN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE taxonomy = 'vc_menu_category');

-- Contar categorias de restaurante (cuisine)
SELECT COUNT(*) as total_cuisines FROM wp_terms WHERE term_id IN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE taxonomy = 'vc_cuisine');

-- Últimos restaurantes criados
SELECT ID, post_title, post_status, post_date FROM wp_posts WHERE post_type = 'vc_restaurant' ORDER BY post_date DESC LIMIT 5;

-- Últimos produtos criados
SELECT ID, post_title, post_status, post_date FROM wp_posts WHERE post_type = 'vc_menu_item' ORDER BY post_date DESC LIMIT 5;

-- Verificar meta de onboarding
SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE meta_key LIKE '%onboarding%' LIMIT 20;
"@
    
    $report += "Queries SQL para executar no Adminer/phpMyAdmin:"
    $report += $sqlQueries
    $report += ""
} else {
    Write-Host "[6/10] Pulando banco de dados (use -IncludeDatabase para incluir)" -ForegroundColor Gray
}

# ============================================================================
# 7. CONFIGURAÇÕES DO PLUGIN
# ============================================================================
Write-Host "[7/10] Coletando configurações do plugin..." -ForegroundColor Yellow
$report += "========================================"
$report += "CONFIGURAÇÕES DO PLUGIN"
$report += "========================================"

# Verificar constantes definidas
$pluginMainFile = Join-Path $pluginPath "vemcomer-core.php"
if (Test-Path $pluginMainFile) {
    $pluginContent = Get-Content $pluginMainFile -Raw
    if ($pluginContent -match "define\s*\(\s*['""](VEMCOMER_CORE_VERSION|VC_DEBUG)['""]") {
        $report += "Constantes do plugin encontradas no arquivo principal"
    }
}

# Verificar arquivos de configuração
$configFiles = @(
    "inc\bootstrap.php",
    "inc\settings.php"
)

foreach ($configFile in $configFiles) {
    $fullPath = Join-Path $pluginPath $configFile
    if (Test-Path $fullPath) {
        $report += "Arquivo de configuração encontrado: $configFile"
    }
}
$report += ""

# ============================================================================
# 8. ESTRUTURA DE ARQUIVOS IMPORTANTES
# ============================================================================
Write-Host "[8/10] Verificando estrutura de arquivos..." -ForegroundColor Yellow
$report += "========================================"
$report += "ESTRUTURA DE ARQUIVOS"
$report += "========================================"

$importantFiles = @(
    "vemcomer-core.php",
    "inc\REST\Onboarding_Controller.php",
    "inc\REST\Menu_Categories_Controller.php",
    "inc\REST\Menu_Items_Controller.php",
    "assets\js\onboarding-wizard.js",
    "templates\marketplace\onboarding-wizard.php",
    "templates\marketplace\onboarding\onboarding-step-4.php",
    "templates\marketplace\onboarding\onboarding-step-5.php"
)

foreach ($file in $importantFiles) {
    $fullPath = Join-Path $pluginPath $file
    if (Test-Path $fullPath) {
        $fileInfo = Get-Item $fullPath
        $report += "✓ $file (Última modificação: $($fileInfo.LastWriteTime))"
    } else {
        $report += "✗ $file (NÃO ENCONTRADO)"
    }
}
$report += ""

# ============================================================================
# 9. CACHE E TRANSIENTS
# ============================================================================
if ($IncludeCache) {
    Write-Host "[9/10] Coletando informações de cache..." -ForegroundColor Yellow
    $report += "========================================"
    $report += "CACHE E TRANSIENTS"
    $report += "========================================"
    $report += "Para verificar cache, execute no Adminer:"
    $report += "SELECT * FROM wp_options WHERE option_name LIKE '%_transient_%' OR option_name LIKE '%_cache%' LIMIT 50;"
    $report += ""
} else {
    Write-Host "[9/10] Pulando cache (use -IncludeCache para incluir)" -ForegroundColor Gray
}

# ============================================================================
# 10. INFORMAÇÕES DE REDE (REST API)
# ============================================================================
Write-Host "[10/10] Coletando informações de REST API..." -ForegroundColor Yellow
$report += "========================================"
$report += "REST API ENDPOINTS"
$report += "========================================"
$report += "Endpoints principais do VemComer:"
$report += "  - /wp-json/vemcomer/v1/onboarding/status"
$report += "  - /wp-json/vemcomer/v1/onboarding/step"
$report += "  - /wp-json/vemcomer/v1/onboarding/step-content"
$report += "  - /wp-json/vemcomer/v1/menu-categories"
$report += "  - /wp-json/vemcomer/v1/menu-items"
$report += ""

# ============================================================================
# SALVAR RELATÓRIO
# ============================================================================
Write-Host ""
Write-Host "Salvando relatório..." -ForegroundColor Green
$report | Out-File -FilePath $outputFile -Encoding UTF8

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ RELATÓRIO COMPLETO GERADO!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "Arquivo: $outputFile" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Você pode:" -ForegroundColor Yellow
Write-Host "   1. Abrir o arquivo e copiar o conteúdo para mim" -ForegroundColor White
Write-Host "   2. Ou me pedir para ler diretamente: 'Lê o relatório de debug mais recente'" -ForegroundColor White
Write-Host ""

# Perguntar se quer abrir
$open = Read-Host "Deseja abrir o arquivo agora? (S/N)"
if ($open -eq "S" -or $open -eq "s") {
    notepad $outputFile
}

