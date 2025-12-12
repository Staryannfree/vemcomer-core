# ============================================================================
# EXPORTAR LOGS DO NAVEGADOR (JavaScript)
# ============================================================================
# Instruções para capturar logs do console do navegador
# Execute: .\scripts\export-browser-logs.ps1
# ============================================================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "EXPORTAR LOGS DO NAVEGADOR" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "📋 INSTRUÇÕES PARA CAPTURAR LOGS DO CONSOLE:" -ForegroundColor Yellow
Write-Host ""

Write-Host "1. Abra o navegador e acesse o wizard de onboarding" -ForegroundColor White
Write-Host "2. Pressione F12 para abrir o DevTools" -ForegroundColor White
Write-Host "3. Vá na aba 'Console'" -ForegroundColor White
Write-Host "4. Clique com botão direito no console → 'Save as...' OU" -ForegroundColor White
Write-Host "5. No console, digite: exportOnboardingDebugLogs()" -ForegroundColor White
Write-Host "   (Isso baixará um arquivo com todos os logs do wizard)" -ForegroundColor Gray
Write-Host ""

Write-Host "📋 INSTRUÇÕES PARA CAPTURAR NETWORK REQUESTS:" -ForegroundColor Yellow
Write-Host ""

Write-Host "1. No DevTools, vá na aba 'Network'" -ForegroundColor White
Write-Host "2. Filtre por 'onboarding' ou 'menu-categories'" -ForegroundColor White
Write-Host "3. Reproduza o problema (Passo 1 → Passo 4 → Passo 5)" -ForegroundColor White
Write-Host "4. Clique com botão direito em uma requisição → 'Save all as HAR'" -ForegroundColor White
Write-Host "5. OU copie manualmente:" -ForegroundColor White
Write-Host "   - Clique na requisição" -ForegroundColor Gray
Write-Host "   - Vá na aba 'Headers' → copie 'Request URL' e 'Request Headers'" -ForegroundColor Gray
Write-Host "   - Vá na aba 'Response' → copie o conteúdo JSON" -ForegroundColor Gray
Write-Host ""

Write-Host "📋 LOGS AUTOMÁTICOS DO WIZARD:" -ForegroundColor Yellow
Write-Host ""

Write-Host "O wizard agora salva logs automaticamente no localStorage." -ForegroundColor White
Write-Host "Para exportar, execute no console do navegador:" -ForegroundColor White
Write-Host "  exportOnboardingDebugLogs()" -ForegroundColor Cyan
Write-Host ""

Write-Host "Isso baixará um arquivo com todos os logs do wizard de onboarding." -ForegroundColor Gray
Write-Host ""

Write-Host "✅ Pronto! Siga as instruções acima e compartilhe os logs comigo." -ForegroundColor Green
Write-Host ""

