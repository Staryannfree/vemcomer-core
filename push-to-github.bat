@echo off
echo ========================================
echo   Fazendo push para GitHub...
echo ========================================
echo.

git push origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   Push concluido com sucesso!
    echo ========================================
    git status
) else (
    echo.
    echo ========================================
    echo   Erro ao fazer push!
    echo ========================================
    echo Verifique sua autenticacao ou conexao.
)

echo.
pause

