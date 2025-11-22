@echo off
chcp 65001 >nul
echo ========================================
echo   Git Push para GitHub
echo ========================================
echo.

REM Verificar se há commits para fazer push
git log origin/main..HEAD --oneline >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Nenhum commit pendente para fazer push.
    echo.
    pause
    exit /b 0
)

echo Verificando commits pendentes...
git log origin/main..HEAD --oneline
echo.
echo.

REM Fazer o push
echo Fazendo push para origin/main...
git push origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   ✓ Push concluído com sucesso!
    echo ========================================
    echo.
    git status
) else (
    echo.
    echo ========================================
    echo   ✗ Erro ao fazer push!
    echo ========================================
    echo.
    echo Possíveis causas:
    echo - Problema de autenticação
    echo - Problema de conexão
    echo - Conflitos no repositório remoto
    echo.
)

echo.
pause

