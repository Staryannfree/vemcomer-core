@echo off
chcp 65001 >nul
echo ========================================
echo   Watch Mode - Deploy Automático
echo ========================================
echo.
echo Este script monitora mudanças nos arquivos
echo e faz deploy automaticamente.
echo.
echo Pressione Ctrl+C para parar
echo.

REM Configurações (AJUSTE AQUI)
set "WP_PATH=D:\xampp\htdocs\vemcomer"
set "PLUGIN_PATH=%WP_PATH%\wp-content\plugins\vemcomer-core"
set "THEME_PATH=%WP_PATH%\wp-content\themes\theme-vemcomer"

:loop
echo.
echo [%TIME%] Verificando mudanças...

REM Verificar se há arquivos modificados nos últimos 5 segundos
for /f "delims=" %%i in ('forfiles /P "inc" /S /M *.php /C "cmd /c if @fdate==%date% if @ftime GTR %time:~0,8% echo @path" 2^>nul') do (
    echo Arquivo modificado: %%i
    echo Copiando para plugin...
    copy /Y "%%i" "%PLUGIN_PATH%\%%~pi" >nul 2>&1
)

for /f "delims=" %%i in ('forfiles /P "theme-vemcomer" /S /M *.php /C "cmd /c if @fdate==%date% if @ftime GTR %time:~0,8% echo @path" 2^>nul') do (
    echo Arquivo modificado: %%i
    echo Copiando para tema...
    copy /Y "%%i" "%THEME_PATH%\%%~pi" >nul 2>&1
)

timeout /t 5 /nobreak >nul
goto loop


