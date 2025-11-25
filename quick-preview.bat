@echo off
chcp 65001 >nul
echo ========================================
echo   Quick Preview - Abertura Rápida
echo ========================================
echo.

REM Configurações (AJUSTE AQUI)
set "LOCAL_URL=http://localhost/vemcomer-dev"
set "PROD_URL=https://bisque-swallow-757363.hostingersite.com"

echo Escolha onde abrir:
echo.
echo 1. Local (XAMPP)
echo 2. Produção
echo 3. Ambos
echo.
set /p choice="Digite sua escolha (1-3): "

if "%choice%"=="1" (
    echo.
    echo Abrindo local...
    start "" "%LOCAL_URL%"
    start "" "%LOCAL_URL%/wp-admin"
)

if "%choice%"=="2" (
    echo.
    echo Abrindo produção...
    start "" "%PROD_URL%"
    start "" "%PROD_URL%/wp-admin"
)

if "%choice%"=="3" (
    echo.
    echo Abrindo ambos...
    start "" "%LOCAL_URL%"
    start "" "%PROD_URL%"
    start "" "%LOCAL_URL%/wp-admin"
    start "" "%PROD_URL%/wp-admin"
)

echo.
echo Pressione qualquer tecla para continuar...
pause >nul


