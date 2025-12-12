# ============================================================================
# COLETA COMPLETA VIA REST API - Captura TODAS as variáveis
# ============================================================================
# Este script usa o endpoint REST para capturar ABSOLUTAMENTE TUDO
# Execute: .\scripts\collect-everything-via-api.ps1
# ============================================================================

param(
    [string]$SiteUrl = "http://pedevem-local.local",
    [string]$Username = "",
    [string]$Password = ""
)

$ErrorActionPreference = "Continue"
$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
$outputDir = Join-Path $pluginPath "debug-reports"
$timestamp = Get-Date -Format 'yyyy-MM-dd-HHmmss'

if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "COLETANDO TUDO VIA REST API" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Se não tiver credenciais, tentar como guest (só funciona em local)
$authHeader = @{}
if ($Username -and $Password) {
    $base64Auth = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("${Username}:${Password}"))
    $authHeader = @{ Authorization = "Basic $base64Auth" }
}

# Função para fazer requisição REST
function Invoke-VemComerAPI {
    param(
        [string]$Endpoint,
        [string]$OutputFile
    )
    
    $url = "$SiteUrl/wp-json/vemcomer/v1/$Endpoint"
    Write-Host "Coletando: $Endpoint..." -ForegroundColor Yellow
    
    try {
        $response = Invoke-RestMethod -Uri $url -Method Get -Headers $authHeader -ErrorAction Stop
        $json = $response | ConvertTo-Json -Depth 20 -Compress:$false
        $json | Out-File -FilePath $OutputFile -Encoding UTF8
        Write-Host "  ✅ Salvo em: $OutputFile" -ForegroundColor Green
        return $response
    } catch {
        Write-Host "  ❌ Erro: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

# Coletar tudo
Write-Host "[1/7] Estado completo do sistema..." -ForegroundColor Cyan
$stateFile = Join-Path $outputDir "api-full-state-$timestamp.json"
Invoke-VemComerAPI -Endpoint "debug/state" -OutputFile $stateFile

Write-Host ""
Write-Host "[2/7] Variáveis globais..." -ForegroundColor Cyan
$globalsFile = Join-Path $outputDir "api-globals-$timestamp.json"
Invoke-VemComerAPI -Endpoint "debug/globals" -OutputFile $globalsFile

Write-Host ""
Write-Host "[3/7] Dados do usuário atual..." -ForegroundColor Cyan
$userFile = Join-Path $outputDir "api-current-user-$timestamp.json"
Invoke-VemComerAPI -Endpoint "debug/current-user" -OutputFile $userFile

Write-Host ""
Write-Host "[4/7] Estado do restaurante..." -ForegroundColor Cyan
$restaurantFile = Join-Path $outputDir "api-restaurant-state-$timestamp.json"
Invoke-VemComerAPI -Endpoint "debug/restaurant-state" -OutputFile $restaurantFile

Write-Host ""
Write-Host "[5/7] Hooks registrados..." -ForegroundColor Cyan
$hooksFile = Join-Path $outputDir "api-hooks-$timestamp.json"
Invoke-VemComerAPI -Endpoint "debug/hooks" -OutputFile $hooksFile

Write-Host ""
Write-Host "[6/7] Rotas REST API..." -ForegroundColor Cyan
$routesFile = Join-Path $outputDir "api-rest-routes-$timestamp.json"
Invoke-VemComerAPI -Endpoint "debug/rest-routes" -OutputFile $routesFile

Write-Host ""
Write-Host "[7/7] PHP Info..." -ForegroundColor Cyan
$phpinfoFile = Join-Path $outputDir "api-phpinfo-$timestamp.html"
try {
    $url = "$SiteUrl/wp-json/vemcomer/v1/debug/phpinfo"
    $response = Invoke-RestMethod -Uri $url -Method Get -Headers $authHeader -ErrorAction Stop
    $response.phpinfo | Out-File -FilePath $phpinfoFile -Encoding UTF8
    Write-Host "  ✅ Salvo em: $phpinfoFile" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Erro: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ COLETA VIA API COMPLETA!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Arquivos gerados em: $outputDir" -ForegroundColor Cyan
Write-Host ""
Write-Host "Proximos passos:" -ForegroundColor Yellow
Write-Host "   1. Abra os arquivos JSON para ver todas as variaveis" -ForegroundColor White
Write-Host "   2. OU me peca: 'Le o estado completo da API mais recente'" -ForegroundColor White
Write-Host ""

