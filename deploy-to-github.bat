@echo off
chcp 65001 >nul
echo ========================================
echo   Deploy Automático - Commit + Push
echo ========================================
echo.

REM Verificar se há mudanças
git status --porcelain >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Nenhuma mudança detectada.
    echo.
    pause
    exit /b 0
)

echo Arquivos modificados:
git status --short
echo.

REM Adicionar todos os arquivos modificados
echo Adicionando arquivos ao staging...
git add .

REM Fazer commit com mensagem automática
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set timestamp=%datetime:~0,4%-%datetime:~4,2%-%datetime:~6,2% %datetime:~8,2%:%datetime:~10,2%
echo Fazendo commit...
git commit -m "Deploy automático: %timestamp%"
if %ERRORLEVEL% EQU 0 (
    echo ✓ Commit criado com sucesso.
    echo.
) else (
    echo.
    echo ========================================
    echo   ✗ Erro ao fazer commit!
    echo ========================================
    echo.
    echo Possíveis causas:
    echo - Nenhuma mudança para commitar
    echo - Problema com mensagem de commit
    echo.
    pause
    exit /b 1
)

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   Fazendo push para GitHub...
    echo ========================================
    git push origin main
    
    if %ERRORLEVEL% EQU 0 (
        echo.
        echo ========================================
        echo   ✓ Deploy concluído com sucesso!
        echo ========================================
        echo.
        echo O WP Pusher vai fazer o deploy automático
        echo para seu WordPress na Hostinger.
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
) else (
    echo.
    echo ========================================
    echo   ✗ Erro ao fazer commit!
    echo ========================================
    echo.
    echo Possíveis causas:
    echo - Nenhuma mudança para commitar
    echo - Problema com mensagem de commit
    echo.
)

echo.
pause
